<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
  http_response_code(403);
  exit('Forbidden');
}

$rawPath = isset($_GET['path']) ? (string) $_GET['path'] : '';
$rawPath = str_replace('\\', '/', trim($rawPath));

if ($rawPath === '' || strpos($rawPath, '..') !== false) {
  http_response_code(400);
  exit('Invalid path');
}

if (stripos($rawPath, 'public/uploads/') !== 0) {
  http_response_code(400);
  exit('Invalid path');
}

$projectRoot = dirname(__DIR__, 4);
$fullPath = $projectRoot . '/' . ltrim($rawPath, '/');

if (!is_file($fullPath) || !is_readable($fullPath)) {
  http_response_code(404);
  exit('Not found');
}

$mimeType = function_exists('mime_content_type') ? mime_content_type($fullPath) : 'application/octet-stream';
if (!is_string($mimeType) || $mimeType === '') {
  $mimeType = 'application/octet-stream';
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($fullPath));
header('Cache-Control: private, max-age=300');
readfile($fullPath);
exit;
