<?php
require_once __DIR__ . '/FUNC_SHARE/ham_dung_chung.php';
dashboard_chan_ip_khong_hop_le();
dashboard_khoi_tao_phien('DASHBOARD_VB_IOT_SESSID');
require_once __DIR__ . '/DB/connect_DB.php';

$ketNoiDashboard = $conn ?? null;
if (!$ketNoiDashboard instanceof mysqli) {
    die('Khong ket noi duoc DB cho dashboard.');
}

$danhSachMenu = dashboard_cau_hinh_menu();
$danhSachMenu = array_values(array_filter($danhSachMenu, static function (array $nhom): bool {
    return ($nhom['id'] ?? '') !== 'tin-dung';
}));
$thongTinChanTrang = dashboard_thong_tin_chan_trang();
$duongDanTrangChu = 'HOME_PAGE/home_page.php';
$idTrangChu = 'tong-hop-du-no';
$docsTotal = 0;
$docsUnread = 0;

$countSql = "
    SELECT COUNT(*) AS total
    FROM eoffice_approval d
    WHERE EXISTS (SELECT 1 FROM eoffice_approval_file f WHERE f.maso = d.maso)
";
$countStmt = db_mysqli_prepare($ketNoiDashboard, $countSql);
if ($countStmt) {
    $countStmt->execute();
    $docsTotal = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();
}

$readDocs = $_SESSION['read_docs'] ?? [];
$readDocs = array_values(array_unique(array_map('intval', (array)$readDocs)));
$readDocs = array_values(array_filter($readDocs, static function (int $id): bool {
    return $id > 0;
}));

$readExisting = 0;
if (!empty($readDocs)) {
    $placeholders = implode(',', array_fill(0, count($readDocs), '?'));
    $readSql = "
        SELECT COUNT(*) AS total
        FROM eoffice_approval d
        WHERE EXISTS (SELECT 1 FROM eoffice_approval_file f WHERE f.maso = d.maso)
          AND d.maso IN ($placeholders)
    ";
    $readStmt = db_mysqli_prepare($ketNoiDashboard, $readSql);
    if ($readStmt) {
        $types = str_repeat('i', count($readDocs));
        $readStmt->bind_param($types, ...$readDocs);
        $readStmt->execute();
        $readExisting = (int)($readStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $readStmt->close();
    }
}
$docsUnread = max(0, $docsTotal - $readExisting);

if (isset($_GET['api']) && $_GET['api'] === 'unread_count') {
    dashboard_phan_hoi_json([
        'ok' => true,
        'unread' => $docsUnread,
        'total' => $docsTotal,
    ]);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard VB IOT</title>
<link rel="icon" type="image/png" href="assets/dashboard-vb-iot-icon.png">
<link rel="apple-touch-icon" href="assets/dashboard-vb-iot-icon.png">
<link rel="stylesheet" href="view/style_Das.php">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="titlebar">
            <button
                type="button"
                id="homeButton"
                class="brand-block"
                data-home-src="<?php echo dashboard_html($duongDanTrangChu); ?>"
                data-home-id="<?php echo dashboard_html($idTrangChu); ?>"
                aria-label="Mở trang tổng hợp dư nợ"
            >
                <span class="brand-mark">
                    <img src="assets/dashboard-vb-iot-icon.png" alt="Dashboard VB IOT">
                </span>
                <span class="brand-text">
                    <strong>Dashboard VB IOT</strong>
                    <small>Home Page</small>
                </span>
            </button>
            <span class="creator-chip"><?php echo dashboard_html($thongTinChanTrang['nguoi_tao']); ?></span>
        </div>

        <nav class="main-nav" id="mainNav" aria-label="Menu chính dashboard">
            <?php foreach ($danhSachMenu as $nhom): ?>
                <section class="nav-group" data-nav-group="<?php echo dashboard_html((string)($nhom['id'] ?? '')); ?>">
                    <button
                        type="button"
                        class="nav-group__toggle"
                        data-menu-toggle="<?php echo dashboard_html((string)($nhom['id'] ?? '')); ?>"
                        aria-expanded="false"
                    >
                        <span><?php echo dashboard_html((string)($nhom['nhan'] ?? '')); ?></span>
                        <span class="nav-group__caret" aria-hidden="true"></span>
                    </button>

                    <div class="nav-group__panel" id="menu-<?php echo dashboard_html((string)($nhom['id'] ?? '')); ?>">
                        <?php foreach (($nhom['muc_con'] ?? []) as $mucCon): ?>
                            <button
                                type="button"
                                class="nav-item"
                                data-feature-id="<?php echo dashboard_html((string)($mucCon['id'] ?? '')); ?>"
                                data-src="<?php echo dashboard_html((string)($mucCon['duong_dan'] ?? '')); ?>"
                                data-title="<?php echo dashboard_html((string)($mucCon['nhan'] ?? '')); ?>"
                                data-open-mode="<?php echo !empty($mucCon['mo_tab_moi']) ? 'new-tab' : 'iframe'; ?>"
                            >
                                <span class="nav-item__label"><?php echo dashboard_html((string)($mucCon['nhan'] ?? '')); ?></span>
                                <?php if (!empty($mucCon['hien_huy_hieu'])): ?>
                                    <span class="menu-badge" id="menuUnreadBadge"><?php echo number_format($docsUnread); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </nav>
    </header>

    <main class="content-shell">
        <section class="workspace">
            <div class="frame-stack" id="frameStack">
                <iframe
                    class="content-frame is-active"
                    data-feature-id="<?php echo dashboard_html($idTrangChu); ?>"
                    data-src="<?php echo dashboard_html($duongDanTrangChu); ?>"
                    src="<?php echo dashboard_html($duongDanTrangChu); ?>"
                    title="Nội dung dashboard"
                ></iframe>
            </div>
        </section>
    </main>
</div>

<script>
(function () {
    var khungStack = document.getElementById('frameStack');
    var nutThuongHieu = document.getElementById('homeButton');
    var nutMenuCon = Array.prototype.slice.call(document.querySelectorAll('.nav-item'));
    var nutMoNhom = Array.prototype.slice.call(document.querySelectorAll('[data-menu-toggle]'));
    var huyHieuMenu = document.getElementById('menuUnreadBadge');
    var dangTaiSoChuaDoc = false;
    var lanTaiGanNhat = 0;
    var doTreTai = 500;
    var khungTheoChucNang = {};

    function khoaKhung(idChucNang, duongDan) {
        return idChucNang || duongDan || 'home';
    }

    function ganSuKienKhung(khung) {
        if (!khung || khung.dataset.bound === '1') {
            return;
        }
        khung.dataset.bound = '1';
        khung.addEventListener('load', taiSoVanBanChuaDoc);
    }

    Array.prototype.slice.call(document.querySelectorAll('.content-frame')).forEach(function (khung) {
        khungTheoChucNang[khoaKhung(khung.getAttribute('data-feature-id'), khung.getAttribute('data-src'))] = khung;
        ganSuKienKhung(khung);
    });

    function dongTatCaMenu(idDangMo) {
        nutMoNhom.forEach(function (nut) {
            var idNhom = nut.getAttribute('data-menu-toggle');
            var nhom = nut.closest('.nav-group');
            var dangMo = idDangMo && idNhom === idDangMo;
            if (nhom) {
                nhom.classList.toggle('is-open', !!dangMo);
            }
            nut.setAttribute('aria-expanded', dangMo ? 'true' : 'false');
        });
    }

    function datChucNangDangChon(idChucNang) {
        nutMenuCon.forEach(function (nut) {
            var trung = nut.getAttribute('data-feature-id') === idChucNang;
            nut.classList.toggle('is-active', trung);
        });
    }

    function capNhatHuyHieu(value) {
        var so = Number(value);
        if (!isFinite(so) || so < 0 || !huyHieuMenu) {
            return;
        }
        huyHieuMenu.textContent = String(Math.floor(so));
    }

    function taoHoacLayKhung(duongDan, idChucNang, tieuDe) {
        var key = khoaKhung(idChucNang, duongDan);
        if (khungTheoChucNang[key]) {
            return khungTheoChucNang[key];
        }
        if (!khungStack) {
            return null;
        }

        var khung = document.createElement('iframe');
        khung.className = 'content-frame';
        khung.setAttribute('data-feature-id', idChucNang || '');
        khung.setAttribute('data-src', duongDan || '');
        khung.title = tieuDe || 'Nội dung dashboard';
        khung.src = duongDan;
        ganSuKienKhung(khung);
        khungStack.appendChild(khung);
        khungTheoChucNang[key] = khung;
        return khung;
    }

    function hienKhung(khungCanMo) {
        if (!khungCanMo || !khungStack) {
            return;
        }
        Array.prototype.slice.call(khungStack.querySelectorAll('.content-frame')).forEach(function (khung) {
            khung.classList.toggle('is-active', khung === khungCanMo);
        });
    }

    function moNoiDung(duongDan, idChucNang, tieuDe) {
        if (!duongDan) {
            return;
        }
        var khung = taoHoacLayKhung(duongDan, idChucNang, tieuDe);
        hienKhung(khung);
        datChucNangDangChon(idChucNang || '');
        dongTatCaMenu('');
    }

    function moLinkNgoai(duongDan) {
        if (!duongDan) {
            return;
        }
        var tabMoi = window.open(duongDan, '_blank', 'noopener,noreferrer');
        if (tabMoi) {
            tabMoi.opener = null;
        }
        dongTatCaMenu('');
    }

    function moNoiDungTuNut(nutBam) {
        if (!nutBam) {
            return;
        }
        var duongDan = nutBam.getAttribute('data-src') || '';
        if ((nutBam.getAttribute('data-open-mode') || '') === 'new-tab') {
            moLinkNgoai(duongDan);
            return;
        }
        moNoiDung(
            duongDan,
            nutBam.getAttribute('data-feature-id') || '',
            nutBam.getAttribute('data-title') || ''
        );
    }

    function veTrangChu(event) {
        if (event) {
            event.preventDefault();
        }
        if (!nutThuongHieu) {
            return;
        }
        moNoiDung(
            nutThuongHieu.getAttribute('data-home-src') || '',
            nutThuongHieu.getAttribute('data-home-id') || '',
            'Tổng hợp dư nợ'
        );
    }

    function taiSoVanBanChuaDoc() {
        if (!huyHieuMenu) {
            return;
        }
        var hienTai = Date.now();
        if (dangTaiSoChuaDoc || hienTai - lanTaiGanNhat < doTreTai) {
            return;
        }
        dangTaiSoChuaDoc = true;
        lanTaiGanNhat = hienTai;

        fetch('index.php?api=unread_count&_t=' + Date.now(), { cache: 'no-store' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || data.ok !== true || typeof data.unread === 'undefined') {
                    return;
                }
                capNhatHuyHieu(data.unread);
            })
            .catch(function () {})
            .finally(function () {
                dangTaiSoChuaDoc = false;
            });
    }

    nutMoNhom.forEach(function (nut) {
        nut.addEventListener('click', function () {
            var idNhom = nut.getAttribute('data-menu-toggle') || '';
            var nhom = nut.closest('.nav-group');
            var dangMo = nhom && nhom.classList.contains('is-open');
            dongTatCaMenu(dangMo ? '' : idNhom);
        });
    });

    nutMenuCon.forEach(function (nut) {
        nut.addEventListener('click', function () {
            moNoiDungTuNut(nut);
        });
    });

    document.addEventListener('click', function (event) {
        var namTrongMenu = event.target && event.target.closest('.nav-group');
        if (!namTrongMenu) {
            dongTatCaMenu('');
        }
    });

    if (nutThuongHieu) {
        nutThuongHieu.addEventListener('click', veTrangChu);
    }

    window.addEventListener('message', function (event) {
        if (!event) {
            return;
        }
        if (event.data === 'vb_iot:refresh_unread') {
            taiSoVanBanChuaDoc();
            return;
        }
        if (event.data && event.data.type === 'vb_iot:unread_count') {
            capNhatHuyHieu(event.data.unread);
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            taiSoVanBanChuaDoc();
        }
    });
    window.addEventListener('focus', taiSoVanBanChuaDoc);

    datChucNangDangChon('<?php echo dashboard_html($idTrangChu); ?>');
    taiSoVanBanChuaDoc();
})();
</script>
</body>
</html>