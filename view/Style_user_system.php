<?php
header('Content-Type: text/css; charset=UTF-8');
?>
:root {
    --bg-1: #eef7ff;
    --bg-2: #dfeeff;
    --surface: rgba(255, 255, 255, 0.92);
    --line: #c9dcee;
    --line-soft: #e4edf6;
    --text: #17324d;
    --muted: #58718d;
    --brand: #0c5a8a;
    --brand-2: #093b64;
    --accent: #1c8dd6;
    --accent-2: #16a085;
    --danger: #cc4a4a;
    --shadow-lg: 0 22px 48px rgba(9, 59, 100, 0.14);
    --shadow-sm: 0 8px 22px rgba(9, 59, 100, 0.10);
}
* { box-sizing: border-box; }
html, body {
    margin: 0;
    min-height: 100%;
    font-family: "Times New Roman", Times, serif;
    color: var(--text);
    background:
        radial-gradient(circle at 14% 10%, rgba(28, 141, 214, 0.12) 0%, rgba(28, 141, 214, 0) 35%),
        radial-gradient(circle at 82% 16%, rgba(22, 160, 133, 0.10) 0%, rgba(22, 160, 133, 0) 32%),
        linear-gradient(165deg, var(--bg-1) 0%, var(--bg-2) 100%);
}
.container {
    width: min(1500px, 100%);
    margin: 0 auto;
    padding: 18px;
}
.hero,
.panel,
.stat-card,
.notice {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: var(--shadow-sm);
    backdrop-filter: blur(8px);
}
.hero {
    padding: 22px 24px;
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
    margin-bottom: 16px;
}
.eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 999px;
    background: linear-gradient(180deg, #e9f6ff 0%, #d4ecff 100%);
    color: var(--brand);
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}
.hero h1 {
    margin: 10px 0 8px;
    font-size: 34px;
    color: var(--brand-2);
}
.hero p {
    margin: 0;
    max-width: 920px;
    color: var(--muted);
    font-size: 18px;
    line-height: 1.55;
}
.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
}
.hero-btn,
.tool-btn,
.btn-search,
.action-btn {
    border: 1px solid transparent;
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.hero-btn,
.tool-btn,
.action-btn {
    background: linear-gradient(180deg, #ffffff 0%, #ecf6ff 100%);
    color: var(--brand-2);
    border-color: #bfd4e6;
}
.hero-btn-primary,
.btn-search,
.action-btn-primary {
    color: #ffffff;
    background: linear-gradient(180deg, #1a92dd 0%, #0d5c93 100%);
    border-color: #0d5c93;
    box-shadow: var(--shadow-sm);
}
.hero-btn-ghost {
    background: linear-gradient(180deg, #f7fffb 0%, #e5fbf5 100%);
    border-color: #bce2d7;
    color: #0f6c59;
}
.hero-btn:disabled,
.tool-btn:disabled,
.btn-search:disabled,
.action-btn:disabled {
    opacity: 0.62;
    cursor: not-allowed;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 16px;
}
.stat-card {
    padding: 16px 18px;
}
.stat-label {
    display: block;
    font-size: 14px;
    color: var(--muted);
    margin-bottom: 8px;
    font-weight: 700;
}
.stat-card strong {
    display: block;
    font-size: 32px;
    color: var(--brand-2);
    line-height: 1;
}
.stat-card small {
    display: block;
    margin-top: 10px;
    color: var(--muted);
    font-size: 13px;
}
.search-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    padding: 14px 16px;
    margin-bottom: 14px;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
}
.search-bar input,
.search-bar select {
    height: 42px;
    border-radius: 12px;
    border: 1px solid #bfd4e6;
    padding: 8px 12px;
    background: #fff;
    color: var(--text);
    font-size: 15px;
}
.search-bar input:focus,
.search-bar select:focus {
    outline: none;
    border-color: #7fb9df;
    box-shadow: 0 0 0 3px rgba(28, 141, 214, 0.14);
}
.field-keyword {
    flex: 2 1 360px;
}
.field-status {
    flex: 0 0 220px;
}
.btn-search {
    flex: 0 0 170px;
}
.notice {
    padding: 12px 16px;
    margin-bottom: 14px;
    font-size: 15px;
    line-height: 1.45;
}
.notice-info {
    color: #15506f;
    border-color: #c9e1f1;
    background: linear-gradient(180deg, rgba(244, 251, 255, 0.96) 0%, rgba(232, 245, 255, 0.96) 100%);
}
.notice-error {
    color: #8b2f2f;
    border-color: #f0c2c2;
    background: linear-gradient(180deg, rgba(255, 247, 247, 0.96) 0%, rgba(255, 236, 236, 0.96) 100%);
}
.workspace {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(360px, 0.9fr);
    gap: 16px;
    align-items: start;
}
.panel {
    overflow: hidden;
}
.panel-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
    padding: 18px 18px 14px;
    border-bottom: 1px solid var(--line-soft);
}
.panel-head-tight {
    padding-bottom: 12px;
}
.panel-head h2 {
    margin: 0 0 6px;
    color: var(--brand-2);
    font-size: 24px;
}
.panel-head p {
    margin: 0;
    color: var(--muted);
    font-size: 14px;
}
.panel-tools {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.quick-filter {
    height: 40px;
    width: min(320px, 100%);
    border-radius: 12px;
    border: 1px solid #bfd4e6;
    padding: 8px 12px;
    font-size: 14px;
    background: #fff;
}
.table-wrap {
    overflow-x: auto;
    padding: 0 16px 16px;
}
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border: 1px solid var(--line);
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
}
th, td {
    padding: 12px 12px;
    border-bottom: 1px solid #d8e6f0;
    border-left: 1px solid #d8e6f0;
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
    background: linear-gradient(180deg, #137ec1 0%, #0d5c93 100%);
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.25px;
    font-size: 13px;
}
tbody tr:nth-child(odd) td {
    background: #ffffff;
}
tbody tr:nth-child(even) td {
    background: #f7fbff;
}
tbody tr:hover td {
    background: #e8f4ff;
}
.empty-state {
    text-align: center;
    color: var(--muted);
    padding: 26px 18px;
    line-height: 1.6;
}
.panel-form {
    padding-bottom: 16px;
}
.form-grid {
    padding: 16px 18px 0;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.form-grid label {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.form-grid .span-2 {
    grid-column: span 2;
}
.form-grid span {
    font-size: 13px;
    font-weight: 700;
    color: #355a7b;
}
.form-grid input,
.form-grid select,
.form-grid textarea {
    width: 100%;
    border: 1px solid #bfd4e6;
    border-radius: 12px;
    padding: 10px 12px;
    font-size: 15px;
    background: #fff;
    color: var(--text);
    font-family: inherit;
}
.form-grid textarea {
    resize: vertical;
    min-height: 110px;
}
.form-grid input:disabled,
.form-grid select:disabled,
.form-grid textarea:disabled {
    color: #6d8196;
    background: #f8fbfe;
}
.permission-box {
    margin: 16px 18px 0;
    padding: 14px;
    border: 1px solid #d3e4f1;
    border-radius: 14px;
    background: linear-gradient(180deg, rgba(247, 251, 255, 0.96) 0%, rgba(237, 246, 252, 0.96) 100%);
}
.permission-box h3 {
    margin: 0 0 10px;
    color: var(--brand-2);
    font-size: 18px;
}
.permission-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 12px;
}
.permission-list label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #355a7b;
    font-size: 14px;
    font-weight: 600;
}
.form-actions {
    display: flex;
    gap: 10px;
    padding: 16px 18px 0;
}
.action-btn {
    min-width: 120px;
}
.panel-footer {
    margin-top: 16px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.pagination {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    padding: 14px 18px 18px;
    border-top: 1px solid var(--line-soft);
    background: linear-gradient(180deg, rgba(248, 252, 255, 0.92) 0%, rgba(240, 247, 252, 0.92) 100%);
}
.pagination a,
.pagination span {
    min-width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 12px;
    border: 1px solid #bfd4e6;
    border-radius: 11px;
    text-decoration: none;
    font-size: 15px;
    font-weight: 700;
    line-height: 1;
    transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease, border-color 0.16s ease;
}
.pagination a {
    color: var(--brand-2);
    background: linear-gradient(180deg, #ffffff 0%, #edf6ff 100%);
    box-shadow: 0 4px 10px rgba(9, 59, 100, 0.06);
}
.pagination a:hover {
    transform: translateY(-1px);
    border-color: #8ec1e5;
    background: linear-gradient(180deg, #ffffff 0%, #e4f2ff 100%);
}
.pagination span.current {
    color: #ffffff;
    border-color: #0d5c93;
    background: linear-gradient(180deg, #1a92dd 0%, #0d5c93 100%);
    box-shadow: var(--shadow-sm);
}
.pagination span.nav {
    color: #7d97ad;
    background: #f7fbfe;
}
.pagination span.nav:hover {
    transform: none;
}
.footer-note {
    color: var(--muted);
    font-size: 15px;
    line-height: 1.5;
}
.footer-note strong {
    color: var(--brand-2);
}
.footer-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.footer-tags span {
    padding: 7px 10px;
    border-radius: 999px;
    border: 1px solid #bfd4e6;
    background: #fff;
    color: var(--brand);
    font-weight: 700;
    font-size: 13px;
}
@media (max-width: 1100px) {
    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .workspace {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 720px) {
    .container {
        padding: 12px;
    }
    .hero {
        flex-direction: column;
    }
    .hero h1 {
        font-size: 28px;
    }
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .search-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .field-keyword,
    .field-status,
    .btn-search,
    .quick-filter {
        width: 100%;
        flex: 0 0 auto;
    }
    .panel-head {
        flex-direction: column;
    }
    .panel-tools {
        width: 100%;
        justify-content: stretch;
    }
    .form-grid {
        grid-template-columns: 1fr;
    }
    .form-grid .span-2 {
        grid-column: span 1;
    }
    .permission-list {
        grid-template-columns: 1fr;
    }
    .panel-footer {
        flex-direction: column;
        align-items: flex-start;
    }
}
