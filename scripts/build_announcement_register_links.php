<?php
require_once dirname(__FILE__) . '/../announcement_register_helpers.php';

$targetDirectory = isset($argv[1]) ? rtrim($argv[1], '\\/') : '';
if ($targetDirectory === '' || !is_dir($targetDirectory)) {
  fwrite(STDERR, "Announcement mirror target is unavailable\n");
  exit(1);
}

$targetBase = realpath($targetDirectory);
$allowedBase = strtolower('\\\\192.168.5.7\\www\\SiMenu\\data\\announcement_register');
$targetCompare = strtolower(str_replace('/', '\\', $targetBase));
if ($targetBase === false || strpos($targetCompare, $allowedBase) !== 0) {
  fwrite(STDERR, "Announcement mirror target is outside the allowed directory\n");
  exit(1);
}

$servedDirectory = $targetBase . DIRECTORY_SEPARATOR . 'served';
if (!is_dir($servedDirectory) && !mkdir($servedDirectory, 0775, true)) {
  fwrite(STDERR, "Cannot create the served document directory\n");
  exit(1);
}

$register = announcement_register_read();
if (!$register['ok']) {
  fwrite(STDERR, $register['error'] . "\n");
  exit(1);
}

$allowedExtensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png');
$copied = 0;
$available = 0;

foreach ($register['rows'] as $row) {
  if (!isset($row['download_ref']) || $row['download_ref'] === '' || $row['download_target'] === '') {
    continue;
  }

  $sourceDocument = announcement_register_resolve_document($register['workbook'], $row['download_target']);
  if ($sourceDocument === '') {
    continue;
  }

  $extension = strtolower(pathinfo($sourceDocument, PATHINFO_EXTENSION));
  if (!in_array($extension, $allowedExtensions)) {
    continue;
  }

  $destination = $servedDirectory . DIRECTORY_SEPARATOR . $row['download_ref'] . '.' . $extension;
  $needsCopy = !is_file($destination)
    || filesize($destination) !== filesize($sourceDocument)
    || filemtime($destination) < filemtime($sourceDocument);

  if ($needsCopy) {
    if (!copy($sourceDocument, $destination)) {
      fwrite(STDERR, 'Cannot create link alias for ' . $row['download_ref'] . "\n");
      exit(1);
    }
    @touch($destination, filemtime($sourceDocument));
    $copied++;
  }

  $available++;
}

echo 'AVAILABLE=' . $available . PHP_EOL;
echo 'COPIED=' . $copied . PHP_EOL;
exit(0);
