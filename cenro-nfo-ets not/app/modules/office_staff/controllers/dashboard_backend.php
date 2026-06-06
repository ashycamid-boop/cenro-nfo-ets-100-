<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'office_staff') {
  header('Location: ' . app_url('index.php'));
  exit;
}

// Compute service request counts for this module/user
$total_service_requests = 0;
$completed_service_requests = 0;
$pending_service_requests = 0;
$ongoing_scheduled_service_requests = 0;

// Determine current user identity (some pages use uid, user_id or id)
$currentUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$currentUserEmail = $_SESSION['email'] ?? null;

try {
  require_once __DIR__ . '/../../../config/db.php';

  if (!empty($currentUserId)) {
    $where = 'created_by = ?';
    $params = [$currentUserId];
  } elseif (!empty($currentUserEmail)) {
    $where = 'requester_email = ?';
    $params = [$currentUserEmail];
  } else {
    // no user context -> no rows
    $where = '1=0';
    $params = [];
  }

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE " . $where);
  $stmt->execute($params);
  $total_service_requests = (int)$stmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE " . $where . " AND LOWER(status) IN ('completed','done')");
  $stmt->execute($params);
  $completed_service_requests = (int)$stmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE " . $where . " AND LOWER(status) IN ('pending','open')");
  $stmt->execute($params);
  $pending_service_requests = (int)$stmt->fetchColumn();

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE " . $where . " AND LOWER(status) IN ('ongoing','scheduled')");
  $stmt->execute($params);
  $ongoing_scheduled_service_requests = (int)$stmt->fetchColumn();
} catch (Exception $e) {
  error_log('dashboard counts error: ' . $e->getMessage());
}
