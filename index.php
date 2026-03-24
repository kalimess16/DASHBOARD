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
require_once __DIR__ . '/DB/connect_DB.php';

$docs_total = 0;
$docs_unread = 0;

$count_sql = "
    SELECT COUNT(*) AS total
    FROM eoffice_approval d
    WHERE EXISTS (SELECT 1 FROM eoffice_approval_file f WHERE f.maso = d.maso)
";
$count_stmt = db_mysqli_prepare($conn, $count_sql);
if ($count_stmt) {
    $count_stmt->execute();
    $docs_total = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $count_stmt->close();
}

$read_docs = $_SESSION['read_docs'] ?? [];
$read_docs = array_values(array_unique(array_map('intval', (array)$read_docs)));
$read_docs = array_values(array_filter($read_docs, static function (int $id): bool {
    return $id > 0;
}));

$read_existing = 0;
if (!empty($read_docs)) {
    $placeholders = implode(',', array_fill(0, count($read_docs), '?'));
    $read_sql = "
        SELECT COUNT(*) AS total
        FROM eoffice_approval d
        WHERE EXISTS (SELECT 1 FROM eoffice_approval_file f WHERE f.maso = d.maso)
          AND d.maso IN ($placeholders)
    ";
    $read_stmt = db_mysqli_prepare($conn, $read_sql);
    if ($read_stmt) {
        $types = str_repeat('i', count($read_docs));
        $read_stmt->bind_param($types, ...$read_docs);
        $read_stmt->execute();
        $read_existing = (int)($read_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $read_stmt->close();
    }
}
$docs_unread = max(0, $docs_total - $read_existing);

if (isset($_GET['api']) && $_GET['api'] === 'unread_count') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ok' => true,
        'unread' => $docs_unread,
        'total' => $docs_total,
    ], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard VB IOT</title>
<link rel="stylesheet" href="view/style_Das.php">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand-dot" id="homeIcon" title="Dashboard">
            <img src="/icon/VBSP.png" alt="Menu icon">
        </div>
        <button id="menuDocs" class="icon-btn" title="Văn bản" aria-label="Văn bản">
            📄
            <span class="menu-badge" id="menuUnreadBadge"><?php echo number_format($docs_unread); ?></span>
            <span class="tooltip">Văn bản</span>
        </button>
        <button id="menuDgx" class="icon-btn" title="Điểm DGX" aria-label="Điểm DGX">
            🏠
            <span class="tooltip">Điểm DGX</span>
        </button>
        <button id="menuHomePage" class="icon-btn" title="Tổng hợp dư nợ" aria-label="Tổng hợp dư nợ">
            📊
            <span class="tooltip">Tổng hợp dư nợ</span>
        </button>
        <button id="menuUserSystem" class="icon-btn" title="Quản lý user" aria-label="Quản lý user">
            👥
            <span class="tooltip">Quản lý user</span>
        </button>
    </aside>

    <main class="main">
        <section class="header">
            <h1><a href="#" id="homeLink" class="home-link">Dashboard Cá Nhân</a></h1>
        </section>

        <section class="content">
            <div id="placeholder" class="placeholder">
                <div>
                    <div class="logo-wrap">
                        <img src="/icon/VBSP.png" alt="Logo VBSP">
                    </div>
                    <strong>Bạn chưa mở chức năng</strong>
                    Bấm icon chức năng ở menu bên trái để mở chức năng.
                </div>
            </div>
            <iframe id="contentFrame" src="about:blank" title="Nội dung chính"></iframe>
        </section>
    </main>
</div>

<script>
(function () {
    var menuDocs = document.getElementById('menuDocs');
    var menuDgx = document.getElementById('menuDgx');
    var menuHomePage = document.getElementById('menuHomePage');
    var menuUserSystem = document.getElementById('menuUserSystem');
    var homeLink = document.getElementById('homeLink');
    var homeIcon = document.getElementById('homeIcon');
    var frame = document.getElementById('contentFrame');
    var placeholder = document.getElementById('placeholder');
    var unreadBadge = document.getElementById('menuUnreadBadge');
    var unreadFetchInFlight = false;
    var unreadLastFetchAt = 0;
    var unreadThrottleMs = 500;

    function openDocs() {
        menuDocs.classList.add('active');
        if (menuDgx) {
            menuDgx.classList.remove('active');
        }
        if (menuHomePage) {
            menuHomePage.classList.remove('active');
        }
        if (menuUserSystem) {
            menuUserSystem.classList.remove('active');
        }
        placeholder.style.display = 'none';
        frame.style.display = 'block';
        if (!frame.src || frame.src.indexOf('VB_IOT/vb_iot.php') === -1) {
            frame.src = 'VB_IOT/vb_iot.php';
        }
    }

    function openDgx() {
        if (menuDgx) {
            menuDgx.classList.add('active');
        }
        menuDocs.classList.remove('active');
        if (menuHomePage) {
            menuHomePage.classList.remove('active');
        }
        if (menuUserSystem) {
            menuUserSystem.classList.remove('active');
        }
        placeholder.style.display = 'none';
        frame.style.display = 'block';
        if (!frame.src || frame.src.indexOf('DGX/dgx.php') === -1) {
            frame.src = 'DGX/dgx.php';
        }
    }
    function openHomePage() {
        if (menuHomePage) {
            menuHomePage.classList.add('active');
        }
        menuDocs.classList.remove('active');
        if (menuDgx) {
            menuDgx.classList.remove('active');
        }
        if (menuUserSystem) {
            menuUserSystem.classList.remove('active');
        }
        placeholder.style.display = 'none';
        frame.style.display = 'block';
        if (!frame.src || frame.src.indexOf('HOME_PAGE/home_page.php') === -1) {
            frame.src = 'HOME_PAGE/home_page.php';
        }
    }

    function openUserSystem() {
        if (menuUserSystem) {
            menuUserSystem.classList.add('active');
        }
        menuDocs.classList.remove('active');
        if (menuDgx) {
            menuDgx.classList.remove('active');
        }
        if (menuHomePage) {
            menuHomePage.classList.remove('active');
        }
        placeholder.style.display = 'none';
        frame.style.display = 'block';
        if (!frame.src || frame.src.indexOf('USER-SYSTEM/user_system.php') === -1) {
            frame.src = 'USER-SYSTEM/user_system.php';
        }
    }

    function goHome(e) {
        if (e) {
            e.preventDefault();
        }
        menuDocs.classList.remove('active');
        if (menuDgx) {
            menuDgx.classList.remove('active');
        }
        if (menuHomePage) {
            menuHomePage.classList.remove('active');
        }
        if (menuUserSystem) {
            menuUserSystem.classList.remove('active');
        }
        frame.style.display = 'none';
        frame.src = 'about:blank';
        placeholder.style.display = 'grid';
    }

    menuDocs.addEventListener('click', openDocs);
    if (menuDgx) {
        menuDgx.addEventListener('click', openDgx);
    }
    if (menuHomePage) {
        menuHomePage.addEventListener('click', openHomePage);
    }
    if (menuUserSystem) {
        menuUserSystem.addEventListener('click', openUserSystem);
    }
    if (homeLink) {
        homeLink.addEventListener('click', goHome);
    }
    if (homeIcon) {
        homeIcon.addEventListener('click', goHome);
    }
    if (frame) {
        frame.addEventListener('load', refreshUnreadBadge);
    }
    window.addEventListener('message', function (event) {
        if (!event) {
            return;
        }
        if (event.data === 'vb_iot:refresh_unread') {
            refreshUnreadBadge();
            return;
        }
        if (event.data && event.data.type === 'vb_iot:unread_count') {
            setUnreadBadge(event.data.unread);
        }
    });
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            refreshUnreadBadge();
        }
    });
    window.addEventListener('focus', refreshUnreadBadge);

    function setUnreadBadge(value) {
        if (!unreadBadge) {
            return;
        }
        var n = Number(value);
        if (!isFinite(n) || n < 0) {
            return;
        }
        unreadBadge.textContent = String(Math.floor(n));
    }

    function refreshUnreadBadge() {
        if (!unreadBadge) {
            return;
        }
        var now = Date.now();
        if (unreadFetchInFlight) {
            return;
        }
        if (now - unreadLastFetchAt < unreadThrottleMs) {
            return;
        }
        unreadFetchInFlight = true;
        unreadLastFetchAt = now;
        fetch('index.php?api=unread_count&_t=' + Date.now(), { cache: 'no-store' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || data.ok !== true || typeof data.unread === 'undefined') {
                    return;
                }
                setUnreadBadge(data.unread);
            })
            .catch(function () {})
            .finally(function () {
                unreadFetchInFlight = false;
            });
    }

    refreshUnreadBadge();
})();
</script>
</body>
</html>



