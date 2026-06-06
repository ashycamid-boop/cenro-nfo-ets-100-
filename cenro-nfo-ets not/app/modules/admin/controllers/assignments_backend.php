<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../../config/db.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
  header('Location: ' . app_url('index.php'));
  exit;
}

$users = [];
try {
  $stmt = $pdo->query("SELECT id, email, full_name, role, office_unit, profile_picture, created_at FROM users ORDER BY id DESC");
  $users = $stmt->fetchAll();
} catch (Exception $e) {
  $users = [];
}

// Compute device counts per user. Use a prepared statement to handle numeric ID, exact name, or partial name matches.
if (!empty($users)) {
  try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM equipment WHERE actual_user = :user_id OR actual_user = :full_name OR actual_user LIKE :like_name");
    foreach ($users as &$u) {
      $uid = (int)($u['id'] ?? 0);
      $full = $u['full_name'] ?? '';
      $like = '%' . $full . '%';
      try {
        $countStmt->execute([':user_id' => (string)$uid, ':full_name' => $full, ':like_name' => $like]);
        $row = $countStmt->fetch();
        $u['device_count'] = $row ? (int)$row['cnt'] : 0;
      } catch (Exception $e) {
        $u['device_count'] = 0;
      }
    }
    unset($u);
  } catch (Exception $e) {
    // Ignore counts on failure
    foreach ($users as &$u) {
      $u['device_count'] = 0;
    }
    unset($u);
  }
}

$sidebarRole = 'Administrator';
