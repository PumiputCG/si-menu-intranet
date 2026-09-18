<?php
require_once dirname(__FILE__) . '/no_cache.php';   /* ส่ง header กันแคช ต้องอยู่ก่อน output ใด ๆ */
/* ══════════════════════════════════════════════════════════════
   index.php — SI MENU · Intranet Dashboard (SUPAVUT GROUP)
   จัดทุกอย่างให้พอดี 1 หน้าจอ ไม่ต้องเลื่อน · เขียนแบบ PHP 5.2-safe
   ══════════════════════════════════════════════════════════════ */

require_once dirname(__FILE__) . '/announcement_helpers.php';

$announcements = announcement_read_all();
$newsCategories = announcement_category_read_all();
$categoryCounts = announcement_category_counts($announcements);

/* ── แยก 2 กอง: ประกาศสำคัญ = ปักหมุด · ประกาศทั่วไป = ที่เหลือ ── */
$pinnedItems = array();
$feedItems = array();
foreach ($announcements as $pItem) {
  if (announcement_is_pinned($pItem)) {
    $pinnedItems[] = $pItem;
  } else {
    $feedItems[] = $pItem;
  }
}

/* แท็บของแต่ละช่อง = เฉพาะหมวดที่มีประกาศอยู่จริงในช่องนั้น
   หมวดที่ไม่มีประกาศในช่องนั้นจะไม่ขึ้นแท็บ */
function simenu_used_categories($items, $categories)
{
  $used = array();
  foreach ($items as $usedItem) {
    $usedId = announcement_category_of($usedItem);
    if ($usedId !== '') {
      $used[$usedId] = true;
    }
  }

  $out = array();
  foreach ($categories as $usedCat) {
    if (isset($used[$usedCat['id']])) {
      $out[] = $usedCat;
    }
  }

  return $out;
}

$pinnedCats = simenu_used_categories($pinnedItems, $newsCategories);
$feedCats = simenu_used_categories($feedItems, $newsCategories);

/* ── การ์ดประกาศ 1 ใบ — ใช้ร่วมกันทั้ง 2 ช่อง หน้าตาจึงเหมือนกันเป๊ะ ── */
function simenu_post_card($item)
{
  $cover = announcement_cover_url($item);
  $url = 'announcement_view.php?id=' . urlencode($item['id']);
  ?>
  <a class="post" href="<?php echo $url; ?>"
     data-news data-cat="<?php echo announcement_h(announcement_category_of($item)); ?>"
     data-title-th="<?php echo announcement_h(announcement_localized_title($item, 'th')); ?>"
     data-title-en="<?php echo announcement_h(announcement_localized_title($item, 'en')); ?>"
     data-title-my="<?php echo announcement_h(announcement_localized_title($item, 'my')); ?>"
     data-body-th="<?php echo announcement_h(announcement_excerpt(announcement_localized_body($item, 'th'), 90)); ?>"
     data-body-en="<?php echo announcement_h(announcement_excerpt(announcement_localized_body($item, 'en'), 90)); ?>"
     data-body-my="<?php echo announcement_h(announcement_excerpt(announcement_localized_body($item, 'my'), 90)); ?>">

    <span class="post-thumb">
      <?php if ($cover !== '') { ?>
        <img src="<?php echo announcement_h($cover); ?>" alt="" loading="lazy">
      <?php } else { ?>
        <span class="ph" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5" width="17" height="14" rx="1.5" stroke="currentColor" stroke-width="1.7"/><path d="m4 16 4.5-4.5L13 16l3-3 4 4" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        </span>
      <?php } ?>
      <span class="post-date" aria-hidden="true">
        <strong><?php echo announcement_h(announcement_date_day($item['created_at'])); ?></strong>
        <span><?php echo announcement_h(announcement_date_month_short_th($item['created_at'])); ?></span>
      </span>
    </span>

    <span class="post-text">
      <span class="post-top">
        <b data-title><?php echo announcement_h(announcement_localized_title($item, 'th')); ?></b>
      </span>
      <p data-body><?php echo announcement_h(announcement_excerpt(announcement_localized_body($item, 'th'), 90)); ?></p>
      <span class="post-when"><?php echo announcement_h(announcement_format_date($item['created_at'])); ?></span>
    </span>

    <span class="post-go" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </span>

    <?php /* หมุดมุมขวาบน — ขึ้นเฉพาะประกาศที่ปักหมุดไว้ */ ?>
    <?php if (announcement_is_pinned($item)) { ?>
      <span class="post-pin" title="ปักหมุด" aria-label="ปักหมุด">
        <svg viewBox="0 0 24 24" fill="none"><path d="M7 4h10a1 1 0 0 1 1 1v15l-6-4-6 4V5a1 1 0 0 1 1-1Z" fill="currentColor"/></svg>
      </span>
    <?php } ?>
  </a>
  <?php
}

/* ── แถบพาจิเนท (ลูกศร + จุด) ของแต่ละช่อง ── */
function simenu_pager_foot($key)
{
  ?>
  <div class="card-foot" id="<?php echo $key; ?>Foot" hidden>
    <button class="pg-btn" type="button" data-pager="<?php echo $key; ?>" data-dir="-1" aria-label="หน้าก่อนหน้า">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <div class="dots" id="<?php echo $key; ?>Dots"></div>
    <button class="pg-btn" type="button" data-pager="<?php echo $key; ?>" data-dir="1" aria-label="หน้าถัดไป">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
  </div>
  <?php
}

/* ── ภาพสไลด์หัวหน้า ── */
$galleryImages = array();
foreach (hero_read_all() as $heroSlide) {
  $heroSlideUrl = hero_url($heroSlide);
  if ($heroSlideUrl !== '') {
    $galleryImages[] = $heroSlideUrl;
  }
}
if (count($galleryImages) === 0) {
  $galleryImages[] = 'img/gallery/1.jpg';
}

/* ── ระบบงาน ── */
$appCatRows = array(array('key' => '', 'th' => 'ทั้งหมด', 'en' => 'All', 'my' => 'အားလုံး'));
foreach (app_category_list() as $appCatItem) {
  $appCatRows[] = array(
    'key' => $appCatItem['id'],
    'th' => $appCatItem['th'],
    'en' => $appCatItem['en'],
    'my' => $appCatItem['my']
  );
}

$appRows = array();
$appTextRows = array();
foreach (app_read_all() as $appItem) {
  $appRows[] = array(
    'id' => $appItem['id'],
    'cat' => isset($appItem['cat']) ? $appItem['cat'] : '',
    'img' => app_icon_url($appItem),
    'url' => isset($appItem['url']) ? $appItem['url'] : ''
  );
  $appTextRows[$appItem['id']] = array(
    'th' => array(app_name($appItem, 'th'), app_desc($appItem, 'th')),
    'en' => array(app_name($appItem, 'en'), app_desc($appItem, 'en')),
    'my' => array(app_name($appItem, 'my'), app_desc($appItem, 'my'))
  );
}

$companyMapQuery = 'Supavut Industry Co., Ltd., 44/2 Mu 8, Pong, Bang Lamung, Chon Buri 20150, Thailand';
$companyMapEmbedUrl = 'https://maps.google.com/maps?hl=th&q=' . rawurlencode($companyMapQuery) . '&z=16&output=embed';
$companyMapOpenUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($companyMapQuery);

$mailHr = 'hr.manager@supavut.com';
$mailIt = 'Pumiput.it@supavut.com';

/* ── ผู้ติดต่อ — แก้ชื่อ / อีเมล ได้ที่นี่ที่เดียว ──
   ฝ่ายบุคคลอยู่บนสุดตามลำดับใน array นี้ */
$contacts = array(
  array(
    'dept_th' => 'ฝ่ายบุคคล (HR)',
    'dept_en' => 'Human Resources',
    'dept_my' => 'လူ့စွမ်းအားအရင်းအမြစ် (HR)',
    'name' => 'คุณฐานพัฒน์ พิมายกลาง',
    'mail' => $mailHr
  ),
  array(
    'dept_th' => 'ฝ่าย IT',
    'dept_en' => 'IT Department',
    'dept_my' => 'IT ဌာန',
    'name' => 'คุณภูมิพัฒน์ ไชยชาติ',
    'mail' => $mailIt
  )
);
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>SUPAVUT GROUP — Intranet Dashboard</title>
  <meta name="description" content="ศูนย์รวมระบบงานภายใน ประกาศ ปฏิทินบริษัท และข้อมูลองค์กรของ SUPAVUT GROUP">
  <link rel="icon" type="image/png" href="./img/3si.png">
  <link rel="apple-touch-icon" href="./img/pwa/icon-192.png">
  <link rel="manifest" href="./site.webmanifest">
  <meta name="theme-color" content="#064ba6">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* ══════════════════════════════════════════════════════════
       1. DESIGN TOKENS
       ══════════════════════════════════════════════════════════ */
    :root {
      --navy: #064ba6;
      --navy-deep: #052a57;
      --navy-mid: #073a75;
      --navy-soft: #eaf1fb;
      --navy-line: #cbdcf2;
      --green: #168642;
      --green-soft: #e6f4ec;

      --bg: #12004d;   /* พื้นหลังหน้า — น้ำเงินเข้มของระบบ */
      --surface: #ffffff;
      --surface-2: #f7f9fc;
      --surface-3: #eaeff5;
      --ink: #0f1a2b;
      --ink-2: #33415c;
      --muted: #5b6778;
      --faint: #8b95a5;

      --line: #dfe5ee;
      --line-2: #c6cfdb;

      --amber: #b45309;
      --amber-soft: #fef3e2;
      --red: #c02626;
      --red-soft: #fdeced;
      --gold: #c9a227;

      --r-sm: 3px;
      --r-md: 4px;

      --sh-1: 0 1px 2px rgba(15, 26, 43, 0.05);
      --sh-2: 0 2px 10px rgba(15, 26, 43, 0.07);
      --sh-3: 0 14px 38px rgba(15, 26, 43, 0.18);

      --head-h: 64px;
      --pad: clamp(8px, 1vw, 14px);
      --screen-h: 100vh;

      /* ขนาดไทล์ระบบงาน — ใช้คำนวณความสูงคงที่ 3 แถว
         ความสูงแถวผูกกับ vh เพื่อให้ทุกระดับการซูมได้สัดส่วนเท่ากัน
         (ซูม 100% จอ 1080 → ~78px · ซูม 150% → ตกไปที่ขั้นต่ำ 62px) */
      --app-cols: 4;
      --app-row: clamp(82px, 9.8vh, 96px);
      --app-icon: clamp(30px, 3.8vh, 40px);
      --app-gap: 8px;

      /* จำนวนประกาศต่อหน้า — ต้องตรงกับ POSTS_PER_PAGE ในสคริปต์ */
      --post-per-page: 5;

      --z-dropdown: 200;
      --z-modal: 400;
      --z-toast: 500;

      --ease: cubic-bezier(0.22, 1, 0.36, 1);
      --fast: 140ms;
      --base: 210ms;
    }

    /* ══════════════════════════════════════════════════════════
       2. RESET / BASE — หน้าจอเดียว ไม่มีสกรอลล์
       ══════════════════════════════════════════════════════════ */
    * { box-sizing: border-box; }

    /* overflow:hidden ที่ html ด้วย — กันหน้าเลื่อนจากการปัดเศษ 1-2px
       ตอนคิดความสูงภายใต้การล็อคซูม (body อย่างเดียวไม่พอ) */
    html, body { height: 100%; overflow: hidden; }

    body {
      margin: 0;
      overflow: hidden;
      display: grid;
      grid-template-rows: auto minmax(0, 1fr);
      background: var(--bg);
      color: var(--ink);
      font-family: "Inter", "Noto Sans Thai", "Noto Sans Myanmar", system-ui, -apple-system, "Segoe UI", sans-serif;
      font-size: 17px;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3, h4, p { margin: 0; }
    img { max-width: 100%; display: block; }
    a { color: inherit; text-decoration: none; -webkit-tap-highlight-color: transparent; }

    button, input, select { font: inherit; color: inherit; }

    button {
      border: 0;
      background: none;
      cursor: pointer;
      -webkit-tap-highlight-color: transparent;
    }

    :focus-visible { outline: 2px solid var(--navy); outline-offset: 1px; border-radius: 2px; }

    /* แถบเลื่อนภายในการ์ด — บางและกลมกลืน */
    .scroll-y { overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--line-2) transparent; }
    .scroll-y::-webkit-scrollbar { width: 6px; }
    .scroll-y::-webkit-scrollbar-thumb { background: var(--line-2); border-radius: 3px; }
    .scroll-y::-webkit-scrollbar-track { background: transparent; }

    /* ══════════════════════════════════════════════════════════
       3. แถบเมนูบนสุด
       ══════════════════════════════════════════════════════════ */
    .site-header {
      display: flex;
      align-items: center;
      gap: clamp(10px, 1.6vw, 26px);
      height: var(--head-h);
      padding-inline: clamp(12px, 1.6vw, 22px);
      background: var(--surface);
      border-bottom: 1px solid var(--line);
    }

    .brand { display: flex; align-items: center; gap: 11px; flex: 0 0 auto; }
    .brand-mark { width: 40px; height: 40px; object-fit: contain; }
    .brand-name { display: grid; line-height: 1; }

    .brand-name b {
      color: var(--navy);
      font-size: 1.0625rem;
      font-weight: 700;
      letter-spacing: 0.05em;
    }

    .brand-name span {
      color: var(--green);
      font-size: 0.625rem;
      font-weight: 700;
      letter-spacing: 0.3em;
      margin-top: 3px;
    }

    /* ── เมนูหลัก ── */
    .main-nav {
      display: flex;
      align-items: center;
      gap: 2px;
      min-width: 0;
      overflow-x: auto;
      scrollbar-width: none;
    }

    .main-nav::-webkit-scrollbar { display: none; }

    .nav-link {
      position: relative;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      height: var(--head-h);
      padding-inline: clamp(8px, 0.9vw, 14px);
      color: var(--muted);
      font-size: 0.9375rem;
      font-weight: 600;
      white-space: nowrap;
      transition: color var(--fast) var(--ease);
    }

    .nav-link svg { width: 18px; height: 18px; flex: 0 0 auto; }

    .nav-link::after {
      content: "";
      position: absolute;
      left: clamp(8px, 0.9vw, 14px);
      right: clamp(8px, 0.9vw, 14px);
      bottom: 0;
      height: 3px;
      background: var(--navy);
      transform: scaleX(0);
      transition: transform var(--base) var(--ease);
    }

    .nav-link:hover { color: var(--navy); }
    .nav-link.is-active { color: var(--navy); }
    .nav-link.is-active::after { transform: scaleX(1); }

    /* ── ลิงก์องค์กรฝั่งขวา ── */
    .nav-org {
      display: flex;
      align-items: center;
      gap: 3px;
      margin-left: auto;
      flex: 0 0 auto;
    }

    .org-link {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      height: 38px;
      padding: 0 13px;
      border-radius: var(--r-sm);
      color: var(--ink-2);
      font-size: 0.875rem;
      font-weight: 600;
      white-space: nowrap;
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .org-link svg { width: 16px; height: 16px; color: var(--muted); flex: 0 0 auto; }
    .org-link:hover { background: var(--navy-soft); color: var(--navy); }
    .org-link:hover svg { color: var(--navy); }

    .head-div { width: 1px; height: 24px; background: var(--line); margin-inline: 6px; }

    .icon-btn {
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      border: 1px solid transparent;
      border-radius: var(--r-sm);
      color: var(--muted);
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease), border-color var(--fast) var(--ease);
    }

    .icon-btn:hover, .icon-btn[aria-expanded="true"] {
      border-color: var(--line-2);
      background: var(--surface-2);
      color: var(--navy);
    }

    .icon-btn svg { width: 19px; height: 19px; }

    .lang-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      height: 38px;
      padding: 0 11px;
      border: 1px solid transparent;
      border-radius: var(--r-sm);
      font-size: 0.875rem;
      font-weight: 600;
      transition: background var(--fast) var(--ease), border-color var(--fast) var(--ease);
    }

    .lang-btn:hover, .lang-btn[aria-expanded="true"] { border-color: var(--line-2); background: var(--surface-2); }
    .lang-btn img { width: 20px; height: 14px; object-fit: cover; border-radius: 2px; }
    .lang-btn > svg { width: 11px; height: 11px; color: var(--muted); }

    .pop { position: relative; flex: 0 0 auto; }

    .pop-panel {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      z-index: var(--z-dropdown);
      min-width: 208px;
      padding: 5px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface);
      box-shadow: var(--sh-3);
      display: none;
    }

    .pop-panel.on { display: grid; gap: 2px; }

    .pop-label {
      padding: 6px 10px 4px;
      color: var(--faint);
      font-size: 0.625rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .pop-item {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 8px 10px;
      border-radius: var(--r-sm);
      color: var(--ink);
      font-size: 0.8125rem;
      text-align: left;
      transition: background var(--fast) var(--ease);
    }

    .pop-item:hover { background: var(--surface-3); }
    .pop-item img { width: 18px; height: 13px; object-fit: cover; border-radius: 2px; flex: 0 0 auto; }
    .pop-item svg { width: 16px; height: 16px; flex: 0 0 auto; color: var(--muted); }
    .pop-item span { flex: 1 1 auto; }

    .pop-item[aria-current="true"] { background: var(--navy-soft); color: var(--navy); font-weight: 600; }
    .pop-divider { height: 1px; margin: 4px 6px; background: var(--line); }

    /* ══════════════════════════════════════════════════════════
       4. โครงแดชบอร์ด — 2 คอลัมน์ เต็มความสูงที่เหลือ
       ══════════════════════════════════════════════════════════ */
    /* แถบข้างแคบลงจากเดิม (400 → 368) เพื่อคืนความกว้างให้แบนเนอร์
       26vw เดิมทำให้จอกว้างมากแถบข้างบวมจนแบนเนอร์ถูกบีบ */
    .dash {
      display: grid;
      grid-template-columns: minmax(0, 1fr) clamp(296px, 20vw, 368px);
      gap: var(--pad);
      min-height: 0;
      padding: var(--pad);
    }

    /* ซ้าย = แบนเนอร์ภาพอยู่บน · ระบบงานภายในอยู่ล่าง
       ขวา = ประกาศ 2 การ์ดซ้อน สูงเต็มคอลัมน์

       แถวแบนเนอร์เป็น 1fr ส่วนแถวระบบงานเป็น auto
       แบนเนอร์จึงยืดลงมากินที่ว่างทั้งหมด และการ์ดระบบงาน
       สูงพอดีกับจำนวนไทล์ ไม่เหลือช่องว่าง */
    .col-main {
      display: grid;
      /* 1.3fr : 1fr — แบนเนอร์/ระบบงานได้ราว 57% ของคอลัมน์ซ้าย
         เดิม 1:1 ทำให้แบนเนอร์แคบเกินไปตอนซูม 100% */
      grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
      grid-template-rows: minmax(0, 1fr) auto;
      grid-template-areas:
        "banner news"
        "apps   news";
      gap: var(--pad);
      min-height: 0;
    }

    .banner { grid-area: banner; }
    #cardApps { grid-area: apps; }
    .news-col { grid-area: news; }

    .news-col {
      display: grid;
      grid-template-rows: minmax(0, 1fr) minmax(0, 1fr);
      gap: var(--pad);
      min-height: 0;
    }

    /* เวลา | ปฏิทิน | แผนที่ — ปฏิทินสูงตามเนื้อหา ที่เหลือยกให้แผนที่ */
    .col-side {
      display: grid;
      grid-template-rows: auto auto minmax(150px, 1fr);
      gap: var(--pad);
      min-height: 0;
      overflow-x: hidden;
      overflow-y: auto;
      padding-right: 4px;
      overscroll-behavior: contain;
      scrollbar-color: var(--line-2) transparent;
      scrollbar-width: thin;
    }

    .col-side::-webkit-scrollbar { width: 6px; }
    .col-side::-webkit-scrollbar-thumb { background: var(--line-2); border-radius: 3px; }
    .col-side::-webkit-scrollbar-track { background: transparent; }

    .side-clock,
    .side-map {
      min-height: 0;
    }

    /* ไม่ล็อกความสูงตายตัว (เดิม 408px) — ปฏิทินสูงเท่าที่เนื้อหาต้องการ
       ส่วนที่เหลือจึงตกไปเป็นความสูงของการ์ดแผนที่แทน
       min-height:min-content จำเป็น เพราะ overflow:hidden ทำให้ขนาดต่ำสุด
       อัตโนมัติของ grid item กลายเป็น 0 → กริดจะบีบจนตารางวันโดนตัดตอนซูมมาก */
    .side-calendar {
      min-height: min-content;
      overflow: hidden;
      overscroll-behavior: contain;
      scrollbar-color: var(--line-2) transparent;
      scrollbar-width: thin;
    }

    .side-calendar::-webkit-scrollbar { width: 6px; }
    .side-calendar::-webkit-scrollbar-thumb { background: var(--line-2); border-radius: 3px; }
    .side-calendar::-webkit-scrollbar-track { background: transparent; }

    .side-calendar #miniCal {
      flex: 0 0 auto;
      min-height: 0;
      align-content: start;
      padding-top: 0 !important;
      padding: 2px 12px 10px !important;
    }

    .side-calendar .cal-dow {
      height: 18px;
      font-size: 0.625rem;
    }

    /* ช่องวันสูงขึ้น แต่ตัวเลขเล็กลง — ได้ที่มาจากการยุบแถวหัวเดือน
       กับการย่อการ์ดเวลา จึงโชว์ครบ 6 สัปดาห์ทุกระดับการซูม */
    .side-calendar .cal-day {
      aspect-ratio: auto;
      height: clamp(26px, 3.9vh, 38px);
      font-size: 0.8125rem;
    }

    .side-calendar .cal-legend {
      flex: 0 0 auto;
      padding-top: 7px;
      padding-bottom: 7px;
    }

    /* ── หัวการ์ดปฏิทิน: ปฏิทิน · ‹ เดือน › · ปุ่มขยาย บนแถวเดียว ──
       ทุกกฎต้องนำหน้าด้วย .side-calendar เพราะ .card-head / .cal-nav
       ถูกประกาศทีหลังในไฟล์ ถ้าน้ำหนักเท่ากันตัวหลังจะชนะ */
    .side-calendar .calendar-toolbar { padding: 9px 12px; gap: 8px; }
    .side-calendar .calendar-toolbar h2 { min-width: 0; }

    .side-calendar .calendar-toolbar h2 > span {
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .side-calendar .calendar-month-nav {
      margin-left: auto;
      gap: 2px;
      min-width: 0;
    }

    .side-calendar .calendar-month-nav b {
      min-width: 82px;
      font-size: 0.78125rem;
    }

    .side-calendar .calendar-month-nav button { width: 26px; height: 26px; }
    .side-calendar .calendar-month-nav svg { width: 15px; height: 15px; }

    .side-calendar .calendar-expand {
      width: 28px;
      height: 28px;
      justify-content: center;
      flex: 0 0 auto;
      margin-left: 2px;
    }

    /* ── แถบสำนักงาน/โรงงาน: แถวบางเต็มความกว้าง ไม่มีเส้นคั่นล่าง ── */
    .side-calendar .calendar-scope-head {
      border-bottom: 0;
      padding: 2px 12px 4px;
    }

    .side-calendar .calendar-scope {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      width: 100%;
      gap: 4px;
    }

    .side-calendar .calendar-scope button { width: 100%; height: 27px; }

    /* ── การ์ดมาตรฐาน ── */
    .card {
      display: flex;
      flex-direction: column;
      min-height: 0;
      overflow: hidden;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
      box-shadow: var(--sh-1);
    }

    .card-head {
      display: flex;
      align-items: center;
      gap: 10px;
      flex: 0 0 auto;
      padding: 11px 14px;
      border-bottom: 1px solid var(--line);
    }

    .card-head h2 {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.9375rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .card-head h2 > svg { width: 20px; height: 20px; color: var(--navy); flex: 0 0 auto; }

    .card-link {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-left: auto;
      flex: 0 0 auto;
      color: var(--navy);
      font-size: 0.8125rem;
      font-weight: 600;
      white-space: nowrap;
      transition: gap var(--fast) var(--ease);
    }

    .card-link:hover { gap: 9px; }
    .card-link svg { width: 16px; height: 16px; }

    /* overflow:hidden กันไม่ให้จำนวนรายการดันความสูงกล่อง
       ค่าที่วัดได้จึงเป็นความสูงช่องจริงเสมอ และไม่มีแถบเลื่อนโผล่ */
    .card-body { flex: 1 1 auto; min-height: 0; overflow: hidden; padding: 5px 13px 10px; }

    /* ══════════════════════════════════════════════════════════
       5. แบนเนอร์หัวหน้า (สไลด์ภาพ)
       ══════════════════════════════════════════════════════════ */
    /* ความสูงมาจากแถวกริด (1fr) ไม่ได้ตั้งตายตัว · ไม่มีกรอบ */
    .banner {
      position: relative;
      min-height: 0;
      overflow: hidden;
      border-radius: var(--r-md);
      background: var(--navy-deep);
    }

    .banner-slide {
      position: absolute;
      inset: 0;
      opacity: 0;
      transition: opacity 700ms var(--ease);
    }

    .banner-slide.is-on { opacity: 1; }
    .banner-slide img { width: 100%; height: 100%; object-fit: cover; }

    .banner-veil {
      position: absolute;
      inset: 0;
      background: linear-gradient(100deg, rgba(5, 24, 48, 0.92) 0%, rgba(5, 24, 48, 0.72) 40%, rgba(5, 24, 48, 0.18) 78%);
    }

    .banner-copy {
      position: absolute;
      inset: 0;
      z-index: 2;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 0 clamp(18px, 2.4vw, 36px);
      color: #ffffff;
    }

    .banner-copy h1 {
      font-size: clamp(1.0625rem, 1.9vw, 1.625rem);
      font-weight: 700;
      line-height: 1.25;
      letter-spacing: -0.015em;
    }

    .banner-copy p {
      max-width: 72ch;
      margin-top: 6px;
      min-height: 1.6em;
      color: rgba(255, 255, 255, 0.82);
      font-size: clamp(0.75rem, 0.95vw, 0.875rem);
      line-height: 1.6;
    }

    /* เคอร์เซอร์กะพริบ ย้ายตามช่องที่กำลังพิมพ์ */
    .banner-copy h1::after,
    .banner-copy p::after {
      content: "";
      display: none;
      width: 3px;
      height: 0.9em;
      margin-left: 3px;
      background: var(--gold);
      vertical-align: text-bottom;
      animation: caret 900ms steps(2, start) infinite;
    }

    .banner-copy.phase-title h1::after { display: inline-block; }
    .banner-copy.phase-text p::after { display: inline-block; }

    .banner-nav {
      position: absolute;
      right: 12px;
      bottom: 10px;
      z-index: 3;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .banner-dots { display: flex; align-items: center; gap: 5px; margin-right: 4px; }

    .banner-dots button {
      width: 18px;
      height: 3px;
      background: rgba(255, 255, 255, 0.4);
      transition: background var(--fast) var(--ease), width var(--base) var(--ease);
    }

    .banner-dots button[aria-current="true"] { width: 28px; background: var(--gold); }

    .banner-arrow {
      width: 26px;
      height: 26px;
      display: grid;
      place-items: center;
      border-radius: var(--r-sm);
      color: rgba(255, 255, 255, 0.82);
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .banner-arrow:hover { color: #ffffff; }

    .banner-arrow:hover { background: rgba(255, 255, 255, 0.18); }
    .banner-arrow svg { width: 14px; height: 14px; }

    /* ══════════════════════════════════════════════════════════
       6. แถวประกาศ (ประกาศสำคัญ | ข่าวสารล่าสุด)
       ══════════════════════════════════════════════════════════ */
    .news-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      gap: var(--pad);
      min-height: 0;
    }
    /* การ์ดประกาศไม่มีเส้นคั่นหัว/ท้าย */
    #cardAlert .card-head,
    #cardFeed .card-head { border-bottom: 0; }

    #cardAlert .card-foot,
    #cardFeed .card-foot { border-top: 0; background: transparent; }

    /* ── การ์ดประกาศ — ชุดละ 5 รายการ และเลื่อนภายในแต่ละการ์ดได้ ── */
    .post-list {
      display: grid;
      grid-auto-rows: 78px;
      align-content: start;
      gap: 7px;
      height: 100%;
      overflow-y: auto;
      overscroll-behavior: contain;
      padding-right: 4px;
      scrollbar-gutter: stable;
      scrollbar-width: thin;
      scrollbar-color: var(--line-2) transparent;
    }

    .post-list::-webkit-scrollbar { width: 6px; }
    .post-list::-webkit-scrollbar-thumb { background: var(--line-2); border-radius: 3px; }
    .post-list::-webkit-scrollbar-track { background: transparent; }

    /* ภาพชิดขอบการ์ด — ตัด padding ฝั่งซ้าย/บน/ล่างออก แล้วให้ภาพเต็มความสูงแถว */
    .post {
      position: relative;
      display: flex;
      align-items: stretch;
      gap: 12px;
      min-height: 0;
      overflow: hidden;
      padding: 0 12px 0 0;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface);
      transition: border-color var(--fast) var(--ease), background var(--fast) var(--ease);
    }

    .post:hover { border-color: var(--navy-line); background: var(--surface-2); }

    /* ต้องมี ไม่งั้น display:flex จะชนะ [hidden] แล้วพาจิเนทไม่ทำงาน */
    .post[hidden] { display: none; }

    /* ภาพเต็มความสูงแถว ชนขอบการ์ด ความกว้างตามสัดส่วน 4:3 */
    .post-thumb {
      position: relative;
      height: 100%;
      width: auto;
      aspect-ratio: 4 / 3;
      flex: 0 0 auto;
      overflow: hidden;
      background: var(--surface-3);
    }

    .post-thumb img { width: 100%; height: 100%; object-fit: cover; }

    .post-thumb .ph {
      display: grid;
      place-items: center;
      width: 100%;
      height: 100%;
      color: var(--line-2);
    }

    .post-thumb .ph svg { width: 18px; height: 18px; }

    .post-date {
      position: absolute;
      left: 0;
      top: 0;
      display: grid;
      place-items: center;
      min-width: 28px;
      padding: 2px 5px;
      background: var(--navy);
      color: #ffffff;
      line-height: 1.05;
      text-align: center;
    }

    .post-date strong { font-size: 0.75rem; font-weight: 700; }
    .post-date span { font-size: 0.5rem; opacity: 0.88; }

    .post-text {
      flex: 1 1 auto;
      min-width: 0;
      align-self: center;
      overflow: hidden;
      padding-block: 5px;
    }

    .post-top {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .post-text b {
      display: block;
      flex: 1 1 auto;
      min-width: 0;
      font-size: 0.875rem;
      font-weight: 700;
      line-height: 1.4;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .post-text p {
      margin-top: 2px;
      color: var(--muted);
      font-size: 0.75rem;
      line-height: 1.35;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .post-when {
      display: block;
      margin-top: 3px;
      color: var(--navy-deep);
      font-size: 0.6875rem;
      font-weight: 600;
      line-height: 1.25;
    }

    .post-go { flex: 0 0 auto; align-self: center; color: var(--line-2); }
    .post-go svg { width: 16px; height: 16px; }
    .post:hover .post-go { color: var(--navy); }

    /* หมุดมุมขวาบนของประกาศที่ปักหมุด — ขยายให้เห็นชัดจากระยะไกล */
    .post-pin {
      position: absolute;
      top: 0;
      right: 13px;
      color: var(--navy);
      line-height: 0;
      filter: drop-shadow(0 1px 2px rgba(6, 75, 166, 0.28));
    }

    .post-pin svg { width: 30px; height: 41px; }

    .media-blank {
      display: grid;
      place-items: center;
      width: 100%;
      height: 100%;
      color: var(--line-2);
      font-size: 0.5625rem;
      font-weight: 700;
      letter-spacing: 0.18em;
    }

    /* ── แอนิเมชันเลื่อนตอนเปลี่ยนหน้า / เปลี่ยนมุมมอง ── */
    @keyframes slideFromRight {
      from { opacity: 0; transform: translateX(26px); }
      to { opacity: 1; transform: none; }
    }

    @keyframes slideFromLeft {
      from { opacity: 0; transform: translateX(-26px); }
      to { opacity: 1; transform: none; }
    }

    .slide-next { animation: slideFromRight 260ms var(--ease); }
    .slide-prev { animation: slideFromLeft 260ms var(--ease); }

    /* ── แถบพาจิเนท: ลูกศร + จุด ── */
    .card-foot {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      flex: 0 0 auto;
      padding: 6px 13px;
      border-top: 1px solid var(--line);
      background: var(--surface-2);
    }

    .card-foot[hidden] { display: none; }

    /* ลูกศรแบ่งหน้า — ไม่มีกรอบเช่นกัน */
    .pg-btn {
      width: 30px;
      height: 32px;
      display: grid;
      place-items: center;
      color: var(--faint);
      transition: color var(--fast) var(--ease);
    }

    .pg-btn:hover:not(:disabled) { color: var(--navy); }
    .pg-btn:disabled { opacity: 0.3; cursor: default; }
    .pg-btn svg { width: 16px; height: 16px; }

    .dots { display: flex; align-items: center; gap: 5px; }

    .dots button {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--line-2);
      transition: background var(--fast) var(--ease), width var(--base) var(--ease);
    }

    .dots button:hover { background: var(--navy-line); }

    .dots button[aria-current="true"] {
      width: 18px;
      border-radius: 4px;
      background: var(--navy);
    }

    /* ── ชิปหมวดประกาศ (ในหัวการ์ด) — ทรงเหลี่ยม เลื่อนด้วย ‹ › ── */
    .mini-chips {
      display: flex;
      gap: 4px;
      min-width: 0;
      overflow-x: auto;
      scroll-behavior: smooth;
      scrollbar-width: none;
    }

    .mini-chips::-webkit-scrollbar { display: none; }

    .mini-chip {
      height: 30px;
      padding: 0 12px;
      flex: 0 0 auto;
      border-radius: var(--r-sm);
      color: var(--muted);
      font-size: 0.8125rem;
      font-weight: 600;
      white-space: nowrap;
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .mini-chip:hover { color: var(--navy); }
    .mini-chip[aria-selected="true"] { background: var(--navy); color: #ffffff; }

    /* ══════════════════════════════════════════════════════════
       7. Applications
       ══════════════════════════════════════════════════════════ */
    /* ── ตาราง 4 คอลัมน์ × 3 แถวคงที่ ──
       ความสูงการ์ดล็อกไว้ที่ 3 แถวเสมอ กดแท็บกรองแล้วการ์ดไม่ยุบ
       แบนเนอร์ด้านบนจึงไม่ขยับตาม */
    .app-grid {
      display: grid;
      grid-template-columns: repeat(var(--app-cols), minmax(0, 1fr));
      grid-auto-rows: var(--app-row);
      gap: var(--app-gap);
      align-content: start;
    }

    #cardApps .card-body {
      flex: 0 0 auto;
      height: calc(var(--app-row) * 3 + var(--app-gap) * 2 + 13px);
    }

    /* แถบแบ่งหน้ายึดที่ไว้ตลอด — ซ่อนด้วย visibility ไม่ใช่ display
       กดแท็บที่มีแอปไม่ถึง 1 หน้าแล้วการ์ดจึงสูงเท่าเดิมเป๊ะ ไม่กระตุก */
    #appsFoot { visibility: hidden; }
    #appsFoot.is-on { visibility: visible; }

    .app {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 5px;
      padding: 8px 5px;
      overflow: hidden;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface);
      text-align: center;
      transition: border-color var(--fast) var(--ease), box-shadow var(--fast) var(--ease), transform var(--fast) var(--ease);
    }

    .app:hover {
      border-color: var(--navy-line);
      box-shadow: var(--sh-2);
      transform: translateY(-2px);
    }

    /* ไอคอนและตัวอักษรย่อตามความสูงจอ — 5 คอลัมน์แล้วไทล์แคบและเตี้ยลง
       ทั้งคู่ต้องรวมกันไม่เกิน --app-row ทุกระดับการซูม
       (ไอคอน + ช่องไฟ 5 + ชื่อ 2 บรรทัด + padding 16) */
    .app-icon {
      width: var(--app-icon);
      height: var(--app-icon);
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      overflow: hidden;
    }

    .app-icon img { width: 100%; height: 100%; object-fit: contain; }

    .app b {
      width: 100%;
      font-size: clamp(0.6875rem, 1.3vh, 0.8125rem);
      font-weight: 600;
      line-height: 1.3;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      word-break: break-word;
    }

    .app.off { cursor: not-allowed; opacity: 0.55; }
    .app.off:hover { transform: none; box-shadow: none; border-color: var(--line); }

    /* ── หมวดระบบงาน ในหัวการ์ด + ปุ่มเลื่อน ‹ › ── */
    .chip-nav {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-left: auto;
      min-width: 0;
    }

    /* ลูกศรเลื่อนแท็บ — ไม่มีกรอบ เหลือแต่ไอคอน */
    .chip-arrow {
      width: 28px;
      height: 30px;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      color: var(--faint);
      transition: color var(--fast) var(--ease);
    }

    .chip-arrow:hover { color: var(--navy); }
    .chip-arrow svg { width: 16px; height: 16px; }
    .chip-arrow[hidden] { display: none; }

    .quick-items {
      scroll-behavior: smooth;
      display: flex;
      gap: 6px;
      min-width: 0;
      overflow-x: auto;
      scrollbar-width: none;
    }

    .quick-items::-webkit-scrollbar { display: none; }

    /* แท็บไม่มีกรอบ — เหลือกรอบสีน้ำเงินเฉพาะตัวที่เลือกอยู่ */
    .chip {
      display: inline-flex;
      align-items: center;
      height: 30px;
      padding: 0 12px;
      flex: 0 0 auto;
      border-radius: var(--r-sm);
      color: var(--muted);
      font-size: 0.8125rem;
      font-weight: 600;
      white-space: nowrap;
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .chip:hover { color: var(--navy); }
    .chip[aria-selected="true"] { background: var(--navy); color: #ffffff; }

    /* ══════════════════════════════════════════════════════════
       8. ปฏิทิน (แถบข้าง)
       ══════════════════════════════════════════════════════════ */
    .cal-nav { display: flex; align-items: center; gap: 4px; margin-left: auto; }

    .cal-nav b {
      min-width: 128px;
      font-size: 0.8125rem;
      font-weight: 700;
      text-align: center;
      white-space: nowrap;
    }

    .cal-nav button {
      width: 32px;
      height: 32px;
      display: grid;
      place-items: center;
      color: var(--muted);
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .cal-nav button:hover { background: var(--navy-soft); color: var(--navy); }
    .cal-nav svg { width: 16px; height: 16px; }

    /* สำนักงาน / โรงงาน — ไม่มีกรอบครอบ เหลือพื้นน้ำเงินเฉพาะตัวที่เลือก */
    .seg {
      display: inline-flex;
      gap: 2px;
    }

    .seg button {
      height: 30px;
      padding: 0 12px;
      border-radius: var(--r-sm);
      color: var(--muted);
      font-size: 0.8125rem;
      font-weight: 600;
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .seg button:hover { color: var(--navy); }
    .seg button[aria-selected="true"] { background: var(--navy); color: #ffffff; }

    .cal-grid {
      display: grid;
      grid-template-columns: repeat(7, minmax(0, 1fr));
      gap: 2px;
    }

    .cal-dow {
      display: grid;
      place-items: center;
      height: 24px;
      color: var(--faint);
      font-size: 0.6875rem;
      font-weight: 700;
    }

    .cal-dow:first-child { color: var(--red); }

    .cal-day {
      position: relative;
      display: grid;
      place-items: center;
      aspect-ratio: 1 / 1;
      border-radius: var(--r-sm);
      font-size: 0.8125rem;
      font-variant-numeric: tabular-nums;
    }

    .cal-day > span { position: relative; z-index: 1; }
    .cal-day.out { color: var(--line-2); }

    .cal-day.today {
      background: var(--navy);
      color: #ffffff;
      font-weight: 700;
    }

    .cal-day.today .mk { display: none; }

    .cal-day .mk {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 118%;
      height: 118%;
      max-width: 30px;
      max-height: 30px;
      transform: translate(-50%, -50%);
      z-index: 0;
      pointer-events: none;
    }

    /* สามเหลี่ยมส่วนกว้างอยู่ล่าง — เลื่อนรูปขึ้นและดันตัวเลขลง
       ให้ตัวเลขไปอยู่ช่วงกว้าง ไม่ถูกเส้นบีบจนอ่านไม่ออก */
    .cal-day .m-compensate { transform: translate(-50%, -58%); }
    .cal-day.is-compensate > span { transform: translateY(0.18em); }

    /* ══ สีสัญลักษณ์ + สีตัวเลข — ถอดจากไฟล์ Excel ต้นฉบับ ══
       อาทิตย์ = แดง · นักขัตฤกษ์/ชดเชยนักขัตฤกษ์ = ดำ
       หยุดชดเชยเสาร์ = น้ำเงินเข้ม · พักผ่อน = แดง · หยุดพิเศษ = แดงเลือดหมู
       (ชุดเดียวกับเซิร์ฟเวอร์บริษัท 192.168.5.7) */
    .mk {
      fill: none;
      stroke-width: 1.4;
      stroke-linejoin: round;
    }

    .m-sunday, .m-public, .m-substitution { stroke: #000000; }
    .m-compensate { stroke: #000099; }
    .m-vacation { stroke: #ff0000; }
    .m-special { stroke: #400000; }

    .cal-day.is-sunday { color: #ff0000; font-weight: 700; }
    .cal-day.is-public, .cal-day.is-substitution { color: #1c2333; font-weight: 800; }
    .cal-day.is-compensate { color: #000099; font-weight: 700; }
    .cal-day.is-vacation { color: #d81414; font-weight: 700; }
    .cal-day.is-special { color: #400000; font-weight: 700; }

    /* วันนี้ — พื้นน้ำเงิน ตัวเลขและเส้นเป็นสีขาว */
    .cal-day.today, .cal-day.today > span { color: #ffffff; }
    .cal-day.today .mk { stroke: #ffffff; }

    /* คำอธิบายสัญลักษณ์ — จัดเป็นตาราง 3 คอลัมน์ ให้อ่านเป็นระเบียบ */
    .cal-legend {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 5px 8px;
      padding: 9px 13px;
      border-top: 1px solid var(--line);
      background: var(--surface-2);
      color: var(--muted);
      font-size: 0.625rem;
    }

    .cal-legend > span {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      min-width: 0;
    }

    .cal-legend > span > span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .cal-legend .mk { width: 12px; height: 12px; flex: 0 0 auto; }

    /* ══════════════════════════════════════════════════════════
       9. นาฬิกา
       ══════════════════════════════════════════════════════════ */
    /* การ์ดเวลาเตี้ยที่สุดเท่าที่ยังอ่านง่าย — ที่ว่างที่ได้ยกไปให้ปฏิทิน */
    .side-clock .card-head { padding: 8px 13px; }
    .clock-card { text-align: center; padding: 8px 13px 10px; }

    .clock-time {
      color: var(--navy);
      font-size: clamp(1.5rem, 2.5vw, 2.125rem);
      font-weight: 700;
      line-height: 1.1;
      letter-spacing: 0.03em;
      font-variant-numeric: tabular-nums;
    }

    .clock-date { margin-top: 2px; color: var(--muted); font-size: 0.75rem; }

    /* ══════════════════════════════════════════════════════════
       10. แผนที่
       ══════════════════════════════════════════════════════════ */
    .map-body { position: relative; flex: 1 1 auto; min-height: 130px; background: var(--surface-3); }
    .side-map .map-body { min-height: 0; }

    /* iframe ปูเต็มกรอบด้วย inset:0 — ไม่พึ่ง height:100% ที่คำนวณพลาด
       ในกล่อง flex (เคยเหลือแถบเทาด้านล่าง)
       และต้องรับเมาส์เต็ม ๆ เพื่อให้ลากเลื่อน/ซูมแผนที่ได้จากหน้าแรกเลย
       ห้ามมีแผ่นทับ (.map-open เดิม) มาคั่นอีก */
    .map-body iframe {
      position: absolute;
      inset: 0;
      z-index: 0;
      width: 100%;
      height: 100%;
      border: 0;
      display: block;
      pointer-events: auto;
    }

    /* ป้ายชื่อบริษัท — ลอยอยู่มุมขวาบน กินพื้นที่น้อยและปล่อยเมาส์ทะลุ
       ยกเว้นตัวลิงก์ที่ต้องกดได้ */
    .map-info {
      position: absolute;
      right: 8px;
      top: 8px;
      z-index: 1;
      max-width: 168px;
      padding: 8px 10px;
      border-radius: var(--r-sm);
      background: rgba(255, 255, 255, 0.94);
      box-shadow: var(--sh-2);
      pointer-events: none;
    }

    .map-info b { display: block; font-size: 0.75rem; font-weight: 700; }
    .map-info p { margin-top: 3px; color: var(--muted); font-size: 0.6875rem; line-height: 1.5; }
    .map-info a { pointer-events: auto; }

    .map-info a {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-top: 7px;
      color: var(--navy);
      font-size: 0.6875rem;
      font-weight: 600;
      pointer-events: auto;
    }

    .map-info a svg { width: 11px; height: 11px; }

    /* ══════════════════════════════════════════════════════════
       11. สถานะว่าง
       ══════════════════════════════════════════════════════════ */
    .empty {
      display: grid;
      align-content: center;
      gap: 4px;
      height: 100%;
      min-height: 90px;
      padding: 16px;
      text-align: center;
    }

    /* ต้องมี ไม่งั้น display:grid จะชนะ [hidden] แล้วกล่องว่างโผล่ตลอด */
    .empty[hidden] { display: none; }

    .empty b { font-size: 0.8125rem; }
    .empty span { color: var(--faint); font-size: 0.75rem; }

    /* ══════════════════════════════════════════════════════════
       12. MODAL + TOAST
       ══════════════════════════════════════════════════════════ */
    .modal {
      position: fixed;
      inset: 0;
      z-index: var(--z-modal);
      display: none;
      align-items: center;
      justify-content: center;
      padding: clamp(10px, 2vw, 26px);
    }

    .modal.on { display: flex; }

    .modal-bg { position: absolute; inset: 0; background: rgba(6, 16, 30, 0.6); }

    .modal-card {
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      width: min(100%, 1080px);
      /* เว้นขอบบน-ล่างไว้ 12% ของจอ ไม่ให้ชนแถบหน้าต่าง */
      max-height: calc(var(--screen-h) * 0.88);
      overflow: hidden;
      border-radius: var(--r-md);
      background: var(--surface);
      box-shadow: var(--sh-3);
      animation: pop 220ms var(--ease);
    }

    .modal-card.is-narrow { width: min(100%, 560px); }

    @keyframes pop {
      from { opacity: 0; transform: translateY(8px) scale(0.99); }
      to { opacity: 1; transform: none; }
    }

    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex: 0 0 auto;
      padding: 13px 18px;
      border-bottom: 1px solid var(--line);
    }

    .modal-head h2 { font-size: 0.9375rem; font-weight: 700; }
    .modal-body { flex: 1 1 auto; overflow-y: auto; padding: 16px 18px; }

    /* 12 เดือนจัดเป็น 4 คอลัมน์ × 3 แถวเสมอ — ไม่เหลือแถวสุดท้ายแหว่ง */
    .year-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
    }

    /* ต้องมี ไม่งั้น display:grid จะชนะ [hidden] แล้วกริดปีค้างอยู่ */
    .year-grid[hidden] { display: none; }

    .month-card {
      overflow: hidden;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface);
      text-align: left;
      transition: border-color var(--fast) var(--ease), box-shadow var(--fast) var(--ease), transform var(--fast) var(--ease);
    }

    .month-card:hover {
      border-color: var(--navy);
      box-shadow: var(--sh-2);
      transform: translateY(-2px);
    }

    .month-card > b {
      display: block;
      padding: 7px 11px;
      border-bottom: 1px solid var(--line);
      background: var(--surface-2);
      font-size: 0.75rem;
      font-weight: 700;
    }

    .month-card .cal-grid { padding: 7px; }
    .month-card .cal-day { font-size: 0.625rem; }
    .month-card .cal-dow { height: 18px; font-size: 0.5rem; }

    /* ── มุมมองรายเดือนในโมดัล ── */
    .cal-back {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 28px;
      width: 28px;
      padding: 0;
      flex: 0 0 auto;
      border: 0;
      border-radius: 50%;
      background: transparent;
      color: var(--ink-2);
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .cal-back:hover { background: var(--navy-soft); color: var(--navy); }
    .cal-back svg { width: 14px; height: 14px; }
    .cal-back[hidden] { display: none; }

    /* คำอธิบายในโมดัลกว้างกว่า วางเรียงแถวเดียว 6 ช่อง */
    #mCalendar .cal-legend {
      grid-template-columns: repeat(6, auto);
      justify-content: start;
      gap: 6px 24px;
    }

    .month-view {
      display: grid;
      grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
      gap: 24px;
      align-items: start;
    }

    .month-view[hidden] { display: none; }

    .month-big {
      overflow: hidden;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
    }

    .month-big-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 10px 13px;
      border-bottom: 1px solid var(--line);
      background: var(--surface-2);
    }

    .month-big-head b { font-size: 0.9375rem; font-weight: 700; }

    .month-big-head button {
      width: 28px;
      height: 28px;
      display: grid;
      place-items: center;
      border-radius: var(--r-sm);
      color: var(--muted);
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .month-big-head button:hover { background: var(--navy-soft); color: var(--navy); }
    .month-big-head svg { width: 14px; height: 14px; }

    /* ปฏิทินเดือนใหญ่ขึ้น ช่องวันโปร่งกว่าเดิม */
    .month-big .cal-grid { padding: 16px; gap: 5px; }

    .month-big .cal-day {
      aspect-ratio: auto;
      height: 46px;
      font-size: 1rem;
    }

    .month-big .cal-day .mk { max-width: 40px; max-height: 40px; }
    .month-big .cal-dow { height: 34px; font-size: 0.8125rem; }

    .month-side h3 {
      margin-bottom: 12px;
      font-size: 1rem;
      font-weight: 700;
    }

    /* รายการวันหยุด — ไม่มีกรอบรอบวันที่ */
    .m-holiday {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 12px 0;
      border-bottom: 1px dashed var(--line);
    }

    .m-holiday:last-child { border-bottom: 0; }

    .m-holiday-date {
      display: grid;
      place-items: center;
      width: 44px;
      flex: 0 0 auto;
      line-height: 1.1;
      text-align: center;
    }

    .m-holiday-date strong { color: var(--navy); font-size: 1.25rem; font-weight: 700; }
    .m-holiday-date em { font-style: normal; color: var(--faint); font-size: 0.6875rem; }

    .m-holiday-name { flex: 1 1 auto; min-width: 0; }
    .m-holiday-name b { display: block; font-size: 0.875rem; font-weight: 600; line-height: 1.5; }
    .m-holiday-name span { color: var(--faint); font-size: 0.75rem; }
    .m-holiday .mk { width: 20px; height: 20px; flex: 0 0 auto; }

    .m-empty { padding: 26px 0; text-align: center; color: var(--faint); font-size: 0.8125rem; }

    /* ══════════════════════════════════════════════════════════
       โมดัลเว็บใช้บ่อย + ลิงก์โปรด
       ══════════════════════════════════════════════════════════ */
    .web-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
      gap: 10px;
    }

    /* ไม่มีกรอบ — ยกขึ้นตอนชี้อย่างเดียว */
    .web-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 9px;
      padding: 14px 8px;
      border-radius: var(--r-sm);
      text-align: center;
      transition: background var(--fast) var(--ease), transform var(--fast) var(--ease);
    }

    .web-item:hover {
      background: var(--surface-2);
      transform: translateY(-2px);
    }

    .web-mark {
      width: 46px;
      height: 46px;
      display: grid;
      place-items: center;
    }

    .web-mark img { width: 100%; height: 100%; object-fit: contain; }

    .web-item b { font-size: 0.8125rem; font-weight: 600; }


    /* ── โมดัลติดต่อ — ไม่มีกรอบ เน้นรูปพนักงาน ── */
    .contact-stack { display: grid; gap: 4px; }

    .contact {
      display: block;
      padding: 14px 6px;
      border-radius: var(--r-sm);
      transition: background var(--fast) var(--ease);
    }

    .contact + .contact { border-top: 1px solid var(--line); }
    .contact:hover { background: var(--surface-2); }

    .contact-text { min-width: 0; }

    .contact-dept {
      display: block;
      color: var(--faint);
      font-size: 0.75rem;
      font-weight: 600;
    }

    .contact-text b {
      display: block;
      margin-top: 3px;
      font-size: 1.0625rem;
      font-weight: 700;
      line-height: 1.35;
    }

    .contact-mail {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      margin-top: 7px;
      color: var(--navy);
      font-size: 0.8125rem;
      font-weight: 600;
      word-break: break-all;
    }

    .contact-mail svg { width: 14px; height: 14px; flex: 0 0 auto; }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      height: 34px;
      padding: 0 15px;
      border-radius: var(--r-sm);
      background: var(--navy);
      color: #ffffff;
      font-size: 0.8125rem;
      font-weight: 600;
      transition: background var(--fast) var(--ease);
    }

    .btn:hover { background: var(--navy-mid); }
    .btn svg { width: 15px; height: 15px; }

    /* โมดัลระบบงาน — ล็อกขนาดไว้ กดสลับแท็บแล้วกรอบไม่ยืด-หด */
    #mApps .modal-card { height: calc(var(--screen-h) * 0.76); }

    /* โมดัลปฏิทิน — กว้างขึ้นให้ 4 คอลัมน์อยู่สบาย
       ความสูงยืดตามเนื้อหา แต่ไม่เกิน 88% ของจอ จึงไม่ชนแถบหน้าต่าง
       และไม่เหลือช่องว่างค้างใต้ปฏิทิน */
    #mCalendar .modal-card { width: min(100%, 1180px); }

    /* ── แถบแท็บใต้หัวโมดัล ── */
    .modal-sub {
      flex: 0 0 auto;
      padding: 10px 18px;
      border-bottom: 1px solid var(--line);
      background: var(--surface-2);
    }

    /* ตารางแอปในโมดัล — ไทล์ใหญ่ขึ้น เลื่อนดูได้ */
    .app-grid.is-modal {
      grid-template-columns: repeat(auto-fill, minmax(132px, 1fr));
      grid-auto-rows: 118px;
      gap: 10px;
    }

    .app-grid.is-modal .app { padding: 16px 8px; gap: 10px; }
    .app-grid.is-modal .app-icon { width: 44px; height: 44px; }
    .app-grid.is-modal .app b { font-size: 0.8125rem; }

    /* ══════════════════════════════════════════════════════════
       ป้ายลอยตอนชี้แอป
       ══════════════════════════════════════════════════════════ */
    .app-pop {
      position: fixed;
      /* จอดไว้นอกจอก่อน ไม่ให้ไปกินพื้นที่ตอนยังไม่ถูกเรียกใช้ */
      left: -9999px;
      top: 0;
      z-index: var(--z-toast);
      width: 250px;
      padding: 16px;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
      box-shadow: var(--sh-3);
      text-align: center;
      opacity: 0;
      transform: translateY(6px) scale(0.97);
      pointer-events: none;
      transition: opacity var(--fast) var(--ease), transform var(--fast) var(--ease);
    }

    .app-pop.on { opacity: 1; transform: none; }

    .app-pop-icon {
      display: grid;
      place-items: center;
      width: 68px;
      height: 68px;
      margin: 0 auto 10px;
      animation: popIcon 320ms var(--ease);
    }

    .app-pop-icon img { width: 100%; height: 100%; object-fit: contain; }

    @keyframes popIcon {
      from { transform: scale(0.6); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .app-pop b {
      display: block;
      color: var(--navy);
      font-size: 0.9375rem;
      font-weight: 700;
      line-height: 1.4;
    }

    /* เคอร์เซอร์กะพริบท้ายข้อความที่กำลังพิมพ์ */
    .app-pop p {
      min-height: 2.9em;
      margin-top: 6px;
      color: var(--muted);
      font-size: 0.75rem;
      line-height: 1.6;
      text-align: left;
    }

    .app-pop p::after {
      content: "";
      display: inline-block;
      width: 2px;
      height: 0.95em;
      margin-left: 2px;
      background: var(--navy);
      vertical-align: text-bottom;
      animation: caret 900ms steps(2, start) infinite;
    }

    .app-pop.is-done p::after { animation: none; opacity: 0; }

    @keyframes caret { to { opacity: 0; } }

    /* ══════════════════════════════════════════════════════════
       ป้ายลอยตอนชี้ประกาศ
       ══════════════════════════════════════════════════════════ */
    .post-pop {
      position: fixed;
      left: -9999px;
      top: 0;
      z-index: var(--z-toast);
      width: 310px;
      overflow: hidden;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
      box-shadow: var(--sh-3);
      opacity: 0;
      transform: translateY(6px) scale(0.98);
      pointer-events: none;
      transition: opacity var(--fast) var(--ease), transform var(--fast) var(--ease);
    }

    .post-pop.on { opacity: 1; transform: none; }

    .post-pop-media {
      display: block;
      aspect-ratio: 4 / 3;
      overflow: hidden;
      background: var(--surface-3);
      animation: popIcon 320ms var(--ease);
    }

    .post-pop-media img { width: 100%; height: 100%; object-fit: cover; }

    .post-pop b {
      display: block;
      padding: 12px 14px 0;
      color: var(--navy);
      font-size: 0.9375rem;
      font-weight: 700;
      line-height: 1.45;
    }

    .post-pop p {
      min-height: 3.2em;
      padding: 5px 14px 14px;
      color: var(--muted);
      font-size: 0.8125rem;
      line-height: 1.65;
    }

    /* เคอร์เซอร์ย้ายตามช่องที่กำลังพิมพ์ */
    .post-pop b::after,
    .post-pop p::after {
      content: "";
      display: none;
      width: 2px;
      height: 0.95em;
      margin-left: 2px;
      background: var(--navy);
      vertical-align: text-bottom;
      animation: caret 900ms steps(2, start) infinite;
    }

    .post-pop.phase-title b::after { display: inline-block; }
    .post-pop.phase-desc p::after { display: inline-block; }

    .toast {
      position: fixed;
      left: 50%;
      bottom: 24px;
      z-index: var(--z-toast);
      max-width: min(92vw, 400px);
      padding: 11px 17px;
      border-radius: var(--r-sm);
      background: var(--ink);
      color: #ffffff;
      font-size: 0.8125rem;
      text-align: center;
      opacity: 0;
      transform: translate(-50%, 10px);
      pointer-events: none;
      transition: opacity var(--base) var(--ease), transform var(--base) var(--ease);
    }

    .toast.on { opacity: 1; transform: translate(-50%, 0); }

    /* ══════════════════════════════════════════════════════════
       13. อินโทร SI GROUP
       ══════════════════════════════════════════════════════════ */
    /* โทนเดียวกับฉากตอนเปิดแอป (launch.php) */
    .si-intro {
      position: fixed;
      inset: 0;
      z-index: 900;
      display: grid;
      place-items: center;
      background: var(--navy);
      animation: siFade 320ms var(--ease) 1720ms forwards;
    }

    @keyframes siFade { to { opacity: 0; visibility: hidden; } }

    .si-loader-word {
      display: inline-flex;
      color: #ffffff;
      font-size: clamp(2rem, 8vw, 5rem);
      font-weight: 700;
      letter-spacing: 0.08em;
      transform-origin: var(--si-origin-x, 50%) var(--si-origin-y, 50%);
    }

    .si-loader-word.is-running { animation: siZoom 1700ms cubic-bezier(0.66, 0, 0.34, 1) forwards; }

    @keyframes siZoom {
      0% { transform: translate(0, 0) scale(1); }
      36% { transform: translate(0, 0) scale(1); }
      100% { transform: translate(var(--si-shift-x, 0), var(--si-shift-y, 0)) scale(var(--si-zoom, 60)); }
    }

    .si-loader-target { color: var(--gold); }

    /* ══════════════════════════════════════════════════════════
       14. RESPONSIVE

       ── สำคัญ: media query วัดจาก viewport จริง ไม่ใช่ค่าที่ถูก zoom ขยาย
          จอ 1920×1080 → MQ เห็นประมาณ 1898×926
          จอ 1600×900  → MQ เห็นประมาณ 1580×746
          จอ 1366×768  → MQ เห็นประมาณ 1346×614
          ตัวเลขข้างล่างจึงเป็น px จริงทั้งหมด
       ══════════════════════════════════════════════════════════ */

    /* ── เดสก์ท็อปจอเตี้ยจริง ๆ (จอ ~768px ลงมา): บีบแถบข้าง ──
         min-width กันไม่ให้ไปมีผลกับมือถือที่จอสูงน้อยอยู่แล้ว */
    /* ≤800px ครอบทั้งจอ 1600×900 ที่ซูม 100% และจอ 1920 ที่ซูม 125% */
    @media (min-width: 1025px) and (max-height: 800px) {
      .side-calendar .cal-day { font-size: 0.75rem; }
      .side-calendar .cal-dow { height: 17px; }
      .side-calendar #miniCal { padding: 2px 12px 8px !important; }
      .side-calendar .calendar-toolbar { padding: 7px 12px; }
      .side-clock .card-head { padding: 7px 13px; }
      .clock-card { padding: 7px 13px 9px; }
      .clock-time { font-size: 1.625rem; }
      .banner-copy h1 { font-size: clamp(1.125rem, 1.6vw, 1.375rem); }
    }

    /* จอ 1920 ที่ซูม 150% ขึ้นไป — บีบทุกอย่างให้แน่นที่สุด */
    @media (min-width: 1025px) and (max-height: 640px) {
      .col-side { grid-template-rows: auto auto minmax(120px, 1fr); }
      .side-calendar .cal-day { height: 24px; font-size: 0.6875rem; }
      .side-calendar .cal-dow { height: 15px; }
      .side-calendar .cal-legend { gap: 3px 8px; padding: 6px 13px; font-size: 0.625rem; }
      .side-calendar .calendar-scope button { height: 24px; }
      .clock-card { padding: 5px 13px 7px; }
      .clock-time { font-size: 1.375rem; }
      .card-head { padding: 8px 13px; }
      .app { padding: 6px 4px; gap: 4px; }
      .banner-copy p { display: none; }
    }

    /* ══════════════════════════════════════════════════════════
       แท็บเล็ต / ย่อหน้าต่าง / มือถือ — ตั้งแต่ 1024px ลงมา
       ปลดล็อคซูมแล้ว จึงเลิกบังคับให้จบใน 1 หน้าจอ ปล่อยให้เลื่อนได้
       ══════════════════════════════════════════════════════════ */
    @media (max-width: 1024px) {
      html, body { height: auto; overflow: visible; }
      body { display: block; font-size: 16px; }

      .dash {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
        padding: 12px;
      }

      /* ทุกคอลัมน์เรียงต่อกันลงมา */
      .col-main, .col-side, .news-col {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        grid-template-rows: none;
        grid-template-areas: none;
        gap: 12px;
        min-height: 0;
      }

      .banner, #cardApps, .news-col { grid-area: auto; }

      /* การ์ดสูงตามเนื้อหา ไม่ล็อกความสูงอีกต่อไป */
      .card { height: auto; }
      .card-body { overflow: visible; }
      #cardApps .card-body { height: auto; }

      /* จอเล็กไม่ได้ล็อกความสูงการ์ด จึงยุบแถบแบ่งหน้าทิ้งไปเลย
         ไม่ต้องยึดที่ว่างไว้เหมือนเดสก์ท็อป */
      #appsFoot:not(.is-on) { display: none; }

      .post-list {
        grid-auto-rows: 78px;
        height: auto;
        overflow-y: visible;
        overscroll-behavior: auto;
        padding-right: 0;
      }
      .post { min-height: 78px; }
      .post-thumb { max-height: 78px; }

      .banner { height: 190px; }
      .map-body { height: 280px; }

      /* แถบเมนูขึ้นบรรทัดใหม่ได้ */
      .site-header {
        position: sticky;
        top: 0;
        height: auto;
        flex-wrap: wrap;
        gap: 8px;
        padding-block: 8px;
      }

      .main-nav { order: 3; flex-basis: 100%; }
      .nav-org { margin-left: auto; }

      /* โมดัลไม่ต้องล็อกความสูงตามจอ */
      #mApps .modal-card,
      #mCalendar .modal-card { height: auto; width: 100%; }

      .modal-card { max-height: calc(100vh - 24px); }
      .modal { padding: 12px; }

      .month-view { grid-template-columns: minmax(0, 1fr); }
      .year-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      #mCalendar .cal-legend { grid-template-columns: repeat(3, auto); }
    }

    /* ── แท็บเล็ตแนวตั้ง ── */
    @media (max-width: 820px) {
      .app-grid { --app-cols: 4; }
      .nav-link span { display: none; }
      .cal-legend { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    /* ══════════════════════════════════════════════════════════
       มือถือ
       ══════════════════════════════════════════════════════════ */
    @media (max-width: 640px) {
      body { font-size: 15px; }
      .dash { gap: 10px; padding: 10px; }

      .brand-mark { width: 32px; height: 32px; }
      .brand-name b { font-size: 0.9375rem; }

      .main-nav { gap: 0; }
      .nav-link { padding-inline: 10px; height: 44px; }

      .banner { height: 150px; }
      .banner-copy { padding: 0 16px; }
      .banner-copy p { display: none; }

      /* การ์ดประกาศ: ภาพเล็กลง ข้อความได้ที่มากขึ้น */
      .post { gap: 11px; padding: 0 12px 0 0; }
      .post-thumb { max-height: 78px; }
      .post-text b { font-size: 0.875rem; }
      .post-text p { display: none; }

      .app-grid { --app-cols: 3; }

      .clock-time { font-size: 2rem; }
      .cal-day { height: 34px; font-size: 0.875rem; }
      .cal-dow { height: 24px; font-size: 0.75rem; }

      .year-grid { grid-template-columns: minmax(0, 1fr); }
      .web-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .modal-head h2 { font-size: 0.9375rem; }

      /* แท็บหมวดในหัวการ์ดขึ้นบรรทัดใหม่ ไม่เบียดหัวข้อ */
      .card-head { flex-wrap: wrap; }
      .chip-nav { margin-left: 0; flex-basis: 100%; }
      .mini-chips { flex: 1 1 auto; }

      /* ป้ายลอยตอนชี้ไม่ต้องมีบนจอสัมผัส */
      .app-pop, .post-pop { display: none; }
    }

    @media (max-width: 420px) {
      .app-grid { --app-cols: 2; }
      .web-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .post-thumb { max-height: 66px; }
      .banner { height: 128px; }
    }
    /* ══════════════════════════════════════════════════════════
       15. ลดการเคลื่อนไหว
       ══════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>

<body>
  <div class="si-intro" id="siIntro" aria-hidden="true">
    <span class="si-loader-word" id="siIntroWord"><span>SI G</span><span class="si-loader-target" id="siIntroTarget">R</span><span>OUP</span></span>
  </div>

  <!-- ══════════════════ แถบเมนู ══════════════════ -->
  <header class="site-header">
    <a class="brand" href="index.php" aria-label="SUPAVUT GROUP">
      <img class="brand-mark" src="img/3si.png" alt="">
      <span class="brand-name">
        <b>SUPAVUT</b>
        <span>GROUP</span>
      </span>
    </a>

    <nav class="main-nav" aria-label="เมนูหลัก">
      <button class="nav-link is-active" type="button" data-nav="home">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 11 9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        <span data-i18n="navHome">หน้าแรก</span>
      </button>
      <button class="nav-link" type="button" data-open="apps">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4.5h6v6H4v-6Zm10 0h6v6h-6v-6ZM4 13.5h6v6H4v-6Zm10 0h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        <span data-i18n="navApps">ระบบงาน</span>
      </button>
      <button class="nav-link" type="button" data-open="contact">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6.5 4h3l1.5 4-2 1.5a11 11 0 0 0 5.5 5.5l1.5-2 4 1.5v3a1.5 1.5 0 0 1-1.6 1.5C11 18.5 5.5 13 5 6.6A1.5 1.5 0 0 1 6.5 4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        <span data-i18n="navContact">ติดต่อ</span>
      </button>
    </nav>

    <div class="nav-org">
      <?php /* เว็บใช้บ่อยนอกองค์กร — จุดทึบ 4 จุด อ่านง่ายที่ขนาดเล็ก */ ?>
      <button class="icon-btn" type="button" data-open="web" title="เว็บใช้บ่อย" aria-label="เว็บใช้บ่อย">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="7.5" cy="7.5" r="3.1"/><circle cx="16.5" cy="7.5" r="3.1"/><circle cx="7.5" cy="16.5" r="3.1"/><circle cx="16.5" cy="16.5" r="3.1"/></svg>
      </button>

      <div class="pop">
        <button class="icon-btn" id="orgBtn" type="button" aria-label="เมนู" aria-expanded="false" aria-haspopup="true">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
        <div class="pop-panel" id="orgPanel" role="menu">
          <p class="pop-label" data-i18n="orgTitle">ข้อมูลองค์กร</p>
          <a class="pop-item" href="rules.php" role="menuitem">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9 12.5h6M9 16h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            <span data-i18n="orgRules">กฎระเบียบข้อบังคับ</span>
          </a>
          <button class="pop-item" type="button" role="menuitem" data-soon>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 3.5h4v4h-4v-4ZM3.5 16.5h4v4h-4v-4Zm13 0h4v4h-4v-4ZM18.5 16.5v-2h-13v2M12 7.5v7" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
            <span data-i18n="orgChart">แผนผังองค์กร</span>
          </button>
          <div class="pop-divider"></div>
          <a class="pop-item" href="announcements_admin.php" role="menuitem">
            <img src="img/login-logo.png" alt="" style="width:17px;height:17px;object-fit:contain">
            <span data-i18n="orgAdmin">เข้าสู่ระบบผู้ดูแล</span>
          </a>
        </div>
      </div>

      <div class="pop">
        <button class="lang-btn" id="langBtn" type="button" aria-label="เปลี่ยนภาษา" aria-expanded="false" aria-haspopup="true">
          <img id="langFlag" src="img/flags/th.png" alt="">
          <span id="langCode">TH</span>
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="pop-panel" id="langPanel" role="menu">
          <button class="pop-item" type="button" role="menuitem" data-lang="th"><img src="img/flags/th.png" alt=""><span>ไทย</span></button>
          <button class="pop-item" type="button" role="menuitem" data-lang="en"><img src="img/flags/en.png" alt=""><span>English</span></button>
          <button class="pop-item" type="button" role="menuitem" data-lang="my"><img src="img/flags/my.png" alt=""><span>မြန်မာ</span></button>
        </div>
      </div>
    </div>
  </header>

  <!-- ══════════════════ แดชบอร์ด ══════════════════ -->
  <div class="dash">

    <!-- ─────────── คอลัมน์ซ้าย ─────────── -->
    <div class="col-main">

      <!-- ── แบนเนอร์ ── -->
      <section class="banner" aria-label="ภาพประชาสัมพันธ์">
        <?php foreach ($galleryImages as $gIndex => $gUrl) { ?>
          <div class="banner-slide<?php echo $gIndex === 0 ? ' is-on' : ''; ?>" data-slide="<?php echo (int) $gIndex; ?>">
            <img src="<?php echo announcement_h($gUrl); ?>" alt="" <?php echo $gIndex === 0 ? '' : 'loading="lazy"'; ?>>
          </div>
        <?php } ?>
        <div class="banner-veil" aria-hidden="true"></div>

        <?php /* ไล่พิมพ์ทีละตัว — ไม่ใส่ data-i18n เพราะสคริปต์ดึงคำแปลเองผ่าน tr()
                 ถ้าใส่ ตัวเต็มจะแวบขึ้นก่อนแล้วค่อยถูกลบไปพิมพ์ใหม่ */ ?>
        <div class="banner-copy" id="bannerCopy">
          <h1 id="bannerTitle">ยินดีต้อนรับสู่ SUPAVUT GROUP</h1>
          <p id="bannerText">ศูนย์กลางประกาศ ระบบงานภายใน ปฏิทินบริษัท และข้อมูลองค์กร รวมไว้ในหน้าเดียว</p>
        </div>

        <?php if (count($galleryImages) > 1) { ?>
          <div class="banner-nav">
            <div class="banner-dots" id="bannerDots" role="tablist" aria-label="เลือกภาพ">
              <?php foreach ($galleryImages as $dIndex => $dUrl) { ?>
                <button type="button" role="tab" data-dot="<?php echo (int) $dIndex; ?>" aria-current="<?php echo $dIndex === 0 ? 'true' : 'false'; ?>" aria-label="ภาพที่ <?php echo (int) $dIndex + 1; ?>"></button>
              <?php } ?>
            </div>
            <button class="banner-arrow" type="button" data-hero="prev" aria-label="ภาพก่อนหน้า">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button class="banner-arrow" type="button" data-hero="next" aria-label="ภาพถัดไป">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
        <?php } ?>
      </section>

      <!-- ── ซ้าย: ระบบงานภายใน (เต็มความสูง) · ขวา: แบนเนอร์ + ประกาศ ── -->
        <div class="news-col">

        <section class="card" id="cardAlert">
          <div class="card-head">
            <h2>
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 18 5v13l-14-5.5v-2Zm0 0H3a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1m2 .8V20a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-3.6" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
              <span data-i18n="alertTitle">ประกาศสำคัญ</span>
            </h2>

            <?php if (count($pinnedCats) > 0) { ?>
              <div class="chip-nav">
                <button class="chip-arrow" type="button" data-tabscroll="-1" data-strip="alertChips" aria-label="เลื่อนซ้าย" hidden>
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="mini-chips" id="alertChips" role="tablist" aria-label="หมวดประกาศสำคัญ">
                  <button class="mini-chip" role="tab" type="button" data-alertcat="" aria-selected="true" data-i18n="catAll">ทั้งหมด</button>
                  <?php foreach ($pinnedCats as $cat) { ?>
                    <button class="mini-chip" role="tab" type="button" data-alertcat="<?php echo announcement_h($cat['id']); ?>" aria-selected="false"
                            data-name-th="<?php echo announcement_h(announcement_category_name($cat, 'th')); ?>"
                            data-name-en="<?php echo announcement_h(announcement_category_name($cat, 'en')); ?>"
                            data-name-my="<?php echo announcement_h(announcement_category_name($cat, 'my')); ?>"><?php echo announcement_h(announcement_category_name($cat, 'th')); ?></button>
                  <?php } ?>
                </div>
                <button class="chip-arrow" type="button" data-tabscroll="1" data-strip="alertChips" aria-label="เลื่อนขวา" hidden>
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
              </div>
            <?php } ?>
          </div>

          <div class="card-body">
            <?php if (count($pinnedItems) === 0) { ?>
              <div class="empty">
                <b data-i18n="alertEmptyTitle">ยังไม่มีประกาศสำคัญ</b>
                <span data-i18n="alertEmptyText">ประกาศที่ปักหมุดจะแสดงที่นี่</span>
              </div>
            <?php } else { ?>
              <div class="post-list" id="alertList">
                <?php foreach ($pinnedItems as $aItem) { simenu_post_card($aItem); } ?>
              </div>

              <div class="empty" id="alertNone" hidden>
                <b data-i18n="newsNoneTitle">ไม่มีประกาศในหมวดนี้</b>
                <span data-i18n="newsNoneText">ลองเลือกหมวดอื่น</span>
              </div>
            <?php } ?>
          </div>

          <?php if (count($pinnedItems) > 0) { simenu_pager_foot('alert'); } ?>
        </section>

        <section class="card" id="cardFeed">
          <div class="card-head">
            <h2>
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5.5h11a1 1 0 0 1 1 1V18a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5.5Zm12 3h3a1 1 0 0 1 1 1V17a2 2 0 0 1-4 0M7 9h5M7 12.5h5M7 16h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span data-i18n="feedTitle">ประกาศทั่วไป</span>
            </h2>

            <?php if (count($feedCats) > 0) { ?>
              <div class="chip-nav">
                <button class="chip-arrow" type="button" data-tabscroll="-1" data-strip="feedChips" aria-label="เลื่อนซ้าย" hidden>
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="mini-chips" id="feedChips" role="tablist" aria-label="หมวดประกาศทั่วไป">
                  <button class="mini-chip" role="tab" type="button" data-cat="" aria-selected="true" data-i18n="catAll">ทั้งหมด</button>
                  <?php foreach ($feedCats as $cat) { ?>
                    <button class="mini-chip" role="tab" type="button" data-cat="<?php echo announcement_h($cat['id']); ?>" aria-selected="false"
                            data-name-th="<?php echo announcement_h(announcement_category_name($cat, 'th')); ?>"
                            data-name-en="<?php echo announcement_h(announcement_category_name($cat, 'en')); ?>"
                            data-name-my="<?php echo announcement_h(announcement_category_name($cat, 'my')); ?>"><?php echo announcement_h(announcement_category_name($cat, 'th')); ?></button>
                  <?php } ?>
                </div>
                <button class="chip-arrow" type="button" data-tabscroll="1" data-strip="feedChips" aria-label="เลื่อนขวา" hidden>
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
              </div>
            <?php } ?>
          </div>

          <div class="card-body">
            <?php if (count($feedItems) === 0) { ?>
              <div class="empty">
                <b data-i18n="feedEmptyTitle">ยังไม่มีประกาศทั่วไป</b>
                <span data-i18n="feedEmptyText">ประกาศที่ไม่ได้ปักหมุดจะแสดงที่นี่</span>
              </div>
            <?php } else { ?>
              <div class="post-list" id="feedList">
                <?php foreach ($feedItems as $item) { simenu_post_card($item); } ?>
              </div>

              <div class="empty" id="feedNone" hidden>
                <b data-i18n="newsNoneTitle">ไม่มีประกาศในหมวดนี้</b>
                <span data-i18n="newsNoneText">ลองเลือกหมวดอื่น</span>
              </div>
            <?php } ?>
          </div>

          <?php if (count($feedItems) > 0) { simenu_pager_foot('feed'); } ?>
        </section>

        </div>

        <!-- ── ระบบงานภายใน — กรอบยาวเต็มความสูงฝั่งซ้าย ── -->
        <section class="card" id="cardApps">
          <div class="card-head">
            <h2>
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 4.5h6v6H4v-6Zm10 0h6v6h-6v-6ZM4 13.5h6v6H4v-6Zm10 0h6v6h-6v-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
              <span data-i18n="appsTitle">ระบบงานภายใน</span>
            </h2>

            <?php /* หมวดระบบงาน — ถ้าล้นจะมีปุ่ม ‹ › ให้เลื่อนดู */ ?>
            <div class="chip-nav">
              <button class="chip-arrow" type="button" data-chipscroll="-1" aria-label="เลื่อนซ้าย" hidden>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
              <div class="quick-items" id="appChips" role="tablist" aria-label="หมวดระบบงาน"></div>
              <button class="chip-arrow" type="button" data-chipscroll="1" aria-label="เลื่อนขวา" hidden>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
            </div>
          </div>

          <div class="card-body">
            <div class="app-grid" id="appGrid" aria-live="polite"></div>
            <div class="empty" id="appNone" hidden>
              <b data-i18n="appsNoneTitle">ไม่พบระบบที่ค้นหา</b>
              <span data-i18n="appsNoneText">ลองเลือกหมวดอื่น</span>
            </div>
          </div>

          <?php /* ยึดที่ไว้เสมอ (ซ่อนด้วย visibility) — กดแท็บแล้วการ์ดไม่ยุบ
                   โผล่จริงเฉพาะตอนแอปเกิน 1 หน้า ผ่านคลาส .is-on */ ?>
          <div class="card-foot" id="appsFoot">
            <button class="pg-btn" type="button" data-apppage="-1" aria-label="หน้าก่อนหน้า">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="dots" id="appsDots"></div>
            <button class="pg-btn" type="button" data-apppage="1" aria-label="หน้าถัดไป">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
        </section>
    </div>

    <!-- ─────────── คอลัมน์ขวา ─────────── -->
    <div class="col-side">

      <!-- ── เวลาปัจจุบัน ── -->
      <section class="card side-clock">
        <div class="card-head">
          <h2>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M12 7v5.2l3.2 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span data-i18n="clockTitle">เวลาปัจจุบัน</span>
          </h2>
        </div>
        <div class="clock-card">
          <div class="clock-time" id="clockTime">--:--:--</div>
          <div class="clock-date" id="clockDate">—</div>
        </div>
      </section>

      <!-- ── ปฏิทิน ── -->
      <section class="card side-calendar">
        <?php /* ตัวเลื่อนเดือนย้ายขึ้นมาอยู่แถวหัวการ์ดแล้ว — ประหยัดไปหนึ่งแถว
                 ตารางวันจึงมีที่พอโชว์ครบ 6 สัปดาห์ทุกระดับการซูม */ ?>
        <div class="card-head calendar-toolbar">
          <h2>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6.5A1.5 1.5 0 0 1 5.5 5h13A1.5 1.5 0 0 1 20 6.5V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6.5ZM4 9.5h16M8.5 3v4M15.5 3v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span data-i18n="calWord">ปฏิทิน</span>
          </h2>
          <div class="cal-nav calendar-month-nav">
            <button type="button" data-shift="-1" aria-label="เดือนก่อนหน้า">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <b id="monthLabel">—</b>
            <button type="button" data-shift="1" aria-label="เดือนถัดไป">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
          <button class="card-link calendar-expand" type="button" data-open="calendar" title="ดูทั้ง 12 เดือน" aria-label="ดูทั้ง 12 เดือน">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 4h6v6M20 4l-7 7M10 20H4v-6M4 20l7-7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>

        <?php /* แถบสำนักงาน/โรงงาน — แถวบางเต็มความกว้าง */ ?>
        <div class="card-head calendar-scope-head">
          <div class="seg calendar-scope" role="tablist" aria-label="เลือกประเภทปฏิทิน">
            <button role="tab" type="button" data-scope="office" aria-selected="true" data-i18n="scopeOffice">สำนักงาน</button>
            <button role="tab" type="button" data-scope="factory" aria-selected="false" data-i18n="scopeFactory">โรงงาน</button>
          </div>
        </div>

        <div class="cal-grid" id="miniCal"></div>

      </section>

      <!-- ── แผนที่บริษัท ── -->
      <section class="card side-map">
        <div class="card-head">
          <h2>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-5.8 7-11a7 7 0 1 0-14 0c0 5.2 7 11 7 11Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6"/></svg>
            <span data-i18n="mapTitle">แผนที่บริษัท</span>
          </h2>
          <button class="card-link" type="button" data-open="map" title="ขยายแผนที่" aria-label="ขยายแผนที่">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 4h6v6M20 4l-7 7M10 20H4v-6M4 20l7-7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>

        <div class="map-body">
          <?php /* ไม่มีแผ่นทับแล้ว — ลากเลื่อน/ซูมแผนที่ได้จากหน้าแรกเลย
                   ถ้าต้องการดูเต็มจอใช้ปุ่มขยายที่หัวการ์ด */ ?>
          <iframe src="<?php echo htmlspecialchars($companyMapEmbedUrl, ENT_QUOTES); ?>" title="แผนที่ Supavut Industry" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

          <div class="map-info">
            <b>SUPAVUT INDUSTRY</b>
            <p data-i18n="mapNote">สำนักงานใหญ่และโรงงาน</p>
            <a href="<?php echo htmlspecialchars($companyMapOpenUrl, ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer">
              <span data-i18n="mapOpenGoogle">เปิดใน Google Maps</span>
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 5h5v5m0-5-7 7M18 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
          </div>
        </div>
      </section>
    </div>
  </div>

  <!-- ══════════════════ MODAL: ปฏิทินเต็มปี ══════════════════ -->
  <div class="modal" id="mCalendar" role="dialog" aria-modal="true" aria-labelledby="mCalTitle">
    <div class="modal-bg" data-close></div>
    <div class="modal-card">
      <div class="modal-head">
        <div style="display:flex;align-items:center;gap:12px;min-width:0">
          <?php /* ปุ่มย้อนกลับ — โผล่เฉพาะตอนดูรายเดือน */ ?>
          <button class="cal-back" id="calBack" type="button" aria-label="ทั้ง 12 เดือน" hidden>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <h2 id="mCalTitle">ปฏิทินบริษัท 2026</h2>
        </div>

        <div style="display:flex;align-items:center;gap:10px">
          <div class="seg" role="tablist">
            <button role="tab" type="button" data-mscope="office" aria-selected="true" data-i18n="scopeOffice">สำนักงาน</button>
            <button role="tab" type="button" data-mscope="factory" aria-selected="false" data-i18n="scopeFactory">โรงงาน</button>
          </div>
          <button class="icon-btn" type="button" data-close aria-label="ปิด">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          </button>
        </div>
      </div>

      <div class="modal-body">
        <?php /* มุมมองปี — กดการ์ดเดือนเพื่อเข้าไปดูเดือนนั้น */ ?>
        <div class="year-grid" id="yearGrid"></div>

        <?php /* มุมมองรายเดือน — ปฏิทินใหญ่ + รายการวันหยุดของเดือนนั้น */ ?>
        <div class="month-view" id="monthView" hidden>
          <div class="month-big">
            <div class="month-big-head">
              <button type="button" data-mshift="-1" aria-label="เดือนก่อนหน้า">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
              <b id="mMonthLabel">—</b>
              <button type="button" data-mshift="1" aria-label="เดือนถัดไป">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </button>
            </div>
            <div class="cal-grid" id="mMonthGrid"></div>
          </div>

          <div class="month-side">
            <h3 data-i18n="holidayTitle">วันหยุดในเดือนนี้</h3>
            <div id="mHolidayList"></div>
          </div>
        </div>
      </div>

      <div class="cal-legend" style="font-size:0.6875rem;padding:11px 18px">
        <span><svg viewBox="0 0 24 24" aria-hidden="true" class="mk m-sunday"><circle cx="12" cy="12" r="10"/></svg><span data-i18n="lgSunday">วันอาทิตย์</span></span>
        <span><svg viewBox="0 0 24 24" aria-hidden="true" class="mk m-public"><rect x="2.5" y="3" width="19" height="18" rx="1"/></svg><span data-i18n="lgPublic">นักขัตฤกษ์</span></span>
        <span><svg viewBox="0 0 24 24" aria-hidden="true" class="mk m-substitution"><polygon points="12,2 14.5,8.6 21.5,8.9 16,13.3 17.9,20.1 12,16.2 6.1,20.1 8,13.3 2.5,8.9 9.5,8.6"/></svg><span data-i18n="lgSubstitution">ชดเชยนักขัตฤกษ์</span></span>
        <span><svg viewBox="0 0 24 24" aria-hidden="true" class="mk m-compensate"><polygon points="12,2.6 22,21.4 2,21.4"/></svg><span data-i18n="lgCompensate">หยุดชดเชย</span></span>
        <span><svg viewBox="0 0 24 24" aria-hidden="true" class="mk m-vacation"><polygon points="12,2.5 21.5,12 12,21.5 2.5,12"/></svg><span data-i18n="lgVacation">พักผ่อน</span></span>
        <span><svg viewBox="0 0 24 24" aria-hidden="true" class="mk m-special"><polygon points="7,3 17,3 22,12 17,21 7,21 2,12"/></svg><span data-i18n="lgSpecial">หยุดพิเศษ</span></span>
      </div>
    </div>
  </div>

  <!-- ══════════════════ MODAL: เว็บใช้บ่อย ══════════════════ -->
  <div class="modal" id="mWeb" role="dialog" aria-modal="true" aria-labelledby="mWebTitle">
    <div class="modal-bg" data-close></div>
    <div class="modal-card is-narrow">
      <div class="modal-head">
        <h2 id="mWebTitle" data-i18n="webTitle">เว็บใช้บ่อย</h2>
        <button class="icon-btn" type="button" data-close aria-label="ปิด">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </div>

      <div class="modal-body">
        <div class="web-grid">
          <?php
            /* เว็บนอกองค์กรที่ใช้บ่อย — เพิ่ม/ลบได้ที่นี่
               โลโก้อยู่ที่ img/weblogo/ (ไฟล์พื้นหลังโปร่ง) */
            $webLinks = array(
              array('name' => 'Google', 'url' => 'https://www.google.com', 'logo' => 'img/weblogo/google.png'),
              array('name' => 'ChatGPT', 'url' => 'https://chatgpt.com', 'logo' => 'img/weblogo/chatgpt.png'),
              array('name' => 'Gemini', 'url' => 'https://gemini.google.com', 'logo' => 'img/weblogo/gemini.png'),
              array('name' => 'Claude', 'url' => 'https://claude.ai', 'logo' => 'img/weblogo/claude.png'),
              array('name' => 'Canva', 'url' => 'https://www.canva.com', 'logo' => 'img/weblogo/canva.png')
            );
          ?>
          <?php foreach ($webLinks as $web) { ?>
            <a class="web-item" href="<?php echo announcement_h($web['url']); ?>" target="_blank" rel="noopener noreferrer">
              <span class="web-mark"><img src="<?php echo announcement_h($web['logo']); ?>" alt="<?php echo announcement_h($web['name']); ?>" loading="lazy"></span>
              <b><?php echo announcement_h($web['name']); ?></b>
            </a>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════ MODAL: ระบบงานภายในทั้งหมด ══════════════════ -->
  <div class="modal" id="mApps" role="dialog" aria-modal="true" aria-labelledby="mAppsTitle">
    <div class="modal-bg" data-close></div>
    <div class="modal-card">
      <div class="modal-head">
        <h2 id="mAppsTitle" data-i18n="appsTitle">ระบบงานภายใน</h2>
        <button class="icon-btn" type="button" data-close aria-label="ปิด">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </div>

      <?php /* แท็บหมวดของตัวเอง แยกจากการ์ดบนหน้า */ ?>
      <div class="modal-sub">
        <div class="quick-items" id="mAppChips" role="tablist" aria-label="หมวดระบบงาน"></div>
      </div>

      <div class="modal-body">
        <div class="app-grid is-modal" id="mAppGrid" aria-live="polite"></div>
        <div class="empty" id="mAppNone" hidden>
          <b data-i18n="appsNoneTitle">ไม่พบระบบที่ค้นหา</b>
          <span data-i18n="appsNoneText">ลองเลือกหมวดอื่น</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════ MODAL: แผนที่ ══════════════════ -->
  <div class="modal" id="mMap" role="dialog" aria-modal="true" aria-labelledby="mMapTitle">
    <div class="modal-bg" data-close></div>
    <div class="modal-card">
      <div class="modal-head">
        <h2 id="mMapTitle" data-i18n="mapTitle">แผนที่บริษัท</h2>
        <button class="icon-btn" type="button" data-close aria-label="ปิด">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </div>
      <div style="aspect-ratio:16/9;background:var(--surface-3)">
        <iframe src="<?php echo htmlspecialchars($companyMapEmbedUrl, ENT_QUOTES); ?>" title="แผนที่ Supavut Industry" style="width:100%;height:100%;border:0;display:block" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
      <div style="padding:13px 18px;border-top:1px solid var(--line)">
        <a class="btn" href="<?php echo htmlspecialchars($companyMapOpenUrl, ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" data-i18n="mapOpenGoogle">เปิดใน Google Maps</a>
      </div>
    </div>
  </div>

  <!-- ══════════════════ MODAL: ติดต่อ ══════════════════ -->
  <div class="modal" id="mContact" role="dialog" aria-modal="true" aria-labelledby="mContactTitle">
    <div class="modal-bg" data-close></div>
    <div class="modal-card is-narrow">
      <div class="modal-head">
        <h2 id="mContactTitle" data-i18n="contactTitle">ติดต่อ</h2>
        <button class="icon-btn" type="button" data-close aria-label="ปิด">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </div>
      <div class="modal-body">
        <div class="contact-stack">
          <?php foreach ($contacts as $person) { ?>
            <a class="contact" href="mailto:<?php echo announcement_h($person['mail']); ?>">
              <span class="contact-text">
                <span class="contact-dept"
                      data-dept
                      data-name-th="<?php echo announcement_h($person['dept_th']); ?>"
                      data-name-en="<?php echo announcement_h($person['dept_en']); ?>"
                      data-name-my="<?php echo announcement_h($person['dept_my']); ?>"><?php echo announcement_h($person['dept_th']); ?></span>
                <b><?php echo announcement_h($person['name']); ?></b>
                <span class="contact-mail">
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16v12H4V6Zm0 .5 8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <?php echo announcement_h($person['mail']); ?>
                </span>
              </span>
            </a>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>

  <?php /* ป้ายลอยตอนเอาเมาส์ชี้แอป — ไอคอนใหญ่ + คำอธิบายพิมพ์ทีละตัว */ ?>
  <div class="app-pop" id="appPop" aria-hidden="true">
    <span class="app-pop-icon"><img id="appPopIcon" src="" alt=""></span>
    <b id="appPopName"></b>
    <p id="appPopDesc"></p>
  </div>

  <?php /* ป้ายลอยตอนชี้ประกาศ — ภาพตัวอย่าง + หัวข้อและรายละเอียดพิมพ์ทีละตัว */ ?>
  <div class="post-pop" id="postPop" aria-hidden="true">
    <span class="post-pop-media"><img id="postPopImg" src="" alt=""></span>
    <b id="postPopTitle"></b>
    <p id="postPopDesc"></p>
  </div>

  <div class="toast" id="toast" role="status" aria-live="polite"></div>

  <script>
    'use strict';

    /* ══════════════════════════════════════════════════════════
       1. ข้อมูลจากฝั่ง PHP
       ══════════════════════════════════════════════════════════ */
    var apps = <?php echo json_encode($appRows); ?>;
    var appText = <?php echo count($appTextRows) > 0 ? json_encode($appTextRows) : '{}'; ?>;
    var appCats = <?php echo json_encode($appCatRows); ?>;
    var slideCount = <?php echo count($galleryImages); ?>;

    /* ══════════════════════════════════════════════════════════
       2. คำแปล 3 ภาษา
       ══════════════════════════════════════════════════════════ */
    var t = {
      th: {
        navHome: 'หน้าแรก', navApps: 'ระบบงาน', navNews: 'ข่าวสาร', navContact: 'ติดต่อ',
        orgTitle: 'ข้อมูลองค์กร', orgRules: 'กฎระเบียบข้อบังคับ', orgChart: 'แผนผังองค์กร', orgAdmin: 'เข้าสู่ระบบผู้ดูแล',
        heroTitle: 'ยินดีต้อนรับสู่ SUPAVUT GROUP',
        heroText: 'ศูนย์กลางประกาศ ระบบงานภายใน ปฏิทินบริษัท และข้อมูลองค์กร รวมไว้ในหน้าเดียว',
        alertTitle: 'ประกาศสำคัญ', feedTitle: 'ประกาศทั่วไป', seeAll: 'ดูทั้งหมด',
        catAll: 'ทั้งหมด', tagNew: 'ใหม่', tagPinned: 'ปักหมุด',
        newsEmptyTitle: 'ยังไม่มีประกาศ', newsEmptyText: 'เมื่อ HR เผยแพร่ประกาศ จะแสดงที่นี่',
        newsNoneTitle: 'ไม่มีประกาศในหมวดนี้', newsNoneText: 'ลองเลือกหมวดอื่น',
        feedEmptyTitle: 'ยังไม่มีประกาศทั่วไป', feedEmptyText: 'ประกาศที่ไม่ได้ปักหมุดจะแสดงที่นี่',
        alertEmptyTitle: 'ยังไม่มีประกาศสำคัญ', alertEmptyText: 'ประกาศที่ปักหมุดจะแสดงที่นี่', pageWord: 'หน้า',
        appsTitle: 'ระบบงานภายใน',
        appsNoneTitle: 'ไม่พบระบบในหมวดนี้', appsNoneText: 'ลองเลือกหมวดอื่น',
        webTitle: 'เว็บใช้บ่อย',
        calWord: 'ปฏิทิน', calTitle: 'ปฏิทินบริษัท', calFullYear: 'ปฏิทินทั้ง 12 เดือน',
        calBack: 'ทั้ง 12 เดือน', holidayTitle: 'วันหยุดในเดือนนี้', noHoliday: 'ไม่มีวันหยุดในเดือนนี้',
        scopeOffice: 'สำนักงาน', scopeFactory: 'โรงงาน',
        clockTitle: 'เวลาปัจจุบัน',
        mapTitle: 'แผนที่บริษัท', mapExpand: 'ขยาย', mapNote: 'สำนักงานใหญ่และโรงงาน', mapOpenGoogle: 'เปิดใน Google Maps',
        contactTitle: 'ติดต่อ',
        contactItName: 'ฝ่าย IT', contactItRole: 'ระบบงาน อุปกรณ์ และบัญชีผู้ใช้',
        contactHrName: 'ฝ่ายบุคคล (HR)', contactHrRole: 'ประกาศ สวัสดิการ และงานบุคคล',
        lgSunday: 'วันอาทิตย์', lgPublic: 'นักขัตฤกษ์', lgSubstitution: 'ชดเชยนักขัตฤกษ์',
        lgCompensate: 'หยุดชดเชย', lgVacation: 'พักผ่อน', lgSpecial: 'หยุดพิเศษ',
        soon: 'กำลังเตรียมเอกสารนี้', noLink: 'ยังไม่มีลิงก์ระบบนี้'
      },
      en: {
        navHome: 'Home', navApps: 'Application', navNews: 'News', navContact: 'Contact',
        orgTitle: 'Company info', orgRules: 'Work rules', orgChart: 'Organisation chart', orgAdmin: 'Admin sign in',
        heroTitle: 'Welcome to SUPAVUT GROUP',
        heroText: 'Announcements, internal applications, the company calendar and corporate information in one place.',
        alertTitle: 'Important notices', feedTitle: 'General announcements', seeAll: 'See all',
        catAll: 'All', tagNew: 'New', tagPinned: 'Pinned',
        newsEmptyTitle: 'No announcements yet', newsEmptyText: 'HR announcements will appear here.',
        newsNoneTitle: 'No announcements in this topic', newsNoneText: 'Try another topic.',
        feedEmptyTitle: 'No general announcements', feedEmptyText: 'Announcements that are not pinned appear here.',
        alertEmptyTitle: 'No important announcements', alertEmptyText: 'Pinned announcements appear here.', pageWord: 'Page',
        appsTitle: 'Internal applications',
        appsNoneTitle: 'No applications in this category', appsNoneText: 'Try another category.',
        webTitle: 'Frequently used sites',
        calWord: 'Calendar', calTitle: 'Company calendar', calFullYear: 'All 12 months',
        calBack: 'All 12 months', holidayTitle: 'Holidays this month', noHoliday: 'No holidays this month',
        scopeOffice: 'Office', scopeFactory: 'Factory',
        clockTitle: 'Current time',
        mapTitle: 'Company map', mapExpand: 'Expand', mapNote: 'Head office and factory', mapOpenGoogle: 'Open in Google Maps',
        contactTitle: 'Contact',
        contactItName: 'IT Department', contactItRole: 'Systems, equipment and user accounts',
        contactHrName: 'Human Resources', contactHrRole: 'Announcements, welfare and HR matters',
        lgSunday: 'Sunday', lgPublic: 'Public holiday', lgSubstitution: 'Substitution holiday',
        lgCompensate: 'Compensation holiday', lgVacation: 'Vacation', lgSpecial: 'Special holiday',
        soon: 'This document is being prepared.', noLink: 'No link for this system yet'
      },
      my: {
        navHome: 'ပင်မ', navApps: 'အက်ပ်များ', navNews: 'သတင်း', navContact: 'ဆက်သွယ်ရန်',
        orgTitle: 'ကုမ္ပဏီအချက်အလက်', orgRules: 'လုပ်ငန်းစည်းမျဉ်း', orgChart: 'ဖွဲ့စည်းပုံ', orgAdmin: 'Admin ဝင်ရောက်ရန်',
        heroTitle: 'SUPAVUT GROUP မှ ကြိုဆိုပါသည်',
        heroText: 'ကြေညာချက်၊ အက်ပ်များ၊ ပြက္ခဒိန်နှင့် ကုမ္ပဏီအချက်အလက်များကို တစ်နေရာတည်းတွင် စုစည်းထားသည်။',
        alertTitle: 'အရေးကြီး ကြေညာချက်', feedTitle: 'ယေဘုယျ ကြေညာချက်', seeAll: 'အားလုံး',
        catAll: 'အားလုံး', tagNew: 'အသစ်', tagPinned: 'ပင်တွဲထား',
        newsEmptyTitle: 'ကြေညာချက် မရှိသေးပါ', newsEmptyText: 'HR ကြေညာချက်များ ဤနေရာတွင် ပေါ်ပါမည်။',
        newsNoneTitle: 'ဤခေါင်းစဉ်တွင် ကြေညာချက် မရှိပါ', newsNoneText: 'အခြားခေါင်းစဉ် ရွေးကြည့်ပါ။',
        feedEmptyTitle: 'ယေဘုယျ ကြေညာချက် မရှိသေးပါ', feedEmptyText: 'ပင်တွဲမထားသော ကြေညာချက်များ ဤနေရာတွင် ပေါ်ပါမည်။',
        alertEmptyTitle: 'အရေးကြီး ကြေညာချက် မရှိသေးပါ', alertEmptyText: 'ပင်တွဲထားသော ကြေညာချက်များ ဤနေရာတွင် ပေါ်ပါမည်။', pageWord: 'စာမျက်နှာ',
        appsTitle: 'အတွင်းပိုင်း အက်ပ်များ',
        appsNoneTitle: 'ဤအမျိုးအစားတွင် အက်ပ် မရှိပါ', appsNoneText: 'အခြားအမျိုးအစား စမ်းကြည့်ပါ။',
        webTitle: 'အသုံးများသော ဝဘ်ဆိုက်',
        calWord: 'ပြက္ခဒိန်', calTitle: 'ကုမ္ပဏီ ပြက္ခဒိန်', calFullYear: 'လ ၁၂ လ',
        calBack: 'လ ၁၂ လ', holidayTitle: 'ဤလ၏ အားလပ်ရက်များ', noHoliday: 'ဤလတွင် အားလပ်ရက် မရှိပါ',
        scopeOffice: 'ရုံး', scopeFactory: 'စက်ရုံ',
        clockTitle: 'လက်ရှိအချိန်',
        mapTitle: 'ကုမ္ပဏီ မြေပုံ', mapExpand: 'ချဲ့ရန်', mapNote: 'ရုံးချုပ်နှင့် စက်ရုံ', mapOpenGoogle: 'Google Maps တွင် ဖွင့်ရန်',
        contactTitle: 'ဆက်သွယ်ရန်',
        contactItName: 'IT ဌာန', contactItRole: 'စနစ်များ၊ ပစ္စည်းများနှင့် အသုံးပြုသူအကောင့်',
        contactHrName: 'လူ့စွမ်းအားအရင်းအမြစ် (HR)', contactHrRole: 'ကြေညာချက်၊ သက်သာချောင်ချိရေးနှင့် HR',
        lgSunday: 'တနင်္ဂနွေ', lgPublic: 'အများပြည်သူ အားလပ်ရက်', lgSubstitution: 'အစားထိုး အားလပ်ရက်',
        lgCompensate: 'အစားထိုးနားရက်', lgVacation: 'အားလပ်ရက်', lgSpecial: 'အထူးအားလပ်ရက်',
        soon: 'ဤစာရွက်စာတမ်း ပြင်ဆင်နေပါသည်။', noLink: 'ဤစနစ်အတွက် လင့်ခ် မရှိသေးပါ'
      }
    };

    /* ══════════════════════════════════════════════════════════
       3. ปฏิทินบริษัท 2026
       ══════════════════════════════════════════════════════════ */
    var cal = {
      year: 2026,
      scopes: {
        office: {
          public: ['2026-01-01', '2026-04-06', '2026-04-13', '2026-04-14', '2026-04-15', '2026-05-01', '2026-06-03', '2026-07-28', '2026-07-29', '2026-08-12', '2026-10-23', '2026-12-31'],
          substitution: ['2026-06-01'],
          vacation: ['2026-01-02'],
          compensate: ['2026-01-03', '2026-01-10', '2026-02-14', '2026-02-28', '2026-03-07', '2026-03-21', '2026-04-04', '2026-04-18', '2026-05-02', '2026-05-16', '2026-05-30', '2026-06-13', '2026-06-27', '2026-07-11', '2026-07-25', '2026-08-08', '2026-08-22', '2026-09-05', '2026-09-19', '2026-10-03', '2026-10-17', '2026-10-24', '2026-11-14', '2026-11-28', '2026-12-12', '2026-12-26']
        },
        factory: {
          public: ['2026-01-01', '2026-04-06', '2026-04-13', '2026-04-14', '2026-04-15', '2026-05-01', '2026-06-03', '2026-07-28', '2026-07-29', '2026-08-12', '2026-10-23', '2026-12-31'],
          substitution: ['2026-06-01'],
          vacation: ['2026-01-02', '2026-01-03'],
          compensate: []
        }
      },
      names: {
        '2026-01-01': { th: 'วันขึ้นปีใหม่', en: "New Year's Day", my: 'နှစ်သစ်ကူးနေ့' },
        '2026-01-02': { th: 'วันหยุดพักผ่อนประจำปี', en: 'Vacation', my: 'အားလပ်ရက်' },
        '2026-01-03': { th: 'วันหยุดพักผ่อนประจำปี', en: 'Vacation', my: 'အားလပ်ရက်' },
        '2026-04-06': { th: 'วันจักรี', en: 'Chakri Memorial Day', my: 'Chakri အောက်မေ့ဖွယ်နေ့' },
        '2026-04-13': { th: 'วันสงกรานต์', en: 'Songkran', my: 'သင်္ကြန်နေ့' },
        '2026-04-14': { th: 'วันสงกรานต์', en: 'Songkran', my: 'သင်္ကြန်နေ့' },
        '2026-04-15': { th: 'วันสงกรานต์', en: 'Songkran', my: 'သင်္ကြန်နေ့' },
        '2026-05-01': { th: 'วันแรงงานแห่งชาติ', en: 'National Labour Day', my: 'အလုပ်သမားနေ့' },
        '2026-06-01': { th: 'ชดเชยวันวิสาขบูชา', en: 'Substitution for Visakha Bucha', my: 'Visakha Bucha အစားထိုး' },
        '2026-06-03': { th: 'วันเฉลิมพระชนมพรรษา สมเด็จพระนางเจ้าฯ', en: "H.M. Queen Suthida's Birthday", my: 'HM Queen Suthida မွေးနေ့' },
        '2026-07-28': { th: 'วันเฉลิมพระชนมพรรษา ในหลวง ร.10', en: "King Maha Vajiralongkorn's Birthday", my: 'ဘုရင် မွေးနေ့' },
        '2026-07-29': { th: 'วันอาสาฬหบูชา', en: 'Asalha Bucha', my: 'Asalha Bucha နေ့' },
        '2026-08-12': { th: 'วันแม่แห่งชาติ', en: "Mother's Day", my: 'အမေများနေ့' },
        '2026-10-23': { th: 'วันปิยมหาราช', en: "King Chulalongkorn's Memorial Day", my: 'Chulalongkorn အောက်မေ့ဖွယ်နေ့' },
        '2026-12-31': { th: 'วันสิ้นปี', en: "New Year's Eve", my: 'နှစ်ကုန်နေ့' }
      }
    };

    /* รูปทรงสัญลักษณ์ ตรงตามไฟล์ Excel ต้นฉบับ */
    var shapes = {
      sunday: '<circle cx="12" cy="12" r="10"/>',
      public: '<rect x="2.5" y="3" width="19" height="18" rx="1"/>',
      substitution: '<polygon points="12,2 14.5,8.6 21.5,8.9 16,13.3 17.9,20.1 12,16.2 6.1,20.1 8,13.3 2.5,8.9 9.5,8.6"/>',
      compensate: '<polygon points="12,2.6 22,21.4 2,21.4"/>',
      vacation: '<polygon points="12,2.5 21.5,12 12,21.5 2.5,12"/>',
      special: '<polygon points="7,3 17,3 22,12 17,21 7,21 2,12"/>'
    };

    var marks = (function () {
      var out = {};
      for (var scope in cal.scopes) {
        out[scope] = {};
        var groups = cal.scopes[scope];
        for (var type in groups) {
          for (var i = 0; i < groups[type].length; i++) {
            out[scope][groups[type][i]] = type;
          }
        }
      }
      return out;
    })();

    /* ══════════════════════════════════════════════════════════
       4. สถานะ + ตัวช่วย
       ══════════════════════════════════════════════════════════ */
    var LANG_KEY = 'simenu_language';
    var langs = ['th', 'en', 'my'];
    var locales = { th: 'th-TH', en: 'en-US', my: 'my-MM' };
    var langCodes = { th: 'TH', en: 'EN', my: 'MY' };
    var lang = 'th';
    var newsCat = '';
    var alertCat = '';
    var appCat = '';
    var calScope = 'office';
    var calMonth = new Date().getMonth();
    var modalScope = 'office';
    var modalMonth = -1;          /* -1 = มุมมองทั้งปี · 0-11 = ดูเดือนนั้น */
    var toastTimer = null;

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
    var tr = function (key) { return (t[lang] && t[lang][key]) || t.th[key] || key; };
    var esc = function (s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    };
    var pad2 = function (n) { return (n < 10 ? '0' : '') + n; };
    var iso = function (y, m, d) { return y + '-' + pad2(m + 1) + '-' + pad2(d); };

    function toast(msg) {
      var el = $('#toast');
      el.textContent = msg;
      el.classList.add('on');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () { el.classList.remove('on'); }, 2600);
    }

    /* ══════════════════════════════════════════════════════════
       5. ภาษา
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

      /* หัวข้อ/เนื้อหาประกาศ */
      $$('[data-news]').forEach(function (el) {
        var title = el.getAttribute('data-title-' + lang) || el.getAttribute('data-title-th');
        var body = el.getAttribute('data-body-' + lang) || el.getAttribute('data-body-th');
        var tEl = $('[data-title]', el);
        var bEl = $('[data-body]', el);
        if (tEl && title) { tEl.textContent = title; }
        if (bEl) { bEl.textContent = body || ''; }
      });

      /* ชื่อฝ่ายในหน้าติดต่อ */
      $$('[data-dept]').forEach(function (el) {
        var deptName = el.getAttribute('data-name-' + lang) || el.getAttribute('data-name-th');
        if (deptName) { el.textContent = deptName; }
      });

      /* ชื่อหมวดประกาศบนชิป */
      $$('.mini-chip[data-cat]').forEach(function (el) {
        var name = el.getAttribute('data-name-' + lang);
        if (name) { el.textContent = name; }
      });

      $('#langFlag').src = 'img/flags/' + lang + '.png';
      $('#langCode').textContent = langCodes[lang];
      $$('#langPanel .pop-item').forEach(function (b) {
        b.setAttribute('aria-current', b.getAttribute('data-lang') === lang ? 'true' : 'false');
      });

      renderAppChips();
      renderApps();
      renderMiniCal();
      updateClock();
      syncTabArrows();
      renderPagers();
      typeBanner();

      if ($('#mCalendar').classList.contains('on')) { renderModalCal(); }
    }

    /* ══════════════════════════════════════════════════════════
       6. นาฬิกา
       ══════════════════════════════════════════════════════════ */
    function updateClock() {
      var now = new Date();
      var dateEl = $('#clockDate');
      var timeEl = $('#clockTime');

      if (timeEl) {
        timeEl.textContent = pad2(now.getHours()) + ':' + pad2(now.getMinutes()) + ':' + pad2(now.getSeconds());
      }

      if (dateEl) {
        dateEl.textContent = now.toLocaleDateString(locales[lang], {
          weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
      }
    }

    /* ══════════════════════════════════════════════════════════
       7. ประกาศ — กรองตามหมวด
       ══════════════════════════════════════════════════════════ */
    /* ── พาจิเนทของ 2 ช่องประกาศ — หน้าละ 5 รายการ ──
         ถ้าเปลี่ยนตัวเลขนี้ ต้องแก้ --post-per-page ใน CSS ให้ตรงกัน
         รายการภายในแต่ละหน้าจะเลื่อนด้วยเมาส์แยกกันในแต่ละการ์ด */
    var POSTS_PER_PAGE = 5;

    /* ระบบงานภายใน — 4 คอลัมน์ × 3 แถว (ต้องตรงกับ --app-cols ใน CSS) */
    var APPS_PER_PAGE = 12;
    var appPage = 0;
    var modalAppCat = '';

    var pagers = {
      alert: { list: 'alertList', dots: 'alertDots', foot: 'alertFoot', none: 'alertNone', page: 0, cat: '' },
      feed: { list: 'feedList', dots: 'feedDots', foot: 'feedFoot', none: 'feedNone', page: 0, cat: '' }
    };

    /* เล่นแอนิเมชันเลื่อนซ้ำได้ ต้องถอดคลาสแล้วบังคับ reflow ก่อนใส่ใหม่ */
    function playSlide(el, dir) {
      if (!el || !dir) { return; }

      el.classList.remove('slide-next', 'slide-prev');
      void el.offsetWidth;
      el.classList.add(dir > 0 ? 'slide-next' : 'slide-prev');
    }

    function renderPager(key, dir) {
      var p = pagers[key];
      var listEl = document.getElementById(p.list);
      if (!listEl) { return; }

      var all = $$('.post', listEl);

      var items = all.filter(function (el) {
        return p.cat === '' || el.getAttribute('data-cat') === p.cat;
      });

      /* แสดงหน้าละ 5 รายการคงที่ (ดู --post-per-page ในไฟล์ CSS ด้วย) */
      var per = POSTS_PER_PAGE;

      var pages = items.length > 0 ? Math.ceil(items.length / per) : 1;
      if (p.page >= pages) { p.page = pages - 1; }
      if (p.page < 0) { p.page = 0; }

      /* ซ่อนทั้งหมด แล้วโชว์เฉพาะหน้าปัจจุบัน */
      all.forEach(function (el) { el.hidden = true; });
      items.slice(p.page * per, p.page * per + per).forEach(function (el) { el.hidden = false; });

      /* จุดพาจิเนท */
      var dots = document.getElementById(p.dots);
      if (dots) {
        var html = '';
        for (var i = 0; i < pages; i++) {
          html += '<button type="button" data-pager="' + key + '" data-goto="' + i + '" aria-current="' +
            (i === p.page ? 'true' : 'false') + '" aria-label="' + tr('pageWord') + ' ' + (i + 1) + '"></button>';
        }
        dots.innerHTML = html;
      }

      var foot = document.getElementById(p.foot);
      if (foot) { foot.hidden = pages <= 1; }

      $$('[data-pager="' + key + '"][data-dir]').forEach(function (b) {
        b.disabled = pages <= 1;
      });

      var none = document.getElementById(p.none);
      if (none) { none.hidden = items.length > 0; }

      listEl.scrollTop = 0;
      playSlide(listEl, dir);
    }

    function renderPagers() {
      renderPager('alert');
      renderPager('feed');
    }

    /* หมายเหตุ: เดิมมี ResizeObserver คอยคำนวณจำนวนต่อหน้าใหม่ตามความสูงช่อง
       ตอนนี้ล็อกไว้ 5 รายการ/หน้าแล้ว จึงไม่ต้องเฝ้าขนาด — และตัดทิ้งไปเลย
       เพราะ callback ที่แก้ DOM แล้ววนกลับมาเรียกตัวเองทำให้เลย์เอาต์กระตุกได้ */

    function movePage(key, dir) {
      var p = pagers[key];
      if (!p) { return; }
      p.page += dir;
      renderPager(key, dir);
    }

    /* ประกาศทั่วไป */
    function filterNews(catId) {
      newsCat = catId;
      pagers.feed.cat = catId;
      pagers.feed.page = 0;

      $$('.mini-chip[data-cat]').forEach(function (b) {
        b.setAttribute('aria-selected', b.getAttribute('data-cat') === catId ? 'true' : 'false');
      });

      renderPager('feed');
    }

    /* ประกาศสำคัญ (ปักหมุด) — แท็บของตัวเอง แยกจากประกาศทั่วไป */
    function filterAlerts(catId) {
      alertCat = catId;
      pagers.alert.cat = catId;
      pagers.alert.page = 0;

      $$('.mini-chip[data-alertcat]').forEach(function (b) {
        b.setAttribute('aria-selected', b.getAttribute('data-alertcat') === catId ? 'true' : 'false');
      });

      renderPager('alert');
    }

    /* ปุ่ม ‹ › ของแถบแท็บ — โชว์เฉพาะตอนล้นกรอบ */
    function syncTabArrows() {
      $$('[data-tabscroll]').forEach(function (b) {
        var strip = document.getElementById(b.getAttribute('data-strip'));
        b.hidden = !strip || strip.scrollWidth <= strip.clientWidth + 2;
      });
    }

    /* ══════════════════════════════════════════════════════════
       8. ระบบงาน
       ══════════════════════════════════════════════════════════ */
    function appCopy(id) {
      var row = appText[id];
      return (row && (row[lang] || row.en || row.th)) || ['', ''];
    }

    function renderAppChips() {
      var box = $('#appChips');
      if (!box) { return; }

      box.innerHTML = appCats.map(function (c) {
        return '<button class="chip" role="tab" type="button" data-appcat="' + c.key + '" aria-selected="' +
          (c.key === appCat ? 'true' : 'false') + '">' + esc(c[lang] || c.en) + '</button>';
      }).join('');

      syncChipArrows();
    }

    /* โชว์ปุ่ม ‹ › เฉพาะตอนชิปล้นกรอบ */
    function syncChipArrows() {
      var box = $('#appChips');
      if (!box) { return; }

      var overflow = box.scrollWidth > box.clientWidth + 2;

      $$('[data-chipscroll]').forEach(function (b) { b.hidden = !overflow; });
    }

    function renderApps(dir) {
      var grid = $('#appGrid');
      if (!grid) { return; }

      var all = apps.filter(function (a) { return appCat === '' || a.cat === appCat; });

      /* แบ่งหน้าเมื่อแอปเกิน 3 แถว (4 คอลัมน์ × 3 แถว = 12 ตัว/หน้า) */
      var pages = all.length > 0 ? Math.ceil(all.length / APPS_PER_PAGE) : 1;
      if (appPage >= pages) { appPage = pages - 1; }
      if (appPage < 0) { appPage = 0; }

      var list = all.slice(appPage * APPS_PER_PAGE, appPage * APPS_PER_PAGE + APPS_PER_PAGE);

      var appsDots = $('#appsDots');
      if (appsDots) {
        var dotHtml = '';
        for (var d = 0; d < pages; d++) {
          dotHtml += '<button type="button" data-appgoto="' + d + '" aria-current="' +
            (d === appPage ? 'true' : 'false') + '" aria-label="' + tr('pageWord') + ' ' + (d + 1) + '"></button>';
        }
        appsDots.innerHTML = dotHtml;
      }

      /* ไม่ใช้ hidden — สลับคลาสแทน แถบยังยึดที่ไว้ ความสูงการ์ดจึงคงที่ทุกแท็บ */
      var appsFoot = $('#appsFoot');
      if (appsFoot) {
        appsFoot.hidden = false;
        if (pages > 1) { appsFoot.className = 'card-foot is-on'; }
        else { appsFoot.className = 'card-foot'; }
      }

      grid.innerHTML = list.map(function (a) {
        var copy = appCopy(a.id);
        var inner = '<span class="app-icon"><img src="' + a.img + '" alt="" loading="lazy" decoding="async"></span><b>' + esc(copy[0]) + '</b>';

        if (a.url) {
          return '<a class="app" href="launch.php?app=' + encodeURIComponent(a.id) + '&amp;lang=' + encodeURIComponent(lang) +
            '" data-app="' + a.id + '" title="' + esc(copy[0]) + (copy[1] ? ' — ' + esc(copy[1]) : '') + '">' + inner + '</a>';
        }
        return '<button class="app off" type="button" data-app="' + a.id + '" aria-disabled="true" title="' + esc(copy[0]) + '">' + inner + '</button>';
      }).join('');

      $('#appNone').hidden = all.length > 0;

      playSlide(grid, dir);
    }

    /* ── ระบบงานภายในในโมดัล — แท็บและสถานะแยกจากการ์ดบนหน้า ── */
    function appCardHtml(a) {
      var copy = appCopy(a.id);
      var inner = '<span class="app-icon"><img src="' + a.img + '" alt="" loading="lazy" decoding="async"></span><b>' + esc(copy[0]) + '</b>';

      if (a.url) {
        return '<a class="app" href="launch.php?app=' + encodeURIComponent(a.id) + '&amp;lang=' + encodeURIComponent(lang) +
          '" data-app="' + a.id + '">' + inner + '</a>';
      }
      return '<button class="app off" type="button" data-app="' + a.id + '" aria-disabled="true">' + inner + '</button>';
    }

    function renderModalAppChips() {
      var box = $('#mAppChips');
      if (!box) { return; }

      box.innerHTML = appCats.map(function (c) {
        return '<button class="chip" role="tab" type="button" data-mappcat="' + c.key + '" aria-selected="' +
          (c.key === modalAppCat ? 'true' : 'false') + '">' + esc(c[lang] || c.en) + '</button>';
      }).join('');
    }

    function renderModalApps() {
      var grid = $('#mAppGrid');
      if (!grid) { return; }

      var list = apps.filter(function (a) { return modalAppCat === '' || a.cat === modalAppCat; });

      grid.innerHTML = list.map(appCardHtml).join('');
      $('#mAppNone').hidden = list.length > 0;
    }

    /* ══════════════════════════════════════════════════════════
       9. ปฏิทิน
       ══════════════════════════════════════════════════════════ */
    function dayType(scope, y, m, d) {
      var key = iso(y, m, d);
      var found = marks[scope] && marks[scope][key];
      if (found) { return found; }
      return new Date(y, m, d).getDay() === 0 ? 'sunday' : '';
    }

    function markSvg(type) {
      if (!shapes[type]) { return ''; }
      return '<svg class="mk m-' + type + '" viewBox="0 0 24 24" aria-hidden="true">' + shapes[type] + '</svg>';
    }

    function monthName(m, style) {
      return new Date(cal.year, m, 1).toLocaleDateString(locales[lang], { month: style || 'long' });
    }

    function buildGrid(scope, y, m) {
      var first = new Date(y, m, 1).getDay();
      var days = new Date(y, m + 1, 0).getDate();
      var prevDays = new Date(y, m, 0).getDate();
      var today = new Date();
      var dow = [];
      var i;

      for (i = 0; i < 7; i++) {
        dow.push('<div class="cal-dow">' + new Date(2026, 1, 1 + i).toLocaleDateString(locales[lang], { weekday: 'narrow' }) + '</div>');
      }

      var cells = [];

      for (i = first - 1; i >= 0; i--) {
        cells.push('<div class="cal-day out"><span>' + (prevDays - i) + '</span></div>');
      }

      for (i = 1; i <= days; i++) {
        var type = dayType(scope, y, m, i);
        var isToday = today.getFullYear() === y && today.getMonth() === m && today.getDate() === i;
        var cls = 'cal-day' + (type ? ' is-' + type : '') + (isToday ? ' today' : '');
        cells.push('<div class="' + cls + '">' + markSvg(type) + '<span>' + i + '</span></div>');
      }

      var tail = (7 - (cells.length % 7)) % 7;
      for (i = 1; i <= tail; i++) {
        cells.push('<div class="cal-day out"><span>' + i + '</span></div>');
      }

      return dow.join('') + cells.join('');
    }

    function renderMiniCal(dir) {
      var grid = $('#miniCal');
      if (!grid) { return; }

      grid.innerHTML = buildGrid(calScope, cal.year, calMonth);

      var label = $('#monthLabel');
      if (label) { label.textContent = monthName(calMonth) + ' ' + cal.year; }

      playSlide(grid, dir);
    }

    /* ชื่อวันหยุด + ชื่อประเภท (ใช้ในรายการวันหยุดของมุมมองรายเดือน) */
    function holidayName(key, type) {
      var row = cal.names[key];
      if (row) { return row[lang] || row.en; }
      return typeLabel(type) || tr('lgPublic');
    }

    function typeLabel(type) {
      var map = {
        public: 'lgPublic', substitution: 'lgSubstitution',
        compensate: 'lgCompensate', vacation: 'lgVacation', special: 'lgSpecial'
      };
      return map[type] ? tr(map[type]) : '';
    }

    /* วันหยุดในเดือน (ไม่รวมวันอาทิตย์ปกติ) */
    function monthHolidays(scope, y, m) {
      var out = [];
      var prefix = y + '-' + pad2(m + 1) + '-';
      var map = marks[scope] || {};

      for (var key in map) {
        if (key.indexOf(prefix) === 0) {
          out.push({ key: key, type: map[key], day: parseInt(key.slice(8, 10), 10) });
        }
      }

      out.sort(function (a, b) { return a.day - b.day; });
      return out;
    }

    /* โมดัลปฏิทิน — สลับระหว่างมุมมองทั้งปีกับรายเดือน */
    function renderModalCal(dir) {
      var yearGrid = $('#yearGrid');
      var monthView = $('#monthView');
      var back = $('#calBack');
      if (!yearGrid) { return; }

      var isMonth = modalMonth >= 0;

      yearGrid.hidden = isMonth;
      if (monthView) { monthView.hidden = !isMonth; }
      if (back) { back.hidden = !isMonth; }

      if (!isMonth) {
        /* ── มุมมองทั้งปี ── */
        $('#mCalTitle').textContent = tr('calTitle') + ' ' + cal.year;
        yearGrid.innerHTML = '';

        for (var m = 0; m < 12; m++) {
          var card = document.createElement('button');
          card.className = 'month-card';
          card.type = 'button';
          card.setAttribute('data-month', m);
          card.innerHTML = '<b>' + esc(monthName(m, 'short')) + '</b><div class="cal-grid">' +
            buildGrid(modalScope, cal.year, m) + '</div>';
          yearGrid.appendChild(card);
        }

        playSlide(yearGrid, dir);
        return;
      }

      /* ── มุมมองรายเดือน ── */
      $('#mCalTitle').textContent = monthName(modalMonth) + ' ' + cal.year;

      var label = $('#mMonthLabel');
      if (label) { label.textContent = monthName(modalMonth) + ' ' + cal.year; }

      var grid = $('#mMonthGrid');
      if (grid) { grid.innerHTML = buildGrid(modalScope, cal.year, modalMonth); }

      playSlide(monthView, dir);

      var list = $('#mHolidayList');
      if (!list) { return; }

      var rows = monthHolidays(modalScope, cal.year, modalMonth);

      if (rows.length === 0) {
        list.innerHTML = '<div class="m-empty">' + esc(tr('noHoliday')) + '</div>';
        return;
      }

      list.innerHTML = rows.map(function (r) {
        var d = new Date(cal.year, modalMonth, r.day);
        var dowText = d.toLocaleDateString(locales[lang], { weekday: 'short' });
        var name = holidayName(r.key, r.type);
        var kind = typeLabel(r.type);

        /* วันที่ไม่มีชื่อเฉพาะจะถอยไปใช้ชื่อประเภท — อย่าเขียนซ้ำ 2 บรรทัด */
        var sub = (kind && kind !== name) ? '<span>' + esc(kind) + '</span>' : '';

        return '<div class="m-holiday">' +
          '<span class="m-holiday-date"><strong>' + r.day + '</strong><em>' + esc(dowText) + '</em></span>' +
          '<span class="m-holiday-name"><b>' + esc(name) + '</b>' + sub + '</span>' +
          markSvg(r.type) +
          '</div>';
      }).join('');
    }

    /* ══════════════════════════════════════════════════════════
       ข้อความแบนเนอร์ — ไล่พิมพ์หัวข้อ แล้วต่อด้วยรายละเอียด

       ใช้ตัวจับเวลาแยกจาก typeInto() ของป้ายลอย
       ไม่งั้นพอเอาเมาส์ไปชี้ที่อื่น hideAppPop() จะไปหยุดการพิมพ์ตรงนี้ด้วย
       ══════════════════════════════════════════════════════════ */
    var bannerTimer = null;
    var introDone = false;      /* เริ่มพิมพ์หลังฉากอินโทรหายไปแล้วเท่านั้น */

    function typeBannerInto(el, text, speed, onDone) {
      var i = 0;
      el.textContent = '';

      bannerTimer = setInterval(function () {
        el.textContent = text.slice(0, ++i);

        if (i >= text.length) {
          clearInterval(bannerTimer);
          bannerTimer = null;
          if (onDone) { onDone(); }
        }
      }, speed);
    }

    function typeBanner() {
      var wrap = $('#bannerCopy');
      var head = $('#bannerTitle');
      var text = $('#bannerText');
      if (!wrap || !head || !text) { return; }

      clearInterval(bannerTimer);
      wrap.classList.remove('phase-title', 'phase-text');

      var title = tr('heroTitle');
      var body = tr('heroText');

      /* เครื่องที่ตั้งค่าลดการเคลื่อนไหว — ใส่ข้อความเต็มไปเลย */
      var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (reduce) {
        head.textContent = title;
        text.textContent = body;
        return;
      }

      /* ฉากอินโทรยังไม่จบ — เว้นข้อความไว้ก่อน เดี๋ยวถูกเรียกอีกทีตอนอินโทรหาย */
      if (!introDone) {
        head.textContent = '';
        text.textContent = '';
        return;
      }

      head.textContent = '';
      text.textContent = '';
      wrap.classList.add('phase-title');

      typeBannerInto(head, title, 105, function () {
        wrap.classList.remove('phase-title');
        wrap.classList.add('phase-text');

        typeBannerInto(text, body, 34, function () {
          wrap.classList.remove('phase-text');
        });
      });
    }

    /* ══════════════════════════════════════════════════════════
       10. สไลด์แบนเนอร์
       ══════════════════════════════════════════════════════════ */
    var slideAt = 0;
    var slideTimer = null;

    function showSlide(index) {
      if (slideCount < 2) { return; }

      slideAt = (index + slideCount) % slideCount;

      $$('.banner-slide').forEach(function (s) {
        s.classList.toggle('is-on', parseInt(s.getAttribute('data-slide'), 10) === slideAt);
      });

      $$('#bannerDots button').forEach(function (b) {
        b.setAttribute('aria-current', parseInt(b.getAttribute('data-dot'), 10) === slideAt ? 'true' : 'false');
      });
    }

    function startSlides() {
      if (slideCount < 2) { return; }
      clearInterval(slideTimer);
      slideTimer = setInterval(function () { showSlide(slideAt + 1); }, 7000);
    }

    /* ══════════════════════════════════════════════════════════
       ป้ายลอยตอนชี้แอป — ไอคอนใหญ่ + คำอธิบายพิมพ์ทีละตัว
       ══════════════════════════════════════════════════════════ */
    var typeTimers = [];
    var popAt = null;

    function stopTyping() {
      for (var i = 0; i < typeTimers.length; i++) { clearInterval(typeTimers[i]); }
      typeTimers = [];
    }

    /* พิมพ์ข้อความทีละตัว แล้วเรียก onDone ตอนจบ (ต่อคิวกันได้) */
    function typeInto(el, text, speed, onDone) {
      if (!el) {
        if (onDone) { onDone(); }
        return;
      }

      el.textContent = '';

      var i = 0;
      var timer = setInterval(function () {
        el.textContent = text.slice(0, ++i);

        if (i >= text.length) {
          clearInterval(timer);
          if (onDone) { onDone(); }
        }
      }, speed);

      typeTimers.push(timer);
    }

    /* วางป้ายเหนือเป้าหมาย ถ้าชนขอบบนย้ายลงล่าง และคุมไม่ให้ล้นซ้าย-ขวา */
    function placePop(pop, target) {
      var r = target.getBoundingClientRect();
      var pw = pop.offsetWidth;
      var ph = pop.offsetHeight;
      var gap = 10;

      var left = r.left + (r.width / 2) - (pw / 2);
      left = Math.max(8, Math.min(left, window.innerWidth - pw - 8));

      var top = r.top - ph - gap;
      if (top < 8) { top = r.bottom + gap; }
      if (top + ph > window.innerHeight - 8) { top = Math.max(8, window.innerHeight - ph - 8); }

      pop.style.left = left + 'px';
      pop.style.top = top + 'px';
    }

    function hideAppPop() {
      stopTyping();
      popAt = null;

      var pop = $('#appPop');
      if (pop) { pop.classList.remove('on', 'is-done'); }

      var pPop = $('#postPop');
      if (pPop) { pPop.classList.remove('on', 'phase-title', 'phase-desc'); }
    }

    /* เล่นแอนิเมชันเด้งซ้ำทุกครั้งที่เปลี่ยนเป้าหมาย */
    function replayPop(el) {
      if (!el) { return; }
      el.style.animation = 'none';
      void el.offsetWidth;
      el.style.animation = '';
    }

    function showAppPop(tile) {
      var pop = $('#appPop');
      if (!pop) { return; }

      var id = tile.getAttribute('data-app');
      if (popAt === 'app:' + id) { return; }

      hideAppPop();
      popAt = 'app:' + id;

      var copy = appCopy(id);
      var img = tile.querySelector('.app-icon img');

      $('#appPopIcon').src = img ? img.getAttribute('src') : '';
      $('#appPopName').textContent = copy[0] || '';

      pop.classList.add('on');
      replayPop(pop.querySelector('.app-pop-icon'));

      typeInto($('#appPopDesc'), copy[1] || '', 18, function () {
        pop.classList.add('is-done');
      });

      placePop(pop, tile);
    }

    /* ป้ายลอยของประกาศ — ภาพตัวอย่าง แล้วไล่พิมพ์หัวข้อ ตามด้วยรายละเอียด */
    function showPostPop(card) {
      var pop = $('#postPop');
      if (!pop) { return; }

      var href = card.getAttribute('href') || '';
      if (popAt === 'post:' + href) { return; }

      hideAppPop();
      popAt = 'post:' + href;

      var title = card.getAttribute('data-title-' + lang) || card.getAttribute('data-title-th') || '';
      var body = card.getAttribute('data-body-' + lang) || card.getAttribute('data-body-th') || '';
      var thumb = card.querySelector('.post-thumb img');
      var media = pop.querySelector('.post-pop-media');

      /* ประกาศที่ไม่มีภาพปก ก็ซ่อนช่องภาพไปเลย */
      if (thumb) {
        $('#postPopImg').src = thumb.getAttribute('src');
        media.hidden = false;
        replayPop(media);
      } else {
        media.hidden = true;
      }

      $('#postPopTitle').textContent = '';
      $('#postPopDesc').textContent = '';
      pop.classList.add('on', 'phase-title');

      typeInto($('#postPopTitle'), title, 22, function () {
        pop.classList.remove('phase-title');
        pop.classList.add('phase-desc');

        typeInto($('#postPopDesc'), body, 12, function () {
          pop.classList.remove('phase-desc');
        });
      });

      placePop(pop, card);
    }

    document.addEventListener('mouseover', function (e) {
      var tile = e.target.closest('.app');
      if (tile) { showAppPop(tile); return; }

      var card = e.target.closest('.post');
      if (card) { showPostPop(card); return; }

      if (!e.target.closest('#appPop') && !e.target.closest('#postPop')) { hideAppPop(); }
    });

    window.addEventListener('scroll', hideAppPop, true);


    /* ══════════════════════════════════════════════════════════
       11. โมดัล + เมนู
       ══════════════════════════════════════════════════════════ */
    var lastFocus = null;
    var modalIds = { calendar: 'mCalendar', map: 'mMap', contact: 'mContact', apps: 'mApps', web: 'mWeb', fav: 'mFav' };

    function openModal(key) {
      var id = modalIds[key] || key;
      lastFocus = document.activeElement;
      $('#' + id).classList.add('on');

      if (id === 'mCalendar') {
        modalScope = calScope;
        modalMonth = -1;               /* เปิดมาที่มุมมองทั้ง 12 เดือนเสมอ */
        $$('[data-mscope]').forEach(function (b) {
          b.setAttribute('aria-selected', b.getAttribute('data-mscope') === modalScope ? 'true' : 'false');
        });
        renderModalCal();
      }

      if (id === 'mApps') {
        modalAppCat = appCat;          /* เริ่มที่หมวดเดียวกับการ์ดบนหน้า */
        renderModalAppChips();
        renderModalApps();
      }


      var close = $('#' + id + ' [data-close]');
      if (close) { close.focus(); }
    }

    function closeModal() {
      $$('.modal.on').forEach(function (m) { m.classList.remove('on'); });
      if (lastFocus) { lastFocus.focus(); }
    }

    function closePops() {
      $$('.pop-panel').forEach(function (p) { p.classList.remove('on'); });
      var ob = $('#orgBtn');
      var lb = $('#langBtn');
      if (ob) { ob.setAttribute('aria-expanded', 'false'); }
      if (lb) { lb.setAttribute('aria-expanded', 'false'); }
    }

    /* ── แท็บ "หน้าแรก" — คืนค่าทุกอย่างกลับหลักเริ่มต้น ── */
    function resetHome() {
      closeModal();
      closePops();
      hideAppPop();

      alertCat = '';
      newsCat = '';
      pagers.alert.cat = '';
      pagers.feed.cat = '';
      pagers.alert.page = 0;
      pagers.feed.page = 0;

      appCat = '';
      appPage = 0;
      modalAppCat = '';

      calScope = 'office';
      calMonth = new Date().getMonth();
      modalScope = 'office';
      modalMonth = -1;

      $$('.mini-chip[data-alertcat]').forEach(function (b) {
        b.setAttribute('aria-selected', b.getAttribute('data-alertcat') === '' ? 'true' : 'false');
      });

      $$('.mini-chip[data-cat]').forEach(function (b) {
        b.setAttribute('aria-selected', b.getAttribute('data-cat') === '' ? 'true' : 'false');
      });

      $$('[data-scope]').forEach(function (b) {
        b.setAttribute('aria-selected', b.getAttribute('data-scope') === 'office' ? 'true' : 'false');
      });

      renderAppChips();
      renderApps();
      renderMiniCal();
      renderPagers();
      showSlide(0);
      startSlides();

      if (document.body.scrollHeight > window.innerHeight + 4) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    }

    /* ══════════════════════════════════════════════════════════
       12. เชื่อมเหตุการณ์
       ══════════════════════════════════════════════════════════ */
    document.addEventListener('click', function (e) {
      var el;

      /* เมนูบน — "หน้าแรก" คืนค่าเริ่มต้นทั้งหมด */
      el = e.target.closest('[data-nav="home"]');
      if (el) {
        $$('.nav-link').forEach(function (b) { b.classList.remove('is-active'); });
        el.classList.add('is-active');
        resetHome();
        return;
      }

      /* หมวดระบบงานในโมดัล */
      el = e.target.closest('.chip[data-mappcat]');
      if (el) {
        modalAppCat = el.getAttribute('data-mappcat');
        renderModalAppChips();
        renderModalApps();
        return;
      }

      /* ปุ่มเลื่อนแถบแท็บหมวด */
      el = e.target.closest('[data-tabscroll]');
      if (el) {
        var tabStrip = document.getElementById(el.getAttribute('data-strip'));
        if (tabStrip) {
          tabStrip.scrollLeft += parseInt(el.getAttribute('data-tabscroll'), 10) * Math.max(110, tabStrip.clientWidth * 0.7);
        }
        return;
      }

      /* พาจิเนท: ลูกศรซ้าย/ขวา */
      el = e.target.closest('[data-pager][data-dir]');
      if (el) {
        movePage(el.getAttribute('data-pager'), parseInt(el.getAttribute('data-dir'), 10));
        return;
      }

      /* พาจิเนท: กดที่จุด — เลื่อนไปทางที่หน้าเปลี่ยนไป */
      el = e.target.closest('[data-pager][data-goto]');
      if (el) {
        var pk = el.getAttribute('data-pager');
        var goTo = parseInt(el.getAttribute('data-goto'), 10);
        var dotDir = goTo > pagers[pk].page ? 1 : (goTo < pagers[pk].page ? -1 : 0);
        pagers[pk].page = goTo;
        renderPager(pk, dotDir);
        return;
      }

      /* หมวดประกาศสำคัญ */
      el = e.target.closest('.mini-chip[data-alertcat]');
      if (el) { filterAlerts(el.getAttribute('data-alertcat')); return; }

      /* หมวดข่าวสาร */
      el = e.target.closest('.mini-chip[data-cat]');
      if (el) { filterNews(el.getAttribute('data-cat')); return; }

      /* โมดัลปฏิทิน: กดการ์ดเดือน → เข้าไปดูเดือนนั้น */
      el = e.target.closest('.month-card[data-month]');
      if (el) {
        modalMonth = parseInt(el.getAttribute('data-month'), 10);
        renderModalCal();
        return;
      }

      /* โมดัลปฏิทิน: ย้อนกลับไปมุมมองทั้ง 12 เดือน */
      if (e.target.closest('#calBack')) {
        modalMonth = -1;
        renderModalCal();
        return;
      }

      /* โมดัลปฏิทิน: เลื่อนเดือนในมุมมองรายเดือน */
      el = e.target.closest('[data-mshift]');
      if (el) {
        var mShift = parseInt(el.getAttribute('data-mshift'), 10);
        modalMonth = (modalMonth + mShift + 12) % 12;
        renderModalCal(mShift);
        return;
      }

      /* "ดูทั้งหมด" — ล้างตัวกรองของทั้ง 2 ช่องประกาศ */
      if (e.target.closest('[data-showall]')) {
        filterAlerts('');
        filterNews('');
        return;
      }

      /* ปุ่มเลื่อนแถบหมวดระบบงาน */
      el = e.target.closest('[data-chipscroll]');
      if (el) {
        var strip = $('#appChips');
        if (strip) {
          strip.scrollLeft += parseInt(el.getAttribute('data-chipscroll'), 10) * Math.max(120, strip.clientWidth * 0.7);
        }
        return;
      }

      /* แบ่งหน้าระบบงาน: ลูกศร */
      el = e.target.closest('[data-apppage]');
      if (el) {
        var appDir = parseInt(el.getAttribute('data-apppage'), 10);
        appPage += appDir;
        renderApps(appDir);
        return;
      }

      /* แบ่งหน้าระบบงาน: จุด */
      el = e.target.closest('[data-appgoto]');
      if (el) {
        var appGoTo = parseInt(el.getAttribute('data-appgoto'), 10);
        var appDotDir = appGoTo > appPage ? 1 : (appGoTo < appPage ? -1 : 0);
        appPage = appGoTo;
        renderApps(appDotDir);
        return;
      }

      /* หมวดระบบงาน */
      el = e.target.closest('.chip[data-appcat]');
      if (el) {
        appCat = el.getAttribute('data-appcat');
        appPage = 0;
        renderAppChips();
        renderApps();
        return;
      }

      /* การ์ดแอปที่ยังไม่มีลิงก์ */
      el = e.target.closest('.app.off');
      if (el) { toast(tr('noLink')); return; }

      /* สไลด์ */
      el = e.target.closest('[data-hero]');
      if (el) {
        showSlide(slideAt + (el.getAttribute('data-hero') === 'next' ? 1 : -1));
        startSlides();
        return;
      }

      el = e.target.closest('[data-dot]');
      if (el) {
        showSlide(parseInt(el.getAttribute('data-dot'), 10));
        startSlides();
        return;
      }

      /* ปฏิทิน: สลับสำนักงาน/โรงงาน — เลื่อนตามทิศที่แท็บอยู่ */
      el = e.target.closest('[data-scope]');
      if (el) {
        var nextScope = el.getAttribute('data-scope');
        if (nextScope === calScope) { return; }

        var scopeDir = nextScope === 'factory' ? 1 : -1;
        calScope = nextScope;

        $$('[data-scope]').forEach(function (b) {
          b.setAttribute('aria-selected', b.getAttribute('data-scope') === calScope ? 'true' : 'false');
        });

        renderMiniCal(scopeDir);
        return;
      }

      /* ปฏิทิน: เลื่อนเดือน */
      el = e.target.closest('[data-shift]');
      if (el) {
        var shift = parseInt(el.getAttribute('data-shift'), 10);
        calMonth = (calMonth + shift + 12) % 12;
        renderMiniCal(shift);
        return;
      }

      /* เปิดโมดัล */
      el = e.target.closest('[data-open]');
      if (el) {
        closePops();
        hideAppPop();

        /* ปุ่มบนแถบเมนูให้ไฮไลต์ตาม */
        if (el.classList.contains('nav-link')) {
          $$('.nav-link').forEach(function (b) { b.classList.remove('is-active'); });
          el.classList.add('is-active');
        }

        openModal(el.getAttribute('data-open'));
        return;
      }

      /* ปิดโมดัล */
      if (e.target.closest('[data-close]')) { closeModal(); return; }

      /* โมดัลปฏิทิน: สลับ scope */
      el = e.target.closest('[data-mscope]');
      if (el) {
        var nextMScope = el.getAttribute('data-mscope');
        if (nextMScope === modalScope) { return; }

        var mScopeDir = nextMScope === 'factory' ? 1 : -1;
        modalScope = nextMScope;

        $$('[data-mscope]').forEach(function (b) {
          b.setAttribute('aria-selected', b.getAttribute('data-mscope') === modalScope ? 'true' : 'false');
        });

        renderModalCal(mScopeDir);
        return;
      }

      /* เมนูข้อมูลองค์กร */
      if (e.target.closest('#orgBtn')) {
        var op = $('#orgPanel');
        var open = !op.classList.contains('on');
        closePops();
        op.classList.toggle('on', open);
        $('#orgBtn').setAttribute('aria-expanded', open ? 'true' : 'false');
        return;
      }

      /* เมนูภาษา */
      if (e.target.closest('#langBtn')) {
        var lp = $('#langPanel');
        var lopen = !lp.classList.contains('on');
        closePops();
        lp.classList.toggle('on', lopen);
        $('#langBtn').setAttribute('aria-expanded', lopen ? 'true' : 'false');
        return;
      }

      el = e.target.closest('[data-lang]');
      if (el) {
        setLang(el.getAttribute('data-lang'));
        closePops();
        return;
      }

      /* ยังไม่มีหน้าจริง */
      if (e.target.closest('[data-soon]')) {
        toast(tr('soon'));
        closePops();
        return;
      }

      /* คลิกนอกเมนู */
      if (!e.target.closest('.pop')) { closePops(); }
    });

    document.addEventListener('keydown', function (e) {
      var tab = e.target.closest && e.target.closest('[role="tab"]');
      if (tab && (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Home' || e.key === 'End')) {
        var tablist = tab.closest('[role="tablist"]');
        var items = tablist ? $$('[role="tab"]', tablist) : [];
        var index = items.indexOf(tab);
        var nextIndex = index;

        if (e.key === 'ArrowLeft') { nextIndex = (index - 1 + items.length) % items.length; }
        if (e.key === 'ArrowRight') { nextIndex = (index + 1) % items.length; }
        if (e.key === 'Home') { nextIndex = 0; }
        if (e.key === 'End') { nextIndex = items.length - 1; }

        if (items[nextIndex]) {
          e.preventDefault();
          items[nextIndex].focus();
          items[nextIndex].click();
        }
        return;
      }

      if (e.key === 'Escape') {
        closeModal();
        closePops();
      }
    });

    /* ══════════════════════════════════════════════════════════
       13. เริ่มทำงาน
       ══════════════════════════════════════════════════════════ */
    setLang(readLang(), false);
    startSlides();
    setInterval(updateClock, 1000);
    syncTabArrows();
    renderPagers();

    /* ฟอนต์โหลดเสร็จแล้วความสูงรายการอาจเปลี่ยน — คำนวณจำนวนต่อหน้าใหม่ */
    window.addEventListener('load', renderPagers);

    window.addEventListener('resize', function () {
      syncChipArrows();
      syncTabArrows();
      renderPagers();
    });

    (function startSiIntro() {
      var intro = $('#siIntro');
      var word = $('#siIntroWord');
      var target = $('#siIntroTarget');
      var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      /* อินโทรหายแล้วค่อยเริ่มพิมพ์ข้อความบนแบนเนอร์ */
      function removeIntro() {
        if (intro && intro.parentNode) { intro.parentNode.removeChild(intro); }
        introDone = true;
        typeBanner();
      }

      if (!intro || !word || !target || reduceMotion) {
        removeIntro();
        return;
      }

      window.requestAnimationFrame(function () {
        var wordRect = word.getBoundingClientRect();
        var targetRect = target.getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        var focusX = targetRect.left + (targetRect.width * 0.12);
        var focusY = targetRect.top + (targetRect.height * 0.56);
        var strokeWidth = Math.max(targetRect.width * 0.14, 1);
        var zoom = Math.min(180, Math.max(56, (Math.max(viewportWidth, viewportHeight) / strokeWidth) * 1.08));

        word.style.setProperty('--si-origin-x', (focusX - wordRect.left) + 'px');
        word.style.setProperty('--si-origin-y', (focusY - wordRect.top) + 'px');
        word.style.setProperty('--si-shift-x', ((viewportWidth / 2) - focusX) + 'px');
        word.style.setProperty('--si-shift-y', ((viewportHeight / 2) - focusY) + 'px');
        word.style.setProperty('--si-zoom', zoom);
        word.classList.add('is-running');
      });

      window.setTimeout(removeIntro, 2240);
    })();
  </script>
</body>

</html>
