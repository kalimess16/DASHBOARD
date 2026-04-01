<?php
header('Content-Type: text/css; charset=UTF-8');
?>
:root {
    --bg-top: #eef7fb;
    --bg-bottom: #f7efe6;
    --panel: rgba(247, 250, 248, 0.86);
    --panel-strong: rgba(255, 255, 255, 0.7);
    --line: rgba(22, 86, 106, 0.16);
    --line-strong: rgba(22, 86, 106, 0.28);
    --text: #16384a;
    --muted: #627586;
    --brand: #0f7b84;
    --brand-deep: #0e5468;
    --brand-soft: #d7edf1;
    --accent: #e88a61;
    --accent-soft: #fde2d4;
    --accent-2: #d2ab4f;
    --accent-2-soft: #faefc9;
    --green: #3f7b67;
    --green-soft: #dfeee6;
    --rose-soft: #f4dfe4;
    --violet-soft: #e7e1f4;
    --shadow: 0 24px 64px rgba(17, 67, 90, 0.12);
    --shadow-soft: 0 12px 28px rgba(22, 131, 164, 0.1);
}

* { box-sizing: border-box; }

html,
body {
    margin: 0;
    padding: 0;
    min-height: 100%;
    text-size-adjust: 100%;
    -webkit-text-size-adjust: 100%;
    font-family: "Times New Roman", Times, serif;
    color: var(--text);
    background:
        radial-gradient(circle at 12% 12%, rgba(232, 138, 97, 0.16), transparent 24%),
        radial-gradient(circle at 84% 10%, rgba(15, 123, 132, 0.14), transparent 26%),
        radial-gradient(circle at 80% 84%, rgba(210, 171, 79, 0.14), transparent 20%),
        radial-gradient(circle at 16% 78%, rgba(63, 123, 103, 0.12), transparent 24%),
        linear-gradient(180deg, var(--bg-top) 0%, var(--bg-bottom) 100%);
}

body.modal-open { overflow: hidden; }

.page-shell {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 14px 18px 28px;
}

.top-layout {
    display: grid;
    grid-template-columns: minmax(680px, 1.08fr) minmax(420px, 0.92fr);
    gap: 16px;
    align-items: start;
}

.top-stack {
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-width: 0;
}

.top-stack > .search-form,
.top-stack > .error-box,
.top-stack > .loading,
.top-stack > .panel-source-toolbar {
    margin-top: 0;
}

.hero,
.panel,
.card,
.search-form,
.loading,
.error-box,
.detail-dialog {
    position: relative;
    z-index: 1;
    width: min(1020px, 100%);
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 22px;
    border: 1px solid rgba(22, 131, 164, 0.16);
    border-radius: 26px;
    background: linear-gradient(180deg, rgba(255, 250, 244, 0.98) 0%, rgba(234, 247, 250, 0.96) 100%);
    box-shadow: 0 30px 80px rgba(8, 34, 46, 0.24);
}

.top-layout > .scheme-panel {
    margin-top: 0;
    align-self: stretch;
}

.hero {
    position: relative;
    overflow: hidden;
    display: block;
    padding: 24px 28px;
    border: 1px solid var(--line);
    border-radius: 28px;
    background: linear-gradient(135deg, rgba(251, 252, 255, 0.86) 0%, rgba(224, 241, 245, 0.92) 42%, rgba(248, 235, 221, 0.9) 100%);
    box-shadow: var(--shadow);
}

.hero::before,
.hero::after {
    content: "";
    position: absolute;
    border-radius: 999px;
    pointer-events: none;
}

.hero::before {
    top: -120px;
    right: -70px;
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(107, 224, 217, 0.38) 0%, rgba(107, 224, 217, 0) 72%);
}

.hero::after {
    bottom: -140px;
    left: -70px;
    width: 270px;
    height: 270px;
    background: radial-gradient(circle, rgba(22, 131, 164, 0.18) 0%, rgba(22, 131, 164, 0) 72%);
}

.hero-copy {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 18px;
    max-width: 760px;
}

.page-title {
    margin: 0;
    max-width: 720px;
    font-size: clamp(36px, 3.4vw, 56px);
    line-height: 1.02;
    letter-spacing: -0.03em;
}

.page-title a {
    display: inline-flex;
    flex-direction: column;
    gap: 2px;
    color: var(--brand-deep);
    text-decoration: none;
}

.page-title__line {
    display: block;
    text-wrap: balance;
}

.hero-note {
    margin: 14px 0 0;
    font-size: 16px;
    line-height: 1.65;
    color: var(--muted);
}

.hero-meta {
    width: min(100%, 560px);
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 0;
}

.meta-chip {
    min-width: 0;
    min-height: 72px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 14px 16px;
    border-radius: 18px;
    border: 1px solid rgba(22, 131, 164, 0.14);
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.82) 0%, rgba(243, 247, 244, 0.9) 100%);
    color: var(--muted);
    font-size: 14px;
    box-shadow: var(--shadow-soft);
}

.meta-chip strong {
    display: block;
    margin-top: 6px;
    font-size: 18px;
    color: var(--brand-deep);
}

.search-form {
    margin-top: 16px;
    display: grid;
    grid-template-columns: auto minmax(180px, 220px) auto minmax(180px, 220px) minmax(200px, 1fr);
    gap: 12px;
    align-items: center;
    padding: 16px 18px;
    border: 1px solid var(--line);
    border-radius: 22px;
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.76) 0%, rgba(240, 246, 243, 0.9) 100%);
    box-shadow: var(--shadow-soft);
}

.search-form label {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--muted);
}

.search-form input,
.search-form button,
.source-reset,
.source-option,
.summary-item,
.scheme-legend__item,
.modal-close {
    font-family: inherit;
}

.search-form input,
.search-form button {
    height: 46px;
    border-radius: 14px;
    border: 1px solid rgba(22, 131, 164, 0.18);
    font-size: 15px;
}

.search-form input {
    padding: 0 14px;
    background: rgba(255, 255, 255, 0.96);
    color: var(--text);
}

.search-form input:focus,
.source-option:focus,
.source-reset:focus,
.summary-item:focus,
.scheme-legend__item:focus,
.modal-close:focus {
    outline: none;
    border-color: rgba(44, 195, 207, 0.76);
    box-shadow: 0 0 0 4px rgba(107, 224, 217, 0.16);
}

.search-form button,
.source-reset,
.modal-close {
    border: 0;
    cursor: pointer;
    color: #ffffff;
    font-weight: 700;
    letter-spacing: 0.04em;
    background: linear-gradient(135deg, var(--brand) 0%, var(--accent) 54%, var(--accent-2) 100%);
    box-shadow: 0 14px 28px rgba(22, 131, 164, 0.2);
}

.search-form button:hover,
.source-reset:hover,
.modal-close:hover {
    filter: brightness(1.03);
}

.error-box,
.loading,
.panel,
.cards,
.split-layout,
.panel-source-toolbar { margin-top: 16px; }

.error-box {
    padding: 14px 16px;
    border-radius: 18px;
    border: 1px solid rgba(255, 140, 112, 0.32);
    background: rgba(255, 242, 239, 0.94);
    color: #95462d;
    font-size: 14px;
}

.loading {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
    padding: 18px 20px;
    border-radius: 24px;
    border: 1px solid rgba(22, 131, 164, 0.16);
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(232, 245, 248, 0.92) 56%, rgba(250, 240, 228, 0.9) 100%);
    color: var(--muted);
    box-shadow: var(--shadow-soft);
}

.loading-badge {
    align-self: flex-start;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(22, 131, 164, 0.1);
    color: var(--brand-deep);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.loading-head {
    display: flex;
    align-items: center;
    gap: 14px;
}

.loading-copy {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.loading-title {
    font-size: 22px;
    line-height: 1.15;
    color: var(--brand-deep);
}

.loading-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.loading-card {
    position: relative;
    height: 88px;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(22, 131, 164, 0.1);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.92) 0%, rgba(238, 248, 250, 0.9) 100%);
}

.loading-card--wide {
    grid-column: span 2;
}

.loading-card::before,
.loading-card::after {
    content: "";
    position: absolute;
    left: 16px;
    right: 16px;
    border-radius: 999px;
    background: rgba(22, 131, 164, 0.1);
}

.loading-card::before {
    top: 22px;
    height: 12px;
}

.loading-card::after {
    top: 46px;
    right: 40%;
    height: 10px;
}

.spinner {
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    border: 4px solid rgba(107, 224, 217, 0.22);
    border-top-color: var(--brand);
    border-right-color: rgba(232, 138, 97, 0.62);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.loading-text {
    font-size: 14px;
    line-height: 1.6;
    font-weight: 700;
}
@keyframes spin { to { transform: rotate(360deg); } }

.panel,
.card {
    border: 1px solid var(--line);
    border-radius: 22px;
    background: linear-gradient(150deg, rgba(255, 255, 255, 0.76) 0%, rgba(244, 249, 246, 0.92) 100%);
    box-shadow: var(--shadow-soft);
}

.panel { padding: 18px; }
.card { padding: 16px 16px 18px; min-width: 0; }
.cards {
    display: grid;
    grid-template-columns: repeat(6, minmax(165px, 1fr));
    gap: 12px;
}

.cards .card:nth-child(2) { background: linear-gradient(160deg, rgba(255, 255, 255, 0.82) 0%, rgba(253, 236, 225, 0.94) 100%); }
.cards .card:nth-child(3) { background: linear-gradient(160deg, rgba(255, 255, 255, 0.82) 0%, rgba(228, 241, 246, 0.94) 100%); }
.cards .card:nth-child(4) { background: linear-gradient(160deg, rgba(255, 255, 255, 0.82) 0%, rgba(250, 242, 210, 0.94) 100%); }
.cards .card:nth-child(5) { background: linear-gradient(160deg, rgba(255, 255, 255, 0.82) 0%, rgba(232, 246, 239, 0.94) 100%); }
.cards .card:nth-child(6) { background: linear-gradient(160deg, rgba(255, 255, 255, 0.82) 0%, rgba(240, 232, 246, 0.94) 100%); }

.panel-source-toolbar {
    position: relative;
    overflow: hidden;
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.78) 0%, rgba(224, 239, 243, 0.88) 48%, rgba(249, 239, 227, 0.88) 100%);
}

.panel-source-toolbar::before {
    content: "";
    position: absolute;
    inset: auto -80px -100px auto;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(232, 138, 97, 0.16) 0%, rgba(232, 138, 97, 0) 72%);
    pointer-events: none;
}

.scheme-panel {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    background: linear-gradient(155deg, rgba(255, 248, 239, 0.84) 0%, rgba(231, 244, 248, 0.92) 100%);
}

.list-panel--pos {
    background: linear-gradient(160deg, rgba(231, 245, 250, 0.88) 0%, rgba(255, 255, 255, 0.78) 100%);
    border-color: rgba(15, 123, 132, 0.18);
}

.list-panel--xa {
    background: linear-gradient(160deg, rgba(227, 242, 233, 0.9) 0%, rgba(255, 255, 255, 0.78) 100%);
    border-color: rgba(63, 123, 103, 0.18);
}

.accent-card {
    background: linear-gradient(145deg, rgba(15, 123, 132, 0.92) 0%, rgba(232, 138, 97, 0.86) 100%);
    border-color: rgba(15, 123, 132, 0.2);
}

.card .label {
    display: block;
    margin-bottom: 8px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--muted);
}

.accent-card .label { color: rgba(255, 255, 255, 0.82); }
.card .value {
    display: block;
    font-size: clamp(18px, 1.8vw, 24px);
    line-height: 1.2;
    color: var(--brand-deep);
    overflow-wrap: anywhere;
    font-variant-numeric: tabular-nums;
}
.accent-card .value { color: #ffffff; }

.panel-head,
.panel-head--compact {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
}
.panel-head { margin-bottom: 12px; }
.panel-head h2 {
    margin: 0;
    font-size: 22px;
    line-height: 1.1;
    color: var(--brand-deep);
}

.panel-head--chart {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
}
.panel-note,
.panel-note-inline {
    margin: 0;
    font-size: 15px;
    line-height: 1.6;
    color: var(--muted);
}

.panel-note { margin-bottom: 14px; }

.panel-note--compact {
    margin-bottom: 10px;
    max-width: 760px;
    font-size: 13px;
    line-height: 1.45;
}

.panel-count {
    padding: 8px 12px;
    border-radius: 999px;
    background: linear-gradient(145deg, rgba(215, 237, 241, 0.9) 0%, rgba(250, 239, 201, 0.76) 100%);
    color: var(--brand-deep);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.panel-count--source {
    padding: 7px 11px;
    font-size: 11px;
}

.source-head-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}

.source-reset {
    min-width: 112px;
    height: 38px;
    padding: 0 14px;
    border-radius: 999px;
    font-size: 12px;
    letter-spacing: 0.08em;
}

.source-reset:disabled {
    cursor: default;
    opacity: 0.55;
    filter: grayscale(0.1);
}

.source-toolbar {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.source-option {
    width: 100%;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid rgba(22, 131, 164, 0.14);
    border-radius: 18px;
    text-align: left;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.62);
    color: var(--text);
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
}

.source-option:hover,
.source-option.is-active {
    transform: translateY(-1px);
    border-color: rgba(22, 131, 164, 0.28);
    box-shadow: 0 12px 24px rgba(22, 131, 164, 0.12);
}

.source-option[data-source="ALL"] { background: linear-gradient(145deg, rgba(255, 255, 255, 0.72) 0%, rgba(253, 236, 225, 0.84) 100%); }
.source-option[data-source="TW"] { background: linear-gradient(145deg, rgba(255, 255, 255, 0.72) 0%, rgba(227, 244, 248, 0.86) 100%); }
.source-option[data-source="DP"] { background: linear-gradient(145deg, rgba(255, 255, 255, 0.72) 0%, rgba(227, 242, 233, 0.88) 100%); }
.source-option.is-active[data-source="ALL"] { background: linear-gradient(145deg, rgba(232, 138, 97, 0.92) 0%, rgba(210, 171, 79, 0.88) 100%); color: #ffffff; }
.source-option.is-active[data-source="TW"] { background: linear-gradient(145deg, rgba(15, 123, 132, 0.92) 0%, rgba(73, 164, 189, 0.88) 100%); color: #ffffff; }
.source-option.is-active[data-source="DP"] { background: linear-gradient(145deg, rgba(63, 123, 103, 0.92) 0%, rgba(99, 168, 127, 0.88) 100%); color: #ffffff; }

.source-option__check {
    position: relative;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 1px solid rgba(22, 131, 164, 0.22);
    background: rgba(255, 255, 255, 0.76);
    flex: 0 0 20px;
}

.source-option__check::after {
    content: "✓";
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    font-size: 11px;
    font-weight: 700;
    color: #ffffff;
    opacity: 0;
    transform: scale(0.7);
    transition: opacity 0.16s ease, transform 0.16s ease;
}

.source-option__body {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.source-option__eyebrow,
.source-option small {
    display: block;
    letter-spacing: 0.08em;
    font-weight: 700;
}

.source-option__eyebrow {
    font-size: 12px;
    text-transform: uppercase;
}

.source-option strong {
    display: block;
    margin: 0;
    font-size: 15px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-variant-numeric: tabular-nums;
    color: var(--brand-deep);
}

.source-option small {
    font-size: 10px;
    color: var(--muted);
}

.source-option.is-active strong,
.source-option.is-active small,
.source-option.is-active .source-option__eyebrow {
    color: #ffffff;
}

.source-option.is-active .source-option__check {
    border-color: rgba(255, 255, 255, 0.42);
    background: rgba(255, 255, 255, 0.18);
}

.source-option.is-active .source-option__check::after {
    opacity: 1;
    transform: scale(1);
}

.split-layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.scheme-chart {
    --scheme-active-color: rgba(15, 123, 132, 0.18);
    display: grid;
    grid-template-columns: minmax(240px, 0.8fr) minmax(0, 1.2fr);
    gap: 18px;
    align-items: center;
    flex: 1 1 auto;
}

.scheme-chart__visual {
    position: relative;
    width: min(290px, 100%);
    aspect-ratio: 1;
    margin: 0 auto;
    display: grid;
    place-items: center;
}

.scheme-chart__visual::before {
    content: "";
    position: absolute;
    inset: 12%;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.92) 0%, rgba(255, 255, 255, 0) 68%);
    pointer-events: none;
}

.scheme-chart__svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 16px 22px rgba(16, 84, 104, 0.08));
}

.scheme-slice {
    cursor: pointer;
    transform-origin: 120px 120px;
    transition: transform 0.18s ease, opacity 0.18s ease, filter 0.18s ease;
}

.scheme-slice:hover,
.scheme-slice.is-active {
    transform: scale(1.04);
    filter: drop-shadow(0 10px 14px rgba(15, 94, 121, 0.16));
}

.scheme-spotlight {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 140px;
    height: 140px;
    padding: 12px 10px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    background: linear-gradient(160deg, rgba(255, 255, 255, 0.96) 0%, rgba(247, 250, 248, 0.92) 100%);
    border: 1px solid var(--scheme-active-color);
    text-align: center;
    box-shadow: 0 14px 26px rgba(22, 131, 164, 0.12);
}

.scheme-spotlight__eyebrow,
.scheme-spotlight__value,
.scheme-legend__copy small {
    display: block;
    font-variant-numeric: tabular-nums;
}

.scheme-spotlight__eyebrow {
    font-size: 10px;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--muted);
}

.scheme-spotlight__name {
    display: -webkit-box;
    margin: 0;
    font-size: 14px;
    line-height: 1.14;
    max-width: 100%;
    color: var(--brand-deep);
    overflow: hidden;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.scheme-spotlight__value {
    max-width: 100%;
    font-size: 15px;
    line-height: 1.12;
    font-weight: 700;
    color: var(--brand);
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.scheme-chart__legend,
.summary-list,
.detail-table-wrap {
    overflow: auto;
    padding-right: 4px;
}

.scheme-chart__legend { max-height: 460px; }
.summary-list { max-height: 600px; }
.summary-list { display: flex; flex-direction: column; gap: 12px; max-height: 600px; }

.scheme-chart__legend::-webkit-scrollbar,
.summary-list::-webkit-scrollbar,
.detail-table-wrap::-webkit-scrollbar { width: 10px; height: 10px; }
.scheme-chart__legend::-webkit-scrollbar-thumb,
.summary-list::-webkit-scrollbar-thumb,
.detail-table-wrap::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: rgba(22, 131, 164, 0.22);
}

.scheme-legend__item,
.summary-item {
    width: 100%;
    border: 1px solid rgba(22, 131, 164, 0.14);
    border-radius: 18px;
    cursor: pointer;
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.8) 0%, rgba(244, 248, 250, 0.9) 100%);
    color: inherit;
}

.scheme-legend__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    text-align: left;
}

.scheme-legend__item:hover,
.scheme-legend__item.is-active,
.summary-item:hover {
    border-color: rgba(22, 131, 164, 0.32);
    box-shadow: 0 12px 24px rgba(22, 131, 164, 0.12);
}

.scheme-legend__dot {
    flex: 0 0 12px;
    width: 12px;
    height: 12px;
    border-radius: 999px;
}

.scheme-legend__copy { min-width: 0; }
.scheme-legend__copy strong {
    display: block;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.summary-item { padding: 14px; text-align: left; transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease; }
.summary-item:hover { transform: translateY(-2px); }
.summary-item--pos { background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(227, 249, 255, 0.88) 100%); }
.summary-item--xa { background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(228, 246, 236, 0.92) 100%); }
.summary-item__top,
.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}
.summary-item__top { margin-bottom: 10px; }
.summary-item__code,
.summary-item__badge,
.detail-stat-label,
.empty-row,
.empty-box {
    font-weight: 700;
}
.summary-item__code {
    font-size: 14px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
.summary-item--pos .summary-item__code { color: var(--brand-deep); }
.summary-item--xa .summary-item__code { color: var(--green); }
.summary-item__badge {
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.summary-item--pos .summary-item__badge { background: rgba(22, 131, 164, 0.12); color: var(--brand); }
.summary-item--xa .summary-item__badge { background: rgba(29, 122, 75, 0.12); color: var(--green); }
.summary-item__name { display: block; font-size: 17px; font-weight: 700; color: var(--text); }
.summary-item__metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 12px; }
.summary-item__metric {
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.72);
    border: 1px solid rgba(22, 131, 164, 0.1);
}
.summary-item__metric strong {
    display: block;
    margin-bottom: 4px;
    font-size: 16px;
    color: var(--brand-deep);
    overflow-wrap: anywhere;
    font-variant-numeric: tabular-nums;
}
.summary-item__metric small {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--muted);
}

.empty-box,
.empty-row {
    text-align: center;
    color: var(--muted);
}
.empty-box {
    padding: 26px 16px;
    border: 1px dashed rgba(22, 131, 164, 0.22);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.68);
}

.detail-modal {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.18s ease, visibility 0.18s ease;
}
.detail-modal.is-open { opacity: 1; visibility: visible; }
.detail-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(8, 34, 46, 0.42);
    backdrop-filter: blur(6px);
}
.detail-dialog {
    position: relative;
    z-index: 1;
    width: min(1020px, 100%);
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 22px;
    border: 1px solid rgba(22, 131, 164, 0.16);
    border-radius: 26px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(238, 251, 255, 0.96) 100%);
    box-shadow: 0 30px 80px rgba(8, 34, 46, 0.24);
    transform: translateY(18px) scale(0.98);
    transition: transform 0.18s ease;
}
.detail-modal.is-open .detail-dialog { transform: translateY(0) scale(1); }
.detail-header h2 { margin: 0; font-size: 30px; color: var(--brand-deep); }
.detail-subtitle { margin: 8px 0 0; color: var(--muted); }
.modal-close { height: 42px; padding: 0 18px; border-radius: 14px; }
.detail-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
.detail-stat {
    min-width: 0;
    padding: 14px;
    border-radius: 16px;
    border: 1px solid rgba(22, 131, 164, 0.12);
    background: rgba(255, 255, 255, 0.88);
}
.detail-stat-label {
    display: block;
    margin-bottom: 8px;
    font-size: 11px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--muted);
}
.detail-stat strong {
    display: block;
    font-size: clamp(18px, 1.55vw, 24px);
    color: var(--brand-deep);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-variant-numeric: tabular-nums;
}
.detail-table-wrap {
    border-radius: 20px;
    border: 1px solid rgba(22, 131, 164, 0.14);
    background: rgba(255, 255, 255, 0.86);
}
.detail-table { width: 100%; border-collapse: collapse; min-width: 620px; }
.detail-table th,
.detail-table td { padding: 14px 16px; border-bottom: 1px solid rgba(22, 131, 164, 0.12); font-size: 14px; }
.detail-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: linear-gradient(135deg, var(--brand-deep) 0%, var(--brand) 100%);
    color: #ffffff;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    font-size: 12px;
}
.detail-table tbody tr:nth-child(odd) td { background: rgba(255, 255, 255, 0.84); }
.detail-table tbody tr:nth-child(even) td { background: rgba(238, 251, 255, 0.94); }
.detail-table tbody tr:hover td { background: rgba(220, 248, 255, 0.96); }
.num { text-align: right; }

@media (max-width: 1240px) {
    .top-layout,
    .split-layout,
    .scheme-chart {
        grid-template-columns: 1fr;
    }

    .cards {
        grid-template-columns: repeat(3, minmax(180px, 1fr));
    }

    .hero-meta {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        width: min(100%, 620px);
    }
}

@media (max-width: 900px) {
    .page-shell {
        padding: 14px 12px 24px;
    }

    .search-form,
    .source-toolbar,
    .detail-stats,
    .summary-item__metrics,
    .hero-meta,
    .scheme-chart {
        grid-template-columns: 1fr;
    }

    .cards {
        grid-template-columns: repeat(2, minmax(160px, 1fr));
    }

    .detail-dialog {
        padding: 18px;
    }

    .detail-header,
    .source-head-actions,
    .panel-head,
    .panel-head--compact,
    .loading-head {
        flex-direction: column;
    }

    .loading-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .loading-card--wide {
        grid-column: span 2;
    }

    .source-reset,
    .modal-close {
        width: 100%;
    }
}

@media (max-width: 560px) {
    .cards { grid-template-columns: 1fr; }
    .hero,
    .panel,
    .card,
    .search-form,
    .detail-dialog { border-radius: 20px; }
    .page-title { font-size: 30px; }
    .detail-modal { padding: 12px; }
    .loading {
        padding: 16px;
    }
    .loading-title {
        font-size: 18px;
    }
    .loading-grid {
        grid-template-columns: 1fr;
    }
    .loading-card--wide {
        grid-column: span 1;
    }
}
