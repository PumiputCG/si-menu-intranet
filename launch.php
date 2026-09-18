<?php
require_once dirname(__FILE__) . '/announcement_helpers.php';

$langs = array('th', 'en', 'my');
$lang = isset($_GET['lang']) && in_array($_GET['lang'], $langs) ? $_GET['lang'] : 'th';
$app_id = isset($_GET['app']) ? trim((string) $_GET['app']) : '';
$app = $app_id !== '' ? app_find($app_id) : null;
$target_url = is_array($app) && isset($app['url']) ? trim((string) $app['url']) : '';
$scheme = $target_url !== '' ? strtolower((string) parse_url($target_url, PHP_URL_SCHEME)) : '';
$is_valid = is_array($app) && $target_url !== '' && in_array($scheme, array('http', 'https'));

$copy = array(
  'th' => array(
    'loading' => 'กำลังเปิด',
    'missing' => 'ไม่พบระบบงาน',
    'missing_text' => 'ลิงก์นี้อาจถูกแก้ไขหรือปิดใช้งานโดยผู้ดูแลระบบ',
    'back' => 'กลับไปหน้าระบบงาน'
  ),
  'en' => array(
    'loading' => 'Opening',
    'missing' => 'Application not found',
    'missing_text' => 'This link may have been changed or disabled by an administrator.',
    'back' => 'Back to applications'
  ),
  'my' => array(
    'loading' => 'ဖွင့်နေသည်',
    'missing' => 'အက်ပ်ကို မတွေ့ပါ',
    'missing_text' => 'ဤလင့်ခ်ကို စီမံခန့်ခွဲသူက ပြောင်းလဲထားခြင်း သို့မဟုတ် ပိတ်ထားခြင်း ဖြစ်နိုင်သည်။',
    'back' => 'အက်ပ်များသို့ ပြန်သွားရန်'
  )
);

$text = $copy[$lang];
$app_name = $is_valid ? app_name($app, $lang) : '';

if (!$is_valid) {
  header('HTTP/1.1 404 Not Found');
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#424242">
  <meta name="robots" content="noindex,nofollow">
  <title><?php echo htmlspecialchars($is_valid ? $app_name : $text['missing'], ENT_QUOTES); ?> | SI GROUP</title>
  <link rel="icon" type="image/png" href="img/3si.png">
  <?php if ($is_valid) { ?>
    <meta http-equiv="refresh" content="2;url=<?php echo htmlspecialchars($target_url, ENT_QUOTES); ?>">
  <?php } ?>
  <style>
    :root {
      --accent: #064ba6;   /* น้ำเงินของระบบ (เดิมเทา #424242) */
      --ink: #052a57;
      --white: #ffffff;
      --ease: cubic-bezier(0.22, 1, 0.36, 1);
    }

    * { box-sizing: border-box; }

    html,
    body {
      width: 100%;
      height: 100%;
      min-height: 100%;
    }

    body {
      margin: 0;
      display: grid;
      place-items: center;
      overflow: hidden;
      min-height: 100svh;
      background: var(--accent);
      color: var(--white);
      font-family: Inter, "Noto Sans Thai", "Noto Sans Myanmar", system-ui, -apple-system, "Segoe UI", sans-serif;
      -webkit-font-smoothing: antialiased;
    }

    body.is-error { background: var(--ink); }

    .launch {
      position: fixed;
      inset: 0;
      display: grid;
      place-items: center;
      width: 100%;
      min-height: 100svh;
      padding: 20px;
      text-align: center;
    }

    .launch > [role="status"] {
      width: min(1000px, 100%);
      transform: translateY(-1vh);
    }

    .loader-word {
      position: relative;
      display: inline-block;
      max-width: 100%;
      color: rgba(255, 255, 255, 0.18);
      font-size: clamp(2.1rem, 7.5vw, 5.2rem);
      font-weight: 700;
      letter-spacing: -0.055em;
      line-height: 1;
      overflow-wrap: anywhere;
    }

    .loader-word::after {
      content: attr(data-text);
      position: absolute;
      inset: 0;
      overflow: hidden;
      color: var(--white);
      clip-path: inset(0 100% 0 0);
      animation: text-fill 820ms var(--ease) forwards;
    }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0 0 0 0);
      white-space: nowrap;
      border: 0;
    }

    .error-mark {
      width: 52px;
      height: 52px;
      display: grid;
      place-items: center;
      margin: 0 auto 22px;
      border: 1px solid rgba(255, 255, 255, 0.35);
      border-radius: 50%;
      font-size: 1.35rem;
    }

    .error h1 {
      margin: 0;
      font-size: clamp(1.6rem, 5vw, 2.4rem);
      line-height: 1.25;
    }

    .error p {
      max-width: 520px;
      margin: 12px auto 0;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
      line-height: 1.7;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 44px;
      margin-top: 24px;
      padding: 0 18px;
      border: 1px solid rgba(255, 255, 255, 0.4);
      border-radius: 999px;
      color: var(--white);
      font-size: 0.875rem;
      font-weight: 600;
      text-decoration: none;
    }

    .back-link:hover,
    .back-link:focus-visible {
      background: var(--white);
      color: var(--ink);
    }

    .back-link:focus-visible {
      outline: 2px solid var(--white);
      outline-offset: 4px;
    }

    @keyframes text-fill {
      to { clip-path: inset(0 0 0 0); }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-delay: 0ms !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>
<body class="<?php echo $is_valid ? 'is-loading' : 'is-error'; ?>">
  <main class="launch">
    <?php if ($is_valid) { ?>
      <div role="status" aria-live="polite" aria-label="<?php echo htmlspecialchars($text['loading'] . ' ' . $app_name, ENT_QUOTES); ?>">
        <span class="loader-word" data-text="<?php echo htmlspecialchars($app_name, ENT_QUOTES); ?>" aria-hidden="true"><?php echo htmlspecialchars($app_name, ENT_QUOTES); ?></span>
      </div>
    <?php } else { ?>
      <div class="error" role="alert">
        <div class="error-mark" aria-hidden="true">!</div>
        <h1><?php echo htmlspecialchars($text['missing'], ENT_QUOTES); ?></h1>
        <p><?php echo htmlspecialchars($text['missing_text'], ENT_QUOTES); ?></p>
        <a class="back-link" href="index.php#apps"><?php echo htmlspecialchars($text['back'], ENT_QUOTES); ?></a>
      </div>
    <?php } ?>
  </main>

  <?php if ($is_valid) { ?>
    <script>
      'use strict';
      (function () {
        var target = <?php echo json_encode($target_url); ?>;
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.setTimeout(function () {
          window.location.replace(target);
        }, reduceMotion ? 120 : 920);
      })();
    </script>
  <?php } ?>
</body>
</html>
