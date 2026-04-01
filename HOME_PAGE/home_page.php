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
ini_set('default_socket_timeout', '6');
@set_time_limit(60);
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../DB/connect_DB.php';
require_once __DIR__ . '/home_page_sql.php';

const HOME_PAGE_DEFAULT_MAPOS = '3400';
const HOME_PAGE_DEFAULT_SOURCE = 'ALL';

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

function default_report_date_input(): string
{
    return date('Y-m-d', strtotime('-1 day'));
}

function resolve_mapos_input($value): string
{
    $value = trim((string)$value);
    return $value === '' ? HOME_PAGE_DEFAULT_MAPOS : $value;
}

function resolve_source_input($value): string
{
    $value = strtoupper(trim((string)$value));
    return in_array($value, ['ALL', 'TW', 'DP'], true) ? $value : HOME_PAGE_DEFAULT_SOURCE;
}

function home_page_source_label(string $source): string
{
    return match ($source) {
        'TW' => 'Nguồn Trung ương',
        'DP' => 'Nguồn địa phương',
        default => 'Toàn nguồn',
    };
}

function home_page_source_bind_value(string $source): ?string
{
    return $source === HOME_PAGE_DEFAULT_SOURCE ? null : $source;
}

function as_number($value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }
    if (is_numeric($value)) {
        return (float)$value;
    }
    $clean = preg_replace('/[^0-9.\-]/', '', (string)$value) ?? '';
    return is_numeric($clean) ? (float)$clean : 0.0;
}

function oci_run_query($conn, string $sql, array $binds = [], ?string &$err = null): ?array
{
    $stmt = db_oci_parse($conn, $sql);
    if ($stmt === false) {
        $e = oci_error($conn);
        $err = $e['message'] ?? 'OCI parse error or blocked write SQL';
        return null;
    }

    $bindVars = [];
    foreach ($binds as $name => $value) {
        $bindVars[$name] = $value;
        if (@oci_bind_by_name($stmt, ':' . $name, $bindVars[$name]) === false) {
            $e = oci_error($stmt);
            $err = $e['message'] ?? 'OCI bind error';
            oci_free_statement($stmt);
            return null;
        }
    }

    if (@oci_execute($stmt, OCI_NO_AUTO_COMMIT) === false) {
        $e = oci_error($stmt);
        $err = $e['message'] ?? 'OCI execute error';
        oci_free_statement($stmt);
        return null;
    }

    $rows = [];
    while (($row = oci_fetch_assoc($stmt)) !== false) {
        $rows[] = $row;
    }
    oci_free_statement($stmt);
    return $rows;
}

function build_breakdown_index(array $rows, string $codeField, string $nameField): array
{
    $groups = [];

    foreach ($rows as $row) {
        $code = trim((string)($row[$codeField] ?? ''));
        if ($code === '') {
            continue;
        }

        $name = trim((string)($row[$nameField] ?? ''));
        $scheme = trim((string)($row['TENCTVAY'] ?? ''));
        if ($scheme === '') {
            $scheme = 'Không rõ CT vay';
        }

        $duno = as_number($row['DUNO'] ?? 0);
        $dnqh = as_number($row['DNQH'] ?? 0);
        $dnkh = as_number($row['DNKH'] ?? 0);

        if (!isset($groups[$code])) {
            $groups[$code] = [
                'CODE' => $code,
                'NAME' => $name,
                'TOTALS' => [
                    'DUNO' => 0.0,
                    'DNQH' => 0.0,
                    'DNKH' => 0.0,
                ],
                'ITEMS' => [],
                'SCHEME_COUNT' => 0,
            ];
        }

        if ($groups[$code]['NAME'] === '' && $name !== '') {
            $groups[$code]['NAME'] = $name;
        }

        $groups[$code]['TOTALS']['DUNO'] += $duno;
        $groups[$code]['TOTALS']['DNQH'] += $dnqh;
        $groups[$code]['TOTALS']['DNKH'] += $dnkh;

        if (!isset($groups[$code]['ITEMS'][$scheme])) {
            $groups[$code]['ITEMS'][$scheme] = [
                'TENCTVAY' => $scheme,
                'DUNO' => 0.0,
                'DNQH' => 0.0,
                'DNKH' => 0.0,
            ];
        }

        $groups[$code]['ITEMS'][$scheme]['DUNO'] += $duno;
        $groups[$code]['ITEMS'][$scheme]['DNQH'] += $dnqh;
        $groups[$code]['ITEMS'][$scheme]['DNKH'] += $dnkh;
    }

    foreach ($groups as &$group) {
        $group['ITEMS'] = array_values($group['ITEMS']);
        usort($group['ITEMS'], fn($a, $b) => $b['DUNO'] <=> $a['DUNO']);
        $group['SCHEME_COUNT'] = count($group['ITEMS']);
    }
    unset($group);

    return $groups;
}

function build_chart_scheme_items(array $rows, int $limit = 6): array
{
    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'TENCTVAY' => trim((string)($row['TENCTVAY'] ?? 'Không rõ CT vay')),
            'DUNO' => as_number($row['DUNO'] ?? 0),
        ];
    }

    usort($items, fn($a, $b) => $b['DUNO'] <=> $a['DUNO']);
    if (count($items) <= $limit) {
        return $items;
    }

    $visible = array_slice($items, 0, $limit);
    $rest = array_slice($items, $limit);
    $restDuno = 0.0;
    foreach ($rest as $item) {
        $restDuno += $item['DUNO'];
    }

    if ($restDuno > 0) {
        $visible[] = [
            'TENCTVAY' => 'Nhóm còn lại',
            'DUNO' => $restDuno,
        ];
    }

    return $visible;
}
function build_home_page_data(string $reportDateOracle, string $mapos, string $sourceFilter, string &$queryError): array
{
    global $oracle_conn;

    $detailRows = [];
    $schemeRows = [];
    $topSchemeRows = [];
    $totalsFromSql = null;
    $totalRowsFromSql = null;
    $totalKhFromSql = null;
    $sourceTotals = [
        'ALL' => 0.0,
        'TW' => 0.0,
        'DP' => 0.0,
    ];

    if (!isset($oracle_conn) || $oracle_conn === null) {
        $queryError = 'Chưa kết nối được Oracle. Kiểm tra OCI8 và thông tin kết nối trong DB/connect_DB.php.';
    } else {
        $binds = [
            'P_NGAYBC' => $reportDateOracle,
            'P_MAPOS' => $mapos,
            'P_NGUONVON' => home_page_source_bind_value($sourceFilter),
        ];
        $sourceTotalBinds = [
            'P_NGAYBC' => $reportDateOracle,
            'P_MAPOS' => $mapos,
        ];

        $detailRows = oci_run_query($oracle_conn, home_page_detail_sql(), $binds, $queryError) ?? [];

        $totalsErr = '';
        $totalsRows = oci_run_query($oracle_conn, home_page_totals_sql(), $binds, $totalsErr);
        if (is_array($totalsRows) && isset($totalsRows[0])) {
            $totalsFromSql = $totalsRows[0];
            $totalRowsFromSql = isset($totalsFromSql['TOTAL_ROWS']) ? (int)$totalsFromSql['TOTAL_ROWS'] : null;
            $totalKhFromSql = isset($totalsFromSql['TOTAL_KH']) ? (int)$totalsFromSql['TOTAL_KH'] : null;
        } elseif ($totalsErr !== '' && $queryError === '') {
            $queryError = $totalsErr;
        }

        $sourceTotalsErr = '';
        $sourceTotalRows = oci_run_query($oracle_conn, home_page_source_totals_sql(), $sourceTotalBinds, $sourceTotalsErr);
        if (is_array($sourceTotalRows) && isset($sourceTotalRows[0])) {
            $sourceTotals['TW'] = as_number($sourceTotalRows[0]['DUNO_TW'] ?? 0);
            $sourceTotals['DP'] = as_number($sourceTotalRows[0]['DUNO_DP'] ?? 0);
            $sourceTotals['ALL'] = $sourceTotals['TW'] + $sourceTotals['DP'];
        } elseif ($sourceTotalsErr !== '' && $queryError === '') {
            $queryError = $sourceTotalsErr;
        }

        $schemeErr = '';
        $schemeRows = oci_run_query($oracle_conn, home_page_scheme_breakdown_sql(), $binds, $schemeErr) ?? [];
        if ($schemeErr !== '' && $queryError === '') {
            $queryError = $schemeErr;
        }

        $topSchemeErr = '';
        $topSchemeRows = oci_run_query($oracle_conn, home_page_top_scheme_sql(), $binds, $topSchemeErr) ?? [];
        if ($topSchemeErr !== '' && $queryError === '') {
            $queryError = $topSchemeErr;
        }
    }

    $totals = [
        'DUNO' => $totalsFromSql !== null ? as_number($totalsFromSql['DUNO'] ?? 0) : 0.0,
        'DNQH' => $totalsFromSql !== null ? as_number($totalsFromSql['DNQH'] ?? 0) : 0.0,
        'DNTH' => $totalsFromSql !== null ? as_number($totalsFromSql['DNTH'] ?? 0) : 0.0,
        'DNKH' => $totalsFromSql !== null ? as_number($totalsFromSql['DNKH'] ?? 0) : 0.0,
        'CHOVAY' => $totalsFromSql !== null ? as_number($totalsFromSql['CHOVAY'] ?? 0) : 0.0,
        'THUNO' => $totalsFromSql !== null ? as_number($totalsFromSql['THUNO'] ?? 0) : 0.0,
    ];

    $byPos = [];
    $byXa = [];

    foreach ($detailRows as $row) {
        $duno = as_number($row['DUNO'] ?? 0);
        $dnqh = as_number($row['DNQH'] ?? 0);
        $dnth = as_number($row['DNTH'] ?? 0);
        $dnkh = as_number($row['DNKH'] ?? 0);
        $chovay = as_number($row['CHOVAY'] ?? 0);
        $thuno = as_number($row['THUNO'] ?? 0);

        if ($totalsFromSql === null) {
            $totals['DUNO'] += $duno;
            $totals['DNQH'] += $dnqh;
            $totals['DNTH'] += $dnth;
            $totals['DNKH'] += $dnkh;
            $totals['CHOVAY'] += $chovay;
            $totals['THUNO'] += $thuno;
        }

        $maposRow = trim((string)($row['MAPOS'] ?? ''));
        $tenpos = trim((string)($row['TENPOS'] ?? ''));
        if ($maposRow !== '') {
            if (!isset($byPos[$maposRow])) {
                $byPos[$maposRow] = [
                    'MAPOS' => $maposRow,
                    'TENPOS' => $tenpos,
                    'DUNO' => 0.0,
                    'DNQH' => 0.0,
                    'DNKH' => 0.0,
                    'COUNT' => 0,
                ];
            }
            $byPos[$maposRow]['DUNO'] += $duno;
            $byPos[$maposRow]['DNQH'] += $dnqh;
            $byPos[$maposRow]['DNKH'] += $dnkh;
            $byPos[$maposRow]['COUNT'] += 1;
            if ($byPos[$maposRow]['TENPOS'] === '' && $tenpos !== '') {
                $byPos[$maposRow]['TENPOS'] = $tenpos;
            }
        }

        $maxa = trim((string)($row['MAXA'] ?? ''));
        $tenxa = trim((string)($row['TENXA'] ?? ''));
        if ($maxa !== '') {
            if (!isset($byXa[$maxa])) {
                $byXa[$maxa] = [
                    'MAXA' => $maxa,
                    'TENXA' => $tenxa,
                    'DUNO' => 0.0,
                    'DNQH' => 0.0,
                    'DNKH' => 0.0,
                    'COUNT' => 0,
                ];
            }
            $byXa[$maxa]['DUNO'] += $duno;
            $byXa[$maxa]['DNQH'] += $dnqh;
            $byXa[$maxa]['DNKH'] += $dnkh;
            $byXa[$maxa]['COUNT'] += 1;
            if ($byXa[$maxa]['TENXA'] === '' && $tenxa !== '') {
                $byXa[$maxa]['TENXA'] = $tenxa;
            }
        }
    }

    $posList = array_values($byPos);
    $xaList = array_values($byXa);
    usort($posList, fn($a, $b) => $b['DUNO'] <=> $a['DUNO']);
    usort($xaList, fn($a, $b) => $b['DUNO'] <=> $a['DUNO']);

    $detailsByPos = build_breakdown_index($schemeRows, 'MAPOS', 'TENPOS');
    $detailsByXa = build_breakdown_index($schemeRows, 'MAXA', 'TENXA');

    return [
        'totals' => $totals,
        'source_totals' => $sourceTotals,
        'top_scheme' => build_chart_scheme_items($topSchemeRows),
        'pos_list' => $posList,
        'xa_list' => $xaList,
        'details_by_pos' => $detailsByPos,
        'details_by_xa' => $detailsByXa,
        'total_kh' => $totalKhFromSql !== null ? $totalKhFromSql : 0,
        'total_rows' => $totalRowsFromSql !== null ? $totalRowsFromSql : count($detailRows),
        'active_source' => $sourceFilter,
        'active_source_label' => home_page_source_label($sourceFilter),
        'error' => $queryError,
    ];
}

$indexUrl = '/dashboard/HOME_PAGE/home_page.php';
$maposDefault = HOME_PAGE_DEFAULT_MAPOS;
$reportDateDefault = default_report_date_input();
$reportDate = normalize_date_input((string)($_GET['report_date'] ?? $reportDateDefault));
if ($reportDate === '') {
    $reportDate = $reportDateDefault;
}
$mapos = resolve_mapos_input($_GET['mapos'] ?? $maposDefault);
$sourceFilter = resolve_source_input($_GET['source'] ?? HOME_PAGE_DEFAULT_SOURCE);
$reportDateOracle = date('d/m/Y', strtotime($reportDate));

if (isset($_GET['api']) && $_GET['api'] === 'data') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: application/json; charset=UTF-8');

    $queryError = '';
    $data = build_home_page_data($reportDateOracle, $mapos, $sourceFilter, $queryError);

    echo json_encode([
        'ok' => $queryError === '',
        'error' => $queryError,
        'report_date' => $reportDateOracle,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);

    if (isset($oracle_conn) && (is_resource($oracle_conn) || is_object($oracle_conn))) {
        @oci_close($oracle_conn);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HOME PAGE</title>
<link rel="stylesheet" href="../view/Style_home_page.php">
</head>
<body>
<div class="page-shell">
    <section class="top-layout">
        <div class="top-stack">
            <header class="hero">
                <div class="hero-copy">
                    <h1 class="page-title">
                        <a href="<?php echo $indexUrl; ?>">
                            <span class="page-title__line">Tổng hợp dư nợ theo POS / Xã</span>
                        </a>
                    </h1>
                    <div class="hero-meta" aria-label="Thông tin tổng quan báo cáo">
                        <span class="meta-chip">Ngày BC: <strong id="metaDate"><?php echo htmlspecialchars($reportDateOracle, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                        <span class="meta-chip">Tổng bản ghi: <strong id="metaTotal">0</strong></span>
                        <span class="meta-chip">Tổng KH: <strong id="metaKh">0</strong></span>
                    </div>
                </div>
            </header>

            <form method="get" action="<?php echo $indexUrl; ?>" class="search-form" id="searchForm">
                <input type="hidden" name="source" id="sourceFilter" value="<?php echo htmlspecialchars($sourceFilter, ENT_QUOTES, 'UTF-8'); ?>">
                <label for="reportDate">Ngày BC</label>
                <input type="date" name="report_date" id="reportDate" value="<?php echo htmlspecialchars($reportDate, ENT_QUOTES, 'UTF-8'); ?>">
                <label for="mapos">MAPOS</label>
                <input type="text" name="mapos" id="mapos" placeholder="MAPOS" value="<?php echo htmlspecialchars($mapos, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">Tải báo cáo</button>
            </form>

            <div id="errorBox" class="error-box" style="display:none;"></div>
            <div class="loading" id="loadingBox" role="status" aria-live="polite">
                <div class="loading-badge">Đang lấy số liệu</div>
                <div class="loading-head">
                    <div class="spinner" aria-hidden="true"></div>
                    <div class="loading-copy">
                        <strong class="loading-title">Hệ thống đang tải dữ liệu báo cáo</strong>
                        <div class="loading-text">Vui lòng chờ trong giây lát để đồng bộ số liệu mới nhất.</div>
                    </div>
                </div>
                <div class="loading-grid" aria-hidden="true">
                    <span class="loading-card loading-card--wide"></span>
                    <span class="loading-card"></span>
                    <span class="loading-card"></span>
                    <span class="loading-card"></span>
                </div>
            </div>

            <section class="panel panel-source-toolbar">
                <div class="panel-head panel-head--compact">
                    <div>
                        <p class="panel-kicker">Nguồn vốn</p>
                        <h2>Bộ lọc nguồn</h2>
                    </div>
                    <div class="source-head-actions">
                        <span class="panel-count panel-count--source" id="sourceCurrent">Đang xem: <?php echo htmlspecialchars(home_page_source_label($sourceFilter), ENT_QUOTES, 'UTF-8'); ?></span>
                        <button type="button" class="source-reset" id="sourceResetBtn">Bỏ lọc</button>
                    </div>
                </div>
                <div class="source-toolbar" id="sourceToolbar">
                    <button type="button" class="source-option" data-source="ALL" aria-pressed="false">
                        <span class="source-option__check" aria-hidden="true"></span>
                        <span class="source-option__body">
                            <span class="source-option__eyebrow">Toàn nguồn</span>
                            <small>Hiển thị đầy đủ</small>
                        </span>
                        <strong id="sourceAllValue">--</strong>
                    </button>
                    <button type="button" class="source-option" data-source="TW" aria-pressed="false">
                        <span class="source-option__check" aria-hidden="true"></span>
                        <span class="source-option__body">
                            <span class="source-option__eyebrow">TW</span>
                            <small>Nguồn Trung ương</small>
                        </span>
                        <strong id="sourceTwValue">--</strong>
                    </button>
                    <button type="button" class="source-option" data-source="DP" aria-pressed="false">
                        <span class="source-option__check" aria-hidden="true"></span>
                        <span class="source-option__body">
                            <span class="source-option__eyebrow">DP</span>
                            <small>Nguồn địa phương</small>
                        </span>
                        <strong id="sourceDpValue">--</strong>
                    </button>
                </div>
            </section>
        </div>

        <article class="panel scheme-panel">
            <div class="panel-head panel-head--compact panel-head--chart">
                <div>
                    <p class="panel-kicker">Chương trình vay</p>
                    <h2>Biểu đồ dư nợ theo chương trình</h2>
                </div>
                <p class="panel-note-inline">Hover hoặc chạm vào lát cắt để xem chi tiết.</p>
            </div>
            <div id="schemeChart" class="scheme-chart empty">
                <div class="empty-box">Đang tải dữ liệu...</div>
            </div>
        </article>
    </section>

    <section class="cards" id="cardsBox">
        <article class="card accent-card">
            <span class="label">Tổng dư nợ</span>
            <strong class="value" id="cardDuno">--</strong>
        </article>
        <article class="card">
            <span class="label">Nợ quá hạn</span>
            <strong class="value" id="cardDnqh">--</strong>
        </article>
        <article class="card">
            <span class="label">Nợ trong hạn</span>
            <strong class="value" id="cardDnth">--</strong>
        </article>
        <article class="card">
            <span class="label">Nợ khoanh</span>
            <strong class="value" id="cardDnkh">--</strong>
        </article>
        <article class="card">
            <span class="label">Cho vay</span>
            <strong class="value" id="cardChovay">--</strong>
        </article>
        <article class="card">
            <span class="label">Thu nợ</span>
            <strong class="value" id="cardThuno">--</strong>
        </article>
    </section>

    <section class="split-layout"> 
        <article class="panel list-panel list-panel--pos">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">POS Layout</p>
                    <h2>Danh sách theo POS</h2>
                </div>
                <span class="panel-count" id="posCount">0 dòng</span>
            </div>
            <p class="panel-note">Khối bên trái là tổng hợp theo POS. Màu nhấn thiên xanh dương để dễ nhận diện điểm giao dịch.</p>
            <div id="posList" class="summary-list">
                <div class="empty-box">Đang tải dữ liệu...</div>
            </div>
        </article>

        <article class="panel list-panel list-panel--xa">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">Xã Layout</p>
                    <h2>Danh sách theo Xã</h2>
                </div>
                <span class="panel-count" id="xaCount">0 dòng</span>
            </div>
            <p class="panel-note">Khối bên phải là tổng hợp theo xã. Màu nhấn thiên xanh lá để tách rõ với phần POS.</p>
            <div id="xaList" class="summary-list">
                <div class="empty-box">Đang tải dữ liệu...</div>
            </div>
        </article>
    </section>
</div>

<div class="detail-modal" id="detailModal" aria-hidden="true">
    <div class="detail-backdrop" id="detailBackdrop"></div>
    <div class="detail-dialog" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
        <div class="detail-header">
            <div>
                <p class="detail-kicker" id="detailKicker">Chi tiết</p>
                <h2 id="detailTitle">Chọn 1 dòng</h2>
                <p class="detail-subtitle" id="detailSubtitle">Chi tiết sẽ hiển thị theo chương trình vay.</p>
            </div>
            <button type="button" class="modal-close" id="detailCloseBtn">Đóng</button>
        </div>

        <div class="detail-stats">
            <article class="detail-stat">
                <span class="detail-stat-label">Tổng dư nợ</span>
                <strong id="detailDuno">0</strong>
            </article>
            <article class="detail-stat">
                <span class="detail-stat-label">Tổng nợ quá hạn</span>
                <strong id="detailDnqh">0</strong>
            </article>
            <article class="detail-stat">
                <span class="detail-stat-label">Tổng nợ khoanh</span>
                <strong id="detailDnkh">0</strong>
            </article>
            <article class="detail-stat">
                <span class="detail-stat-label">Số CT vay</span>
                <strong id="detailSchemeCount">0</strong>
            </article>
        </div>

        <div class="detail-table-wrap">
            <table class="detail-table">
                <thead>
                    <tr>
                        <th style="width: 43%;">CHƯƠNG TRÌNH VAY</th>
                        <th style="width: 19%;">DƯ NỢ</th>
                        <th style="width: 19%;">DNQH</th>
                        <th style="width: 19%;">DNKH</th>
                    </tr>
                </thead>
                <tbody id="detailBody">
                    <tr><td colspan="4" class="empty-row">Chưa có dữ liệu.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function(){
    var pageUrl = <?php echo json_encode($indexUrl, JSON_UNESCAPED_UNICODE); ?>;
    var loadingBox = document.getElementById('loadingBox');
    var errorBox = document.getElementById('errorBox');
    var metaDate = document.getElementById('metaDate');
    var metaTotal = document.getElementById('metaTotal');
    var metaKh = document.getElementById('metaKh');
    var searchForm = document.getElementById('searchForm');
    var reportDateInput = document.getElementById('reportDate');
    var maposInput = document.getElementById('mapos');
    var sourceInput = document.getElementById('sourceFilter');
    var sourceToolbar = document.getElementById('sourceToolbar');
    var sourceCurrent = document.getElementById('sourceCurrent');
    var sourceResetBtn = document.getElementById('sourceResetBtn');
    var sourceAllValue = document.getElementById('sourceAllValue');
    var sourceTwValue = document.getElementById('sourceTwValue');
    var sourceDpValue = document.getElementById('sourceDpValue');
    var posList = document.getElementById('posList');
    var xaList = document.getElementById('xaList');
    var posCount = document.getElementById('posCount');
    var xaCount = document.getElementById('xaCount');
    var schemeChart = document.getElementById('schemeChart');
    var detailModal = document.getElementById('detailModal');
    var detailBackdrop = document.getElementById('detailBackdrop');
    var detailCloseBtn = document.getElementById('detailCloseBtn');
    var detailKicker = document.getElementById('detailKicker');
    var detailTitle = document.getElementById('detailTitle');
    var detailSubtitle = document.getElementById('detailSubtitle');
    var detailDuno = document.getElementById('detailDuno');
    var detailDnqh = document.getElementById('detailDnqh');
    var detailDnkh = document.getElementById('detailDnkh');
    var detailSchemeCount = document.getElementById('detailSchemeCount');
    var detailBody = document.getElementById('detailBody');
    var cards = {
        DUNO: document.getElementById('cardDuno'),
        DNQH: document.getElementById('cardDnqh'),
        DNTH: document.getElementById('cardDnth'),
        DNKH: document.getElementById('cardDnkh'),
        CHOVAY: document.getElementById('cardChovay'),
        THUNO: document.getElementById('cardThuno')
    };

    var currentData = null;
    var currentSource = <?php echo json_encode($sourceFilter, JSON_UNESCAPED_UNICODE); ?>;
    var schemePalette = ['#0f5e79', '#1683a4', '#21a7b5', '#40c2bf', '#72dbc3', '#9be7d0', '#d7f5e9'];
    function fmt(value){
        var n = Number(value);
        if (!isFinite(n)) {
            return '--';
        }
        return n.toLocaleString('en-US', { maximumFractionDigits: 0 });
    }

    function esc(value){
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function normalizeSource(value){
        value = String(value == null ? '' : value).toUpperCase().trim();
        return value === 'TW' || value === 'DP' ? value : 'ALL';
    }

    function sourceLabel(value){
        value = normalizeSource(value);
        if (value === 'TW') {
            return 'Nguồn Trung ương';
        }
        if (value === 'DP') {
            return 'Nguồn địa phương';
        }
        return 'Toàn nguồn';
    }

    function setLoading(isLoading){
        if (loadingBox) {
            loadingBox.style.display = isLoading ? 'flex' : 'none';
        }
    }

    function showError(message){
        if (!errorBox) {
            return;
        }
        errorBox.textContent = message || 'Không tải được dữ liệu.';
        errorBox.style.display = '';
    }

    function clearError(){
        if (errorBox) {
            errorBox.style.display = 'none';
        }
    }

    function getFilters(){
        return {
            report_date: reportDateInput ? reportDateInput.value : '',
            mapos: maposInput ? maposInput.value.trim() : '',
            source: normalizeSource(sourceInput ? sourceInput.value : currentSource)
        };
    }

    function buildUrl(withApi){
        var filters = getFilters();
        var params = new URLSearchParams();
        if (withApi) {
            params.set('api', 'data');
        }
        if (filters.report_date) {
            params.set('report_date', filters.report_date);
        }
        if (filters.mapos) {
            params.set('mapos', filters.mapos);
        }
        if (filters.source !== 'ALL') {
            params.set('source', filters.source);
        }
        var query = params.toString();
        return pageUrl + (query ? ('?' + query) : '');
    }

    function syncUrl(){
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, '', buildUrl(false));
        }
    }

    function renderSourceToolbar(sourceTotals){
        var totals = sourceTotals || {};
        currentSource = normalizeSource(sourceInput ? sourceInput.value : currentSource);

        if (sourceAllValue) {
            sourceAllValue.textContent = fmt(totals.ALL || 0);
        }
        if (sourceTwValue) {
            sourceTwValue.textContent = fmt(totals.TW || 0);
        }
        if (sourceDpValue) {
            sourceDpValue.textContent = fmt(totals.DP || 0);
        }
        if (sourceCurrent) {
            sourceCurrent.textContent = 'Đang xem: ' + sourceLabel(currentSource);
        }
        if (sourceResetBtn) {
            sourceResetBtn.disabled = currentSource === 'ALL';
        }

        document.querySelectorAll('.source-option').forEach(function(button){
            var buttonSource = normalizeSource(button.getAttribute('data-source'));
            var isActive = buttonSource === currentSource;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function polarToCartesian(cx, cy, radius, angleInDegrees){
        var angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;
        return {
            x: cx + (radius * Math.cos(angleInRadians)),
            y: cy + (radius * Math.sin(angleInRadians))
        };
    }

    function describeDonutSlice(cx, cy, outerRadius, innerRadius, startAngle, endAngle){
        var safeEndAngle = endAngle;
        if (safeEndAngle - startAngle >= 360) {
            safeEndAngle = startAngle + 359.999;
        }

        var startOuter = polarToCartesian(cx, cy, outerRadius, safeEndAngle);
        var endOuter = polarToCartesian(cx, cy, outerRadius, startAngle);
        var startInner = polarToCartesian(cx, cy, innerRadius, startAngle);
        var endInner = polarToCartesian(cx, cy, innerRadius, safeEndAngle);
        var largeArcFlag = safeEndAngle - startAngle > 180 ? 1 : 0;

        return [
            'M', startOuter.x.toFixed(3), startOuter.y.toFixed(3),
            'A', outerRadius, outerRadius, 0, largeArcFlag, 0, endOuter.x.toFixed(3), endOuter.y.toFixed(3),
            'L', startInner.x.toFixed(3), startInner.y.toFixed(3),
            'A', innerRadius, innerRadius, 0, largeArcFlag, 1, endInner.x.toFixed(3), endInner.y.toFixed(3),
            'Z'
        ].join(' ');
    }

    function clearSchemeActive(){
        if (!schemeChart) {
            return;
        }
        schemeChart.querySelectorAll('[data-scheme-index]').forEach(function(node){
            node.classList.remove('is-active');
        });
    }

    function setSchemeSpotlight(eyebrow, name, value, color){
        if (!schemeChart) {
            return;
        }
        var spotlightEyebrow = schemeChart.querySelector('.scheme-spotlight__eyebrow');
        var spotlightName = schemeChart.querySelector('.scheme-spotlight__name');
        var spotlightValue = schemeChart.querySelector('.scheme-spotlight__value');
        if (spotlightEyebrow) {
            spotlightEyebrow.textContent = eyebrow;
        }
        if (spotlightName) {
            spotlightName.textContent = name;
        }
        if (spotlightValue) {
            spotlightValue.textContent = value;
        }
        schemeChart.style.setProperty('--scheme-active-color', color || 'rgba(22, 131, 164, 0.16)');
    }

    function renderSchemeChart(items){
        if (!schemeChart) {
            return;
        }

        var chartItems = Array.isArray(items)
            ? items.filter(function(item){ return Number(item.DUNO || 0) > 0; })
            : [];

        if (chartItems.length === 0) {
            schemeChart.className = 'scheme-chart empty';
            schemeChart.innerHTML = '<div class="empty-box">Không có dữ liệu chương trình vay.</div>';
            return;
        }

        var total = chartItems.reduce(function(sum, item){
            return sum + Number(item.DUNO || 0);
        }, 0);

        var cx = 120;
        var cy = 120;
        var outerRadius = 98;
        var innerRadius = 56;
        var startAngle = 0;
        var slicesHtml = '';
        var legendHtml = '';

        chartItems.forEach(function(item, index){
            var value = Number(item.DUNO || 0);
            var ratio = total > 0 ? (value / total) : 0;
            var sweep = ratio * 360;
            var endAngle = startAngle + sweep;
            var color = schemePalette[index % schemePalette.length];
            var label = esc(item.TENCTVAY || 'Không rõ CT vay');
            var valueText = fmt(value);

            slicesHtml += '<path class="scheme-slice" data-scheme-index="' + index + '" fill="' + color + '" tabindex="0" d="' + describeDonutSlice(cx, cy, outerRadius, innerRadius, startAngle, endAngle) + '"><title>' + label + ': ' + valueText + '</title></path>';
            legendHtml += '<button type="button" class="scheme-legend__item" data-scheme-index="' + index + '">'
                + '<span class="scheme-legend__dot" style="background:' + color + '"></span>'
                + '<span class="scheme-legend__copy">'
                + '<strong title="' + label + '">' + label + '</strong>'
                + '<small>' + valueText + '</small>'
                + '</span>'
                + '</button>';
            startAngle = endAngle;
        });

        schemeChart.className = 'scheme-chart';
        schemeChart.innerHTML = ''
            + '<div class="scheme-chart__visual">'
            + '    <svg class="scheme-chart__svg" viewBox="0 0 240 240" aria-hidden="true">'
            + '        <circle cx="120" cy="120" r="103" fill="none" stroke="rgba(22, 131, 164, 0.08)" stroke-width="18"></circle>'
            +          slicesHtml
            + '    </svg>'
            + '    <div class="scheme-spotlight">'
            + '        <span class="scheme-spotlight__eyebrow">Tổng dư nợ</span>'
            + '        <strong class="scheme-spotlight__name">Danh mục vay</strong>'
            + '        <span class="scheme-spotlight__value">' + fmt(total) + '</span>'
            + '    </div>'
            + '</div>'
            + '<div class="scheme-chart__legend">' + legendHtml + '</div>';
        function activate(index){
            var item = chartItems[index];
            if (!item) {
                return;
            }
            clearSchemeActive();
            schemeChart.querySelectorAll('[data-scheme-index="' + index + '"]').forEach(function(node){
                node.classList.add('is-active');
            });
            setSchemeSpotlight('Chương trình vay', item.TENCTVAY || 'Không rõ CT vay', fmt(item.DUNO || 0), schemePalette[index % schemePalette.length]);
        }

        function reset(){
            clearSchemeActive();
            setSchemeSpotlight('Tổng dư nợ', 'Danh mục vay', fmt(total), 'rgba(22, 131, 164, 0.16)');
        }

        schemeChart.querySelectorAll('[data-scheme-index]').forEach(function(node){
            var index = Number(node.getAttribute('data-scheme-index'));
            node.addEventListener('mouseenter', function(){ activate(index); });
            node.addEventListener('focus', function(){ activate(index); });
            node.addEventListener('click', function(){ activate(index); });
        });

        var visual = schemeChart.querySelector('.scheme-chart__visual');
        var legend = schemeChart.querySelector('.scheme-chart__legend');
        if (visual) {
            visual.addEventListener('mouseleave', reset);
        }
        if (legend) {
            legend.addEventListener('mouseleave', reset);
        }
        schemeChart.addEventListener('focusout', function(){
            window.requestAnimationFrame(function(){
                if (!schemeChart.contains(document.activeElement)) {
                    reset();
                }
            });
        });

        reset();
    }

    function renderSummaryList(target, items, kind){
        if (!target) {
            return;
        }
        if (!items || items.length === 0) {
            target.innerHTML = '<div class="empty-box">Không có dữ liệu.</div>';
            return;
        }

        var codeKey = kind === 'pos' ? 'MAPOS' : 'MAXA';
        var nameKey = kind === 'pos' ? 'TENPOS' : 'TENXA';
        var modifier = kind === 'pos' ? 'summary-item--pos' : 'summary-item--xa';
        var badgeText = kind === 'pos' ? 'Xem PGD' : 'Xem xã';
        var html = '';

        items.forEach(function(item){
            var code = esc(item[codeKey] || '');
            var name = esc(item[nameKey] || '');
            html += '<button type="button" class="summary-item ' + modifier + '" data-kind="' + kind + '" data-key="' + code + '">'
                + '<span class="summary-item__top">'
                + '<span class="summary-item__code">' + code + '</span>'
                + '<span class="summary-item__badge">' + badgeText + '</span>'
                + '</span>'
                + '<span class="summary-item__name" title="' + name + '">' + name + '</span>'
                + '<span class="summary-item__metrics">'
                + '<span class="summary-item__metric"><strong>' + fmt(item.DUNO) + '</strong><small>Dư nợ</small></span>'
                + '<span class="summary-item__metric"><strong>' + fmt(item.DNQH) + '</strong><small>DNQH</small></span>'
                + '<span class="summary-item__metric"><strong>' + fmt(item.DNKH) + '</strong><small>DNKH</small></span>'
                + '</span>'
                + '</button>';
        });

        target.innerHTML = html;
    }

    function renderDetailRows(items){
        if (!detailBody) {
            return;
        }
        if (!items || items.length === 0) {
            detailBody.innerHTML = '<tr><td colspan="4" class="empty-row">Không có dữ liệu chi tiết.</td></tr>';
            return;
        }

        var html = '';
        items.forEach(function(item){
            var scheme = esc(item.TENCTVAY || 'Không rõ CT vay');
            html += '<tr>'
                + '<td title="' + scheme + '">' + scheme + '</td>'
                + '<td class="num">' + fmt(item.DUNO) + '</td>'
                + '<td class="num">' + fmt(item.DNQH) + '</td>'
                + '<td class="num">' + fmt(item.DNKH) + '</td>'
                + '</tr>';
        });
        detailBody.innerHTML = html;
    }

    function closeDetail(){
        if (!detailModal) {
            return;
        }
        detailModal.classList.remove('is-open');
        detailModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    function openDetail(kind, key){
        if (!currentData) {
            return;
        }

        var source = kind === 'pos' ? currentData.details_by_pos : currentData.details_by_xa;
        if (!source || !source[key]) {
            return;
        }

        var detail = source[key];
        var totals = detail.TOTALS || {};
        var isPos = kind === 'pos';
        var fallbackTitle = isPos ? ('PGD ' + key) : ('Xã ' + key);

        detailKicker.textContent = isPos ? 'Chi tiết theo PGD' : 'Chi tiết theo xã';
        detailTitle.textContent = detail.NAME || fallbackTitle;
        detailSubtitle.textContent = isPos ? ('Mã POS: ' + key) : ('Mã xã: ' + key);
        detailDuno.textContent = fmt(totals.DUNO || 0);
        detailDnqh.textContent = fmt(totals.DNQH || 0);
        detailDnkh.textContent = fmt(totals.DNKH || 0);
        detailSchemeCount.textContent = fmt(detail.SCHEME_COUNT || 0);
        renderDetailRows(detail.ITEMS || []);

        detailModal.classList.add('is-open');
        detailModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function bindListEvents(target){
        if (!target) {
            return;
        }
        target.addEventListener('click', function(event){
            var button = event.target.closest('.summary-item');
            if (!button) {
                return;
            }
            openDetail(button.getAttribute('data-kind'), button.getAttribute('data-key'));
        });
    }

    function setSource(source){
        currentSource = normalizeSource(source);
        if (sourceInput) {
            sourceInput.value = currentSource;
        }
        renderSourceToolbar(currentData ? currentData.source_totals : null);
    }

    bindListEvents(posList);
    bindListEvents(xaList);

    if (searchForm) {
        searchForm.addEventListener('submit', function(event){
            event.preventDefault();
            syncUrl();
            fetchData();
        });
    }

    if (sourceToolbar) {
        sourceToolbar.addEventListener('click', function(event){
            var button = event.target.closest('.source-option');
            if (!button) {
                return;
            }
            setSource(button.getAttribute('data-source'));
            syncUrl();
            fetchData();
        });
    }

    if (sourceResetBtn) {
        sourceResetBtn.addEventListener('click', function(){
            if (currentSource === 'ALL') {
                return;
            }
            setSource('ALL');
            syncUrl();
            fetchData();
        });
    }

    if (detailCloseBtn) {
        detailCloseBtn.addEventListener('click', closeDetail);
    }
    if (detailBackdrop) {
        detailBackdrop.addEventListener('click', closeDetail);
    }
    document.addEventListener('keydown', function(event){
        if (event.key === 'Escape') {
            closeDetail();
        }
    });

    function fetchData(){
        setLoading(true);
        clearError();

        fetch(buildUrl(true), { cache: 'no-store' })
            .then(function(response){
                return response.ok ? response.json() : null;
            })
            .then(function(payload){
                if (!payload) {
                    showError('Không tải được dữ liệu.');
                    renderSummaryList(posList, [], 'pos');
                    renderSummaryList(xaList, [], 'xa');
                    renderSchemeChart([]);
                    return;
                }

                if (!payload.ok) {
                    showError(payload.error || 'Lỗi truy vấn.');
                    renderSummaryList(posList, [], 'pos');
                    renderSummaryList(xaList, [], 'xa');
                    renderSchemeChart([]);
                    return;
                }

                if (payload.report_date && metaDate) {
                    metaDate.textContent = payload.report_date;
                }

                currentData = payload.data || {};
                setSource(currentData.active_source || currentSource);
                closeDetail();

                var totals = currentData.totals || {};
                cards.DUNO.textContent = fmt(totals.DUNO);
                cards.DNQH.textContent = fmt(totals.DNQH);
                cards.DNTH.textContent = fmt(totals.DNTH);
                cards.DNKH.textContent = fmt(totals.DNKH);
                cards.CHOVAY.textContent = fmt(totals.CHOVAY);
                cards.THUNO.textContent = fmt(totals.THUNO);
                if (metaTotal) {
                    metaTotal.textContent = fmt(currentData.total_rows || 0);
                }
                if (metaKh) {
                    metaKh.textContent = fmt(currentData.total_kh || 0);
                }

                renderSourceToolbar(currentData.source_totals || {});

                var posItems = currentData.pos_list || [];
                var xaItems = currentData.xa_list || [];
                if (posCount) {
                    posCount.textContent = fmt(posItems.length) + ' dòng';
                }
                if (xaCount) {
                    xaCount.textContent = fmt(xaItems.length) + ' dòng';
                }

                renderSummaryList(posList, posItems, 'pos');
                renderSummaryList(xaList, xaItems, 'xa');
                renderSchemeChart(currentData.top_scheme || []);
            })
            .catch(function(){
                showError('Lỗi kết nối khi tải dữ liệu.');
                renderSummaryList(posList, [], 'pos');
                renderSummaryList(xaList, [], 'xa');
                renderSchemeChart([]);
            })
            .finally(function(){
                setLoading(false);
            });
    }

    renderSourceToolbar(null);
    fetchData();
})();
</script>
</body>
</html>
<?php
if (isset($oracle_conn) && (is_resource($oracle_conn) || is_object($oracle_conn))) {
    @oci_close($oracle_conn);
}
?>