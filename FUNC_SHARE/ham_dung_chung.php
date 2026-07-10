<?php
header_remove('X-Powered-By');

const DASHBOARD_IP_DUOC_PHEP_MAC_DINH = ['10.64.0.108'];

function dashboard_duong_dan_goc(string $duongDan = ''): string
{
    $duongDan = ltrim($duongDan, '/');
    return $duongDan === '' ? '/dashboard' : '/dashboard/' . $duongDan;
}

function dashboard_lay_danh_sach_ip_duoc_phep(): array
{
    $giaTriMoiTruong = trim((string)getenv('DASHBOARD_ALLOWED_IPS'));
    $danhSach = $giaTriMoiTruong !== ''
        ? preg_split('/[\s,;]+/', $giaTriMoiTruong) ?: []
        : DASHBOARD_IP_DUOC_PHEP_MAC_DINH;

    $ketQua = [];
    foreach ($danhSach as $ip) {
        $ip = trim((string)$ip);
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            continue;
        }
        $ketQua[$ip] = true;
    }

    return array_keys($ketQua);
}

function dashboard_lay_ip_nguoi_dung(): string
{
    $cacNguon = [
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['HTTP_CLIENT_IP'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ($cacNguon as $nguon) {
        $danhSach = array_map('trim', explode(',', (string)$nguon));
        foreach ($danhSach as $ip) {
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                return $ip;
            }
        }
    }

    return '';
}

function dashboard_chan_ip_khong_hop_le(?array $danhSachIp = null): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $danhSachIp = $danhSachIp ?? dashboard_lay_danh_sach_ip_duoc_phep();
    $ipHienTai = dashboard_lay_ip_nguoi_dung();

    if ($ipHienTai !== '' && in_array($ipHienTai, $danhSachIp, true)) {
        return;
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Truy cap bi tu choi. He thong chi cho phep IP noi bo da duoc dang ky.';
    exit;
}

function dashboard_khoi_tao_phien(string $tenPhien = 'DASHBOARD_VB_IOT_SESSID', int $thoiHan = 2592000): void
{
    if (session_status() === PHP_SESSION_ACTIVE && session_name() === $tenPhien) {
        return;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    ini_set('session.gc_maxlifetime', (string)$thoiHan);
    ini_set('session.cookie_lifetime', (string)$thoiHan);
    session_name($tenPhien);
    $thamSoCookie = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $thoiHan,
        'path' => '/dashboard',
        'domain' => $thamSoCookie['domain'] ?? '',
        'secure' => (bool)($thamSoCookie['secure'] ?? false),
        'httponly' => (bool)($thamSoCookie['httponly'] ?? true),
        'samesite' => $thamSoCookie['samesite'] ?? 'Lax',
    ]);

    session_start();
}

function dashboard_chuan_hoa_ngay(string $giaTri): string
{
    $giaTri = trim($giaTri);
    if ($giaTri === '') {
        return '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $giaTri) === 1) {
        return $giaTri;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $giaTri, $khop) === 1) {
        return $khop[3] . '-' . $khop[2] . '-' . $khop[1];
    }

    return '';
}

function dashboard_chuan_hoa_text(string $giaTri): string
{
    return trim((string)(preg_replace('/\s+/', ' ', $giaTri) ?? $giaTri));
}

function dashboard_html(string $giaTri): string
{
    return htmlspecialchars($giaTri, ENT_QUOTES, 'UTF-8');
}

function dashboard_duong_dan_gif_loading(): string
{
    return dashboard_duong_dan_goc('LOADDING.gif');
}

function dashboard_render_loading_chung(array $options = []): void
{
    static $daRender = false;
    if ($daRender) {
        return;
    }
    $daRender = true;

    $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($options['id'] ?? 'dashboardLoadingOverlay')) ?: 'dashboardLoadingOverlay';
    $message = (string)($options['message'] ?? html_entity_decode('&#272;ang l&#7845;y s&#7889; li&#7879;u', ENT_QUOTES, 'UTF-8'));
    $detail = (string)($options['detail'] ?? html_entity_decode('Vui l&#242;ng ch&#7901; trong gi&#226;y l&#225;t.', ENT_QUOTES, 'UTF-8'));
    $imageUrl = (string)($options['image_url'] ?? dashboard_duong_dan_gif_loading());
    $autoFetch = array_key_exists('auto_fetch', $options) ? (bool)$options['auto_fetch'] : true;
    ?>
<style id="dashboard-loading-common-style">
.dashboard-loading-overlay {
    position: fixed;
    inset: 0;
    z-index: 2147483000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(13, 20, 31, 0.48);
    backdrop-filter: blur(2px);
}
.dashboard-loading-overlay[hidden] {
    display: none !important;
}
.dashboard-loading-dialog {
    width: auto;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    color: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
}
.dashboard-loading-image {
    width: 140px;
    height: 140px;
    object-fit: contain;
    display: block;
}
.dashboard-loading-title,
.dashboard-loading-detail {
    display: none !important;
}
@media (max-width: 520px) {
    .dashboard-loading-image {
        width: 118px;
        height: 118px;
    }
}
</style>
<div
    class="dashboard-loading-overlay"
    id="<?php echo dashboard_html($id); ?>"
    data-auto-fetch="<?php echo $autoFetch ? '1' : '0'; ?>"
    role="alert"
    aria-live="assertive"
    aria-busy="true"
    hidden
>
    <div class="dashboard-loading-dialog">
        <img class="dashboard-loading-image" src="<?php echo dashboard_html($imageUrl); ?>" alt="">
        <div class="dashboard-loading-title" data-dashboard-loading-title><?php echo dashboard_html($message); ?></div>
        <div class="dashboard-loading-detail" data-dashboard-loading-detail><?php echo dashboard_html($detail); ?></div>
    </div>
</div>
<script id="dashboard-loading-common-script">
(function () {
    var overlay = document.getElementById(<?php echo json_encode($id, JSON_UNESCAPED_UNICODE); ?>);
    if (!overlay || (window.DashboardLoading && window.DashboardLoading.ready)) {
        return;
    }

    var titleNode = overlay.querySelector('[data-dashboard-loading-title]');
    var detailNode = overlay.querySelector('[data-dashboard-loading-detail]');
    var defaultTitle = titleNode ? titleNode.textContent : 'Dang lay so lieu';
    var defaultDetail = detailNode ? detailNode.textContent : '';
    var activeCount = 0;
    var nativeFetch = window.fetch ? window.fetch.bind(window) : null;

    function normalizeOptions(options) {
        if (typeof options === 'string') {
            return { title: options };
        }
        return options && typeof options === 'object' ? options : {};
    }

    function setText(options) {
        var opts = normalizeOptions(options);
        var title = opts.title || opts.message || defaultTitle;
        var detail = opts.detail || defaultDetail;
        if (titleNode) {
            titleNode.textContent = title;
        }
        if (detailNode) {
            detailNode.textContent = detail;
            detailNode.style.display = detail === '' ? 'none' : '';
        }
    }

    function show(options) {
        activeCount += 1;
        setText(options);
        overlay.hidden = false;
        overlay.classList.add('is-visible');
        return activeCount;
    }

    function hide(force) {
        activeCount = force === true ? 0 : Math.max(0, activeCount - 1);
        if (activeCount > 0) {
            return activeCount;
        }
        overlay.classList.remove('is-visible');
        overlay.hidden = true;
        setText({});
        return activeCount;
    }

    function cloneFetchInit(init) {
        if (!init || typeof init !== 'object') {
            return init;
        }
        var copy = {};
        Object.keys(init).forEach(function (key) {
            if (key !== 'dashboardLoading') {
                copy[key] = init[key];
            }
        });
        return copy;
    }

    function wrapResponseBody(response, release) {
        if (!response || typeof response !== 'object') {
            release();
            return response;
        }

        var releaseTimer = window.setTimeout(release, 0);
        ['arrayBuffer', 'blob', 'formData', 'json', 'text'].forEach(function (method) {
            if (typeof response[method] !== 'function') {
                return;
            }
            var original = response[method].bind(response);
            try {
                response[method] = function () {
                    if (releaseTimer !== null) {
                        window.clearTimeout(releaseTimer);
                        releaseTimer = null;
                    }
                    var result;
                    try {
                        result = original.apply(response, arguments);
                    } catch (error) {
                        release();
                        throw error;
                    }
                    if (result && typeof result.finally === 'function') {
                        return result.finally(release);
                    }
                    release();
                    return result;
                };
            } catch (error) {
                release();
            }
        });

        return response;
    }

    function track(promise, options) {
        show(options);
        return Promise.resolve(promise).then(function (value) {
            hide();
            return value;
        }, function (error) {
            hide();
            throw error;
        });
    }

    function fetchWithLoading(input, init, options) {
        if (!nativeFetch) {
            return Promise.reject(new Error('Fetch API is not available.'));
        }

        var loadingOptions = options;
        var fetchInit = init;
        if (fetchInit && typeof fetchInit === 'object' && Object.prototype.hasOwnProperty.call(fetchInit, 'dashboardLoading')) {
            loadingOptions = fetchInit.dashboardLoading;
            fetchInit = cloneFetchInit(fetchInit);
        }
        if (loadingOptions === false) {
            return nativeFetch(input, fetchInit);
        }

        var released = false;
        function release() {
            if (released) {
                return;
            }
            released = true;
            hide();
        }

        show(loadingOptions);
        return nativeFetch(input, fetchInit).then(function (response) {
            return wrapResponseBody(response, release);
        }, function (error) {
            release();
            throw error;
        });
    }

    window.DashboardLoading = {
        ready: true,
        show: show,
        hide: hide,
        forceHide: function () { return hide(true); },
        setText: setText,
        track: track,
        fetch: fetchWithLoading
    };

    if (nativeFetch && overlay.getAttribute('data-auto-fetch') === '1' && !window.__dashboardLoadingFetchWrapped) {
        window.__dashboardLoadingFetchWrapped = true;
        window.fetch = function (input, init) {
            return fetchWithLoading(input, init);
        };
    }

    window.addEventListener('pageshow', function () {
        hide(true);
    });
})();
</script>
    <?php
}
function dashboard_phan_hoi_json(array $duLieu, int $maTrangThai = 200): void
{
    http_response_code($maTrangThai);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($duLieu, JSON_UNESCAPED_UNICODE);
    exit;
}

function dashboard_db_gan_tham_so_stmt(mysqli_stmt $cauLenh, array $giaTri): bool
{
    if (empty($giaTri)) {
        return true;
    }

    $loai = '';
    $bienBind = [];
    foreach ($giaTri as $index => $giaTriBind) {
        if (is_int($giaTriBind)) {
            $loai .= 'i';
        } elseif (is_float($giaTriBind)) {
            $loai .= 'd';
        } else {
            $loai .= 's';
        }
        $bienBind[$index] = $giaTriBind;
    }

    $thamSo = [$loai];
    foreach ($bienBind as &$giaTriRef) {
        $thamSo[] = &$giaTriRef;
    }
    unset($giaTriRef);

    return call_user_func_array([$cauLenh, 'bind_param'], $thamSo) !== false;
}

function dashboard_db_lay_danh_sach_assoc(mysqli $ketNoi, string $sql, array $giaTri = [], ?string &$loi = null): ?array
{
    $cauLenh = db_mysqli_prepare($ketNoi, $sql);
    if ($cauLenh === false) {
        $loi = $ketNoi->error !== '' ? $ketNoi->error : 'Khong prepare duoc SQL.';
        return null;
    }

    if (!dashboard_db_gan_tham_so_stmt($cauLenh, $giaTri)) {
        $loi = $cauLenh->error !== '' ? $cauLenh->error : 'Khong bind duoc tham so SQL.';
        $cauLenh->close();
        return null;
    }

    if (!$cauLenh->execute()) {
        $loi = $cauLenh->error !== '' ? $cauLenh->error : 'Khong execute duoc SQL.';
        $cauLenh->close();
        return null;
    }

    $ketQua = [];
    $tapKetQua = $cauLenh->get_result();
    while ($tapKetQua && ($dong = $tapKetQua->fetch_assoc())) {
        $ketQua[] = $dong;
    }
    if ($tapKetQua instanceof mysqli_result) {
        $tapKetQua->free();
    }
    $cauLenh->close();

    return $ketQua;
}

function dashboard_db_lay_mot_assoc(mysqli $ketNoi, string $sql, array $giaTri = [], ?string &$loi = null): ?array
{
    $danhSach = dashboard_db_lay_danh_sach_assoc($ketNoi, $sql, $giaTri, $loi);
    if ($danhSach === null) {
        return null;
    }

    return $danhSach[0] ?? null;
}

function dashboard_oracle_chay_truy_van($ketNoi, string $sql, array $giaTri = [], ?string &$loi = null): ?array
{
    $cauLenh = db_oci_parse($ketNoi, $sql);
    if ($cauLenh === false) {
        $loiOracle = oci_error($ketNoi);
        $loi = $loiOracle['message'] ?? 'Loi phan tich cau lenh Oracle hoac cau SQL ghi bi chan.';
        return null;
    }

    $bienBind = [];
    foreach ($giaTri as $ten => $giaTriBind) {
        $bienBind[$ten] = $giaTriBind;
        if (@oci_bind_by_name($cauLenh, ':' . $ten, $bienBind[$ten]) === false) {
            $loiOracle = oci_error($cauLenh);
            $loi = $loiOracle['message'] ?? 'Loi bind tham so Oracle.';
            oci_free_statement($cauLenh);
            return null;
        }
    }

    if (@oci_execute($cauLenh, OCI_NO_AUTO_COMMIT) === false) {
        $loiOracle = oci_error($cauLenh);
        $loi = $loiOracle['message'] ?? 'Loi thuc thi cau lenh Oracle.';
        oci_free_statement($cauLenh);
        return null;
    }

    $ketQua = [];
    while (($dong = oci_fetch_assoc($cauLenh)) !== false) {
        $ketQua[] = $dong;
    }
    oci_free_statement($cauLenh);

    return $ketQua;
}

function dashboard_cau_hinh_menu(): array
{
    return [
        [
            'id' => 'bao-cao',
            'nhan' => 'Báo cáo',
            'muc_con' => [
                [
                    'id' => 'dgx',
                    'nhan' => 'Báo cáo điểm GDX',
                    'duong_dan' => 'DGX/dgx.php',
                    'nhan_ngan' => 'DG',
                    'hien_huy_hieu' => false,
                ],
            ],
        ],

        [
            'id' => 'van-ban',
            'nhan' => 'Văn Bản',
            'muc_con' => [
                [
                    'id' => 'van-ban-noi-bo',
                    'nhan' => 'Xem văn bản',
                    'duong_dan' => 'VB_IOT/vb_iot.php',
                    'nhan_ngan' => 'VB',
                    'hien_huy_hieu' => true,
                ],
            ],
        ],
        [
            'id' => 'he-thong',
            'nhan' => 'SYSTEM',
            'muc_con' => [
                [
                    'id' => 'quan-ly-user',
                    'nhan' => 'Quản lý User',
                    'duong_dan' => 'USER-SYSTEM/user_system.php',
                    'nhan_ngan' => 'US',
                    'hien_huy_hieu' => false,
                ],
            ],
        ],
    ];
}

function dashboard_tim_muc_menu(array $menu, string $idMuc): ?array
{
    foreach ($menu as $nhom) {
        foreach ($nhom['muc_con'] ?? [] as $mucCon) {
            if (($mucCon['id'] ?? '') === $idMuc) {
                return [
                    'nhom' => $nhom,
                    'muc' => $mucCon,
                ];
            }
        }
    }

    return null;
}

function dashboard_thong_tin_chan_trang(): array
{
    $nguoiTao = trim((string)getenv('DASHBOARD_CREATOR_NAME'));
    if ($nguoiTao === '') {
        $nguoiTao = '@Huynvvbsp';
    }
    
    return [
        'nguoi_tao' => $nguoiTao,
    ];
}