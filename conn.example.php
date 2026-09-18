<?php
/**
 * Database connection template.
 *
 * TH: คัดลอกไฟล์นี้เป็น conn.php แล้วใส่ค่าจริงของเครื่องคุณ
 *     ไฟล์ conn.php ถูก gitignore ไว้ — ห้าม commit ขึ้น repo เด็ดขาด
 * EN: Copy this file to conn.php and fill in your own values.
 *     conn.php is gitignored — never commit it.
 */

$servername   = 'YOUR_DB_HOST';      // e.g. 192.168.x.x
$databasename = 'YOUR_DB_NAME';
$user         = 'YOUR_DB_USER';
$pass         = 'YOUR_DB_PASSWORD';

$connection_string = "DRIVER={SQL Server};SERVER=$servername;DATABASE=$databasename;AutoTranslate=no";
$conn = odbc_connect($connection_string, $user, $pass);

if (!$conn) {
  die('Database connection failed.');
}
