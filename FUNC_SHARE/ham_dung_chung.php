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
            'id' => 'tin-dung',
            'nhan' => 'Tín Dụng',
            'mo_ta' => 'Tong hop du no va thong ke nguon von.',
            'muc_con' => [
                [
                    'id' => 'tong-hop-du-no',
                    'nhan' => 'Tổng hợp dư nợ',
                    'duong_dan' => 'HOME_PAGE/home_page.php',
                    'nhan_ngan' => 'TD',
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