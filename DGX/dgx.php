<?php
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
require_once __DIR__ . '/dgx_sql.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function normalize_date_input(string $value): string
{
    $value = trim($value);
    if ($value === '') return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) return $value;
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m) === 1) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return '';
}

function normalize_dgx_codes_input(string $value): string
{
    $value = strtoupper(trim($value));
    if ($value === '') return '';
    $parts = preg_split('/[;\s,]+/', $value) ?: [];
    $codes = [];
    foreach ($parts as $part) {
        $code = preg_replace('/[^A-Z0-9_-]/', '', trim((string)$part)) ?? '';
        if ($code !== '') $codes[$code] = true;
    }
    return implode(';', array_keys($codes));
}

function is_fast_dgx_keyword(string $value): bool
{
    $value = strtoupper(trim($value));
    return $value !== '' && preg_match('/^(TXN|DGX)[A-Z0-9_-]{3,20}$/', $value) === 1;
}

function oci_run_query($conn, string $sql, array $binds = [], ?string &$err = null): ?array
{
    $stmt = db_oci_parse($conn, $sql);
    if ($stmt === false) {
        $e = oci_error($conn);
        $err = $e['message'] ?? 'OCI parse error';
        return null;
    }

    $bind_vars = [];
    foreach ($binds as $name => $value) {
        $bind_vars[$name] = $value;
        if (@oci_bind_by_name($stmt, ':' . $name, $bind_vars[$name]) === false) {
            $e = oci_error($stmt);
            $err = $e['message'] ?? 'OCI bind error';
            return null;
        }
    }

    if (@oci_execute($stmt, OCI_NO_AUTO_COMMIT) === false) {
        $e = oci_error($stmt);
        $err = $e['message'] ?? 'OCI execute error';
        return null;
    }

    $rows = [];
    while (($row = oci_fetch_assoc($stmt)) !== false) {
        $rows[] = $row;
    }
    oci_free_statement($stmt);
    return $rows;
}

function get_missing_dgx_codes($conn, string $report_date_ddmmyyyy, string $codes_csv, ?string &$err = null): ?array
{
    $codes_csv = normalize_dgx_codes_input($codes_csv);
    if ($codes_csv === '') return [];
    $rows = oci_run_query($conn, dgx_missing_codes_sql(), [
        'P_CODES' => $codes_csv,
        'P_NGAYBC' => $report_date_ddmmyyyy,
    ], $err);
    if ($rows === null) return null;
    $codes = [];
    foreach ($rows as $row) {
        $code = strtoupper(trim((string)($row['MA_DIEM_GDX'] ?? '')));
        if ($code !== '') $codes[] = $code;
    }
    return $codes;
}

$index_url = '/dashboard/DGX/dgx.php';
$keyword = trim((string)($_GET['keyword'] ?? ''));
$from_date = normalize_date_input((string)($_GET['from_date'] ?? date('Y-m-d')));
if ($from_date === '') $from_date = date('Y-m-d');
$from_date_oracle = date('d/m/Y', strtotime($from_date));
$fixed_mode = trim((string)($_GET['fixed_mode'] ?? 'day'));
if (!in_array($fixed_mode, ['day', 'all'], true)) $fixed_mode = 'day';
$fixed_date_text = trim((string)($_GET['fixed_date'] ?? ''));
if ($fixed_date_text === '') $fixed_date_text = date('d');
$report_dgx_text = trim((string)($_GET['report_dgx'] ?? ''));
$report_date_text = normalize_date_input((string)($_GET['report_date'] ?? $from_date));
if ($report_date_text === '') $report_date_text = $from_date;
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 40;
$offset = ($page - 1) * $per_page;
$start_row = $offset + 1;
$end_row = $offset + $per_page;

if (isset($_GET['api']) && $_GET['api'] === 'fixed_points') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!isset($oracle_conn) || $oracle_conn === null) {
        echo json_encode(['ok' => false, 'message' => 'Chua ket noi duoc Oracle.', 'rows' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $api_mode = trim((string)($_GET['fixed_mode'] ?? 'day'));
    if (!in_array($api_mode, ['day', 'all'], true)) $api_mode = 'day';
    $api_date_raw = trim((string)($_GET['fixed_date'] ?? ''));
    $api_fixed_keyword = strtoupper(trim((string)($_GET['fixed_keyword'] ?? '')));
    $api_date = normalize_date_input($api_date_raw);
    $ngay_gdx_bind = 'All';
    $ngay_gdx_digits = 'All';
    $ngay_gdx_digits_alt = 'All';
    $ngay_gdx_day_only = '';
    if ($api_mode === 'day') {
        if ($api_date_raw === '') $api_date_raw = date('d');
        $ngay_gdx_bind = $api_date !== '' ? date('d/m/Y', strtotime($api_date)) : $api_date_raw;
        $ngay_gdx_digits = preg_replace('/\D+/', '', $ngay_gdx_bind) ?? '';
        $ngay_gdx_digits_alt = strlen($ngay_gdx_digits) === 8
            ? substr($ngay_gdx_digits, 4, 4) . substr($ngay_gdx_digits, 2, 2) . substr($ngay_gdx_digits, 0, 2)
            : $ngay_gdx_digits;
        if (preg_match('/^\d{1,2}$/', trim($api_date_raw)) === 1) {
            $ngay_gdx_day_only = str_pad(trim($api_date_raw), 2, '0', STR_PAD_LEFT);
        } elseif (strlen($ngay_gdx_digits) >= 2) {
            $ngay_gdx_day_only = substr($ngay_gdx_digits, 0, 2);
        }
    }
    $api_err = '';
    $fixed_rows = oci_run_query($oracle_conn, dgx_fixed_points_sql(), [
        'ngay_gdx' => $ngay_gdx_bind,
        'ngay_gdx_digits' => $ngay_gdx_digits,
        'ngay_gdx_digits_alt' => $ngay_gdx_digits_alt,
        'ngay_gdx_day_only' => $ngay_gdx_day_only,
        'fixed_keyword' => $api_fixed_keyword,
        'fixed_keyword_like' => '%' . $api_fixed_keyword . '%',
    ], $api_err);
    if ($fixed_rows === null) {
        echo json_encode(['ok' => false, 'message' => $api_err !== '' ? $api_err : 'Loi truy van Oracle.', 'rows' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => true, 'mode' => $api_mode, 'date' => $ngay_gdx_bind, 'total' => count($fixed_rows), 'rows' => $fixed_rows], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['api']) && $_GET['api'] === 'fixed_dates') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!isset($oracle_conn) || $oracle_conn === null) {
        echo json_encode(['ok' => false, 'message' => 'Chua ket noi duoc Oracle.', 'rows' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $api_err = '';
    $date_rows = oci_run_query($oracle_conn, dgx_fixed_dates_sql(), [], $api_err);
    if ($date_rows === null) {
        echo json_encode(['ok' => false, 'message' => $api_err !== '' ? $api_err : 'Loi truy van Oracle.', 'rows' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => true, 'rows' => $date_rows], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['api']) && $_GET['api'] === 'report_list') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!isset($oracle_conn) || $oracle_conn === null) {
        echo json_encode(['ok' => false, 'message' => 'Chua ket noi duoc Oracle.', 'rows' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $api_report_date = normalize_date_input((string)($_GET['report_date'] ?? '')) ?: date('Y-m-d');
    $api_report_dgx = normalize_dgx_codes_input((string)($_GET['report_dgx'] ?? ''));
    $api_base_dgx = normalize_dgx_codes_input((string)($_GET['base_dgx'] ?? ''));
    $validate_codes_csv = $api_report_dgx !== '' ? $api_report_dgx : $api_base_dgx;
    $report_date_ddmmyyyy = date('d/m/Y', strtotime($api_report_date));
    $missing_codes = [];
    if ($validate_codes_csv !== '') {
        $api_err = '';
        $missing_codes = get_missing_dgx_codes($oracle_conn, $report_date_ddmmyyyy, $validate_codes_csv, $api_err);
        if ($missing_codes === null) {
            echo json_encode(['ok' => false, 'message' => $api_err !== '' ? $api_err : 'Loi kiem tra ma diem GDX.', 'rows' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    $api_err = '';
    $rows = oci_run_query($oracle_conn, dgx_report_sql(), [
        'P_NGAYBC' => $report_date_ddmmyyyy,
        'P_MADGD' => $api_report_dgx !== '' ? $api_report_dgx : null,
    ], $api_err);
    if ($rows === null) {
        echo json_encode(['ok' => false, 'message' => $api_err !== '' ? $api_err : 'Loi truy van Oracle.', 'rows' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode([
        'ok' => true,
        'date' => $report_date_ddmmyyyy,
        'dgx' => $api_report_dgx,
        'checked_codes' => $validate_codes_csv,
        'missing_codes' => $missing_codes,
        'total' => count($rows),
        'rows' => $rows,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['api']) && $_GET['api'] === 'report_excel') {
    if (!isset($oracle_conn) || $oracle_conn === null) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Chua ket noi duoc Oracle.';
        exit;
    }
    $api_report_date = normalize_date_input((string)($_GET['report_date'] ?? '')) ?: date('Y-m-d');
    $api_report_dgx = normalize_dgx_codes_input((string)($_GET['report_dgx'] ?? ''));
    $api_base_dgx = normalize_dgx_codes_input((string)($_GET['base_dgx'] ?? ''));
    $validate_codes_csv = $api_report_dgx !== '' ? $api_report_dgx : $api_base_dgx;
    $report_date_ddmmyyyy = date('d/m/Y', strtotime($api_report_date));
    if ($validate_codes_csv !== '') {
        $api_err = '';
        $missing_codes = get_missing_dgx_codes($oracle_conn, $report_date_ddmmyyyy, $validate_codes_csv, $api_err);
        if ($missing_codes === null) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo $api_err !== '' ? $api_err : 'Loi kiem tra ma diem GDX.';
            exit;
        }
    }
    $api_err = '';
    $rows = oci_run_query($oracle_conn, dgx_report_sql(), [
        'P_NGAYBC' => $report_date_ddmmyyyy,
        'P_MADGD' => $api_report_dgx !== '' ? $api_report_dgx : null,
    ], $api_err);
    if ($rows === null) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $api_err !== '' ? $api_err : 'Loi truy van Oracle.';
        exit;
    }
    $filename = 'bao_cao_giao_dich_xa_' . date('Ymd', strtotime($api_report_date)) . '.xlsx';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('BaoCaoDGX');
    $headers = ['MÃ POS', 'TÊN GDV', 'ĐIỂM GDX', 'TỔ TN', 'SỐ KU', 'KH GN', 'SỐ TIỀN GN', 'KH TNCN', 'KH TKCKH', 'GỬI TK', 'RÚT TK'];
    foreach ($headers as $i => $header) $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
    $rowIndex = 2;
    foreach ($rows as $row) {
        $sheet->setCellValueByColumnAndRow(1, $rowIndex, (string)($row['MAPOS'] ?? ''));
        $sheet->setCellValueByColumnAndRow(2, $rowIndex, (string)($row['TEN_GDV'] ?? ''));
        $sheet->setCellValueByColumnAndRow(3, $rowIndex, (string)($row['DIEM_GDX'] ?? ''));
        $sheet->setCellValueByColumnAndRow(4, $rowIndex, (float)($row['TO_TN'] ?? 0));
        $sheet->setCellValueByColumnAndRow(5, $rowIndex, (float)($row['SO_KU'] ?? 0));
        $sheet->setCellValueByColumnAndRow(6, $rowIndex, (float)($row['KH_GN'] ?? 0));
        $sheet->setCellValueByColumnAndRow(7, $rowIndex, (float)($row['SOTIEN_GN'] ?? 0));
        $sheet->setCellValueByColumnAndRow(8, $rowIndex, (float)($row['KH_TNCN'] ?? 0));
        $sheet->setCellValueByColumnAndRow(9, $rowIndex, (float)($row['KH_TKCKH'] ?? 0));
        $sheet->setCellValueByColumnAndRow(10, $rowIndex, (float)($row['GUITK'] ?? 0));
        $sheet->setCellValueByColumnAndRow(11, $rowIndex, (float)($row['RUTTK'] ?? 0));
        $rowIndex++;
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    if (ob_get_length()) ob_clean();
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
}

$rows = [];
$dgx_codes_from_base = [];
$total_rows = 0;
$total_pages = 1;
$query_error = '';
$query_hint = '';

if (!isset($oracle_conn) || $oracle_conn === null) {
    $query_error = 'Chua ket noi duoc Oracle. Kiem tra OCI8 va thong tin ket noi trong DB/connect_DB.php.';
} else {
    $keyword_upper = strtoupper($keyword);
    $fast_dgx_search = is_fast_dgx_keyword($keyword) ? $keyword_upper : '';
    $base_sql = dgx_base_sql($fast_dgx_search);
    $binds = [
        'from_date_value' => $from_date_oracle,
        'dgx_search' => $fast_dgx_search,
        'dgx_search_prefix' => $fast_dgx_search !== '' ? $fast_dgx_search . '%' : '',
    ];
    $where_sql = '';
    if ($keyword !== '' && $fast_dgx_search === '') {
        $where_sql = ' WHERE (UPPER(q.ma_pgd) LIKE :keyword OR UPPER(q.tenpos) LIKE :keyword OR UPPER(q.dgx) LIKE :keyword OR UPPER(q.ten_diem_gdx) LIKE :keyword)';
        $binds['keyword'] = '%' . $keyword_upper . '%';
    }
    $count_rows = oci_run_query($oracle_conn, 'SELECT COUNT(*) AS TOTAL FROM (SELECT * FROM (' . $base_sql . ') q' . $where_sql . ')', $binds, $query_error);
    if ($count_rows !== null) {
        $total_rows = (int)($count_rows[0]['TOTAL'] ?? 0);
        $total_pages = max(1, (int)ceil($total_rows / $per_page));
        if ($page > $total_pages) {
            $page = $total_pages;
            $offset = ($page - 1) * $per_page;
            $start_row = $offset + 1;
            $end_row = $offset + $per_page;
        }
        $list_binds = $binds;
        $list_binds['start_row'] = $start_row;
        $list_binds['end_row'] = $end_row;
        $list_sql = '
            SELECT *
            FROM (
                SELECT q.*, ROW_NUMBER() OVER (ORDER BY q.ngay_gd DESC NULLS LAST, q.ma_pgd ASC, q.dgx ASC) AS RN
                FROM (SELECT * FROM (' . $base_sql . ') q' . $where_sql . ') q
            )
            WHERE RN BETWEEN :start_row AND :end_row
            ORDER BY RN
        ';
        $list_rows = oci_run_query($oracle_conn, $list_sql, $list_binds, $query_error);
        if ($list_rows !== null) $rows = $list_rows;
        $dgx_rows = oci_run_query($oracle_conn, 'SELECT DISTINCT UPPER(TRIM(q.dgx)) AS DGX FROM (SELECT * FROM (' . $base_sql . ') q' . $where_sql . ') q WHERE TRIM(q.dgx) IS NOT NULL ORDER BY 1', $binds, $query_error);
        if ($dgx_rows !== null) {
            foreach ($dgx_rows as $dgx_row) {
                $dgx_code = strtoupper(trim((string)($dgx_row['DGX'] ?? '')));
                if ($dgx_code !== '') $dgx_codes_from_base[] = $dgx_code;
            }
        }
    }
    if ($query_error !== '') {
        $query_hint = 'Can cap nhat dung ten bang/view va cot trong khoi SQL o DGX/dgx.php.';
    }
}

$canonical_params = [];
if ($keyword !== '') $canonical_params['keyword'] = $keyword;
if ($from_date !== '') $canonical_params['from_date'] = $from_date;
if ($page > 1) $canonical_params['page'] = $page;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tong hop diem DGX</title>
<link rel="stylesheet" href="../view/Style_dgx.php">
</head>
<body>
<div class="container">
    <h1 class="page-title"><a href="<?php echo $index_url; ?>">Tổng Hợp các DGX</a></h1>

    <form method="get" action="<?php echo $index_url; ?>" class="search-form">
        <input type="hidden" name="page" id="search_page" value="<?php echo $page; ?>">
        <input class="field-keyword" type="text" name="keyword" placeholder="Tìm theo mã PGD, tên POS, mã DGX, tên điểm" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
        <input class="field-date" type="date" name="from_date" value="<?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?>" title="Ngày GDX">
        <button type="button" class="btn-fixed-list" id="openFixedListBtn">Danh sách điểm cố định</button>
        <button type="submit" class="btn-search">Search</button>
    </form>

    <div class="meta">Tổng số: <?php echo number_format($total_rows); ?> điểm</div>
    <div class="quick-filter-wrap">
        <input type="text" id="tableQuickFilter" class="table-quick-filter" placeholder="Lọc nhanh trong trang hiện tại (PGD, POS, DGX, ngày...)">
        <input type="text" id="reportDgxInput" class="report-dgx-input" placeholder="Mã DGX (nhiều mã cách nhau bằng ';')" value="<?php echo htmlspecialchars($report_dgx_text, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="date" id="reportDateInput" class="report-date-input" value="<?php echo htmlspecialchars($report_date_text, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn-report-list" id="openReportListBtn">Báo cáo</button>
    </div>

    <?php if ($query_error !== ''): ?>
        <div class="error-box">
            <strong>Lỗi truy vấn Oracle:</strong> <?php echo htmlspecialchars($query_error, ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($query_hint !== ''): ?>
                <div class="error-hint"><?php echo htmlspecialchars($query_hint, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 7%;" class="col-stt">STT</th>
                    <th style="width: 12%;" class="col-ma-pgd">Mã PGD</th>
                    <th style="width: 24%;" class="col-ten-pos">Tên POS</th>
                    <th style="width: 13%;" class="col-ma-dgx">Mã DGX</th>
                    <th class="col-ten-diem">Tên điểm GDX</th>
                    <th style="width: 14%;" class="col-ngay-gdx">Ngày GDX</th>
                    <th style="width: 14%;" class="col-ngay">Ngày GD</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="7" class="empty">Không có dữ liệu phù hợp.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $idx => $row): ?>
                    <?php
                    $stt = $offset + $idx + 1;
                    $ma_pgd = (string)($row['MA_PGD'] ?? '');
                    $ten_pos = (string)($row['TENPOS'] ?? '');
                    $ma_dgx = (string)($row['DGX'] ?? '');
                    $ten_diem_gdx = (string)($row['TEN_DIEM_GDX'] ?? '');
                    $ngay_gdx = (string)($row['NGAY_GDX'] ?? '');
                    $ngay_gd = (string)($row['NGAY_GD'] ?? '');
                    if ($ngay_gdx !== '') {
                        $ts = strtotime($ngay_gdx);
                        if ($ts !== false) $ngay_gdx = date('d/m/Y', $ts);
                    }
                    if ($ngay_gd !== '') {
                        $ts = strtotime($ngay_gd);
                        if ($ts !== false) $ngay_gd = date('d/m/Y', $ts);
                    }
                    ?>
                    <tr>
                        <td class="col-stt"><?php echo number_format($stt); ?></td>
                        <td class="col-ma-pgd"><?php echo htmlspecialchars($ma_pgd, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="col-ten-pos" title="<?php echo htmlspecialchars($ten_pos, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ten_pos, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="col-ma-dgx"><?php echo htmlspecialchars($ma_dgx, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="col-ten-diem" title="<?php echo htmlspecialchars($ten_diem_gdx, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ten_diem_gdx, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="col-ngay-gdx"><?php echo htmlspecialchars($ngay_gdx, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="col-ngay"><?php echo htmlspecialchars($ngay_gd, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="fixed-overlay" id="fixedOverlay" hidden>
        <div class="fixed-dialog" role="dialog" aria-modal="true" aria-labelledby="fixedDialogTitle">
            <h3 id="fixedDialogTitle" class="fixed-drag-handle">Danh sách điểm cố định</h3>
            <div class="fixed-filters">
                <select id="fixedMode" class="fixed-control">
                    <option value="day" <?php echo $fixed_mode === 'day' ? 'selected' : ''; ?>>Ngày</option>
                    <option value="all" <?php echo $fixed_mode === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                </select>
                <input id="fixedDate" class="fixed-control" type="text" list="fixedDateOptions" value="<?php echo htmlspecialchars($fixed_date_text, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nhập hoặc chọn ngày GDX">
                <input id="fixedKeyword" class="fixed-control" type="text" placeholder="Tìm mã điểm hoặc tên điểm">
                <datalist id="fixedDateOptions"></datalist>
                <button type="button" id="fixedFilterBtn" class="fixed-action">Lọc</button>
            </div>
            <div class="fixed-meta" id="fixedMeta">Tổng số: 0 dòng</div>
            <div class="fixed-warning" id="fixedWarn" hidden></div>
            <div class="fixed-table-wrap">
                <table class="fixed-table">
                    <thead>
                        <tr>
                            <th style="width: 16%;">Mã PGD</th>
                            <th style="width: 22%;">Tên POS</th>
                            <th style="width: 20%;">Mã điểm GDX</th>
                            <th style="width: 28%;">Tên điểm GDX</th>
                            <th style="width: 14%;">Ngày GDX</th>
                        </tr>
                    </thead>
                    <tbody id="fixedListBody">
                        <tr><td colspan="5" class="fixed-empty">Chưa tải dữ liệu.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="fixed-actions">
                <button type="button" id="fixedCloseBtn" class="fixed-close">Đóng</button>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/dgx_report_layout.php'; ?>

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
            <?php for ($i = 1; $i <= $total_pages; $i++):
                $base_query['page'] = $i; ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo $index_url; ?>?<?php echo htmlspecialchars(http_build_query($base_query), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $total_pages):
                $base_query['page'] = $page + 1;
            ?>
                <a class="nav" href="<?php echo $index_url; ?>?<?php echo htmlspecialchars(http_build_query($base_query), ENT_QUOTES, 'UTF-8'); ?>">&rsaquo;</a>
            <?php else: ?>
                <span class="nav">&rsaquo;</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var form = document.querySelector('.search-form');
    var searchPageInput = document.getElementById('search_page');
    if (!form || !searchPageInput) return;
    form.addEventListener('submit', function () {
        searchPageInput.value = '1';
    });
})();
</script>
<script>
(function () {
    var quickInput = document.getElementById('tableQuickFilter');
    var table = document.querySelector('.table-wrap table');
    if (!quickInput || !table) return;
    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    function parseQuickTokens(raw) {
        return String(raw || '')
            .toLowerCase()
            .split(/[;\s,]+/)
            .map(function (x) { return x.trim(); })
            .filter(function (x) { return x !== ''; })
            .filter(function (x, idx, arr) { return arr.indexOf(x) === idx; });
    }

    quickInput.addEventListener('input', function () {
        var tokens = parseQuickTokens(quickInput.value || '');
        var rows = tbody.querySelectorAll('tr');
        var visibleCount = 0;
        rows.forEach(function (row) {
            if (row.querySelector('.empty')) {
                row.style.display = '';
                return;
            }
            var text = (row.textContent || '').toLowerCase();
            var show = tokens.length === 0 || tokens.some(function (token) {
                return text.indexOf(token) !== -1;
            });
            row.style.display = show ? '' : 'none';
            if (show) visibleCount += 1;
        });
        var emptyRow = tbody.querySelector('.quick-empty-row');
        if (visibleCount === 0 && !tbody.querySelector('.empty')) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.className = 'quick-empty-row';
                emptyRow.innerHTML = '<td colspan="7" class="empty">Không có dữ liệu phù hợp với bộ lọc nhanh.</td>';
                tbody.appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    });
})();
</script>
<script>
(function () {
    var overlay = document.getElementById('fixedOverlay');
    var openBtn = document.getElementById('openFixedListBtn');
    var closeBtn = document.getElementById('fixedCloseBtn');
    var mode = document.getElementById('fixedMode');
    var dateInput = document.getElementById('fixedDate');
    var filterBtn = document.getElementById('fixedFilterBtn');
    var fixedKeyword = document.getElementById('fixedKeyword');
    var tbody = document.getElementById('fixedListBody');
    var meta = document.getElementById('fixedMeta');
    var warnBox = document.getElementById('fixedWarn');
    var dateOptions = document.getElementById('fixedDateOptions');
    if (!overlay || !openBtn || !closeBtn || !mode || !dateInput || !filterBtn || !fixedKeyword || !tbody || !meta || !warnBox || !dateOptions) return;

    function esc(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderRows(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="fixed-empty">Không có dữ liệu.</td></tr>';
            meta.textContent = 'Tổng số: 0 dòng';
            return;
        }
        var html = '';
        rows.forEach(function (row) {
            html += '<tr>'
                + '<td>' + esc(row.MA_PGD || '') + '</td>'
                + '<td title="' + esc(row.TENPOS || '') + '">' + esc(row.TENPOS || '') + '</td>'
                + '<td>' + esc(row.MA_DIEM_GDX || '') + '</td>'
                + '<td title="' + esc(row.TEN_DIEM_GDX || '') + '">' + esc(row.TEN_DIEM_GDX || '') + '</td>'
                + '<td>' + esc(row.NGAY_GDX || '') + '</td>'
                + '</tr>';
        });
        tbody.innerHTML = html;
    }

    function loadFixedDates() {
        fetch('<?php echo $index_url; ?>?api=fixed_dates', { cache: 'no-store' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || data.ok !== true || !Array.isArray(data.rows)) return;
                var html = '';
                data.rows.forEach(function (row) {
                    var value = String(row.NGAY_GDX || '').trim();
                    if (value !== '') html += '<option value="' + esc(value) + '"></option>';
                });
                dateOptions.innerHTML = html;
            })
            .catch(function () {
                dateOptions.innerHTML = '';
            });
    }

    function loadFixedList() {
        var params = new URLSearchParams();
        params.set('api', 'fixed_points');
        params.set('fixed_mode', String(mode.value || 'day').trim());
        params.set('fixed_date', String(dateInput.value || '').trim());
        params.set('fixed_keyword', String(fixedKeyword.value || '').trim());
        tbody.innerHTML = '<tr><td colspan="5" class="fixed-empty">Đang tải...</td></tr>';
        meta.textContent = 'Đang tải dữ liệu...';
        warnBox.hidden = true;
        warnBox.textContent = '';

        fetch('<?php echo $index_url; ?>?' + params.toString(), { cache: 'no-store' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || data.ok !== true) {
                    var msg = (data && data.message) ? data.message : 'Không tải được dữ liệu.';
                    tbody.innerHTML = '<tr><td colspan="5" class="fixed-empty">' + esc(msg) + '</td></tr>';
                    meta.textContent = 'Tổng số: 0 dòng';
                    return;
                }
                renderRows(data.rows || []);
                meta.textContent = 'Tổng số: ' + (data.total || 0) + ' dòng | Ngày: ' + esc(data.date || '');
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="5" class="fixed-empty">Lỗi kết nối.</td></tr>';
                meta.textContent = 'Tổng số: 0 dòng';
            });
    }

    openBtn.addEventListener('click', function () {
        overlay.hidden = false;
        loadFixedDates();
        loadFixedList();
    });
    closeBtn.addEventListener('click', function () {
        overlay.hidden = true;
    });
    filterBtn.addEventListener('click', loadFixedList);
    mode.addEventListener('change', loadFixedList);
    fixedKeyword.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadFixedList();
        }
    });
    dateInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadFixedList();
        }
    });
})();
</script>
</body>
</html>
