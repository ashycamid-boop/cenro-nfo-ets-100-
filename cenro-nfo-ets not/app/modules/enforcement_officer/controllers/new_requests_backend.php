<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$sidebarRole = 'Enforcement Officer';

$request_id = isset($_GET['id']) ? $_GET['id'] : '';

// Server-side preview ticket number and current date (shows next sequential ticket)
$current_date = date('m/d/Y');
$ticket_no = '';
try {
  require_once dirname(__DIR__, 3) . '/config/db.php';
  $year = date('Y');
  $month = date('m');
  $like = $year . '-' . $month . '-%';
  $maxStmt = $pdo->prepare("SELECT MAX(CAST(RIGHT(ticket_no,4) AS UNSIGNED)) FROM service_requests WHERE ticket_no LIKE ?");
  $maxStmt->execute([$like]);
  $maxVal = (int)$maxStmt->fetchColumn();
  $next = $maxVal + 1;
  $ticket_no = 'CN-' . sprintf('%s-%s-%04d', $year, $month, $next);
} catch (Exception $e) {
  // Fallback: show a readable placeholder if DB unavailable
  $ticket_no = 'CN-' . date('Y') . '-' . date('m') . '-0001';
}

// Auto-populate requester details from logged-in user (session -> database)
$requester_name = $requester_email = $requester_phone = $requester_office = '';
$sessionUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$sessionUserEmail = $_SESSION['email'] ?? null;
if (!empty($sessionUserId) || !empty($sessionUserEmail)) {
  require_once dirname(__DIR__, 3) . '/config/db.php';
  try {
    if (!empty($sessionUserId)) {
      $stmt = $pdo->prepare('SELECT full_name, email, contact_number, office_unit FROM users WHERE id = ? LIMIT 1');
      $stmt->execute([$sessionUserId]);
    } else {
      $stmt = $pdo->prepare('SELECT full_name, email, contact_number, office_unit FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$sessionUserEmail]);
    }
    $r = $stmt->fetch();
    if (!empty($r)) {
      $requester_name = $r['full_name'] ?? $requester_name;
      $requester_email = $r['email'] ?? $requester_email;
      $requester_phone = $r['contact_number'] ?? $requester_phone;
      $requester_office = $r['office_unit'] ?? $requester_office;
    }
  } catch (Exception $e) {
    // silently ignore DB errors; fields will remain empty
  }
}

