<?php
header('Content-Type: text/css; charset=UTF-8');
?>
:root {
    --bg-1: #eaf6fb;
    --bg-2: #d7ecf7;
    --line: #b8d4e4;
    --line-soft: #d9eaf3;
    --text: #061a2f;
    --muted: #5e7485;
    --brand: #0b3f6f;
    --brand-2: #061a2f;
    --water: #0f6ea8;
    --water-cyan: #0ea5b7;
    --destiny-9: #4f46a8;
    --shadow-sm: 0 6px 18px rgba(6, 26, 47, 0.14);
}
* { box-sizing: border-box; }
html, body {
    margin: 0;
    padding: 0;
    min-height: 100%;
    text-size-adjust: 100%;
    -webkit-text-size-adjust: 100%;
    font-family: "Times New Roman", Times, serif;
    color: var(--text);
    background: #ffffff;
}
.container {
    width: 100%;
    min-width: 1100px;
    min-height: 100%;
}
.page-title {
    margin: 0;
    padding: 18px 22px;
    text-align: center;
    border-bottom: 1px solid var(--line-soft);
    color: var(--brand);
    font-size: 28px;
}
.page-title a {
    color: inherit;
    text-decoration: none;
}
.search-form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    padding: 16px 22px;
    border-bottom: 1px solid var(--line-soft);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(255, 248, 231, 0.95) 100%);
}
.search-form input,
.search-form button {
    height: 42px;
    border: 1px solid #b8d4e4;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 16px;
}
.field-keyword { flex: 2 1 360px; }
.field-date { flex: 1 1 170px; }
.btn-search {
    flex: 0 0 160px;
    color: #fff;
    cursor: pointer;
    font-weight: 700;
    border-color: #0b3f6f;
    background: linear-gradient(180deg, #0f6ea8 0%, #0b3f6f 100%);
    box-shadow: var(--shadow-sm);
}
.btn-search:hover {
    background: linear-gradient(180deg, #0b5e91 0%, #061a2f 100%);
}
.btn-fixed-list {
    flex: 0 0 220px;
    color: #6a4300;
    cursor: pointer;
    font-weight: 700;
    border-color: #9fc3d8;
    background: linear-gradient(180deg, #f4f9fc 0%, #e5f3fb 100%);
}
.btn-fixed-list:hover {
    background: linear-gradient(180deg, #eef7fc 0%, #d7ecf7 100%);
}
.btn-custom-report {
    flex: 0 0 230px;
    color: #fff;
    cursor: pointer;
    font-weight: 700;
    border-color: #4f46a8;
    background: linear-gradient(180deg, #4f46a8 0%, #312e81 100%);
    box-shadow: var(--shadow-sm);
}
.btn-custom-report:hover {
    background: linear-gradient(180deg, #4338ca 0%, #312e81 100%);
}
.search-form input:focus {
    outline: none;
    border-color: #0ea5b7;
    box-shadow: 0 0 0 3px rgba(14, 165, 183, 0.16);
}
.meta {
    padding: 0 22px 8px;
    color: var(--muted);
    font-size: 15px;
    font-weight: 600;
}
.quick-filter-wrap {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    padding: 0 22px 8px;
}
.table-quick-filter {
    width: min(380px, 100%);
    height: 38px;
    border: 1px solid #b8d4e4;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 15px;
    color: #061a2f;
    background: #fff;
}
.table-quick-filter:focus {
    outline: none;
    border-color: #0ea5b7;
    box-shadow: 0 0 0 3px rgba(14, 165, 183, 0.16);
}
.report-dgx-input,
.report-date-input,
.btn-report-list {
    height: 38px;
    border-radius: 10px;
    font-size: 15px;
    border: 1px solid #b8d4e4;
    padding: 8px 12px;
}
.report-dgx-input {
    width: min(320px, 100%);
}
.report-date-input {
    width: 170px;
    background: #fff;
}
.report-dgx-input:focus,
.report-date-input:focus {
    outline: none;
    border-color: #0ea5b7;
    box-shadow: 0 0 0 3px rgba(14, 165, 183, 0.16);
}
.btn-report-list {
    min-width: 120px;
    cursor: pointer;
    color: #fff;
    font-weight: 700;
    border-color: #0b3f6f;
    background: linear-gradient(180deg, #0f6ea8 0%, #061a2f 100%);
}
.btn-report-list:hover {
    background: linear-gradient(180deg, #0b5e91 0%, #07395f 100%);
}
.error-box {
    margin: 0 22px 12px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #edb7b7;
    background: #fff2f2;
    color: #8a2323;
    font-size: 14px;
}
.error-hint {
    margin-top: 6px;
    color: #995050;
}
.table-wrap {
    overflow-x: auto;
    padding: 10px 16px 0;
}
table {
    width: 100%;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
    border: 2px solid #7baec8;
    border-radius: 14px;
    overflow: hidden;
}
th, td {
    padding: 11px 12px;
    border-bottom: 1px solid #b8d4e4;
    border-left: 1px solid #b8d4e4;
    font-size: 15px;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
}
th:first-child,
td:first-child {
    border-left: 0;
}
th {
    background: linear-gradient(180deg, #0f6ea8 0%, #0b3f6f 100%);
    color: #ffffff;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
tbody tr:nth-child(odd) td { background: #ffffff; }
tbody tr:nth-child(even) td { background: #eef7fc; }
tbody tr:hover td { background: #d7ecf7; }
.col-stt,
.col-ma-pgd,
.col-ma-dgx,
.col-ngay-gdx,
.col-ngay {
    text-align: center;
    white-space: nowrap;
}
.col-ten-pos,
.col-ten-diem {
    white-space: nowrap;
}
.empty {
    text-align: center;
    color: var(--muted);
    padding: 22px;
}
.score-cell {
    font-weight: 700;
    color: #0b3f6f;
}
.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 14px 22px 18px;
}
.pagination a,
.pagination span {
    min-width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    border: 1px solid #b8d4e4;
    text-decoration: none;
    background: #f4f9fc;
}
.pagination a {
    color: #0b3f6f;
}
.pagination a:hover {
    background: #e5f3fb;
}
.pagination span.current {
    color: #fff;
    border-color: #0b3f6f;
    background: linear-gradient(180deg, #0f6ea8 0%, #0b3f6f 100%);
}
.pagination span.nav {
    color: #8f7a4c;
}
.fixed-overlay {
    position: fixed;
    inset: 0;
    background: rgba(20, 22, 28, 0.45);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 14px;
}
.fixed-overlay[hidden] {
    display: none !important;
}
.fixed-dialog {
    width: min(920px, 100%);
    max-height: 88vh;
    background: #fffdf8;
    border: 1px solid #d7bc80;
    border-radius: 14px;
    box-shadow: 0 16px 40px rgba(49, 35, 11, 0.25);
    display: flex;
    flex-direction: column;
}
.fixed-dialog h3 {
    margin: 0;
    padding: 14px 16px 10px;
    border-bottom: 1px solid #ecd8ac;
    color: #061a2f;
}
.fixed-drag-handle {
    cursor: move;
    user-select: none;
}
.fixed-filters {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 12px 16px 10px;
}
.fixed-control,
.fixed-action,
.fixed-close {
    height: 38px;
    border-radius: 9px;
    border: 1px solid #d2b374;
    padding: 8px 10px;
    font-size: 15px;
}
.fixed-control {
    background: #fff;
}
.fixed-action {
    min-width: 92px;
    cursor: pointer;
    color: #fff;
    font-weight: 700;
    border-color: #0b3f6f;
    background: linear-gradient(180deg, #0f6ea8 0%, #0b3f6f 100%);
}
.fixed-action:hover {
    background: linear-gradient(180deg, #0b5e91 0%, #061a2f 100%);
}
.fixed-control:disabled {
    color: #8a7340;
    cursor: not-allowed;
    background: #f4ecdc;
}
.fixed-action:disabled,
.fixed-close:disabled {
    opacity: 0.72;
    cursor: wait;
    box-shadow: none;
}
.fixed-action-secondary {
    color: #5a3b00;
    border-color: #9fc3d8;
    background: linear-gradient(180deg, #f4f9fc 0%, #e5f3fb 100%);
}
.fixed-action-secondary:hover {
    background: linear-gradient(180deg, #eef7fc 0%, #d7ecf7 100%);
}
.fixed-meta {
    padding: 0 16px 10px;
    color: #5e7485;
    font-size: 14px;
    font-weight: 600;
}
.fixed-warning {
    margin: 0 16px 10px;
    padding: 8px 10px;
    border: 1px solid #e3b5b5;
    border-radius: 8px;
    background: #fff3f3;
    color: #8a2323;
    font-size: 13px;
    font-weight: 600;
}
.fixed-table-wrap {
    padding: 0 16px 12px;
    overflow: auto;
}
.fixed-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    border: 1px solid #dcbc7a;
}
.fixed-table th,
.fixed-table td {
    border: 1px solid #e7d2a4;
    padding: 9px 10px;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.fixed-table th {
    background: #f7e7c1;
    color: #6d4600;
    text-transform: none;
}
.fixed-table tbody tr:nth-child(even) td {
    background: #fff6e5;
}
.report-dialog {
    width: min(96vw, 1460px);
}
.report-table {
    min-width: 1380px;
    table-layout: auto;
}
.report-table th,
.report-table td {
    overflow: visible;
    text-overflow: clip;
}
.report-table th:nth-child(n+4),
.report-table td:nth-child(n+4) {
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.report-table th:nth-child(2),
.report-table td:nth-child(2),
.report-table th:nth-child(3),
.report-table td:nth-child(3) {
    white-space: normal;
    word-break: break-word;
}
.report-filters .fixed-control {
    flex: 1 1 220px;
}
.report-filters .fixed-action {
    flex: 0 0 auto;
}
.custom-report-dialog {
    width: min(96vw, 1560px);
}
.custom-report-table {
    min-width: 1760px;
    table-layout: auto;
}
.custom-report-table th,
.custom-report-table td {
    overflow: visible;
    text-overflow: clip;
}
.custom-report-table th:nth-child(3),
.custom-report-table td:nth-child(3),
.custom-report-table th:nth-child(4),
.custom-report-table td:nth-child(4),
.custom-report-table th:nth-child(6),
.custom-report-table td:nth-child(6) {
    white-space: normal;
    word-break: break-word;
}
.custom-report-table th:nth-child(1),
.custom-report-table td:nth-child(1),
.custom-report-table th:nth-child(2),
.custom-report-table td:nth-child(2),
.custom-report-table th:nth-child(5),
.custom-report-table td:nth-child(5) {
    text-align: center;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.custom-report-table th:nth-child(n+7),
.custom-report-table td:nth-child(n+7) {
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.custom-report-filters .fixed-control {
    flex: 1 1 220px;
}
.custom-report-filters .fixed-action {
    flex: 0 0 auto;
}
.fixed-empty {
    text-align: center;
    color: #8d7340;
}
.fixed-actions {
    padding: 0 16px 14px;
    display: flex;
    justify-content: flex-end;
}
.fixed-close {
    min-width: 94px;
    cursor: pointer;
    color: #5a3b00;
    font-weight: 700;
    background: #fff4dc;
}
.fixed-close:hover {
    background: #ffe8ba;
}
@media (max-width: 700px) {
    .container {
        min-width: 100%;
    }
    .page-title {
        font-size: 22px;
        padding: 14px;
    }
    .search-form {
        padding: 12px 14px;
        flex-direction: column;
        align-items: stretch;
    }
    .btn-search {
        width: 100%;
        flex: 0 0 auto;
    }
    .btn-fixed-list {
        width: 100%;
        flex: 0 0 auto;
    }
    .btn-custom-report {
        width: 100%;
        flex: 0 0 auto;
    }
    .meta {
        padding: 0 14px 8px;
    }
    .quick-filter-wrap {
        padding: 0 14px 8px;
        flex-direction: column;
        align-items: stretch;
    }
    .table-quick-filter {
        width: 100%;
    }
    .report-dgx-input,
    .report-date-input,
    .btn-report-list {
        width: 100%;
    }
    .table-wrap {
        padding: 8px 8px 0;
    }
    th, td {
        font-size: 14px;
        padding: 9px 9px;
    }
    .pagination {
        padding: 10px 14px 14px;
        gap: 6px;
    }
    .fixed-filters {
        flex-direction: column;
        align-items: stretch;
    }
    .fixed-control,
    .fixed-action,
    .fixed-close {
        width: 100%;
    }
}
