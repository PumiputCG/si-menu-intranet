<?php
require_once dirname(__FILE__) . '/announcement_helpers.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$fileId = isset($_GET['file']) ? $_GET['file'] : '';
$announcements = announcement_read_all();
$match = null;
$attachment = null;

foreach ($announcements as $announcement) {
  if (isset($announcement['id']) && $announcement['id'] === $id) {
    $match = $announcement;
    break;
  }
}

if ($match) {
  $attachments = announcement_get_attachments($match);
  foreach ($attachments as $item) {
    $itemId = isset($item['id']) ? $item['id'] : (isset($item['stored_name']) ? $item['stored_name'] : '');
    if ($fileId === '' || $itemId === $fileId) {
      $attachment = $item;
      break;
    }
  }
}

if (!$match || !isset($attachment['stored_name'])) {
  if (function_exists('http_response_code')) {
    http_response_code(404);
  } else {
    header('HTTP/1.1 404 Not Found');
  }
  echo 'File not found';
  exit;
}

$storedName = basename($attachment['stored_name']);
$filePath = ANNOUNCEMENT_UPLOAD_DIR . '/' . $storedName;
$realUploadDir = realpath(ANNOUNCEMENT_UPLOAD_DIR);
$realFile = realpath($filePath);

if (!$realFile || strpos($realFile, $realUploadDir) !== 0 || !is_file($realFile)) {
  if (function_exists('http_response_code')) {
    http_response_code(404);
  } else {
    header('HTTP/1.1 404 Not Found');
  }
  echo 'File not found';
  exit;
}

$originalName = isset($attachment['original_name']) ? $attachment['original_name'] : $storedName;
$mimeType = 'application/octet-stream';
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

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($realFile));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $originalName) . '"');
header('X-Content-Type-Options: nosniff');
readfile($realFile);
exit;
?>
