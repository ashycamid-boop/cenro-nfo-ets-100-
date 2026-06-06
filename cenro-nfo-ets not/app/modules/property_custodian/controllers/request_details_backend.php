<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'property_custodian') {
  header('Location: ' . app_url('index.php'));
  exit;
}

$save_error = $save_error ?? '';
$debug_info = $debug_info ?? '';
$request_id = isset($_GET['id']) ? $_GET['id'] : null;

require_once dirname(__DIR__, 3) . '/config/db.php';
require_once dirname(__DIR__, 3) . '/views/partials/property_custodian_signature_helper.php';
if (isset($pdo) && is_object($pdo)) {
  try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (Exception $e) {
    error_log('Could not set PDO ERRMODE: ' . $e->getMessage());
  }
}

$request = null;
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

$ticket_no = $request['ticket_no'] ?? $request_id ?? '';
$ticket_date = '';
if (!empty($request['ticket_date'])) {
  $ticket_date = date('m/d/Y', strtotime($request['ticket_date']));
} elseif (!empty($request['created_at'])) {
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

$auth1_name = $request['auth1_name'] ?? $request['auth1_fullname'] ?? '';
$auth1_position = $request['auth1_position'] ?? '';
$auth2_name = $request['auth2_name'] ?? $request['auth2_fullname'] ?? '';
$auth2_position = $request['auth2_position'] ?? '';

$requester_sig_url = !empty($request['requester_signature_path']) ? property_custodian_signature_proxy_url($request['requester_signature_path']) : '';
$auth1_sig_url = !empty($request['auth1_signature_path']) ? property_custodian_signature_proxy_url($request['auth1_signature_path']) : '';
$auth2_sig_url = !empty($request['auth2_signature_path']) ? property_custodian_signature_proxy_url($request['auth2_signature_path']) : '';
$ack_sig_url = !empty($request['ack_signature_path']) ? property_custodian_signature_proxy_url($request['ack_signature_path']) : '';

$request_actions = [];
$feedback_rating = $request['feedback_rating'] ?? $request['feedback'] ?? '';
$feedback_rating = is_string($feedback_rating) ? strtolower(trim($feedback_rating)) : '';
$is_completed = false;
if (!empty($request['status'])) {
  $status_val = strtolower(trim((string) $request['status']));
  $is_completed = in_array($status_val, ['completed', 'done', '1', 'true'], true);
}

$service_request_id_for_actions = null;
try {
  if (!empty($request['id'])) {
    $service_request_id_for_actions = (int) $request['id'];
  } elseif (!empty($request_id) && ctype_digit((string) $request_id)) {
    $service_request_id_for_actions = (int) $request_id;
  } elseif (!empty($request_id)) {
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

try {
  $stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE full_name IS NOT NULL AND TRIM(full_name) <> '' ORDER BY full_name ASC");
  $stmt->execute();
  $staff_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $staff_users = [];
  error_log('fetch staff users error: ' . $e->getMessage());
}
