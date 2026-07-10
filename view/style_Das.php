<?php
header('Content-Type: text/css; charset=UTF-8');
?>
:root {
    --bg: #eaf6fb;
    --panel: #ffffff;
    --line: #b8d4e4;
    --line-strong: #7baec8;
    --text: #061a2f;
    --muted: #5e7485;
    --brand: #0b3f6f;
    --brand-dark: #061a2f;
    --brand-mid: #0f6ea8;
    --brand-soft: #e5f3fb;
    --water-cyan: #0ea5b7;
    --destiny-9: #4f46a8;
    --danger: #b42318;
    --shadow: 0 10px 26px rgba(6, 26, 47, 0.18);
    --topbar-height: 94px;
}

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    min-height: 100%;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
    color: var(--text);
    background: var(--bg);
}

button,
input,
select,
textarea {
    font: inherit;
}

button {
    color: inherit;
}

.app-shell {
    min-height: 100dvh;
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
}

.topbar {
    position: sticky;
    top: 0;
    z-index: 50;
    display: grid;
    grid-template-rows: 54px 40px;
    background: linear-gradient(90deg, var(--brand-dark) 0%, var(--brand) 52%, var(--brand-mid) 100%);
    color: #ffffff;
    box-shadow: var(--shadow);
}

.titlebar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-width: 0;
    padding: 8px 14px 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.16);
}

.brand-block {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    padding: 0;
    border: 0;
    background: transparent;
    cursor: pointer;
    text-align: left;
}

.brand-block:hover .brand-mark {
    transform: translateY(-1px);
}

.brand-mark {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    display: block;
    border-radius: 10px;
    overflow: hidden;
    background: transparent;
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.16);
    transition: transform 0.16s ease;
}

.brand-mark img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.brand-text {
    min-width: 0;
    display: grid;
    gap: 1px;
}

.brand-text strong {
    font-size: 18px;
    line-height: 1.12;
}

.brand-text small {
    color: rgba(255, 255, 255, 0.78);
    font-size: 12px;
}

.creator-chip {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: 7px 10px;
    border: 1px solid rgba(255, 255, 255, 0.24);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.9);
    font-size: 12px;
}

.main-nav {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 8px;
    min-width: 0;
    padding: 5px 14px;
    background: rgba(255, 255, 255, 0.08);
}

.nav-group {
    position: relative;
}

.nav-group__toggle {
    min-height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 12px;
    border: 1px solid rgba(255, 255, 255, 0.24);
    border-radius: 7px;
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    cursor: pointer;
    font-weight: 700;
}

.nav-group__toggle:hover,
.nav-group.is-open .nav-group__toggle {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.38);
}

.nav-group__caret {
    width: 8px;
    height: 8px;
    border-right: 2px solid currentColor;
    border-bottom: 2px solid currentColor;
    transform: rotate(45deg) translateY(-1px);
    transition: transform 0.16s ease;
}

.nav-group.is-open .nav-group__caret {
    transform: rotate(225deg) translateY(-1px);
}

.nav-group__panel {
    position: absolute;
    top: calc(100% + 7px);
    left: 0;
    min-width: 230px;
    display: none;
    padding: 8px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--panel);
    color: var(--text);
    box-shadow: var(--shadow);
}

.nav-group.is-open .nav-group__panel {
    display: grid;
    gap: 6px;
}

.nav-item {
    width: 100%;
    min-height: 38px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 10px;
    border: 1px solid transparent;
    border-radius: 6px;
    background: transparent;
    cursor: pointer;
    text-align: left;
}

.nav-item:hover,
.nav-item.is-active {
    border-color: var(--line-strong);
    background: linear-gradient(180deg, var(--brand-soft) 0%, #f2f8fc 100%);
    color: var(--brand-dark);
}

.nav-item__label {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.menu-badge {
    min-width: 24px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 7px;
    border-radius: 999px;
    background: var(--danger);
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
}

.content-shell,
.workspace,
.frame-stack {
    min-width: 0;
    min-height: 0;
}

.workspace {
    padding: 0;
}

.frame-stack {
    position: relative;
    width: 100%;
    height: calc(100dvh - var(--topbar-height));
    background: #ffffff;
}

.content-frame {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: none;
    border: 0;
    background: #ffffff;
}

.content-frame.is-active {
    display: block;
}

@media (max-width: 900px) {
    :root {
    --bg: #eaf6fb;
    --panel: #ffffff;
    --line: #b8d4e4;
    --line-strong: #7baec8;
    --text: #061a2f;
    --muted: #5e7485;
    --brand: #0b3f6f;
    --brand-dark: #061a2f;
    --brand-mid: #0f6ea8;
    --brand-soft: #e5f3fb;
    --water-cyan: #0ea5b7;
    --destiny-9: #4f46a8;
    --danger: #b42318;
    --shadow: 0 10px 26px rgba(6, 26, 47, 0.18);
    --topbar-height: 94px;
}

@media (max-width: 620px) {
    :root {
    --bg: #eaf6fb;
    --panel: #ffffff;
    --line: #b8d4e4;
    --line-strong: #7baec8;
    --text: #061a2f;
    --muted: #5e7485;
    --brand: #0b3f6f;
    --brand-dark: #061a2f;
    --brand-mid: #0f6ea8;
    --brand-soft: #e5f3fb;
    --water-cyan: #0ea5b7;
    --destiny-9: #4f46a8;
    --danger: #b42318;
    --shadow: 0 10px 26px rgba(6, 26, 47, 0.18);
    --topbar-height: 94px;
}