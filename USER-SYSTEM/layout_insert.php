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

function layout_insert_normalize_text(string $value): string
{
    return dashboard_chuan_hoa_text($value);
}


function layout_insert_text(string $value): string
{
    return dashboard_html($value);
}

function layout_insert_flash_take(): array
{
    $flash = $_SESSION['user_system_flash'] ?? [];
    unset($_SESSION['user_system_flash']);
    return is_array($flash) ? $flash : [];
}

function layout_insert_db_fetch_assoc_rows(mysqli $conn, string $sql, array $binds = [], ?string &$err = null): ?array
{
    return dashboard_db_lay_danh_sach_assoc($conn, $sql, $binds, $err);
}

$index_url = '/dashboard/USER-SYSTEM/user_system.php';
$insert_url = '/dashboard/USER-SYSTEM/layout_insert.php';
$flash = layout_insert_flash_take();
$query_error = '';
$query_hint = 'Màn hình này dành cho thêm mới cán bộ. Khi lưu, hệ thống sẽ hiển thị thông báo ngay trên màn hình này và vẫn hỏi xác nhận trước khi ghi dữ liệu.';
$department_rows = [];
$position_rows = [];
$form_values = [
    'macanbo' => layout_insert_normalize_text((string)($_GET['macanbo'] ?? '')),
    'hoten' => layout_insert_normalize_text((string)($_GET['hoten'] ?? '')),
    'maphong' => layout_insert_normalize_text((string)($_GET['maphong'] ?? '')),
    'machucvu' => layout_insert_normalize_text((string)($_GET['machucvu'] ?? '')),
    'email' => layout_insert_normalize_text((string)($_GET['email'] ?? '')),
];

$department_err = '';
$department_rows = layout_insert_db_fetch_assoc_rows($conn, user_system_department_sql(), [], $department_err);
if ($department_rows === null) {
    $query_error = $department_err !== '' ? $department_err : 'Không đọc được danh sách phòng ban.';
    $department_rows = [];
}

$position_err = '';
$position_rows = layout_insert_db_fetch_assoc_rows($conn, user_system_posotion_sql(), [], $position_err);
if ($position_rows === null && $query_error === '') {
    $query_error = $position_err !== '' ? $position_err : 'Không đọc được danh sách chức vụ.';
    $position_rows = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thêm cán bộ</title>
<link rel="stylesheet" href="../view/Style_user_system.php">
</head>
<body>
<div class="container">
    <header class="hero">
        <div class="hero-copy">
            <div class="eyebrow">USER-SYSTEM</div>
            <h1>Thêm cán bộ mới</h1>
            <p>
                Trang này tách riêng thao tác thêm mới khỏi màn hình danh sách để dễ nhập liệu hơn.
                Dữ liệu phòng ban và chức vụ vẫn đọc từ database `qlcv` như ở màn hình quản lý cán bộ.
            </p>
        </div>
        <div class="hero-actions">
            <a class="hero-btn hero-btn-primary" href="<?php echo $index_url; ?>">Danh sách cán bộ</a>
            <a class="hero-btn hero-btn-ghost" href="<?php echo $insert_url; ?>">Làm mới form</a>
        </div>
    </header>

    <?php if (!empty($flash['message'])): ?>
        <div class="notice <?php echo (($flash['type'] ?? 'info') === 'error') ? 'notice-error' : 'notice-info'; ?>">
            <?php echo layout_insert_text((string)$flash['message']); ?>
        </div>
    <?php elseif ($query_error !== ''): ?>
        <div class="notice notice-error"><?php echo layout_insert_text($query_error); ?></div>
    <?php else: ?>
        <div class="notice notice-info"><?php echo layout_insert_text($query_hint); ?></div>
    <?php endif; ?>

    <section class="workspace">
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2>Hướng dẫn nhập nhanh</h2>
                    <p>Dùng màn hình này khi cần thêm cán bộ mới thay vì nhập trực tiếp trên trang danh sách.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <tbody>
                        <tr>
                            <td style="width: 30%;"><strong>Mã cán bộ</strong></td>
                            <td>Nhập mã duy nhất, ví dụ `CB001`.</td>
                        </tr>
                        <tr>
                            <td><strong>Mật khẩu</strong></td>
                            <td>Bắt buộc khi thêm mới. Hệ thống sẽ mã hóa qua hàm `PASSWORD(...)` của MySQL như luồng hiện tại.</td>
                        </tr>
                        <tr>
                            <td><strong>Phòng ban / Chức vụ</strong></td>
                            <td>Chọn từ dữ liệu hiện có trong DB để tránh sai mã.</td>
                        </tr>
                        <tr>
                            <td><strong>Sau khi lưu</strong></td>
                            <td>Hệ thống sẽ hiển thị thông báo thành công hoặc thất bại ngay trên màn hình thêm mới.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="panel panel-form">
            <div class="panel-head panel-head-tight">
                <div>
                    <h2>Thông tin cán bộ</h2>
                    <p>Nhập dữ liệu rồi bấm lưu. Hệ thống sẽ hỏi xác nhận trước khi ghi.</p>
                </div>
            </div>

            <form method="post" action="<?php echo $index_url; ?>" class="user-form" id="userInsertForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="confirm_write" id="confirmWrite" value="0">
                <input type="hidden" name="original_macanbo" value="">
                <input type="hidden" name="return_keyword" value="">
                <input type="hidden" name="return_page" value="1">
                <input type="hidden" name="return_url" value="<?php echo layout_insert_text($insert_url); ?>">

                <div class="form-grid">
                    <label>
                        <span>Mã cán bộ</span>
                        <input type="text" name="macanbo" id="macanbo" value="<?php echo layout_insert_text($form_values['macanbo']); ?>" placeholder="VD: CB001">
                    </label>
                    <label>
                        <span>Họ tên</span>
                        <input type="text" name="hoten" id="hoten" value="<?php echo layout_insert_text($form_values['hoten']); ?>" placeholder="Nguyễn Văn A">
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
                                <option value="<?php echo layout_insert_text($department_code); ?>" <?php echo $department_code === $form_values['maphong'] ? 'selected' : ''; ?>>
                                    <?php echo layout_insert_text($department_name !== '' ? $department_name : $department_code); ?>
                                </option>
                            <?php endforeach; ?>
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
                                <option value="<?php echo layout_insert_text($position_code); ?>" <?php echo $position_code === $form_values['machucvu'] ? 'selected' : ''; ?>>
                                    <?php echo layout_insert_text($position_name !== '' ? $position_name : $position_code); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Mật khẩu</span>
                        <input type="password" name="password" id="password" placeholder="Nhập mật khẩu ban đầu" autocomplete="new-password">
                    </label>

                    <label class="span-2">
                        <span>Email</span>
                        <input type="email" name="email" id="email" value="<?php echo layout_insert_text($form_values['email']); ?>" placeholder="user@example.com">
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="action-btn action-btn-primary" id="saveBtn">Lưu cán bộ</button>
                    <button type="button" class="action-btn" id="resetBtn">Reset</button>
                </div>
            </form>
        </aside>
    </section>
</div>
<script>
(function () {
    var form = document.getElementById('userInsertForm');
    var confirmWrite = document.getElementById('confirmWrite');
    var saveBtn = document.getElementById('saveBtn');
    var resetBtn = document.getElementById('resetBtn');

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm('Bạn có xác nhận muốn thêm mới user này không?')) {
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
            window.location.href = '<?php echo $insert_url; ?>';
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