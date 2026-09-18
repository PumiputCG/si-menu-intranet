<?php
require_once dirname(__FILE__) . '/no_cache.php';   /* ส่ง header กันแคช ต้องอยู่ก่อน output ใด ๆ */
require_once dirname(__FILE__) . '/announcement_helpers.php';
require_once dirname(__FILE__) . '/announcement_register_helpers.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$registerAnnouncementId = ANNOUNCEMENT_REGISTER_POST_ID;
$announcements = announcement_read_all();
$match = null;

foreach ($announcements as $item) {
  if (isset($item['id']) && $item['id'] === $id) {
    $match = $item;
    break;
  }
}

if (!$match && function_exists('http_response_code')) {
  http_response_code(404);
}

/* ทะเบียน Excel แสดงต่อท้ายเฉพาะประกาศที่ Manager สร้างไว้ */
$isRegisterAnnouncement = $match && isset($match['id']) && $match['id'] === $registerAnnouncementId;
$announcementRegister = $isRegisterAnnouncement ? announcement_register_read() : null;
$registerRows = $announcementRegister && isset($announcementRegister['rows']) ? $announcementRegister['rows'] : array();

$attachments = $match ? announcement_get_attachments($match) : array();

/* แยกไฟล์แนบ: ภาพแสดงเป็นแกลเลอรี ส่วนเอกสารแสดงเป็นรายการ */
$imageFiles = array();
$docFiles = array();
foreach ($attachments as $att) {
  if (announcement_file_kind($att) === 'image') {
    $imageFiles[] = $att;
  } else {
    $docFiles[] = $att;
  }
}
/* หน้านี้ใช้ภาพปก 2 (ถ้าไม่มีจะถอยไปใช้ภาพปก 1 ให้เอง) */
$coverUrl = $match ? announcement_cover2_url($match) : '';
$isNew = $match ? announcement_is_new($match) : false;
$pageTitle = $match ? $match['title'] . ' | SUPAVUT GROUP' : 'ไม่พบประกาศ | SUPAVUT GROUP';

/* หมวด/แท็บของประกาศนี้ */
$viewCat = $match ? announcement_category_of($match) : '';
$viewCatObj = $viewCat !== '' ? announcement_category_find($viewCat) : null;

/* ประกาศอื่นในแท็บเดียวกัน (ไม่เอาตัวที่กำลังอ่าน) */
$relatedItems = array();
if ($match) {
  foreach ($announcements as $item) {
    if (isset($item['id']) && $item['id'] === $match['id']) {
      continue;
    }
    if (announcement_category_of($item) === $viewCat) {
      $relatedItems[] = $item;
    }
    if (count($relatedItems) >= 4) {
      break;
    }
  }
}

$mailIt = 'Pumiput.it@supavut.com';
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?php echo announcement_h($pageTitle); ?></title>
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
      --on-navy: #ffffff;

      --line: #e3e8ef;
      --line-2: #cfd8e3;

      --amber: #b45309;
      --amber-soft: #fef3e2;

      /* ป้ายวันที่บนภาพปก (ชุดสีเดิมจากเวอร์ชัน 1) */
      --news-wine: #8d2b3a;
      --news-gold: #d9c374;

      --r-sm: 4px;
      --r-md: 6px;
      --r-lg: 8px;
      --r-full: 999px;

      --sh-1: 0 1px 2px rgba(17, 24, 39, 0.05);
      --sh-2: 0 2px 8px rgba(17, 24, 39, 0.06), 0 1px 2px rgba(17, 24, 39, 0.04);
      --sh-3: 0 10px 30px rgba(17, 24, 39, 0.10), 0 2px 6px rgba(17, 24, 39, 0.05);

      /* ขนาดเผื่อการคุมซูมไว้แล้ว (ดู zoom_lock.php)
         พื้นที่จริงกว้างขึ้น ~1.43 เท่า จึงขยายกรอบและตัวอักษรตามส่วน */
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

    h1, h2, h3, h4, p, figure { margin: 0; }
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

    .sr-only {
      position: absolute;
      width: 1px; height: 1px;
      padding: 0; margin: -1px;
      overflow: hidden;
      clip: rect(0 0 0 0);
      white-space: nowrap;
    }

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

    /* ══════════════════════════════════════════════════════════
       4. หน้ากระดาษ
       ══════════════════════════════════════════════════════════ */
    /* ฮีโร่ชนขอบบนได้ ระยะห่างล่างคงไว้ */
    .page {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      padding-bottom: clamp(14px, 1.8vw, 22px);
    }

    .page > .shell:first-child { padding-top: clamp(14px, 2vw, 22px); }

    /* ให้การ์ดบทความยืดเต็มพื้นที่ที่เหลือ ไม่ทิ้งช่องว่างขาวก่อนแถบท้าย */
    .page > .shell { flex: 1 1 auto; display: flex; flex-direction: column; }

    /* แถบนำทางย้อนกลับ */
    /* เส้นทางย้อนกลับบนแถบเมนู — เว้นจากโลโก้ด้วยเส้นคั่น */
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
       5. บทความประกาศ
       ══════════════════════════════════════════════════════════ */
    /* ประกาศอยู่กลางหน้า คอลัมน์เดียว */
    .article-wrap {
      width: 100%;
      margin-inline: auto;
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
    }

    /* การ์ดยืดลงมาเกือบชิดแถบท้าย แม้เนื้อหาสั้น */
    .article {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      border: 1px solid var(--line);
      border-radius: var(--r-lg);
      background: var(--surface);
      box-shadow: var(--sh-1);
    }

    /* ══════════════════════════════════════════════════════════
       ฮีโร่ — ภาพปกเต็มความกว้าง หัวข้อทับบนภาพ
       ══════════════════════════════════════════════════════════ */
    /* สัดส่วน 16:9 ตรงกับที่ครอปไว้ในหน้าแอดมิน (จำกัดความสูงบนจอกว้าง) */
    /* 16:9 เต็มความกว้าง ไม่จำกัดความสูง เพื่อให้เห็นภาพตรงกับที่ครอปไว้ทุกพิกเซล */
    /* ภาพปกกินความกว้างเต็มจอ ถ้าปล่อยตาม 3:1 จะสูงเกือบเต็มหน้า
       จึงคุมไม่ให้เกิน 46% ของความสูงจอจริง (var(--screen-h) หักซูมแล้ว) */
    .hero {
      position: relative;
      display: flex;
      align-items: flex-end;
      aspect-ratio: 3 / 1;
      max-height: calc(var(--screen-h) * 0.46);
      overflow: hidden;
      background: var(--navy-deep);
    }

    /* ไม่มีภาพปก — ใช้พื้นน้ำเงินแบรนด์แทน */
    /* ไม่มีภาพปก — ไม่ต้องสูงเท่า 16:9 */
    .hero.no-cover {
      aspect-ratio: auto;
      min-height: clamp(150px, 20vw, 230px);
      background: linear-gradient(135deg, var(--navy-deep), var(--navy));
    }

    .hero-photo {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* ไล่เฉดมืดจากล่างขึ้นบน ให้ตัวอักษรอ่านออกทุกภาพ */
    .hero-veil {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(9, 12, 18, 0.92), rgba(9, 12, 18, 0.55) 55%, rgba(9, 12, 18, 0.25));
    }

    .hero.no-cover .hero-veil { background: none; }

    .hero-inner {
      position: relative;
      z-index: 1;
      padding-block: clamp(18px, 2.6vw, 34px);
      color: #ffffff;
    }

    .hero-meta {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 10px;
    }

    /* ป้ายหมวดแบบเส้นขอบ (อ้างอิงสไตล์ tu.ac.th) */
    .cat-pill {
      display: inline-flex;
      align-items: center;
      height: 22px;
      padding: 0 9px;
      border: 1px solid var(--news-gold);
      border-radius: 3px;
      color: var(--news-gold);
      font-size: 0.9375rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .hero-date {
      color: rgba(255, 255, 255, 0.88);
      font-size: 1.125rem;
    }

    .hero-flag {
      display: inline-flex;
      align-items: center;
      height: 20px;
      padding: 0 8px;
      border-radius: var(--r-full);
      background: rgba(255, 255, 255, 0.18);
      color: #ffffff;
      font-size: 0.9375rem;
      font-weight: 600;
    }

    .hero-flag.is-pin { background: rgba(217, 195, 116, 0.28); color: var(--news-gold); }

    .hero h1 {
      max-width: 24ch;
      color: #ffffff;
      font-size: clamp(1.75rem, 4.2vw, 2.75rem);
      font-weight: 700;
      line-height: 1.3;
      letter-spacing: -0.015em;
      text-wrap: balance;
      text-shadow: 0 1px 12px rgba(0, 0, 0, 0.3);
    }

    /* แถบไล่สีแบรนด์ใต้ฮีโร่ */
    .accent-bar {
      height: 6px;
      background: var(--navy);
    }

    .article-body { flex: 1 1 auto; padding: clamp(18px, 2.4vw, 30px); }

    .article-meta {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 7px;
      margin-bottom: 10px;
    }

    .tag {
      display: inline-flex;
      align-items: center;
      height: 22px;
      padding: 0 9px;
      border-radius: var(--r-full);
      background: var(--surface-3);
      color: var(--muted);
      font-size: 0.9375rem;
      font-weight: 500;
    }

    .tag-cat { background: var(--navy-soft); color: var(--navy-ink); font-weight: 600; }
    .tag-new { background: var(--green-soft); color: var(--green); font-weight: 600; }
    .tag-pin { background: var(--amber-soft); color: var(--amber); font-weight: 600; }
    .meta-date { color: var(--faint); font-size: 1.125rem; }

    .article h1 {
      font-size: clamp(1.75rem, 3.1vw, 2.25rem);
      font-weight: 700;
      line-height: 1.35;
      letter-spacing: -0.015em;
      text-wrap: balance;
    }

    /* เนื้อหา — จำกัดความยาวบรรทัดให้อ่านสบาย และวางกลางคอลัมน์ */
    .article-text {
      max-width: 68ch;
      margin-inline: auto;
      color: var(--ink-2);
      font-size: 1.5rem;
      line-height: 1.8;
      white-space: pre-wrap;
      text-wrap: pretty;
    }

    /* ── ทะเบียนประกาศจาก Excel — ต่อท้ายรายละเอียดประกาศพิเศษ ── */
    .register-section {
      margin-top: clamp(28px, 3.4vw, 44px);
      padding-top: clamp(22px, 2.8vw, 34px);
      border-top: 1px solid var(--line);
    }

    .register-head {
      display: flex;
      align-items: flex-end;
      justify-content: flex-end;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }

    .register-search {
      width: min(100%, 500px);
      height: 56px;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0 17px;
      border: 1px solid var(--line-2);
      border-radius: var(--r-sm);
      background: var(--surface);
      color: var(--muted);
    }

    .register-search:focus-within {
      border-color: var(--navy);
      box-shadow: 0 0 0 3px rgba(7, 58, 117, 0.1);
    }

    .register-search svg { width: 24px; height: 24px; flex: 0 0 auto; }

    .register-search input {
      width: 100%;
      min-width: 0;
      border: 0;
      outline: 0;
      background: transparent;
      font-size: 1.125rem;
    }

    .register-search input::placeholder { color: var(--faint); }

    .register-error {
      margin-bottom: 12px;
      padding: 14px 16px;
      border: 1px solid var(--line-2);
      border-left: 4px solid var(--navy);
      background: var(--surface-2);
      color: var(--ink-2);
      font-size: 1.125rem;
    }

    .register-table-wrap {
      width: 100%;
      overflow-x: auto;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
      scrollbar-width: thin;
      scrollbar-color: var(--line-2) transparent;
    }

    .register-table {
      width: 100%;
      min-width: 1360px;
      border-collapse: separate;
      border-spacing: 0;
      table-layout: fixed;
      font-size: 1.125rem;
    }

    .register-table th {
      padding: 11px 12px;
      border-right: 1px solid var(--line);
      border-bottom: 1px solid var(--line-2);
      background: var(--surface-2);
      color: var(--ink-2);
      font-size: 1rem;
      font-weight: 700;
      line-height: 1.45;
      text-align: left;
      vertical-align: middle;
    }

    .register-table td {
      padding: 13px 12px;
      border-right: 1px solid var(--line);
      border-bottom: 1px solid var(--line);
      color: var(--ink-2);
      line-height: 1.55;
      vertical-align: top;
      overflow-wrap: anywhere;
    }

    .register-table th:last-child,
    .register-table td:last-child { border-right: 0; }
    .register-table tbody tr:last-child td { border-bottom: 0; }
    .register-table tbody tr:nth-child(even) td { background: #fbfcfe; }
    .register-table tbody tr:hover td { background: var(--navy-soft); }

    .register-col-sequence { width: 66px; text-align: center !important; }
    .register-col-number { width: 116px; }
    .register-col-subject { width: 360px; }
    .register-col-recipient { width: 180px; }
    .register-col-owner { width: 160px; }
    .register-col-department { width: 145px; }
    .register-col-date { width: 122px; white-space: nowrap; }
    .register-col-link { width: 130px; text-align: center !important; }
    .register-subject { color: var(--ink); font-weight: 600; }

    .register-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      min-height: 44px;
      padding: 0 15px;
      border: 1px solid var(--navy);
      border-radius: var(--r-sm);
      color: var(--navy);
      font-size: 1.0625rem;
      font-weight: 600;
      white-space: nowrap;
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .register-link:hover { background: var(--navy); color: #ffffff; }
    .register-link svg { width: 20px; height: 20px; }
    .register-no-link { color: var(--faint); }

    .register-no-result {
      padding: 32px 18px;
      color: var(--muted);
      font-size: 1.125rem;
      text-align: center;
    }

    /* ── ไฟล์แนบ ── */
    .files {
      margin-top: 26px;
      padding-top: 20px;
      border-top: 1px solid var(--line);
    }

    .files h2 {
      margin-bottom: 12px;
      font-size: 1.375rem;
      font-weight: 700;
    }

    .file-list { display: grid; gap: 8px; }

    .file {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 13px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface-2);
      transition: border-color var(--fast) var(--ease), background var(--fast) var(--ease);
    }

    .file:hover { border-color: var(--navy); background: var(--navy-soft); }

    .file-icon {
      width: 44px;
      height: 44px;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      color: var(--muted);
    }

    .file-icon img { width: 100%; height: 100%; object-fit: contain; }
    .file-icon svg { width: 34px; height: 34px; }

    .file-info { flex: 1 1 auto; min-width: 0; }

    .file-info strong {
      display: block;
      overflow: hidden;
      font-size: 1.1875rem;
      font-weight: 600;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .file-info span { color: var(--faint); font-size: 1rem; }

    .file-go {
      flex: 0 0 auto;
      color: var(--faint);
    }

    .file-go svg { width: 22px; height: 22px; }
    .file:hover .file-go { color: var(--navy-ink); }

    /* ── ภาพประกอบ — แถวละ 4 ── */
    /* จัดกึ่งกลางเสมอ ไม่ว่าจะมี 1, 2, 3 หรือ 4 ภาพ */
    .photo-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 8px;
    }

    /* ขนาดภาพปรับตามจำนวน — ยิ่งน้อยยิ่งใหญ่ (quantity query ใช้ได้ทุกเบราว์เซอร์) */
    .photo-grid .photo { flex: 0 1 calc(25% - 6px); }

    /* 1 ภาพ — ใหญ่แต่ไม่เต็มความกว้าง */
    .photo-grid .photo:only-child { flex-basis: 46%; }

    /* 2 ภาพ */
    .photo-grid .photo:first-child:nth-last-child(2),
    .photo-grid .photo:first-child:nth-last-child(2) ~ .photo { flex-basis: calc(50% - 4px); }

    /* 3 ภาพ */
    .photo-grid .photo:first-child:nth-last-child(3),
    .photo-grid .photo:first-child:nth-last-child(3) ~ .photo { flex-basis: calc(33.333% - 6px); }

    .photo {
      position: relative;
      display: block;
      aspect-ratio: 4 / 3;
      overflow: hidden;
      padding: 0;
      border: 1px solid var(--line);
      background: var(--surface-3);
      transition: border-color var(--fast) var(--ease), transform var(--fast) var(--ease);
    }

    .photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform var(--base) var(--ease);
    }

    /* ภาพเดียว = เนื้อหาหลักของประกาศ (เช่นตารางเมนูอาหาร)
       แสดงเต็มภาพ ไม่ครอปตามกรอบ 4:3 ไม่งั้นข้อมูลด้านล่างหาย */
    .photo-grid .photo:only-child {
      aspect-ratio: auto;
      background: var(--surface);
    }

    .photo-grid .photo:only-child img { height: auto; object-fit: contain; }
    .photo-grid .photo:only-child:hover img { transform: none; }

    .photo:hover { border-color: var(--navy); }
    .photo:hover img { transform: scale(1.05); }

    .photo-zoom {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      background: rgba(9, 12, 18, 0.42);
      color: #ffffff;
      opacity: 0;
      transition: opacity var(--fast) var(--ease);
    }

    .photo-zoom svg { width: 22px; height: 22px; }
    .photo:hover .photo-zoom, .photo:focus-visible .photo-zoom { opacity: 1; }

    @media (max-width: 640px) {
      .photo-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    /* ── โมดัลดูภาพขยาย ── */
    .lightbox {
      position: fixed;
      inset: 0;
      z-index: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: clamp(8px, 1.4vw, 18px);
      background: rgba(9, 12, 18, 0.94);
    }

    .lightbox[hidden] { display: none; }

    .lb-stage {
      max-width: 100%;
      max-height: 100%;
      margin: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
    }

    .lb-stage img {
      max-width: 100%;
      max-height: calc(var(--screen-h) - 74px);
      object-fit: contain;
      transform-origin: center center;
      transition: transform 120ms var(--ease);
      cursor: grab;
    }

    /* ลากดูภาพได้ตลอด ไม่ต้องซูมก่อน */
    .lb-stage img.is-dragging { cursor: grabbing; transition: none; }

    /* คำใบ้ — ตัวหนังสือเปล่า ไม่มีกรอบ */
    .lb-hint {
      position: absolute;
      bottom: 8px;
      left: 50%;
      transform: translateX(-50%);
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.6875rem;
      pointer-events: none;
    }

    .lb-stage figcaption {
      color: rgba(255, 255, 255, 0.82);
      font-size: 0.8125rem;
      text-align: center;
    }

    .lb-close {
      position: absolute;
      top: clamp(10px, 2vw, 20px);
      right: clamp(10px, 2vw, 20px);
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.14);
      color: #ffffff;
      transition: background var(--fast) var(--ease);
    }

    .lb-close:hover { background: rgba(255, 255, 255, 0.28); }
    .lb-close svg { width: 18px; height: 18px; }

    .lb-nav {
      flex: 0 0 auto;
      width: 40px;
      height: 40px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.14);
      color: #ffffff;
      transition: background var(--fast) var(--ease);
    }

    .lb-nav:hover { background: rgba(255, 255, 255, 0.28); }
    .lb-nav svg { width: 20px; height: 20px; }
    .lb-nav[hidden] { visibility: hidden; display: grid; }

    /* ══════════════════════════════════════════════════════════
       7. ไม่พบประกาศ
       ══════════════════════════════════════════════════════════ */
    .missing {
      max-width: 460px;
      margin: clamp(30px, 6vw, 70px) auto;
      padding: 34px 28px;
      border: 1px solid var(--line);
      border-radius: var(--r-lg);
      background: var(--surface);
      box-shadow: var(--sh-1);
      text-align: center;
    }

    .missing svg {
      width: 34px;
      height: 34px;
      margin: 0 auto 14px;
      color: var(--faint);
    }

    .missing h1 {
      margin-bottom: 6px;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .missing p {
      margin-bottom: 20px;
      color: var(--muted);
      font-size: 1.1875rem;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      height: 50px;
      padding: 0 22px;
      border-radius: var(--r-sm);
      background: var(--navy);
      color: var(--on-navy);
      font-size: 1.1875rem;
      font-weight: 600;
      transition: background var(--fast) var(--ease);
    }

    .btn:hover { background: var(--navy-deep); }
    .btn svg { width: 22px; height: 22px; }

    /* ══════════════════════════════════════════════════════════
       8. FOOTER — เหมือนหน้าแรก
       ══════════════════════════════════════════════════════════ */
    .site-footer {
      border-top: 1px solid var(--line);
      background: var(--surface);
      padding-block: 12px;
      margin-top: 14px;
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
       9. RESPONSIVE
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
      .pop-item { padding: 8px 10px; font-size: 0.8125rem; }
      .pop-item img { width: 18px; height: 13px; }
      .pop-item svg { width: 16px; height: 16px; }
      .icon-btn { width: 38px; height: 38px; }
      .icon-btn svg { width: 19px; height: 19px; }
      .lang-btn { gap: 6px; height: 38px; padding: 0 11px; font-size: 0.875rem; }
      .lang-btn img { width: 20px; height: 14px; }
      .lang-btn svg { width: 11px; height: 11px; }
      .crumb { gap: 8px; font-size: 0.8125rem; }
      .cat-pill, .hero-flag, .tag { font-size: 0.6875rem; }
      .hero-date, .meta-date { font-size: 0.8125rem; }
      .hero h1 { font-size: clamp(1.25rem, 3vw, 2rem); }
      .article h1 { font-size: clamp(1.25rem, 2.2vw, 1.625rem); }
      .article-text { font-size: 1.125rem; line-height: 1.9; }
      .register-search { width: min(100%, 360px); height: 40px; gap: 9px; padding: 0 12px; }
      .register-search svg { width: 17px; height: 17px; }
      .register-search input, .register-error, .register-no-result { font-size: 0.8125rem; }
      .register-table { font-size: 0.79rem; }
      .register-table th { font-size: 0.73rem; }
      .register-link { min-height: 32px; padding: 0 11px; font-size: 0.75rem; }
      .register-link svg { width: 14px; height: 14px; }
      .files h2 { font-size: 1rem; }
      .file-icon { width: 32px; height: 32px; }
      .file-icon svg { width: 24px; height: 24px; }
      .file-info strong { font-size: 0.875rem; }
      .file-info span, .footer-row { font-size: 0.75rem; }
      .file-go svg { width: 16px; height: 16px; }
      .missing h1 { font-size: 1.0625rem; }
      .missing p, .btn { font-size: 0.875rem; }
      .btn { height: 36px; padding: 0 16px; }
      .btn svg { width: 16px; height: 16px; }
    }

    /* จอแคบ — ซ่อนเส้นทางย้อนกลับบนแถบเมนู ไม่ให้เบียดโลโก้ */
    @media (max-width: 860px) {
      .crumb { display: none; }
      .register-head { align-items: stretch; }
      .register-search { width: 100%; max-width: none; }
    }

    /* ══════════════════════════════════════════════════════════
       10. ลดการเคลื่อนไหว
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

      <?php if ($match) { ?>
        <!-- เส้นทางย้อนกลับ อยู่บนแถบเมนู -->
        <nav class="crumb" aria-label="breadcrumb">
          <a href="index.php" data-i18n="menuHome">หน้าแรก</a>
          <span class="sep">»</span>
          <span class="here" data-crumb-title
                data-name-th="<?php echo announcement_h(announcement_localized_title($match, 'th')); ?>"
                data-name-en="<?php echo announcement_h(announcement_localized_title($match, 'en')); ?>"
                data-name-my="<?php echo announcement_h(announcement_localized_title($match, 'my')); ?>"><?php echo announcement_h(announcement_localized_title($match, 'th')); ?></span>
        </nav>
      <?php } ?>

      <div class="header-tools">
        <div class="pop">
          <button class="icon-btn" id="menuBtn" type="button" aria-label="เมนู" aria-expanded="false" aria-haspopup="true">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          </button>
          <div class="pop-panel" id="menuPanel" role="menu">
            <a class="pop-item" href="index.php" role="menuitem">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 11 9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
              <span data-i18n="menuHome">หน้าแรก</span>
            </a>
            <a class="pop-item" href="rules.php" role="menuitem">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 2.5v3.2A1.3 1.3 0 0 0 15.3 7h3.2M9 12.5h6M9 16h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span data-i18n="menuRules">กฎระเบียบข้อบังคับ</span>
            </a>
            <div class="pop-divider"></div>
            <a class="pop-item" href="announcements_admin.php" role="menuitem">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span data-i18n="menuAdmin">เข้าสู่ระบบผู้ดูแล</span>
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
      <?php if (!$match) { ?>
        <!-- ── ไม่พบประกาศ ── -->
        <div class="shell">
          <div class="missing">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M12 7.5v5m0 3.2v.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <h1 data-i18n="notFoundTitle">ไม่พบประกาศ</h1>
            <p data-i18n="notFound">ประกาศนี้อาจถูกลบไปแล้ว หรือลิงก์ไม่ถูกต้อง</p>
            <a class="btn" href="index.php">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span data-i18n="backHome">กลับหน้าแรก</span>
            </a>
          </div>
        </div>

      <?php } else { ?>
        <!-- ── ฮีโร่: ภาพปกเต็มความกว้าง + หัวข้อทับบนภาพ ── -->
        <section class="hero<?php echo $coverUrl === '' ? ' no-cover' : ''; ?>" data-news
          data-title-th="<?php echo announcement_h(announcement_localized_title($match, 'th')); ?>"
          data-title-en="<?php echo announcement_h(announcement_localized_title($match, 'en')); ?>"
          data-title-my="<?php echo announcement_h(announcement_localized_title($match, 'my')); ?>"
          data-body-th="<?php echo announcement_h(announcement_localized_body($match, 'th')); ?>"
          data-body-en="<?php echo announcement_h(announcement_localized_body($match, 'en')); ?>"
          data-body-my="<?php echo announcement_h(announcement_localized_body($match, 'my')); ?>">

          <?php if ($coverUrl !== '') { ?>
            <img class="hero-photo" src="<?php echo announcement_h($coverUrl); ?>" alt="">
          <?php } ?>

          <div class="hero-veil" aria-hidden="true"></div>

          <div class="shell hero-inner">
            <div class="hero-meta">
              <?php if ($viewCatObj) { ?>
                <span class="cat-pill" data-cat-name
                      data-name-th="<?php echo announcement_h(announcement_category_name($viewCatObj, 'th')); ?>"
                      data-name-en="<?php echo announcement_h(announcement_category_name($viewCatObj, 'en')); ?>"
                      data-name-my="<?php echo announcement_h(announcement_category_name($viewCatObj, 'my')); ?>"><?php echo announcement_h(announcement_category_name($viewCatObj, 'th')); ?></span>
              <?php } ?>
              <span class="hero-date"><?php echo announcement_h(announcement_format_date($match['created_at'])); ?></span>
              <?php if ($isNew) { ?>
                <span class="hero-flag" data-i18n="tagNew">ใหม่</span>
              <?php } ?>
              <?php if (announcement_is_pinned($match)) { ?>
                <span class="hero-flag is-pin" data-i18n="tagPinned">ปักหมุด</span>
              <?php } ?>
            </div>

            <h1 data-title><?php echo announcement_h(announcement_localized_title($match, 'th')); ?></h1>
          </div>
        </section>

        <div class="accent-bar" aria-hidden="true"></div>

        <!-- ── เส้นทางย้อนกลับ ── -->
        <div class="shell">
        <div class="article-wrap">
          <article class="article">
            <div class="article-body">
              <div class="article-text" data-body><?php echo announcement_h(announcement_localized_body($match, 'th')); ?></div>

              <?php if ($isRegisterAnnouncement && $announcementRegister) { ?>
                <section class="register-section" aria-label="ทะเบียนประกาศประจำปี พ.ศ. 2569">
                  <div class="register-head">
                    <label class="register-search" for="registerSearch">
                      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.7" cy="10.7" r="6.2" stroke="currentColor" stroke-width="1.8"/><path d="m15.5 15.5 4.2 4.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                      <input id="registerSearch" type="search" autocomplete="off"
                             placeholder="ค้นหาเลขที่ เรื่อง หน่วยงาน หรือผู้จัดทำ"
                             data-placeholder-th="ค้นหาเลขที่ เรื่อง หน่วยงาน หรือผู้จัดทำ"
                             data-placeholder-en="Search number, subject, department or owner"
                             data-placeholder-my="နံပါတ်၊ အကြောင်းအရာ၊ ဌာန သို့မဟုတ် တာဝန်ခံကို ရှာပါ">
                    </label>
                  </div>

                  <?php if (!$announcementRegister['ok']) { ?>
                    <div class="register-error" role="alert"><?php echo announcement_h($announcementRegister['error']); ?></div>
                  <?php } ?>

                  <?php if (count($registerRows) > 0) { ?>
                    <div class="register-table-wrap" tabindex="0" aria-label="ตารางทะเบียนประกาศ เลื่อนซ้ายขวาเพื่อดูข้อมูลทั้งหมด">
                      <table class="register-table">
                        <thead>
                          <tr>
                            <th class="register-col-sequence" scope="col"><?php echo announcement_h($announcementRegister['headers']['A']); ?></th>
                            <th class="register-col-number" scope="col"><?php echo announcement_h($announcementRegister['headers']['B']); ?></th>
                            <th class="register-col-subject" scope="col"><?php echo announcement_h($announcementRegister['headers']['C']); ?></th>
                            <th class="register-col-recipient" scope="col"><?php echo announcement_h($announcementRegister['headers']['D']); ?></th>
                            <th class="register-col-owner" scope="col"><?php echo announcement_h($announcementRegister['headers']['E']); ?></th>
                            <th class="register-col-department" scope="col"><?php echo announcement_h($announcementRegister['headers']['F']); ?></th>
                            <th class="register-col-date" scope="col"><?php echo announcement_h($announcementRegister['headers']['G']); ?></th>
                            <th class="register-col-link" scope="col"><?php echo announcement_h($announcementRegister['headers']['H']); ?></th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($registerRows as $registerRow) { ?>
                            <?php $registerSearchText = implode(' ', array($registerRow['sequence'], $registerRow['number'], $registerRow['subject'], $registerRow['recipient'], $registerRow['owner'], $registerRow['department'], $registerRow['date'])); ?>
                            <tr data-register-row data-search="<?php echo announcement_h($registerSearchText); ?>">
                              <td class="register-col-sequence"><?php echo announcement_h($registerRow['sequence']); ?></td>
                              <td class="register-col-number"><?php echo announcement_h($registerRow['number']); ?></td>
                              <td class="register-col-subject"><span class="register-subject"><?php echo announcement_h($registerRow['subject']); ?></span></td>
                              <td class="register-col-recipient"><?php echo announcement_h($registerRow['recipient'] !== '' ? $registerRow['recipient'] : '—'); ?></td>
                              <td class="register-col-owner"><?php echo announcement_h($registerRow['owner'] !== '' ? $registerRow['owner'] : '—'); ?></td>
                              <td class="register-col-department"><?php echo announcement_h($registerRow['department'] !== '' ? $registerRow['department'] : '—'); ?></td>
                              <td class="register-col-date"><?php echo announcement_h($registerRow['date'] !== '' ? $registerRow['date'] : '—'); ?></td>
                              <td class="register-col-link">
                                <?php /* link_url มาจาก helper แล้ว — ลิงก์ที่แอดมินกรอก > ไฟล์ที่แอดมินแนบ > ไฮเปอร์ลิงก์เดิมใน Excel */ ?>
                                <?php if ($registerRow['link_url'] !== '') { ?>
                                  <a class="register-link" href="<?php echo announcement_h($registerRow['link_url']); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo announcement_h($registerRow['subject']); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 5h5v5m0-5-7 7M18 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span data-i18n="registerOpenLink">เปิดลิงก์</span>
                                  </a>
                                <?php } else { ?>
                                  <span class="register-no-link">—</span>
                                <?php } ?>
                              </td>
                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                    <p class="register-no-result" id="registerNoResult" data-i18n="registerNoResult" hidden>ไม่พบประกาศที่ตรงกับคำค้นหา</p>
                  <?php } ?>
                </section>
              <?php } ?>

              <?php if (count($imageFiles) > 0) { ?>
                <!-- ── ภาพประกอบ — แถวละ 4 ภาพ กดเพื่อขยาย ── -->
                <div class="files">
                  <h2 data-i18n="photos">ภาพประกอบ</h2>
                  <div class="photo-grid">
                    <?php foreach ($imageFiles as $imgIndex => $attachment) { ?>
                      <?php $imgUrl = announcement_attachment_url($match['id'], $attachment); ?>
                      <button class="photo" type="button"
                              data-photo="<?php echo announcement_h($imgUrl); ?>"
                              data-photo-name="<?php echo announcement_h($attachment['original_name']); ?>"
                              aria-label="<?php echo announcement_h($attachment['original_name']); ?>">
                        <img src="<?php echo announcement_h($imgUrl); ?>" alt="<?php echo announcement_h($attachment['original_name']); ?>" loading="lazy">
                        <span class="photo-zoom" aria-hidden="true">
                          <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.9"/><path d="M11 8.5v5M8.5 11h5m2.5 5 4.5 4.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                        </span>
                      </button>
                    <?php } ?>
                  </div>
                </div>
              <?php } ?>

              <?php if (count($docFiles) > 0) { ?>
                <div class="files">
                  <h2 data-i18n="attachments">เอกสารแนบ</h2>
                  <div class="file-list">
                    <?php foreach ($docFiles as $attachment) { ?>
                      <?php
                        $fileKind = announcement_file_kind($attachment);
                        $fileIconUrl = announcement_file_icon_url($fileKind);
                        $fileSize = isset($attachment['size']) ? announcement_format_file_size($attachment['size']) : '';
                      ?>
                      <a class="file" href="<?php echo announcement_h(announcement_attachment_url($match['id'], $attachment)); ?>" target="_blank" rel="noopener noreferrer">
                        <span class="file-icon">
                          <?php if ($fileIconUrl !== '') { ?>
                            <img src="<?php echo announcement_h($fileIconUrl); ?>" alt="">
                          <?php } else { ?>
                            <?php echo announcement_file_icon_svg($fileKind); ?>
                          <?php } ?>
                        </span>
                        <span class="file-info">
                          <strong><?php echo announcement_h($attachment['original_name']); ?></strong>
                          <span><?php echo announcement_h($fileSize); ?></span>
                        </span>
                        <span class="file-go">
                          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 5h5v5m0-5-7 7M18 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                      </a>
                    <?php } ?>
                  </div>
                </div>
              <?php } ?>

            </div>
          </article>

        </div>
        </div>
      <?php } ?>
  </main>

  <!-- ══════════════════ MODAL: ดูภาพขยาย ══════════════════ -->
  <div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="ดูภาพ" hidden>
    <button class="lb-close" id="lbClose" type="button" aria-label="ปิด">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>

    <button class="lb-nav is-prev" id="lbPrev" type="button" aria-label="ภาพก่อนหน้า">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <figure class="lb-stage">
      <img id="lbImg" src="" alt="">
      <figcaption id="lbCap"></figcaption>
    </figure>

    <span class="lb-hint" id="lbHint" data-i18n="zoomHint">เลื่อนเมาส์ซูม · ลากเลื่อนภาพ · ดับเบิลคลิกรีเซ็ต</span>

    <button class="lb-nav is-next" id="lbNext" type="button" aria-label="ภาพถัดไป">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
  </div>

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
        menuHome: 'หน้าแรก', menuRules: 'กฎระเบียบข้อบังคับ', menuAdmin: 'เข้าสู่ระบบผู้ดูแล',
        backHome: 'กลับหน้าแรก',
        notFoundTitle: 'ไม่พบประกาศ', notFound: 'ประกาศนี้อาจถูกลบไปแล้ว หรือลิงก์ไม่ถูกต้อง',
        tagNew: 'ใหม่', tagPinned: 'ปักหมุด',
        attachments: 'เอกสารแนบ', photos: 'ภาพประกอบ', zoomHint: 'เลื่อนเมาส์ซูม · ลากเลื่อนภาพ · ดับเบิลคลิกรีเซ็ต',
        registerOpenLink: 'เปิดลิงก์',
        registerNoResult: 'ไม่พบประกาศที่ตรงกับคำค้นหา',
        footerBy: 'พัฒนาโดยฝ่าย IT'
      },
      en: {
        menuHome: 'Home', menuRules: 'Work rules and regulations', menuAdmin: 'Admin sign in',
        backHome: 'Back to home',
        notFoundTitle: 'Announcement not found', notFound: 'It may have been removed, or the link is incorrect.',
        tagNew: 'New', tagPinned: 'Pinned',
        attachments: 'Attachments', photos: 'Photos', zoomHint: 'Scroll to zoom · drag to pan · double-click to reset',
        registerOpenLink: 'Open link',
        registerNoResult: 'No announcements match your search',
        footerBy: 'Built by IT'
      },
      my: {
        menuHome: 'ပင်မ', menuRules: 'လုပ်ငန်းစည်းမျဉ်းများ', menuAdmin: 'စီမံခန့်ခွဲသူ ဝင်ရန်',
        backHome: 'ပင်မသို့ ပြန်ရန်',
        notFoundTitle: 'ကြေညာချက် မတွေ့ပါ', notFound: 'ဖျက်ပြီးဖြစ်နိုင်သည် သို့မဟုတ် လင့်ခ် မမှန်ပါ။',
        tagNew: 'အသစ်', tagPinned: 'ပင်တွဲထား',
        attachments: 'ပူးတွဲဖိုင်များ', photos: 'ဓာတ်ပုံများ', zoomHint: 'ဇူးမ်ရန် လှိမ့်ပါ · ရွှေ့ရန် ဆွဲပါ · ပြန်စရန် နှစ်ချက်နှိပ်ပါ',
        registerOpenLink: 'လင့်ခ်ဖွင့်ရန်',
        registerNoResult: 'ရှာဖွေမှုနှင့် ကိုက်ညီသော ကြေညာချက်မရှိပါ',
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

      /* หัวข้อ/เนื้อหาประกาศ
         ค่าทุกภาษาเก็บไว้ที่ [data-news] แต่ตัวเนื้อหา (.article-text) อยู่ "นอก" section นั้น
         จึงต้องหาจากทั้งหน้า ไม่ใช่หาเฉพาะในตัว art (เดิมหาไม่เจอ เนื้อหาเลยค้างเป็นภาษาไทย) */
      var art = $('[data-news]');
      if (art) {
        var title = art.getAttribute('data-title-' + lang) || art.getAttribute('data-title-th');
        var body = art.getAttribute('data-body-' + lang) || art.getAttribute('data-body-th');
        var tEl = $('[data-title]', art) || $('[data-title]');
        var bEl = $('[data-body]', art) || $('[data-body]');
        if (tEl && title) { tEl.textContent = title; }
        if (bEl) { bEl.textContent = body || ''; }
      }

      /* ชื่อแท็บ + หัวข้อใน breadcrumb + หัวข้อประกาศอื่น */
      $$('[data-cat-name], [data-crumb-title], [data-rel-title]').forEach(function (el) {
        var name = el.getAttribute('data-name-' + lang) || el.getAttribute('data-name-th');
        if (name) { el.textContent = name; }
      });


      var flag = $('#langFlag');
      if (flag) { flag.src = 'img/flags/' + lang + '.png'; }

      var registerSearch = $('#registerSearch');
      if (registerSearch) {
        registerSearch.placeholder = registerSearch.getAttribute('data-placeholder-' + lang) || registerSearch.getAttribute('data-placeholder-th');
      }

      $$('#langPanel .pop-item').forEach(function (b) {
        b.setAttribute('aria-current', b.getAttribute('data-lang') === lang ? 'true' : 'false');
      });
    }

    /* ══════════════════════════════════════════════════════════
       4. โมดัลดูภาพขยาย
       ══════════════════════════════════════════════════════════ */
    var photos = $$('[data-photo]');
    var lb = $('#lightbox');
    var lbImg = $('#lbImg');
    var lbCap = $('#lbCap');
    var lbPrev = $('#lbPrev');
    var lbNext = $('#lbNext');
    var lbAt = 0;

    function lbShow(i) {
      if (!photos.length) { return; }
      if (i < 0) { i = photos.length - 1; }
      if (i >= photos.length) { i = 0; }
      lbAt = i;

      var btn = photos[i];
      lbImg.src = btn.getAttribute('data-photo');
      lbImg.alt = btn.getAttribute('data-photo-name') || '';
      lbCap.textContent = btn.getAttribute('data-photo-name') || '';
      zoomReset();

      /* ซ่อนปุ่มเลื่อนเมื่อมีภาพเดียว */
      var many = photos.length > 1;
      lbPrev.hidden = !many;
      lbNext.hidden = !many;
    }

    /* ── ซูมด้วยล้อเมาส์ + ลากดู ── */
    var zScale = 1;
    var zX = 0;
    var zY = 0;
    var zMin = 1;
    var zMax = 5;

    function zoomApply() {
      lbImg.style.transform = 'translate(' + zX + 'px,' + zY + 'px) scale(' + zScale + ')';
      lbImg.classList.toggle('is-zoomed', zScale > 1);
    }

    function zoomReset() {
      zScale = 1;
      zX = 0;
      zY = 0;
      zoomApply();
    }

    if (lbImg) {
      lbImg.addEventListener('wheel', function (e) {
        e.preventDefault();

        var prev = zScale;
        var step = e.deltaY < 0 ? 1.15 : 1 / 1.15;
        zScale = Math.min(zMax, Math.max(zMin, zScale * step));

        if (zScale === zMin) {
          zX = 0;
          zY = 0;
        } else {
          /* ซูมเข้าหาตำแหน่งเคอร์เซอร์ */
          var r = lbImg.getBoundingClientRect();
          var cx = e.clientX - (r.left + r.width / 2);
          var cy = e.clientY - (r.top + r.height / 2);
          var k = zScale / prev;
          zX = cx - (cx - zX) * k;
          zY = cy - (cy - zY) * k;
        }

        zoomApply();
      }, { passive: false });

      lbImg.addEventListener('dblclick', zoomReset);

      /* ลากดูเมื่อซูมอยู่ */
      var dragging = false;
      var sx = 0;
      var sy = 0;

      lbImg.addEventListener('pointerdown', function (e) {
        e.preventDefault();
        dragging = true;
        sx = e.clientX - zX;
        sy = e.clientY - zY;
        lbImg.classList.add('is-dragging');
        lbImg.setPointerCapture(e.pointerId);
      });

      lbImg.addEventListener('pointermove', function (e) {
        if (!dragging) { return; }
        zX = e.clientX - sx;
        zY = e.clientY - sy;
        zoomApply();
      });

      lbImg.addEventListener('pointerup', function () {
        dragging = false;
        lbImg.classList.remove('is-dragging');
      });
    }

    function lbOpen(i) {
      if (!lb) { return; }
      lbShow(i);
      lb.hidden = false;
      document.body.style.overflow = 'hidden';
      $('#lbClose').focus();
    }

    function lbClose() {
      if (!lb) { return; }
      lb.hidden = true;
      lbImg.src = '';
      document.body.style.overflow = '';
    }

    photos.forEach(function (btn, i) {
      btn.addEventListener('click', function () { lbOpen(i); });
    });

    if (lb) {
      $('#lbClose').addEventListener('click', lbClose);
      lbPrev.addEventListener('click', function () { lbShow(lbAt - 1); });
      lbNext.addEventListener('click', function () { lbShow(lbAt + 1); });

      /* คลิกพื้นหลังเพื่อปิด */
      lb.addEventListener('click', function (e) {
        if (e.target === lb || e.target.closest('.lb-stage') === null && !e.target.closest('.lb-nav') && !e.target.closest('.lb-close')) {
          lbClose();
        }
      });
    }

    document.addEventListener('keydown', function (e) {
      if (!lb || lb.hidden) { return; }
      if (e.key === 'Escape') { lbClose(); }
      if (e.key === 'ArrowLeft') { lbShow(lbAt - 1); }
      if (e.key === 'ArrowRight') { lbShow(lbAt + 1); }
    });

    /* ══════════════════════════════════════════════════════════
       5. เมนูป๊อปอัป
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

    /* ค้นหาภายในทะเบียน โดยไม่ตัดตารางเป็นหน้า เพื่อให้เลื่อนอ่านต่อเนื่องลงด้านล่าง */
    function filterRegister() {
      var input = $('#registerSearch');
      if (!input) { return; }

      var query = input.value.trim().toLowerCase();
      var visible = 0;

      $$('[data-register-row]').forEach(function (row) {
        var searchText = (row.getAttribute('data-search') || '').toLowerCase();
        var matches = query === '' || searchText.indexOf(query) > -1;
        row.hidden = !matches;
        if (matches) { visible++; }
      });

      var empty = $('#registerNoResult');
      if (empty) { empty.hidden = visible !== 0; }
    }

    var registerSearchInput = $('#registerSearch');
    if (registerSearchInput) {
      registerSearchInput.addEventListener('input', filterRegister);
    }

    /* ══════════════════════════════════════════════════════════
       6. เริ่มทำงาน
       ══════════════════════════════════════════════════════════ */
    setLang(readLang(), false);
  </script>
</body>

</html>
