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
if (isset($pdo) && is_object($pdo)) {
  try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (Exception $e) {}
}

$requests = [];
try {
  $stmt = $pdo->query('SELECT * FROM service_requests ORDER BY created_at DESC');
  $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log('all_requests fetch error: ' . $e->getMessage());
  $requests = [];
}

try {
  $earliestStmt = $pdo->prepare("SELECT action_date, action_time FROM service_request_actions WHERE service_request_id = :id AND action_date IS NOT NULL ORDER BY action_date ASC, action_time ASC LIMIT 1");
  $latestStmt = $pdo->prepare("SELECT action_date, action_time FROM service_request_actions WHERE service_request_id = :id ORDER BY id DESC LIMIT 1");
} catch (Exception $e) {
  error_log('prepare action stmts error: ' . $e->getMessage());
  $earliestStmt = $latestStmt = null;
}
