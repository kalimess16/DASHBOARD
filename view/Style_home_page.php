<?php
header('Content-Type: text/css; charset=UTF-8');
?>
:root {
    --bg-1: #fff3d9;
    --bg-2: #ffe8b8;
    --line: #e9cc87;
    --line-soft: #f1deaf;
    --text: #4d3908;
    --muted: #7d6634;
    --brand: #b37400;
    --brand-2: #8f5b00;
    --accent: #d28a00;
    --accent-2: #9b6400;
    --card: #fffdf7;
    --shadow-sm: 0 6px 18px rgba(143, 91, 0, 0.14);
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
    position: relative;
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
.meta {
    padding: 0 22px 8px;
    color: var(--muted);
    font-size: 15px;
    font-weight: 600;
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
.search-form label { font-weight: 700; color: var(--muted); }
.search-form input,
.search-form button {
    height: 42px;
    border: 1px solid #d9c18c;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 16px;
}
.search-form input:focus {
    outline: none;
    border-color: #c9962a;
    box-shadow: 0 0 0 3px rgba(201, 150, 42, 0.15);
}
.search-form button {
    min-width: 150px;
    color: #fff;
    cursor: pointer;
    font-weight: 700;
    border-color: #9b6400;
    background: linear-gradient(180deg, #d28a00 0%, #a76b00 100%);
    box-shadow: var(--shadow-sm);
}
.search-form button:hover { background: linear-gradient(180deg, #bf7d00 0%, #8f5b00 100%); }
.error-box {
    margin: 0 22px 12px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #edb7b7;
    background: #fff2f2;
    color: #8a2323;
    font-size: 14px;
}
.loading {
    position: sticky;
    top: 0;
    margin: 0 22px 12px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fffaf0;
    border: 1px dashed #d9c18c;
    border-radius: 10px;
    color: #7d6634;
}
.spinner {
    width: 24px;
    height: 24px;
    border: 3px solid #f0d8a0;
    border-top-color: #c78600;
    border-radius: 50%;
    animation: spin 0.9s linear infinite;
}
.loading-text { font-weight: 700; }
@keyframes spin { to { transform: rotate(360deg); } }
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    padding: 0 22px 12px;
}
.card {
    background: var(--card);
    border: 1px solid var(--line-soft);
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: var(--shadow-sm);
}
.card .label { color: var(--muted); font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; }
.card .value { font-size: 20px; font-weight: 700; color: var(--brand-2); }
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
    padding: 0 22px 16px;
}
.panel {
    background: var(--card);
    border: 1px solid var(--line-soft);
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: var(--shadow-sm);
}
.panel h3 { margin: 0 0 12px; font-size: 16px; color: var(--brand-2); }
.bar-item { display: grid; grid-template-columns: 1fr 1.5fr auto; gap: 10px; align-items: center; margin-bottom: 8px; }
.bar-label { font-size: 13px; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bar-track { background: #f4ead2; border-radius: 10px; height: 10px; overflow: hidden; border: 1px solid #ead7aa; }
.bar-fill { display: block; height: 100%; background: linear-gradient(90deg, var(--accent), var(--accent-2)); }
.bar-value { font-size: 12px; color: var(--muted); text-align: right; min-width: 70px; }
.bar-list.empty { color: var(--muted); font-size: 13px; }
.source-box { display: flex; gap: 12px; flex-wrap: wrap; }
.source-pill { padding: 6px 12px; border-radius: 999px; background: #fff5dc; color: var(--brand-2); font-size: 12px; font-weight: 700; border: 1px solid #e2c78d; }
.table-wrap { overflow-x: auto; padding: 0 22px 24px; }
.table-wrap table { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0; border: 2px solid #cf9c2f; border-radius: 14px; overflow: hidden; }
.table-wrap th,
.table-wrap td { padding: 11px 12px; border-bottom: 1px solid #e2bb66; border-left: 1px solid #e2bb66; font-size: 14px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; }
.table-wrap th:first-child,
.table-wrap td:first-child { border-left: 0; }
.table-wrap th { background: linear-gradient(180deg, #c78600 0%, #9f6500 100%); color: #ffffff; font-size: 13px; text-transform: uppercase; letter-spacing: 0.3px; }
.table-wrap tbody tr:nth-child(odd) td { background: #ffffff; }
.table-wrap tbody tr:nth-child(even) td { background: #fff6e2; }
.table-wrap tbody tr:hover td { background: #ffeab7; }
.num { text-align: right; }
.empty { text-align: center; color: var(--muted); padding: 22px; }
@media (max-width: 900px) {
    .container { min-width: 100%; }
    .search-form { padding: 12px 14px; flex-direction: column; align-items: stretch; }
    .search-form button { width: 100%; }
    .meta { padding: 0 14px 8px; }
    .cards, .grid, .table-wrap { padding: 0 14px 14px; }
    .bar-item { grid-template-columns: 1fr; }
    .bar-value { text-align: left; }
}
