<?php
/* ══════════════════════════════════════════════════════════════
   rules_helpers.php — กฎระเบียบข้อบังคับ (Work Rules)
   เก็บเป็นไฟล์ JSON เหมือนระบบประกาศ · เขียนแบบ PHP 5.2-safe
   ══════════════════════════════════════════════════════════════ */

require_once dirname(__FILE__) . '/announcement_helpers.php';

define('RULES_DATA_FILE', ANNOUNCEMENT_DATA_DIR . '/rules.json');
define('RULES_UPLOAD_DIR', dirname(__FILE__) . '/uploads/rules');
define('RULES_UPLOAD_URL', 'uploads/rules');

/* ── ค่าตั้งต้น: ใช้ตอนไฟล์ JSON หาย เพื่อไม่ให้หน้าพัง ──────── */
function rules_defaults()
{
  return array(
    'intro' => array(
      'th' => 'ข้อบังคับเกี่ยวกับการทำงานของ SUPAVUT GROUP เลือกเอกสารตามภาษาที่ถนัดได้จากรายการด้านล่าง',
      'en' => 'The SUPAVUT GROUP work rules. Choose the document in your preferred language below.',
      'my' => 'SUPAVUT GROUP ၏ လုပ်ငန်းစည်းမျဉ်းများ။ အောက်တွင် သင်နှစ်သက်ရာ ဘာသာစကားဖြင့် ရွေးချယ်နိုင်ပါသည်။'
    ),
    'documents' => array()
  );
}

/* ── อ่านข้อมูลทั้งก้อน ──────────────────────────────────────── */
function rules_read()
{
  $defaults = rules_defaults();

  if (!file_exists(RULES_DATA_FILE)) {
    return $defaults;
  }

  $raw = file_get_contents(RULES_DATA_FILE);
  if ($raw === false || $raw === '') {
    return $defaults;
  }

  /* กัน BOM ที่อาจติดมาตอนแก้ไฟล์บนวินโดวส์ */
  if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
    $raw = substr($raw, 3);
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    return $defaults;
  }

  if (!isset($data['intro']) || !is_array($data['intro'])) {
    $data['intro'] = $defaults['intro'];
  }

  if (!isset($data['documents']) || !is_array($data['documents'])) {
    $data['documents'] = array();
  }

  return $data;
}

/* ── รายการเอกสารทั้งหมด ────────────────────────────────────── */
function rules_documents()
{
  $data = rules_read();
  return array_values($data['documents']);
}

/* ── หาเอกสารจาก id ─────────────────────────────────────────── */
function rules_document_find($id)
{
  $documents = rules_documents();

  foreach ($documents as $doc) {
    if (isset($doc['id']) && $doc['id'] === $id) {
      return $doc;
    }
  }

  return null;
}

/* ── ข้อความตามภาษา: th ใช้คีย์ปกติ · en/my ใช้คีย์ _en / _my ── */
function rules_localized($row, $key, $lang)
{
  if ($lang === 'en' || $lang === 'my') {
    $suffix = $key . '_' . $lang;
    if (isset($row[$suffix]) && trim((string) $row[$suffix]) !== '') {
      return $row[$suffix];
    }
  }

  return isset($row[$key]) ? $row[$key] : '';
}

/* ── คำอธิบายหัวหน้าเพจตามภาษา ──────────────────────────────── */
function rules_intro($lang)
{
  $data = rules_read();
  $intro = $data['intro'];

  if (isset($intro[$lang]) && trim((string) $intro[$lang]) !== '') {
    return $intro[$lang];
  }

  return isset($intro['th']) ? $intro['th'] : '';
}

/* ── ไฟล์แนบของเอกสาร ───────────────────────────────────────── */
function rules_file($doc)
{
  if (isset($doc['file']) && is_array($doc['file'])) {
    return $doc['file'];
  }

  return null;
}

/* ── ลิงก์เปิดอ่านในเบราว์เซอร์ (ไฟล์นิ่ง — โหลดทีละส่วนได้) ─── */
function rules_view_url($doc)
{
  $file = rules_file($doc);
  if (!$file || !isset($file['stored_name'])) {
    return '';
  }

  return RULES_UPLOAD_URL . '/' . rawurlencode(basename($file['stored_name']));
}

/* ── ลิงก์ดาวน์โหลด: ผ่าน PHP เพื่อคืนชื่อไฟล์จริง ──────────── */
function rules_download_url($doc)
{
  if (!isset($doc['id'])) {
    return '';
  }

  return 'rule_download.php?id=' . rawurlencode($doc['id']);
}

/* ── ขนาดไฟล์อ่านง่าย (ใช้ตัวช่วยชุดเดียวกับประกาศ) ─────────── */
function rules_file_size_label($doc)
{
  $file = rules_file($doc);
  if (!$file || !isset($file['size'])) {
    return '';
  }

  return announcement_format_file_size($file['size']);
}

/* ── วันที่ปรับปรุงล่าสุด ───────────────────────────────────── */
function rules_updated_label($doc)
{
  if (!isset($doc['updated_at'])) {
    return '';
  }

  $time = strtotime($doc['updated_at']);
  if (!$time) {
    return '';
  }

  return date('d/m/Y', $time);
}
?>
