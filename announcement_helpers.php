<?php
define('ANNOUNCEMENT_ADMIN_USERNAME', 'admin');
define('ANNOUNCEMENT_ADMIN_PASSWORD', '000000');
define('ANNOUNCEMENT_DATA_DIR', dirname(__FILE__) . '/data');
define('ANNOUNCEMENT_DATA_FILE', ANNOUNCEMENT_DATA_DIR . '/announcements.json');
define('ANNOUNCEMENT_CATEGORY_FILE', ANNOUNCEMENT_DATA_DIR . '/categories.json');
define('ANNOUNCEMENT_UPLOAD_DIR', dirname(__FILE__) . '/uploads/announcements');
define('ANNOUNCEMENT_COVER_DIR', ANNOUNCEMENT_UPLOAD_DIR . '/covers');
define('ANNOUNCEMENT_MAX_FILE_SIZE', 100 * 1024 * 1024);

/* ภาพสไลด์หน้าแรก + ระบบงาน (แอดมินจัดการเองได้) */
define('HERO_DATA_FILE', ANNOUNCEMENT_DATA_DIR . '/hero_slides.json');
define('HERO_UPLOAD_DIR', dirname(__FILE__) . '/uploads/hero');
define('APP_DATA_FILE', ANNOUNCEMENT_DATA_DIR . '/apps.json');
define('APP_CAT_FILE', ANNOUNCEMENT_DATA_DIR . '/app_categories.json');
define('APP_ICON_DIR', dirname(__FILE__) . '/uploads/appicons');

function announcement_start_session()
{
  if (function_exists('session_status')) {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    return;
  }

  if (session_id() === '') {
    session_start();
  }
}

function announcement_h($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function announcement_field($announcement, $key)
{
  return isset($announcement[$key]) ? $announcement[$key] : '';
}

function announcement_ensure_storage()
{
  if (!is_dir(ANNOUNCEMENT_DATA_DIR)) {
    mkdir(ANNOUNCEMENT_DATA_DIR, 0775, true);
  }

  if (!is_dir(ANNOUNCEMENT_UPLOAD_DIR)) {
    mkdir(ANNOUNCEMENT_UPLOAD_DIR, 0775, true);
  }

  if (!is_dir(ANNOUNCEMENT_COVER_DIR)) {
    mkdir(ANNOUNCEMENT_COVER_DIR, 0775, true);
  }

  if (!file_exists(ANNOUNCEMENT_DATA_FILE)) {
    file_put_contents(ANNOUNCEMENT_DATA_FILE, "[]");
  }

  if (!file_exists(ANNOUNCEMENT_CATEGORY_FILE)) {
    file_put_contents(ANNOUNCEMENT_CATEGORY_FILE, announcement_json_encode(announcement_category_defaults()));
  }
}

// ── หมวดหมู่ประกาศ (หัวข้อใหญ่) — แอดมินเพิ่ม/ลบเองได้ กลายเป็นแท็บในหน้าประกาศ ──

function announcement_category_defaults()
{
  return array(
    array(
      'id' => 'news',
      'name_th' => 'ข่าวสาร',
      'name_en' => 'News',
      'name_my' => 'သတင်း',
      'created_at' => date('Y-m-d H:i:s')
    )
  );
}

function announcement_category_read_all()
{
  announcement_ensure_storage();
  $raw = file_get_contents(ANNOUNCEMENT_CATEGORY_FILE);
  $items = json_decode($raw, true);

  if (!is_array($items)) {
    return array();
  }

  $clean = array();
  foreach ($items as $item) {
    if (is_array($item) && isset($item['id']) && trim((string) $item['id']) !== '') {
      $clean[] = $item;
    }
  }

  return $clean;
}

function announcement_category_write_all($items)
{
  announcement_ensure_storage();
  $encoded = announcement_json_encode(array_values($items));

  if ($encoded === false) {
    return false;
  }

  return file_put_contents(ANNOUNCEMENT_CATEGORY_FILE, $encoded, LOCK_EX) !== false;
}

function announcement_category_find($id)
{
  $id = trim((string) $id);
  if ($id === '') {
    return null;
  }

  foreach (announcement_category_read_all() as $item) {
    if ($item['id'] === $id) {
      return $item;
    }
  }

  return null;
}

function announcement_category_name($category, $lang)
{
  if (!is_array($category)) {
    return '';
  }

  $key = 'name_' . $lang;
  if (isset($category[$key]) && trim((string) $category[$key]) !== '') {
    return $category[$key];
  }

  return isset($category['name_th']) ? $category['name_th'] : '';
}

// สร้าง id จากชื่อไทย/อังกฤษ ถ้าเป็นอักษรไทยล้วนจะ fallback เป็น cat_<hex>
function announcement_category_make_id($nameEn, $nameTh)
{
  $seed = trim((string) $nameEn) !== '' ? $nameEn : $nameTh;
  $slug = strtolower(trim((string) $seed));
  $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
  $slug = trim($slug, '-');

  if ($slug === '' || strlen($slug) < 2) {
    $slug = 'cat-' . substr(md5(uniqid('', true)), 0, 8);
  }

  $existing = announcement_category_read_all();
  $taken = array();
  foreach ($existing as $item) {
    $taken[$item['id']] = true;
  }

  if (!isset($taken[$slug])) {
    return $slug;
  }

  $counter = 2;
  while (isset($taken[$slug . '-' . $counter])) {
    $counter++;
  }

  return $slug . '-' . $counter;
}

function announcement_category_create($nameTh, $nameEn, $nameMy, &$errors)
{
  $errors = array();
  $nameTh = trim((string) $nameTh);
  $nameEn = trim((string) $nameEn);
  $nameMy = trim((string) $nameMy);

  if ($nameTh === '') {
    $errors[] = 'กรุณากรอกชื่อหัวข้อ (ภาษาไทย)';
    return false;
  }

  $items = announcement_category_read_all();

  foreach ($items as $item) {
    if (isset($item['name_th']) && $item['name_th'] === $nameTh) {
      $errors[] = 'มีหัวข้อชื่อนี้อยู่แล้ว';
      return false;
    }
  }

  $items[] = array(
    'id' => announcement_category_make_id($nameEn, $nameTh),
    'name_th' => $nameTh,
    'name_en' => $nameEn !== '' ? $nameEn : $nameTh,
    'name_my' => $nameMy !== '' ? $nameMy : $nameTh,
    'created_at' => date('Y-m-d H:i:s')
  );

  if (!announcement_category_write_all($items)) {
    $errors[] = 'บันทึกหัวข้อไม่สำเร็จ';
    return false;
  }

  return true;
}

// ลบหมวด: ประกาศที่อยู่ในหมวดนี้จะถูกย้ายไปเป็น "ไม่ระบุหมวด" ไม่ถูกลบตาม
function announcement_category_delete($id, &$errors)
{
  $errors = array();
  $id = trim((string) $id);

  if ($id === '') {
    $errors[] = 'ไม่พบหัวข้อที่ต้องการลบ';
    return false;
  }

  $items = announcement_category_read_all();
  $next = array();
  $found = false;

  foreach ($items as $item) {
    if ($item['id'] === $id) {
      $found = true;
      continue;
    }
    $next[] = $item;
  }

  if (!$found) {
    $errors[] = 'ไม่พบหัวข้อที่ต้องการลบ';
    return false;
  }

  if (!announcement_category_write_all($next)) {
    $errors[] = 'ลบหัวข้อไม่สำเร็จ';
    return false;
  }

  $announcements = announcement_read_all();
  $changed = false;
  foreach ($announcements as $index => $item) {
    if (isset($item['category']) && $item['category'] === $id) {
      $announcements[$index]['category'] = '';
      $changed = true;
    }
  }

  if ($changed) {
    announcement_write_all($announcements);
  }

  return true;
}

// จำนวนประกาศในแต่ละหมวด ใช้แสดงตัวเลขบนแท็บ
function announcement_category_counts($announcements)
{
  $counts = array();

  foreach ($announcements as $item) {
    $key = isset($item['category']) && trim((string) $item['category']) !== '' ? $item['category'] : '';
    if ($key === '') {
      continue;
    }
    if (!isset($counts[$key])) {
      $counts[$key] = 0;
    }
    $counts[$key]++;
  }

  return $counts;
}

function announcement_json_encode($data)
{
  if (defined('JSON_PRETTY_PRINT') && defined('JSON_UNESCAPED_UNICODE')) {
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  return json_encode($data);
}

function announcement_read_all()
{
  announcement_ensure_storage();
  $raw = file_get_contents(ANNOUNCEMENT_DATA_FILE);
  $items = json_decode($raw, true);

  if (!is_array($items)) {
    return array();
  }

  usort($items, 'announcement_sort_latest');
  return $items;
}

function announcement_sort_latest($a, $b)
{
  $aPinned = announcement_is_pinned($a) ? 1 : 0;
  $bPinned = announcement_is_pinned($b) ? 1 : 0;

  if ($aPinned !== $bPinned) {
    return ($aPinned < $bPinned) ? 1 : -1;
  }

  $aTime = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
  $bTime = isset($b['created_at']) ? strtotime($b['created_at']) : 0;

  if ($aTime === $bTime) {
    return 0;
  }

  return ($aTime < $bTime) ? 1 : -1;
}

function announcement_is_pinned($announcement)
{
  if (!isset($announcement['pinned'])) {
    return false;
  }

  return $announcement['pinned'] === true || $announcement['pinned'] === 1 || $announcement['pinned'] === '1';
}

function announcement_write_all($items)
{
  announcement_ensure_storage();
  $encoded = announcement_json_encode(array_values($items));

  if ($encoded === false) {
    return false;
  }

  return file_put_contents(ANNOUNCEMENT_DATA_FILE, $encoded, LOCK_EX) !== false;
}

function announcement_allowed_extensions()
{
  return array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'webp');
}

function announcement_safe_id()
{
  if (function_exists('random_bytes')) {
    return date('YmdHis') . bin2hex(random_bytes(6));
  }

  return date('YmdHis') . mt_rand(100000, 999999);
}

function announcement_format_file_size($bytes)
{
  $bytes = (int) $bytes;
  if ($bytes >= 1048576) {
    return number_format($bytes / 1048576, 1) . ' MB';
  }

  if ($bytes >= 1024) {
    return number_format($bytes / 1024, 1) . ' KB';
  }

  return $bytes . ' B';
}

function announcement_gallery_images()
{
  $galleryDir = dirname(__FILE__) . '/img/gallery';
  $galleryImages = array();

  if (!is_dir($galleryDir)) {
    return $galleryImages;
  }

  $allowedExt = array('jpg', 'jpeg', 'png', 'webp', 'gif');
  $filtered = array();
  $files = scandir($galleryDir);
  if ($files !== false) {
    foreach ($files as $file) {
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      if (in_array($ext, $allowedExt)) {
        $filtered[] = $file;
      }
    }
  }

  natcasesort($filtered);
  foreach ($filtered as $file) {
    $galleryImages[] = 'img/gallery/' . rawurlencode($file);
  }

  return array_values($galleryImages);
}

function announcement_get_attachments($announcement)
{
  if (isset($announcement['attachments']) && is_array($announcement['attachments'])) {
    return array_values($announcement['attachments']);
  }

  if (isset($announcement['attachment']) && is_array($announcement['attachment'])) {
    return array($announcement['attachment']);
  }

  return array();
}

function announcement_file_kind($attachment)
{
  $extension = '';
  if (isset($attachment['extension'])) {
    $extension = strtolower((string) $attachment['extension']);
  } elseif (isset($attachment['original_name'])) {
    $extension = strtolower(pathinfo($attachment['original_name'], PATHINFO_EXTENSION));
  } elseif (isset($attachment['stored_name'])) {
    $extension = strtolower(pathinfo($attachment['stored_name'], PATHINFO_EXTENSION));
  }

  if (in_array($extension, array('jpg', 'jpeg', 'png', 'webp'))) {
    return 'image';
  }

  if ($extension === 'pdf') {
    return 'pdf';
  }

  if (in_array($extension, array('doc', 'docx'))) {
    return 'word';
  }

  if (in_array($extension, array('xls', 'xlsx'))) {
    return 'excel';
  }

  if (in_array($extension, array('ppt', 'pptx'))) {
    return 'powerpoint';
  }

  return 'document';
}

function announcement_file_short_label($kind)
{
  $labels = array(
    'image' => 'IMG',
    'pdf' => 'PDF',
    'word' => 'DOC',
    'excel' => 'XLS',
    'powerpoint' => 'PPT',
    'document' => 'FILE'
  );

  return isset($labels[$kind]) ? $labels[$kind] : 'FILE';
}

function announcement_file_icon_svg($kind)
{
  $icons = array(
    'pdf' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" fill="currentColor" fill-opacity="0.14"/><path d="M14 2.5v3.2A1.3 1.3 0 0 0 15.3 7h3.2" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><text x="12" y="16.6" text-anchor="middle" font-size="6.6" font-weight="800" fill="currentColor" font-family="Arial, sans-serif">PDF</text></svg>',
    'word' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" fill="currentColor" fill-opacity="0.14"/><path d="M14 2.5v3.2A1.3 1.3 0 0 0 15.3 7h3.2" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><text x="12" y="16.6" text-anchor="middle" font-size="6.6" font-weight="800" fill="currentColor" font-family="Arial, sans-serif">DOC</text></svg>',
    'excel' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" fill="currentColor" fill-opacity="0.14"/><path d="M14 2.5v3.2A1.3 1.3 0 0 0 15.3 7h3.2" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><text x="12" y="16.6" text-anchor="middle" font-size="6.6" font-weight="800" fill="currentColor" font-family="Arial, sans-serif">XLS</text></svg>',
    'powerpoint' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" fill="currentColor" fill-opacity="0.14"/><path d="M14 2.5v3.2A1.3 1.3 0 0 0 15.3 7h3.2" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><text x="12" y="16.6" text-anchor="middle" font-size="6.6" font-weight="800" fill="currentColor" font-family="Arial, sans-serif">PPT</text></svg>',
    'image' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="4.5" width="17" height="15" rx="2" fill="currentColor" fill-opacity="0.14" stroke="currentColor" stroke-width="1.4"/><circle cx="8.5" cy="9.5" r="1.6" fill="currentColor"/><path d="m4 17 5-5 3.5 3.5L17 11l3.5 3.5" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" stroke-linecap="round"/></svg>',
    'document' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 2.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V4A1.5 1.5 0 0 1 7 2.5Z" fill="currentColor" fill-opacity="0.14" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M14 2.5v3.2A1.3 1.3 0 0 0 15.3 7h3.2" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M9 13h6M9 16.3h4.2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>'
  );

  return isset($icons[$kind]) ? $icons[$kind] : $icons['document'];
}

function announcement_file_icon_url($kind)
{
  $urls = array(
    'pdf' => 'img/file-icons/pdf.png',
    'word' => 'img/file-icons/word.webp',
    'excel' => 'img/file-icons/excel.jpg',
    'powerpoint' => 'img/file-icons/powerpoint.jpg',
    'image' => 'img/file-icons/image.png'
  );

  return isset($urls[$kind]) ? $urls[$kind] : '';
}

function announcement_attachment_url($announcementId, $attachment)
{
  $fileId = isset($attachment['id']) ? $attachment['id'] : (isset($attachment['stored_name']) ? $attachment['stored_name'] : '');
  return 'announcement_download.php?id=' . rawurlencode($announcementId) . '&file=' . rawurlencode($fileId);
}

function announcement_date_day($date)
{
  $time = strtotime($date);
  if (!$time) {
    return '';
  }

  return date('d', $time);
}

function announcement_date_month_short_th($date)
{
  $time = strtotime($date);
  if (!$time) {
    return '';
  }

  $months = array('', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.');
  return $months[(int) date('n', $time)];
}

function announcement_is_new($announcement)
{
  if (!isset($announcement['created_at'])) {
    return false;
  }

  $time = strtotime($announcement['created_at']);
  if (!$time) {
    return false;
  }

  return (time() - $time) < 86400;
}

function announcement_normalize_files($files)
{
  if (!isset($files) || !isset($files['name'])) {
    return array();
  }

  if (!is_array($files['name'])) {
    return array($files);
  }

  $normalized = array();
  $count = count($files['name']);
  for ($i = 0; $i < $count; $i++) {
    $normalized[] = array(
      'name' => isset($files['name'][$i]) ? $files['name'][$i] : '',
      'type' => isset($files['type'][$i]) ? $files['type'][$i] : '',
      'tmp_name' => isset($files['tmp_name'][$i]) ? $files['tmp_name'][$i] : '',
      'error' => isset($files['error'][$i]) ? $files['error'][$i] : UPLOAD_ERR_NO_FILE,
      'size' => isset($files['size'][$i]) ? $files['size'][$i] : 0
    );
  }

  return $normalized;
}

function announcement_delete_uploaded_files($attachments)
{
  foreach ($attachments as $attachment) {
    if (!isset($attachment['stored_name'])) {
      continue;
    }

    $filePath = ANNOUNCEMENT_UPLOAD_DIR . '/' . basename($attachment['stored_name']);
    if (is_file($filePath)) {
      unlink($filePath);
    }
  }
}

function announcement_store_uploaded_file($file, &$error)
{
  $error = '';

  if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    return null;
  }

  if ($file['error'] !== UPLOAD_ERR_OK) {
    $error = 'อัปโหลดไฟล์ไม่สำเร็จ';
    return false;
  }

  if ($file['size'] > ANNOUNCEMENT_MAX_FILE_SIZE) {
    $error = 'ไฟล์ต้องมีขนาดไม่เกิน 100MB';
    return false;
  }

  $originalName = basename($file['name']);
  $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
  if (!in_array($extension, announcement_allowed_extensions())) {
    $error = 'รองรับเฉพาะไฟล์ PDF, Office และรูปภาพ';
    return false;
  }

  announcement_ensure_storage();
  $storedName = announcement_safe_id() . '.' . $extension;
  $targetPath = ANNOUNCEMENT_UPLOAD_DIR . '/' . $storedName;

  if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $error = 'บันทึกไฟล์แนบไม่สำเร็จ';
    return false;
  }

  return array(
    'id' => announcement_safe_id(),
    'original_name' => $originalName,
    'stored_name' => $storedName,
    'size' => (int) $file['size'],
    'extension' => $extension
  );
}

function announcement_upload_file($file, &$error)
{
  return announcement_store_uploaded_file($file, $error);
}

/* บันทึกภาพหน้าปกจาก data URL (ภาพที่ผู้ใช้ครอปในเบราว์เซอร์)
   ทำฝั่ง client เพราะเซิร์ฟเวอร์ไม่มี GD extension */
function announcement_store_cover_from_data($dataUrl, &$error)
{
  $error = '';
  $dataUrl = trim((string) $dataUrl);

  if ($dataUrl === '') {
    return null;
  }

  if (strpos($dataUrl, 'data:image/') !== 0) {
    $error = 'ข้อมูลภาพหน้าปกไม่ถูกต้อง';
    return false;
  }

  $comma = strpos($dataUrl, ',');
  if ($comma === false) {
    $error = 'ข้อมูลภาพหน้าปกไม่ถูกต้อง';
    return false;
  }

  $meta = substr($dataUrl, 0, $comma);
  $raw = substr($dataUrl, $comma + 1);

  $extension = 'jpg';
  if (strpos($meta, 'image/png') !== false) {
    $extension = 'png';
  } elseif (strpos($meta, 'image/webp') !== false) {
    $extension = 'webp';
  }

  $binary = base64_decode($raw);

  if ($binary === false || strlen($binary) === 0) {
    $error = 'ถอดรหัสภาพหน้าปกไม่สำเร็จ';
    return false;
  }

  if (strlen($binary) > ANNOUNCEMENT_MAX_FILE_SIZE) {
    $error = 'ภาพหน้าปกต้องมีขนาดไม่เกิน 100MB';
    return false;
  }

  announcement_ensure_storage();
  $storedName = announcement_safe_id() . '.' . $extension;

  if (file_put_contents(ANNOUNCEMENT_COVER_DIR . '/' . $storedName, $binary) === false) {
    $error = 'บันทึกภาพหน้าปกไม่สำเร็จ';
    return false;
  }

  return array(
    'original_name' => 'cover.' . $extension,
    'stored_name' => $storedName,
    'extension' => $extension
  );
}

function announcement_store_cover_image($file, &$error, $slot = 1)
{
  $error = '';
  $postKey = ((int) $slot === 2) ? 'cover2_cropped' : 'cover_cropped';

  /* ถ้ามีภาพที่ครอปมาแล้ว ให้ใช้อันนั้นก่อน */
  if (isset($_POST[$postKey]) && trim((string) $_POST[$postKey]) !== '') {
    return announcement_store_cover_from_data($_POST[$postKey], $error);
  }

  if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    return null;
  }

  if ($file['error'] !== UPLOAD_ERR_OK) {
    $error = 'อัปโหลดภาพหน้าปกไม่สำเร็จ';
    return false;
  }

  if ($file['size'] > ANNOUNCEMENT_MAX_FILE_SIZE) {
    $error = 'ภาพหน้าปกต้องมีขนาดไม่เกิน 100MB';
    return false;
  }

  $originalName = basename($file['name']);
  $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
  if (!in_array($extension, array('jpg', 'jpeg', 'png', 'webp'))) {
    $error = 'ภาพหน้าปกรองรับเฉพาะไฟล์ JPG, PNG หรือ WEBP';
    return false;
  }

  announcement_ensure_storage();
  $storedName = announcement_safe_id() . '.' . $extension;
  $targetPath = ANNOUNCEMENT_COVER_DIR . '/' . $storedName;

  if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $error = 'บันทึกภาพหน้าปกไม่สำเร็จ';
    return false;
  }

  return array(
    'original_name' => $originalName,
    'stored_name' => $storedName,
    'extension' => $extension
  );
}

function announcement_cover_url($announcement)
{
  return announcement_cover_slot_url($announcement, 1);
}

/* ภาพปก 2 = ภาพหัวเรื่องในหน้าอ่านประกาศ (ถ้าไม่มีให้ถอยไปใช้ภาพปก 1) */
function announcement_cover2_url($announcement)
{
  $url = announcement_cover_slot_url($announcement, 2);
  return $url !== '' ? $url : announcement_cover_slot_url($announcement, 1);
}

/* $slot 1 = ภาพปกในหน้าแรก · 2 = ภาพหัวเรื่องหน้าอ่านประกาศ */
function announcement_cover_slot_url($announcement, $slot)
{
  $key = ((int) $slot === 2) ? 'cover_image_2' : 'cover_image';

  if (!isset($announcement[$key]) || !is_array($announcement[$key])) {
    return '';
  }

  $cover = $announcement[$key];
  if (!isset($cover['stored_name']) || $cover['stored_name'] === '') {
    return '';
  }

  return 'uploads/announcements/covers/' . rawurlencode($cover['stored_name']);
}

function announcement_delete_cover_image($announcement, $slot = 1)
{
  $key = ((int) $slot === 2) ? 'cover_image_2' : 'cover_image';

  if (!isset($announcement[$key]) || !is_array($announcement[$key])) {
    return;
  }

  $cover = $announcement[$key];
  if (!isset($cover['stored_name'])) {
    return;
  }

  $filePath = ANNOUNCEMENT_COVER_DIR . '/' . basename($cover['stored_name']);
  if (is_file($filePath)) {
    unlink($filePath);
  }
}

function announcement_upload_files($files, &$errors)
{
  $errors = array();
  $attachments = array();
  $normalizedFiles = announcement_normalize_files($files);

  foreach ($normalizedFiles as $file) {
    $uploadError = '';
    $attachment = announcement_store_uploaded_file($file, $uploadError);

    if ($attachment === false) {
      if ($uploadError !== '') {
        $errors[] = $uploadError;
      }
      announcement_delete_uploaded_files($attachments);
      return false;
    }

    if ($attachment !== null) {
      $attachments[] = $attachment;
    }
  }

  return $attachments;
}

function announcement_create($title, $body, $files, $coverFile, $translations, &$errors, $pinned = false, $category = '')
{
  $errors = array();
  $title = trim($title);
  $body = trim($body);
  $titleEn = isset($translations['en']['title']) ? trim($translations['en']['title']) : '';
  $bodyEn = isset($translations['en']['body']) ? trim($translations['en']['body']) : '';
  $titleMy = isset($translations['my']['title']) ? trim($translations['my']['title']) : '';
  $bodyMy = isset($translations['my']['body']) ? trim($translations['my']['body']) : '';

  if ($title === '') {
    $errors[] = 'กรุณากรอกหัวข้อประกาศ';
  }

  if ($body === '') {
    $errors[] = 'กรุณากรอกรายละเอียดประกาศ';
  }

  /* ต้องเลือกแท็บเสมอ */
  if (trim((string) $category) === '' || !announcement_category_find($category)) {
    $errors[] = 'กรุณาเลือกแท็บของประกาศ';
  }

  if (count($errors) > 0) {
    return false;
  }

  $uploadErrors = array();
  $attachments = announcement_upload_files($files, $uploadErrors);
  if ($attachments === false) {
    $errors = array_merge($errors, $uploadErrors);
  }

  $coverError = '';
  $coverImage = announcement_store_cover_image($coverFile, $coverError, 1);
  if ($coverImage === false) {
    $errors[] = $coverError;
  }

  /* ภาพปก 2 — รับจาก $_FILES / $_POST โดยตรง */
  $cover2Error = '';
  $cover2File = isset($_FILES['cover_image_2']) ? $_FILES['cover_image_2'] : null;
  $coverImage2 = announcement_store_cover_image($cover2File, $cover2Error, 2);
  if ($coverImage2 === false) {
    $errors[] = $cover2Error;
  }

  if (count($errors) > 0) {
    if (is_array($attachments)) {
      announcement_delete_uploaded_files($attachments);
    }
    return false;
  }

  $items = announcement_read_all();
  array_unshift($items, array(
    'id' => announcement_safe_id(),
    'title' => $title,
    'body' => $body,
    'title_en' => $titleEn,
    'body_en' => $bodyEn,
    'title_my' => $titleMy,
    'body_my' => $bodyMy,
    'attachment' => count($attachments) > 0 ? $attachments[0] : null,
    'attachments' => $attachments,
    'cover_image' => $coverImage,
    'cover_image_2' => $coverImage2,
    'category' => announcement_category_find($category) ? trim((string) $category) : '',
    'pinned' => $pinned ? true : false,
    'created_at' => date('Y-m-d H:i:s')
  ));

  if (!announcement_write_all($items)) {
    announcement_delete_uploaded_files($attachments);
    if ($coverImage) {
      announcement_delete_cover_image(array('cover_image' => $coverImage));
    }
    if ($coverImage2) {
      announcement_delete_cover_image(array('cover_image_2' => $coverImage2), 2);
    }
    $errors[] = 'บันทึกประกาศไม่สำเร็จ';
    return false;
  }

  return true;
}

function announcement_attachment_identifier($attachment)
{
  if (isset($attachment['id']) && trim((string) $attachment['id']) !== '') {
    return (string) $attachment['id'];
  }

  if (isset($attachment['stored_name']) && trim((string) $attachment['stored_name']) !== '') {
    return (string) $attachment['stored_name'];
  }

  return '';
}

function announcement_remaining_attachments($attachments, $removeIds, &$removed)
{
  $remaining = array();
  $removed = array();
  $removeMap = array();

  if (!is_array($removeIds)) {
    $removeIds = array($removeIds);
  }

  foreach ($removeIds as $removeId) {
    $key = trim((string) $removeId);
    if ($key !== '') {
      $removeMap[$key] = true;
    }
  }

  foreach ($attachments as $attachment) {
    $attachmentId = announcement_attachment_identifier($attachment);
    if ($attachmentId !== '' && isset($removeMap[$attachmentId])) {
      $removed[] = $attachment;
      continue;
    }

    $remaining[] = $attachment;
  }

  return $remaining;
}

function announcement_update($id, $title, $body, $files, $coverFile, $translations, $removeAttachmentIds, $removeCover, &$errors, $pinned = false, $category = '')
{
  $errors = array();
  $id = trim((string) $id);
  $title = trim($title);
  $body = trim($body);
  $titleEn = isset($translations['en']['title']) ? trim($translations['en']['title']) : '';
  $bodyEn = isset($translations['en']['body']) ? trim($translations['en']['body']) : '';
  $titleMy = isset($translations['my']['title']) ? trim($translations['my']['title']) : '';
  $bodyMy = isset($translations['my']['body']) ? trim($translations['my']['body']) : '';

  if ($id === '') {
    $errors[] = 'ไม่พบประกาศที่ต้องการแก้ไข';
  }

  if ($title === '') {
    $errors[] = 'กรุณากรอกหัวข้อประกาศ';
  }

  if ($body === '') {
    $errors[] = 'กรุณากรอกรายละเอียดประกาศ';
  }

  /* ต้องเลือกแท็บเสมอ */
  if (trim((string) $category) === '' || !announcement_category_find($category)) {
    $errors[] = 'กรุณาเลือกแท็บของประกาศ';
  }

  if (count($errors) > 0) {
    return false;
  }

  $items = announcement_read_all();
  $foundIndex = -1;
  $oldItem = null;

  foreach ($items as $index => $item) {
    if (isset($item['id']) && $item['id'] === $id) {
      $foundIndex = $index;
      $oldItem = $item;
      break;
    }
  }

  if ($foundIndex < 0 || !is_array($oldItem)) {
    $errors[] = 'ไม่พบประกาศที่ต้องการแก้ไข';
    return false;
  }

  $removedAttachments = array();
  $remainingAttachments = announcement_remaining_attachments(announcement_get_attachments($oldItem), $removeAttachmentIds, $removedAttachments);

  $uploadErrors = array();
  $newAttachments = announcement_upload_files($files, $uploadErrors);
  if ($newAttachments === false) {
    $errors = array_merge($errors, $uploadErrors);
    return false;
  }

  $coverError = '';
  $newCoverImage = announcement_store_cover_image($coverFile, $coverError, 1);
  if ($newCoverImage === false) {
    announcement_delete_uploaded_files($newAttachments);
    $errors[] = $coverError;
    return false;
  }

  $oldCoverImage = isset($oldItem['cover_image']) && is_array($oldItem['cover_image']) ? $oldItem['cover_image'] : null;
  $coverImage = $oldCoverImage;
  $deleteOldCover = false;

  if ($newCoverImage !== null) {
    $coverImage = $newCoverImage;
    $deleteOldCover = $oldCoverImage !== null;
  } elseif ($removeCover && $oldCoverImage !== null) {
    $coverImage = null;
    $deleteOldCover = true;
  }

  /* ภาพปก 2 */
  $cover2Error = '';
  $cover2File = isset($_FILES['cover_image_2']) ? $_FILES['cover_image_2'] : null;
  $newCoverImage2 = announcement_store_cover_image($cover2File, $cover2Error, 2);
  if ($newCoverImage2 === false) {
    announcement_delete_uploaded_files($newAttachments);
    $errors[] = $cover2Error;
    return false;
  }

  $oldCoverImage2 = isset($oldItem['cover_image_2']) && is_array($oldItem['cover_image_2']) ? $oldItem['cover_image_2'] : null;
  $coverImage2 = $oldCoverImage2;
  $deleteOldCover2 = false;

  /* ปุ่ม × ของภาพปก 2 ส่งค่ามาพร้อมฟอร์มแก้ไข (เหมือน remove_cover ของภาพปก 1) */
  $removeCover2 = isset($_POST['remove_cover_2']) && $_POST['remove_cover_2'] === '1';

  if ($newCoverImage2 !== null) {
    $coverImage2 = $newCoverImage2;
    $deleteOldCover2 = $oldCoverImage2 !== null;
  } elseif ($removeCover2 && $oldCoverImage2 !== null) {
    $coverImage2 = null;
    $deleteOldCover2 = true;
  }

  $attachments = array_merge($remainingAttachments, $newAttachments);
  $updatedItem = $oldItem;
  $updatedItem['title'] = $title;
  $updatedItem['body'] = $body;
  $updatedItem['title_en'] = $titleEn;
  $updatedItem['body_en'] = $bodyEn;
  $updatedItem['title_my'] = $titleMy;
  $updatedItem['body_my'] = $bodyMy;
  $updatedItem['attachment'] = count($attachments) > 0 ? $attachments[0] : null;
  $updatedItem['attachments'] = $attachments;
  $updatedItem['cover_image'] = $coverImage;
  $updatedItem['cover_image_2'] = $coverImage2;
  $updatedItem['category'] = announcement_category_find($category) ? trim((string) $category) : '';
  $updatedItem['pinned'] = $pinned ? true : false;
  $updatedItem['created_at'] = isset($oldItem['created_at']) ? $oldItem['created_at'] : date('Y-m-d H:i:s');
  $updatedItem['updated_at'] = date('Y-m-d H:i:s');

  $items[$foundIndex] = $updatedItem;

  if (!announcement_write_all($items)) {
    announcement_delete_uploaded_files($newAttachments);
    if ($newCoverImage !== null) {
      announcement_delete_cover_image(array('cover_image' => $newCoverImage));
    }
    if ($newCoverImage2 !== null) {
      announcement_delete_cover_image(array('cover_image_2' => $newCoverImage2), 2);
    }
    $errors[] = 'อัปเดตประกาศไม่สำเร็จ';
    return false;
  }

  if (count($removedAttachments) > 0) {
    announcement_delete_uploaded_files($removedAttachments);
  }

  if ($deleteOldCover && $oldCoverImage !== null) {
    announcement_delete_cover_image(array('cover_image' => $oldCoverImage));
  }

  if ($deleteOldCover2 && $oldCoverImage2 !== null) {
    announcement_delete_cover_image(array('cover_image_2' => $oldCoverImage2), 2);
  }

  return true;
}

function announcement_set_pinned($id, $pinned, &$errors)
{
  $errors = array();
  $id = trim((string) $id);

  if ($id === '') {
    $errors[] = 'ไม่พบประกาศที่ต้องการปักหมุด';
    return false;
  }

  $items = announcement_read_all();
  $found = false;

  foreach ($items as $index => $item) {
    if (isset($item['id']) && $item['id'] === $id) {
      $items[$index]['pinned'] = $pinned ? true : false;
      $items[$index]['updated_at'] = date('Y-m-d H:i:s');
      $found = true;
      break;
    }
  }

  if (!$found) {
    $errors[] = 'ไม่พบประกาศที่ต้องการปักหมุด';
    return false;
  }

  if (!announcement_write_all($items)) {
    $errors[] = 'อัปเดตสถานะปักหมุดไม่สำเร็จ';
    return false;
  }

  return true;
}

function announcement_delete($id)
{
  $items = announcement_read_all();
  $nextItems = array();
  $deleted = false;

  foreach ($items as $item) {
    if (isset($item['id']) && $item['id'] === $id) {
      announcement_delete_uploaded_files(announcement_get_attachments($item));
      announcement_delete_cover_image($item, 1);
      announcement_delete_cover_image($item, 2);
      $deleted = true;
      continue;
    }

    $nextItems[] = $item;
  }

  if ($deleted) {
    return announcement_write_all($nextItems);
  }

  return false;
}

function announcement_is_admin()
{
  announcement_start_session();
  return isset($_SESSION['announcement_admin']) && $_SESSION['announcement_admin'] === true;
}

function announcement_login($username, $password)
{
  announcement_start_session();
  if ($username === ANNOUNCEMENT_ADMIN_USERNAME && $password === ANNOUNCEMENT_ADMIN_PASSWORD) {
    $_SESSION['announcement_admin'] = true;
    session_regenerate_id(true);
    return true;
  }

  return false;
}

function announcement_logout()
{
  announcement_start_session();
  unset($_SESSION['announcement_admin']);
}

function announcement_csrf_token()
{
  announcement_start_session();
  if (!isset($_SESSION['announcement_csrf'])) {
    $_SESSION['announcement_csrf'] = announcement_safe_id();
  }

  return $_SESSION['announcement_csrf'];
}

function announcement_check_csrf($token)
{
  announcement_start_session();
  return isset($_SESSION['announcement_csrf']) && $token === $_SESSION['announcement_csrf'];
}

function announcement_format_date($date)
{
  $time = strtotime($date);
  if (!$time) {
    return '';
  }

  return date('d/m/Y H:i', $time);
}

// ── ข้อความสำหรับค้นหาประกาศ: หัวข้อ 3 ภาษา ──
// เขียนรองรับ PHP 5.2 (เซิร์ฟบริษัท)
function announcement_search_index($announcement)
{
  $parts = array();

  $parts[] = announcement_field($announcement, 'title');
  $parts[] = announcement_field($announcement, 'title_en');
  $parts[] = announcement_field($announcement, 'title_my');

  $text = implode(' ', $parts);

  if (function_exists('mb_strtolower')) {
    return mb_strtolower($text, 'UTF-8');
  }

  return strtolower($text);
}

// ── งวดของประกาศในรูปแบบ MM/YYYY ใช้กับ dropdown กรองเดือน/ปี ──
function announcement_period($announcement)
{
  $time = strtotime(announcement_field($announcement, 'created_at'));
  if (!$time) {
    return '';
  }

  return date('m/Y', $time);
}

// ── รายการงวดทั้งหมดที่มีประกาศ เรียงใหม่สุดก่อน ──
function announcement_period_list($announcements)
{
  $seen = array();
  foreach ($announcements as $announcement) {
    $period = announcement_period($announcement);
    if ($period === '') {
      continue;
    }
    // key เรียงได้ = YYYYMM
    $sortKey = substr($period, 3, 4) . substr($period, 0, 2);
    $seen[$sortKey] = $period;
  }

  krsort($seen);
  return array_values($seen);
}

function announcement_localized_title($announcement, $lang)
{
  if ($lang === 'en' && isset($announcement['title_en']) && trim((string) $announcement['title_en']) !== '') {
    return $announcement['title_en'];
  }

  if ($lang === 'my' && isset($announcement['title_my']) && trim((string) $announcement['title_my']) !== '') {
    return $announcement['title_my'];
  }

  return isset($announcement['title']) ? $announcement['title'] : '';
}

function announcement_localized_body($announcement, $lang)
{
  if ($lang === 'en' && isset($announcement['body_en']) && trim((string) $announcement['body_en']) !== '') {
    return $announcement['body_en'];
  }

  if ($lang === 'my' && isset($announcement['body_my']) && trim((string) $announcement['body_my']) !== '') {
    return $announcement['body_my'];
  }

  return isset($announcement['body']) ? $announcement['body'] : '';
}

// ── ตัดเนื้อหาให้สั้นสำหรับการ์ดข่าว (รองรับ UTF-8 ไทย/พม่า) ──
function announcement_excerpt($text, $limit = 130)
{
  $text = trim(preg_replace('/\s+/u', ' ', (string) $text));

  if ($text === '') {
    return '';
  }

  if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($text, 'UTF-8') <= $limit) {
      return $text;
    }
    return rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '…';
  }

  if (strlen($text) <= $limit) {
    return $text;
  }

  return rtrim(substr($text, 0, $limit)) . '…';
}

// หมวดของประกาศ ('' = ยังไม่จัดหมวด)
function announcement_category_of($announcement)
{
  if (!isset($announcement['category'])) {
    return '';
  }

  return trim((string) $announcement['category']);
}

/* ══════════════════════════════════════════════════════════════
   ภาพสไลด์หน้าแรก (hero) — แอดมินอัปโหลดเองได้
   ══════════════════════════════════════════════════════════════ */

function hero_ensure_storage()
{
  if (!is_dir(ANNOUNCEMENT_DATA_DIR)) {
    mkdir(ANNOUNCEMENT_DATA_DIR, 0775, true);
  }

  if (!is_dir(HERO_UPLOAD_DIR)) {
    mkdir(HERO_UPLOAD_DIR, 0775, true);
  }

  if (!file_exists(HERO_DATA_FILE)) {
    file_put_contents(HERO_DATA_FILE, announcement_json_encode(hero_defaults()));
  }
}

// ตั้งต้นจากไฟล์เดิมใน img/gallery เพื่อไม่ให้หน้าแรกว่างตอนย้ายมาใช้ JSON
function hero_defaults()
{
  $items = array();
  $dir = dirname(__FILE__) . '/img/gallery';

  if (!is_dir($dir)) {
    return $items;
  }

  $allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif');
  $files = scandir($dir);
  $picked = array();

  if ($files !== false) {
    foreach ($files as $file) {
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      if (in_array($ext, $allowed)) {
        $picked[] = $file;
      }
    }
  }

  natcasesort($picked);
  $order = 0;

  foreach ($picked as $file) {
    $items[] = array(
      'id' => 'seed-' . preg_replace('/[^A-Za-z0-9_]/', '_', $file),
      'path' => 'img/gallery/' . $file,
      'original_name' => $file,
      'sort' => $order,
      'created_at' => date('Y-m-d H:i:s')
    );
    $order++;
  }

  return $items;
}

function hero_read_all()
{
  hero_ensure_storage();
  $raw = file_get_contents(HERO_DATA_FILE);
  $items = json_decode($raw, true);

  if (!is_array($items)) {
    return array();
  }

  usort($items, 'hero_sort_cmp');
  return $items;
}

function hero_sort_cmp($a, $b)
{
  $aSort = isset($a['sort']) ? (int) $a['sort'] : 0;
  $bSort = isset($b['sort']) ? (int) $b['sort'] : 0;

  if ($aSort === $bSort) {
    return 0;
  }

  return ($aSort < $bSort) ? -1 : 1;
}

function hero_write_all($items)
{
  hero_ensure_storage();
  return file_put_contents(HERO_DATA_FILE, announcement_json_encode(array_values($items)), LOCK_EX) !== false;
}

// URL ที่ใช้แสดงผล — รองรับทั้งไฟล์เดิมใน img/gallery และไฟล์ที่อัปโหลดใหม่
function hero_url($slide)
{
  if (!is_array($slide) || !isset($slide['path']) || $slide['path'] === '') {
    return '';
  }

  $path = (string) $slide['path'];
  $parts = explode('/', $path);
  $encoded = array();

  foreach ($parts as $part) {
    $encoded[] = rawurlencode($part);
  }

  return implode('/', $encoded);
}

function hero_create($file, &$errors)
{
  $error = '';
  $stored = hero_store_image($file, $error);

  if ($stored === false) {
    $errors[] = $error;
    return false;
  }

  if ($stored === null) {
    $errors[] = 'กรุณาเลือกไฟล์ภาพ';
    return false;
  }

  $items = hero_read_all();
  $maxSort = -1;

  foreach ($items as $item) {
    $sort = isset($item['sort']) ? (int) $item['sort'] : 0;
    if ($sort > $maxSort) {
      $maxSort = $sort;
    }
  }

  $items[] = array(
    'id' => announcement_safe_id(),
    'path' => 'uploads/hero/' . $stored['stored_name'],
    'original_name' => $stored['original_name'],
    'sort' => $maxSort + 1,
    'created_at' => date('Y-m-d H:i:s')
  );

  if (!hero_write_all($items)) {
    $errors[] = 'บันทึกภาพสไลด์ไม่สำเร็จ';
    return false;
  }

  return true;
}

function hero_store_image($file, &$error)
{
  $error = '';

  if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    return null;
  }

  if ($file['error'] !== UPLOAD_ERR_OK) {
    $error = 'อัปโหลดภาพไม่สำเร็จ';
    return false;
  }

  if ($file['size'] > ANNOUNCEMENT_MAX_FILE_SIZE) {
    $error = 'ภาพต้องมีขนาดไม่เกิน 100MB';
    return false;
  }

  $originalName = basename($file['name']);
  $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

  if (!in_array($extension, array('jpg', 'jpeg', 'png', 'webp'))) {
    $error = 'ภาพสไลด์รองรับเฉพาะไฟล์ JPG, PNG หรือ WEBP';
    return false;
  }

  hero_ensure_storage();
  $storedName = announcement_safe_id() . '.' . $extension;

  if (!move_uploaded_file($file['tmp_name'], HERO_UPLOAD_DIR . '/' . $storedName)) {
    $error = 'บันทึกไฟล์ภาพไม่สำเร็จ';
    return false;
  }

  return array('original_name' => $originalName, 'stored_name' => $storedName);
}

function hero_delete($id, &$errors)
{
  $id = trim((string) $id);

  if ($id === '') {
    $errors[] = 'ไม่พบภาพที่ต้องการลบ';
    return false;
  }

  $items = hero_read_all();
  $next = array();
  $found = false;

  foreach ($items as $item) {
    if (isset($item['id']) && $item['id'] === $id) {
      $found = true;

      // ลบเฉพาะไฟล์ที่อัปโหลดผ่านระบบ ไม่แตะไฟล์เดิมใน img/gallery
      if (isset($item['path']) && strpos($item['path'], 'uploads/hero/') === 0) {
        $realFile = dirname(__FILE__) . '/' . $item['path'];
        if (file_exists($realFile)) {
          unlink($realFile);
        }
      }

      continue;
    }

    $next[] = $item;
  }

  if (!$found) {
    $errors[] = 'ไม่พบภาพที่ต้องการลบ';
    return false;
  }

  if (!hero_write_all($next)) {
    $errors[] = 'ลบภาพไม่สำเร็จ';
    return false;
  }

  return true;
}

// เลื่อนลำดับภาพขึ้น/ลง ($dir = -1 ขึ้น, 1 ลง)
function hero_move($id, $dir, &$errors)
{
  $items = hero_read_all();
  $index = -1;
  $count = count($items);

  for ($i = 0; $i < $count; $i++) {
    if (isset($items[$i]['id']) && $items[$i]['id'] === $id) {
      $index = $i;
      break;
    }
  }

  if ($index < 0) {
    $errors[] = 'ไม่พบภาพที่ต้องการย้าย';
    return false;
  }

  $target = $index + ((int) $dir);

  if ($target < 0 || $target >= $count) {
    return true;
  }

  $tmp = $items[$index];
  $items[$index] = $items[$target];
  $items[$target] = $tmp;

  for ($i = 0; $i < $count; $i++) {
    $items[$i]['sort'] = $i;
  }

  if (!hero_write_all($items)) {
    $errors[] = 'ย้ายลำดับไม่สำเร็จ';
    return false;
  }

  return true;
}

/* ══════════════════════════════════════════════════════════════
   ระบบงาน (Applications) ที่แสดงในหน้าภาพรวม
   ══════════════════════════════════════════════════════════════ */

function app_ensure_storage()
{
  if (!is_dir(ANNOUNCEMENT_DATA_DIR)) {
    mkdir(ANNOUNCEMENT_DATA_DIR, 0775, true);
  }

  if (!is_dir(APP_ICON_DIR)) {
    mkdir(APP_ICON_DIR, 0775, true);
  }

  if (!file_exists(APP_DATA_FILE)) {
    file_put_contents(APP_DATA_FILE, announcement_json_encode(app_defaults()));
  }
}

// หมวดของระบบงาน — ตั้งต้นชุดเดียวกับหน้าแรก แล้วแอดมินเพิ่ม/ลบเองได้
function app_category_defaults()
{
  return array(
    array('id' => 'hr', 'th' => 'บุคคล', 'en' => 'HR', 'my' => 'HR'),
    array('id' => 'booking', 'th' => 'จอง/นัด', 'en' => 'Booking', 'my' => 'ဘွတ်ကင်'),
    array('id' => 'request', 'th' => 'คำขอ/ซื้อ', 'en' => 'Requests', 'my' => 'တောင်းဆိုမှု'),
    array('id' => 'support', 'th' => 'IT', 'en' => 'IT', 'my' => 'IT'),
    array('id' => 'safety', 'th' => 'แจ้งซ่อม', 'en' => 'Repair', 'my' => 'Repair'),
    array('id' => 'admin', 'th' => 'เอกสาร', 'en' => 'Docs', 'my' => 'စာရွက်စာတမ်း')
  );
}

function app_category_list()
{
  app_ensure_storage();

  if (!file_exists(APP_CAT_FILE)) {
    file_put_contents(APP_CAT_FILE, announcement_json_encode(app_category_defaults()));
  }

  $items = json_decode(file_get_contents(APP_CAT_FILE), true);

  if (!is_array($items)) {
    return array();
  }

  return $items;
}

function app_category_write_all($items)
{
  app_ensure_storage();
  return file_put_contents(APP_CAT_FILE, announcement_json_encode(array_values($items)), LOCK_EX) !== false;
}

function app_category_create($nameTh, $nameEn, $nameMy, &$errors)
{
  $nameTh = trim((string) $nameTh);

  if ($nameTh === '') {
    $errors[] = 'กรุณากรอกชื่อหมวด (ไทย)';
    return false;
  }

  $nameEn = trim((string) $nameEn);
  $nameMy = trim((string) $nameMy);
  $items = app_category_list();

  $base = strtolower($nameEn !== '' ? $nameEn : $nameTh);
  $base = preg_replace('/[^a-z0-9]+/', '-', $base);
  $base = trim($base, '-');

  if ($base === '') {
    $base = 'cat';
  }

  $taken = array();
  foreach ($items as $item) {
    $taken[] = $item['id'];
  }

  $id = $base;
  $n = 2;
  while (in_array($id, $taken)) {
    $id = $base . '-' . $n;
    $n++;
  }

  $items[] = array(
    'id' => $id,
    'th' => $nameTh,
    'en' => $nameEn !== '' ? $nameEn : $nameTh,
    'my' => $nameMy !== '' ? $nameMy : $nameTh
  );

  if (!app_category_write_all($items)) {
    $errors[] = 'บันทึกหมวดไม่สำเร็จ';
    return false;
  }

  return true;
}

function app_category_delete($id, &$errors)
{
  $id = trim((string) $id);
  $items = app_category_list();
  $next = array();
  $found = false;

  foreach ($items as $item) {
    if ($item['id'] === $id) {
      $found = true;
      continue;
    }
    $next[] = $item;
  }

  if (!$found) {
    $errors[] = 'ไม่พบหมวดที่ต้องการลบ';
    return false;
  }

  if (!app_category_write_all($next)) {
    $errors[] = 'ลบหมวดไม่สำเร็จ';
    return false;
  }

  // แอปที่อยู่หมวดนี้ให้กลับไปเป็นไม่ระบุหมวด (ไม่ลบแอปทิ้ง)
  $apps = app_read_all();
  $changed = false;

  foreach ($apps as $index => $app) {
    if (isset($app['cat']) && $app['cat'] === $id) {
      $apps[$index]['cat'] = '';
      $changed = true;
    }
  }

  if ($changed) {
    app_write_all($apps);
  }

  return true;
}

function app_category_label($id, $lang)
{
  $key = in_array($lang, array('th', 'en', 'my')) ? $lang : 'th';

  foreach (app_category_list() as $cat) {
    if ($cat['id'] === $id) {
      return $cat[$key];
    }
  }

  return '';
}

// ตั้งต้นจาก 17 ระบบงานเดิมที่ hardcode ไว้ในหน้าแรก
function app_defaults()
{
  $rows = array(
    array('supavutInsight', 'hr', 'img/supavut-insight.png', 'http://192.168.7.12:8080/Insight/public/index.php', 'Supavut Insight', 'ระบบ HR ภายใน พื้นที่ 5ส และประเมินพนักงาน', 'Supavut Insight', 'HR hub: 5S area, employee assessment', 'Supavut Insight', 'HR hub: 5S area, assessment'),
    array('supavutAcademe', 'hr', 'img/supavut-academe-si.png', 'http://192.168.5.7/si-academe/login', 'Supavut Academe', 'อบรมพนักงาน เรียนออนไลน์ และพัฒนาทักษะ', 'Supavut Academe', 'Training hub: courses and skill development', 'Supavut Academe', 'ဝန်ထမ်းသင်တန်းစင်တာ'),
    array('ot', 'hr', 'img/e-leave-si.png', 'https://e-leave.supavut.com/Login/Login.aspx', 'E-Leaver', 'ลาออนไลน์และขออนุมัติการลา', 'E-Leaver', 'Online leave request and approval', 'E-Leaver', 'Online leave request'),
    array('penaltyBonus', 'hr', 'img/penalty-si.png', 'http://192.168.7.12:8080/Supavut_penalty&bonus/public/index.php', 'Supavut Penalty', 'จัดการบทลงโทษและโบนัส', 'Supavut Penalty', 'Penalty and bonus management', 'Supavut Penalty', 'Penalty and bonus management'),
    array('okrKpi', 'hr', 'img/okr-kpi-si.png', 'http://192.168.7.12:8080/okr-kpi-system/public/index.php', 'OKR - KPI', 'ติดตามเป้าหมายและตัวชี้วัดผลงาน', 'OKR - KPI', 'Objective and performance tracking', 'OKR - KPI', 'Objective and performance tracking'),
    array('meeting', 'booking', 'img/meeting.png', 'http://192.168.5.6/meeting/', 'Meeting Room', 'จองห้องประชุมและอุปกรณ์ส่วนกลาง', 'Meeting Room', 'Book meeting rooms and facilities', 'Meeting Room', 'အစည်းအဝေးခန်း ဘွတ်ကင်'),
    array('car', 'booking', 'img/car.png', 'http://192.168.5.6/vehicle/', 'Car System', 'จองรถบริษัทและจัดการการใช้รถ', 'Car System', 'Company car reservation and fleet', 'Car System', 'ကုမ္ပဏီကား ဘွတ်ကင်'),
    array('visitor', 'booking', 'img/vs.png', 'http://192.168.5.7/visitor/login.php', 'Visitor', 'ลงทะเบียนและจัดการผู้มาติดต่อ', 'Visitor', 'Visitor registration and appointments', 'Visitor', 'ဧည့်သည်စာရင်းသွင်းခြင်း'),
    array('pr', 'request', 'img/buy.png', 'http://192.168.5.7/RequestPR/login.php', 'Request PR', 'ขอเปิด PR และติดตามคำขอจัดซื้อ', 'Request PR', 'Purchase request and tracking', 'Request PR', 'ဝယ်ယူမှုတောင်းဆိုချက်'),
    array('prCompare', 'request', 'img/pr-compare.png', 'http://192.168.7.12:8080/QuoteCompare/public/login', 'QuoteCompare', 'เปรียบเทียบราคาผู้ขายและส่งอนุมัติ', 'QuoteCompare', 'Compare supplier quotes for approval', 'QuoteCompare', 'ဈေးနှုန်းများ နှိုင်းယှဉ်ရန်'),
    array('printer', 'request', 'img/printer2.png', 'http://192.168.5.7/printer_/login.php', 'Request Printer', 'ขอใช้งานและจัดการงานเครื่องพิมพ์', 'Request Printer', 'Printer and equipment support', 'Request Printer', 'Printer support တောင်းဆိုရန်'),
    array('ticket', 'support', 'img/ticket.png', 'http://192.168.5.6/logistic/index2.php', 'Ticket', 'เปิดตั๋วขอรับบริการหรือแจ้งปัญหา', 'Ticket', 'IT and service desk support', 'Ticket', 'IT service desk support'),
    array('it', 'support', 'img/it_service.png', 'http://192.168.5.7/ITService/login.php', 'IT Service', 'แจ้งงานและขอรับบริการจากฝ่าย IT', 'IT Service', 'Request IT support and assistance', 'IT Service', 'IT support တောင်းဆိုရန်'),
    array('repair', 'safety', 'img/repair.png', 'http://192.168.5.7/MT_repair/index.php', 'Machine Repair (MT)', 'แจ้งซ่อมเครื่องจักรถึงทีม MT', 'Machine Repair (MT)', 'Machine repair request to MT team', 'Machine Repair (MT)', 'စက်ပြင်တောင်းဆိုချက်'),
    array('she', 'safety', 'img/she_repair.png', 'http://192.168.5.7/SHE_Repair/index.php', 'SHE Repair', 'แจ้งซ่อมด้านความปลอดภัย รถ และพื้นที่', 'SHE Repair', 'Safety, vehicle and workplace repair', 'SHE Repair', 'Safety repair request'),
    array('omnex', 'admin', 'img/omnex-si.png', 'http://192.168.7.12/EwQIMS/Common/EwIMSNew/homepage/Index', 'Omnex (Admin)', 'ระบบจัดการและดูแลข้อมูล OMNEX', 'Omnex (Admin)', 'OMNEX administration and management', 'Omnex (Admin)', 'OMNEX စီမံခန့်ခွဲရေးစနစ်'),
    array('memo', 'admin', 'img/memo.png', 'http://192.168.5.7/Docusign/login.php', 'Memo Online', 'สร้าง ส่ง และติดตามเอกสารอนุมัติ', 'Memo Online', 'Create, send and track internal memos', 'Memo Online', 'အတွင်းပိုင်း memo များ')
  );

  $items = array();
  $order = 0;

  foreach ($rows as $row) {
    $items[] = array(
      'id' => $row[0],
      'cat' => $row[1],
      'icon' => $row[2],
      'url' => $row[3],
      'name_th' => $row[4],
      'desc_th' => $row[5],
      'name_en' => $row[6],
      'desc_en' => $row[7],
      'name_my' => $row[8],
      'desc_my' => $row[9],
      'sort' => $order,
      'created_at' => date('Y-m-d H:i:s')
    );
    $order++;
  }

  return $items;
}

function app_read_all()
{
  app_ensure_storage();
  $raw = file_get_contents(APP_DATA_FILE);
  $items = json_decode($raw, true);

  if (!is_array($items)) {
    return array();
  }

  usort($items, 'hero_sort_cmp');
  return $items;
}

function app_write_all($items)
{
  app_ensure_storage();
  return file_put_contents(APP_DATA_FILE, announcement_json_encode(array_values($items)), LOCK_EX) !== false;
}

function app_find($id)
{
  $id = trim((string) $id);

  if ($id === '') {
    return null;
  }

  foreach (app_read_all() as $item) {
    if (isset($item['id']) && $item['id'] === $id) {
      return $item;
    }
  }

  return null;
}

function app_name($app, $lang)
{
  return app_text_field($app, 'name', $lang);
}

function app_desc($app, $lang)
{
  return app_text_field($app, 'desc', $lang);
}

// อ่านข้อความตามภาษา ถ้าว่างให้ถอยไปใช้ภาษาไทย
function app_text_field($app, $prefix, $lang)
{
  if (!is_array($app)) {
    return '';
  }

  $key = $prefix . '_' . (in_array($lang, array('th', 'en', 'my')) ? $lang : 'th');

  if (isset($app[$key]) && trim((string) $app[$key]) !== '') {
    return $app[$key];
  }

  return isset($app[$prefix . '_th']) ? $app[$prefix . '_th'] : '';
}

function app_icon_url($app)
{
  if (!is_array($app) || !isset($app['icon']) || $app['icon'] === '') {
    return '';
  }

  $path = (string) $app['icon'];
  $parts = explode('/', $path);
  $encoded = array();

  foreach ($parts as $part) {
    $encoded[] = rawurlencode($part);
  }

  return implode('/', $encoded);
}

function app_make_id($nameEn, $nameTh)
{
  $base = trim((string) $nameEn) !== '' ? $nameEn : $nameTh;
  $base = strtolower(trim((string) $base));
  $base = preg_replace('/[^a-z0-9]+/', '-', $base);
  $base = trim($base, '-');

  if ($base === '') {
    $base = 'app';
  }

  $existing = app_read_all();
  $taken = array();

  foreach ($existing as $item) {
    if (isset($item['id'])) {
      $taken[] = $item['id'];
    }
  }

  $id = $base;
  $n = 2;

  while (in_array($id, $taken)) {
    $id = $base . '-' . $n;
    $n++;
  }

  return $id;
}

/* บันทึกไอคอนจาก data URL (ภาพที่แอดมินครอปกรอบ 1:1 ในเบราว์เซอร์)
   ทำฝั่ง client เพราะเซิร์ฟเวอร์ไม่มี GD extension — แนวเดียวกับภาพปก */
function app_store_icon_from_data($dataUrl, &$error)
{
  $error = '';
  $dataUrl = trim((string) $dataUrl);

  if ($dataUrl === '') {
    return null;
  }

  if (strpos($dataUrl, 'data:image/') !== 0) {
    $error = 'ข้อมูลไอคอนไม่ถูกต้อง';
    return false;
  }

  $comma = strpos($dataUrl, ',');
  if ($comma === false) {
    $error = 'ข้อมูลไอคอนไม่ถูกต้อง';
    return false;
  }

  $meta = substr($dataUrl, 0, $comma);
  $raw = substr($dataUrl, $comma + 1);

  $extension = 'png';
  if (strpos($meta, 'image/webp') !== false) {
    $extension = 'webp';
  } elseif (strpos($meta, 'image/jpeg') !== false) {
    $extension = 'jpg';
  }

  $binary = base64_decode($raw);

  if ($binary === false || strlen($binary) === 0) {
    $error = 'ถอดรหัสไอคอนไม่สำเร็จ';
    return false;
  }

  if (strlen($binary) > ANNOUNCEMENT_MAX_FILE_SIZE) {
    $error = 'ไอคอนต้องมีขนาดไม่เกิน 100MB';
    return false;
  }

  app_ensure_storage();
  $storedName = announcement_safe_id() . '.' . $extension;

  if (file_put_contents(APP_ICON_DIR . '/' . $storedName, $binary) === false) {
    $error = 'บันทึกไฟล์ไอคอนไม่สำเร็จ';
    return false;
  }

  return array('stored_name' => $storedName);
}

function app_store_icon($file, &$error)
{
  $error = '';

  /* ครอปมาแล้วให้ใช้ภาพที่ครอปก่อน */
  if (isset($_POST['app_icon_cropped']) && trim((string) $_POST['app_icon_cropped']) !== '') {
    return app_store_icon_from_data($_POST['app_icon_cropped'], $error);
  }

  if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    return null;
  }

  if ($file['error'] !== UPLOAD_ERR_OK) {
    $error = 'อัปโหลดไอคอนไม่สำเร็จ';
    return false;
  }

  if ($file['size'] > ANNOUNCEMENT_MAX_FILE_SIZE) {
    $error = 'ไอคอนต้องมีขนาดไม่เกิน 100MB';
    return false;
  }

  $originalName = basename($file['name']);
  $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

  if (!in_array($extension, array('jpg', 'jpeg', 'png', 'webp', 'svg'))) {
    $error = 'ไอคอนรองรับเฉพาะไฟล์ PNG, JPG, WEBP หรือ SVG';
    return false;
  }

  app_ensure_storage();
  $storedName = announcement_safe_id() . '.' . $extension;

  if (!move_uploaded_file($file['tmp_name'], APP_ICON_DIR . '/' . $storedName)) {
    $error = 'บันทึกไฟล์ไอคอนไม่สำเร็จ';
    return false;
  }

  return array('stored_name' => $storedName);
}

function app_create($data, $iconFile, &$errors)
{
  $nameTh = isset($data['name_th']) ? trim($data['name_th']) : '';
  $url = isset($data['url']) ? trim($data['url']) : '';

  if ($nameTh === '') {
    $errors[] = 'กรุณากรอกชื่อระบบงาน (ไทย)';
    return false;
  }

  if ($url === '') {
    $errors[] = 'กรุณากรอกลิงก์ของระบบงาน';
    return false;
  }

  $iconError = '';
  $icon = app_store_icon($iconFile, $iconError);

  if ($icon === false) {
    $errors[] = $iconError;
    return false;
  }

  $items = app_read_all();
  $maxSort = -1;

  foreach ($items as $item) {
    $sort = isset($item['sort']) ? (int) $item['sort'] : 0;
    if ($sort > $maxSort) {
      $maxSort = $sort;
    }
  }

  $nameEn = isset($data['name_en']) ? trim($data['name_en']) : '';

  $items[] = array(
    'id' => app_make_id($nameEn, $nameTh),
    'cat' => isset($data['cat']) ? trim($data['cat']) : '',
    'icon' => $icon === null ? '' : 'uploads/appicons/' . $icon['stored_name'],
    'url' => $url,
    'name_th' => $nameTh,
    'desc_th' => isset($data['desc_th']) ? trim($data['desc_th']) : '',
    'name_en' => $nameEn,
    'desc_en' => isset($data['desc_en']) ? trim($data['desc_en']) : '',
    'name_my' => isset($data['name_my']) ? trim($data['name_my']) : '',
    'desc_my' => isset($data['desc_my']) ? trim($data['desc_my']) : '',
    'sort' => $maxSort + 1,
    'created_at' => date('Y-m-d H:i:s')
  );

  if (!app_write_all($items)) {
    $errors[] = 'บันทึกระบบงานไม่สำเร็จ';
    return false;
  }

  return true;
}

function app_update($id, $data, $iconFile, &$errors)
{
  $items = app_read_all();
  $index = -1;
  $count = count($items);

  for ($i = 0; $i < $count; $i++) {
    if (isset($items[$i]['id']) && $items[$i]['id'] === $id) {
      $index = $i;
      break;
    }
  }

  if ($index < 0) {
    $errors[] = 'ไม่พบระบบงานที่ต้องการแก้ไข';
    return false;
  }

  $nameTh = isset($data['name_th']) ? trim($data['name_th']) : '';
  $url = isset($data['url']) ? trim($data['url']) : '';

  if ($nameTh === '') {
    $errors[] = 'กรุณากรอกชื่อระบบงาน (ไทย)';
    return false;
  }

  if ($url === '') {
    $errors[] = 'กรุณากรอกลิงก์ของระบบงาน';
    return false;
  }

  $iconError = '';
  $icon = app_store_icon($iconFile, $iconError);

  if ($icon === false) {
    $errors[] = $iconError;
    return false;
  }

  if ($icon !== null) {
    $old = isset($items[$index]['icon']) ? $items[$index]['icon'] : '';
    if (strpos($old, 'uploads/appicons/') === 0) {
      $realFile = dirname(__FILE__) . '/' . $old;
      if (file_exists($realFile)) {
        unlink($realFile);
      }
    }
    $items[$index]['icon'] = 'uploads/appicons/' . $icon['stored_name'];
  }

  $items[$index]['cat'] = isset($data['cat']) ? trim($data['cat']) : '';
  $items[$index]['url'] = $url;
  $items[$index]['name_th'] = $nameTh;
  $items[$index]['desc_th'] = isset($data['desc_th']) ? trim($data['desc_th']) : '';
  $items[$index]['name_en'] = isset($data['name_en']) ? trim($data['name_en']) : '';
  $items[$index]['desc_en'] = isset($data['desc_en']) ? trim($data['desc_en']) : '';
  $items[$index]['name_my'] = isset($data['name_my']) ? trim($data['name_my']) : '';
  $items[$index]['desc_my'] = isset($data['desc_my']) ? trim($data['desc_my']) : '';
  $items[$index]['updated_at'] = date('Y-m-d H:i:s');

  if (!app_write_all($items)) {
    $errors[] = 'อัปเดตระบบงานไม่สำเร็จ';
    return false;
  }

  return true;
}

function app_delete($id, &$errors)
{
  $id = trim((string) $id);
  $items = app_read_all();
  $next = array();
  $found = false;

  foreach ($items as $item) {
    if (isset($item['id']) && $item['id'] === $id) {
      $found = true;

      if (isset($item['icon']) && strpos($item['icon'], 'uploads/appicons/') === 0) {
        $realFile = dirname(__FILE__) . '/' . $item['icon'];
        if (file_exists($realFile)) {
          unlink($realFile);
        }
      }

      continue;
    }

    $next[] = $item;
  }

  if (!$found) {
    $errors[] = 'ไม่พบระบบงานที่ต้องการลบ';
    return false;
  }

  if (!app_write_all($next)) {
    $errors[] = 'ลบระบบงานไม่สำเร็จ';
    return false;
  }

  return true;
}
?>
