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
$thongTinChanTrang = dashboard_thong_tin_chan_trang();
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
<link rel="stylesheet" href="view/style_Das.php">
</head>
<body>
<div class="page-shell">
    <header class="page-header">
        <div class="page-header__inner">
            <button type="button" id="homeButton" class="brand-block" aria-label="Trang chủ dashboard">
                <span class="brand-block__logo">
                    <img src="/icon/VBSP.png" alt="Logo VBSP">
                </span>
                <span class="brand-block__text">
                    <strong>PHỤC VỤ CHO CÔNG VIỆC CÁ NHÂN</strong>
                </span>
            </button>

            <nav class="main-nav" aria-label="Menu chính dashboard">
                <?php foreach ($danhSachMenu as $nhom): ?>
                    <div class="nav-group" data-nav-group="<?php echo dashboard_html((string)($nhom['id'] ?? '')); ?>">
                        <button type="button" class="nav-group__toggle" data-menu-toggle="<?php echo dashboard_html((string)($nhom['id'] ?? '')); ?>" aria-expanded="false">
                            <span><?php echo dashboard_html((string)($nhom['nhan'] ?? '')); ?></span>
                            <span class="nav-group__caret"></span>
                        </button>
                        <div class="nav-group__panel" id="menu-<?php echo dashboard_html((string)($nhom['id'] ?? '')); ?>">
                            <?php foreach (($nhom['muc_con'] ?? []) as $mucCon): ?>
                                <button
                                    type="button"
                                    class="nav-item"
                                    data-feature-id="<?php echo dashboard_html((string)($mucCon['id'] ?? '')); ?>"
                                    data-src="<?php echo dashboard_html((string)($mucCon['duong_dan'] ?? '')); ?>"
                                    data-title="<?php echo dashboard_html((string)($mucCon['nhan'] ?? '')); ?>"
                                    data-group-label="<?php echo dashboard_html((string)($nhom['nhan'] ?? '')); ?>"
                                >
                                    <span class="nav-item__body">
                                        <strong><?php echo dashboard_html((string)($mucCon['nhan'] ?? '')); ?></strong>
                                        <span><?php echo dashboard_html((string)($mucCon['mo_ta'] ?? '')); ?></span>
                                    </span>
                                    <?php if (!empty($mucCon['hien_huy_hieu'])): ?>
                                        <span class="menu-badge" id="menuUnreadBadge"><?php echo number_format($docsUnread); ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <section class="workspace-card">
            <div id="placeholder" class="placeholder">
                <div class="placeholder__glow"></div>
                <div class="placeholder__content">
                    <div class="placeholder__logo">
                        <img src="/icon/VBSP.png" alt="Logo VBSP">
                    </div>
                    <p class="placeholder__tagline">Thấu hiểu lòng dân - Tận tâm phục vụ</p>
                    <p class="placeholder__note">Chọn một menu phía trên để mở chức năng tương ứng trong cùng khung làm việc.</p>
                </div>
            </div>

            <iframe id="contentFrame" src="about:blank" title="Nội dung dashboard"></iframe>
        </section>
    </main>

    <footer class="page-footer">
        <div class="footer-block footer-block--wide">
            <span class="footer-label">Người tạo: <?php echo dashboard_html($thongTinChanTrang['nguoi_tao']); ?></span>
        </div>
    </footer>
</div>

<script>
(function () {
    var khungNoiDung = document.getElementById('contentFrame');
    var khoiCho = document.getElementById('placeholder');
    var nutThuongHieu = document.getElementById('homeButton');
    var nutMenuCon = Array.prototype.slice.call(document.querySelectorAll('.nav-item'));
    var nutMoNhom = Array.prototype.slice.call(document.querySelectorAll('[data-menu-toggle]'));
    var huyHieuMenu = document.getElementById('menuUnreadBadge');
    var dangTaiSoChuaDoc = false;
    var lanTaiGanNhat = 0;
    var doTreTai = 500;
    var khungLamViec = document.querySelector('.workspace-card');

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

    function layChieuCaoKhungLamViec() {
        if (!khungLamViec) {
            return 420;
        }

        return Math.max(360, Math.floor(khungLamViec.getBoundingClientRect().height));
    }

    function dongBoChieuCaoKhungNoiDung() {
        var chieuCao = layChieuCaoKhungLamViec();
        if (khungNoiDung) {
            khungNoiDung.style.height = chieuCao + 'px';
        }
        if (khoiCho) {
            khoiCho.style.minHeight = chieuCao + 'px';
        }
    }

    function datChieuCaoMacDinh() {
        dongBoChieuCaoKhungNoiDung();
    }

    function huyTheoDoiChieuCao() {
        return;
    }

    function caiTheoDoiChieuCao() {
        dongBoChieuCaoKhungNoiDung();
    }

    function lapLichDongBoChieuCao() {
        [0, 80, 180].forEach(function (delay) {
            window.setTimeout(dongBoChieuCaoKhungNoiDung, delay);
        });
    }

    function veTrangChu(event) {
        if (event) {
            event.preventDefault();
        }
        huyTheoDoiChieuCao();
        if (khungNoiDung) {
            khungNoiDung.src = 'about:blank';
            khungNoiDung.style.display = 'none';
            datChieuCaoMacDinh();
        }
        if (khoiCho) {
            khoiCho.style.display = 'grid';
        }
        datChucNangDangChon('');
        dongTatCaMenu('');
    }

    function capNhatHuyHieu(value) {
        var so = Number(value);
        if (!isFinite(so) || so < 0 || !huyHieuMenu) {
            return;
        }
        huyHieuMenu.textContent = String(Math.floor(so));
    }

    function moNoiDung(nutBam) {
        if (!nutBam || !khungNoiDung || !khoiCho) {
            return;
        }
        var duongDan = nutBam.getAttribute('data-src') || '';
        var idChucNang = nutBam.getAttribute('data-feature-id') || '';

        if (duongDan === '') {
            return;
        }

        khoiCho.style.display = 'none';
        khungNoiDung.style.display = 'block';
        datChieuCaoMacDinh();
        if (!khungNoiDung.src || khungNoiDung.src.indexOf(duongDan) === -1) {
            khungNoiDung.src = duongDan;
        }
        datChucNangDangChon(idChucNang);
        dongTatCaMenu('');
        lapLichDongBoChieuCao();
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
            moNoiDung(nut);
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
    if (khungNoiDung) {
        khungNoiDung.addEventListener('load', function () {
            taiSoVanBanChuaDoc();
            lapLichDongBoChieuCao();
            caiTheoDoiChieuCao();
        });
    }

    window.addEventListener('message', function (event) {
        if (!event) {
            return;
        }
        if (event.data === 'vb_iot:refresh_unread') {
            taiSoVanBanChuaDoc();
            lapLichDongBoChieuCao();
            return;
        }
        if (event.data && event.data.type === 'vb_iot:unread_count') {
            capNhatHuyHieu(event.data.unread);
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            taiSoVanBanChuaDoc();
            lapLichDongBoChieuCao();
        }
    });
    window.addEventListener('focus', function () {
        taiSoVanBanChuaDoc();
        lapLichDongBoChieuCao();
    });
    window.addEventListener('resize', dongBoChieuCaoKhungNoiDung);

    datChieuCaoMacDinh();
    taiSoVanBanChuaDoc();
})();
</script>
</body>
</html>