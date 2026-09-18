<?php
/* ══════════════════════════════════════════════════════════════
   no_cache.php — บังคับให้เบราว์เซอร์ดึงหน้าใหม่เสมอ

   ใส่ไว้ "บรรทัดแรกสุด" ของหน้า ก่อนจะมี output ใด ๆ ออกไป:
     <?php require_once dirname(__FILE__) . '/no_cache.php'; ?>

   ทำไมต้องมี:
   Apache 2.2.8 + PHP 5.2.6 บนเซิร์ฟเวอร์บริษัทไม่ส่ง cache header
   ให้หน้า PHP เลยสักตัว (ไม่มีทั้ง Cache-Control, ETag, Last-Modified)
   เบราว์เซอร์จึงเดาเองว่าเก็บแคชได้ พออัปเดตโค้ดขึ้นเซิร์ฟเวอร์
   ผู้ใช้จะยังเห็นหน้าเก่าจนกว่าจะล้างแคชเอง
   ══════════════════════════════════════════════════════════════ */

if (!headers_sent()) {
  header('Content-Type: text/html; charset=UTF-8');
  header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}
?>
