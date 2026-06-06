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

$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
$user = null;
$devices = [];

if (!empty($user_id)) {
  $user_id_int = (int)$user_id;
  try {
    $stmt = $pdo->prepare("SELECT id, full_name, email, role, office_unit, profile_picture, contact_number FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id_int]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    $user = null;
  }

  if ($user) {
    // actual_user may store numeric user id, full name, or free text.
    try {
      $q = "SELECT id, property_number, equipment_type, brand, model, serial_number, year_acquired, status
            FROM equipment
            WHERE actual_user = :user_id
               OR actual_user = :full_name
               OR actual_user LIKE :like_name
            ORDER BY property_number ASC";
      $stmt2 = $pdo->prepare($q);
      $like = '%' . $user['full_name'] . '%';
      $stmt2->execute([
        ':user_id' => (string)$user_id_int,
        ':full_name' => $user['full_name'],
        ':like_name' => $like
      ]);
      $devices = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      $devices = [];
    }
  }
}

$sidebarRole = 'Administrator';
