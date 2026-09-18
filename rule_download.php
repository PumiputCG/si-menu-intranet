<?php
/* ══════════════════════════════════════════════════════════════
   rule_download.php — ดาวน์โหลดไฟล์กฎระเบียบพร้อมชื่อไฟล์จริง
   (ไฟล์ในโฟลเดอร์เก็บด้วยชื่อสุ่ม เหมือนไฟล์แนบของประกาศ)
   ══════════════════════════════════════════════════════════════ */

require_once dirname(__FILE__) . '/rules_helpers.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$doc = rules_document_find($id);
$file = $doc ? rules_file($doc) : null;

function rule_download_not_found()
{
  if (function_exists('http_response_code')) {
    http_response_code(404);
  } else {
    header('HTTP/1.1 404 Not Found');
  }

  echo 'File not found';
  exit;
}

if (!$file || !isset($file['stored_name'])) {
  rule_download_not_found();
}

$storedName = basename($file['stored_name']);
$filePath = RULES_UPLOAD_DIR . '/' . $storedName;
$realUploadDir = realpath(RULES_UPLOAD_DIR);
$realFile = realpath($filePath);

/* กัน path traversal: ไฟล์ต้องอยู่ในโฟลเดอร์ uploads/rules เท่านั้น */
if (!$realFile || !$realUploadDir || strpos($realFile, $realUploadDir) !== 0 || !is_file($realFile)) {
  rule_download_not_found();
}

$originalName = isset($file['original_name']) ? $file['original_name'] : $storedName;
$originalName = str_replace(array('"', "\r", "\n"), '', $originalName);

/* ชื่อไฟล์เป็นภาษาไทย/พม่า — ต้องส่ง 2 แบบ
   filename=   ชื่อ ASCII สำรอง (เบราว์เซอร์เก่า)
   filename*=  ชื่อจริง UTF-8 ตาม RFC 5987 (เบราว์เซอร์ปัจจุบัน) */
$extension = strtolower(pathinfo($storedName, PATHINFO_EXTENSION));
$asciiName = preg_replace('/[^A-Za-z0-9._-]/', '-', $doc['id']);
if ($asciiName === '') {
  $asciiName = 'document';
}
if ($extension !== '') {
  $asciiName .= '.' . $extension;
}

$mimeType = 'application/pdf';
if (function_exists('finfo_open')) {
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  if ($finfo) {
    $detected = finfo_file($finfo, $realFile);
    if ($detected) {
      $mimeType = $detected;
    }
    finfo_close($finfo);
  }
}

/* ปิด output buffer ก่อนส่งไฟล์ — ไฟล์ใหญ่จะได้ไม่กินหน่วยความจำ */
while (ob_get_level() > 0) {
  ob_end_clean();
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($realFile));
header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($originalName));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($realFile);
exit;
?>
