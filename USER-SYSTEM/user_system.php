<?php
require_once __DIR__ . '/../FUNC_SHARE/ham_dung_chung.php';
dashboard_chan_ip_khong_hop_le();
dashboard_khoi_tao_phien('DASHBOARD_USER_SYSTEM_SESSID');
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../DB/connect_DB.php';
require_once __DIR__ . '/user_system_sql.php';

$dashboard_default_conn = $conn ?? null;
$dashboard_default_mysql_host = (string)($GLOBALS['mysqlActiveHost'] ?? $sourceHost ?? '');
$user_system_db_name = 'qlcv';
$user_system_conn = null;
if (function_exists('connect_mysql') && !empty($sourceHost)) {
    $user_system_conn = connect_mysql($sourceHost, $sourceUser, $sourcePass, $user_system_db_name, $sourcePort);
    if ($user_system_conn !== null) {
        $conn = $user_system_conn;
        $GLOBALS['mysqlActiveHost'] = $sourceHost;
    }
}
if ($conn === null) {
    $conn = $dashboard_default_conn;
    if ($conn === null) {
        die('Không kết nối được DB cho USER-SYSTEM.');
    }
    $GLOBALS['mysqlActiveHost'] = $dashboard_default_mysql_host;
}

if (function_exists('db_allow_protected_host_writes')) {
    db_allow_protected_host_writes((string)($GLOBALS['mysqlActiveHost'] ?? $sourceHost ?? ''));
}

function json_exit(array $payload): void
{
    dashboard_phan_hoi_json($payload);
}

function normalize_text(string $value): string
{
    return dashboard_chuan_hoa_text($value);
}


function user_system_resolve_return_url(string $value, string $fallback): string
{
    $value = trim($value);
    if ($value === '' || preg_match('/[\r\n]/', $value)) {
        return $fallback;
    }

    $parts = parse_url($value);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
        return $fallback;
    }

    $path = (string)($parts['path'] ?? '');
    if ($path === '' || strpos($path, '/dashboard/USER-SYSTEM/') !== 0) {
        return $fallback;
    }

    return $path;
}

function user_system_redirect(string $baseUrl, array $params = []): void
{
    $queryParams = [];
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $queryParams[$key] = $value;
    }

    $location = $baseUrl;
    if (!empty($queryParams)) {
        $location .= '?' . http_build_query($queryParams);
    }

    header('Location: ' . $location);
    exit;
}

function user_system_row_matches(array $row, string $keyword): bool
{
    $keyword = trim($keyword);
    if ($keyword === '') {
        return true;
    }

    if (function_exists('mb_strtolower')) {
        $keyword = mb_strtolower($keyword, 'UTF-8');
    } else {
        $keyword = strtolower($keyword);
    }

    $haystack = implode(' ', [
        (string)($row['macanbo'] ?? ''),
        (string)($row['hoten'] ?? ''),
        (string)($row['mota'] ?? ''),
        (string)($row['tenphong'] ?? ''),
        (string)($row['email'] ?? ''),
        (string)($row['maphong'] ?? ''),
        (string)($row['machucvu'] ?? ''),
    ]);

    if (function_exists('mb_strtolower')) {
        $haystack = mb_strtolower($haystack, 'UTF-8');
    } else {
        $haystack = strtolower($haystack);
    }

    return strpos($haystack, $keyword) !== false;
}

function user_system_row_matches_department(array $row, string $departmentCode): bool
{
    $departmentCode = trim($departmentCode);
    if ($departmentCode === '') {
        return true;
    }

    return trim((string)($row['maphong'] ?? '')) === $departmentCode;
}

function db_fetch_assoc_rows(mysqli $conn, string $sql, array $binds = [], ?string &$err = null): ?array
{
    return dashboard_db_lay_danh_sach_assoc($conn, $sql, $binds, $err);
}

function db_fetch_one_assoc(mysqli $conn, string $sql, array $binds = [], ?string &$err = null): ?array
{
    return dashboard_db_lay_mot_assoc($conn, $sql, $binds, $err);
}

function user_system_flash_set(string $message, string $type = 'info'): void
{
    $_SESSION['user_system_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function user_system_flash_take(): array
{
    $flash = $_SESSION['user_system_flash'] ?? [];
    unset($_SESSION['user_system_flash']);
    return is_array($flash) ? $flash : [];
}

function user_system_text(string $value): string
{
    return dashboard_html($value);
}

function user_system_option_exists(array $rows, string $valueKey, string $selectedValue): bool
{
    foreach ($rows as $row) {
        if ((string)($row[$valueKey] ?? '') === $selectedValue) {
            return true;
        }
    }
    return false;
}


$index_url = '/dashboard/USER-SYSTEM/user_system.php';
$keyword = normalize_text((string)($_GET['keyword'] ?? ''));
$filter_maphong = normalize_text((string)($_GET['filter_maphong'] ?? ''));
$macanbo = normalize_text((string)($_GET['macanbo'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$flash = user_system_flash_take();
$query_error = '';
$query_hint = 'Khung này đã sẵn sàng cho SQL của bạn. Chỉ cần thay phần TODO trong `user_system_sql.php`.';
$list_rows = [];
$current_user = [
    'macanbo' => '',
    'hoten' => '',
    'mota' => '',
    'tenphong' => '',
    'email' => '',
    'maphong' => '',
    'machucvu' => '',
    'password_hash' => '',
];

$department_rows = [];
$position_rows = [];

$department_err = '';
$department_rows = db_fetch_assoc_rows($conn, user_system_department_sql(), [], $department_err);
if ($department_rows === null) {
    if ($query_error === '') {
        $query_error = $department_err !== '' ? $department_err : 'Không đọc được danh sách phòng ban.';
    }
    $department_rows = [];
}
if ($filter_maphong !== '' && !user_system_option_exists($department_rows, 'department_code', $filter_maphong)) {
    $filter_maphong = '';
}

$position_err = '';
$position_rows = db_fetch_assoc_rows($conn, user_system_posotion_sql(), [], $position_err);
if ($position_rows === null) {
    if ($query_error === '') {
        $query_error = $position_err !== '' ? $position_err : 'Không đọc được danh sách chức vụ.';
    }
    $position_rows = [];
}


if (isset($_GET['api']) && $_GET['api'] === 'detail') {
    $api_code = normalize_text((string)($_GET['id'] ?? ''));
    if ($api_code === '') {
        json_exit(['ok' => false, 'message' => 'Thiếu mã cán bộ.']);
    }
    $detail_err = '';
    $detail_row = db_fetch_one_assoc($conn, user_system_detail_sql(), [$api_code], $detail_err);
    if ($detail_row === null) {
        json_exit(['ok' => false, 'message' => $detail_err !== '' ? $detail_err : 'Khong doc duoc thong tin user.']);
    }

    json_exit(['ok' => true, 'row' => $detail_row]);
}

if (isset($_GET['api']) && $_GET['api'] === 'list') {
    $list_err = '';
    $rows = db_fetch_assoc_rows($conn, user_system_list_sql(), [], $list_err);
    if ($rows === null) {
        json_exit(['ok' => false, 'message' => $list_err !== '' ? $list_err : 'Không đọc được danh sách user.', 'rows' => []]);
    }
    $filtered = [];
    foreach ($rows as $row) {
        if (user_system_row_matches($row, $keyword) && user_system_row_matches_department($row, $filter_maphong)) {
            $filtered[] = $row;
        }
    }
    $total_filtered = count($filtered);
    $page_count = max(1, (int)ceil($total_filtered / $per_page));
    if ($page > $page_count) {
        $page = $page_count;
    }
    $offset = ($page - 1) * $per_page;
    $filtered = array_slice($filtered, $offset, $per_page);
    json_exit([
        'ok' => true,
        'total' => $total_filtered,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $page_count,
        'rows' => $filtered,
    ]);
}

if (isset($_POST['action']) && $_POST['action'] === 'save') {
    $confirm_write = trim((string)($_POST['confirm_write'] ?? '0'));
    $original_macanbo = normalize_text((string)($_POST['original_macanbo'] ?? ''));
    $posted_macanbo = normalize_text((string)($_POST['macanbo'] ?? ''));
    $return_url = user_system_resolve_return_url((string)($_POST['return_url'] ?? ''), $index_url);
    $return_keyword = normalize_text((string)($_POST['return_keyword'] ?? ''));
    $return_filter_maphong = normalize_text((string)($_POST['return_filter_maphong'] ?? ''));
    $return_page = max(1, (int)($_POST['return_page'] ?? 1));
    $is_update = $original_macanbo !== '';
    $action_text = $is_update ? 'cập nhật' : 'thêm mới';

    if ($confirm_write !== '1') {
        user_system_flash_set('Ban co xac nhan muon ' . $action_text . ' user nay khong? Hay xac nhan tren giao dien.', 'error');
        user_system_redirect($return_url, [
            'macanbo' => $is_update ? $original_macanbo : $posted_macanbo,
            'keyword' => $return_keyword,
            'filter_maphong' => $return_url === $index_url ? $return_filter_maphong : '',
            'page' => $return_url === $index_url && $return_page > 1 ? $return_page : '',
        ]);
    }

    $hoten = normalize_text((string)($_POST['hoten'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $maphong = normalize_text((string)($_POST['maphong'] ?? ''));
    $machucvu = normalize_text((string)($_POST['machucvu'] ?? ''));
    $email = normalize_text((string)($_POST['email'] ?? ''));

    if ($posted_macanbo === '' || $hoten === '') {
        user_system_flash_set('Vui long nhap ma can bo va ho ten.', 'error');
        user_system_redirect($return_url, [
            'macanbo' => $is_update ? $original_macanbo : $posted_macanbo,
            'hoten' => !$is_update && $return_url !== $index_url ? $hoten : '',
            'maphong' => !$is_update && $return_url !== $index_url ? $maphong : '',
            'machucvu' => !$is_update && $return_url !== $index_url ? $machucvu : '',
            'email' => !$is_update && $return_url !== $index_url ? $email : '',
            'keyword' => $return_keyword,
            'filter_maphong' => $return_url === $index_url ? $return_filter_maphong : '',
            'page' => $return_url === $index_url && $return_page > 1 ? $return_page : '',
        ]);
    }


    if ($maphong === '' || !user_system_option_exists($department_rows, 'department_code', $maphong)) {
        user_system_flash_set('Vui long chon phong ban hop le.', 'error');
        user_system_redirect($return_url, [
            'macanbo' => $is_update ? $original_macanbo : $posted_macanbo,
            'hoten' => !$is_update && $return_url !== $index_url ? $hoten : '',
            'maphong' => !$is_update && $return_url !== $index_url ? $maphong : '',
            'machucvu' => !$is_update && $return_url !== $index_url ? $machucvu : '',
            'email' => !$is_update && $return_url !== $index_url ? $email : '',
            'keyword' => $return_keyword,
            'filter_maphong' => $return_url === $index_url ? $return_filter_maphong : '',
            'page' => $return_url === $index_url && $return_page > 1 ? $return_page : '',
        ]);
    }

    if ($machucvu === '' || !user_system_option_exists($position_rows, 'position_code', $machucvu)) {
        user_system_flash_set('Vui long chon chuc vu hop le.', 'error');
        user_system_redirect($return_url, [
            'macanbo' => $is_update ? $original_macanbo : $posted_macanbo,
            'hoten' => !$is_update && $return_url !== $index_url ? $hoten : '',
            'maphong' => !$is_update && $return_url !== $index_url ? $maphong : '',
            'machucvu' => !$is_update && $return_url !== $index_url ? $machucvu : '',
            'email' => !$is_update && $return_url !== $index_url ? $email : '',
            'keyword' => $return_keyword,
            'filter_maphong' => $return_url === $index_url ? $return_filter_maphong : '',
            'page' => $return_url === $index_url && $return_page > 1 ? $return_page : '',
        ]);
    }

    $write_err = '';
    if (!$is_update) {
        if (trim($password) === '') {
            $write_err = html_entity_decode('Vui l&#242;ng nh&#7853;p m&#7853;t kh&#7849;u khi th&#234;m m&#7899;i.', ENT_QUOTES, 'UTF-8');
        } else {
            $hashps = $password;
            $sql = user_system_insert_sql();
            $stmt = db_mysqli_prepare($conn, $sql);
            if ($stmt === false) {
                $write_err = $conn->error !== '' ? $conn->error : 'Không prepare được insert SQL.';
            } else {
                $bind_ok = $stmt->bind_param('ssssss', $posted_macanbo, $hoten, $hashps, $maphong, $machucvu, $email);
                if ($bind_ok === false || !$stmt->execute()) {
                    $write_err = $stmt->error !== '' ? $stmt->error : 'Không insert được user.';
                }
                $stmt->close();
            }
        }
    } else {
        if (trim($password) !== '') {
            $hashps = $password;
            $sql = user_system_update_sql(true);
            $stmt = db_mysqli_prepare($conn, $sql);
            if ($stmt === false) {
                $write_err = $conn->error !== '' ? $conn->error : 'Không prepare được update SQL.';
            } else {
                $bind_ok = $stmt->bind_param('ssssss', $hoten, $hashps, $maphong, $machucvu, $email, $original_macanbo);
                if ($bind_ok === false || !$stmt->execute()) {
                    $write_err = $stmt->error !== '' ? $stmt->error : 'Không update được user.';
                }
                $stmt->close();
            }
        } else {
            $sql = user_system_update_sql(false);
            $stmt = db_mysqli_prepare($conn, $sql);
            if ($stmt === false) {
                $write_err = $conn->error !== '' ? $conn->error : 'Không prepare được update SQL.';
            } else {
                $bind_ok = $stmt->bind_param('sssss', $hoten, $maphong, $machucvu, $email, $original_macanbo);
                if ($bind_ok === false || !$stmt->execute()) {
                    $write_err = $stmt->error !== '' ? $stmt->error : 'Không update được user.';
                }
                $stmt->close();
            }
        }
    }

    if ($write_err !== '') {
        user_system_flash_set('Luu that bai: ' . $write_err, 'error');
    } else {
        user_system_flash_set($is_update ? 'Da cap nhat user thanh cong.' : 'Da them moi user thanh cong.', 'info');
    }

    $redirect_params = [
        'macanbo' => $return_url === $index_url ? ($is_update ? $original_macanbo : $posted_macanbo) : '',
        'keyword' => $return_url === $index_url ? $return_keyword : '',
        'filter_maphong' => $return_url === $index_url ? $return_filter_maphong : '',
        'page' => $return_url === $index_url && $return_page > 1 ? $return_page : '',
    ];
    if ($write_err !== '' && !$is_update && $return_url !== $index_url) {
        $redirect_params['macanbo'] = $posted_macanbo;
        $redirect_params['hoten'] = $hoten;
        $redirect_params['maphong'] = $maphong;
        $redirect_params['machucvu'] = $machucvu;
        $redirect_params['email'] = $email;
    }

    user_system_redirect($return_url, $redirect_params);
}

$list_err = '';
$all_rows = db_fetch_assoc_rows($conn, user_system_list_sql(), [], $list_err);
if ($all_rows === null) {
    $query_error = $list_err !== '' ? $list_err : 'Không đọc được danh sách user.';
    $all_rows = [];
}
$filtered_rows = [];
foreach ($all_rows as $row) {
    if (user_system_row_matches($row, $keyword) && user_system_row_matches_department($row, $filter_maphong)) {
        $filtered_rows[] = $row;
    }
}

$total_rows = count($filtered_rows);
$total_pages = max(1, (int)ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;
$list_rows = array_slice($filtered_rows, $offset, $per_page);
$page_start = $total_rows > 0 ? $offset + 1 : 0;
$page_end = min($offset + $per_page, $total_rows);

$stats = [
    'total' => $total_rows,
    'phong' => [],
    'chucvu' => [],
    'email' => 0,
];
foreach ($filtered_rows as $row) {
    $tenphong = trim((string)($row['tenphong'] ?? ''));
    $mota = trim((string)($row['mota'] ?? ''));
    $email_value = trim((string)($row['email'] ?? ''));
    if ($tenphong !== '') {
        $stats['phong'][$tenphong] = true;
    }
    if ($mota !== '') {
        $stats['chucvu'][$mota] = true;
    }
    if ($email_value !== '') {
        $stats['email']++;
    }
}
$stats['phong'] = count($stats['phong']);
$stats['chucvu'] = count($stats['chucvu']);

if ($macanbo !== '') {
    $detail_err = '';
    $detail_row = db_fetch_one_assoc($conn, user_system_detail_sql(), [$macanbo], $detail_err);
    if ($detail_row === null) {
        $query_error = $detail_err !== '' ? $detail_err : 'Không đọc được thông tin user.';
    } elseif (!empty($detail_row)) {
        $current_user = array_merge($current_user, $detail_row);
    }
}


$canonical_params = [];
if ($keyword !== '') {
    $canonical_params['keyword'] = $keyword;
}
if ($filter_maphong !== '') {
    $canonical_params['filter_maphong'] = $filter_maphong;
}
if ($macanbo !== '') {
    $canonical_params['macanbo'] = $macanbo;
}
if ($page > 1) {
    $canonical_params['page'] = $page;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User System</title>
<link rel="stylesheet" href="../view/Style_user_system.php">
</head>
<body>
<div class="container">
    <header class="hero">
        <div class="hero-copy">
            <div class="eyebrow">USER-SYSTEM</div>
            <h1>Quản lý cán bộ</h1>
            <p>
                Màn này đang đọc từ các bảng `canbo`, `phongban` và `CHUCVU` trên database `qlcv`.
                Khi thêm hoặc cập nhật, hệ thống sẽ hỏi xác nhận trước khi ghi dữ liệu.
            </p>
        </div>
        <div class="hero-actions">
            <a class="hero-btn hero-btn-primary" href="<?php echo $index_url; ?>">+ Thêm cán bộ</a>
            <button type="button" class="hero-btn" id="reloadListBtn">Tải lại danh sách</button>
            <a class="hero-btn hero-btn-ghost" href="<?php echo $index_url; ?>">Xóa lọc</a>
        </div>
    </header>

    <section class="stats-grid">
        <article class="stat-card">
            <span class="stat-label">Tổng cán bộ</span>
            <strong><?php echo number_format((int)$stats['total']); ?></strong>
            <small>Số bản ghi đang hiển thị</small>
        </article>
        <article class="stat-card">
            <span class="stat-label">Phòng ban</span>
            <strong><?php echo number_format((int)$stats['phong']); ?></strong>
            <small>Số phòng ban khác nhau</small>
        </article>
        <article class="stat-card">
            <span class="stat-label">Chức vụ</span>
            <strong><?php echo number_format((int)$stats['chucvu']); ?></strong>
            <small>Số chức vụ khác nhau</small>
        </article>
        <article class="stat-card">
            <span class="stat-label">Có email</span>
            <strong><?php echo number_format((int)$stats['email']); ?></strong>
            <small>Số cán bộ có email</small>
        </article>
    </section>

    <form method="get" action="<?php echo $index_url; ?>" class="search-bar" id="filterForm">
        <input type="text" name="keyword" class="field-keyword" placeholder="Tìm theo mã cán bộ, họ tên, phòng, chức vụ, email..." value="<?php echo user_system_text($keyword); ?>">
        <select name="filter_maphong" class="field-status" id="filterMaphong">
            <option value="">Tất cả phòng ban</option>
            <?php foreach ($department_rows as $department): ?>
                <?php
                $department_code = trim((string)($department['department_code'] ?? ''));
                $department_name = trim((string)($department['department_name'] ?? ''));
                if ($department_code === '') {
                    continue;
                }
                ?>
                <option value="<?php echo user_system_text($department_code); ?>" <?php echo $department_code === $filter_maphong ? 'selected' : ''; ?>>
                    <?php echo user_system_text($department_name !== '' ? $department_name : $department_code); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="page" id="filterPage" value="1">
        <button type="submit" class="btn-search">Lọc danh sách</button>
    </form>

    <?php if (!empty($flash['message'])): ?>
        <div class="notice <?php echo (($flash['type'] ?? 'info') === 'error') ? 'notice-error' : 'notice-info'; ?>">
            <?php echo user_system_text((string)$flash['message']); ?>
        </div>
    <?php elseif ($query_error !== ''): ?>
        <div class="notice notice-error"><?php echo user_system_text($query_error); ?></div>
    <?php else: ?>
        <div class="notice notice-info">
            <?php echo user_system_text($query_hint); ?>
            <?php if ($total_rows > 0): ?>
                <span style="display:block;margin-top:6px;">
                    Đang hiển thị <?php echo number_format($page_start); ?> - <?php echo number_format($page_end); ?> / <?php echo number_format($total_rows); ?> dòng.
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <section class="workspace">
        <div class="panel panel-table">
            <div class="panel-head">
                <div>
                    <h2>Danh sách cán bộ</h2>
                    <p>Bố cục sẵn cho table + action theo SQL bạn đang dùng.</p>
                </div>
                <div class="panel-tools">
                    <input type="text" class="quick-filter" id="quickFilter" placeholder="Lọc nhanh trong bảng...">
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 6%;">STT</th>
                            <th style="width: 16%;">Mã cán bộ</th>
                            <th style="width: 24%;">Họ tên</th>
                            <th style="width: 18%;">Chức vụ</th>
                            <th style="width: 18%;">Phòng ban</th>
                            <th style="width: 14%;">Email</th>
                            <th style="width: 8%;">Sửa</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php if (count($list_rows) === 0): ?>
                            <tr>
                                <td colspan="7" class="empty-state">Chưa có dữ liệu phù hợp.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($list_rows as $idx => $row): ?>
                                <?php
                                $row_macanbo = (string)($row['macanbo'] ?? '');
                                $row_hoten = (string)($row['hoten'] ?? '');
                                $row_mota = (string)($row['mota'] ?? '');
                                $row_tenphong = (string)($row['tenphong'] ?? '');
                                $row_email = (string)($row['email'] ?? '');
                                $stt = $offset + $idx + 1;
                                ?>
                                <tr>
                                    <td><?php echo number_format($stt); ?></td>
                                    <td><?php echo user_system_text($row_macanbo); ?></td>
                                    <td title="<?php echo user_system_text($row_hoten); ?>"><?php echo user_system_text($row_hoten); ?></td>
                                    <td title="<?php echo user_system_text($row_mota); ?>"><?php echo user_system_text($row_mota); ?></td>
                                    <td title="<?php echo user_system_text($row_tenphong); ?>"><?php echo user_system_text($row_tenphong); ?></td>
                                    <td><?php echo user_system_text($row_email); ?></td>
                                    <td>
                                        <a class="row-link" href="<?php echo $index_url . '?' . htmlspecialchars(http_build_query(array_merge($canonical_params, ['macanbo' => $row_macanbo])), ENT_QUOTES, 'UTF-8'); ?>">Sửa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $base_query = $canonical_params;
                    if ($page > 1):
                        $base_query['page'] = $page - 1;
                    ?>
                        <a class="nav" href="<?php echo $index_url; ?>?<?php echo htmlspecialchars(http_build_query($base_query), ENT_QUOTES, 'UTF-8'); ?>">&lsaquo;</a>
                    <?php else: ?>
                        <span class="nav">&lsaquo;</span>
                    <?php endif; ?>

                    <?php
                    for ($i = 1; $i <= $total_pages; $i++):
                        $base_query['page'] = $i;
                        if ($i === $page):
                    ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo $index_url; ?>?<?php echo htmlspecialchars(http_build_query($base_query), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $i; ?></a>
                    <?php
                        endif;
                    endfor;
                    if ($page < $total_pages):
                        $base_query['page'] = $page + 1;
                    ?>
                        <a class="nav" href="<?php echo $index_url; ?>?<?php echo htmlspecialchars(http_build_query($base_query), ENT_QUOTES, 'UTF-8'); ?>">&rsaquo;</a>
                    <?php else: ?>
                        <span class="nav">&rsaquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="panel panel-form">
            <div class="panel-head panel-head-tight">
                <div>
                    <h2>Chi tiết cán bộ</h2>
                    <p>Nhập dữ liệu rồi bấm lưu. Hệ thống sẽ hỏi xác nhận trước khi ghi.</p>
                </div>
            </div>

            <form method="post" action="<?php echo $index_url; ?>" class="user-form" id="userForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="confirm_write" id="confirmWrite" value="0">
                <input type="hidden" name="original_macanbo" id="originalMacanbo" value="<?php echo user_system_text((string)$current_user['macanbo']); ?>">
                <input type="hidden" name="return_keyword" value="<?php echo user_system_text($keyword); ?>">
                <input type="hidden" name="return_filter_maphong" value="<?php echo user_system_text($filter_maphong); ?>">
                <input type="hidden" name="return_page" value="<?php echo (int)$page; ?>">
                <input type="hidden" name="return_url" value="<?php echo user_system_text($index_url); ?>">

                <div class="form-grid">
                    <label>
                        <span>Mã cán bộ</span>
                        <input type="text" name="macanbo" id="macanbo" value="<?php echo user_system_text((string)$current_user['macanbo']); ?>" placeholder="VD: CB001">
                    </label>
                    <label>
                        <span>Họ tên</span>
                        <input type="text" name="hoten" id="hoten" value="<?php echo user_system_text((string)$current_user['hoten']); ?>" placeholder="Nguyễn Văn A">
                    </label>
                    <label>
                        <span>Phòng ban</span>
                        <select name="maphong" id="maphong">
                            <option value="">Chọn phòng ban</option>
                            <?php foreach ($department_rows as $department): ?>
                                <?php
                                $department_code = trim((string)($department['department_code'] ?? ''));
                                $department_name = trim((string)($department['department_name'] ?? ''));
                                if ($department_code === '') {
                                    continue;
                                }
                                ?>
                                <option value="<?php echo user_system_text($department_code); ?>" <?php echo $department_code === (string)$current_user['maphong'] ? 'selected' : ''; ?>>
                                    <?php echo user_system_text($department_name !== '' ? $department_name : $department_code); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ((string)$current_user['maphong'] !== '' && !user_system_option_exists($department_rows, 'department_code', (string)$current_user['maphong'])): ?>
                                <option value="<?php echo user_system_text((string)$current_user['maphong']); ?>" selected>
                                    <?php echo user_system_text((string)$current_user['maphong']); ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </label>
                    <label>
                        <span>Chức vụ</span>
                        <select name="machucvu" id="machucvu">
                            <option value="">Chọn chức vụ</option>
                            <?php foreach ($position_rows as $position): ?>
                                <?php
                                $position_code = trim((string)($position['position_code'] ?? ''));
                                $position_name = trim((string)($position['position_name'] ?? ''));
                                if ($position_code === '') {
                                    continue;
                                }
                                ?>
                                <option value="<?php echo user_system_text($position_code); ?>" <?php echo $position_code === (string)$current_user['machucvu'] ? 'selected' : ''; ?>>
                                    <?php echo user_system_text($position_name !== '' ? $position_name : $position_code); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ((string)$current_user['machucvu'] !== '' && !user_system_option_exists($position_rows, 'position_code', (string)$current_user['machucvu'])): ?>
                                <option value="<?php echo user_system_text((string)$current_user['machucvu']); ?>" selected>
                                    <?php echo user_system_text((string)$current_user['machucvu']); ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </label>

                    <label>
                        <span>M&#7853;t kh&#7849;u</span>
                        <input type="password" name="password" id="password" placeholder="&#272;&#7875; tr&#7889;ng n&#7871;u kh&#244;ng &#273;&#7893;i" autocomplete="new-password">
                    </label>
                    <label class="span-2">
                        <span>Email</span>
                        <input type="email" name="email" id="email" value="<?php echo user_system_text((string)$current_user['email']); ?>" placeholder="user@example.com">
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="action-btn action-btn-primary" id="saveBtn"><?php echo ((string)$current_user['macanbo'] !== '') ? 'Cập nhật' : 'Lưu'; ?></button>
                    <button type="button" class="action-btn" id="resetBtn">Reset</button>
                </div>
            </form>
        </aside>
    </section>

    <section class="panel panel-footer">
        <div class="footer-note">
            <strong>Ghi chú:</strong>
            Màn này đang đọc/ghi trên database `qlcv` của host `10.64.0.251`, nhưng chỉ ghi sau khi bạn xác nhận.
        </div>
        <div class="footer-tags">
            <span>List</span>
            <span>Form</span>
            <span>Confirm write</span>
        </div>
    </section>
</div>
<script>
(function () {
    var form = document.getElementById('userForm');
    var confirmWrite = document.getElementById('confirmWrite');
    var saveBtn = document.getElementById('saveBtn');
    var resetBtn = document.getElementById('resetBtn');
    var reloadBtn = document.getElementById('reloadListBtn');
    var quickFilter = document.getElementById('quickFilter');
    var tableBody = document.getElementById('userTableBody');
    var passwordInput = document.getElementById('password');

    function trimValue(value) {
        return String(value || '').trim();
    }

    function isUpdateMode() {
        var original = document.getElementById('originalMacanbo');
        return trimValue(original ? original.value : '') !== '';
    }

    function promptSave() {
        var action = isUpdateMode() ? 'cập nhật' : 'thêm mới';
        return window.confirm('Bạn có xác nhận muốn ' + action + ' user này không?');
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!promptSave()) {
                event.preventDefault();
                return;
            }
            if (confirmWrite) {
                confirmWrite.value = '1';
            }
            if (saveBtn) {
                saveBtn.disabled = true;
            }
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            window.location.href = '<?php echo $index_url; ?>';
        });
    }

    if (reloadBtn) {
        reloadBtn.addEventListener('click', function () {
            window.location.reload();
        });
    }

    if (quickFilter && tableBody) {
        quickFilter.addEventListener('input', function () {
            var query = trimValue(quickFilter.value).toLowerCase();
            var rows = tableBody.querySelectorAll('tr');
            var visibleCount = 0;
            rows.forEach(function (row) {
                if (row.querySelector('.empty-state')) {
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
            var existingEmpty = tableBody.querySelector('.quick-empty-row');
            if (visibleCount === 0 && rows.length > 0) {
                if (!existingEmpty) {
                    existingEmpty = document.createElement('tr');
                    existingEmpty.className = 'quick-empty-row';
                    existingEmpty.innerHTML = '<td colspan="7" class="empty-state">Không có dữ liệu phù hợp với bộ lọc nhanh.</td>';
                    tableBody.appendChild(existingEmpty);
                }
            } else if (existingEmpty) {
                existingEmpty.remove();
            }
        });
    }
})();
</script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>


