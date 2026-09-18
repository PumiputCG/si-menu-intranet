<?php
require_once dirname(__FILE__) . '/announcement_helpers.php';
$announcements = announcement_read_all();
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HR Announcement | SI Intranet Portal</title>
  <link rel="icon" type="image/x-icon" href="./img/logo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f4f7fb;
      --surface: #ffffff;
      --ink: #10172a;
      --muted: #53617a;
      --line: #e4e9f1;
      --accent: #424242;
      --accent-ink: #1a1a1a;
      --accent-soft: #f1f1f1;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      background: var(--bg);
      color: var(--ink);
      font-family: "Inter", "Noto Sans Thai", system-ui, sans-serif;
    }

    .page {
      width: min(100%, 980px);
      margin: 0 auto;
      padding: 32px 18px 56px;
    }

    .top {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      align-items: center;
      margin-bottom: 24px;
    }

    h1 {
      margin: 0;
      font-size: clamp(1.8rem, 4vw, 2.7rem);
      line-height: 1.1;
    }

    .subtitle {
      margin: 8px 0 0;
      color: var(--muted);
    }

    .link {
      color: var(--accent-ink);
      font-weight: 700;
      text-decoration: none;
      white-space: nowrap;
    }

    .list {
      display: grid;
      gap: 14px;
    }

    .item,
    .empty {
      padding: 20px;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: var(--surface);
    }

    .date {
      color: var(--muted);
      font-size: 0.82rem;
      font-weight: 600;
    }

    .meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 8px;
    }

    .new-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      min-height: 24px;
      padding: 0 9px;
      border-radius: 999px;
      background: #fee2e2;
      color: #b91c1c;
      font-size: 0.7rem;
      font-weight: 800;
      text-transform: uppercase;
    }

    .new-badge::before {
      content: "";
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #ef4444;
    }

    .title {
      margin: 0 0 8px;
      font-size: 1.12rem;
    }

    .body {
      margin: 0;
      color: var(--muted);
      line-height: 1.7;
      white-space: pre-line;
    }

    .files {
      display: grid;
      gap: 8px;
      margin-top: 14px;
    }

    .file {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border: 1px solid var(--line);
      border-radius: 14px;
      background: var(--surface);
      color: var(--ink);
      font-size: 0.9rem;
      font-weight: 700;
      text-decoration: none;
    }

    .file:hover {
      border-color: var(--accent);
      color: var(--accent-ink);
    }

    .file-kind {
      display: inline-grid;
      place-items: center;
      width: 36px;
      height: 26px;
      flex: 0 0 auto;
      border-radius: 999px;
      background: #e0f2fe;
      color: #0369a1;
      font-size: 0.65rem;
      font-weight: 900;
    }

    .file-kind.pdf {
      background: #fee2e2;
      color: #b91c1c;
    }

    .file-kind.word {
      background: #dbeafe;
      color: #1d4ed8;
    }

    .file-kind.excel {
      background: #dcfce7;
      color: #15803d;
    }

    .file-kind.powerpoint {
      background: #ffedd5;
      color: #c2410c;
    }

    .file-kind.document {
      background: #f1f5f9;
      color: #475569;
    }

    @media (max-width: 640px) {
      .top {
        align-items: flex-start;
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
  <main class="page">
    <div class="top">
      <div>
        <h1>Announcement</h1>
        <p class="subtitle">ประกาศจาก HR สำหรับพนักงาน</p>
      </div>
      <a class="link" href="index.php">กลับหน้าแรก</a>
    </div>

    <div class="list">
      <?php if (count($announcements) === 0) { ?>
        <div class="empty">ยังไม่มีประกาศจาก HR</div>
      <?php } ?>

      <?php foreach ($announcements as $announcement) { ?>
        <?php
          $attachments = announcement_get_attachments($announcement);
          $isNew = announcement_is_new($announcement);
        ?>
        <article class="item">
          <div class="meta">
            <span class="date"><?php echo announcement_h(announcement_format_date($announcement['created_at'])); ?></span>
            <?php if ($isNew) { ?>
              <span class="new-badge">New</span>
            <?php } ?>
          </div>
          <h2 class="title"><?php echo announcement_h($announcement['title']); ?></h2>
          <p class="body"><?php echo announcement_h($announcement['body']); ?></p>
          <?php if (count($attachments) > 0) { ?>
            <div class="files">
              <?php foreach ($attachments as $attachment) { ?>
                <?php
                  $fileKind = announcement_file_kind($attachment);
                  $fileId = isset($attachment['id']) ? $attachment['id'] : (isset($attachment['stored_name']) ? $attachment['stored_name'] : '');
                ?>
                <a class="file" href="announcement_download.php?id=<?php echo urlencode($announcement['id']); ?>&amp;file=<?php echo urlencode($fileId); ?>" target="_blank" rel="noopener noreferrer">
                  <span class="file-kind <?php echo announcement_h($fileKind); ?>"><?php echo announcement_h(announcement_file_short_label($fileKind)); ?></span>
                  <span><?php echo announcement_h($attachment['original_name']); ?></span>
                </a>
              <?php } ?>
            </div>
          <?php } ?>
        </article>
      <?php } ?>
    </div>
  </main>
</body>

</html>
