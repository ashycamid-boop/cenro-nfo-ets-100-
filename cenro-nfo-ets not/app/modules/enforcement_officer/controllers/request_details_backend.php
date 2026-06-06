<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$sidebarRole = $_SESSION['role'] ?? 'Enforcement Officer';
$save_error = $save_error ?? '';
$debug_info = $debug_info ?? null;

$request_id = isset($_GET['id']) ? $_GET['id'] : null;
$request = null;
$request_actions = [];
$staff_users = [];
$service_request_id_for_actions = null;

require_once dirname(__DIR__, 3) . '/config/db.php';
require_once dirname(__DIR__, 3) . '/views/partials/enforcement_officer_signature_helper.php';

// Ensure PDO throws exceptions for easier debugging when available
if (isset($pdo) && is_object($pdo)) {
  try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (Exception $e) {
    error_log('Could not set PDO ERRMODE: ' . $e->getMessage());
  }
}

if (!empty($request_id)) {
  try {
    if (ctype_digit((string) $request_id)) {
      $stmt = $pdo->prepare('SELECT * FROM service_requests WHERE id = ? LIMIT 1');
      $stmt->execute([$request_id]);
    } else {
      $stmt = $pdo->prepare('SELECT * FROM service_requests WHERE ticket_no = ? LIMIT 1');
      $stmt->execute([$request_id]);
    }
    $request = $stmt->fetch();
  } catch (Exception $e) {
    error_log('request_details fetch error: ' . $e->getMessage());
    $request = null;
  }
}

// Populate display variables (fallback to empty)
$ticket_no = $request['ticket_no'] ?? $request_id ?? '';
$ticket_date = '';
if (!empty($request['ticket_date'])) {
  $ticket_date = date('m/d/Y', strtotime($request['ticket_date']));
} else if (!empty($request['created_at'])) {
  $ticket_date = date('m/d/Y', strtotime($request['created_at']));
}

$requester_name = $request['requester_name'] ?? '';
$requester_position = $request['requester_position'] ?? 'Project Support Staff';
$requester_office = $request['requester_office'] ?? 'CENRO Nasipit';
$requester_division = $request['requester_division'] ?? 'Construction Development Section';
$requester_phone = $request['requester_phone'] ?? $request['phone'] ?? '';
$requester_email = $request['requester_email'] ?? 'amyrcamid@gmail.com';
$request_type = $request['request_type'] ?? 'ASSIST IN THE ORIENTATION OF WATERSHED';
$request_description = $request['request_description'] ?? $request['description'] ?? '';

// Optional auth fields (may be empty if not stored)
// Do not default to a specific person/position here - show DB values or blank
$auth1_name = $request['auth1_name'] ?? $request['auth1_fullname'] ?? '';
$auth1_position = $request['auth1_position'] ?? '';
$auth2_name = $request['auth2_name'] ?? $request['auth2_fullname'] ?? '';
$auth2_position = $request['auth2_position'] ?? '';

// Signature URLs (saved as public/uploads/... in DB)
$requester_sig_url = !empty($request['requester_signature_path']) ? enforcement_officer_signature_proxy_url($request['requester_signature_path']) : '';
$auth1_sig_url = !empty($request['auth1_signature_path']) ? enforcement_officer_signature_proxy_url($request['auth1_signature_path']) : '';
$auth2_sig_url = !empty($request['auth2_signature_path']) ? enforcement_officer_signature_proxy_url($request['auth2_signature_path']) : '';
$ack_sig_url = !empty($request['ack_signature_path'])
  ? enforcement_officer_signature_proxy_url($request['ack_signature_path'])
  : '';

// Feedback and completion state (fall back to common fields)
$feedback_rating = $request['feedback_rating'] ?? $request['feedback'] ?? '';
$feedback_rating = is_string($feedback_rating) ? strtolower(trim($feedback_rating)) : '';
// If feedback already stored, prevent further edits to rating
$rating_finalized = !empty($request['feedback_rating']) || !empty($request['feedback']);
$is_completed = false;
if (!empty($request['status'])) {
  $status_val = strtolower(trim((string) $request['status']));
  $is_completed = in_array($status_val, ['completed', 'done', '1', 'true'], true);
}

try {
  if (!empty($request['id'])) {
    $service_request_id_for_actions = (int) $request['id'];
  } else if (!empty($request_id) && ctype_digit((string) $request_id)) {
    $service_request_id_for_actions = (int) $request_id;
  } else if (!empty($request_id)) {
    $tstmt = $pdo->prepare('SELECT id FROM service_requests WHERE ticket_no = ? LIMIT 1');
    $tstmt->execute([$request_id]);
    $service_request_id_for_actions = $tstmt->fetchColumn();
  }

  if (!empty($service_request_id_for_actions)) {
    $as = $pdo->prepare('SELECT * FROM service_request_actions WHERE service_request_id = ? ORDER BY created_at ASC');
    $as->execute([$service_request_id_for_actions]);
    $request_actions = $as->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Exception $e) {
  error_log('fetch request actions error: ' . $e->getMessage());
  $request_actions = [];
}

// Fetch users with role Admin or Property Custodian for staff dropdowns
try {
  $stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE status = 1 AND role IN ('Admin','Property Custodian') ORDER BY full_name ASC");
  $stmt->execute();
  $staff_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $staff_users = [];
  error_log('fetch staff users error: ' . $e->getMessage());
}
