<?php
header('Content-Type: text/css; charset=UTF-8');
?>
:root {
    --bg-1: #ffe9f5;
    --bg-2: #ffd8ec;
    --panel: #a90f6d;
    --panel-2: #7a0b50;
    --panel-3: #cf2f8a;
    --line: #efc8df;
    --text: #4e1738;
    --muted: #8e5c78;
    --accent: #d51382;
    --white-soft: rgba(255, 255, 255, 0.88);
    --shadow: 0 18px 34px rgba(122, 11, 80, 0.16);
}

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    height: 100%;
    min-height: 100vh;
}

body {
    font-family: "Times New Roman", Times, serif;
    font-size: 14px;
    color: var(--text);
    background:
        radial-gradient(circle at 14% 12%, rgba(255, 210, 233, 0.72) 0%, rgba(255, 210, 233, 0) 28%),
        radial-gradient(circle at 88% 16%, rgba(255, 194, 227, 0.76) 0%, rgba(255, 194, 227, 0) 30%),
        linear-gradient(180deg, #fff8fc 0%, #ffeef8 100%);
}

button,
a,
input,
select,
textarea {
    font: inherit;
}

img {
    display: block;
    max-width: 80%;
}

.page-shell {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
}

.page-header {
    color: #FFFFFF;
    background: linear-gradient(50deg, var(--panel-2) 0%, var(--panel) 58%, var(--panel-3) 100%);
    border-radius: 0;
    box-shadow: 0 10px 18px rgba(122, 11, 80, 0.18);
    position: relative;
    z-index: 2;
}

.page-header__inner {
    max-width: 100%;
    padding: 10px 20px 0;
}

.brand-block {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    border: 0;
    padding: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    text-align: left;
}

.brand-block__logo {
    width: 52px;
    height: 52px;
    flex: 0 0 52px;
    display: grid;
    place-items: center;
}

.brand-block__logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: saturate(1.08) brightness(1.05);
}

.brand-block__text strong {
    font-size: clamp(20px, 2.1vw, 38px);
    line-height: 1.05;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    text-shadow: 0 4px 12px rgba(92, 8, 60, 0.28);
}

.main-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
    padding-bottom: 0;
}

.nav-group {
    position: relative;
}

.nav-group__toggle {
    min-height: 30px;
    padding: 5px 14px;
    border: 0;
    border-radius: 12px 12px 0 0;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.12) 100%);
    color: #ffffff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
    transition: background 0.18s ease, transform 0.18s ease;
}

.nav-group__toggle:hover,
.nav-group.is-open .nav-group__toggle {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.28) 0%, rgba(255, 255, 255, 0.16) 100%);
    transform: translateY(-1px);
}

.nav-group__caret {
    width: 9px;
    height: 9px;
    border-right: 2px solid currentColor;
    border-bottom: 2px solid currentColor;
    transform: rotate(45deg) translateY(-1px);
    transition: transform 0.18s ease;
}

.nav-group.is-open .nav-group__caret,
.nav-group:hover .nav-group__caret {
    transform: rotate(225deg) translateY(-1px);
}

.nav-group__panel {
    position: absolute;
    top: calc(100% + 2px);
    left: 0;
    min-width: 250px;
    display: none;
    z-index: 30;
    padding: 10px;
    border-radius: 14px;
    border: 1px solid rgba(169, 15, 109, 0.14);
    background: linear-gradient(180deg, rgba(255, 248, 252, 0.98) 0%, rgba(255, 236, 246, 0.98) 100%);
    box-shadow: 0 18px 36px rgba(122, 11, 80, 0.22);
}

.nav-group:hover .nav-group__panel,
.nav-group.is-open .nav-group__panel {
    display: block;
}

.nav-item {
    width: 100%;
    border: 1px solid transparent;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.72);
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    color: var(--text);
    cursor: pointer;
    text-align: left;
    transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
}

.nav-item + .nav-item {
    margin-top: 8px;
}

.nav-item:hover,
.nav-item.is-active {
    transform: translateY(-1px);
    border-color: rgba(169, 15, 109, 0.18);
    background: rgba(255, 255, 255, 0.96);
}

.nav-item__body {
    display: grid;
    gap: 4px;
    min-width: 0;
}

.nav-item__body strong {
    font-size: 14px;
    color: var(--panel-2);
}

.nav-item__body span {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.35;
}

.menu-badge {
    min-width: 24px;
    height: 24px;
    padding: 0 7px;
    border-radius: 999px;
    background: var(--accent);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.45);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    box-shadow: 0 6px 14px rgba(213, 19, 130, 0.28);
}

.page-main {
    flex: 1;
    min-height: 0;
    display: flex;
    padding: 0 16px 10px;
}

.workspace-card {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    border: 1px solid var(--line);
    border-top: 0;
    border-radius: 0 0 24px 24px;
    background:
        radial-gradient(circle at center, rgba(255, 209, 234, 0.44) 0%, rgba(255, 209, 234, 0) 28%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(255, 250, 253, 0.94) 100%);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.placeholder {
    flex: 1;
    min-height: 0;
    display: grid;
    place-items: center;
    position: relative;
    overflow: hidden;
    padding: 24px 18px 32px;
}

.placeholder__glow {
    position: absolute;
    inset: 10% 20%;
    background: radial-gradient(circle, rgba(255, 168, 214, 0.28) 0%, rgba(255, 168, 214, 0) 62%);
    pointer-events: none;
}

.placeholder__content {
    position: relative;
    z-index: 1;
    text-align: center;
}

.placeholder__logo {
    width: min(460px, 48vw);
    margin: 0 auto;
    filter: drop-shadow(0 24px 38px rgba(213, 19, 130, 0.14));
}

.placeholder__tagline {
    margin: 2px 0 0;
    font-size: clamp(18px, 1.7vw, 30px);
    line-height: 1.2;
    color: var(--panel-2);
    font-weight: 700;
}

.placeholder__note {
    margin: 12px auto 0;
    max-width: 620px;
    font-size: 14px;
    line-height: 1.5;
    color: var(--muted);
}

#contentFrame {
    flex: 1;
    width: 100%;
    height: 100%;
    min-height: 0;
    border: 0;
    background: #ffffff;
    display: none;
}

.page-footer {
    padding: 8px 16px 12px;
}

.footer-block {
    display: grid;
    gap: 4px;
    width: 100%;
    padding: 14px 18px;
    border-radius: 18px;
    border: 1px solid rgba(169, 15, 109, 0.1);
    background: rgba(255, 255, 255, 0.74);
    color: var(--muted);
}

.footer-label {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.12em;
    color: var(--accent);
}

.footer-block strong {
    color: var(--panel-2);
    line-height: 1.45;
}

.footer-note {
    line-height: 1.5;
}

.footer-block--wide {
    width: 100%;
}

@media (max-width: 980px) {
    .page-header__inner,
    .page-main,
    .page-footer {
        padding-left: 14px;
        padding-right: 14px;
    }

    .placeholder__logo {
        width: min(380px, 72vw);
    }
}

@media (max-width: 720px) {
    .page-header__inner {
        padding-top: 10px;
    }

    .brand-block {
        align-items: flex-start;
    }

    .brand-block__logo {
        width: 42px;
        height: 42px;
        flex-basis: 42px;
    }

    .brand-block__text strong {
        font-size: 18px;
    }

    .main-nav {
        gap: 6px;
    }

    .nav-group {
        width: calc(50% - 4px);
    }

    .nav-group__toggle {
        width: 100%;
        justify-content: space-between;
        border-radius: 12px;
        font-size: 12px;
    }

    .nav-group__panel {
        position: static;
        min-width: 100%;
        margin-top: 4px;
    }

    .page-main {
        padding-bottom: 8px;
    }

    .workspace-card {
        border-radius: 0 0 18px 18px;
    }

    .placeholder {
        padding: 20px 14px 24px;
    }

    .placeholder__logo {
        width: min(300px, 76vw);
    }

    .placeholder__note {
        font-size: 13px;
    }

    .page-footer {
        padding-bottom: 10px;
    }
}