<?php
/**
 * Report overlay + client script for DGX report list/excel export.
 * Expected vars from parent scope: $report_date_text, $report_dgx_text, $index_url, $dgx_codes_from_base.
 */
?>
<div class="fixed-overlay" id="reportOverlay" hidden>
    <div class="fixed-dialog report-dialog" role="dialog" aria-modal="true" aria-labelledby="reportDialogTitle">
        <h3 id="reportDialogTitle" class="fixed-drag-handle">BÁO CÁO THEO DGX</h3>
        <div class="fixed-filters report-filters">
            <input id="reportDgxFilter" class="fixed-control" type="text" value="<?php echo htmlspecialchars($report_dgx_text, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nhập mã DGX, cách nhau bằng ';'">
            <button type="button" id="reportFilterBtn" class="fixed-action">Xem Báo Cáo</button>
            <button type="button" id="reportExportBtn" class="fixed-action fixed-action-secondary">Xuất Excel</button>
        </div>
        <div class="fixed-warning" id="reportWarn" hidden></div>
        <div class="fixed-meta" id="reportMeta">Tổng số: 0 dòng</div>
        <div class="fixed-table-wrap">
            <table class="fixed-table report-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Mã POS</th>
                        <th style="width: 16%;">Tên GDV</th>
                        <th style="width: 14%;">Điểm GDX</th>
                        <th style="width: 8%;">Tổ TN</th>
                        <th style="width: 8%;">Số KU</th>
                        <th style="width: 8%;">KH GN</th>
                        <th style="width: 12%;">Số tiền GN</th>
                        <th style="width: 8%;">KH TNCN</th>
                        <th style="width: 8%;">KH TKCKH</th>
                        <th style="width: 8%;">Gửi TK</th>
                        <th style="width: 8%;">Rút TK</th>
                    </tr>
                </thead>
                <tbody id="reportListBody">
                    <tr><td colspan="11" class="fixed-empty">Chưa tải dữ liệu.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="fixed-actions">
            <button type="button" id="reportCloseBtn" class="fixed-close">Đóng</button>
        </div>
    </div>
</div>

<script>
(function () {
    var overlay = document.getElementById('reportOverlay');
    var dialog = overlay ? overlay.querySelector('.report-dialog') : null;
    var dragHandle = document.getElementById('reportDialogTitle');
    var openBtn = document.getElementById('openReportListBtn');
    var closeBtn = document.getElementById('reportCloseBtn');
    var filterBtn = document.getElementById('reportFilterBtn');
    var exportBtn = document.getElementById('reportExportBtn');
    var dgxFilter = document.getElementById('reportDgxFilter');
    var topDateInput = document.getElementById('reportDateInput');
    var topDgxInput = document.getElementById('reportDgxInput');
    var tbody = document.getElementById('reportListBody');
    var meta = document.getElementById('reportMeta');
    var warnBox = document.getElementById('reportWarn');
    if (!overlay || !dialog || !dragHandle || !openBtn || !closeBtn || !filterBtn || !exportBtn || !dgxFilter || !tbody || !meta || !warnBox || !topDateInput || !topDgxInput) {
        return;
    }
    var baseDgxCodes = <?php echo (json_encode(array_values(array_unique($dgx_codes_from_base ?? [])), JSON_UNESCAPED_UNICODE) ?: '[]'); ?>;
    if (!Array.isArray(baseDgxCodes)) {
        baseDgxCodes = [];
    }

    var drag = {
        active: false,
        startX: 0,
        startY: 0,
        left: 0,
        top: 0
    };

    function esc(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function numberText(value) {
        var num = Number(value || 0);
        if (!Number.isFinite(num)) {
            return '';
        }
        return num.toLocaleString('vi-VN');
    }

    function renderRows(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" class="fixed-empty">Không có dữ liệu.</td></tr>';
            return;
        }
        var html = '';
        rows.forEach(function (row) {
            html += '<tr>'
                + '<td>' + esc(row.MAPOS || '') + '</td>'
                + '<td title="' + esc(row.TEN_GDV || '') + '">' + esc(row.TEN_GDV || '') + '</td>'
                + '<td title="' + esc(row.DIEM_GDX || '') + '">' + esc(row.DIEM_GDX || '') + '</td>'
                + '<td>' + esc(numberText(row.TO_TN)) + '</td>'
                + '<td>' + esc(numberText(row.SO_KU)) + '</td>'
                + '<td>' + esc(numberText(row.KH_GN)) + '</td>'
                + '<td>' + esc(numberText(row.SOTIEN_GN)) + '</td>'
                + '<td>' + esc(numberText(row.KH_TNCN)) + '</td>'
                + '<td>' + esc(numberText(row.KH_TKCKH)) + '</td>'
                + '<td>' + esc(numberText(row.GUITK)) + '</td>'
                + '<td>' + esc(numberText(row.RUTTK)) + '</td>'
                + '</tr>';
        });
        tbody.innerHTML = html;
    }

    function normalizeCodes(raw) {
        return String(raw || '')
            .toUpperCase()
            .split(/[;\s,]+/)
            .map(function (x) { return x.replace(/[^A-Z0-9_-]/g, '').trim(); })
            .filter(function (x) { return x !== ''; })
            .filter(function (x, idx, arr) { return arr.indexOf(x) === idx; })
            .join(';');
    }

    function splitCodes(raw) {
        var codes = normalizeCodes(raw).split(';');
        return codes.filter(function (x) { return x !== ''; });
    }

    function loadReportList() {
        var dateValue = String(topDateInput.value || '').trim();
        var dgxValue = normalizeCodes(dgxFilter.value || '');
        var baseDgxValue = normalizeCodes(baseDgxCodes.join(';'));
        dgxFilter.value = dgxValue;
        topDateInput.value = dateValue;
        topDgxInput.value = dgxValue;
        warnBox.hidden = true;
        warnBox.textContent = '';

        tbody.innerHTML = '<tr><td colspan="11" class="fixed-empty">Đang tải...</td></tr>';
        meta.textContent = 'Đang tải dữ liệu...';

        var params = new URLSearchParams();
        params.set('api', 'report_list');
        params.set('report_date', dateValue);
        params.set('report_dgx', dgxValue);
        params.set('base_dgx', baseDgxValue);

        fetch('<?php echo $index_url; ?>?' + params.toString(), { cache: 'no-store' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || data.ok !== true) {
                    var msg = (data && data.message) ? data.message : 'Không tải được dữ liệu báo cáo.';
                    tbody.innerHTML = '<tr><td colspan="11" class="fixed-empty">' + esc(msg) + '</td></tr>';
                    meta.textContent = 'Tổng số: 0 dòng';
                    return;
                }
                renderRows(data.rows || []);
                meta.textContent = 'Tổng số: ' + (data.total || 0) + ' dòng | Ngày: ' + esc(data.date || '');
                if (Array.isArray(data.missing_codes) && data.missing_codes.length > 0) {
                    var checkedCodes = splitCodes(data.checked_codes || '');
                    var missingCodes = (data.missing_codes || []).map(function (x) {
                        return String(x || '').trim();
                    }).filter(function (x) { return x !== ''; });
                    var missingSet = {};
                    missingCodes.forEach(function (x) { missingSet[x] = true; });
                    var syncedCodes = checkedCodes.filter(function (x) { return !missingSet[x]; });

                    var warningHtml = 'Mã điểm GDX chưa đồng bộ: ' + esc(missingCodes.join('; '));
                    if (syncedCodes.length > 0) {
                        warningHtml += '<br>Các mã điểm đã đồng bộ: ' + esc(syncedCodes.join('; '));
                    }
                    warnBox.innerHTML = warningHtml;
                    warnBox.hidden = false;
                }
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="11" class="fixed-empty">Lỗi kết nối.</td></tr>';
                meta.textContent = 'Tổng số: 0 dòng';
                warnBox.hidden = true;
                warnBox.textContent = '';
            });
    }

    function exportReportExcel() {
        var dateValue = String(topDateInput.value || '').trim();
        var dgxValue = normalizeCodes(dgxFilter.value || '');
        var baseDgxValue = normalizeCodes(baseDgxCodes.join(';'));
        dgxFilter.value = dgxValue;
        topDateInput.value = dateValue;
        topDgxInput.value = dgxValue;

        var params = new URLSearchParams();
        params.set('api', 'report_excel');
        params.set('report_date', dateValue);
        params.set('report_dgx', dgxValue);
        params.set('base_dgx', baseDgxValue);

        var url = '<?php echo $index_url; ?>?' + params.toString();

        meta.textContent = 'Đang tải file Excel...';
        if (!exportBtn.dataset.label) {
            exportBtn.dataset.label = exportBtn.textContent;
        }
        exportBtn.textContent = 'Đang tải...';
        exportBtn.setAttribute('aria-busy', 'true');
        warnBox.hidden = true;
        warnBox.textContent = '';
        exportBtn.disabled = true;
        filterBtn.disabled = true;

        fetch(url, { cache: 'no-store' })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('Lỗi tải file.');
                }
                return res.blob();
            })
            .then(function (blob) {
                // Kiểm tra xem server có trả đúng loại file không
                if (!/spreadsheetml|ms-excel|octet-stream/.test(blob.type)) {
                    throw new Error('Định dạng file không đúng.');
                }
                var filename = 'bao_cao_giao_dich_xa_' + dateValue.replace(/[^0-9]/g, '') + '.xlsx';
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                setTimeout(function () {
                    URL.revokeObjectURL(link.href);
                    document.body.removeChild(link);
                }, 1000);
            })
            .catch(function () {
                warnBox.hidden = false;
                warnBox.textContent = 'Không tải được file Excel.';
            })
            .finally(function () {
                meta.textContent = '';
                exportBtn.removeAttribute('aria-busy');
                exportBtn.textContent = exportBtn.dataset.label || 'Xuất Excel';
                exportBtn.disabled = false;
                filterBtn.disabled = false;
            });
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function onDragMove(event) {
        if (!drag.active) {
            return;
        }
        var dx = event.clientX - drag.startX;
        var dy = event.clientY - drag.startY;
        var maxLeft = Math.max(0, overlay.clientWidth - dialog.offsetWidth);
        var maxTop = Math.max(0, overlay.clientHeight - dialog.offsetHeight);
        var nextLeft = clamp(drag.left + dx, 0, maxLeft);
        var nextTop = clamp(drag.top + dy, 0, maxTop);
        dialog.style.left = nextLeft + 'px';
        dialog.style.top = nextTop + 'px';
    }

    function stopDrag() {
        if (!drag.active) {
            return;
        }
        drag.active = false;
        document.removeEventListener('mousemove', onDragMove);
        document.removeEventListener('mouseup', stopDrag);
    }

    function startDrag(event) {
        if (event.button !== 0 || overlay.hidden) {
            return;
        }
        drag.active = true;
        drag.startX = event.clientX;
        drag.startY = event.clientY;
        var dialogRect = dialog.getBoundingClientRect();
        var overlayRect = overlay.getBoundingClientRect();
        dialog.style.position = 'absolute';
        dialog.style.margin = '0';
        dialog.style.left = (dialogRect.left - overlayRect.left) + 'px';
        dialog.style.top = (dialogRect.top - overlayRect.top) + 'px';
        drag.left = parseFloat(dialog.style.left) || 0;
        drag.top = parseFloat(dialog.style.top) || 0;
        document.addEventListener('mousemove', onDragMove);
        document.addEventListener('mouseup', stopDrag);
        event.preventDefault();
    }

    function openOverlay() {
        dgxFilter.value = topDgxInput.value || '';
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        dialog.style.position = '';
        dialog.style.left = '';
        dialog.style.top = '';
        dialog.style.margin = '';
        loadReportList();
    }

    function closeOverlay() {
        overlay.hidden = true;
        document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', openOverlay);
    closeBtn.addEventListener('click', closeOverlay);
    filterBtn.addEventListener('click', loadReportList);
    exportBtn.addEventListener('click', exportReportExcel);
    dgxFilter.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadReportList();
        }
    });
    dragHandle.addEventListener('mousedown', startDrag);
    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeOverlay();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (!overlay.hidden && event.key === 'Escape') {
            closeOverlay();
        }
    });
})();
</script>


