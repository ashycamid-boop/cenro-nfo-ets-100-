<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
  header('Location: ' . app_url('index.php'));
  exit;
}

require_once __DIR__ . '/../../../config/db.php';

$pendingCount = 0;
$pending = [];

try {
  $stmtCount = $pdo->prepare("SELECT COUNT(*) AS cnt FROM service_requests WHERE LOWER(status) IN ('pending','open')");
  $stmtCount->execute();
  $row = $stmtCount->fetch();
  $pendingCount = isset($row['cnt']) ? (int)$row['cnt'] : 0;
} catch (Exception $e) {
  error_log('admin new_requests count error: ' . $e->getMessage());
  $pendingCount = 0;
}

try {
  // Include legacy 'open' rows as 'pending' (case-insensitive)
  $stmt = $pdo->prepare("SELECT * FROM service_requests WHERE LOWER(status) IN ('pending','open') ORDER BY created_at DESC");
  $stmt->execute();
  $pending = $stmt->fetchAll();
} catch (Exception $e) {
  error_log('admin new_requests fetch error: ' . $e->getMessage());
  $pending = [];
}

$sidebarRole = 'Administrator';
