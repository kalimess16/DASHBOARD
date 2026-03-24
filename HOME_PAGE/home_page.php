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
// Allow data fetch a bit longer but avoid infinite wait.
ini_set('default_socket_timeout', '6');
@set_time_limit(60);

require_once __DIR__ . '/../DB/connect_DB.php';
require_once __DIR__ . '/home_page_sql.php';

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

function report_number_text($value): string
{
    if ($value === null || $value === '') {
        return '0';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, 0, '.', ',');
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

function build_home_page_data(string $report_date_oracle, ?string $mapos, &$query_error): array
{
    global $oracle_conn;

    $rows = [];
    $totals_from_sql = null;
    $total_rows_from_sql = null;
    $total_kh_from_sql = null;
    $by_source_from_sql = null;
    if (!isset($oracle_conn) || $oracle_conn === null) {
        $query_error = 'Chua ket noi duoc Oracle. Kiem tra OCI8 va thong tin ket noi trong DB/connect_DB.php.';
    } else {
        $binds = [
            'P_NGAYBC' => $report_date_oracle,
            'P_MAPOS' => ($mapos !== null && $mapos !== '') ? $mapos : null,
        ];
        $sql = home_page_base_sql();
        $rows = oci_run_query($oracle_conn, $sql, $binds, $query_error) ?? [];

        $totals_sql = home_page_totals_sql();
        $totals_err = '';
        $totals_rows = oci_run_query($oracle_conn, $totals_sql, $binds, $totals_err);
        if (is_array($totals_rows) && isset($totals_rows[0])) {
            $totals_from_sql = $totals_rows[0];
            $total_rows_from_sql = isset($totals_from_sql['TOTAL_ROWS']) ? (int)$totals_from_sql['TOTAL_ROWS'] : null;
            $total_kh_from_sql = isset($totals_from_sql['TOTAL_KH']) ? (int)$totals_from_sql['TOTAL_KH'] : null;
            $by_source_from_sql = [
                'TW' => as_number($totals_from_sql['DUNO_TW'] ?? 0),
                'DP' => as_number($totals_from_sql['DUNO_DP'] ?? 0),
            ];
        } elseif ($totals_err !== '' && $query_error === '') {
            $query_error = $totals_err;
        }
    }

    $totals = [
        'DUNO' => $totals_from_sql !== null ? as_number($totals_from_sql['DUNO'] ?? 0) : 0.0,
        'DNQH' => $totals_from_sql !== null ? as_number($totals_from_sql['DNQH'] ?? 0) : 0.0,
        'DNTH' => $totals_from_sql !== null ? as_number($totals_from_sql['DNTH'] ?? 0) : 0.0,
        'DNKH' => $totals_from_sql !== null ? as_number($totals_from_sql['DNKH'] ?? 0) : 0.0,
        'CHOVAY' => $totals_from_sql !== null ? as_number($totals_from_sql['CHOVAY'] ?? 0) : 0.0,
        'THUNO' => $totals_from_sql !== null ? as_number($totals_from_sql['THUNO'] ?? 0) : 0.0,
    ];

    $by_pos = [];
    $by_xa = [];
    $by_scheme = [];
    $by_source = [ 'TW' => 0.0, 'DP' => 0.0 ];
    $unique_kh = [];

    foreach ($rows as $row) {
        $duno   = as_number($row['DUNO'] ?? 0);
        $dnqh   = as_number($row['DNQH'] ?? 0);
        $dnth   = as_number($row['DNTH'] ?? 0);
        $dnkh   = as_number($row['DNKH'] ?? 0);
        $chovay = as_number($row['CHOVAY'] ?? 0);
        $thuno  = as_number($row['THUNO'] ?? 0);

        if ($totals_from_sql === null) {
            $totals['DUNO']  += $duno;
            $totals['DNQH']  += $dnqh;
            $totals['DNTH']  += $dnth;
            $totals['DNKH']  += $dnkh;
            $totals['CHOVAY']+= $chovay;
            $totals['THUNO'] += $thuno;
        }

        $maposRow = trim((string)($row['MAPOS'] ?? ''));
        $tenpos = trim((string)($row['TENPOS'] ?? ''));
        if ($maposRow !== '') {
            if (!isset($by_pos[$maposRow])) {
                $by_pos[$maposRow] = ['MAPOS'=>$maposRow,'TENPOS'=>$tenpos,'DUNO'=>0.0,'DNQH'=>0.0,'COUNT'=>0];
            }
            $by_pos[$maposRow]['DUNO'] += $duno;
            $by_pos[$maposRow]['DNQH'] += $dnqh;
            $by_pos[$maposRow]['COUNT'] += 1;
            if ($by_pos[$maposRow]['TENPOS'] === '' && $tenpos !== '') {
                $by_pos[$maposRow]['TENPOS'] = $tenpos;
            }
        }

        $maxa = trim((string)($row['MAXA'] ?? ''));
        $tenxa = trim((string)($row['TENXA'] ?? ''));
        if ($maxa !== '') {
            if (!isset($by_xa[$maxa])) {
                $by_xa[$maxa] = ['MAXA'=>$maxa,'TENXA'=>$tenxa,'DUNO'=>0.0,'COUNT'=>0];
            }
            $by_xa[$maxa]['DUNO'] += $duno;
            $by_xa[$maxa]['COUNT'] += 1;
            if ($by_xa[$maxa]['TENXA'] === '' && $tenxa !== '') {
                $by_xa[$maxa]['TENXA'] = $tenxa;
            }
        }

        $scheme = trim((string)($row['TENCTVAY'] ?? ''));
        if ($scheme === '') { $scheme = 'Khong ro CT vay'; }
        if (!isset($by_scheme[$scheme])) { $by_scheme[$scheme] = 0.0; }
        $by_scheme[$scheme] += $duno;

        if (array_key_exists('NGUON_VON', $row)) {
            $source = trim((string)($row['NGUON_VON'] ?? ''));
            if ($source === '') { $source = 'DP'; }
            if (!isset($by_source[$source])) { $by_source[$source] = 0.0; }
            $by_source[$source] += $duno;
        }

        $makh = trim((string)($row['KU_MAKH'] ?? ''));
        if ($makh !== '') { $unique_kh[$makh] = true; }
    }

    if ($by_source_from_sql !== null) {
        $by_source = $by_source_from_sql;
    }

    $pos_list = array_values($by_pos);
    $xa_list  = array_values($by_xa);
    $scheme_list = [];
    foreach ($by_scheme as $name => $value) {
        $scheme_list[] = ['TENCTVAY'=>$name,'DUNO'=>$value];
    }
    usort($pos_list, fn($a,$b)=>$b['DUNO']<=>$a['DUNO']);
    usort($xa_list, fn($a,$b)=>$b['DUNO']<=>$a['DUNO']);
    usort($scheme_list, fn($a,$b)=>$b['DUNO']<=>$a['DUNO']);

    $top_pos    = array_slice($pos_list, 0, 8);
    $top_xa     = array_slice($xa_list, 0, 8);
    $top_scheme = array_slice($scheme_list, 0, 6);

    $detail_rows = $rows;
    usort($detail_rows, fn($a,$b)=>as_number($b['DUNO'] ?? 0) <=> as_number($a['DUNO'] ?? 0));
    $detail_rows = array_slice($detail_rows, 0, 200);

    $total_kh = $total_kh_from_sql !== null ? $total_kh_from_sql : count($unique_kh);
    $total_rows = $total_rows_from_sql !== null ? $total_rows_from_sql : count($rows);

    return [
        'totals'      => $totals,
        'top_pos'     => $top_pos,
        'top_xa'      => $top_xa,
        'top_scheme'  => $top_scheme,
        'by_source'   => $by_source,
        'detail_rows' => $detail_rows,
        'total_kh'    => $total_kh,
        'total_rows'  => $total_rows,
        'error'       => $query_error,
    ];
}

$index_url = '/dashboard/HOME_PAGE/home_page.php';
$report_date = normalize_date_input((string)($_GET['report_date'] ?? date('Y-m-d')));
if ($report_date === '') {
    $report_date = date('Y-m-d');
}
$report_date_oracle = date('d/m/Y', strtotime($report_date));

if (isset($_GET['api']) && $_GET['api'] === 'data') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: application/json; charset=UTF-8');

    $mapos = isset($_GET['mapos']) ? trim((string)$_GET['mapos']) : '';
    if ($mapos === '') { $mapos = $mapos_default; }
    $query_error = '';
    $data = build_home_page_data($report_date_oracle, $mapos, $query_error);

    echo json_encode([
        'ok' => $query_error === '',
        'error' => $query_error,
        'report_date' => $report_date_oracle,
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
<title>Tong hop du no</title>
<link rel="stylesheet" href="../view/Style_home_page.php">
</head>
<body>
<div class="container">
    <h1 class="page-title"><a href="<?php echo $index_url; ?>">Tong hop du no theo POS / XA</a></h1>
    <div class="meta">Ngay bao cao: <span id="metaDate"><?php echo htmlspecialchars($report_date_oracle, ENT_QUOTES, 'UTF-8'); ?></span> | Tong ban ghi: <span id="metaTotal">0</span> | Tong KH: <span id="metaKh">0</span></div>

    <form method="get" action="<?php echo $index_url; ?>" class="search-form" id="searchForm">
        <label>Ngay BC:</label>
        <input type="date" name="report_date" id="reportDate" value="<?php echo htmlspecialchars($report_date, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="text" name="mapos" id="mapos" placeholder="MAPOS" style="width:120px;" value="<?php echo htmlspecialchars((string)($_GET['mapos'] ?? $mapos_default), ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit">Tai bao cao</button>
    </form>

    <div id="errorBox" class="error-box" style="display:none;"></div>
    <div class="loading" id="loadingBox"><div class="spinner"></div><div class="loading-text">Dang tai du lieu...</div></div>

    <div class="cards" id="cardsBox">
        <div class="card"><div class="label">Du no</div><div class="value" id="cardDuno">--</div></div>
        <div class="card"><div class="label">Du no qua han</div><div class="value" id="cardDnqh">--</div></div>
        <div class="card"><div class="label">Du no trong han</div><div class="value" id="cardDnth">--</div></div>
        <div class="card"><div class="label">Du no khoanh</div><div class="value" id="cardDnkh">--</div></div>
        <div class="card"><div class="label">Cho vay</div><div class="value" id="cardChovay">--</div></div>
        <div class="card"><div class="label">Thu no</div><div class="value" id="cardThuno">--</div></div>
    </div>

    <div class="grid">
        <div class="panel"><h3>Top POS theo du no</h3><div id="listPos" class="bar-list empty">--</div></div>
        <div class="panel"><h3>Top XA theo du no</h3><div id="listXa" class="bar-list empty">--</div></div>
        <div class="panel"><h3>Chuong trinh vay noi bat</h3><div id="listScheme" class="bar-list empty">--</div></div>
    </div>

    <div class="panel" style="margin: 0 22px 16px;">
        <h3>Nguon von</h3>
        <div class="source-box">
            <span class="source-pill" id="pillTw">TW: --</span>
            <span class="source-pill" id="pillDp">DP: --</span>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">MAPOS</th>
                    <th style="width: 18%;">TENPOS</th>
                    <th style="width: 8%;">MAXA</th>
                    <th style="width: 18%;">TENXA</th>
                    <th style="width: 16%;">TENCTVAY</th>
                    <th style="width: 10%;">DU NO</th>
                    <th style="width: 8%;">DNQH</th>
                    <th style="width: 8%;">DNTH</th>
                    <th style="width: 8%;">DNKH</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr><td colspan="9" class="empty">Dang tai du lieu...</td></tr>
            </tbody>
        </table>
    </div>
</div>
<script>
(function(){
    var loadingBox = document.getElementById('loadingBox');
    var errorBox = document.getElementById('errorBox');
    var metaDate = document.getElementById('metaDate');
    var metaTotal = document.getElementById('metaTotal');
    var metaKh = document.getElementById('metaKh');
    var reportDateInput = document.getElementById('reportDate');
    var maposInput = document.getElementById('mapos');
    var cards = {
        DUNO: document.getElementById('cardDuno'),
        DNQH: document.getElementById('cardDnqh'),
        DNTH: document.getElementById('cardDnth'),
        DNKH: document.getElementById('cardDnkh'),
        CHOVAY: document.getElementById('cardChovay'),
        THUNO: document.getElementById('cardThuno')
    };
    var barPos = document.getElementById('listPos');
    var barXa = document.getElementById('listXa');
    var barScheme = document.getElementById('listScheme');
    var pillTw = document.getElementById('pillTw');
    var pillDp = document.getElementById('pillDp');
    var tableBody = document.getElementById('tableBody');

    function fmt(value){
        var n = Number(value);
        if (!isFinite(n)) return '--';
        return n.toLocaleString('en-US', {maximumFractionDigits:0});
    }

    function renderBars(el, items, valueKey, labelKey, subKey){
        if (!el) return;
        if (!items || items.length === 0){
            el.innerHTML = '<div class="empty">Khong co du lieu.</div>';
            return;
        }
        var max = 0;
        items.forEach(function(it){ var v = Number(it[valueKey]||0); if (v>max) max=v; });
        if (max<=0) max=1;
        var html='';
        items.forEach(function(it){
            var v = Number(it[valueKey]||0);
            var w = Math.max(2, Math.min(100, v/max*100));
            var label = String(it[labelKey]||'');
            var sub = subKey ? String(it[subKey]||'') : '';
            var text = label + (sub?(' - '+sub):'');
            html += '<div class="bar-item">'
                + '<div class="bar-label" title="'+text.replace(/"/g,'&quot;')+'">'+text+'</div>'
                + '<div class="bar-track"><span class="bar-fill" style="width:'+w.toFixed(2)+'%"></span></div>'
                + '<div class="bar-value">'+fmt(v)+'</div>'
                + '</div>';
        });
        el.innerHTML = html;
    }

    function renderTable(rows){
        if (!tableBody) return;
        if (!rows || rows.length === 0){
            tableBody.innerHTML = '<tr><td colspan="9" class="empty">Khong co du lieu.</td></tr>';
            return;
        }
        var html = '';
        rows.forEach(function(r){
            html += '<tr>'
                + '<td>'+ (r.MAPOS||'') +'</td>'
                + '<td title="'+ (r.TENPOS||'') +'">'+ (r.TENPOS||'') +'</td>'
                + '<td>'+ (r.MAXA||'') +'</td>'
                + '<td title="'+ (r.TENXA||'') +'">'+ (r.TENXA||'') +'</td>'
                + '<td title="'+ (r.TENCTVAY||'') +'">'+ (r.TENCTVAY||'') +'</td>'
                + '<td class="num">'+ fmt(r.DUNO) +'</td>'
                + '<td class="num">'+ fmt(r.DNQH) +'</td>'
                + '<td class="num">'+ fmt(r.DNTH) +'</td>'
                + '<td class="num">'+ fmt(r.DNKH) +'</td>'
                + '</tr>';
        });
        tableBody.innerHTML = html;
    }

    function setEmptyTable(msg){
        if (!tableBody) return;
        tableBody.innerHTML = '<tr><td colspan="9" class="empty">' + (msg || 'Khong co du lieu.') + '</td></tr>';
    }

    function showError(msg){
        if (!errorBox) return;
        errorBox.textContent = msg || 'Khong tai duoc du lieu.';
        errorBox.style.display = '';
    }

    function clearError(){ if (errorBox) errorBox.style.display='none'; }

    function setLoading(on){ if(loadingBox) loadingBox.style.display = on? 'flex':'none'; }

    function fetchData(){
        setLoading(true);
        clearError();
        var dateVal = reportDateInput ? reportDateInput.value : '';
        var maposVal = maposInput ? maposInput.value.trim() : '';
        var url = 'home_page.php?api=data';
        if (dateVal) url += '&report_date='+encodeURIComponent(dateVal);
        if (maposVal) url += '&mapos='+encodeURIComponent(maposVal);
        fetch(url, {cache:'no-store'})
            .then(function(res){ return res.ok ? res.json() : null; })
            .then(function(payload){
                if (!payload){
                    showError('Khong tai duoc du lieu.');
                    setEmptyTable('Khong co du lieu (fetch null).');
                    return;
                }
                if (!payload.ok){
                    showError(payload.error || 'Loi truy van.');
                    setEmptyTable('Khong co du lieu (' + (payload.error || 'loi') + ')');
                    return;
                }
                if (payload.report_date){ metaDate.textContent = payload.report_date; }
                var d = payload.data || {};
                var t = d.totals || {};
                cards.DUNO.textContent  = fmt(t.DUNO);
                cards.DNQH.textContent  = fmt(t.DNQH);
                cards.DNTH.textContent  = fmt(t.DNTH);
                cards.DNKH.textContent  = fmt(t.DNKH);
                cards.CHOVAY.textContent= fmt(t.CHOVAY);
                cards.THUNO.textContent = fmt(t.THUNO);
                metaTotal.textContent = fmt(d.total_rows || 0);
                metaKh.textContent    = fmt(d.total_kh || 0);
                var bs = d.by_source || {};
                pillTw.textContent = 'TW: ' + fmt(bs.TW || 0);
                pillDp.textContent = 'DP: ' + fmt(bs.DP || 0);
                renderBars(barPos, d.top_pos || [], 'DUNO','MAPOS','TENPOS');
                renderBars(barXa, d.top_xa || [], 'DUNO','MAXA','TENXA');
                renderBars(barScheme, d.top_scheme || [], 'DUNO','TENCTVAY');
                renderTable(d.detail_rows || []);
            })
            .catch(function(){
                showError('Loi ket noi khi tai du lieu.');
                setEmptyTable('Khong co du lieu (ket noi).');
            })
            .finally(function(){ setLoading(false); });
    }

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





