<?php
require_once __DIR__ . '/../FUNC_SHARE/ham_dung_chung.php';
dashboard_chan_ip_khong_hop_le();
dashboard_khoi_tao_phien('DASHBOARD_VB_IOT_SESSID');
ini_set('default_socket_timeout', '6');
@set_time_limit(60);
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../DB/connect_DB.php';
require_once __DIR__ . '/home_page_sql.php';

const HOME_PAGE_DEFAULT_MAPOS = '3400';
const HOME_PAGE_DEFAULT_SOURCE = 'ALL';

function chuan_hoa_ngay(string $value): string
{
    return dashboard_chuan_hoa_ngay($value);
}

function ngay_bao_cao_mac_dinh(): string
{
    return date('Y-m-d', strtotime('-1 day'));
}

function xu_ly_mapos_dau_vao($value): string
{
    $value = trim((string)$value);
    return $value === '' ? HOME_PAGE_DEFAULT_MAPOS : $value;
}

function xu_ly_nguon_von_dau_vao($value): string
{
    $value = strtoupper(trim((string)$value));
    return in_array($value, ['ALL', 'TW', 'DP'], true) ? $value : HOME_PAGE_DEFAULT_SOURCE;
}

function nhan_nguon_von_home_page(string $source): string
{
    return match ($source) {
        'TW' => 'Nguồn Trung ương',
        'DP' => 'Nguồn địa phương',
        default => 'Toàn nguồn',
    };
}

function gia_tri_bind_nguon_von_home_page(string $source): ?string
{
    return $source === HOME_PAGE_DEFAULT_SOURCE ? null : $source;
}

function ep_kieu_so($value): float
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

function oracle_chay_truy_van($conn, string $sql, array $binds = [], ?string &$err = null): ?array
{
    return dashboard_oracle_chay_truy_van($conn, $sql, $binds, $err);
}

function lap_chi_tiet_theo_don_vi(array $rows, string $codeField, string $nameField): array
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

        $duno = ep_kieu_so($row['DUNO'] ?? 0);
        $dnqh = ep_kieu_so($row['DNQH'] ?? 0);
        $dnkh = ep_kieu_so($row['DNKH'] ?? 0);
        $chovay = ep_kieu_so($row['CHOVAY'] ?? 0);
        $thuno = ep_kieu_so($row['THUNO'] ?? 0);

        if (!isset($groups[$code])) {
            $groups[$code] = [
                'CODE' => $code,
                'NAME' => $name,
                'TOTALS' => [
                    'DUNO' => 0.0,
                    'DNQH' => 0.0,
                    'DNKH' => 0.0,
                    'CHOVAY' => 0.0,
                    'THUNO' => 0.0,
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
        $groups[$code]['TOTALS']['CHOVAY'] += $chovay;
        $groups[$code]['TOTALS']['THUNO'] += $thuno;

        if (!isset($groups[$code]['ITEMS'][$scheme])) {
            $groups[$code]['ITEMS'][$scheme] = [
                'TENCTVAY' => $scheme,
                'DUNO' => 0.0,
                'DNQH' => 0.0,
                'DNKH' => 0.0,
                'CHOVAY' => 0.0,
                'THUNO' => 0.0,
            ];
        }

        $groups[$code]['ITEMS'][$scheme]['DUNO'] += $duno;
        $groups[$code]['ITEMS'][$scheme]['DNQH'] += $dnqh;
        $groups[$code]['ITEMS'][$scheme]['DNKH'] += $dnkh;
        $groups[$code]['ITEMS'][$scheme]['CHOVAY'] += $chovay;
        $groups[$code]['ITEMS'][$scheme]['THUNO'] += $thuno;
    }

    foreach ($groups as &$group) {
        $group['ITEMS'] = array_values($group['ITEMS']);
        usort($group['ITEMS'], fn($a, $b) => $b['DUNO'] <=> $a['DUNO']);
        $group['SCHEME_COUNT'] = count($group['ITEMS']);
    }
    unset($group);

    return $groups;
}

function tao_du_lieu_bieu_do_chuong_trinh(array $rows, int $limit = 6): array
{
    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'TENCTVAY' => trim((string)($row['TENCTVAY'] ?? 'Không rõ CT vay')),
            'DUNO' => ep_kieu_so($row['DUNO'] ?? 0),
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
function tao_du_lieu_home_page(string $reportDateOracle, string $mapos, string $sourceFilter, string &$queryError): array
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
            'P_NGUONVON' => gia_tri_bind_nguon_von_home_page($sourceFilter),
        ];
        $sourceTotalBinds = [
            'P_NGAYBC' => $reportDateOracle,
            'P_MAPOS' => $mapos,
        ];

        $detailRows = oracle_chay_truy_van($oracle_conn, sql_chi_tiet_home_page(), $binds, $queryError) ?? [];

        $totalsErr = '';
        $totalsRows = oracle_chay_truy_van($oracle_conn, sql_tong_the_home_page(), $binds, $totalsErr);
        if (is_array($totalsRows) && isset($totalsRows[0])) {
            $totalsFromSql = $totalsRows[0];
            $totalRowsFromSql = isset($totalsFromSql['TOTAL_ROWS']) ? (int)$totalsFromSql['TOTAL_ROWS'] : null;
            $totalKhFromSql = isset($totalsFromSql['TOTAL_KH']) ? (int)$totalsFromSql['TOTAL_KH'] : null;
        } elseif ($totalsErr !== '' && $queryError === '') {
            $queryError = $totalsErr;
        }

        $sourceTotalsErr = '';
        $sourceTotalRows = oracle_chay_truy_van($oracle_conn, sql_tong_theo_nguon_von_home_page(), $sourceTotalBinds, $sourceTotalsErr);
        if (is_array($sourceTotalRows) && isset($sourceTotalRows[0])) {
            $sourceTotals['TW'] = ep_kieu_so($sourceTotalRows[0]['DUNO_TW'] ?? 0);
            $sourceTotals['DP'] = ep_kieu_so($sourceTotalRows[0]['DUNO_DP'] ?? 0);
            $sourceTotals['ALL'] = $sourceTotals['TW'] + $sourceTotals['DP'];
        } elseif ($sourceTotalsErr !== '' && $queryError === '') {
            $queryError = $sourceTotalsErr;
        }

        $schemeErr = '';
        $schemeRows = oracle_chay_truy_van($oracle_conn, sql_chuong_trinh_vay_home_page(), $binds, $schemeErr) ?? [];
        if ($schemeErr !== '' && $queryError === '') {
            $queryError = $schemeErr;
        }

        $topSchemeErr = '';
        $topSchemeRows = oracle_chay_truy_van($oracle_conn, sql_top_chuong_trinh_vay_home_page(), $binds, $topSchemeErr) ?? [];
        if ($topSchemeErr !== '' && $queryError === '') {
            $queryError = $topSchemeErr;
        }
    }

    $totals = [
        'DUNO' => $totalsFromSql !== null ? ep_kieu_so($totalsFromSql['DUNO'] ?? 0) : 0.0,
        'DNQH' => $totalsFromSql !== null ? ep_kieu_so($totalsFromSql['DNQH'] ?? 0) : 0.0,
        'DNTH' => $totalsFromSql !== null ? ep_kieu_so($totalsFromSql['DNTH'] ?? 0) : 0.0,
        'DNKH' => $totalsFromSql !== null ? ep_kieu_so($totalsFromSql['DNKH'] ?? 0) : 0.0,
        'CHOVAY' => $totalsFromSql !== null ? ep_kieu_so($totalsFromSql['CHOVAY'] ?? 0) : 0.0,
        'THUNO' => $totalsFromSql !== null ? ep_kieu_so($totalsFromSql['THUNO'] ?? 0) : 0.0,
    ];

    $byPos = [];
    $byXa = [];

    foreach ($detailRows as $row) {
        $duno = ep_kieu_so($row['DUNO'] ?? 0);
        $dnqh = ep_kieu_so($row['DNQH'] ?? 0);
        $dnth = ep_kieu_so($row['DNTH'] ?? 0);
        $dnkh = ep_kieu_so($row['DNKH'] ?? 0);
        $chovay = ep_kieu_so($row['CHOVAY'] ?? 0);
        $thuno = ep_kieu_so($row['THUNO'] ?? 0);

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
                    'CHOVAY' => 0.0,
                    'THUNO' => 0.0,
                    'COUNT' => 0,
                ];
            }
            $byPos[$maposRow]['DUNO'] += $duno;
            $byPos[$maposRow]['DNQH'] += $dnqh;
            $byPos[$maposRow]['DNKH'] += $dnkh;
            $byPos[$maposRow]['CHOVAY'] += $chovay;
            $byPos[$maposRow]['THUNO'] += $thuno;
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
                    'CHOVAY' => 0.0,
                    'THUNO' => 0.0,
                    'COUNT' => 0,
                ];
            }
            $byXa[$maxa]['DUNO'] += $duno;
            $byXa[$maxa]['DNQH'] += $dnqh;
            $byXa[$maxa]['DNKH'] += $dnkh;
            $byXa[$maxa]['CHOVAY'] += $chovay;
            $byXa[$maxa]['THUNO'] += $thuno;
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

    $detailsByPos = lap_chi_tiet_theo_don_vi($schemeRows, 'MAPOS', 'TENPOS');
    $detailsByXa = lap_chi_tiet_theo_don_vi($schemeRows, 'MAXA', 'TENXA');

    return [
        'totals' => $totals,
        'source_totals' => $sourceTotals,
        'top_scheme' => tao_du_lieu_bieu_do_chuong_trinh($topSchemeRows),
        'pos_list' => $posList,
        'xa_list' => $xaList,
        'details_by_pos' => $detailsByPos,
        'details_by_xa' => $detailsByXa,
        'total_kh' => $totalKhFromSql !== null ? $totalKhFromSql : 0,
        'total_rows' => $totalRowsFromSql !== null ? $totalRowsFromSql : count($detailRows),
        'active_source' => $sourceFilter,
        'active_source_label' => nhan_nguon_von_home_page($sourceFilter),
        'error' => $queryError,
    ];
}

$indexUrl = '/dashboard/HOME_PAGE/home_page.php';
$maposDefault = HOME_PAGE_DEFAULT_MAPOS;
$reportDateDefault = ngay_bao_cao_mac_dinh();
$reportDate = chuan_hoa_ngay((string)($_GET['report_date'] ?? $reportDateDefault));
if ($reportDate === '') {
    $reportDate = $reportDateDefault;
}
$mapos = xu_ly_mapos_dau_vao($_GET['mapos'] ?? $maposDefault);
$sourceFilter = xu_ly_nguon_von_dau_vao($_GET['source'] ?? HOME_PAGE_DEFAULT_SOURCE);
$reportDateOracle = date('d/m/Y', strtotime($reportDate));

if (isset($_GET['api']) && $_GET['api'] === 'data') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: application/json; charset=UTF-8');

    $queryError = '';
    $data = tao_du_lieu_home_page($reportDateOracle, $mapos, $sourceFilter, $queryError);

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
                        <span class="panel-count panel-count--source" id="sourceCurrent">Đang xem: <?php echo htmlspecialchars(nhan_nguon_von_home_page($sourceFilter), ENT_QUOTES, 'UTF-8'); ?></span>
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
                <p class="panel-note-inline">Di chuột hoặc chạm vào lát cắt để xem chi tiết.</p>
            </div>
            <div id="schemeChart" class="scheme-chart empty">
                <div class="empty-box">Đang tải dữ liệu...</div>
            </div>
        </article>
    </section>

    <p class="panel-note panel-note--compact">Đơn vị hiển thị cho các chỉ tiêu tiền: triệu đồng, làm tròn 2 số lẻ.</p>

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
                    <p class="panel-kicker">Khối POS</p>
                    <h2>Danh sách theo POS</h2>
                </div>
                <span class="panel-count" id="posCount">0 dòng</span>
            </div>
            <p class="panel-note">Khối bên trái tổng hợp theo POS, hiển thị thêm dư nợ, DNQH, DNKH, DS cho vay và DS thu nợ.</p>
            <div id="posList" class="summary-list">
                <div class="empty-box">Đang tải dữ liệu...</div>
            </div>
        </article>

        <article class="panel list-panel list-panel--xa">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">Khối Xã</p>
                    <h2>Danh sách theo Xã</h2>
                </div>
                <span class="panel-count" id="xaCount">0 dòng</span>
            </div>
            <p class="panel-note">Khối bên phải tổng hợp theo xã, hiển thị thêm dư nợ, DNQH, DNKH, DS cho vay và DS thu nợ.</p>
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
                <span class="detail-stat-label">DS cho vay</span>
                <strong id="detailChovay">0</strong>
            </article>
            <article class="detail-stat">
                <span class="detail-stat-label">DS thu nợ</span>
                <strong id="detailThuno">0</strong>
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
                        <th style="width: 28%;">CHƯƠNG TRÌNH VAY</th>
                        <th style="width: 14%;">DƯ NỢ</th>
                        <th style="width: 14%;">DNQH</th>
                        <th style="width: 14%;">DNKH</th>
                        <th style="width: 15%;">DS CHO VAY</th>
                        <th style="width: 15%;">DS THU NỢ</th>
                    </tr>
                </thead>
                <tbody id="detailBody">
                    <tr><td colspan="6" class="empty-row">Chưa có dữ liệu.</td></tr>
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
    var detailChovay = document.getElementById('detailChovay');
    var detailThuno = document.getElementById('detailThuno');
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
    function dinh_dang_so(value){
        var n = Number(value);
        if (!isFinite(n)) {
            return '--';
        }
        return n.toLocaleString('en-US', { maximumFractionDigits: 0 });
    }

    function quy_doi_trieu(value){
        var n = Number(value);
        if (!isFinite(n)) {
            return NaN;
        }
        return Math.round((n / 1000000) * 100) / 100;
    }

    function dinh_dang_so_trieu(value){
        var n = quy_doi_trieu(value);
        if (!isFinite(n)) {
            return '--';
        }
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function ma_hoa_html(value){
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function chuan_hoa_nguon(value){
        value = String(value == null ? '' : value).toUpperCase().trim();
        return value === 'TW' || value === 'DP' ? value : 'ALL';
    }

    function nhan_nguon_von(value){
        value = chuan_hoa_nguon(value);
        if (value === 'TW') {
            return 'Nguồn Trung ương';
        }
        if (value === 'DP') {
            return 'Nguồn địa phương';
        }
        return 'Toàn nguồn';
    }

    function bat_trang_thai_tai(isLoading){
        if (loadingBox) {
            loadingBox.style.display = isLoading ? 'flex' : 'none';
        }
    }

    function hien_thi_loi(message){
        if (!errorBox) {
            return;
        }
        errorBox.textContent = message || 'Không tải được dữ liệu.';
        errorBox.style.display = '';
    }

    function xoa_loi(){
        if (errorBox) {
            errorBox.style.display = 'none';
        }
    }

    function lay_bo_loc(){
        return {
            report_date: reportDateInput ? reportDateInput.value : '',
            mapos: maposInput ? maposInput.value.trim() : '',
            source: chuan_hoa_nguon(sourceInput ? sourceInput.value : currentSource)
        };
    }

    function tao_url(withApi){
        var filters = lay_bo_loc();
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

    function dong_bo_url(){
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, '', tao_url(false));
        }
    }

    function ve_bo_loc_nguon(sourceTotals){
        var totals = sourceTotals || {};
        currentSource = chuan_hoa_nguon(sourceInput ? sourceInput.value : currentSource);

        if (sourceAllValue) {
            sourceAllValue.textContent = dinh_dang_so_trieu(totals.ALL || 0);
        }
        if (sourceTwValue) {
            sourceTwValue.textContent = dinh_dang_so_trieu(totals.TW || 0);
        }
        if (sourceDpValue) {
            sourceDpValue.textContent = dinh_dang_so_trieu(totals.DP || 0);
        }
        if (sourceCurrent) {
            sourceCurrent.textContent = 'Đang xem: ' + nhan_nguon_von(currentSource);
        }
        if (sourceResetBtn) {
            sourceResetBtn.disabled = currentSource === 'ALL';
        }

        document.querySelectorAll('.source-option').forEach(function(button){
            var buttonSource = chuan_hoa_nguon(button.getAttribute('data-source'));
            var isActive = buttonSource === currentSource;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function diem_cuc_sang_xy(cx, cy, radius, angleInDegrees){
        var angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;
        return {
            x: cx + (radius * Math.cos(angleInRadians)),
            y: cy + (radius * Math.sin(angleInRadians))
        };
    }

    function mo_ta_lat_cat_donut(cx, cy, outerRadius, innerRadius, startAngle, endAngle){
        var safeEndAngle = endAngle;
        if (safeEndAngle - startAngle >= 360) {
            safeEndAngle = startAngle + 359.999;
        }

        var startOuter = diem_cuc_sang_xy(cx, cy, outerRadius, safeEndAngle);
        var endOuter = diem_cuc_sang_xy(cx, cy, outerRadius, startAngle);
        var startInner = diem_cuc_sang_xy(cx, cy, innerRadius, startAngle);
        var endInner = diem_cuc_sang_xy(cx, cy, innerRadius, safeEndAngle);
        var largeArcFlag = safeEndAngle - startAngle > 180 ? 1 : 0;

        return [
            'M', startOuter.x.toFixed(3), startOuter.y.toFixed(3),
            'A', outerRadius, outerRadius, 0, largeArcFlag, 0, endOuter.x.toFixed(3), endOuter.y.toFixed(3),
            'L', startInner.x.toFixed(3), startInner.y.toFixed(3),
            'A', innerRadius, innerRadius, 0, largeArcFlag, 1, endInner.x.toFixed(3), endInner.y.toFixed(3),
            'Z'
        ].join(' ');
    }

    function xoa_trang_thai_chuong_trinh(){
        if (!schemeChart) {
            return;
        }
        schemeChart.querySelectorAll('[data-scheme-index]').forEach(function(node){
            node.classList.remove('is-active');
        });
    }

    function cap_nhat_tam_nhin_chuong_trinh(eyebrow, name, value, color){
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

    function ve_bieu_do_chuong_trinh(items){
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
            var label = ma_hoa_html(item.TENCTVAY || 'Không rõ CT vay');
            var valueText = dinh_dang_so_trieu(value);

            slicesHtml += '<path class="scheme-slice" data-scheme-index="' + index + '" fill="' + color + '" tabindex="0" d="' + mo_ta_lat_cat_donut(cx, cy, outerRadius, innerRadius, startAngle, endAngle) + '"><title>' + label + ': ' + valueText + '</title></path>';
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
            + '        <span class="scheme-spotlight__value">' + dinh_dang_so_trieu(total) + '</span>'
            + '    </div>'
            + '</div>'
            + '<div class="scheme-chart__legend">' + legendHtml + '</div>';
        function activate(index){
            var item = chartItems[index];
            if (!item) {
                return;
            }
            xoa_trang_thai_chuong_trinh();
            schemeChart.querySelectorAll('[data-scheme-index="' + index + '"]').forEach(function(node){
                node.classList.add('is-active');
            });
            cap_nhat_tam_nhin_chuong_trinh('Chương trình vay', item.TENCTVAY || 'Không rõ CT vay', dinh_dang_so_trieu(item.DUNO || 0), schemePalette[index % schemePalette.length]);
        }

        function reset(){
            xoa_trang_thai_chuong_trinh();
            cap_nhat_tam_nhin_chuong_trinh('Tổng dư nợ', 'Danh mục vay', dinh_dang_so_trieu(total), 'rgba(22, 131, 164, 0.16)');
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

    function ve_danh_sach_tom_tat(target, items, kind){
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
            var code = ma_hoa_html(item[codeKey] || '');
            var name = ma_hoa_html(item[nameKey] || '');
            html += '<button type="button" class="summary-item ' + modifier + '" data-kind="' + kind + '" data-key="' + code + '">'
                + '<span class="summary-item__top">'
                + '<span class="summary-item__code">' + code + '</span>'
                + '<span class="summary-item__badge">' + badgeText + '</span>'
                + '</span>'
                + '<span class="summary-item__name" title="' + name + '">' + name + '</span>'
                + '<span class="summary-item__metrics">'
                + '<span class="summary-item__metric"><strong>' + dinh_dang_so_trieu(item.DUNO) + '</strong><small>Dư nợ</small></span>'
                + '<span class="summary-item__metric"><strong>' + dinh_dang_so_trieu(item.DNQH) + '</strong><small>DNQH</small></span>'
                + '<span class="summary-item__metric"><strong>' + dinh_dang_so_trieu(item.DNKH) + '</strong><small>DNKH</small></span>'
                + '<span class="summary-item__metric"><strong>' + dinh_dang_so_trieu(item.CHOVAY) + '</strong><small>DS cho vay</small></span>'
                + '<span class="summary-item__metric"><strong>' + dinh_dang_so_trieu(item.THUNO) + '</strong><small>DS thu nợ</small></span>'
                + '</span>'
                + '</button>';
        });

        target.innerHTML = html;
    }

    function ve_dong_chi_tiet(items){
        if (!detailBody) {
            return;
        }
        if (!items || items.length === 0) {
            detailBody.innerHTML = '<tr><td colspan="6" class="empty-row">Không có dữ liệu chi tiết.</td></tr>';
            return;
        }

        var html = '';
        items.forEach(function(item){
            var scheme = ma_hoa_html(item.TENCTVAY || 'Không rõ CT vay');
            html += '<tr>'
                + '<td title="' + scheme + '">' + scheme + '</td>'
                + '<td class="num">' + dinh_dang_so_trieu(item.DUNO) + '</td>'
                + '<td class="num">' + dinh_dang_so_trieu(item.DNQH) + '</td>'
                + '<td class="num">' + dinh_dang_so_trieu(item.DNKH) + '</td>'
                + '<td class="num">' + dinh_dang_so_trieu(item.CHOVAY) + '</td>'
                + '<td class="num">' + dinh_dang_so_trieu(item.THUNO) + '</td>'
                + '</tr>';
        });
        detailBody.innerHTML = html;
    }

    function dong_chi_tiet(){
        if (!detailModal) {
            return;
        }
        detailModal.classList.remove('is-open');
        detailModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    function mo_chi_tiet(kind, key){
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
        detailDuno.textContent = dinh_dang_so_trieu(totals.DUNO || 0);
        detailDnqh.textContent = dinh_dang_so_trieu(totals.DNQH || 0);
        detailDnkh.textContent = dinh_dang_so_trieu(totals.DNKH || 0);
        detailChovay.textContent = dinh_dang_so_trieu(totals.CHOVAY || 0);
        detailThuno.textContent = dinh_dang_so_trieu(totals.THUNO || 0);
        detailSchemeCount.textContent = dinh_dang_so(detail.SCHEME_COUNT || 0);
        ve_dong_chi_tiet(detail.ITEMS || []);

        detailModal.classList.add('is-open');
        detailModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function gan_su_kien_danh_sach(target){
        if (!target) {
            return;
        }
        target.addEventListener('click', function(event){
            var button = event.target.closest('.summary-item');
            if (!button) {
                return;
            }
            mo_chi_tiet(button.getAttribute('data-kind'), button.getAttribute('data-key'));
        });
    }

    function dat_nguon_von(source){
        currentSource = chuan_hoa_nguon(source);
        if (sourceInput) {
            sourceInput.value = currentSource;
        }
        ve_bo_loc_nguon(currentData ? currentData.source_totals : null);
    }

    gan_su_kien_danh_sach(posList);
    gan_su_kien_danh_sach(xaList);

    if (searchForm) {
        searchForm.addEventListener('submit', function(event){
            event.preventDefault();
            dong_bo_url();
            tai_du_lieu();
        });
    }

    if (sourceToolbar) {
        sourceToolbar.addEventListener('click', function(event){
            var button = event.target.closest('.source-option');
            if (!button) {
                return;
            }
            dat_nguon_von(button.getAttribute('data-source'));
            dong_bo_url();
            tai_du_lieu();
        });
    }

    if (sourceResetBtn) {
        sourceResetBtn.addEventListener('click', function(){
            if (currentSource === 'ALL') {
                return;
            }
            dat_nguon_von('ALL');
            dong_bo_url();
            tai_du_lieu();
        });
    }

    if (detailCloseBtn) {
        detailCloseBtn.addEventListener('click', dong_chi_tiet);
    }
    if (detailBackdrop) {
        detailBackdrop.addEventListener('click', dong_chi_tiet);
    }
    document.addEventListener('keydown', function(event){
        if (event.key === 'Escape') {
            dong_chi_tiet();
        }
    });

    function tai_du_lieu(){
        bat_trang_thai_tai(true);
        xoa_loi();

        fetch(tao_url(true), { cache: 'no-store' })
            .then(function(response){
                return response.ok ? response.json() : null;
            })
            .then(function(payload){
                if (!payload) {
                    hien_thi_loi('Không tải được dữ liệu.');
                    ve_danh_sach_tom_tat(posList, [], 'pos');
                    ve_danh_sach_tom_tat(xaList, [], 'xa');
                    ve_bieu_do_chuong_trinh([]);
                    return;
                }

                if (!payload.ok) {
                    hien_thi_loi(payload.error || 'Lỗi truy vấn.');
                    ve_danh_sach_tom_tat(posList, [], 'pos');
                    ve_danh_sach_tom_tat(xaList, [], 'xa');
                    ve_bieu_do_chuong_trinh([]);
                    return;
                }

                if (payload.report_date && metaDate) {
                    metaDate.textContent = payload.report_date;
                }

                currentData = payload.data || {};
                dat_nguon_von(currentData.active_source || currentSource);
                dong_chi_tiet();

                var totals = currentData.totals || {};
                cards.DUNO.textContent = dinh_dang_so_trieu(totals.DUNO);
                cards.DNQH.textContent = dinh_dang_so_trieu(totals.DNQH);
                cards.DNTH.textContent = dinh_dang_so_trieu(totals.DNTH);
                cards.DNKH.textContent = dinh_dang_so_trieu(totals.DNKH);
                cards.CHOVAY.textContent = dinh_dang_so_trieu(totals.CHOVAY);
                cards.THUNO.textContent = dinh_dang_so_trieu(totals.THUNO);
                if (metaTotal) {
                    metaTotal.textContent = dinh_dang_so(currentData.total_rows || 0);
                }
                if (metaKh) {
                    metaKh.textContent = dinh_dang_so(currentData.total_kh || 0);
                }

                ve_bo_loc_nguon(currentData.source_totals || {});

                var posItems = currentData.pos_list || [];
                var xaItems = currentData.xa_list || [];
                if (posCount) {
                    posCount.textContent = dinh_dang_so(posItems.length) + ' dòng';
                }
                if (xaCount) {
                    xaCount.textContent = dinh_dang_so(xaItems.length) + ' dòng';
                }

                ve_danh_sach_tom_tat(posList, posItems, 'pos');
                ve_danh_sach_tom_tat(xaList, xaItems, 'xa');
                ve_bieu_do_chuong_trinh(currentData.top_scheme || []);
            })
            .catch(function(){
                hien_thi_loi('Lỗi kết nối khi tải dữ liệu.');
                ve_danh_sach_tom_tat(posList, [], 'pos');
                ve_danh_sach_tom_tat(xaList, [], 'xa');
                ve_bieu_do_chuong_trinh([]);
            })
            .finally(function(){
                bat_trang_thai_tai(false);
            });
    }

    ve_bo_loc_nguon(null);
    tai_du_lieu();
})();
</script>
</body>
</html>
<?php
if (isset($oracle_conn) && (is_resource($oracle_conn) || is_object($oracle_conn))) {
    @oci_close($oracle_conn);
}
?>