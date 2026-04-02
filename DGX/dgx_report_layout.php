<?php
/**
 * Report overlays + client scripts for DGX report features.
 * Expected vars from parent scope:
 * - $report_date_text
 * - $report_dgx_text
 * - $custom_report_from_date
 * - $custom_report_to_date
 * - $custom_report_pos_text
 * - $index_url
 * - $dgx_codes_from_base
 */
?>
<div class="fixed-overlay" id="reportOverlay" hidden>
    <div class="fixed-dialog report-dialog" role="dialog" aria-modal="true" aria-labelledby="reportDialogTitle">
        <h3 id="reportDialogTitle" class="fixed-drag-handle">BÁO CÁO THEO DGX</h3>
        <div class="fixed-filters report-filters">
            <input id="reportDgxFilter" class="fixed-control" type="text" value="<?php echo htmlspecialchars($report_dgx_text, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nhập mã DGX, cách nhau bằng ';'">
            <button type="button" id="reportFilterBtn" class="fixed-action">Xem báo cáo</button>
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

<div class="fixed-overlay" id="customReportOverlay" hidden>
    <div class="fixed-dialog custom-report-dialog" role="dialog" aria-modal="true" aria-labelledby="customReportDialogTitle">
        <h3 id="customReportDialogTitle" class="fixed-drag-handle">BÁO CÁO THEO YÊU CẦU</h3>
        <div class="fixed-filters custom-report-filters">
            <input id="customReportFromDate" class="fixed-control" type="date" value="<?php echo htmlspecialchars($custom_report_from_date, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Từ ngày">
            <input id="customReportToDate" class="fixed-control" type="date" value="<?php echo htmlspecialchars($custom_report_to_date, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Đến ngày">
            <input id="customReportPos" class="fixed-control" type="text" value="<?php echo htmlspecialchars($custom_report_pos_text, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nhập mã POS, cách nhau bằng ';'">
            <button type="button" id="customReportFilterBtn" class="fixed-action">Báo cáo</button>
            <button type="button" id="customReportExportBtn" class="fixed-action fixed-action-secondary">Xuất báo cáo</button>
        </div>
        <div class="fixed-meta" id="customReportMeta">Sẵn sàng tải báo cáo.</div>
        <div class="fixed-warning" id="customReportWarn" hidden></div>
        <div class="fixed-table-wrap">
            <table class="fixed-table custom-report-table">
                <thead>
                    <tr>
                        <th style="width: 11%;">Ngày giao dịch xã</th>
                        <th style="width: 9%;">Mã POS</th>
                        <th style="width: 16%;">Tên GDV</th>
                        <th style="width: 16%;">Điểm GDX</th>
                        <th style="width: 7%;">Tổ TN</th>
                        <th style="width: 7%;">Số KU</th>
                        <th style="width: 7%;">KH GN</th>
                        <th style="width: 10%;">Số tiền GN</th>
                        <th style="width: 7%;">KH TNCN</th>
                        <th style="width: 7%;">KH TKCKH</th>
                        <th style="width: 9%;">Gửi TK</th>
                        <th style="width: 9%;">Rút TK</th>
                    </tr>
                </thead>
                <tbody id="customReportBody">
                    <tr><td colspan="12" class="fixed-empty">Nhập điều kiện rồi nhấn Báo cáo.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="fixed-actions">
            <button type="button" id="customReportCloseBtn" class="fixed-close">Đóng</button>
        </div>
    </div>
</div>
<script>
(function () {
    function esc(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function extractResponseMessage(rawText, fallbackMessage) {
        var text = String(rawText || '')
            .replace(/<[^>]+>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
        return text !== '' ? text : fallbackMessage;
    }

    function readJsonResponse(response, fallbackMessage) {
        return response.text().then(function (text) {
            var rawText = String(text || '');
            var data = null;
            if (rawText !== '') {
                try {
                    data = JSON.parse(rawText);
                } catch (error) {
                    throw new Error(extractResponseMessage(rawText, fallbackMessage));
                }
            }

            if (!response.ok) {
                throw new Error((data && data.message) ? data.message : extractResponseMessage(rawText, fallbackMessage));
            }

            return data;
        });
    }

    function normalizeCodeTokens(raw) {
        return String(raw || '')
            .toUpperCase()
            .split(/[;\s,]+/)
            .map(function (x) { return x.replace(/[^A-Z0-9_-]/g, '').trim(); })
            .filter(function (x) { return x !== ''; })
            .filter(function (x, idx, arr) { return arr.indexOf(x) === idx; })
            .join(';');
    }

    function normalizePosTokens(raw) {
        return String(raw || '')
            .toUpperCase()
            .split(/[;\s,]+/)
            .map(function (x) {
                var code = x.replace(/[^A-Z0-9_-]/g, '').trim();
                if (/^\d+$/.test(code) && code.length < 6) {
                    return code.padStart(6, '0');
                }
                return code;
            })
            .filter(function (x) { return x !== ''; })
            .filter(function (x, idx, arr) { return arr.indexOf(x) === idx; })
            .join(';');
    }

    function splitCodeTokens(raw) {
        var normalized = normalizeCodeTokens(raw);
        return normalized === '' ? [] : normalized.split(';');
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function initDraggableOverlay(overlayId, dialogSelector, titleId, closeId) {
        var overlay = document.getElementById(overlayId);
        var dialog = overlay ? overlay.querySelector(dialogSelector) : null;
        var dragHandle = document.getElementById(titleId);
        var closeBtn = document.getElementById(closeId);
        if (!overlay || !dialog || !dragHandle || !closeBtn) {
            return null;
        }

        var drag = {
            active: false,
            startX: 0,
            startY: 0,
            left: 0,
            top: 0
        };

        function onDragMove(event) {
            if (!drag.active) {
                return;
            }
            var dx = event.clientX - drag.startX;
            var dy = event.clientY - drag.startY;
            var maxLeft = Math.max(0, overlay.clientWidth - dialog.offsetWidth);
            var maxTop = Math.max(0, overlay.clientHeight - dialog.offsetHeight);
            dialog.style.left = clamp(drag.left + dx, 0, maxLeft) + 'px';
            dialog.style.top = clamp(drag.top + dy, 0, maxTop) + 'px';
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
            overlay.hidden = false;
            document.body.style.overflow = 'hidden';
            dialog.style.position = '';
            dialog.style.left = '';
            dialog.style.top = '';
            dialog.style.margin = '';
        }

        function closeOverlay() {
            overlay.hidden = true;
            document.body.style.overflow = '';
            stopDrag();
        }

        dragHandle.addEventListener('mousedown', startDrag);
        closeBtn.addEventListener('click', closeOverlay);
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

        return {
            overlay: overlay,
            open: openOverlay,
            close: closeOverlay
        };
    }

    (function () {
        var controller = initDraggableOverlay('reportOverlay', '.report-dialog', 'reportDialogTitle', 'reportCloseBtn');
        var openBtn = document.getElementById('openReportListBtn');
        var filterBtn = document.getElementById('reportFilterBtn');
        var exportBtn = document.getElementById('reportExportBtn');
        var dgxFilter = document.getElementById('reportDgxFilter');
        var topDateInput = document.getElementById('reportDateInput');
        var topDgxInput = document.getElementById('reportDgxInput');
        var tbody = document.getElementById('reportListBody');
        var meta = document.getElementById('reportMeta');
        var warnBox = document.getElementById('reportWarn');
        if (!controller || !openBtn || !filterBtn || !exportBtn || !dgxFilter || !topDateInput || !topDgxInput || !tbody || !meta || !warnBox) {
            return;
        }

        var baseDgxCodes = <?php echo (json_encode(array_values(array_unique($dgx_codes_from_base ?? [])), JSON_UNESCAPED_UNICODE) ?: '[]'); ?>;
        if (!Array.isArray(baseDgxCodes)) {
            baseDgxCodes = [];
        }

        var countFormatter = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
        var moneyFormatter = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });

        function formatCount(value) {
            var num = Number(value || 0);
            return Number.isFinite(num) ? countFormatter.format(num) : '';
        }

        function formatMoney(value) {
            var num = Number(value || 0);
            return Number.isFinite(num) ? moneyFormatter.format(num) : '';
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
                    + '<td>' + esc(formatCount(row.TO_TN)) + '</td>'
                    + '<td>' + esc(formatCount(row.SO_KU)) + '</td>'
                    + '<td>' + esc(formatCount(row.KH_GN)) + '</td>'
                    + '<td>' + esc(formatMoney(row.SOTIEN_GN)) + '</td>'
                    + '<td>' + esc(formatCount(row.KH_TNCN)) + '</td>'
                    + '<td>' + esc(formatCount(row.KH_TKCKH)) + '</td>'
                    + '<td>' + esc(formatMoney(row.GUITK)) + '</td>'
                    + '<td>' + esc(formatMoney(row.RUTTK)) + '</td>'
                    + '</tr>';
            });
            tbody.innerHTML = html;
        }

        function renderWarning(data) {
            warnBox.hidden = true;
            warnBox.textContent = '';
            if (!data || !Array.isArray(data.missing_codes) || data.missing_codes.length === 0) {
                return;
            }

            var checkedCodes = splitCodeTokens(data.checked_codes || '');
            var missingCodes = (data.missing_codes || []).map(function (x) { return String(x || '').trim(); }).filter(function (x) { return x !== ''; });
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

        function loadReportList() {
            var dateValue = String(topDateInput.value || '').trim();
            var dgxValue = normalizeCodeTokens(dgxFilter.value || '');
            var baseDgxValue = normalizeCodeTokens(baseDgxCodes.join(';'));
            dgxFilter.value = dgxValue;
            topDateInput.value = dateValue;
            topDgxInput.value = dgxValue;
            warnBox.hidden = true;
            warnBox.textContent = '';

            tbody.innerHTML = '<tr><td colspan="11" class="fixed-empty">Đang tải...</td></tr>';
            meta.textContent = 'Đang tổng hợp dữ liệu theo từng ngày...';

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
                    renderWarning(data);
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
            var dgxValue = normalizeCodeTokens(dgxFilter.value || '');
            var baseDgxValue = normalizeCodeTokens(baseDgxCodes.join(';'));
            dgxFilter.value = dgxValue;
            topDateInput.value = dateValue;
            topDgxInput.value = dgxValue;

            var params = new URLSearchParams();
            params.set('api', 'report_excel');
            params.set('report_date', dateValue);
            params.set('report_dgx', dgxValue);
            params.set('base_dgx', baseDgxValue);

            var url = '<?php echo $index_url; ?>?' + params.toString();
            meta.textContent = 'Đang tổng hợp file Excel...';
            if (!exportBtn.dataset.label) {
                exportBtn.dataset.label = exportBtn.textContent;
            }
            exportBtn.textContent = 'Đang tải...';
            exportBtn.setAttribute('aria-busy', 'true');
            exportBtn.disabled = true;
            filterBtn.disabled = true;
            warnBox.hidden = true;
            warnBox.textContent = '';

            fetch(url, { cache: 'no-store' })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('Lỗi tải file.');
                    }
                    return res.blob();
                })
                .then(function (blob) {
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

        openBtn.addEventListener('click', function () {
            dgxFilter.value = topDgxInput.value || '';
            controller.open();
            loadReportList();
        });
        filterBtn.addEventListener('click', loadReportList);
        exportBtn.addEventListener('click', exportReportExcel);
        dgxFilter.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                loadReportList();
            }
        });
    })();

    (function () {
        var controller = initDraggableOverlay('customReportOverlay', '.custom-report-dialog', 'customReportDialogTitle', 'customReportCloseBtn');
        var openBtn = document.getElementById('openCustomReportBtn');
        var filterBtn = document.getElementById('customReportFilterBtn');
        var exportBtn = document.getElementById('customReportExportBtn');
        var fromInput = document.getElementById('customReportFromDate');
        var toInput = document.getElementById('customReportToDate');
        var posInput = document.getElementById('customReportPos');
        var tbody = document.getElementById('customReportBody');
        var meta = document.getElementById('customReportMeta');
        var warnBox = document.getElementById('customReportWarn');
        if (!controller || !openBtn || !filterBtn || !exportBtn || !fromInput || !toInput || !posInput || !tbody || !meta || !warnBox) {
            return;
        }

        var countFormatter = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
        var moneyFormatter = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });
        var idleSummary = 'Sẵn sàng tải báo cáo.';
        var invalidFilterSummary = 'Kiểm tra lại điều kiện lọc.';
        var loadErrorSummary = 'Tải báo cáo thất bại.';
        var hasLoadedResult = false;
        var lastSummary = idleSummary;
        var activeLoadController = null;

        function formatCount(value) {
            var num = Number(value || 0);
            return Number.isFinite(num) ? countFormatter.format(num) : '';
        }

        function formatMoney(value) {
            var num = Number(value || 0);
            return Number.isFinite(num) ? moneyFormatter.format(num) : '';
        }

        function setTableMessage(message) {
            tbody.innerHTML = '<tr><td colspan="12" class="fixed-empty">' + esc(message) + '</td></tr>';
        }

        function setMetaText(message) {
            meta.textContent = String(message || '');
        }

        function commitSummary(summary) {
            lastSummary = String(summary || idleSummary);
            setMetaText(lastSummary);
        }

        function clearWarning() {
            warnBox.hidden = true;
            warnBox.textContent = '';
        }

        function setWarning(message) {
            warnBox.hidden = false;
            warnBox.textContent = message;
        }

        function setControlsBusy(activeButton, busyLabel) {
            [filterBtn, exportBtn].forEach(function (button) {
                if (!button.dataset.label) {
                    button.dataset.label = button.textContent;
                }
                button.disabled = true;
            });
            [fromInput, toInput, posInput].forEach(function (input) {
                input.disabled = true;
            });
            if (activeButton) {
                activeButton.textContent = busyLabel;
                activeButton.setAttribute('aria-busy', 'true');
            }
        }

        function clearControlsBusy() {
            [fromInput, toInput, posInput].forEach(function (input) {
                input.disabled = false;
            });
            [filterBtn, exportBtn].forEach(function (button) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.textContent = button.dataset.label || button.textContent;
            });
        }

        function resetView(message) {
            hasLoadedResult = false;
            tbody.dataset.loaded = '0';
            setTableMessage(message);
            commitSummary(idleSummary);
            clearWarning();
        }

        function renderRows(rows) {
            hasLoadedResult = true;
            tbody.dataset.loaded = '1';
            if (!Array.isArray(rows) || rows.length === 0) {
                setTableMessage('Không có dữ liệu.');
                return;
            }
            var html = '';
            rows.forEach(function (row) {
                html += '<tr>'
                    + '<td>' + esc(row.NGAY_GIAO_DICH_XA || '') + '</td>'
                    + '<td>' + esc(row.MAPOS || '') + '</td>'
                    + '<td title="' + esc(row.TEN_GDV || '') + '">' + esc(row.TEN_GDV || '') + '</td>'
                    + '<td title="' + esc(row.DIEM_GDX || '') + '">' + esc(row.DIEM_GDX || '') + '</td>'
                    + '<td>' + esc(formatCount(row.TO_TN)) + '</td>'
                    + '<td>' + esc(formatCount(row.SO_KU)) + '</td>'
                    + '<td>' + esc(formatCount(row.KH_GN)) + '</td>'
                    + '<td>' + esc(formatMoney(row.SOTIEN_GN)) + '</td>'
                    + '<td>' + esc(formatCount(row.KH_TNCN)) + '</td>'
                    + '<td>' + esc(formatCount(row.KH_TKCKH)) + '</td>'
                    + '<td>' + esc(formatMoney(row.GUITK)) + '</td>'
                    + '<td>' + esc(formatMoney(row.RUTTK)) + '</td>'
                    + '</tr>';
            });
            tbody.innerHTML = html;
        }

        function restorePreviousResult(previousHtml, previousSummary, previousLoaded) {
            if (!previousLoaded) {
                return false;
            }
            hasLoadedResult = true;
            tbody.dataset.loaded = '1';
            tbody.innerHTML = previousHtml;
            commitSummary(previousSummary);
            return true;
        }

        function showLoadError(message, previousHtml, previousSummary, previousLoaded) {
            if (!restorePreviousResult(previousHtml, previousSummary, previousLoaded)) {
                hasLoadedResult = false;
                tbody.dataset.loaded = '0';
                setTableMessage(message);
                commitSummary(loadErrorSummary);
            }
            setWarning(message);
        }

        function validateFilters() {
            var fromValue = String(fromInput.value || '').trim();
            var toValue = String(toInput.value || '').trim();
            if (fromValue === '' || toValue === '') {
                return 'Cần nhập đầy đủ từ ngày và đến ngày.';
            }
            if (fromValue > toValue) {
                return 'Khoảng ngày không hợp lệ.';
            }
            return '';
        }

        function buildParams() {
            var posValue = normalizePosTokens(posInput.value || '');
            posInput.value = posValue;
            var params = new URLSearchParams();
            params.set('custom_from_date', String(fromInput.value || '').trim());
            params.set('custom_to_date', String(toInput.value || '').trim());
            params.set('custom_pos', posValue);
            return params;
        }

        function loadCustomReport() {
            var validationMessage = validateFilters();
            if (validationMessage !== '') {
                hasLoadedResult = false;
                tbody.dataset.loaded = '0';
                setTableMessage(validationMessage);
                commitSummary(invalidFilterSummary);
                setWarning(validationMessage);
                return;
            }

            if (activeLoadController) {
                activeLoadController.abort();
            }

            var previousHtml = tbody.innerHTML;
            var previousSummary = hasLoadedResult ? lastSummary : idleSummary;
            var previousLoaded = hasLoadedResult;
            var params = buildParams();
            params.set('api', 'custom_report_list');
            activeLoadController = typeof AbortController !== 'undefined' ? new AbortController() : null;

            if (!previousLoaded) {
                setTableMessage('Đang tải...');
            }
            clearWarning();
            setMetaText('Đang tổng hợp dữ liệu theo từng ngày...');
            setControlsBusy(filterBtn, 'Đang tải...');

            var fetchOptions = { cache: 'no-store' };
            if (activeLoadController) {
                fetchOptions.signal = activeLoadController.signal;
            }

            fetch('<?php echo $index_url; ?>?' + params.toString(), fetchOptions)
                .then(function (res) { return readJsonResponse(res, 'Không tải được dữ liệu báo cáo.'); })
                .then(function (data) {
                    if (!data || data.ok !== true) {
                        var msg = (data && data.message) ? data.message : 'Không tải được dữ liệu báo cáo.';
                        showLoadError(msg, previousHtml, previousSummary, previousLoaded);
                        return;
                    }
                    renderRows(data.rows || []);
                    clearWarning();
                    commitSummary('Tổng số: ' + (data.total || 0) + ' dòng | Từ ngày: ' + esc(data.from_date || '') + ' | Đến ngày: ' + esc(data.to_date || ''));
                })
                .catch(function (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    var msg = error && error.message ? error.message : 'Không tải được dữ liệu báo cáo.';
                    showLoadError(msg, previousHtml, previousSummary, previousLoaded);
                })
                .finally(function () {
                    activeLoadController = null;
                    clearControlsBusy();
                });
        }

        function exportCustomReport() {
            var validationMessage = validateFilters();
            if (validationMessage !== '') {
                if (!hasLoadedResult) {
                    setTableMessage(validationMessage);
                }
                commitSummary(invalidFilterSummary);
                setWarning(validationMessage);
                return;
            }

            var params = buildParams();
            params.set('api', 'custom_report_excel');
            var fromValue = String(fromInput.value || '').replace(/[^0-9]/g, '');
            var toValue = String(toInput.value || '').replace(/[^0-9]/g, '');
            var filename = 'bao_cao_dgx_theo_yeu_cau_' + fromValue + '_' + toValue + '.xlsx';
            var summaryBeforeExport = hasLoadedResult ? lastSummary : idleSummary;

            clearWarning();
            setMetaText('Đang tổng hợp file Excel...');
            setControlsBusy(exportBtn, 'Đang tải...');

            fetch('<?php echo $index_url; ?>?' + params.toString(), { cache: 'no-store' })
                .then(function (res) {
                    if (!res.ok) {
                        return res.text().then(function (message) {
                            throw new Error(message || 'Không tải được file Excel.');
                        });
                    }
                    return res.blob();
                })
                .then(function (blob) {
                    if (!/spreadsheetml|ms-excel|octet-stream/.test(blob.type)) {
                        throw new Error('Định dạng file không đúng.');
                    }
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
                .catch(function (error) {
                    setWarning(error && error.message ? error.message : 'Không tải được file Excel.');
                })
                .finally(function () {
                    commitSummary(summaryBeforeExport);
                    clearControlsBusy();
                });
        }

        openBtn.addEventListener('click', function () {
            controller.open();
            if (!hasLoadedResult) {
                resetView('Nhập điều kiện rồi nhấn Báo cáo.');
                return;
            }
            clearWarning();
            setMetaText(lastSummary);
        });
        filterBtn.addEventListener('click', loadCustomReport);
        exportBtn.addEventListener('click', exportCustomReport);
        [fromInput, toInput, posInput].forEach(function (input) {
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadCustomReport();
                }
            });
        });
        resetView('Nhập điều kiện rồi nhấn Báo cáo.');
    })();
})();
</script>
