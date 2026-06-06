<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'enforcer') {
  header('Location: ' . app_url('index.php'));
  exit;
}

$sidebarRole = 'Enforcer';
$rows = [];

try {
  require_once dirname(__DIR__, 3) . '/config/db.php';

  // Show only spot reports submitted by the currently logged-in user.
  $sessionUid = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
  if ($sessionUid) {
    $stmt = $pdo->prepare("SELECT s.id, s.reference_no, s.incident_datetime, s.location, s.summary, s.team_leader, s.custodian, s.status, s.status_comment, u.full_name AS submitted_by_name, (SELECT SUM(value) FROM spot_report_items WHERE report_id = s.id) AS est_value FROM spot_reports s LEFT JOIN users u ON u.id = s.submitted_by WHERE s.submitted_by = ? ORDER BY s.created_at DESC");
    $stmt->execute([$sessionUid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Exception $e) {
  $rows = [];
}

if (!function_exists('enforcer_spot_short')) {
  function enforcer_spot_short($s, $len = 120) {
    $s = trim(strip_tags((string) $s));
    if (mb_strlen($s) <= $len) {
      return $s;
    }
    return mb_substr($s, 0, $len) . '...';
  }
}

