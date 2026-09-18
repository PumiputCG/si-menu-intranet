<?php
require_once dirname(__FILE__) . '/announcement_register_helpers.php';

/* cell = ไฮเปอร์ลิงก์เดิมในไฟล์ Excel (ลิงก์ที่แอดมินกรอกเองเป็น URL ตรง ไม่ผ่านสคริปต์นี้)
   ต้องเจอแถวในทะเบียนก่อน และ announcement_register_read() ตัดแถวที่สั่งซ่อนออกแล้ว
   แถวที่ซ่อนไว้จึงหาไม่เจอ = ตอบ 404 เหมือนไม่มีเอกสารนั้น */
/* doc = ไฟล์ที่แอดมินแนบเองรายแถว (เก็บใต้ data/ เปิดตรงผ่าน HTTP ไม่ได้)
   ชื่อไฟล์เป็น md5 ที่ระบบสร้างเอง ตรวจรูปแบบใน announcement_register_doc_path() แล้ว */
$docName = isset($_GET['doc']) ? trim($_GET['doc']) : '';

if ($docName !== '') {
  $docPath = announcement_register_doc_path($docName);
  $docOwner = $docPath !== '' ? announcement_register_doc_owner($docName) : false;

  /* ไม่มีไฟล์ · ไม่มีแถวไหนอ้างถึงแล้ว · หรือแถวนั้นถูกสั่งซ่อน → 404 เหมือนโหมด cell */
  if ($docPath === '' || !$docOwner || $docOwner['hidden']) {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'ไม่พบเอกสารที่แนบไว้';
    exit;
  }

  $docOriginal = $docOwner['name'];
  $docAscii = preg_replace('/[^A-Za-z0-9._-]/', '_', $docOriginal);
  if ($docAscii === '') {
    $docAscii = 'announcement-document';
  }

  while (ob_get_level() > 0) {
    ob_end_clean();
  }

  header('Content-Type: ' . announcement_register_document_content_type($docPath));
  header('Content-Length: ' . filesize($docPath));
  header('Content-Disposition: inline; filename="' . $docAscii . '"; filename*=UTF-8\'\'' . rawurlencode($docOriginal));
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: private, no-store, max-age=0');
  header('Pragma: no-cache');
  readfile($docPath);
  exit;
}

$reference = isset($_GET['cell']) ? strtoupper(trim($_GET['cell'])) : '';

if (!preg_match('/^H[0-9]{1,5}$/', $reference)) {
  header('HTTP/1.1 400 Bad Request');
  header('Content-Type: text/plain; charset=UTF-8');
  echo 'ลิงก์เอกสารไม่ถูกต้อง';
  exit;
}

$register = announcement_register_read();
$row = $register['ok'] ? announcement_register_row_by_reference($register, $reference) : false;
$document = '';

if ($row) {
  /* ใช้พาธที่ผูกไว้ในไฟล์ Excel ของแถวนั้นก่อนเสมอ — ตรงกับตัวประกาศ 100%
     สำเนา served/H##.pdf เป็นแค่ตัวสำรองของเก่า ซึ่งผูกกับ "เลขแถว"
     ถ้าเอามาก่อน พอ HR แทรกแถวใหม่ เลขแถวจะเลื่อนแล้วหยิบเอกสารผิดใบ */
  $document = announcement_register_resolve_document($register['workbook'], $row['download_target']);

  if ($document === '') {
    $document = announcement_register_resolve_mirrored_document($register['workbook'], $reference);
  }
}

if ($document === '') {
  header('HTTP/1.1 404 Not Found');
  header('Content-Type: text/plain; charset=UTF-8');
  echo 'ไม่พบเอกสาร หรือเซิร์ฟเวอร์ยังไม่สามารถเข้าถึงโฟลเดอร์ประกาศของฝ่าย HR ได้';
  exit;
}

$originalTarget = $row && isset($row['download_target']) ? rawurldecode($row['download_target']) : '';
$filename = $originalTarget !== '' ? basename(str_replace('\\', '/', $originalTarget)) : basename($document);
$asciiFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
if ($asciiFilename === '') {
  $asciiFilename = 'announcement-document';
}

while (ob_get_level() > 0) {
  ob_end_clean();
}

header('Content-Type: ' . announcement_register_document_content_type($document));
header('Content-Length: ' . filesize($document));
header('Content-Disposition: inline; filename="' . $asciiFilename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
readfile($document);
exit;
