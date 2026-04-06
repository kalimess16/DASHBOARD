<?php
require_once __DIR__ . '/../FUNC_SHARE/ham_dung_chung.php';
dashboard_chan_ip_khong_hop_le();
dashboard_khoi_tao_phien('DASHBOARD_VB_IOT_SESSID');
require_once __DIR__ . '/../DB/connect_DB.php';

function normalize_mime(?string $mime, ?string $file_name): string
{
    $mime = trim((string)$mime);
    if ($mime !== '' && strpos($mime, '/') !== false) {
        return $mime;
    }

    $ext = strtolower(pathinfo((string)$file_name, PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        return 'application/pdf';
    }

    return 'application/octet-stream';
}

function build_display_title(?string $title, ?string $file_name): string
{
    $display = trim((string)($title ?? ''));
    if ($display === '') {
        $display = trim((string)($file_name ?? ''));
    }
    if ($display === '') {
        $display = 'Van ban';
    }

    $display = preg_replace('/[\\\\\\/:"*?<>|]+/', ' ', $display);
    $display = preg_replace('/\s+/', ' ', (string)$display);
    $display = trim((string)$display);

    return $display !== '' ? $display : 'Van ban';
}

$doc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$raw = isset($_GET['raw']) && $_GET['raw'] === '1';
$back_url = '/dashboard/VB_IOT/vb_iot.php';
$session_back_url = $_SESSION['index_return_url'] ?? '';
if (is_string($session_back_url) && preg_match('#^/dashboard/(?:index\\.php|VB_IOT/vb_iot\\.php)(?:\\?.*)?$#', $session_back_url)) {
    $back_url = $session_back_url;
}

if ($doc_id <= 0) {
    http_response_code(400);
    echo 'ID van ban khong hop le.';
    exit;
}

$just_marked_read = false;
if (!$raw) {
    if (!isset($_SESSION['read_docs']) || !is_array($_SESSION['read_docs'])) {
        $_SESSION['read_docs'] = [];
    }
    if (!in_array($doc_id, $_SESSION['read_docs'], true)) {
        $_SESSION['read_docs'][] = $doc_id;
        $just_marked_read = true;
    }
}
session_write_close();

if ($raw) {
    $raw_sql = "
        SELECT d.tieude, f.TenFile, f.KieuFile, f.DuLieu
        FROM eoffice_approval d
        INNER JOIN eoffice_approval_file f ON f.MaSo = d.maso
        WHERE d.maso = ?
        ORDER BY f.TenFile ASC
        LIMIT 1
    ";
    $raw_stmt = db_mysqli_prepare($conn, $raw_sql);
    if ($raw_stmt === false) {
        die('Loi prepare VIEW RAW: ' . $conn->error);
    }
    $raw_stmt->bind_param('i', $doc_id);
    if ($raw_stmt->execute() === false) {
        die('Loi execute VIEW RAW: ' . $raw_stmt->error);
    }
    $raw_stmt->bind_result($title, $file_name, $file_type, $file_data);
    $has_raw = (bool)$raw_stmt->fetch();
    $raw_stmt->close();

    if (!$has_raw || !isset($file_data) || $file_data === null || $file_data === '') {
        http_response_code(404);
        echo 'Khong tim thay noi dung file.';
        exit;
    }
    $mime = normalize_mime($file_type ?? '', $file_name ?? '');

    $download_name = trim((string)($file_name ?? ''));
    if ($download_name === '') {
        $download_name = trim((string)($title ?? 'Van ban'));
        if ($download_name === '') {
            $download_name = 'van-ban';
        }
        if ($mime === 'application/pdf') {
            $download_name .= '.pdf';
        }
    }

    $ascii_fallback = preg_replace('/[^A-Za-z0-9._-]/', '-', $download_name);
    $ascii_fallback = trim((string)$ascii_fallback, '-');
    if ($ascii_fallback === '') {
        $ascii_fallback = 'document.pdf';
    }

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . $ascii_fallback . '"; filename*=UTF-8\'\'' . rawurlencode($download_name));
    header('Content-Length: ' . strlen($file_data));
    echo $file_data;
    exit;
}

$sql = "
    SELECT d.tieude, f.TenFile, f.KieuFile
    FROM eoffice_approval d
    INNER JOIN eoffice_approval_file f ON f.MaSo = d.maso
    WHERE d.maso = ?
    ORDER BY f.TenFile ASC
    LIMIT 1
";

$stmt = db_mysqli_prepare($conn, $sql);
if ($stmt === false) {
    die('Loi prepare VIEW: ' . $conn->error);
}
$stmt->bind_param('i', $doc_id);
$ok = $stmt->execute();
if ($ok === false) {
    die('Loi execute VIEW: ' . $stmt->error);
}
$stmt->bind_result($title, $file_name, $file_type);
$has_file = (bool)$stmt->fetch();
$stmt->close();

$display_title = build_display_title($title ?? null, $file_name ?? null);
$inline_src = '/dashboard/file/' . $doc_id . '/' . rawurlencode($display_title) . '.pdf';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars((string)($title ?? 'Van ban'), ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="stylesheet" href="../view/Styple_vb_iot.php?page=view">
</head>
<body>
    <div class="header">
        <a href="<?php echo htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8'); ?>">&larr; Quay lai danh sach</a>
        <span class="title"><?php echo htmlspecialchars((string)($title ?? 'Van ban'), ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <?php if ($has_file): ?>
        <div class="viewer">
            <iframe src="<?php echo htmlspecialchars($inline_src, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
        </div>
    <?php else: ?>
        <div class="message">Không tìm thấy văn bản phù hợp.</div>
    <?php endif; ?>
</body>
<?php if ($just_marked_read): ?>
<script>
(function () {
    try {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage('vb_iot:refresh_unread', '*');
        }
    } catch (e) {}
})();
</script>
<?php endif; ?>
</html>
<?php
$conn->close();
?>





