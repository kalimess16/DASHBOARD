<?php
header('Content-Type: text/css; charset=UTF-8');

$page = $_GET['page'] ?? 'index';

if ($page === 'view') {
?>
body {
    margin: 0;
    font-family: Tahoma, Arial, sans-serif;
    background: #eef3f9;
}
.header {
    padding: 12px 16px;
    border-bottom: 1px solid #d4deea;
    background: #ffffff;
}
.header a {
    text-decoration: none;
    color: #0f3d66;
    margin-right: 10px;
}
.title {
    font-weight: 700;
    color: #1f2937;
}
.viewer {
    height: calc(100vh - 58px);
    background: #dce7f5;
}
iframe {
    width: 100%;
    height: 100%;
    border: 0;
}
.message {
    padding: 24px 16px;
    color: #374151;
}
<?php
return;
}
?>
:root {
    --bg-1: #ffe9f5;
    --bg-2: #ffd8ec;
    --surface: rgba(255, 255, 255, 0.92);
    --line: #efc8df;
    --line-soft: #f3d6e6;
    --head: #ffeef7;
    --text: #4e1738;
    --muted: #8e5c78;
    --brand: #9f0f65;
    --brand-strong: #7a0b50;
    --danger: #d64545;
    --shadow-lg: 0 22px 48px rgba(122, 11, 80, 0.15);
    --shadow-sm: 0 6px 18px rgba(122, 11, 80, 0.12);
}
* {
    box-sizing: border-box;
}
html,
body {
    height: 100%;
    margin: 0;
    padding: 0;
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
    margin: 0;
    background: #ffffff;
    border: 0;
    border-radius: 0;
    box-shadow: none;
    overflow: hidden;
}
.page-title {
    margin: 0;
    padding: 18px 22px;
    border-bottom: 1px solid var(--line-soft);
    font-size: 28px;
    letter-spacing: 0.2px;
    color: var(--brand);
    text-align: center;
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
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(244, 249, 255, 0.95) 100%);
}
.search-form > * {
    min-width: 0;
}
.search-form input,
.search-form select,
.search-form button {
    height: 42px;
    padding: 8px 12px;
    border: 1px solid #cad8ea;
    font-size: 16px;
    border-radius: 10px;
    transition: all 0.2s ease;
}
.search-form input {
    color: #17304d;
    background: #fff;
    box-shadow: inset 0 1px 2px rgba(4, 22, 43, 0.04);
}
.search-form select {
    color: #17304d;
    background: #fff;
}
.search-form input:focus {
    outline: none;
    border-color: #8eb7df;
    box-shadow: 0 0 0 3px rgba(13, 94, 168, 0.15);
}
.search-form select:focus {
    outline: none;
    border-color: #8eb7df;
    box-shadow: 0 0 0 3px rgba(13, 94, 168, 0.15);
}
.combo-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 34px;
    background-image:
        linear-gradient(45deg, transparent 50%, #4b6b8d 50%),
        linear-gradient(135deg, #4b6b8d 50%, transparent 50%);
    background-position:
        calc(100% - 18px) 18px,
        calc(100% - 12px) 18px;
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
}
.condition-mode {
    font-weight: 700;
    border-color: #e8a9cf;
    background-color: #ffeef7;
}
.field-keyword { flex: 2 1 360px; }
.field-date { flex: 1 1 160px; }
.field-mode { flex: 1 1 160px; }
.field-year { flex: 1 1 130px; }
.field-month { flex: 1 1 130px; }
.btn-search { flex: 0 0 160px; }
.search-form button {
    background: linear-gradient(180deg, #d51382 0%, #a90f6d 100%);
    color: #fff;
    border-color: #a90f6d;
    cursor: pointer;
    font-weight: 600;
    box-shadow: var(--shadow-sm);
}
.search-form button:hover {
    background: linear-gradient(180deg, #bf1175 0%, #8f0d5c 100%);
    transform: translateY(-1px);
}
.search-form .btn-clear-icon {
    width: 42px;
    height: 42px;
    border: 1px solid #e8b4b4;
    background: linear-gradient(180deg, #fff 0%, #f6f8fb 100%);
    color: var(--danger);
    cursor: pointer;
    font-size: 20px;
    line-height: 1;
    border-radius: 10px;
    font-weight: 700;
    box-shadow: none;
}
.search-form .btn-mark-icon {
    width: 42px;
    height: 42px;
    border: 1px solid #8cc79a;
    background: linear-gradient(180deg, #f5fff7 0%, #e5f7ea 100%);
    color: #1f8c3f;
    cursor: pointer;
    font-size: 22px;
    line-height: 1;
    border-radius: 10px;
    font-weight: 700;
    box-shadow: none;
}
.search-form .btn-mark-icon:hover {
    background: #eafaf0;
    border-color: #66b67c;
    transform: translateY(-1px);
}
.search-form .btn-mark-icon:active {
    transform: translateY(1px);
}
.search-form .btn-archive-list {
    width: 42px;
    height: 42px;
    border: 1px solid #8daed4;
    background: linear-gradient(180deg, #ffffff 0%, #e8f2ff 100%);
    color: #24527f;
    cursor: pointer;
    font-size: 20px;
    line-height: 1;
    border-radius: 10px;
    font-weight: 700;
    box-shadow: none;
}
.search-form .btn-archive-list:hover {
    background: #e2eefb;
    border-color: #6f97c2;
    transform: translateY(-1px);
}
.search-form .btn-archive-list:active {
    transform: translateY(1px);
}
.search-form .btn-reset-read-icon {
    width: 42px;
    height: 42px;
    border: 1px solid #b8a7cf;
    background: linear-gradient(180deg, #ffffff 0%, #f3effb 100%);
    color: #684a93;
    cursor: pointer;
    font-size: 20px;
    line-height: 1;
    border-radius: 10px;
    font-weight: 700;
    box-shadow: none;
}
.search-form .btn-reset-read-icon:hover {
    background: #f0ebfa;
    border-color: #9d84c2;
    transform: translateY(-1px);
}
.search-form .btn-reset-read-icon:active {
    transform: translateY(1px);
}
.search-form .btn-clear-icon:hover {
    background: #fff1f1;
    border-color: #e79a9a;
    transform: translateY(-1px);
}
.search-form .btn-clear-icon:active {
    transform: translateY(1px);
}
.table-wrap {
    overflow-x: auto;
    padding: 12px 16px 0;
}
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border: 2px solid #2ebd56;
    border-radius: 14px;
    overflow: hidden;
}
th, td {
    padding: 12px 14px;
    border-bottom: 1px solid #2ed15a;
    text-align: left;
    font-size: 16px;
    vertical-align: middle;
}
th + th,
td + td {
    border-left: 1px solid #2ed15a;
}
th {
    background: linear-gradient(180deg, #41b900 0%, #248f00 100%);
    color: #ffffff;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 0.35px;
    font-weight: 700;
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);
}
tbody tr:last-child td {
    border-bottom: 0;
}
tbody tr:nth-child(odd) td {
    background: #ffffff;
}
tbody tr:nth-child(even) td {
    background: #ffe4ef;
}
tbody tr:hover td {
    background: #c4eaa0;
}
a.title-link {
    text-decoration: none;
    color: #7d0d52;
    transition: color 0.2s ease;
}
a.title-link.unread {
    font-weight: 700;
    color: #6f0d48;
}
a.title-link.read {
    font-weight: 400;
    color: #546981;
}
a.title-link:hover {
    color: #b41172;
    text-decoration: underline;
    text-underline-offset: 3px;
}
.btn-receiver-popup {
    width: 30px;
    height: 28px;
    padding: 0;
    border: 1px solid #9fbad8;
    background: linear-gradient(180deg, #ffffff 0%, #edf5ff 100%);
    color: #214f7c;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
}
.btn-archive-popup {
    width: 30px;
    height: 28px;
    padding: 0;
    border: 1px solid #9bc78f;
    background: linear-gradient(180deg, #f9fff7 0%, #e8f7e3 100%);
    color: #1f6d34;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
    font-weight: 700;
}
.btn-archive-popup:hover {
    background: #e4f4de;
    border-color: #74a966;
}
.btn-receiver-popup:hover {
    background: #e7f0fb;
    border-color: #7fa4cc;
}
.col-receiver,
.col-receiver-cell {
    text-align: center;
    white-space: nowrap;
}
.action-group {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.receiver-empty {
    color: #7c8ca0;
}
.receiver-overlay {
    position: fixed;
    inset: 0;
    background: rgba(18, 33, 54, 0.46);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 16px;
}
.receiver-overlay[hidden] {
    display: none !important;
}
.receiver-dialog {
    width: min(700px, 100%);
    max-height: 85vh;
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #b9cde4;
    box-shadow: 0 16px 44px rgba(12, 39, 72, 0.25);
    display: flex;
    flex-direction: column;
}
.receiver-dialog h3 {
    margin: 0;
    padding: 16px 18px 10px;
    color: #133d68;
    border-bottom: 1px solid #d5e4f2;
}
.receiver-doc-label {
    margin: 0;
    padding: 10px 18px 0;
    color: #4a6480;
    font-size: 15px;
    font-weight: 600;
}
.receiver-list-wrap {
    padding: 14px 18px;
    overflow: auto;
}
.receiver-list-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #c8d9ea;
}
.receiver-list-table th,
.receiver-list-table td {
    padding: 10px 12px;
    border: 1px solid #c8d9ea;
    font-size: 15px;
}
.receiver-list-table th {
    background: #e8f2ff;
    color: #1c4b7a;
    text-transform: none;
    text-shadow: none;
}
.receiver-actions {
    display: flex;
    justify-content: flex-end;
    padding: 0 18px 16px;
}
.btn-receiver-close {
    min-width: 96px;
    height: 38px;
    border: 1px solid #879fbb;
    border-radius: 10px;
    background: linear-gradient(180deg, #f7fbff 0%, #e8f1fb 100%);
    color: #23466c;
    font-weight: 700;
    cursor: pointer;
}
.btn-receiver-close:hover {
    background: #dfeaf6;
}
.ls-overlay {
    position: fixed;
    inset: 0;
    background: rgba(17, 27, 42, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 16px;
}
.ls-overlay[hidden] {
    display: none !important;
}
.ls-dialog {
    width: min(860px, 100%);
    max-height: 88vh;
    background: #fff;
    border: 1px solid #b9cde4;
    border-radius: 14px;
    box-shadow: 0 16px 44px rgba(12, 39, 72, 0.25);
    display: flex;
    flex-direction: column;
}
.ls-drag-handle {
    margin: 0;
    padding: 16px 18px 10px;
    color: #133d68;
    border-bottom: 1px solid #d5e4f2;
    cursor: move;
    user-select: none;
}
.ls-content {
    padding: 12px 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ls-filter-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ls-label {
    font-weight: 700;
    color: #284d74;
}
.ls-type-select {
    flex: 1;
    height: 38px;
    border-radius: 8px;
    border: 1px solid #b7cce2;
    padding: 7px 10px;
    font-size: 14px;
    color: #193958;
    background: #fff;
}
.ls-doc-info {
    border: 1px solid #d4e2f1;
    border-radius: 10px;
    background: #f8fbff;
    padding: 10px 12px;
    font-size: 14px;
    color: #2a4561;
    line-height: 1.6;
}
.ls-doc-info span {
    color: #163f69;
}
.ls-status {
    min-height: 22px;
    font-size: 14px;
    color: #D42736;
    font-weight: 600;
}
.ls-status.error {
    color: #b53b3b;
}
.ls-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.ls-save-btn,
.ls-view-btn,
.ls-close-btn {
    height: 36px;
    border-radius: 9px;
    border: 1px solid #9ab2cb;
    padding: 0 12px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
}
.ls-save-btn {
    color: #ffffff;
    border-color: #2f8f3d;
    background: linear-gradient(180deg, #39ad4d 0%, #2a8338 100%);
}
.ls-save-btn:hover {
    background: #2f8f3d;
}
.ls-view-btn {
    color: #1f496f;
    background: linear-gradient(180deg, #ffffff 0%, #edf5ff 100%);
}
.ls-view-btn:hover {
    background: #e4effb;
}
.ls-close-btn {
    color: #5b2f5a;
    background: linear-gradient(180deg, #fff 0%, #f7ecf8 100%);
}
.ls-close-btn:hover {
    background: #f4e3f5;
}
.ls-list-wrap {
    overflow: auto;
    max-height: 40vh;
    border: 1px solid #c9d9ea;
    border-radius: 10px;
}
.ls-list-table {
    width: 100%;
    border-collapse: collapse;
    border: 0;
}
.ls-list-table th,
.ls-list-table td {
    padding: 9px 10px;
    border: 1px solid #d2e0ef;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ls-list-table .ls-col-title {
    white-space: normal;
    overflow: visible;
    text-overflow: clip;
    word-break: break-word;
    line-height: 1.35;
}
.ls-list-table th {
    background: #eaf3ff;
    color: #1c4b7a;
    text-transform: none;
    text-shadow: none;
}
.ls-empty {
    text-align: center;
    color: #7188a0;
}
.ls-view-link {
    color: #1b5e91;
    font-weight: 700;
    text-decoration: none;
}
.ls-view-link:hover {
    text-decoration: underline;
}
.empty {
    text-align: center;
    color: var(--muted);
    padding: 28px;
}
.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 16px 22px 20px;
}
.bottom-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 22px 18px;
}
.bottom-bar .pagination {
    padding: 0;
}
.reset-read-form {
    margin-left: auto;
}
.pagination a,
.pagination span {
    min-width: 38px;
    height: 38px;
    padding: 0 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #d2dfef;
    text-decoration: none;
    font-size: 16px;
    background: #f7fbff;
    border-radius: 10px;
}
.pagination a {
    color: #34506f;
    transition: all 0.2s ease;
}
.pagination a:hover {
    background: #e8f2ff;
    border-color: #b9d0ea;
}
.pagination span.current {
    background: linear-gradient(180deg, #d51382 0%, #a90f6d 100%);
    color: #fff;
    border-color: #a90f6d;
    box-shadow: var(--shadow-sm);
}
.pagination span.ellipsis {
    color: #73839a;
}
.pagination a.nav {
    font-size: 19px;
    line-height: 1;
}
.meta {
    padding: 0 22px 6px;
    color: var(--muted);
    font-size: 15px;
    font-weight: 600;
}
.quick-filter-wrap {
    padding: 0 22px 8px;
}
.table-quick-filter {
    width: min(460px, 100%);
    height: 38px;
    border: 1px solid #cad8ea;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 15px;
    color: #17304d;
    background: #fff;
}
.table-quick-filter:focus {
    outline: none;
    border-color: #8eb7df;
    box-shadow: 0 0 0 3px rgba(13, 94, 168, 0.15);
}
.field-docno {
    flex: 1 1 200px;
}
.ls-quick-filter {
    flex: 1;
    height: 38px;
    border-radius: 8px;
    border: 1px solid #b7cce2;
    padding: 7px 10px;
    font-size: 14px;
    color: #193958;
    background: #fff;
}
.ls-quick-filter:focus {
    outline: none;
    border-color: #8eb7df;
    box-shadow: 0 0 0 3px rgba(13, 94, 168, 0.15);
}
@media (max-width: 700px) {
    .container {
        min-width: 100%;
    }
    .container {
        width: 100%;
        margin: 0;
        border-radius: 0;
    }
    .page-title {
        font-size: 22px;
        padding: 14px 14px;
    }
    .search-form {
        flex-direction: column;
        align-items: stretch;
        padding: 12px 14px;
        gap: 10px;
    }
    .btn-search {
        flex: 0 0 auto;
        width: 100%;
    }
    .meta {
        padding: 0 14px 6px;
    }
    .quick-filter-wrap {
        padding: 0 14px 8px;
    }
    .table-quick-filter {
        width: 100%;
    }
    .table-wrap {
        padding: 8px 8px 0;
    }
    th, td {
        padding: 10px 10px;
        font-size: 15px;
    }
    .pagination {
        padding: 12px 14px 16px;
        gap: 6px;
    }
    .bottom-bar {
        padding: 10px 14px 14px;
    }
    .receiver-dialog {
        max-height: 90vh;
    }
    .receiver-list-wrap {
        padding: 10px 12px;
    }
    .receiver-dialog h3,
    .receiver-doc-label,
    .receiver-actions {
        padding-left: 12px;
        padding-right: 12px;
    }
    .ls-filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    .ls-actions {
        flex-direction: column;
    }
    .ls-save-btn,
    .ls-view-btn,
    .ls-close-btn {
        width: 100%;
    }
}

