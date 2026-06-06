<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'property_custodian') {
  header('Location: ' . app_url('index.php'));
  exit;
}

require_once dirname(__DIR__, 3) . '/config/db.php';

$pending = [];
try {
  // Include legacy 'open' rows as 'pending' (case-insensitive)
  $stmt = $pdo->prepare("SELECT * FROM service_requests WHERE LOWER(status) IN ('pending','open') ORDER BY created_at DESC");
  $stmt->execute();
  $pending = $stmt->fetchAll();
} catch (Exception $e) {
  error_log('property_custodian new_requests fetch error: ' . $e->getMessage());
  $pending = [];
}
