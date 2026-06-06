<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'property_custodian') {
  header('Location: ' . app_url('index.php'));
  exit;
}

$preloadedUsers = [];
try {
  require_once dirname(__DIR__, 3) . '/config/db.php';
  $stmt = $pdo->prepare("SELECT id, full_name, '' AS sex FROM users WHERE status = 1 ORDER BY full_name ASC");
  $stmt->execute();
  $preloadedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
  $preloadedUsers = [];
}