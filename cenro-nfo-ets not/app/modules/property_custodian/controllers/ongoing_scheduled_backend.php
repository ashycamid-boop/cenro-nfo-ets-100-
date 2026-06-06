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

$ongoing = [];
try {
  $stmt = $pdo->prepare("SELECT * FROM service_requests WHERE LOWER(status) IN ('ongoing','scheduled') ORDER BY created_at DESC");
  $stmt->execute();
  $ongoing = $stmt->fetchAll();
} catch (Exception $e) {
  error_log('property_custodian ongoing_scheduled fetch error: ' . $e->getMessage());
  $ongoing = [];
}

$ongoingCount = count($ongoing);

try {
  $actionStmt = $pdo->prepare("SELECT action_date, action_time FROM service_request_actions WHERE service_request_id = :id ORDER BY action_date ASC, action_time ASC LIMIT 1");
} catch (Exception $e) {
  error_log('prepare actionStmt error: ' . $e->getMessage());
  $actionStmt = null;
}
