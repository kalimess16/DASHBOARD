<?php
header('Content-Type: text/css; charset=UTF-8');
?>
:root {
    --bg-1: #ffe9f5;
    --bg-2: #ffd8ec;
    --panel: #a90f6d;
    --panel-2: #7a0b50;
    --line: #efc8df;
    --text: #4e1738;
    --muted: #8e5c78;
    --accent: #d51382;
    --white-soft: rgba(255, 255, 255, 0.88);
}
* { box-sizing: border-box; }
body {
    margin: 0;
    min-height: 100vh;
    font-family: "Times New Roman", Times, serif;
    background:
        radial-gradient(circle at 12% 12%, #ffd3ea 0%, rgba(255, 211, 234, 0) 38%),
        radial-gradient(circle at 84% 18%, #ffcae4 0%, rgba(255, 202, 228, 0) 36%),
        linear-gradient(165deg, var(--bg-1) 0%, var(--bg-2) 100%);
    color: var(--text);
}
.app {
    display: grid;
    grid-template-columns: 74px 1fr;
    min-height: 100vh;
}
.sidebar {
    background: linear-gradient(180deg, var(--panel) 0%, var(--panel-2) 100%);
    border-right: 1px solid rgba(255, 255, 255, 0.15);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 14px 10px;
    gap: 10px;
    position: relative;
}
.brand-dot {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow: hidden;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255,255,255,0.12) inset;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}
.brand-dot img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.brand-dot:hover {
    transform: translateY(-1px);
    box-shadow: 0 18px 34px rgba(0, 0, 0, 0.42), 0 0 0 1px rgba(255,255,255,0.18) inset;
}
.icon-btn {
    width: 48px;
    height: 48px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}
.icon-btn:hover,
.icon-btn.active {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.45);
    transform: translateY(-1px);
}
.icon-btn .tooltip {
    position: absolute;
    left: 56px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(25, 14, 23, 0.92);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 14px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.15s ease;
    z-index: 5;
}
.icon-btn:hover .tooltip { opacity: 1; }
.main { padding: 18px; }
.header {
    margin: 0 0 14px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--white-soft);
    padding: 14px 16px;
}
.header h1 {
    margin: 0;
    font-size: 30px;
    color: #9f0f65;
}
.home-link {
    color: inherit;
    text-decoration: none;
    cursor: pointer;
}
.home-link:hover { text-decoration: none; }
.content {
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--white-soft);
    overflow: hidden;
    min-height: calc(100vh - 128px);
}
#contentFrame {
    width: 100%;
    height: calc(100vh - 128px);
    border: 0;
    display: none;
}
.placeholder {
    height: calc(100vh - 128px);
    display: grid;
    place-items: center;
    text-align: center;
    padding: 24px;
    color: var(--muted);
    font-size: 22px;
}
.placeholder strong {
    display: block;
    color: #9f0f65;
    font-size: 28px;
    margin-bottom: 10px;
}
.logo-wrap {
    width: min(520px, 82%);
    margin: 0 auto 18px;
    aspect-ratio: 1 / 1;
    border-radius: 24px;
    background: linear-gradient(180deg, #ffd9ed 0%, #ffc2e3 100%);
    border: 1px solid #f2b5d7;
    display: grid;
    place-items: center;
    overflow: hidden;
}
.logo-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.menu-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 11px;
    background: #d10024;
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.45);
    font-size: 12px;
    line-height: 20px;
    text-align: center;
    font-weight: 700;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.28);
}
@media (max-width: 900px) {
    .app { grid-template-columns: 64px 1fr; }
    .main { padding: 10px; }
    .header h1 { font-size: 24px; }
    .placeholder { font-size: 18px; }
    .placeholder strong { font-size: 24px; }
}
