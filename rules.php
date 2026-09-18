<?php
require_once dirname(__FILE__) . '/no_cache.php';   /* ส่ง header กันแคช ต้องอยู่ก่อน output ใด ๆ */
/* ══════════════════════════════════════════════════════════════
   rules.php — หน้ากฎระเบียบข้อบังคับ (Work Rules and Regulations)
   เข้าจากปุ่มขีด 3 ขีดบนแถบเมนู · ข้อมูลอยู่ที่ data/rules.json
   ══════════════════════════════════════════════════════════════ */

require_once dirname(__FILE__) . '/rules_helpers.php';

$documents = rules_documents();
$mailIt = 'Pumiput.it@supavut.com';
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>กฎระเบียบข้อบังคับ | SUPAVUT GROUP</title>
  <link rel="icon" type="image/png" href="./img/3si.png">
  <link rel="apple-touch-icon" href="./img/pwa/icon-192.png">
  <link rel="manifest" href="./site.webmanifest">
  <meta name="theme-color" content="#064ba6">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">

  <?php /* คุมขนาดการแสดงผลให้เท่ากันทุกเครื่อง (ดู zoom_lock.php) */ ?>
  <?php require_once dirname(__FILE__) . '/zoom_lock.php'; ?>

  <style>
    /* ══════════════════════════════════════════════════════════
       1. DESIGN TOKENS — ชุดเดียวกับหน้าแรก (index.php)
       ══════════════════════════════════════════════════════════ */
    :root {
      --navy: #073a75;
      --navy-deep: #052a57;
      --navy-ink: #052a57;
      --navy-soft: #eaf1fb;
      --green: #168642;
      --green-soft: #e6f4ec;

      --bg: #f4f6f9;
      --surface: #ffffff;
      --surface-2: #f8fafc;
      --surface-3: #eef2f7;

      --ink: #111827;
      --ink-2: #33415c;
      --muted: #5b6778;
      --faint: #8b95a5;

      --line: #e3e8ef;
      --line-2: #cfd8e3;

      --amber: #b45309;
      --amber-soft: #fef3e2;
      --news-gold: #d9c374;

      --r-sm: 4px;
      --r-md: 6px;
      --r-lg: 8px;
      --r-full: 999px;

      --sh-1: 0 1px 2px rgba(17, 24, 39, 0.05);
      --sh-2: 0 2px 8px rgba(17, 24, 39, 0.06), 0 1px 2px rgba(17, 24, 39, 0.04);
      --sh-3: 0 10px 30px rgba(17, 24, 39, 0.10), 0 2px 6px rgba(17, 24, 39, 0.05);

      --shell: 1680px;
      --gut: clamp(20px, 3vw, 46px);
      --head-h: 89px;

      --z-sticky: 100;
      --z-dropdown: 200;

      --ease: cubic-bezier(0.22, 1, 0.36, 1);
      --fast: 140ms;
      --base: 200ms;
    }

    /* ══════════════════════════════════════════════════════════
       2. RESET / BASE
       ══════════════════════════════════════════════════════════ */
    * { box-sizing: border-box; }

    html {
      scroll-behavior: smooth;
      -webkit-text-size-adjust: 100%;
    }

    body {
      margin: 0;
      min-height: var(--screen-h);
      display: flex;
      flex-direction: column;
      background: var(--bg);
      color: var(--ink);
      font-family: "Inter", "Noto Sans Thai", "Noto Sans Myanmar", system-ui, -apple-system, "Segoe UI", sans-serif;
      font-size: 24px;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3, p { margin: 0; }
    img { max-width: 100%; display: block; }
    a { color: inherit; text-decoration: none; -webkit-tap-highlight-color: transparent; }

    button, input, select, textarea { font: inherit; color: inherit; }

    button {
      border: 0;
      background: none;
      cursor: pointer;
      -webkit-tap-highlight-color: transparent;
    }

    :focus-visible {
      outline: 2px solid var(--navy);
      outline-offset: 2px;
      border-radius: 4px;
    }

    .shell {
      width: 100%;
      max-width: var(--shell);
      margin-inline: auto;
      padding-inline: var(--gut);
    }

    /* แถบเมนูกับท้ายหน้ากางเต็มจอ — โลโก้ชิดซ้าย เครื่องมือชิดขวา */
    .site-header .shell,
    .site-footer .shell { max-width: none; padding-inline: clamp(17px, 2.3vw, 31px); }

    /* ══════════════════════════════════════════════════════════
       3. HEADER — เหมือนหน้าแรก
       ══════════════════════════════════════════════════════════ */
    .site-header {
      position: sticky;
      top: 0;
      z-index: var(--z-sticky);
      background: var(--surface);
      border-bottom: 1px solid var(--line);
    }

    .header-row {
      display: flex;
      align-items: center;
      gap: clamp(14px, 2.3vw, 36px);
      min-height: var(--head-h);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 16px;
      flex: 0 0 auto;
    }

    .brand-mark { width: 56px; height: 56px; object-fit: contain; }
    .brand-name { display: grid; line-height: 1; }

    .brand-name b {
      color: var(--navy-ink);
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: 0.05em;
    }

    .brand-name span {
      color: var(--green);
      font-size: 0.875rem;
      font-weight: 700;
      letter-spacing: 0.32em;
      margin-top: 4px;
    }

    .header-tools {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-left: auto;
      flex: 0 0 auto;
    }

    .pop { position: relative; flex: 0 0 auto; }

    .pop-panel {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      z-index: var(--z-dropdown);
      min-width: 289px;
      padding: 7px;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
      box-shadow: var(--sh-3);
      display: none;
    }

    .pop-panel.on { display: grid; gap: 2px; }

    .pop-label {
      padding: 6px 10px 4px;
      color: var(--faint);
      font-size: 0.875rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .pop-item {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 11px 14px;
      border-radius: var(--r-sm);
      color: var(--ink);
      font-size: 1.125rem;
      text-align: left;
      transition: background var(--fast) var(--ease);
    }

    .pop-item:hover { background: var(--surface-3); }
    .pop-item img { width: 25px; height: 18px; object-fit: cover; border-radius: 2px; flex: 0 0 auto; }
    .pop-item svg { width: 22px; height: 22px; flex: 0 0 auto; color: var(--muted); }
    .pop-item span { flex: 1 1 auto; }

    .pop-item[aria-current="true"] {
      background: var(--navy-soft);
      color: var(--navy-ink);
      font-weight: 600;
    }

    .pop-item[aria-current="true"] svg { color: var(--navy-ink); }

    .pop-divider { height: 1px; margin: 5px 6px; background: var(--line); }

    .icon-btn {
      width: 53px;
      height: 53px;
      display: grid;
      place-items: center;
      border: 1px solid transparent;
      border-radius: var(--r-sm);
      color: var(--muted);
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease), border-color var(--fast) var(--ease);
    }

    .icon-btn:hover, .icon-btn[aria-expanded="true"] { border-color: var(--line-2); background: var(--surface-2); color: var(--navy-ink); }
    .icon-btn svg { width: 27px; height: 27px; }

    .lang-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      height: 53px;
      padding: 0 15px;
      border: 1px solid transparent;
      border-radius: var(--r-sm);
      color: var(--ink);
      font-size: 1.1875rem;
      font-weight: 600;
      transition: background var(--fast) var(--ease), border-color var(--fast) var(--ease);
    }

    .lang-btn:hover, .lang-btn[aria-expanded="true"] { border-color: var(--line-2); background: var(--surface-2); }
    .lang-btn img { width: 28px; height: 20px; object-fit: cover; border-radius: 2px; }
    .lang-btn svg { width: 15px; height: 15px; color: var(--muted); }

    /* เส้นทางย้อนกลับบนแถบเมนู */
    .crumb {
      display: flex;
      align-items: center;
      gap: 11px;
      min-width: 0;
      margin-left: clamp(14px, 2.5vw, 30px);
      padding-left: clamp(14px, 2.5vw, 30px);
      border-left: 1px solid var(--line);
      color: var(--muted);
      font-size: 1.3125rem;
    }

    .crumb a { transition: color var(--fast) var(--ease); }
    .crumb a:hover { color: var(--navy-ink); }
    .crumb .sep { color: var(--line-2); }

    .crumb .here {
      min-width: 0;
      overflow: hidden;
      color: var(--ink-2);
      font-weight: 600;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* ══════════════════════════════════════════════════════════
       4. หัวเรื่อง + คำอธิบาย
       ══════════════════════════════════════════════════════════ */
    .page {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      padding-bottom: clamp(14px, 1.8vw, 22px);
    }

    .hero {
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, var(--navy-deep), var(--navy));
      color: #ffffff;
    }

    /* ลายตราสัญลักษณ์จาง ๆ มุมขวา — ให้แถบหัวไม่แบนเกินไป */
    .hero::after {
      content: "";
      position: absolute;
      top: 50%;
      right: clamp(-70px, -4vw, -30px);
      width: 260px;
      height: 260px;
      transform: translateY(-50%);
      border: 34px solid rgba(255, 255, 255, 0.05);
      border-radius: 50%;
      pointer-events: none;
    }

    .hero-inner {
      position: relative;
      z-index: 1;
      padding-block: clamp(24px, 3.4vw, 44px);
    }

    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      height: 22px;
      padding: 0 9px;
      margin-bottom: 12px;
      border: 1px solid var(--news-gold);
      border-radius: 3px;
      color: var(--news-gold);
      font-size: 0.9375rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .hero h1 {
      font-size: clamp(1.875rem, 3.7vw, 2.625rem);
      font-weight: 700;
      line-height: 1.3;
      letter-spacing: -0.015em;
      text-wrap: balance;
    }

    .hero-lead {
      max-width: 74ch;
      margin-top: 12px;
      color: rgba(255, 255, 255, 0.86);
      font-size: 1.3125rem;
      line-height: 1.7;
      text-wrap: pretty;
    }

    .accent-bar {
      height: 3px;
      background: linear-gradient(90deg, var(--green) 0 38%, var(--news-gold) 38% 62%, var(--navy) 62% 100%);
    }

    /* ══════════════════════════════════════════════════════════
       5. รายการเอกสาร
       ══════════════════════════════════════════════════════════ */
    .doc-wrap {
      width: 100%;
      margin-inline: auto;
      padding-top: clamp(18px, 2.4vw, 30px);
    }

    .doc-head {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .doc-head h2 {
      font-size: 1.3125rem;
      font-weight: 700;
    }

    .doc-head p { color: var(--faint); font-size: 1.125rem; }

    .doc-list { display: grid; gap: 10px; }

    .doc {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 22px 25px;
      border: 1px solid var(--line);
      border-radius: var(--r-lg);
      background: var(--surface);
      box-shadow: var(--sh-1);
      transition: border-color var(--fast) var(--ease), box-shadow var(--fast) var(--ease);
    }

    .doc:hover {
      border-color: var(--line-2);
      box-shadow: var(--sh-2);
    }

    .doc-icon {
      width: 58px;
      height: 58px;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      color: var(--muted);
    }

    .doc-icon img { width: 100%; height: 100%; object-fit: contain; }
    .doc-icon svg { width: 42px; height: 42px; }

    .doc-main { flex: 1 1 auto; min-width: 0; }

    .doc-tags {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 6px;
      margin-bottom: 6px;
    }

    .doc-lang, .doc-year {
      display: inline-flex;
      align-items: center;
      height: 28px;
      padding: 0 11px;
      border-radius: var(--r-full);
      font-size: 0.9375rem;
      font-weight: 600;
    }

    .doc-lang { background: var(--navy-soft); color: var(--navy-ink); }
    .doc-year { background: var(--surface-3); color: var(--muted); }

    .doc-main h3 {
      font-size: 1.3125rem;
      font-weight: 700;
      line-height: 1.45;
      text-wrap: pretty;
    }

    .doc-main p {
      margin-top: 5px;
      color: var(--ink-2);
      font-size: 1.125rem;
      line-height: 1.7;
      text-wrap: pretty;
    }

    .doc-meta {
      margin-top: 8px;
      color: var(--faint);
      font-size: 1rem;
    }

    .doc-actions {
      display: flex;
      align-items: center;
      gap: 11px;
      flex: 0 0 auto;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      height: 48px;
      padding: 0 20px;
      border: 1px solid transparent;
      border-radius: var(--r-sm);
      font-size: 1.0625rem;
      font-weight: 600;
      white-space: nowrap;
      transition: background var(--fast) var(--ease), border-color var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .btn svg { width: 21px; height: 21px; flex: 0 0 auto; }

    .btn-primary { background: var(--navy); color: #ffffff; }
    .btn-primary:hover { background: var(--navy-deep); }

    .btn-ghost {
      border-color: var(--line-2);
      background: var(--surface);
      color: var(--ink-2);
    }

    .btn-ghost:hover { border-color: var(--navy); background: var(--navy-soft); color: var(--navy-ink); }

    .empty {
      display: grid;
      gap: 6px;
      padding: 34px 20px;
      border: 1px dashed var(--line-2);
      border-radius: var(--r-lg);
      background: var(--surface);
      text-align: center;
    }

    .empty b { font-size: 1.3125rem; }
    .empty span { color: var(--faint); font-size: 1.125rem; }

    /* ══════════════════════════════════════════════════════════
       6. FOOTER — เหมือนหน้าแรก
       ══════════════════════════════════════════════════════════ */
    .site-footer {
      border-top: 1px solid var(--line);
      background: var(--surface);
      padding-block: 12px;
      margin-top: 18px;
    }

    .footer-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
      color: var(--faint);
      font-size: 1rem;
    }

    .footer-row a { color: var(--muted); }
    .footer-row a:hover { color: var(--navy-ink); }

    /* ══════════════════════════════════════════════════════════
       7. RESPONSIVE
       ══════════════════════════════════════════════════════════ */
    /* จอที่ไม่ใช้ zoom lock: กลับไปใช้สเกลเดียวกับหน้าแรกโดยตรง */
    @media (max-width: 1023px) {
      :root { --head-h: 64px; }
      body { font-size: 17px; }
      .site-header .shell,
      .site-footer .shell { padding-inline: clamp(12px, 1.6vw, 22px); }
      .header-row { gap: clamp(10px, 1.6vw, 26px); }
      .brand { gap: 11px; }
      .brand-mark { width: 40px; height: 40px; }
      .brand-name b { font-size: 1.0625rem; }
      .brand-name span { font-size: 0.625rem; margin-top: 3px; }
      .header-tools { gap: 3px; }
      .pop-panel { min-width: 208px; padding: 5px; }
      .pop-label { font-size: 0.625rem; }
      .pop-item { padding: 8px 10px; font-size: 0.8125rem; }
      .pop-item img { width: 18px; height: 13px; }
      .pop-item svg { width: 16px; height: 16px; }
      .icon-btn { width: 38px; height: 38px; }
      .icon-btn svg { width: 19px; height: 19px; }
      .lang-btn { gap: 6px; height: 38px; padding: 0 11px; font-size: 0.875rem; }
      .lang-btn img { width: 20px; height: 14px; }
      .lang-btn svg { width: 11px; height: 11px; }
      .crumb { gap: 8px; font-size: 0.8125rem; }
      .hero-eyebrow, .doc-lang, .doc-year { font-size: 0.6875rem; }
      .hero h1 { font-size: clamp(1.375rem, 2.6vw, 1.875rem); }
      .hero-lead { font-size: 0.9375rem; line-height: 1.8; }
      .doc-head h2, .doc-main h3, .empty b { font-size: 0.9375rem; }
      .doc-head p, .doc-main p, .btn, .empty span { font-size: 0.8125rem; }
      .doc-main p { line-height: 1.75; }
      .doc-meta, .footer-row { font-size: 0.75rem; }
      .doc { gap: 14px; padding: 16px 18px; }
      .doc-icon { width: 42px; height: 42px; }
      .doc-icon svg { width: 30px; height: 30px; }
      .doc-lang, .doc-year { height: 20px; padding: 0 8px; }
      .doc-actions { gap: 8px; }
      .btn { gap: 7px; height: 34px; padding: 0 14px; }
      .btn svg { width: 15px; height: 15px; }
    }

    @media (max-width: 860px) {
      .crumb { display: none; }
    }

    @media (max-width: 720px) {
      .doc {
        flex-wrap: wrap;
        gap: 12px;
      }

      .doc-main { flex-basis: calc(100% - 56px); }

      .doc-actions {
        flex-basis: 100%;
        justify-content: flex-start;
      }

      .doc-actions .btn { flex: 1 1 auto; }
    }

    /* ══════════════════════════════════════════════════════════
       8. ลดการเคลื่อนไหว
       ══════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
      html { scroll-behavior: auto; }

      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>

<body>
  <!-- ══════════════════ HEADER ══════════════════ -->
  <header class="site-header">
    <div class="shell header-row">
      <a class="brand" href="index.php" aria-label="SUPAVUT GROUP">
        <img class="brand-mark" src="img/3si.png" alt="">
        <span class="brand-name">
          <b>SUPAVUT</b>
          <span>GROUP</span>
        </span>
      </a>

      <nav class="crumb" aria-label="breadcrumb">
        <a href="index.php" data-i18n="menuHome">หน้าแรก</a>
        <span class="sep">»</span>
        <span class="here" data-i18n="rulesTitle">กฎระเบียบข้อบังคับ</span>
      </nav>

      <div class="header-tools">
        <div class="pop">
          <button class="icon-btn" id="menuBtn" type="button" aria-label="เมนู" aria-expanded="false" aria-haspopup="true">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          </button>
          <div class="pop-panel" id="menuPanel" role="menu">
            <p class="pop-label" data-i18n="orgTitle">ข้อมูลองค์กร</p>
            <a class="pop-item" href="index.php" role="menuitem">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 11 9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
              <span data-i18n="menuHome">หน้าแรก</span>
            </a>
            <a class="pop-item" href="rules.php" role="menuitem" aria-current="true">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 2.5v3.2A1.3 1.3 0 0 0 15.3 7h3.2M9 12.5h6M9 16h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span data-i18n="rulesTitle">กฎระเบียบข้อบังคับ</span>
            </a>
            <div class="pop-divider"></div>
            <a class="pop-item" href="announcements_admin.php" role="menuitem">
              <img src="img/login-logo.png" alt="" style="width:18px;height:18px;object-fit:contain">
              <span data-i18n="orgAdmin">เข้าสู่ระบบผู้ดูแล</span>
            </a>
          </div>
        </div>

        <div class="pop">
          <button class="lang-btn" id="langBtn" type="button" aria-label="เปลี่ยนภาษา" aria-expanded="false" aria-haspopup="true">
            <img id="langFlag" src="img/flags/th.png" alt="">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <div class="pop-panel" id="langPanel" role="menu">
            <button class="pop-item" type="button" role="menuitem" data-lang="th"><img src="img/flags/th.png" alt=""><span>ไทย</span></button>
            <button class="pop-item" type="button" role="menuitem" data-lang="en"><img src="img/flags/en.png" alt=""><span>English</span></button>
            <button class="pop-item" type="button" role="menuitem" data-lang="my"><img src="img/flags/my.png" alt=""><span>မြန်မာ</span></button>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- ══════════════════ PAGE ══════════════════ -->
  <main class="page">

    <!-- ── หัวเรื่อง + คำอธิบายสั้น ── -->
    <section class="hero">
      <div class="shell hero-inner">
        <span class="hero-eyebrow" data-i18n="orgTitle">ข้อมูลองค์กร</span>
        <h1 data-i18n="rulesTitle">กฎระเบียบข้อบังคับ</h1>
        <p class="hero-lead" id="rulesIntro"
           data-intro-th="<?php echo announcement_h(rules_intro('th')); ?>"
           data-intro-en="<?php echo announcement_h(rules_intro('en')); ?>"
           data-intro-my="<?php echo announcement_h(rules_intro('my')); ?>"><?php echo announcement_h(rules_intro('th')); ?></p>
      </div>
    </section>

    <div class="accent-bar" aria-hidden="true"></div>

    <!-- ── รายการเอกสาร ── -->
    <div class="shell">
      <div class="doc-wrap">

        <?php if (count($documents) === 0) { ?>
          <div class="empty">
            <b data-i18n="emptyTitle">ยังไม่มีเอกสาร</b>
            <span data-i18n="emptyText">เมื่อฝ่ายบุคคลเผยแพร่เอกสาร จะแสดงที่นี่</span>
          </div>

        <?php } else { ?>
          <div class="doc-head">
            <h2 data-i18n="docHead">เอกสารทั้งหมด</h2>
            <p><span data-i18n="docCountLabel">จำนวน</span> <?php echo count($documents); ?> <span data-i18n="docCountUnit">ฉบับ</span></p>
          </div>

          <div class="doc-list">
            <?php foreach ($documents as $doc) { ?>
              <?php
                $viewUrl = rules_view_url($doc);
                $downloadUrl = rules_download_url($doc);
                $sizeLabel = rules_file_size_label($doc);
                $updatedLabel = rules_updated_label($doc);
                $iconUrl = announcement_file_icon_url('pdf');
              ?>
              <article class="doc">
                <span class="doc-icon" aria-hidden="true">
                  <?php if ($iconUrl !== '') { ?>
                    <img src="<?php echo announcement_h($iconUrl); ?>" alt="">
                  <?php } else { ?>
                    <?php echo announcement_file_icon_svg('pdf'); ?>
                  <?php } ?>
                </span>

                <div class="doc-main">
                  <div class="doc-tags">
                    <?php if (isset($doc['lang_tag']) && $doc['lang_tag'] !== '') { ?>
                      <span class="doc-lang"><?php echo announcement_h($doc['lang_tag']); ?></span>
                    <?php } ?>
                    <?php if (isset($doc['year']) && $doc['year'] !== '') { ?>
                      <span class="doc-year"><span data-i18n="yearWord">ปี</span> <?php echo announcement_h($doc['year']); ?></span>
                    <?php } ?>
                  </div>

                  <h3 data-doc-title
                      data-name-th="<?php echo announcement_h(rules_localized($doc, 'title', 'th')); ?>"
                      data-name-en="<?php echo announcement_h(rules_localized($doc, 'title', 'en')); ?>"
                      data-name-my="<?php echo announcement_h(rules_localized($doc, 'title', 'my')); ?>"><?php echo announcement_h(rules_localized($doc, 'title', 'th')); ?></h3>

                  <p data-doc-body
                     data-name-th="<?php echo announcement_h(rules_localized($doc, 'body', 'th')); ?>"
                     data-name-en="<?php echo announcement_h(rules_localized($doc, 'body', 'en')); ?>"
                     data-name-my="<?php echo announcement_h(rules_localized($doc, 'body', 'my')); ?>"><?php echo announcement_h(rules_localized($doc, 'body', 'th')); ?></p>

                  <div class="doc-meta">
                    PDF<?php if ($sizeLabel !== '') { ?> · <?php echo announcement_h($sizeLabel); ?><?php } ?><?php if ($updatedLabel !== '') { ?> · <span data-i18n="updatedWord">ปรับปรุง</span> <?php echo announcement_h($updatedLabel); ?><?php } ?>
                  </div>
                </div>

                <div class="doc-actions">
                  <a class="btn btn-primary" href="<?php echo announcement_h($viewUrl); ?>" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.8" stroke="currentColor" stroke-width="1.7"/></svg>
                    <span data-i18n="docOpen">เปิดอ่าน</span>
                  </a>
                  <a class="btn btn-ghost" href="<?php echo announcement_h($downloadUrl); ?>">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v10m0 0 4-4m-4 4-4-4M4.5 18.5h15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span data-i18n="docDownload">ดาวน์โหลด</span>
                  </a>
                </div>
              </article>
            <?php } ?>
          </div>
        <?php } ?>

      </div>
    </div>
  </main>

  <!-- ══════════════════ FOOTER ══════════════════ -->
  <footer class="site-footer">
    <div class="shell footer-row">
      <span>SUPAVUT INDUSTRY</span>
      <span><span data-i18n="footerBy">พัฒนาโดยฝ่าย IT</span> · <a href="mailto:<?php echo announcement_h($mailIt); ?>"><?php echo announcement_h($mailIt); ?></a></span>
    </div>
  </footer>

  <script>
    'use strict';

    /* ══════════════════════════════════════════════════════════
       1. คำแปล 3 ภาษา
       ══════════════════════════════════════════════════════════ */
    var t = {
      th: {
        orgTitle: 'ข้อมูลองค์กร', menuHome: 'หน้าแรก', orgAdmin: 'เข้าสู่ระบบผู้ดูแล',
        rulesTitle: 'กฎระเบียบข้อบังคับ',
        docHead: 'เอกสารทั้งหมด', docCountLabel: 'จำนวน', docCountUnit: 'ฉบับ',
        yearWord: 'ปี', updatedWord: 'ปรับปรุง',
        docOpen: 'เปิดอ่าน', docDownload: 'ดาวน์โหลด',
        emptyTitle: 'ยังไม่มีเอกสาร', emptyText: 'เมื่อฝ่ายบุคคลเผยแพร่เอกสาร จะแสดงที่นี่',
        footerBy: 'พัฒนาโดยฝ่าย IT'
      },
      en: {
        orgTitle: 'Company info', menuHome: 'Home', orgAdmin: 'Admin sign in',
        rulesTitle: 'Work rules and regulations',
        docHead: 'All documents', docCountLabel: 'Total', docCountUnit: 'files',
        yearWord: 'Year', updatedWord: 'Updated',
        docOpen: 'Read online', docDownload: 'Download',
        emptyTitle: 'No documents yet', emptyText: 'Documents published by HR will appear here.',
        footerBy: 'Built by IT'
      },
      my: {
        orgTitle: 'ကုမ္ပဏီအချက်အလက်', menuHome: 'ပင်မ', orgAdmin: 'Admin ဝင်ရောက်ရန်',
        rulesTitle: 'လုပ်ငန်းစည်းမျဉ်းများ',
        docHead: 'စာရွက်စာတမ်းအားလုံး', docCountLabel: 'စုစုပေါင်း', docCountUnit: 'စောင်',
        yearWord: 'နှစ်', updatedWord: 'ပြင်ဆင်ချိန်',
        docOpen: 'ဖတ်ရန်', docDownload: 'ဒေါင်းလုဒ်',
        emptyTitle: 'စာရွက်စာတမ်း မရှိသေးပါ', emptyText: 'HR မှ ထုတ်ပြန်သည့်အခါ ဤနေရာတွင် ပေါ်လာပါမည်။',
        footerBy: 'IT ဌာနမှ ဖန်တီးသည်'
      }
    };

    /* ══════════════════════════════════════════════════════════
       2. สถานะ + ตัวช่วย
       ══════════════════════════════════════════════════════════ */
    var LANG_KEY = 'simenu_language';
    var langs = ['th', 'en', 'my'];
    var lang = 'th';

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    /* ══════════════════════════════════════════════════════════
       3. ภาษา
       ══════════════════════════════════════════════════════════ */
    function readLang() {
      try {
        var v = localStorage.getItem(LANG_KEY);
        return langs.indexOf(v) > -1 ? v : 'th';
      } catch (e) { return 'th'; }
    }

    function setLang(next, save) {
      lang = langs.indexOf(next) > -1 ? next : 'th';
      document.documentElement.lang = lang;

      if (save !== false) {
        try { localStorage.setItem(LANG_KEY, lang); } catch (e) {}
      }

      $$('[data-i18n]').forEach(function (el) {
        var key = el.getAttribute('data-i18n');
        if (t[lang] && t[lang][key]) { el.textContent = t[lang][key]; }
      });

      /* คำอธิบายหัวเรื่อง */
      var intro = $('#rulesIntro');
      if (intro) {
        var text = intro.getAttribute('data-intro-' + lang) || intro.getAttribute('data-intro-th');
        if (text) { intro.textContent = text; }
      }

      /* หัวข้อ + คำอธิบายของแต่ละเอกสาร */
      $$('[data-doc-title], [data-doc-body]').forEach(function (el) {
        var name = el.getAttribute('data-name-' + lang) || el.getAttribute('data-name-th');
        if (name) { el.textContent = name; }
      });

      var flag = $('#langFlag');
      if (flag) { flag.src = 'img/flags/' + lang + '.png'; }

      $$('#langPanel .pop-item').forEach(function (b) {
        b.setAttribute('aria-current', b.getAttribute('data-lang') === lang ? 'true' : 'false');
      });
    }

    /* ══════════════════════════════════════════════════════════
       4. เมนูป๊อปอัป
       ══════════════════════════════════════════════════════════ */
    function closePops() {
      $$('.pop-panel').forEach(function (p) { p.classList.remove('on'); });
      var mb = $('#menuBtn');
      var lb = $('#langBtn');
      if (mb) { mb.setAttribute('aria-expanded', 'false'); }
      if (lb) { lb.setAttribute('aria-expanded', 'false'); }
    }

    document.addEventListener('click', function (e) {
      if (e.target.closest('#menuBtn')) {
        var mp = $('#menuPanel');
        var mOpen = !mp.classList.contains('on');
        closePops();
        mp.classList.toggle('on', mOpen);
        $('#menuBtn').setAttribute('aria-expanded', mOpen ? 'true' : 'false');
        return;
      }

      if (e.target.closest('#langBtn')) {
        var lp = $('#langPanel');
        var lOpen = !lp.classList.contains('on');
        closePops();
        lp.classList.toggle('on', lOpen);
        $('#langBtn').setAttribute('aria-expanded', lOpen ? 'true' : 'false');
        return;
      }

      var langItem = e.target.closest('#langPanel [data-lang]');
      if (langItem) {
        setLang(langItem.getAttribute('data-lang'));
        closePops();
        return;
      }

      if (!e.target.closest('.pop')) { closePops(); }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closePops(); }
    });

    window.addEventListener('storage', function (e) {
      if (e.key === LANG_KEY) { setLang(e.newValue || 'th', false); }
    });

    /* ══════════════════════════════════════════════════════════
       5. เริ่มทำงาน
       ══════════════════════════════════════════════════════════ */
    setLang(readLang(), false);
  </script>
</body>

</html>
