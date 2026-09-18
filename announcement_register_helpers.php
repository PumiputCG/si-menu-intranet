<?php
/*
 * อ่านทะเบียนประกาศบริษัทจาก Excel โดยตรง
 * โค้ดส่วนนี้คงรูปแบบ PHP 5.2-safe สำหรับเซิร์ฟเวอร์จริงของบริษัท
 */

/* ประกาศที่ผูกกับตารางทะเบียน — เป็นประกาศระบบ ห้ามลบจากคอนโซล
   (ลบแล้วตารางทะเบียนจะหายไปทั้งหน้า เพราะหน้า view ยึด id นี้เป็นตัวตัดสิน) */
define('ANNOUNCEMENT_REGISTER_POST_ID', '20260826123238584341');

/* โฟลเดอร์ทะเบียนบนเว็บเซิร์ฟเวอร์ — อยู่ใต้ data/ ซึ่ง .htaccess ปิดไม่ให้เปิดตรงผ่าน HTTP
   ไฟล์ที่แอดมินอัปโหลดจึงเปิดได้ทางเดียวคือผ่าน announcement_register_download.php */
define('ANNOUNCEMENT_REGISTER_DIR', dirname(__FILE__) . '/data/announcement_register');

/* ไฟล์ที่แอดมินอัปโหลดจากคอนโซล — ชื่อคงที่ และมีสิทธิ์เหนือทุกแหล่งใน list ข้างล่าง */
define('ANNOUNCEMENT_REGISTER_MANAGED_FILE', ANNOUNCEMENT_REGISTER_DIR . '/register-managed.xlsx');

/* เอกสาร PDF ที่แอดมินแนบเองรายแถว — ใช้กับแถวที่ไฟล์ Excel ยังไม่ได้ผูกลิงก์ไว้
   อยู่ใต้ data/ เหมือนกัน จึงเปิดตรงผ่าน HTTP ไม่ได้ ต้องผ่านสคริปต์ดาวน์โหลด */
define('ANNOUNCEMENT_REGISTER_DOC_DIR', ANNOUNCEMENT_REGISTER_DIR . '/docs');

/* ขนาดไฟล์แนบสูงสุดต่อแถว (30 MB) */
define('ANNOUNCEMENT_REGISTER_DOC_MAX', 31457280);

/* ค่าที่แอดมินตั้งรายแถว: ซ่อน/แสดง · ค่าที่แก้ทับ Excel · ลิงก์เอกสาร */
define('ANNOUNCEMENT_REGISTER_META_FILE', dirname(__FILE__) . '/data/announcement_register_meta.json');

/* ถอดรหัสไฟล์ Excel ที่ฝ่าย HR ตั้งรหัสผ่านเปิดไฟล์ไว้ */
require_once dirname(__FILE__) . '/announcement_xlsx_crypto.php';

function announcement_register_source_directories()
{
  $directories = array();
  $configuredDirectory = getenv('SIMENU_ANNOUNCEMENT_REGISTER_DIR');

  if ($configuredDirectory !== false && trim($configuredDirectory) !== '') {
    $directories[] = trim($configuredDirectory);
  }

  /* แหล่งหลักของฝ่าย HR: ใช้ UNC ก่อน เพราะ Apache มองไม่เห็น mapped drive ของผู้ใช้ */
  $directories[] = '\\\\192.168.5.1\\_DriveZ\\_HR\\5.หนังสือออก&ประกาศ\\3.Announce  Company -ประกาศ\\ประกาศปี2569';
  $directories[] = 'Z:\\_HR\\5.หนังสือออก&ประกาศ\\3.Announce  Company -ประกาศ\\ประกาศปี2569';

  /* สำเนาที่ซิงก์ไว้บนเว็บเซิร์ฟเวอร์ สำหรับ Apache ที่ไม่มีสิทธิ์อ่าน share โดยตรง */
  $directories[] = dirname(__FILE__) . '/data/announcement_register';

  /* สำเนาสำรองสำหรับเครื่องพัฒนา */
  $directories[] = 'C:\\Users\\Pumiput_IT\\Desktop\\รวมงาน\\Project\\SIMenu\\ทะเบียนประกาศ 2569';

  return $directories;
}

function announcement_register_file_newer($left, $right)
{
  $leftTime = @filemtime($left);
  $rightTime = @filemtime($right);
  $leftTime = $leftTime !== false ? $leftTime : 0;
  $rightTime = $rightTime !== false ? $rightTime : 0;

  if ($leftTime === $rightTime) {
    return strcmp($left, $right);
  }

  return $leftTime < $rightTime ? 1 : -1;
}

function announcement_register_workbooks_in($directory)
{
  $preferred = array();
  $fallback = array();

  if (!is_dir($directory) || !is_readable($directory)) {
    return array();
  }

  $handle = @opendir($directory);
  if (!$handle) {
    return array();
  }

  while (($name = readdir($handle)) !== false) {
    if ($name === '.' || $name === '..' || strpos($name, '~$') === 0) {
      continue;
    }

    $path = rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . $name;
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($extension !== 'xlsx' || !is_file($path) || !is_readable($path)) {
      continue;
    }

    if (stripos($name, 'ทะเบียนประกาศ') !== false && strpos($name, '2569') !== false) {
      $preferred[] = $path;
    } else {
      $fallback[] = $path;
    }
  }

  closedir($handle);

  usort($preferred, 'announcement_register_file_newer');
  usort($fallback, 'announcement_register_file_newer');

  return array_merge($preferred, $fallback);
}

/* เปิดจริงได้ไหม — is_readable() บนพาธ UNC คืน true ได้ทั้งที่ Apache เปิดไม่ออก
   ถ้าไม่เช็คตรงนี้ ระบบจะยึดไฟล์แรกที่ "เห็น" แล้วขึ้น error ทั้งที่มีสำเนาที่ใช้ได้อยู่ถัดไป */
function announcement_register_workbook_opens($path)
{
  if (!class_exists('ZipArchive')) {
    return false;
  }

  $zip = new ZipArchive();
  if ($zip->open($path) !== true) {
    return false;
  }

  $ok = $zip->getFromName('xl/worksheets/sheet1.xml') !== false;
  $zip->close();

  return $ok;
}

function announcement_register_find_workbook()
{
  $configuredFile = getenv('SIMENU_ANNOUNCEMENT_REGISTER_FILE');
  if ($configuredFile !== false && trim($configuredFile) !== '') {
    $configuredFile = trim($configuredFile);
    if (is_file($configuredFile) && is_readable($configuredFile)) {
      return $configuredFile;
    }
  }

  /* ไฟล์ที่แอดมินอัปโหลดมาก่อนเสมอ — ไม่งั้นสำเนาเก่าที่ก๊อปไว้ในโฟลเดอร์เดียวกัน
     อาจชนะเพราะเรียงตามเวลาแก้ไข แล้วอัปโหลดไปก็เหมือนไม่มีอะไรเกิดขึ้น */
  if (is_file(ANNOUNCEMENT_REGISTER_MANAGED_FILE) && is_readable(ANNOUNCEMENT_REGISTER_MANAGED_FILE)) {
    return ANNOUNCEMENT_REGISTER_MANAGED_FILE;
  }

  $firstSeen = '';

  foreach (announcement_register_source_directories() as $directory) {
    $workbooks = announcement_register_workbooks_in($directory);

    foreach ($workbooks as $workbook) {
      if ($firstSeen === '') {
        $firstSeen = $workbook;
      }

      if (announcement_register_workbook_opens($workbook)) {
        return $workbook;
      }
    }
  }

  /* เปิดไม่ได้สักไฟล์ — คืนตัวแรกไว้ให้ข้อความ error บอกได้ว่าไปเจอไฟล์ไหน */
  return $firstSeen;
}

function announcement_register_source_name($workbook)
{
  if ($workbook === '') {
    return '';
  }

  $normalized = strtolower(str_replace('/', '\\', $workbook));
  $isHrSource = strpos($normalized, 'z:\\') === 0
    || strpos($normalized, '\\\\192.168.5.1\\_drivez\\') === 0
    || strpos($normalized, '\\data\\announcement_register\\') !== false;

  return $isHrSource ? 'ไฟล์กลางฝ่าย HR' : 'สำเนาสำรองบนเครื่องพัฒนา';
}

/* ══════════════════════════════════════════════════════════
   รายการที่ไม่เผยแพร่บนเว็บ
   ══════════════════════════════════════════════════════════
   ประกาศแต่งตั้ง จป. แนบรายชื่อ/ตำแหน่งของผู้บริหารมาด้วย
   จึงตัดออกจากทะเบียนบนเว็บ แต่ยังคงอยู่ในไฟล์ Excel ของฝ่าย HR ตามเดิม

   เทียบด้วย "ประกาศเลขที่" (คอลัมน์ B) เป็นหลัก เพราะไม่เลื่อนตาม
   การแทรก/ลบแถวใน Excel เหมือนคอลัมน์ลำดับ · สำรองด้วยชื่อเรื่องเผื่อ
   ช่องเลขที่ว่าง · ตัวกรองนี้ทำงานทั้งตารางบนหน้าเว็บและปุ่มดาวน์โหลด
   เพราะ announcement_register_download.php หาแถวจากผลลัพธ์ชุดเดียวกัน */
function announcement_register_hidden_numbers()
{
  return array(
    '59/2569',
    '60/2569',
    '61/2569'
  );
}

/* ตัดช่องว่างซ้ำ/เว้นวรรคไม่ตัดคำ (\xC2\xA0) ออกก่อนเทียบ
   ไม่ใช้ modifier /u เพราะ PCRE บน PHP 5.2.6 ของเซิร์ฟเวอร์อาจไม่รองรับ */
function announcement_register_normalize_key($value)
{
  $value = str_replace(array("\xC2\xA0", "\r", "\n", "\t"), ' ', (string) $value);

  while (strpos($value, '  ') !== false) {
    $value = str_replace('  ', ' ', $value);
  }

  return strtolower(trim($value));
}

function announcement_register_in_list($value, $list)
{
  $needle = announcement_register_normalize_key($value);
  if ($needle === '') {
    return false;
  }

  foreach ($list as $item) {
    if (announcement_register_normalize_key($item) === $needle) {
      return true;
    }
  }

  return false;
}

/* ══════════════════════════════════════════════════════════
   ค่าที่แอดมินตั้งเอง (ซ่อน/แสดง + เอกสารแนบรายแถว)
   ══════════════════════════════════════════════════════════
   เก็บแยกจากไฟล์ Excel เพราะ HR ส่งไฟล์ใหม่มาทับเมื่อไรก็ได้
   จับคู่กับแถวด้วย "ประกาศเลขที่" ซึ่งไม่เลื่อนตามการแทรก/ลบแถว */
/* URL เต็มของหน้านี้ (เช่น http://192.168.5.7/SiMenu) — ใช้ประกอบลิงก์เอกสาร
   ให้คอลัมน์ลิงก์ในคอนโซลโชว์ลิงก์จริงที่กดเปิดได้ ไม่ใช่พาธ relative
   เรียกจาก CLI จะไม่มี $_SERVER จึงคืนค่าว่างและถอยไปใช้พาธ relative แทน */
function announcement_register_base_url()
{
  if (!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] === '') {
    return '';
  }

  $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower($_SERVER['HTTPS']) !== 'off';
  $scheme = $https ? 'https' : 'http';

  $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
  $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

  return $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir;
}

function announcement_register_row_key($number, $sequence, $subject)
{
  $number = announcement_register_normalize_key($number);
  if ($number !== '') {
    return $number;
  }

  $sequence = announcement_register_normalize_key($sequence);
  if ($sequence !== '') {
    return '#' . $sequence;
  }

  return 's:' . md5(announcement_register_normalize_key($subject));
}

/* ช่องที่แอดมินแก้ทับค่าจาก Excel ได้ (ต้องตรงกับคอลัมน์ในตารางคอนโซล) */
function announcement_register_editable_fields()
{
  return array('sequence', 'number', 'subject', 'recipient', 'owner', 'department', 'date');
}

function announcement_register_meta_blank_row()
{
  /* doc_file / doc_name = ไฟล์ PDF ที่แอดมินอัปโหลดให้แถวนี้เอง
     doc_url = ลิงก์ที่เคยพิมพ์เองไว้ (ของเดิม ยังใช้ได้แต่ไม่มีช่องกรอกแล้ว) */
  return array('hidden' => false, 'doc_url' => '', 'doc_file' => '', 'doc_name' => '', 'fields' => array());
}

/* สร้างครั้งแรก: ตั้งรายการที่เคย hard-code ไว้ให้ "ซ่อน" ตามเดิม
   ถ้าไฟล์ meta หายไป ระบบจะกลับมาซ่อนให้เองอีกครั้ง ไม่หลุดออกหน้าเว็บ */
function announcement_register_meta_seed()
{
  $rows = array();

  foreach (announcement_register_hidden_numbers() as $number) {
    $row = announcement_register_meta_blank_row();
    $row['hidden'] = true;
    $rows[announcement_register_normalize_key($number)] = $row;
  }

  return array('updated_at' => date('Y-m-d H:i:s'), 'rows' => $rows);
}

function announcement_register_meta_read()
{
  if (!is_file(ANNOUNCEMENT_REGISTER_META_FILE)) {
    $seed = announcement_register_meta_seed();
    announcement_register_meta_write($seed);
    return $seed;
  }

  $raw = @file_get_contents(ANNOUNCEMENT_REGISTER_META_FILE);
  $meta = $raw !== false ? json_decode($raw, true) : null;

  if (!is_array($meta) || !isset($meta['rows']) || !is_array($meta['rows'])) {
    return announcement_register_meta_seed();
  }

  return $meta;
}

function announcement_register_meta_write($meta)
{
  if (!is_dir(ANNOUNCEMENT_REGISTER_DIR)) {
    @mkdir(ANNOUNCEMENT_REGISTER_DIR, 0775, true);
  }

  $meta['updated_at'] = date('Y-m-d H:i:s');

  if (defined('JSON_PRETTY_PRINT') && defined('JSON_UNESCAPED_UNICODE')) {
    $encoded = json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  } else {
    $encoded = json_encode($meta);
  }

  if ($encoded === false) {
    return false;
  }

  return @file_put_contents(ANNOUNCEMENT_REGISTER_META_FILE, $encoded, LOCK_EX) !== false;
}

function announcement_register_meta_row($meta, $key)
{
  $row = announcement_register_meta_blank_row();

  if (isset($meta['rows'][$key]) && is_array($meta['rows'][$key])) {
    $row = array_merge($row, $meta['rows'][$key]);
  }

  if (!isset($row['fields']) || !is_array($row['fields'])) {
    $row['fields'] = array();
  }

  return $row;
}

function announcement_register_default_headers()
{
  return array(
    'A' => 'ลำดับ',
    'B' => 'ประกาศเลขที่',
    'C' => 'เรื่อง',
    'D' => 'หน่วยงานที่ส่งถึง',
    'E' => 'ผู้จัดทำ',
    'F' => 'แผนก/ฝ่าย',
    'G' => 'วันที่ประกาศ',
    'H' => 'Download'
  );
}

function announcement_register_error_result($message, $workbook)
{
  return array(
    'ok' => false,
    'error' => $message,
    'workbook' => $workbook,
    'source_name' => announcement_register_source_name($workbook),
    'updated_at' => 0,
    'title' => 'ทะเบียนประกาศบริษัท ประจำปี 2569',
    'headers' => announcement_register_default_headers(),
    'rows' => array(),
    'link_count' => 0,
    'hidden_count' => 0,
    'is_managed' => false
  );
}

function announcement_register_xml($xmlString)
{
  if ($xmlString === false || trim($xmlString) === '') {
    return false;
  }

  $previous = libxml_use_internal_errors(true);
  $xml = simplexml_load_string($xmlString);
  libxml_clear_errors();
  libxml_use_internal_errors($previous);
  return $xml;
}

function announcement_register_shared_strings($zip)
{
  $strings = array();
  $xml = announcement_register_xml($zip->getFromName('xl/sharedStrings.xml'));

  if (!$xml) {
    return $strings;
  }

  $namespaces = $xml->getNamespaces(true);
  $mainNamespace = isset($namespaces['']) ? $namespaces[''] : '';
  if ($mainNamespace !== '') {
    $xml->registerXPathNamespace('main', $mainNamespace);
  }

  $items = $mainNamespace !== '' ? $xml->xpath('//main:si') : $xml->xpath('//si');
  if (!is_array($items)) {
    return $strings;
  }

  foreach ($items as $item) {
    if ($mainNamespace !== '') {
      $item->registerXPathNamespace('main', $mainNamespace);
      $parts = $item->xpath('.//main:t');
    } else {
      $parts = $item->xpath('.//t');
    }

    $text = '';
    if (is_array($parts)) {
      foreach ($parts as $part) {
        $text .= (string) $part;
      }
    }
    $strings[] = $text;
  }

  return $strings;
}

function announcement_register_cell_column($reference)
{
  if (preg_match('/^([A-Z]+)[0-9]+$/i', $reference, $matches)) {
    return strtoupper($matches[1]);
  }

  return '';
}

function announcement_register_inline_text($cell, $mainNamespace)
{
  if ($mainNamespace !== '') {
    $cell->registerXPathNamespace('main', $mainNamespace);
    $parts = $cell->xpath('.//main:is//main:t');
  } else {
    $parts = $cell->xpath('.//is//t');
  }

  $text = '';
  if (is_array($parts)) {
    foreach ($parts as $part) {
      $text .= (string) $part;
    }
  }

  return $text;
}

function announcement_register_cell_value($cell, $sharedStrings, $mainNamespace)
{
  $attributes = $cell->attributes();
  $type = isset($attributes['t']) ? (string) $attributes['t'] : '';
  $main = $mainNamespace !== '' ? $cell->children($mainNamespace) : $cell;

  if ($type === 'inlineStr') {
    return announcement_register_inline_text($cell, $mainNamespace);
  }

  $raw = isset($main->v) ? (string) $main->v : '';
  if ($type === 's') {
    $index = (int) $raw;
    return isset($sharedStrings[$index]) ? $sharedStrings[$index] : '';
  }

  if ($type === 'b') {
    return $raw === '1' ? 'TRUE' : 'FALSE';
  }

  return $raw;
}

function announcement_register_relationship_targets($zip)
{
  $targets = array();
  $xml = announcement_register_xml($zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels'));

  if (!$xml) {
    return $targets;
  }

  $namespaces = $xml->getNamespaces(true);
  $mainNamespace = isset($namespaces['']) ? $namespaces[''] : '';
  $relationships = $mainNamespace !== '' ? $xml->children($mainNamespace) : $xml;

  foreach ($relationships->Relationship as $relationship) {
    $attributes = $relationship->attributes();
    $id = isset($attributes['Id']) ? (string) $attributes['Id'] : '';
    $target = isset($attributes['Target']) ? (string) $attributes['Target'] : '';
    $mode = isset($attributes['TargetMode']) ? (string) $attributes['TargetMode'] : '';

    if ($id !== '' && $target !== '' && ($mode === '' || strtolower($mode) === 'external')) {
      $targets[$id] = $target;
    }
  }

  return $targets;
}

function announcement_register_hyperlinks($sheetXml, $relationshipTargets, $mainNamespace)
{
  $links = array();
  $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

  if ($mainNamespace !== '') {
    $sheetXml->registerXPathNamespace('main', $mainNamespace);
    $nodes = $sheetXml->xpath('//main:hyperlink');
  } else {
    $nodes = $sheetXml->xpath('//hyperlink');
  }

  if (!is_array($nodes)) {
    return $links;
  }

  foreach ($nodes as $node) {
    $attributes = $node->attributes();
    $reference = isset($attributes['ref']) ? strtoupper((string) $attributes['ref']) : '';
    $relationshipAttributes = $node->attributes($relationshipNamespace);
    $relationshipId = isset($relationshipAttributes['id']) ? (string) $relationshipAttributes['id'] : '';

    if ($reference !== '' && $relationshipId !== '' && isset($relationshipTargets[$relationshipId])) {
      $links[$reference] = $relationshipTargets[$relationshipId];
    }
  }

  return $links;
}

function announcement_register_excel_date($value)
{
  $value = trim((string) $value);
  if ($value === '') {
    return '';
  }

  if (!is_numeric($value)) {
    return $value;
  }

  $days = (int) floor((float) $value);
  $unixTimestamp = ($days - 25569) * 86400;
  $day = gmdate('d', $unixTimestamp);
  $month = gmdate('m', $unixTimestamp);
  $year = (int) gmdate('Y', $unixTimestamp) + 543;

  return $day . '/' . $month . '/' . $year;
}

/* $includeHidden = true ใช้เฉพาะหน้าคอนโซลแอดมิน
   ค่าปริยายเป็น false เพื่อให้โค้ดที่เผลอเรียกแบบไม่ส่งพารามิเตอร์
   ไม่หลุดแถวที่สั่งซ่อนออกไปหน้าเว็บ */
function announcement_register_read($includeHidden = false)
{
  $workbook = announcement_register_find_workbook();
  if ($workbook === '') {
    return announcement_register_error_result('ไม่พบไฟล์ทะเบียนประกาศ กรุณาตรวจสอบการเชื่อมต่อไดรฟ์ Z: หรือกำหนด path ในระบบ', '');
  }

  if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
    return announcement_register_error_result('เซิร์ฟเวอร์ยังไม่เปิดส่วนขยาย ZIP/XML ที่จำเป็นสำหรับอ่าน Excel', $workbook);
  }

  $zip = new ZipArchive();
  $opened = $zip->open($workbook);

  if ($opened !== true) {
    /* ZIPARCHIVE::ER_NOZIP (19) = ไม่ใช่ไฟล์ zip — เกิดจาก .xls รูปแบบเก่า
       ที่ถูกตั้งชื่อเป็น .xlsx หรือไฟล์ที่ใส่รหัสผ่านไว้ ซึ่งพบบ่อยเวลา HR เซฟผิดรูปแบบ */
    if ($opened === 19) {
      return announcement_register_error_result(
        'ไฟล์นี้ไม่ใช่ .xlsx จริง (เป็นฟอร์แมต Excel 97-2003 หรือถูกใส่รหัสผ่านไว้) ' .
        'ให้เปิดใน Excel แล้ว Save As เป็น "Excel Workbook (*.xlsx)" ก่อน แล้วอัปโหลดใหม่',
        $workbook
      );
    }

    return announcement_register_error_result('ไม่สามารถเปิดไฟล์ทะเบียนประกาศได้ กรุณาตรวจสอบว่าไฟล์ไม่เสียหายหรือถูกล็อกอยู่', $workbook);
  }

  $sharedStrings = announcement_register_shared_strings($zip);
  $sheetXml = announcement_register_xml($zip->getFromName('xl/worksheets/sheet1.xml'));
  if (!$sheetXml) {
    $zip->close();
    return announcement_register_error_result('ไม่พบตารางข้อมูลในไฟล์ทะเบียนประกาศ', $workbook);
  }

  $namespaces = $sheetXml->getNamespaces(true);
  $mainNamespace = isset($namespaces['']) ? $namespaces[''] : '';
  $main = $mainNamespace !== '' ? $sheetXml->children($mainNamespace) : $sheetXml;
  $relationshipTargets = announcement_register_relationship_targets($zip);
  $hyperlinks = announcement_register_hyperlinks($sheetXml, $relationshipTargets, $mainNamespace);

  $title = 'ทะเบียนประกาศบริษัท ประจำปี 2569';
  $headers = announcement_register_default_headers();
  $rows = array();
  $linkCount = 0;
  $hiddenCount = 0;
  $meta = announcement_register_meta_read();
  $baseUrl = announcement_register_base_url();

  foreach ($main->sheetData->row as $rowXml) {
    $rowAttributes = $rowXml->attributes();
    $rowNumber = isset($rowAttributes['r']) ? (int) $rowAttributes['r'] : 0;
    $values = array();

    foreach ($rowXml->c as $cell) {
      $cellAttributes = $cell->attributes();
      $reference = isset($cellAttributes['r']) ? strtoupper((string) $cellAttributes['r']) : '';
      $column = announcement_register_cell_column($reference);
      if ($column !== '') {
        $values[$column] = announcement_register_cell_value($cell, $sharedStrings, $mainNamespace);
      }
    }

    if ($rowNumber === 1 && isset($values['A']) && trim($values['A']) !== '') {
      $title = trim($values['A']);
      continue;
    }

    if ($rowNumber === 2) {
      foreach ($headers as $column => $defaultLabel) {
        if (isset($values[$column]) && trim($values[$column]) !== '') {
          $headers[$column] = trim($values[$column]);
        }
      }
      continue;
    }

    /* แถวที่มีเพียงเลขลำดับเป็นแถวเตรียมไว้ใน Excel จึงไม่แสดงบนเว็บ */
    $subject = isset($values['C']) ? trim($values['C']) : '';
    if ($rowNumber < 3 || $subject === '') {
      continue;
    }

    $number = isset($values['B']) ? trim($values['B']) : '';
    $sequence = isset($values['A']) ? trim($values['A']) : '';

    /* คีย์ยึดค่าจาก Excel เสมอ — ถึงแอดมินจะแก้เลขที่ในคอนโซล
       คีย์ก็ไม่เปลี่ยน ค่าที่แก้ไว้จึงไม่หลุดจากแถวเดิม */
    $key = announcement_register_row_key($number, $sequence, $subject);
    $settings = announcement_register_meta_row($meta, $key);

    /* ค่าที่แอดมินแก้ทับค่าจาก Excel */
    $excel = array(
      'sequence' => $sequence,
      'number' => $number,
      'subject' => $subject,
      'recipient' => isset($values['D']) ? trim($values['D']) : '',
      'owner' => isset($values['E']) ? trim($values['E']) : '',
      'department' => isset($values['F']) ? trim($values['F']) : '',
      'date' => isset($values['G']) ? announcement_register_excel_date($values['G']) : ''
    );

    $shown = $excel;
    $edited = false;

    foreach (announcement_register_editable_fields() as $field) {
      if (isset($settings['fields'][$field])) {
        $shown[$field] = (string) $settings['fields'][$field];
        $edited = true;
      }
    }

    /* แถวที่แอดมินสั่งซ่อน — ไม่ส่งออกไปหน้าเว็บและไม่นับเป็นลิงก์ */
    $hidden = !empty($settings['hidden']);
    if ($hidden) {
      $hiddenCount++;
      if (!$includeHidden) {
        continue;
      }
    }

    $linkReference = 'H' . $rowNumber;
    $linkTarget = isset($hyperlinks[$linkReference]) ? $hyperlinks[$linkReference] : '';

    /* ปุ่ม "เปิดลิงก์" ใช้ลิงก์ที่แอดมินกรอกก่อน
       ถ้าเว้นว่างไว้ค่อยถอยไปใช้ไฮเปอร์ลิงก์เดิมในไฟล์ Excel

       link_auto = ลิงก์เต็มของเอกสารที่มากับไฟล์ Excel — เอาไว้เติมลงช่องกรอก
       ในคอนโซลให้เห็นและกดเปิดได้เลย · ตอนบันทึก ถ้าค่าตรงกับ link_auto เป๊ะ
       จะไม่เก็บทับ เพื่อให้ยังตามไฟล์ Excel ต่อไปเวลาแถวขยับ */
    $docUrl = trim((string) $settings['doc_url']);
    $linkUrl = '';
    $linkKind = '';
    $linkAuto = '';
    $linkAutoLabel = '';

    if ($linkTarget !== '') {
      $relative = 'announcement_register_download.php?cell=' . rawurlencode($linkReference);
      $linkAuto = $baseUrl !== '' ? $baseUrl . '/' . $relative : $relative;

      /* พาธเต็มที่ฝังอยู่ในไฮเปอร์ลิงก์ของ Excel (รวมโฟลเดอร์ย่อย)
         เอาไว้โชว์ในคอนโซลให้ตรงกับที่เห็นตอนเปิดในโปรแกรม Excel */
      $linkAutoLabel = str_replace('/', '\\', rawurldecode($linkTarget));
    }

    /* PDF ที่แอดมินแนบเองรายแถว — ต้องมีไฟล์อยู่จริงถึงจะนับเป็นลิงก์ */
    $docFile = trim((string) $settings['doc_file']);
    $docName = trim((string) $settings['doc_name']);
    $docUpload = '';

    if ($docFile !== '' && is_file(ANNOUNCEMENT_REGISTER_DOC_DIR . '/' . $docFile)) {
      $relativeDoc = 'announcement_register_download.php?doc=' . rawurlencode($docFile);
      $docUpload = $baseUrl !== '' ? $baseUrl . '/' . $relativeDoc : $relativeDoc;
      if ($docName === '') {
        $docName = $docFile;
      }
    } else {
      $docFile = '';
      $docName = '';
    }

    /* ลำดับความสำคัญ: ไฟล์ที่แอดมินแนบเอง > ลิงก์ที่เคยพิมพ์ไว้ > ไฮเปอร์ลิงก์ใน Excel */
    if ($docUpload !== '') {
      $linkUrl = $docUpload;
      $linkKind = 'upload';
    } elseif ($docUrl !== '') {
      $linkUrl = $docUrl;
      $linkKind = 'url';
    } elseif ($linkAuto !== '') {
      $linkUrl = $linkAuto;
      $linkKind = 'excel';
    }

    /* ข้อความที่โชว์ในช่องลิงก์ของคอนโซล: ชื่อไฟล์ที่แนบ · ลิงก์ที่กรอก · ชื่อไฟล์จาก Excel */
    if ($docUpload !== '') {
      $linkLabel = $docName;
    } elseif ($docUrl !== '') {
      $linkLabel = $docUrl;
    } else {
      $linkLabel = $linkAutoLabel;
    }

    if ($linkUrl !== '' && !$hidden) {
      $linkCount++;
    }

    $rows[] = array(
      'excel_row' => $rowNumber,
      'key' => $key,
      'sequence' => $shown['sequence'],
      'number' => $shown['number'],
      'subject' => $shown['subject'],
      'recipient' => $shown['recipient'],
      'owner' => $shown['owner'],
      'department' => $shown['department'],
      'date' => $shown['date'],
      'excel_values' => $excel,
      'edited' => $edited,
      'download_label' => isset($values['H']) ? trim($values['H']) : '',
      'download_ref' => $linkTarget !== '' ? $linkReference : '',
      'download_target' => $linkTarget,
      'hidden' => $hidden,
      'doc_url' => $docUrl,
      'doc_file' => $docFile,
      'doc_name' => $docName,
      'link_auto' => $linkAuto,
      'link_auto_label' => $linkAutoLabel,
      'link_label' => $linkLabel,
      'link_url' => $linkUrl,
      'link_kind' => $linkKind
    );
  }

  $zip->close();
  $modified = @filemtime($workbook);

  return array(
    'ok' => true,
    'error' => '',
    'workbook' => $workbook,
    'source_name' => announcement_register_source_name($workbook),
    'updated_at' => $modified !== false ? $modified : 0,
    'title' => $title,
    'headers' => $headers,
    'rows' => $rows,
    'link_count' => $linkCount,
    'hidden_count' => $hiddenCount,
    'is_managed' => $workbook === ANNOUNCEMENT_REGISTER_MANAGED_FILE
  );
}

/* ══════════════════════════════════════════════════════════
   ฝั่งเขียน — เรียกจากคอนโซลแอดมินเท่านั้น
   ══════════════════════════════════════════════════════════ */

/* ── รหัสผ่านของไฟล์ Excel ที่ฝ่าย HR ตั้งไว้ ──
   เก็บไว้ให้ครั้งต่อไปอัปโหลดได้เลยไม่ต้องพิมพ์ซ้ำ · HR เปลี่ยนรหัสเมื่อไรก็พิมพ์ใหม่ทับได้
   เก็บใน meta ซึ่งอยู่ใต้ data/ ที่ .htaccess ปิดไม่ให้เปิดตรงผ่าน HTTP */
function announcement_register_password_get()
{
  $meta = announcement_register_meta_read();

  return isset($meta['xlsx_password']) ? (string) $meta['xlsx_password'] : '';
}

function announcement_register_password_set($password)
{
  $meta = announcement_register_meta_read();
  $meta['xlsx_password'] = (string) $password;

  return announcement_register_meta_write($meta);
}

/* ── ที่พักไฟล์ที่อัปโหลดมาแล้วแต่ยังไขรหัสไม่ได้ ──
   ต้องพักไว้ เพราะตอนถามรหัสเป็นคนละ request แอดมินจะได้ไม่ต้องเลือกไฟล์ใหม่ */
function announcement_register_pending_dir()
{
  return ANNOUNCEMENT_REGISTER_DIR . '/pending';
}

/* เก็บกวาดไฟล์พักที่ค้างเกิน 1 ชั่วโมง */
function announcement_register_pending_sweep()
{
  $dir = announcement_register_pending_dir();
  if (!is_dir($dir)) {
    return;
  }

  $handle = @opendir($dir);
  if (!$handle) {
    return;
  }

  $cutoff = time() - 3600;
  while (($name = readdir($handle)) !== false) {
    if ($name === '.' || $name === '..') {
      continue;
    }

    $path = $dir . '/' . $name;
    if (is_file($path) && @filemtime($path) < $cutoff) {
      @unlink($path);
    }
  }
  closedir($handle);
}

function announcement_register_pending_path($token)
{
  if (!preg_match('/^[a-f0-9]{32}$/', (string) $token)) {
    return '';
  }

  $path = announcement_register_pending_dir() . '/' . $token . '.bin';

  return is_file($path) ? $path : '';
}

function announcement_register_pending_stash($binary, &$error)
{
  $error = '';
  $dir = announcement_register_pending_dir();

  if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
    $error = 'สร้างโฟลเดอร์พักไฟล์ไม่สำเร็จ';
    return false;
  }

  announcement_register_pending_sweep();

  $token = md5(uniqid('reg', true) . mt_rand());

  if (@file_put_contents($dir . '/' . $token . '.bin', $binary) === false) {
    $error = 'พักไฟล์ไว้ไม่สำเร็จ กรุณาตรวจสอบสิทธิ์เขียนโฟลเดอร์ data';
    return false;
  }

  return $token;
}

function announcement_register_pending_drop($token)
{
  $path = announcement_register_pending_path($token);

  if ($path !== '') {
    @unlink($path);
  }
}

/*
 * รับเนื้อไฟล์ .xlsx (ถอดรหัสให้ถ้าจำเป็น) แล้วเขียนทับไฟล์ที่ระบบใช้
 *
 * $reason บอกชนิดของปัญหาให้หน้าเว็บตัดสินใจต่อ:
 *   'password' = ไฟล์ล็อกอยู่ ต้องขอรหัสจากแอดมิน
 *   'format'   = ไฟล์ผิดรูปแบบ ขอรหัสไปก็ไม่ช่วย
 */
function announcement_register_accept_workbook($binary, $password, &$error, &$reason)
{
  $error = '';
  $reason = '';

  if (!class_exists('ZipArchive')) {
    $error = 'เซิร์ฟเวอร์ยังไม่เปิดส่วนขยาย ZIP ที่จำเป็นสำหรับอ่าน Excel';
    $reason = 'format';
    return false;
  }

  /* ไฟล์ที่ตั้งรหัสผ่านเปิดไฟล์เป็นตู้ OLE2 ไม่ใช่ zip — ต้องถอดรหัสก่อน */
  if (xlsx_is_encrypted($binary)) {
    $password = trim((string) $password);

    if ($password === '') {
      $error = 'ไฟล์นี้ตั้งรหัสผ่านไว้ กรุณากรอกรหัสผ่านของไฟล์';
      $reason = 'password';
      return false;
    }

    /* วนแฮช 100,000 รอบ + ถอด AES เองทั้งหมด อาจกินเวลาหลายวินาทีบนเซิร์ฟเวอร์เก่า */
    @set_time_limit(300);

    $decError = '';
    $decReason = '';
    $plain = xlsx_decrypt($binary, $password, $decError, $decReason);

    if ($plain === false) {
      $error = $decError;
      $reason = $decReason === 'password' ? 'password' : 'format';
      return false;
    }

    $binary = $plain;
  }

  if (substr($binary, 0, 2) !== 'PK') {
    $error = 'ไฟล์เสียหายหรือไม่ใช่ไฟล์ Excel (ถ้าเป็น .xls ให้ Save As เป็น .xlsx ก่อน)';
    $reason = 'format';
    return false;
  }

  if (!is_dir(ANNOUNCEMENT_REGISTER_DIR) && !@mkdir(ANNOUNCEMENT_REGISTER_DIR, 0775, true)) {
    $error = 'สร้างโฟลเดอร์เก็บไฟล์ทะเบียนไม่สำเร็จ';
    $reason = 'format';
    return false;
  }

  /* เขียนลงไฟล์ชั่วคราวก่อน เพื่อให้ ZipArchive ตรวจได้ว่าเป็นทะเบียนจริง
     ยังไม่ทับไฟล์ที่ใช้อยู่จนกว่าจะผ่านการตรวจ */
  $temp = ANNOUNCEMENT_REGISTER_DIR . '/.verify-' . md5(uniqid('v', true)) . '.xlsx';

  if (@file_put_contents($temp, $binary) === false) {
    $error = 'บันทึกไฟล์ทะเบียนไม่สำเร็จ กรุณาตรวจสอบสิทธิ์เขียนโฟลเดอร์ data';
    $reason = 'format';
    return false;
  }

  $probe = new ZipArchive();
  if ($probe->open($temp) !== true) {
    @unlink($temp);
    $error = 'ไฟล์เสียหายหรือไม่ใช่ไฟล์ Excel';
    $reason = 'format';
    return false;
  }

  $hasSheet = $probe->getFromName('xl/worksheets/sheet1.xml') !== false;
  $probe->close();

  if (!$hasSheet) {
    @unlink($temp);
    $error = 'ไม่พบตารางข้อมูลในไฟล์นี้ กรุณาตรวจสอบว่าเป็นไฟล์ทะเบียนประกาศ';
    $reason = 'format';
    return false;
  }

  /* rename ทับไม่ได้บนวินโดวส์ ต้องลบตัวเดิมก่อน */
  if (is_file(ANNOUNCEMENT_REGISTER_MANAGED_FILE)) {
    @unlink(ANNOUNCEMENT_REGISTER_MANAGED_FILE);
  }

  if (!@rename($temp, ANNOUNCEMENT_REGISTER_MANAGED_FILE)) {
    @unlink($temp);
    $error = 'บันทึกไฟล์ทะเบียนไม่สำเร็จ กรุณาตรวจสอบสิทธิ์เขียนโฟลเดอร์ data';
    $reason = 'format';
    return false;
  }

  return true;
}

/* รับไฟล์ .xlsx จากแอดมิน แล้วเขียนทับไฟล์ที่ระบบใช้
   ตรวจว่าเป็น xlsx จริงด้วยการเปิด zip หา sheet1 ไม่ใช่ดูแค่นามสกุล
   ไฟล์ที่ตั้งรหัสผ่านไว้จะถูกถอดรหัสให้อัตโนมัติถ้ารหัสถูก */
function announcement_register_store_workbook($file, &$error, $password = '', &$reason = null)
{
  $error = '';
  $reason = '';

  if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    $error = 'กรุณาเลือกไฟล์ Excel ก่อน';
    $reason = 'format';
    return false;
  }

  if ($file['error'] !== UPLOAD_ERR_OK) {
    $error = 'อัปโหลดไฟล์ไม่สำเร็จ (อาจใหญ่เกินค่าที่เซิร์ฟเวอร์ตั้งไว้)';
    $reason = 'format';
    return false;
  }

  $extension = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
  if ($extension !== 'xlsx') {
    $error = 'รองรับเฉพาะไฟล์ .xlsx เท่านั้น (ถ้าเป็น .xls ให้ Save As เป็น .xlsx ก่อน)';
    $reason = 'format';
    return false;
  }

  $binary = @file_get_contents($file['tmp_name']);

  if ($binary === false || $binary === '') {
    $error = 'อ่านไฟล์ที่อัปโหลดไม่ได้';
    $reason = 'format';
    return false;
  }

  return announcement_register_accept_workbook($binary, $password, $error, $reason);
}

/* บันทึกทั้งตารางในครั้งเดียว — ซ่อน/แสดง + ค่าที่แก้ + ลิงก์
   $register ต้องมาจาก announcement_register_read(true) เพื่อเทียบกับค่าจาก Excel
   ช่องไหนที่แก้แล้วตรงกับ Excel เป๊ะ จะไม่เก็บทับไว้ ค่าใหม่จาก HR จึงไหลผ่านมาได้ */
function announcement_register_save_table($register, $post, &$error)
{
  $error = '';

  $keys = isset($post['row_key']) && is_array($post['row_key']) ? $post['row_key'] : array();
  if (count($keys) === 0) {
    $error = 'ไม่พบข้อมูลที่จะบันทึก';
    return false;
  }

  $visible = isset($post['visible']) && is_array($post['visible']) ? $post['visible'] : array();
  $fieldInput = isset($post['field']) && is_array($post['field']) ? $post['field'] : array();

  $visibleLookup = array();
  foreach ($visible as $vKey) {
    $visibleLookup[(string) $vKey] = true;
  }

  /* ค่าจาก Excel ของแต่ละแถว ไว้เทียบว่าช่องไหน "แก้จริง" */
  $excelLookup = array();
  foreach ($register['rows'] as $registerRow) {
    $excelLookup[$registerRow['key']] = $registerRow['excel_values'];
  }

  $meta = announcement_register_meta_read();
  $editable = announcement_register_editable_fields();

  foreach ($keys as $key) {
    $key = (string) $key;
    if ($key === '') {
      continue;
    }

    $row = announcement_register_meta_row($meta, $key);
    $row['hidden'] = !isset($visibleLookup[$key]);

    /* ลิงก์เอกสารแก้ที่ไฟล์ Excel ของฝ่าย HR ที่เดียว — ช่องบนเว็บอ่านอย่างเดียว
       จึงไม่รับค่ากลับมาที่นี่ (doc_url ที่เคยตั้งไว้จากของเดิมยังใช้ได้ตามปกติ) */

    $fields = array();
    foreach ($editable as $field) {
      if (!isset($fieldInput[$key][$field])) {
        /* ไม่ได้ส่งช่องนี้มา — คงค่าที่เคยแก้ไว้ */
        if (isset($row['fields'][$field])) {
          $fields[$field] = $row['fields'][$field];
        }
        continue;
      }

      $typed = trim((string) $fieldInput[$key][$field]);
      $fromExcel = isset($excelLookup[$key][$field]) ? (string) $excelLookup[$key][$field] : '';

      if ($typed !== $fromExcel) {
        $fields[$field] = $typed;
      }
    }

    $row['fields'] = $fields;
    $meta['rows'][$key] = $row;
  }

  if (!announcement_register_meta_write($meta)) {
    $error = 'บันทึกไม่สำเร็จ กรุณาตรวจสอบสิทธิ์เขียนโฟลเดอร์ data';
    return false;
  }

  return true;
}

/* ══ เอกสาร PDF ที่แอดมินแนบเองรายแถว ══
   ใช้กับแถวที่ไฟล์ Excel ของ HR ยังไม่ได้ผูกลิงก์ไว้ หรืออยากทับลิงก์เดิม */

/* ชื่อไฟล์ที่เก็บจริงบนดิสก์เป็น ASCII ล้วน — ชื่อไทยเดิมเก็บไว้ใน meta
   (PHP 5.2 บนวินโดวส์ไทยคุยกับดิสก์ผ่าน CP874 ชื่อไทยจึงเสี่ยงหาไม่เจอ) */
function announcement_register_doc_stored_name($key, $extension)
{
  return md5($key . '|' . microtime(true) . '|' . mt_rand()) . '.' . $extension;
}

function announcement_register_doc_path($storedName)
{
  $storedName = (string) $storedName;

  /* รับเฉพาะชื่อที่ระบบสร้างเอง กัน ../ และชื่อแปลกปลอมทุกแบบ */
  if (!preg_match('/^[a-f0-9]{32}\.(pdf|jpg|jpeg|png)$/', $storedName)) {
    return '';
  }

  $path = ANNOUNCEMENT_REGISTER_DOC_DIR . '/' . $storedName;

  return is_file($path) ? $path : '';
}

/* หาแถวที่เป็นเจ้าของไฟล์ที่แนบไว้ — คืน false ถ้าไม่มีแถวไหนอ้างถึงไฟล์นี้แล้ว
   (ไฟล์ที่ไม่มีเจ้าของไม่ควรเปิดได้ และแถวที่สั่งซ่อนก็ไม่ควรเปิดได้เหมือนโหมด cell) */
function announcement_register_doc_owner($storedName)
{
  $meta = announcement_register_meta_read();

  foreach ($meta['rows'] as $row) {
    if (isset($row['doc_file']) && $row['doc_file'] === $storedName) {
      $name = isset($row['doc_name']) ? trim((string) $row['doc_name']) : '';

      return array(
        'name' => $name !== '' ? $name : $storedName,
        'hidden' => !empty($row['hidden'])
      );
    }
  }

  return false;
}

function announcement_register_store_document($key, $file, &$error)
{
  $error = '';
  $key = trim((string) $key);

  if ($key === '') {
    $error = 'ไม่พบแถวที่จะแนบไฟล์';
    return false;
  }

  if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    $error = 'กรุณาเลือกไฟล์ก่อน';
    return false;
  }

  if ($file['error'] !== UPLOAD_ERR_OK) {
    $error = 'อัปโหลดไฟล์ไม่สำเร็จ (อาจใหญ่เกินค่าที่เซิร์ฟเวอร์ตั้งไว้)';
    return false;
  }

  if ($file['size'] > ANNOUNCEMENT_REGISTER_DOC_MAX) {
    $error = 'ไฟล์ต้องมีขนาดไม่เกิน 30MB';
    return false;
  }

  $originalName = basename((string) $file['name']);
  $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

  if (!in_array($extension, array('pdf', 'jpg', 'jpeg', 'png'))) {
    $error = 'รองรับเฉพาะไฟล์ PDF, JPG หรือ PNG';
    return false;
  }

  /* ตรวจเนื้อไฟล์จริง ไม่เชื่อแค่นามสกุล — PDF ต้องขึ้นต้นด้วย %PDF */
  if ($extension === 'pdf') {
    $handle = @fopen($file['tmp_name'], 'rb');
    $head = $handle ? fread($handle, 5) : '';
    if ($handle) {
      fclose($handle);
    }

    if (strpos($head, '%PDF') !== 0) {
      $error = 'ไฟล์นี้ไม่ใช่ PDF จริง กรุณาตรวจสอบไฟล์อีกครั้ง';
      return false;
    }
  }

  if (!is_dir(ANNOUNCEMENT_REGISTER_DOC_DIR) && !@mkdir(ANNOUNCEMENT_REGISTER_DOC_DIR, 0775, true)) {
    $error = 'สร้างโฟลเดอร์เก็บเอกสารไม่สำเร็จ';
    return false;
  }

  $storedName = announcement_register_doc_stored_name($key, $extension);

  if (!move_uploaded_file($file['tmp_name'], ANNOUNCEMENT_REGISTER_DOC_DIR . '/' . $storedName)) {
    $error = 'บันทึกไฟล์ไม่สำเร็จ กรุณาตรวจสอบสิทธิ์เขียนโฟลเดอร์ data';
    return false;
  }

  $meta = announcement_register_meta_read();
  $row = announcement_register_meta_row($meta, $key);

  /* แนบไฟล์ใหม่ทับของเดิม — ลบไฟล์เก่าทิ้งไม่ให้ค้างในโฟลเดอร์ */
  $old = isset($row['doc_file']) ? announcement_register_doc_path($row['doc_file']) : '';
  if ($old !== '') {
    @unlink($old);
  }

  $row['doc_file'] = $storedName;
  $row['doc_name'] = $originalName;
  $meta['rows'][$key] = $row;

  if (!announcement_register_meta_write($meta)) {
    @unlink(ANNOUNCEMENT_REGISTER_DOC_DIR . '/' . $storedName);
    $error = 'บันทึกข้อมูลไม่สำเร็จ กรุณาตรวจสอบสิทธิ์เขียนโฟลเดอร์ data';
    return false;
  }

  return true;
}

function announcement_register_delete_document($key, &$error)
{
  $error = '';
  $key = trim((string) $key);

  if ($key === '') {
    $error = 'ไม่พบแถวที่จะลบไฟล์';
    return false;
  }

  $meta = announcement_register_meta_read();
  $row = announcement_register_meta_row($meta, $key);

  $path = isset($row['doc_file']) ? announcement_register_doc_path($row['doc_file']) : '';
  if ($path !== '') {
    @unlink($path);
  }

  $row['doc_file'] = '';
  $row['doc_name'] = '';
  $meta['rows'][$key] = $row;

  if (!announcement_register_meta_write($meta)) {
    $error = 'ลบไฟล์ไม่สำเร็จ กรุณาตรวจสอบสิทธิ์เขียนโฟลเดอร์ data';
    return false;
  }

  return true;
}

/* คืนค่าที่แก้ไว้ทั้งแถวให้กลับไปเป็นค่าจาก Excel */
function announcement_register_reset_row($key)
{
  $key = trim((string) $key);
  if ($key === '') {
    return false;
  }

  $meta = announcement_register_meta_read();
  $row = announcement_register_meta_row($meta, $key);
  $row['fields'] = array();

  $meta['rows'][$key] = $row;
  return announcement_register_meta_write($meta);
}

function announcement_register_row_by_reference($register, $reference)
{
  if (!isset($register['rows']) || !is_array($register['rows'])) {
    return false;
  }

  foreach ($register['rows'] as $row) {
    if (isset($row['download_ref']) && $row['download_ref'] === $reference) {
      return $row;
    }
  }

  return false;
}

/* ══════════════════════════════════════════════════════════
   ชื่อไฟล์ภาษาไทยกับ PHP 5.2 บนวินโดวส์
   ══════════════════════════════════════════════════════════
   ชื่อไฟล์ที่อ่านออกมาจากไฟล์ Excel เป็น UTF-8 แต่ PHP 5.2 คุยกับดิสก์
   ผ่าน ANSI codepage ของเครื่อง (วินโดวส์ไทย = TIS-620/CP874)
   ถ้าไม่แปลงก่อน is_file() จะไม่เจอไฟล์ชื่อไทยเลยสักไฟล์
   (ทดสอบบนเซิร์ฟเวอร์จริงแล้ว: UTF-8 เจอ 0/55 · แปลงแล้วเจอครบ) */
function announcement_register_fs_name($utf8)
{
  $utf8 = (string) $utf8;

  /* ASCII ล้วนไม่ต้องแปลง และถ้าไม่มี iconv ก็ทำอะไรไม่ได้ */
  if ($utf8 === '' || !function_exists('iconv') || !preg_match('/[\x80-\xFF]/', $utf8)) {
    return $utf8;
  }

  $ansi = @iconv('UTF-8', 'TIS-620//IGNORE', $utf8);
  return ($ansi === false || $ansi === '') ? $utf8 : $ansi;
}

/* หาไฟล์จริงจาก base + พาธย่อย โดยลองทั้งแบบแปลง ANSI และแบบ UTF-8 เดิม
   (เผื่อเซิร์ฟเวอร์ที่ codepage ไม่ใช่ไทย จะได้ยังทำงานได้) */
function announcement_register_existing_path($base, $relative)
{
  $variants = array();
  $converted = announcement_register_fs_name($relative);

  if ($converted !== $relative) {
    $variants[] = $converted;
  }
  $variants[] = $relative;

  foreach ($variants as $variant) {
    $candidate = $base . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $variant);

    if (is_file($candidate) && is_readable($candidate)) {
      return $candidate;
    }
  }

  return '';
}

function announcement_register_resolve_document($workbook, $target)
{
  if ($workbook === '' || $target === '') {
    return '';
  }

  $decodedTarget = rawurldecode($target);

  /* กันพาธเต็ม/พาธเครือข่าย/สคีมแปลกปลอม และกันไต่ออกนอกโฟลเดอร์ */
  if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $decodedTarget)
    || preg_match('/^[\\\\\/]/', $decodedTarget)
    || strpos($decodedTarget, '..') !== false) {
    return '';
  }

  $base = dirname($workbook);
  if ($base === '' || !is_dir($base)) {
    return '';
  }

  return announcement_register_existing_path($base, $decodedTarget);
}

function announcement_register_resolve_mirrored_document($workbook, $reference)
{
  if ($workbook === '' || !preg_match('/^H[0-9]{1,5}$/', $reference)) {
    return '';
  }

  $base = realpath(dirname($workbook));
  if ($base === false) {
    return '';
  }

  $servedDirectory = $base . DIRECTORY_SEPARATOR . 'served';
  $extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png');

  foreach ($extensions as $extension) {
    $candidate = $servedDirectory . DIRECTORY_SEPARATOR . $reference . '.' . $extension;
    if (is_file($candidate) && is_readable($candidate)) {
      return $candidate;
    }
  }

  return '';
}

function announcement_register_document_content_type($path)
{
  $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  $types = array(
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
  );

  return isset($types[$extension]) ? $types[$extension] : 'application/octet-stream';
}

function announcement_register_format_updated($timestamp)
{
  if (!$timestamp) {
    return 'ไม่ทราบเวลา';
  }

  return gmdate('d/m/Y H:i', $timestamp + (7 * 3600)) . ' น.';
}
