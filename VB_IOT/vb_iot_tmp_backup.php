<?php
// Keep read/unread state across browser restarts.
$sessionLifetime = 30 * 24 * 60 * 60;
ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
ini_set('session.cookie_lifetime', (string)$sessionLifetime);
session_name('DASHBOARD_VB_IOT_SESSID');
$sessionCookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/dashboard',
    'domain' => $sessionCookieParams['domain'] ?? '',
    'secure' => (bool)($sessionCookieParams['secure'] ?? false),
    'httponly' => (bool)($sessionCookieParams['httponly'] ?? true),
    'samesite' => $sessionCookieParams['samesite'] ?? 'Lax',
]);
session_start();
require_once __DIR__ . '/../DB/connect_DB.php';
require_once __DIR__ . '/vb_iot_sql.php';

function normalize_date_input(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
        return $value;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m) === 1) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    return '';
}

function json_exit(array $payload): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function open_local_dashboard_conn(): ?mysqli
{
    global $localHost, $localUser, $localPass, $localDbName, $localPort;

    $host = (string)$localHost;
    $user = (string)$localUser;
    $pass = (string)$localPass;
    $db = (string)$localDbName;
    $port = (int)$localPort;

    $local_conn = connect_mysql($host, $user, $pass, $db, $port);
    if ($local_conn !== null) {
        @mysqli_query($local_conn, "SET SESSION max_allowed_packet=134217728");
        return $local_conn;
    }

    $connect_err = db_last_mysql_connect_error();
    if (stripos($connect_err, 'Unknown database') !== false) {
        $server_conn = connect_mysql_without_db($host, $user, $pass, $port);
        if ($server_conn !== null) {
            $db_escaped = str_replace('`', '``', $db);
            $create_db_sql = "CREATE DATABASE IF NOT EXISTS `{$db_escaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if ($server_conn->query($create_db_sql) !== false) {
                $server_conn->close();
                $local_conn_retry = connect_mysql($host, $user, $pass, $db, $port);
                if ($local_conn_retry !== null) {
                    @mysqli_query($local_conn_retry, "SET SESSION max_allowed_packet=134217728");
                }
                return $local_conn_retry;
            }
            $GLOBALS['dashboard_last_mysql_connect_error'] = (string)$server_conn->error;
            $server_conn->close();
        }
    }

    return null;
}

function fetch_archive_types(mysqli $local_conn, ?string &$err = null): array
{
    $candidates = [
        "SELECT maPl AS ma, tenPl AS ten FROM classify ORDER BY maPl ASC"
    ];

    foreach ($candidates as $sql) {
        $result = $local_conn->query($sql);
        if ($result === false) {
            continue;
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $ma = trim((string)($row['ma'] ?? ''));
            if ($ma === '') {
                continue;
            }
            $rows[] = [
                'ma' => $ma,
                'ten' => trim((string)($row['ten'] ?? $ma)),
            ];
        }
        $result->close();
        return $rows;
    }

    $err = $local_conn->error !== '' ? $local_conn->error : 'Khong doc duoc danh sach classify.';
    return [];
}

function append_blob_chunks(mysqli $conn, int $docId, string $typeId, string $blobData, int $chunkSize = 128000): bool
{
    $append_sql = "UPDATE save_eoffice SET duLieu = CONCAT(duLieu, UNHEX(?)) WHERE maSo = ? AND maPl = ?";
    $append_stmt = $conn->prepare($append_sql);
    if ($append_stmt === false) {
        return false;
    }

    $length = strlen($blobData);
    if ($length === 0) {
        $chunkHex = '';
        $append_stmt->bind_param('sis', $chunkHex, $docId, $typeId);
        $ok = $append_stmt->execute();
        $append_stmt->close();
        return $ok;
    }

    $ok = true;
    for ($offset = 0; $offset < $length; $offset += $chunkSize) {
        $chunk = substr($blobData, $offset, $chunkSize);
        if ($chunk === false) {
            $chunk = '';
        }
        $chunkHex = bin2hex($chunk);
        $append_stmt->bind_param('sis', $chunkHex, $docId, $typeId);
        if (!$append_stmt->execute()) {
            $ok = false;
            break;
        }
    }
    $append_stmt->close();
    return $ok;
}

function archive_lock_name(int $docId, string $typeId): string
{
    return 'save_eoffice:' . $docId . ':' . md5($typeId);
}

function acquire_archive_lock(mysqli $conn, string $lockName, int $timeoutSeconds = 10): bool
{
    $lock_sql = "SELECT GET_LOCK(?, ?) AS locked_ok";
    $lock_stmt = $conn->prepare($lock_sql);
    if ($lock_stmt === false) {
        return false;
    }
    $lock_stmt->bind_param('si', $lockName, $timeoutSeconds);
    $lock_stmt->execute();
    $row = $lock_stmt->get_result()->fetch_assoc();
    $lock_stmt->close();
    return (int)($row['locked_ok'] ?? 0) === 1;
}

function release_archive_lock(mysqli $conn, string $lockName): void
{
    $unlock_sql = "SELECT RELEASE_LOCK(?)";
    $unlock_stmt = $conn->prepare($unlock_sql);
    if ($unlock_stmt === false) {
        return;
    }
    $unlock_stmt->bind_param('s', $lockName);
    $unlock_stmt->execute();
    $unlock_stmt->close();
}

$keyword = trim($_GET['keyword'] ?? '');
$legacy_doc_symbol = trim($_GET['doc_symbol'] ?? '');
if ($keyword === '' && $legacy_doc_symbol !== '') {
    $keyword = $legacy_doc_symbol;
}
$doc_no = trim((string)($_GET['doc_no'] ?? ''));
$index_url = '/dashboard/VB_IOT/vb_iot.php';
$default_from_date = date('Y-m-01');
$default_to_date = date('Y-m-d');
$current_month = (int)date('n');

if (isset($_GET['api'])) {
    $api = trim((string)$_GET['api']);

    if ($api === 'archive_types') {
        $local_conn = open_local_dashboard_conn();
        if ($local_conn === null) {
            $detail = db_last_mysql_connect_error();
            json_exit(['ok' => false, 'message' => 'Khong ket noi duoc local DB 127.0.0.1/dashboard_my. ' . $detail, 'rows' => []]);
        }
        $local_err = '';
        $type_rows = fetch_archive_types($local_conn, $local_err);
        $local_conn->close();
        if ($local_err !== '' && empty($type_rows)) {
            json_exit(['ok' => false, 'message' => $local_err, 'rows' => []]);
        }
        json_exit(['ok' => true, 'rows' => $type_rows]);
    }

    if ($api === 'archive_save') {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            json_exit(['ok' => false, 'message' => 'Chi ho tro POST.']);
        }
        $doc_id = max(0, (int)($_POST['maso'] ?? 0));
        $type_id = trim((string)($_POST['maPl'] ?? $_POST['ma_phanloai'] ?? ''));
        if ($doc_id <= 0 || $type_id === '') {
            json_exit(['ok' => false, 'message' => 'Thieu maSo hoac maPl.']);
        }

        $source_sql = vb_iot_source_file_sql();
        $source_stmt = db_mysqli_prepare($conn, $source_sql);
        if ($source_stmt === false) {
            json_exit(['ok' => false, 'message' => 'Khong prepare duoc truy van nguon: ' . $conn->error]);
        }
        $source_stmt->bind_param('i', $doc_id);
        $source_stmt->execute();
        $source_result = $source_stmt->get_result();
        $source_row = $source_result ? $source_result->fetch_assoc() : null;
        $source_stmt->close();

        if (!$source_row || !isset($source_row['duLieu']) || $source_row['duLieu'] === null || $source_row['duLieu'] === '') {
            json_exit(['ok' => false, 'message' => 'Khong tim thay tep du lieu tu nguon.']);
        }

        $local_conn = open_local_dashboard_conn();
        if ($local_conn === null) {
            $detail = db_last_mysql_connect_error();
            json_exit(['ok' => false, 'message' => 'Khong ket noi duoc local DB 127.0.0.1/dashboard_my. ' . $detail]);
        }

        $title = (string)($source_row['tieude'] ?? '');
        $ngay_gui = (string)($source_row['ngaytao'] ?? '');
        $tenfile = (string)($source_row['tenFile'] ?? '');
        $kieufile = (string)($source_row['kieuFIile'] ?? '');
        $dulieu = $source_row['duLieu'];
        $dulieu_len = strlen((string)$dulieu);

        $packet_limit = 0;
        $packet_result = $local_conn->query("SELECT @@max_allowed_packet AS max_packet");
        if ($packet_result !== false) {
            $packet_row = $packet_result->fetch_assoc();
            $packet_result->close();
            $packet_limit = (int)($packet_row['max_packet'] ?? 0);
        }
        if ($packet_limit > 0 && $dulieu_len > max(0, $packet_limit - 65536)) {
            $local_conn->close();
            json_exit([
                'ok' => false,
                'message' => 'Khong luu duoc du lieu: file qua lon so voi max_allowed_packet hien tai (' . number_format($packet_limit) . ' bytes). Can tang max_allowed_packet tren MySQL local.'
            ]);
        }

        $lock_name = archive_lock_name($doc_id, $type_id);
        if (!acquire_archive_lock($local_conn, $lock_name, 10)) {
            $local_conn->close();
            json_exit(['ok' => false, 'message' => 'He thong dang ban luu file nay. Vui long thu lai sau.']);
        }

        if (!$local_conn->begin_transaction()) {
            $msg = $local_conn->error;
            release_archive_lock($local_conn, $lock_name);
            $local_conn->close();
            json_exit(['ok' => false, 'message' => 'Khong mo duoc transaction local: ' . $msg]);
        }

        $delete_sql = vb_iot_archive_delete_by_key_sql();
        $delete_stmt = $local_conn->prepare($delete_sql);
        if ($delete_stmt === false) {
            $msg = $local_conn->error;
            $local_conn->rollback();
            release_archive_lock($local_conn, $lock_name);
            $local_conn->close();
            json_exit(['ok' => false, 'message' => 'Loi xoa du lieu trung save_eoffice: ' . $msg]);
        }
        $delete_stmt->bind_param('is', $doc_id, $type_id);
        $ok_delete = $delete_stmt->execute();
        $delete_err = $delete_stmt->error;
        $delete_stmt->close();
        if ($ok_delete === false) {
            $local_conn->rollback();
            release_archive_lock($local_conn, $lock_name);
            $local_conn->close();
            json_exit(['ok' => false, 'message' => 'Khong xoa duoc du lieu cu: ' . $delete_err]);
        }

        $save_sql = vb_iot_archive_insert_sql();
        $save_stmt = $local_conn->prepare($save_sql);
        if ($save_stmt === false) {
            $msg = $local_conn->error;
            $local_conn->rollback();
            release_archive_lock($local_conn, $lock_name);
            $local_conn->close();
            json_exit(['ok' => false, 'message' => 'Loi insert save_eoffice: ' . $msg]);
        }
        $empty_blob = '';
        $save_stmt->bind_param('sisssss', $type_id, $doc_id, $tenfile, $kieufile, $title, $ngay_gui, $empty_blob);
        $ok_head = $save_stmt->execute();
        $save_err = $save_stmt->error;
        $save_stmt->close();
        if ($ok_head === false) {
            $local_conn->rollback();
            release_archive_lock($local_conn, $lock_name);
            $local_conn->close();
            json_exit(['ok' => false, 'message' => 'Khong luu du lieu dau muc: ' . $save_err]);
        }
        $ok_save = append_blob_chunks($local_conn, $doc_id, $type_id, (string)$dulieu);
        if ($ok_save === false) {
            $local_conn->rollback();
            release_archive_lock($local_conn, $lock_name);
            $local_conn->close();
            json_exit(['ok' => false, 'message' => 'Khong luu duoc du lieu blob theo chunk. Kiem tra kieu cot duLieu va quyen ghi local DB.']);
        }
        if (!$local_conn->commit()) {
            $msg = $local_conn->error;
            $local_conn->rollback();
            release_archive_lock($local_conn, $lock_name);
            $local_conn->close();
            json_exit(['ok' => false, 'message' => 'Loi commit du lieu local: ' . $msg]);
        }
        release_archive_lock($local_conn, $lock_name);
        $local_conn->close();

        json_exit(['ok' => true, 'message' => 'Đã lưu thành công vào kho lưu trữ local.']);
    }

    if ($api === 'archive_list') {
        $type_id = trim((string)($_GET['maPl'] ?? $_GET['ma_phanloai'] ?? ''));
        if ($type_id === '') {
            json_exit(['ok' => false, 'message' => 'Thieu maPl.', 'rows' => []]);
        }
        $local_conn = open_local_dashboard_conn();
        if ($local_conn === null) {
            $detail = db_last_mysql_connect_error();
            json_exit(['ok' => false, 'message' => 'Khong ket noi duoc local DB 127.0.0.1/dashboard_my. ' . $detail, 'rows' => []]);
        }
        $list_sql = vb_iot_archive_list_sql();
        $list_stmt = $local_conn->prepare($list_sql);
        if ($list_stmt === false) {
            $msg = $local_conn->error;
            $local_conn->close();
            json_exit(['ok' => false, 'message' => 'Loi doc save_eoffice: ' . $msg, 'rows' => []]);
        }
        $list_stmt->bind_param('s', $type_id);
        $list_stmt->execute();
        $list_result = $list_stmt->get_result();
        $list_rows = [];
        while ($list_result && ($row = $list_result->fetch_assoc())) {
            $list_rows[] = [
                'maSo' => (int)($row['maSo'] ?? 0),
                'maPl' => (string)($row['maPl'] ?? ''),
                'tenFile' => (string)($row['tenFile'] ?? ''),
                'kieuFIile' => (string)($row['kieuFIile'] ?? ''),
                'tieuDe' => (string)($row['tieuDe'] ?? ''),
                'ngayGui' => (string)($row['ngayGui'] ?? ''),
            ];
        }
        $list_stmt->close();
        $local_conn->close();
        json_exit(['ok' => true, 'rows' => $list_rows, 'total' => count($list_rows)]);
    }

    if ($api === 'archive_raw') {
        $doc_id = max(0, (int)($_GET['maso'] ?? 0));
        $type_id = trim((string)($_GET['maPl'] ?? $_GET['ma_phanloai'] ?? ''));
        if ($doc_id <= 0 || $type_id === '') {
            http_response_code(400);
            echo 'Thieu maSo hoac maPl.';
            exit;
        }

        $local_conn = open_local_dashboard_conn();
        if ($local_conn === null) {
            http_response_code(500);
            echo 'Khong ket noi duoc local DB. ' . db_last_mysql_connect_error();
            exit;
        }

        $raw_sql = vb_iot_archive_raw_sql();
        $raw_stmt = $local_conn->prepare($raw_sql);
        if ($raw_stmt === false) {
            $msg = $local_conn->error;
            $local_conn->close();
            http_response_code(500);
            echo 'Loi query local: ' . $msg;
            exit;
        }
        $raw_stmt->bind_param('is', $doc_id, $type_id);
        $raw_stmt->execute();
        $raw_result = $raw_stmt->get_result();
        $raw_row = $raw_result ? $raw_result->fetch_assoc() : null;
        $raw_stmt->close();
        $local_conn->close();

        if (!$raw_row || !isset($raw_row['duLieu']) || $raw_row['duLieu'] === null || $raw_row['duLieu'] === '') {
            http_response_code(404);
            echo 'Khong tim thay tep trong kho luu tru local.';
            exit;
        }
        $mime = trim((string)($raw_row['kieuFIile'] ?? ''));
        if ($mime === '' || strpos($mime, '/') === false) {
            $mime = 'application/octet-stream';
        }
        $file_name = trim((string)($raw_row['tenFile'] ?? ''));
        if ($file_name === '') {
            $file_name = 'archive-' . $doc_id . '.bin';
        }
        $ascii_name = preg_replace('/[^A-Za-z0-9._-]/', '-', $file_name) ?? 'archive.bin';
        if (trim($ascii_name, '-') === '') {
            $ascii_name = 'archive.bin';
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $ascii_name . '"; filename*=UTF-8\'\'' . rawurlencode($file_name));
        header('Content-Length: ' . strlen((string)$raw_row['duLieu']));
        echo $raw_row['duLieu'];
        exit;
    }
}

$year_options = [];
$year_sql = vb_iot_year_options_sql();
$year_result = db_mysqli_query($conn, $year_sql);
if ($year_result !== false) {
    while ($year_row = $year_result->fetch_assoc()) {
        $y = (int)($year_row['y'] ?? 0);
        if ($y > 0) {
            $year_options[] = $y;
        }
    }
    $year_result->close();
}
if (empty($year_options)) {
    $year_options[] = (int)date('Y');
}
$latest_year = (int)$year_options[0];

$date_mode = trim($_GET['date_mode'] ?? 'range');
if (!in_array($date_mode, ['range', 'year', 'month', 'all'], true)) {
    $date_mode = 'range';
}

$filter_year_raw = trim($_GET['filter_year'] ?? '');
$filter_year = ctype_digit($filter_year_raw) ? (int)$filter_year_raw : $latest_year;
if (!in_array($filter_year, $year_options, true)) {
    $filter_year = $latest_year;
}

$filter_month_raw = trim($_GET['filter_month'] ?? '');
$filter_month = ctype_digit($filter_month_raw) ? (int)$filter_month_raw : $current_month;
if ($filter_month < 1 || $filter_month > 12) {
    $filter_month = $current_month;
}

$mark_all_read = isset($_GET['mark_all_read']) && $_GET['mark_all_read'] === '1';
$mark_all_pages_read = isset($_GET['mark_all_pages_read']) && $_GET['mark_all_pages_read'] === '1';
$reset_read = isset($_GET['reset_read']) && $_GET['reset_read'] === '1';

$has_from_date = array_key_exists('from_date', $_GET);
$has_to_date = array_key_exists('to_date', $_GET);
$date_mode_requested = trim($_GET['date_mode'] ?? '');
$has_any_filter_key = array_key_exists('keyword', $_GET)
    || array_key_exists('doc_symbol', $_GET)
    || $has_from_date
    || $has_to_date
    || array_key_exists('date_mode', $_GET)
    || array_key_exists('filter_year', $_GET)
    || array_key_exists('filter_month', $_GET)
    || array_key_exists('page', $_GET)
    || array_key_exists('mark_all_read', $_GET)
    || array_key_exists('mark_all_pages_read', $_GET)
    || array_key_exists('reset_read', $_GET);

$show_all_mode = false;

if ($show_all_mode) {
    $date_mode = 'range';
    $from_date = '';
    $to_date = '';
} elseif ($date_mode === 'all') {
    $from_date = '';
    $to_date = '';
} elseif ($date_mode === 'range' && !$has_from_date && !$has_to_date) {
    $from_date = $default_from_date;
    $to_date = $default_to_date;
} else {
    $from_date = normalize_date_input((string)($_GET['from_date'] ?? ''));
    $to_date = normalize_date_input((string)($_GET['to_date'] ?? ''));
}
$page = max(1, (int)($_GET['page'] ?? 1));

$canonical_params = [];
if ($keyword !== '') {
    $canonical_params['keyword'] = $keyword;
}
if ($doc_no !== '') {
    $canonical_params['doc_no'] = $doc_no;
}
if (!$show_all_mode && $date_mode !== 'all') {
    if ($date_mode === 'range') {
        if ($from_date !== '') {
            $canonical_params['from_date'] = $from_date;
        }
        if ($to_date !== '') {
            $canonical_params['to_date'] = $to_date;
        }
    } elseif ($date_mode === 'year') {
        $canonical_params['date_mode'] = 'year';
        if ($filter_year !== $latest_year) {
            $canonical_params['filter_year'] = $filter_year;
        }
    } elseif ($date_mode === 'month') {
        $canonical_params['date_mode'] = 'month';
        if ($filter_year !== $latest_year) {
            $canonical_params['filter_year'] = $filter_year;
        }
        if ($filter_month !== $current_month) {
            $canonical_params['filter_month'] = $filter_month;
        }
    }
} elseif ($date_mode === 'all') {
    $canonical_params['date_mode'] = 'all';
}
if ($page > 1) {
    $canonical_params['page'] = $page;
}

$relevant_keys = ['keyword', 'doc_symbol', 'doc_no', 'from_date', 'to_date', 'date_mode', 'filter_year', 'filter_month', 'page'];
$current_params = [];
foreach ($relevant_keys as $key) {
    if (array_key_exists($key, $_GET)) {
        $current_params[$key] = (string)$_GET[$key];
    }
}
$canonical_query = http_build_query($canonical_params);
$current_query = http_build_query($current_params);
if ($canonical_query !== $current_query) {
    $target = $index_url . ($canonical_query !== '' ? '?' . $canonical_query : '');
    $_SESSION['index_return_url'] = $target;
    $_SESSION['index_last_query'] = $canonical_query;
    if (!$mark_all_read && !$mark_all_pages_read && !$reset_read) {
        header('Location: ' . $target);
        exit;
    }
}

$per_page = 40;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];
$types = '';

$where[] = 'EXISTS (SELECT 1 FROM eoffice_approval_file f WHERE f.maso = d.maso)';

if ($keyword !== '') {
    $where[] = '(d.tieude LIKE ? OR d.sokyhieu LIKE ?)';
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
    $types .= 'ss';
}
if ($doc_no !== '') {
    $where[] = vb_iot_receiver_filter_sql();
    $params[] = '%' . $doc_no . '%';
    $types .= 's';
}

if (!$show_all_mode && $date_mode !== 'all') {
    if ($date_mode === 'year') {
        $where[] = 'YEAR(d.ngaytao) = ?';
        $params[] = $filter_year;
        $types .= 'i';
    } elseif ($date_mode === 'month') {
        $where[] = 'YEAR(d.ngaytao) = ?';
        $params[] = $filter_year;
        $types .= 'i';
        $where[] = 'MONTH(d.ngaytao) = ?';
        $params[] = $filter_month;
        $types .= 'i';
    } else {
        if ($from_date !== '' && $to_date === '') {
            $where[] = 'DATE(d.ngaytao) = ?';
            $params[] = $from_date;
            $types .= 's';
        } else {
            if ($from_date !== '') {
                $where[] = 'DATE(d.ngaytao) >= ?';
                $params[] = $from_date;
                $types .= 's';
            }

            if ($to_date !== '') {
                $where[] = 'DATE(d.ngaytao) <= ?';
                $params[] = $to_date;
                $types .= 's';
            }
        }
    }
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where);
}

if ($mark_all_read) {
    $mark_sql = vb_iot_mark_page_sql($where_sql);
    $mark_stmt = db_mysqli_prepare($conn, $mark_sql);
    if ($mark_stmt === false) {
        die('Loi prepare MARK ALL READ: ' . $conn->error);
    }
    $mark_params = $params;
    $mark_params[] = $per_page;
    $mark_params[] = $offset;
    $mark_types = $types . 'ii';
    $mark_stmt->bind_param($mark_types, ...$mark_params);
    $mark_stmt->execute();
    $mark_result = $mark_stmt->get_result();
    if (!isset($_SESSION['read_docs']) || !is_array($_SESSION['read_docs'])) {
        $_SESSION['read_docs'] = [];
    }
    while ($mark_row = $mark_result->fetch_assoc()) {
        $mark_id = (int)($mark_row['maso'] ?? 0);
        if ($mark_id > 0 && !in_array($mark_id, $_SESSION['read_docs'], true)) {
            $_SESSION['read_docs'][] = $mark_id;
        }
    }
    $mark_stmt->close();

    $target = $index_url . ($canonical_query !== '' ? '?' . $canonical_query : '');
    $_SESSION['index_return_url'] = $target;
    $_SESSION['index_last_query'] = $canonical_query;
    header('Location: ' . $target);
    exit;
}

if ($mark_all_pages_read) {
    $mark_all_pages_sql = vb_iot_mark_all_pages_sql($where_sql);
    $mark_all_pages_stmt = db_mysqli_prepare($conn, $mark_all_pages_sql);
    if ($mark_all_pages_stmt === false) {
        die('Loi prepare MARK ALL READ ALL PAGES: ' . $conn->error);
    }
    if (!empty($params)) {
        $mark_all_pages_stmt->bind_param($types, ...$params);
    }
    $mark_all_pages_stmt->execute();
    $mark_all_pages_result = $mark_all_pages_stmt->get_result();
    if (!isset($_SESSION['read_docs']) || !is_array($_SESSION['read_docs'])) {
        $_SESSION['read_docs'] = [];
    }
    while ($mark_all_pages_row = $mark_all_pages_result->fetch_assoc()) {
        $mark_all_pages_id = (int)($mark_all_pages_row['maso'] ?? 0);
        if ($mark_all_pages_id > 0 && !in_array($mark_all_pages_id, $_SESSION['read_docs'], true)) {
            $_SESSION['read_docs'][] = $mark_all_pages_id;
        }
    }
    $mark_all_pages_stmt->close();

    $target = $index_url . ($canonical_query !== '' ? '?' . $canonical_query : '');
    $_SESSION['index_return_url'] = $target;
    $_SESSION['index_last_query'] = $canonical_query;
    header('Location: ' . $target);
    exit;
}

if ($reset_read) {
    $reset_sql = vb_iot_mark_page_sql($where_sql);
    $reset_stmt = db_mysqli_prepare($conn, $reset_sql);
    if ($reset_stmt === false) {
        die('Loi prepare RESET READ PAGE: ' . $conn->error);
    }
    $reset_params = $params;
    $reset_params[] = $per_page;
    $reset_params[] = $offset;
    $reset_types = $types . 'ii';
    $reset_stmt->bind_param($reset_types, ...$reset_params);
    $reset_stmt->execute();
    $reset_result = $reset_stmt->get_result();

    $page_ids = [];
    while ($reset_row = $reset_result->fetch_assoc()) {
        $rid = (int)($reset_row['maso'] ?? 0);
        if ($rid > 0) {
            $page_ids[] = $rid;
        }
    }
    $reset_stmt->close();

    if (!isset($_SESSION['read_docs']) || !is_array($_SESSION['read_docs'])) {
        $_SESSION['read_docs'] = [];
    }
    if (!empty($page_ids)) {
        $page_map = array_flip($page_ids);
        $_SESSION['read_docs'] = array_values(array_filter(
            array_map('intval', (array)$_SESSION['read_docs']),
            static function (int $id) use ($page_map): bool {
                return $id > 0 && !isset($page_map[$id]);
            }
        ));
    }

    $target = $index_url . ($canonical_query !== '' ? '?' . $canonical_query : '');
    $_SESSION['index_return_url'] = $target;
    $_SESSION['index_last_query'] = $canonical_query;
    header('Location: ' . $target);
    exit;
}


$count_sql = vb_iot_count_sql($where_sql);

$count_stmt = db_mysqli_prepare($conn, $count_sql);
if ($count_stmt === false) {
    die('Loi prepare COUNT: ' . $conn->error);
}
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$count_stmt->close();

$total_pages = max(1, (int)ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
    $normalized_params = $canonical_params;
    if ($page > 1) {
        $normalized_params['page'] = $page;
    } else {
        unset($normalized_params['page']);
    }
    $normalized_query = http_build_query($normalized_params);
    $target = $index_url . ($normalized_query !== '' ? '?' . $normalized_query : '');
    header('Location: ' . $target);
    exit;
}

if ($page > 1) {
    $offset = ($page - 1) * $per_page;
}

$final_params = $canonical_params;
if ($page > 1) {
    $final_params['page'] = $page;
} else {
    unset($final_params['page']);
}
$final_query = http_build_query($final_params);
$_SESSION['index_return_url'] = $index_url . ($final_query !== '' ? '?' . $final_query : '');
$_SESSION['index_last_query'] = $final_query;

$list_sql = vb_iot_list_sql($where_sql);

$list_stmt = db_mysqli_prepare($conn, $list_sql);
if ($list_stmt === false) {
    die('Loi prepare LIST: ' . $conn->error);
}
$list_params = $params;
$list_params[] = $per_page;
$list_params[] = $offset;
$list_types = $types . 'ii';
$list_stmt->bind_param($list_types, ...$list_params);
$list_stmt->execute();
$result = $list_stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

$receiver_map = [];
if (!empty($rows)) {
    $doc_ids = array_values(array_unique(array_map(static function (array $row): int {
        return (int)($row['maso'] ?? 0);
    }, $rows)));
    $doc_ids = array_values(array_filter($doc_ids, static function (int $id): bool {
        return $id > 0;
    }));

    if (!empty($doc_ids)) {
        $receiver_placeholders = implode(',', array_fill(0, count($doc_ids), '?'));
        $receiver_sql = vb_iot_receiver_sql($receiver_placeholders);
        $receiver_stmt = db_mysqli_prepare($conn, $receiver_sql);
        if ($receiver_stmt !== false) {
            $receiver_types = str_repeat('i', count($doc_ids));
            $receiver_stmt->bind_param($receiver_types, ...$doc_ids);
            $receiver_stmt->execute();
            $receiver_result = $receiver_stmt->get_result();
            while ($receiver_row = $receiver_result->fetch_assoc()) {
                $receiver_doc_id = (int)($receiver_row['maso'] ?? 0);
                if ($receiver_doc_id <= 0) {
                    continue;
                }
                if (!isset($receiver_map[$receiver_doc_id])) {
                    $receiver_map[$receiver_doc_id] = [];
                }
                $receiver_map[$receiver_doc_id][] = [
                    'poscd' => (string)($receiver_row['poscd'] ?? ''),
                    'ten' => (string)($receiver_row['ten'] ?? ''),
                ];
            }
            $receiver_stmt->close();
        }
    }
}
$receiver_json = json_encode($receiver_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($receiver_json === false) {
    $receiver_json = '{}';
}

$read_docs = $_SESSION['read_docs'] ?? [];
$read_docs = array_values(array_unique(array_map('intval', (array)$read_docs)));
$read_docs = array_values(array_filter($read_docs, static function (int $id): bool {
    return $id > 0;
}));
$read_docs_map = array_flip($read_docs);

$read_in_filter = 0;
if (!empty($read_docs)) {
    $read_placeholders = implode(',', array_fill(0, count($read_docs), '?'));
    $read_filter_sql = vb_iot_read_filter_sql($where_sql, $read_placeholders);
    $read_filter_stmt = db_mysqli_prepare($conn, $read_filter_sql);
    if ($read_filter_stmt !== false) {
        $read_filter_params = $params;
        foreach ($read_docs as $rid) {
            $read_filter_params[] = $rid;
        }
        $read_filter_types = $types . str_repeat('i', count($read_docs));
        $read_filter_stmt->bind_param($read_filter_types, ...$read_filter_params);
        $read_filter_stmt->execute();
        $read_in_filter = (int)($read_filter_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $read_filter_stmt->close();
    }
}
$unread_in_filter = max(0, $total_rows - $read_in_filter);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Danh sach van ban</title>
<link rel="stylesheet" href="../view/Styple_vb_iot.php?page=index">
</head>
<body>
<div class="container">
    <h1 class="page-title"><a href="<?php echo $index_url; ?>">Danh Sách Văn Bản VBSP-CN 34</a></h1>

    <form method="get" action="<?php echo $index_url; ?>" class="search-form">
        <input type="hidden" name="page" id="search_page" value="<?php echo $page; ?>">
        <input class="field-keyword" type="text" name="keyword" placeholder="Tìm theo tiêu đề hoặc ký hiệu văn bản" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
        <input class="field-docno" type="text" name="doc_no" placeholder="Tìm theo người nhận" value="<?php echo htmlspecialchars($doc_no, ENT_QUOTES, 'UTF-8'); ?>">
        <input class="from-wrap field-date" type="date" name="from_date" value="<?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?>" data-default-value="<?php echo htmlspecialchars($default_from_date, ENT_QUOTES, 'UTF-8'); ?>" title="Tu ngay">
        <input class="to-wrap field-date" type="date" name="to_date" value="<?php echo htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8'); ?>" data-default-value="<?php echo htmlspecialchars($default_to_date, ENT_QUOTES, 'UTF-8'); ?>" title="Den ngay">
        <select class="combo-select condition-mode field-mode" id="date_mode" name="date_mode" title="Kieu loc ngay">
            <option value="range" <?php echo $date_mode === 'range' ? 'selected' : ''; ?>>Lọc theo ngày</option>
            <option value="year" <?php echo $date_mode === 'year' ? 'selected' : ''; ?>>Lọc theo năm</option>
            <option value="month" <?php echo $date_mode === 'month' ? 'selected' : ''; ?>>Lọc theo tháng</option>
            <option value="all" <?php echo $date_mode === 'all' ? 'selected' : ''; ?>>Tất cả</option>
        </select>
        <select class="combo-select year-wrap field-year" name="filter_year" title="Chon nam">
            <?php foreach ($year_options as $y): ?>
                <option value="<?php echo $y; ?>" <?php echo $y === $filter_year ? 'selected' : ''; ?>>Năm <?php echo $y; ?></option>
            <?php endforeach; ?>
        </select>
        <select class="combo-select month-wrap field-month" name="filter_month" title="Chon thang">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?php echo $m; ?>" <?php echo $m === $filter_month ? 'selected' : ''; ?>>Tháng <?php echo $m; ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn-mark-icon" name="mark_all_read" value="1" title="Da doc toan bo">&#10003;</button>
        <button type="submit" class="btn-search">Search</button>
        <button type="button" class="btn-archive-list" id="openArchiveListBtn" title="Xem danh sách phân loại">&#128451;</button>
    </form>

    <div class="meta">
        Tổng số: <?php echo number_format($total_rows); ?> văn bản
        | Chưa đọc: <?php echo number_format($unread_in_filter); ?> văn bản
    </div>
    <div class="quick-filter-wrap">
        <input type="text" id="tableQuickFilter" class="table-quick-filter" placeholder="Lọc nhanh trong trang hiện tại (mã số, tiêu đề, ngày...)">
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 18%;">Mã số </th>
                    <th>Tiêu đề</th>
                    <th style="width: 16%;">Ngày gửi </th>
                    <th style="width: 12%;" class="col-receiver">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="4" class="empty">Không có dữ liệu phù hợp.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $doc_id = (int)$row['maso'];
                    $title_class = isset($read_docs_map[$doc_id]) ? 'read' : 'unread';
                    $has_receivers = !empty($receiver_map[$doc_id]);
                    ?>
                    <tr>
                        <td>
                            <a class="title-link <?php echo $title_class; ?>" href="view_file.php?id=<?php echo $doc_id; ?>">
                                <?php echo htmlspecialchars((string)$row['sokyhieu'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td>
                            <a class="title-link <?php echo $title_class; ?>" href="view_file.php?id=<?php echo $doc_id; ?>">
                                <?php echo htmlspecialchars((string)$row['tieude'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime((string)$row['ngaytao'])); ?></td>
                        <td class="col-receiver-cell">
                            <div class="action-group">
                                <button
                                    type="button"
                                    class="btn-archive-popup"
                                    data-doc-id="<?php echo $doc_id; ?>"
                                    data-doc-symbol="<?php echo htmlspecialchars((string)$row['sokyhieu'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-doc-title="<?php echo htmlspecialchars((string)$row['tieude'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-doc-date="<?php echo htmlspecialchars(date('d/m/Y', strtotime((string)$row['ngaytao'])), ENT_QUOTES, 'UTF-8'); ?>"
                                    title="Lưu trữ vào local"
                                >
                                    &#128190;
                                </button>
                                <?php if ($has_receivers): ?>
                                    <button
                                        type="button"
                                        class="btn-receiver-popup"
                                        data-doc-id="<?php echo $doc_id; ?>"
                                        data-doc-symbol="<?php echo htmlspecialchars((string)$row['sokyhieu'], ENT_QUOTES, 'UTF-8'); ?>"
                                        title="Xem danh sách người nhận"
                                    >
                                        &#128269;
                                    </button>
                                <?php else: ?>
                                    <span class="receiver-empty">-</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="receiver-overlay" id="receiverOverlay" hidden>
        <div class="receiver-dialog" role="dialog" aria-modal="true" aria-labelledby="receiverDialogTitle">
            <h3 id="receiverDialogTitle">Danh sách người nhận</h3>
            <p class="receiver-doc-label" id="receiverDocLabel"></p>
            <div class="receiver-list-wrap">
                <table class="receiver-list-table">
                    <thead>
                        <tr>
                            <th style="width: 32%;">Mã đơn vị</th>
                            <th>Họ tên</th>
                        </tr>
                    </thead>
                    <tbody id="receiverListBody"></tbody>
                </table>
            </div>
            <div class="receiver-actions">
                <button type="button" class="btn-receiver-close" id="receiverCloseBtn">Đóng</button>
            </div>
        </div>
    </div>

    <div class="ls-overlay" id="lsOverlay" hidden>
        <div class="ls-dialog" id="lsDialog" role="dialog" aria-modal="true" aria-labelledby="lsDialogTitle">
            <h3 id="lsDialogTitle" class="ls-drag-handle">LS - Lưu trữ văn bản</h3>
            <div class="ls-content">
                <div class="ls-filter-row">
                    <label for="lsType" class="ls-label">Phân loại</label>
                    <select id="lsType" class="ls-type-select">
                        <option value="">Chọn phân loại</option>
                    </select>
                    <input type="text" id="lsQuickFilter" class="ls-quick-filter" placeholder="Tìm nhanh trong danh sách lưu trữ">
                </div>
                <div class="ls-doc-info">
                    <div><strong>Mã số:</strong> <span id="lsDocSymbol">-</span></div>
                    <div><strong>Ngày gửi:</strong> <span id="lsDocDate">-</span></div>
                    <div><strong>Tiêu đề:</strong> <span id="lsDocTitle">-</span></div>
                </div>
                <div id="lsStatus" class="ls-status">Chọn phân loại rồi bấm Lưu.</div>
                <div class="ls-actions">
                    <button type="button" id="lsSaveBtn" class="ls-save-btn">Lưu</button>
                    <button type="button" id="lsCloseBtn" class="ls-close-btn">Đóng</button>
                </div>
                <div class="ls-list-wrap">
                    <table class="ls-list-table">
                        <thead>
                            <tr>
                                <th style="width: 18%;">Ngày gửi</th>
                                <th class="ls-col-title">Tiên file</th>
                                <th style="width: 24%;">Văn bản số</th>
                                <th style="width: 14%;">Xem</th>
                            </tr>
                        </thead>
                        <tbody id="lsListBody">
                            <tr><td colspan="4" class="ls-empty">Chưa tải dữ liệu.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="bottom-bar">
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            $base_query = $canonical_params;
            unset($base_query['page']);

            if ($page > 1):
                $base_query['page'] = $page - 1;
            ?>
                <a class="nav" href="<?php echo $index_url; ?>?<?php echo htmlspecialchars(http_build_query($base_query), ENT_QUOTES, 'UTF-8'); ?>">&lsaquo;</a>
            <?php else: ?>
                <span class="nav">&lsaquo;</span>
            <?php endif; ?>

            <?php
            $pages = [];

            if ($total_pages <= 11) {
                for ($i = 1; $i <= $total_pages; $i++) {
                    $pages[] = $i;
                }
            } elseif ($page <= 5) {
                for ($i = 1; $i <= 8; $i++) {
                    $pages[] = $i;
                }
                $pages[] = '...';
                $pages[] = $total_pages - 1;
                $pages[] = $total_pages;
            } elseif ($page >= $total_pages - 4) {
                $pages[] = 1;
                $pages[] = 2;
                $pages[] = '...';
                for ($i = $total_pages - 7; $i <= $total_pages; $i++) {
                    $pages[] = $i;
                }
            } else {
                $pages[] = 1;
                $pages[] = 2;
                $pages[] = '...';
                for ($i = $page - 2; $i <= $page + 2; $i++) {
                    $pages[] = $i;
                }
                $pages[] = '...';
                $pages[] = $total_pages - 1;
                $pages[] = $total_pages;
            }

            foreach ($pages as $item):
                if ($item === '...'):
            ?>
                <span class="ellipsis">...</span>
            <?php
                else:
                    $base_query['page'] = $item;
                    if ((int)$item === $page):
            ?>
                <span class="current"><?php echo $item; ?></span>
            <?php
                    else:
            ?>
                <a href="<?php echo $index_url; ?>?<?php echo htmlspecialchars(http_build_query($base_query), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $item; ?></a>
            <?php
                    endif;
                endif;
            endforeach;
            if ($page < $total_pages):
                $base_query['page'] = $page + 1;
            ?>
                <a class="nav" href="<?php echo $index_url; ?>?<?php echo htmlspecialchars(http_build_query($base_query), ENT_QUOTES, 'UTF-8'); ?>">&rsaquo;</a>
            <?php else: ?>
                <span class="nav">&rsaquo;</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
        <form method="get" action="<?php echo $index_url; ?>" class="reset-read-form">
            <?php foreach ($canonical_params as $key => $value): ?>
                <input type="hidden" name="<?php echo htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn-mark-icon" name="mark_all_pages_read" value="1" title="Da doc tat ca cac trang">&#10003;&#10003;</button>
            <button type="submit" class="btn-reset-read-icon" name="reset_read" value="1" title="Reset da doc">&#8635;</button>
        </form>
    </div>
</div>
<script>
(function () {
    var form = document.querySelector('.search-form');
    var mode = document.getElementById('date_mode');
    var searchPageInput = document.getElementById('search_page');
    if (!mode) {
        return;
    }

    var fromWrap = document.querySelector('.from-wrap');
    var toWrap = document.querySelector('.to-wrap');
    var yearWrap = document.querySelector('.year-wrap');
    var monthWrap = document.querySelector('.month-wrap');

    function toggleDateFilters() {
        var value = mode.value;
        var isRange = value === 'range';
        var isYear = value === 'year';
        var isMonth = value === 'month';

        if (fromWrap) {
            fromWrap.style.display = isRange ? '' : 'none';
        }
        if (toWrap) {
            toWrap.style.display = isRange ? '' : 'none';
        }
        if (yearWrap) {
            yearWrap.style.display = (isYear || isMonth) ? '' : 'none';
        }
        if (monthWrap) {
            monthWrap.style.display = isMonth ? '' : 'none';
        }
    }

    mode.addEventListener('change', toggleDateFilters);
    toggleDateFilters();

    if (form && searchPageInput) {
        form.addEventListener('submit', function (event) {
            var submitter = event.submitter || null;
            var isMarkAll = submitter && submitter.name === 'mark_all_read';
            searchPageInput.value = isMarkAll ? '<?php echo $page; ?>' : '1';
        });
    }
})();
</script>
<script>
(function () {
    var quickInput = document.getElementById('tableQuickFilter');
    var table = document.querySelector('.table-wrap table');
    if (!quickInput || !table) {
        return;
    }

    var tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }

    function applyQuickFilter() {
        var query = (quickInput.value || '').toLowerCase().trim();
        var rows = tbody.querySelectorAll('tr');
        var visibleCount = 0;

        rows.forEach(function (row) {
            if (row.querySelector('.empty')) {
                row.style.display = '';
                return;
            }
            var text = (row.textContent || '').toLowerCase();
            var show = query === '' || text.indexOf(query) !== -1;
            row.style.display = show ? '' : 'none';
            if (show) {
                visibleCount += 1;
            }
        });

        if (rows.length > 0 && !tbody.querySelector('.empty')) {
            var emptyRow = tbody.querySelector('.quick-empty-row');
            if (visibleCount === 0) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'quick-empty-row';
                    emptyRow.innerHTML = '<td colspan="4" class="empty">Không có dữ liệu phù hợp bộ lọc nhanh.</td>';
                    tbody.appendChild(emptyRow);
                }
            } else if (emptyRow) {
                emptyRow.remove();
            }
        }
    }

    quickInput.addEventListener('input', applyQuickFilter);
})();
</script>
<script>
(function () {
    try {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage('vb_iot:refresh_unread', '*');
        }
    } catch (e) {}
})();
</script>
<script>
(function () {
    var receiverMap = <?php echo $receiver_json; ?>;
    var overlay = document.getElementById('receiverOverlay');
    var closeBtn = document.getElementById('receiverCloseBtn');
    var listBody = document.getElementById('receiverListBody');
    var docLabel = document.getElementById('receiverDocLabel');
    var triggerButtons = document.querySelectorAll('.btn-receiver-popup');

    if (!overlay || !closeBtn || !listBody || !docLabel || triggerButtons.length === 0) {
        return;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function closeOverlay() {
        overlay.hidden = true;
        document.body.style.overflow = '';
    }

    function openOverlay(docId, docSymbol) {
        var data = receiverMap[String(docId)] || [];
        if (!Array.isArray(data) || data.length === 0) {
            return;
        }

        var html = '';
        data.forEach(function (person) {
            html += '<tr><td>' + escapeHtml(person.poscd || '') + '</td><td>' + escapeHtml(person.ten || '') + '</td></tr>';
        });
        listBody.innerHTML = html;
        docLabel.textContent = 'Văn bản: ' + docSymbol;
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    triggerButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var docId = button.getAttribute('data-doc-id');
            var docSymbol = button.getAttribute('data-doc-symbol') || '';
            openOverlay(docId, docSymbol);
        });
    });

    closeBtn.addEventListener('click', closeOverlay);
    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeOverlay();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (!overlay.hidden && event.key === 'Escape') {
            closeOverlay();
        }
    });
})();
</script>
<script>
(function () {
    var indexUrl = '<?php echo $index_url; ?>';
    var overlay = document.getElementById('lsOverlay');
    var dialog = document.getElementById('lsDialog');
    var dragHandle = document.getElementById('lsDialogTitle');
    var closeBtn = document.getElementById('lsCloseBtn');
    var saveBtn = document.getElementById('lsSaveBtn');
    var openListBtn = document.getElementById('openArchiveListBtn');
    var typeSelect = document.getElementById('lsType');
    var lsQuickFilter = document.getElementById('lsQuickFilter');
    var statusBox = document.getElementById('lsStatus');
    var listBody = document.getElementById('lsListBody');
    var listWrap = document.querySelector('.ls-list-wrap');
    var docSymbol = document.getElementById('lsDocSymbol');
    var docDate = document.getElementById('lsDocDate');
    var docTitle = document.getElementById('lsDocTitle');
    var docInfo = document.querySelector('.ls-doc-info');
    var openButtons = document.querySelectorAll('.btn-archive-popup');

    if (!overlay || !dialog || !dragHandle || !closeBtn || !saveBtn || !typeSelect || !statusBox || !listBody || !listWrap || !docSymbol || !docDate || !docTitle || !docInfo || !lsQuickFilter || openButtons.length === 0) {
        return;
    }

    var currentDoc = {
        id: '',
        symbol: '',
        title: '',
        date: ''
    };
    var lsMode = 'save';

    var drag = {
        active: false,
        startX: 0,
        startY: 0,
        left: 0,
        top: 0
    };

    function esc(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function setStatus(text, isError) {
        statusBox.textContent = text;
        statusBox.className = 'ls-status' + (isError ? ' error' : '');
    }

    function resetList() {
        listBody.innerHTML = '<tr><td colspan="4" class="ls-empty">Chưa tải dữ liệu.</td></tr>';
    }

    function applyArchiveQuickFilter() {
        var query = (lsQuickFilter.value || '').toLowerCase().trim();
        var rows = listBody.querySelectorAll('tr');
        var visible = 0;
        rows.forEach(function (row) {
            if (row.querySelector('.ls-empty')) {
                row.style.display = '';
                return;
            }
            var text = (row.textContent || '').toLowerCase();
            var show = query === '' || text.indexOf(query) !== -1;
            row.style.display = show ? '' : 'none';
            if (show) {
                visible += 1;
            }
        });
        var quickEmpty = listBody.querySelector('.ls-quick-empty-row');
        if (visible === 0 && rows.length > 0 && !listBody.querySelector('.ls-empty')) {
            if (!quickEmpty) {
                quickEmpty = document.createElement('tr');
                quickEmpty.className = 'ls-quick-empty-row';
                quickEmpty.innerHTML = '<td colspan="4" class="ls-empty">Không có dữ liệu phù hợp bộ lọc nhanh.</td>';
                listBody.appendChild(quickEmpty);
            }
        } else if (quickEmpty) {
            quickEmpty.remove();
        }
    }

    function loadTypes(selectedValue) {
        typeSelect.innerHTML = '<option value="">Đang tải...</option>';
        return fetch(indexUrl + '?api=archive_types&_t=' + Date.now(), { cache: 'no-store' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || data.ok !== true || !Array.isArray(data.rows)) {
                    setStatus((data && data.message) ? data.message : 'Không tải được danh sách phân loại.', true);
                    typeSelect.innerHTML = '<option value="">Chọn phân loại</option>';
                    return;
                }
                var html = '<option value="">Chọn phân loại</option>';
                data.rows.forEach(function (item) {
                    var code = String(item.ma || '').trim();
                    if (code === '') {
                        return;
                    }
                    var name = String(item.ten || code).trim();
                    html += '<option value="' + esc(code) + '">' + esc(code + ' - ' + name) + '</option>';
                });
                typeSelect.innerHTML = html;
                if (selectedValue) {
                    typeSelect.value = selectedValue;
                }
            })
            .catch(function () {
                typeSelect.innerHTML = '<option value="">Chọn phân loại</option>';
                setStatus('Lỗi kết nối khi tải phân loại.', true);
            });
    }

    function openOverlay(button) {
        lsMode = 'save';
        currentDoc.id = String(button.getAttribute('data-doc-id') || '').trim();
        currentDoc.symbol = String(button.getAttribute('data-doc-symbol') || '').trim();
        currentDoc.title = String(button.getAttribute('data-doc-title') || '').trim();
        currentDoc.date = String(button.getAttribute('data-doc-date') || '').trim();

        docSymbol.textContent = currentDoc.symbol || '-';
        docTitle.textContent = currentDoc.title || '-';
        docDate.textContent = currentDoc.date || '-';
        typeSelect.value = '';
        setStatus('Chọn phân loại rồi bấm Lưu.', false);
        lsQuickFilter.value = '';
        saveBtn.disabled = false;
        saveBtn.style.display = '';
        docInfo.style.display = '';
        listWrap.style.display = 'none';
        resetList();

        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        dialog.style.position = '';
        dialog.style.left = '';
        dialog.style.top = '';
        dialog.style.margin = '';

        loadTypes('');
    }

    function openListOverlay() {
        lsMode = 'list';
        currentDoc.id = '';
        currentDoc.symbol = '';
        currentDoc.title = '';
        currentDoc.date = '';
        docSymbol.textContent = '-';
        docTitle.textContent = '-';
        docDate.textContent = '-';
        typeSelect.value = '';
        saveBtn.disabled = true;
        saveBtn.style.display = 'none';
        docInfo.style.display = 'none';
        listWrap.style.display = '';
        setStatus('Chọn phân loại để tự động lọc danh sách.', false);
        lsQuickFilter.value = '';
        resetList();

        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        dialog.style.position = '';
        dialog.style.left = '';
        dialog.style.top = '';
        dialog.style.margin = '';

        loadTypes('');
    }

    function closeOverlay() {
        overlay.hidden = true;
        document.body.style.overflow = '';
    }

    function saveArchive() {
        if (lsMode !== 'save') {
            return;
        }
        var typeId = String(typeSelect.value || '').trim();
        if (currentDoc.id === '' || typeId === '') {
            setStatus('Thiếu mã số hoặc phân loại.', true);
            return;
        }

        setStatus('Đang lưu vào local ...', false);
        saveBtn.disabled = true;

        var body = new URLSearchParams();
        body.set('maso', currentDoc.id);
        body.set('maPl', typeId);

        fetch(indexUrl + '?api=archive_save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || data.ok !== true) {
                    setStatus((data && data.message) ? data.message : 'Lưu thất bại.', true);
                    return;
                }
                setStatus(data.message || 'Đã lưu thành công.', false);
            })
            .catch(function () {
                setStatus('Lỗi kết nối khi lưu dữ liệu.', true);
            })
            .finally(function () {
                saveBtn.disabled = false;
            });
    }

    function loadArchiveList() {
        var typeId = String(typeSelect.value || '').trim();
        if (typeId === '') {
            setStatus('Hãy chọn phân loại trước khi xem danh sách.', true);
            return;
        }
        setStatus('Đang tải danh sách từ local 127.0.0.1...', false);
        listBody.innerHTML = '<tr><td colspan="4" class="ls-empty">Đang tải...</td></tr>';

        fetch(indexUrl + '?api=archive_list&maPl=' + encodeURIComponent(typeId) + '&_t=' + Date.now(), { cache: 'no-store' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || data.ok !== true) {
                    setStatus((data && data.message) ? data.message : 'Không tải được danh sách lưu trữ.', true);
                    listBody.innerHTML = '<tr><td colspan="4" class="ls-empty">Không có dữ liệu.</td></tr>';
                    return;
                }
                if (!Array.isArray(data.rows) || data.rows.length === 0) {
                    setStatus('Không có dữ liệu trong phân loại đã chọn.', false);
                    listBody.innerHTML = '<tr><td colspan="4" class="ls-empty">Không có dữ liệu.</td></tr>';
                    return;
                }

                var html = '';
                data.rows.forEach(function (row) {
                    var maso = String(row.maSo || '');
                    var maPhanLoai = String(row.maPl || '');
                    var href = indexUrl + '?api=archive_raw&maso=' + encodeURIComponent(maso) + '&maPl=' + encodeURIComponent(maPhanLoai);
                    var tieuDe = String(row.tieuDe || row.tenFile || '').trim();
                    var tenFile = String(row.tenFile || '').trim();
                    var ngayGui = String(row.ngayGui || '').trim();
                    var ngayGuiDisplay = ngayGui;
                    if (ngayGuiDisplay !== '') {
                        var parsed = new Date(ngayGuiDisplay);
                        if (!isNaN(parsed.getTime())) {
                            var dd = String(parsed.getDate()).padStart(2, '0');
                            var mm = String(parsed.getMonth() + 1).padStart(2, '0');
                            var yyyy = String(parsed.getFullYear());
                            ngayGuiDisplay = dd + '/' + mm + '/' + yyyy;
                        }
                    }
                    html += '<tr>'
                        + '<td>' + esc(ngayGuiDisplay || '-') + '</td>'
                        + '<td class="ls-col-title" title="' + esc(tieuDe) + '">' + esc(tieuDe) + '</td>'
                        + '<td title="' + esc(tenFile) + '">' + esc(tenFile) + '</td>'
                        + '<td><a class="ls-view-link" target="_blank" href="' + href + '">Xem</a></td>'
                        + '</tr>';
                });
                listBody.innerHTML = html;
                setStatus('Tổng số: ' + (data.total || 0) + ' bản ghi.', false);
                applyArchiveQuickFilter();
            })
            .catch(function () {
                listBody.innerHTML = '<tr><td colspan="4" class="ls-empty">Lỗi kết nối.</td></tr>';
                setStatus('Lỗi kết nối khi tải danh sách.', true);
            });
    }

    function onDragMove(event) {
        if (!drag.active) {
            return;
        }
        var dx = event.clientX - drag.startX;
        var dy = event.clientY - drag.startY;
        var maxLeft = Math.max(0, overlay.clientWidth - dialog.offsetWidth);
        var maxTop = Math.max(0, overlay.clientHeight - dialog.offsetHeight);
        var nextLeft = clamp(drag.left + dx, 0, maxLeft);
        var nextTop = clamp(drag.top + dy, 0, maxTop);
        dialog.style.left = nextLeft + 'px';
        dialog.style.top = nextTop + 'px';
    }

    function stopDrag() {
        if (!drag.active) {
            return;
        }
        drag.active = false;
        document.removeEventListener('mousemove', onDragMove);
        document.removeEventListener('mouseup', stopDrag);
    }

    function startDrag(event) {
        if (event.button !== 0 || overlay.hidden) {
            return;
        }
        drag.active = true;
        drag.startX = event.clientX;
        drag.startY = event.clientY;
        var dialogRect = dialog.getBoundingClientRect();
        var overlayRect = overlay.getBoundingClientRect();
        dialog.style.position = 'absolute';
        dialog.style.margin = '0';
        dialog.style.left = (dialogRect.left - overlayRect.left) + 'px';
        dialog.style.top = (dialogRect.top - overlayRect.top) + 'px';
        drag.left = parseFloat(dialog.style.left) || 0;
        drag.top = parseFloat(dialog.style.top) || 0;
        document.addEventListener('mousemove', onDragMove);
        document.addEventListener('mouseup', stopDrag);
        event.preventDefault();
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            openOverlay(button);
        });
    });
    if (openListBtn) {
        openListBtn.addEventListener('click', openListOverlay);
    }
    closeBtn.addEventListener('click', closeOverlay);
    saveBtn.addEventListener('click', saveArchive);
    typeSelect.addEventListener('change', function () {
        var typeId = String(typeSelect.value || '').trim();
        if (typeId === '') {
            resetList();
            setStatus('Chọn phân loại để tự động lọc danh sách.', false);
            return;
        }
        loadArchiveList();
    });
    lsQuickFilter.addEventListener('input', applyArchiveQuickFilter);
    dragHandle.addEventListener('mousedown', startDrag);
    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeOverlay();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (!overlay.hidden && event.key === 'Escape') {
            closeOverlay();
        }
    });
})();
</script>
</body>
</html>
<?php
$list_stmt->close();
$conn->close();
?>
