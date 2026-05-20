<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();

// Ensure current session user id is available (common project convention)
$sessionUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

// Enable verbose error reporting for debugging while we troubleshoot save failures
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// holder for save error diagnostics (shown in-page during debugging)
$save_error = null;
$debug_info = [];
// flags for approval validation to show inline reminders
$approval_blocked = false;
$missing_auth1 = false;
$missing_auth2 = false;
// first action row missing flags
$missing_action = false;
$missing_action_date = false;
$missing_action_time = false;
$missing_action_details = false;
$missing_action_staff = false;

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'property_custodian') {
  header('Location: ' . app_url('index.php'));
  exit;
}
// Handle approve/reject POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id']) && !empty($_POST['status_action'])) {
  $postId = $_POST['id'];
  $action = $_POST['status_action']; // 'approve', 'reject', 'complete'
  require_once __DIR__ . '/../../../config/db.php';
  // Map action to a status value
  if ($action === 'approve') {
    $newStatus = 'Ongoing';
  } elseif ($action === 'complete') {
    $newStatus = 'Completed';
  } elseif ($action === 'reject') {
    $newStatus = 'Rejected';
  } else {
    $newStatus = null;
  }
  // If approving, ensure both authorization entries exist (either name or signature)
  if ($action === 'approve' && $newStatus !== null) {
    try {
      if (ctype_digit((string)$postId)) {
        $vstmt = $pdo->prepare('SELECT id, auth1_name, auth1_signature_path, auth2_name, auth2_signature_path FROM service_requests WHERE id = ? LIMIT 1');
      } else {
        $vstmt = $pdo->prepare('SELECT id, auth1_name, auth1_signature_path, auth2_name, auth2_signature_path FROM service_requests WHERE ticket_no = ? LIMIT 1');
      }
      $vstmt->execute([$postId]);
      $vrow = $vstmt->fetch(PDO::FETCH_ASSOC);
      $auth1_ok = !empty($vrow['auth1_name']) || !empty($vrow['auth1_signature_path']);
      $auth2_ok = !empty($vrow['auth2_name']) || !empty($vrow['auth2_signature_path']);

      // Require the first action row to have details/staff when approving.
      $first_action_ok = true;
      $missing_action = $missing_action_date = $missing_action_time = $missing_action_details = $missing_action_staff = false;
      try {
        $service_request_id_for_actions = null;
        if (!empty($vrow['id'])) {
          $service_request_id_for_actions = (int)$vrow['id'];
        } elseif (ctype_digit((string)$postId)) {
          $service_request_id_for_actions = (int)$postId;
        } else {
          $t = $pdo->prepare('SELECT id FROM service_requests WHERE ticket_no = ? LIMIT 1');
          $t->execute([$postId]);
          $service_request_id_for_actions = $t->fetchColumn();
        }
        if (!empty($service_request_id_for_actions)) {
          $a = $pdo->prepare('SELECT action_date, action_time, action_details, action_staff_id FROM service_request_actions WHERE service_request_id = ? ORDER BY created_at ASC LIMIT 1');
          $a->execute([$service_request_id_for_actions]);
          $firstAct = $a->fetch(PDO::FETCH_ASSOC);
          if (empty($firstAct) || (trim((string)($firstAct['action_details'] ?? '')) === '' || empty($firstAct['action_staff_id']))) {
            $first_action_ok = false;
            $missing_action = true;
            $missing_action_details = empty(trim((string)($firstAct['action_details'] ?? '')));
            $missing_action_staff = empty(trim((string)($firstAct['action_staff_id'] ?? '')));
            $missing_action_date = empty(trim((string)($firstAct['action_date'] ?? '')));
            $missing_action_time = empty(trim((string)($firstAct['action_time'] ?? '')));
          }
        }
      } catch (Exception $e2) {
        $debug_info[] = ['first_action_check_error' => $e2->getMessage()];
      }

      if (!($auth1_ok && $auth2_ok) || !$first_action_ok) {
        $save_error = 'Approval blocked: both Authorization entries and the first Action row must be completed before approving this request.';
        $debug_info[] = ['approve_validation' => $vrow, 'first_action_ok' => $first_action_ok];
        // mark which auths are missing so we can highlight them inline in the form
        $approval_blocked = true;
        $missing_auth1 = !$auth1_ok;
        $missing_auth2 = !$auth2_ok;
        // If first action missing, ensure flags are set (already computed)
        // Cancel the status update by clearing newStatus
        $newStatus = null;
      }
    } catch (Exception $e) {
      error_log('edit_requests approval validation error: ' . $e->getMessage());
      $debug_info[] = ['approve_validation_exception' => $e->getMessage()];
      // don't change $newStatus here; fail-safe will allow update to continue if desired
    }
  }
  if ($newStatus !== null) {
    try {
      if (ctype_digit((string)$postId)) {
        $stmt = $pdo->prepare('UPDATE service_requests SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$newStatus, $postId]);
      } else {
        $stmt = $pdo->prepare('UPDATE service_requests SET status = ?, updated_at = NOW() WHERE ticket_no = ?');
        $stmt->execute([$newStatus, $postId]);
      }
    } catch (Exception $e) {
      error_log('edit_requests status update error: ' . $e->getMessage());
    }

    // Redirect after action
    if ($action === 'approve') {
      header('Location: ongoing_scheduled.php');
    } elseif ($action === 'complete') {
      header('Location: completed.php');
    } elseif ($action === 'reject') {
      header('Location: new_requests.php');
    } else {
      header('Location: edit_requests.php?id=' . urlencode($postId));
    }
    exit;
  }
}
// Handle saving edits
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id']) && !empty($_POST['save_changes'])) {
  $postId = $_POST['id'];
  require_once __DIR__ . '/../../../config/db.php';
  $auth1_user_id = isset($_POST['auth1_user_id']) ? (int)$_POST['auth1_user_id'] : 0;
  $auth2_user_id = isset($_POST['auth2_user_id']) ? (int)$_POST['auth2_user_id'] : 0;
  $auth1_name = '';
  $auth1_position = $_POST['auth1_position'] ?? '';
  $auth1_date = $_POST['auth1_date'] ?? null;
  $auth2_name = '';
  $auth2_position = $_POST['auth2_position'] ?? '';
  $auth2_date = $_POST['auth2_date'] ?? null;
  $clear_auth1_signature = false;
  $clear_auth2_signature = false;
  $authUsersById = [];
  $existingAuth1Name = '';
  $existingAuth2Name = '';
  try {
    $auStmt = $pdo->prepare("SELECT id, full_name FROM users WHERE status = 1 AND full_name IS NOT NULL AND TRIM(full_name) <> '' AND REPLACE(TRIM(LOWER(role)), ' ', '_') IN ('admin', 'property_custodian') ORDER BY full_name ASC");
    $auStmt->execute();
    foreach ($auStmt->fetchAll(PDO::FETCH_ASSOC) as $au) {
      $authUsersById[(int)$au['id']] = trim((string)$au['full_name']);
    }
    if ($auth1_user_id > 0 && isset($authUsersById[$auth1_user_id])) {
      $auth1_name = $authUsersById[$auth1_user_id];
    }
    if ($auth2_user_id > 0 && isset($authUsersById[$auth2_user_id])) {
      $auth2_name = $authUsersById[$auth2_user_id];
    }
    if (ctype_digit((string)$postId)) {
      $erStmt = $pdo->prepare('SELECT auth1_name, auth2_name FROM service_requests WHERE id = ? LIMIT 1');
    } else {
      $erStmt = $pdo->prepare('SELECT auth1_name, auth2_name FROM service_requests WHERE ticket_no = ? LIMIT 1');
    }
    $erStmt->execute([$postId]);
    $erow = $erStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $existingAuth1Name = trim((string)($erow['auth1_name'] ?? ''));
    $existingAuth2Name = trim((string)($erow['auth2_name'] ?? ''));
  } catch (Exception $e) {
    $save_error = 'Unable to validate authorization users: ' . $e->getMessage();
    $debug_info[] = ['auth_user_validation_exception' => $e->getMessage()];
  }

  if (empty($save_error) && !empty($_POST['auth1_signature_data'])) {
    if (empty($auth1_user_id) || !isset($authUsersById[$auth1_user_id])) {
      $save_error = 'Signature not allowed: please select a valid Authorization signer first.';
    } elseif (empty($sessionUserId) || (string)$auth1_user_id !== (string)$sessionUserId) {
      $save_error = 'Signature not allowed: only the selected Authorization user may sign.';
    }
  }
  if (empty($save_error) && !empty($_POST['auth2_signature_data'])) {
    if (empty($auth2_user_id) || !isset($authUsersById[$auth2_user_id])) {
      $save_error = 'Signature not allowed: please select a valid Infrastructure Authorization signer first.';
    } elseif (empty($sessionUserId) || (string)$auth2_user_id !== (string)$sessionUserId) {
      $save_error = 'Signature not allowed: only the selected Infrastructure Authorization user may sign.';
    }
  }

  if (empty($save_error) && $existingAuth1Name !== '' && trim($auth1_name) !== $existingAuth1Name && empty($_POST['auth1_signature_data'])) {
    $clear_auth1_signature = true;
  }
  if (empty($save_error) && $existingAuth2Name !== '' && trim($auth2_name) !== $existingAuth2Name && empty($_POST['auth2_signature_data'])) {
    $clear_auth2_signature = true;
  }

  // handle signature data (base64) for auth1/auth2
  $auth1_sig_path = null;
  $auth2_sig_path = null;
  if (empty($save_error) && !empty($_POST['auth1_signature_data'])) {
    $data = $_POST['auth1_signature_data'];
    if (preg_match('/^data:\w+\/\w+;base64,/', $data)) {
      $data = preg_replace('/^data:\w+\/\w+;base64,/', '', $data);
    }
    $decoded = base64_decode($data);
    if ($decoded !== false) {
      $dir = __DIR__ . '/../../../../public/uploads/signatures/';
      if (!is_dir($dir)) mkdir($dir, 0755, true);
      $fname = 'auth1_' . uniqid() . '.png';
      $full = $dir . $fname;
      file_put_contents($full, $decoded);
      $auth1_sig_path = 'public/uploads/signatures/' . $fname;
    }
  }
  if (empty($save_error) && !empty($_POST['auth2_signature_data'])) {
    $data = $_POST['auth2_signature_data'];
    if (preg_match('/^data:\w+\/\w+;base64,/', $data)) {
      $data = preg_replace('/^data:\w+\/\w+;base64,/', '', $data);
    }
    $decoded = base64_decode($data);
    if ($decoded !== false) {
      $dir = __DIR__ . '/../../../../public/uploads/signatures/';
      if (!is_dir($dir)) mkdir($dir, 0755, true);
      $fname = 'auth2_' . uniqid() . '.png';
      $full = $dir . $fname;
      file_put_contents($full, $decoded);
      $auth2_sig_path = 'public/uploads/signatures/' . $fname;
    }
  }
  if (empty($save_error)) try {
    // ensure PDO throws exceptions for easier debugging
    if (is_object($pdo)) {
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Determine which columns actually exist in the table so we don't try to update non-existent columns
    $cols = [];
    try {
      $colStmt = $pdo->query("SHOW COLUMNS FROM service_requests");
      $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Exception $inner) {
      // If SHOW COLUMNS fails for any reason, fall back to empty list and continue
      $debug_info[] = ['show_columns_error' => $inner->getMessage()];
      $cols = [];
    }

    // Map of potential columns to values
    $fieldMap = [
      'auth1_name' => $auth1_name,
      'auth1_position' => $auth1_position,
      'auth1_date' => $auth1_date,
      'auth2_name' => $auth2_name,
      'auth2_position' => $auth2_position,
      'auth2_date' => $auth2_date,
    ];

    // Build SET clause only with columns that exist
    $setParts = [];
    $params = [];
    foreach ($fieldMap as $col => $val) {
      if (in_array($col, $cols, true)) {
        $setParts[] = "$col = ?";
        $params[] = $val === '' ? null : $val;
      }
    }
    if ($auth1_sig_path !== null && in_array('auth1_signature_path', $cols, true)) { $setParts[] = 'auth1_signature_path = ?'; $params[] = $auth1_sig_path; }
    if ($auth2_sig_path !== null && in_array('auth2_signature_path', $cols, true)) { $setParts[] = 'auth2_signature_path = ?'; $params[] = $auth2_sig_path; }
    if ($clear_auth1_signature && in_array('auth1_signature_path', $cols, true)) { $setParts[] = 'auth1_signature_path = NULL'; }
    if ($clear_auth2_signature && in_array('auth2_signature_path', $cols, true)) { $setParts[] = 'auth2_signature_path = NULL'; }

    // Always update updated_at if the column exists
    if (in_array('updated_at', $cols, true)) {
      $setParts[] = 'updated_at = NOW()';
    }

    if (empty($setParts)) {
      $save_error = 'No updatable columns present in service_requests table.';
      $debug_info[] = ['available_columns' => $cols];
    } else {
      $sql = 'UPDATE service_requests SET ' . implode(', ', $setParts);
      if (ctype_digit((string)$postId)) {
        $sql .= ' WHERE id = ?';
        $params[] = $postId;
      } else {
        $sql .= ' WHERE ticket_no = ?';
        $params[] = $postId;
      }

      $stmt = $pdo->prepare($sql);
      $ok = $stmt->execute($params);
      if (!$ok || $stmt->errorCode() !== '00000') {
        $save_error = 'Failed to execute UPDATE';
        $debug_info[] = ['sql' => $sql, 'params' => $params, 'error' => $stmt->errorInfo()];
      } else {
        $debug_info[] = ['updated_rows' => $stmt->rowCount(), 'sql' => $sql];
      }
    }
  } catch (Exception $e) {
    $save_error = 'Exception during save: ' . $e->getMessage();
    $debug_info[] = ['exception' => $e->getMessage(), 'sql' => $sql ?? null, 'params' => $params ?? null];
    error_log('edit_requests save error: ' . $e->getMessage());
  }
  // ---- Persist action rows (staff actions) if any were submitted ----
  if (empty($save_error)) try {
    // resolve numeric service_request id
    $service_request_id = null;
    if (ctype_digit((string)$postId)) {
      $service_request_id = (int)$postId;
    } else {
      $stmt = $pdo->prepare('SELECT id FROM service_requests WHERE ticket_no = ? LIMIT 1');
      $stmt->execute([$postId]);
      $service_request_id = $stmt->fetchColumn();
    }

      if ($service_request_id) {
        $debug_info[] = ['service_request_id' => $service_request_id];

        try {
          $pdo->beginTransaction();

          // Delete existing actions for this request (we'll re-insert from the form)
          $del = $pdo->prepare('DELETE FROM service_request_actions WHERE service_request_id = ?');
          $ok = $del->execute([$service_request_id]);
          if (!$ok || $del->errorCode() !== '00000') {
            $save_error = 'Failed to delete existing actions';
            $debug_info[] = ['delete_error' => $del->errorInfo(), 'service_request_id' => $service_request_id];
            $pdo->rollBack();
          } else {
            $debug_info[] = ['deleted_rows' => $del->rowCount()];
          }

          $action_dates = $_POST['action_date'] ?? [];
          $action_times = $_POST['action_time'] ?? [];
          $action_details = $_POST['action_details'] ?? [];
          $action_staff = $_POST['action_staff'] ?? [];
          $action_signatures = $_POST['action_signature_data'] ?? [];
          $action_old_staff_ids = $_POST['action_old_staff_id'] ?? [];
          $action_existing_signature_paths = $_POST['action_existing_signature_path'] ?? [];

          $debug_info[] = ['posted_action_counts' => [count($action_dates), count($action_times), count($action_details), count($action_staff), count($action_signatures)]];

          $insert = $pdo->prepare('INSERT INTO service_request_actions (service_request_id, action_date, action_time, action_details, action_staff_id, action_signature_path) VALUES (?, ?, ?, ?, ?, ?)');

          // prepare upload dir
          $dir = __DIR__ . '/../../../../public/uploads/signatures/';
          if (!is_dir($dir)) mkdir($dir, 0755, true);

          $count = max(count($action_dates), count($action_times), count($action_details), count($action_staff), count($action_signatures));
          for ($i = 0; $i < $count; $i++) {
            $ad = trim($action_dates[$i] ?? '');
            $at = trim($action_times[$i] ?? '');
            $det = trim($action_details[$i] ?? '');
            $staffId = !empty($action_staff[$i]) ? $action_staff[$i] : null;
            $sigPath = null;

            // skip empty rows (no date, time, details and no staff and no signature)
            if ($ad === '' && $at === '' && $det === '' && empty($staffId) && empty($action_signatures[$i])) continue;

            // handle signature data for this action
            $sigData = $action_signatures[$i] ?? '';
            if (!empty($sigData)) {
              // security: only the assigned Action Staff (current user) may supply a signature for this row
              if (empty($sessionUserId) || (string)$staffId !== (string)$sessionUserId) {
                $save_error = 'Signature not allowed: only the assigned Action Staff may sign this action.';
                $debug_info[] = ['action_index' => $i, 'staffId' => $staffId, 'sessionUserId' => $sessionUserId];
                $pdo->rollBack();
                break;
              }
              $data = $sigData;
              if (preg_match('/^data:\w+\/\w+;base64,/', $data)) {
                $data = preg_replace('/^data:\w+\/\w+;base64,/', '', $data);
              }
              $decoded = base64_decode($data);
              if ($decoded !== false) {
                $fname = 'action_' . $service_request_id . '_' . uniqid() . '.png';
                $full = $dir . $fname;
                $res = @file_put_contents($full, $decoded);
                if ($res === false) {
                  $debug_info[] = ['file_write_failed' => $full];
                } else {
                  $sigPath = 'public/uploads/signatures/' . $fname;
                }
              }
            }

            // If no new signature drawn but an existing path was provided, preserve it
            // only when the assigned staff for this row did not change (avoid accidental permanence)
            $oldStaff = $action_old_staff_ids[$i] ?? null;
            if (empty($sigPath) && !empty($action_existing_signature_paths[$i]) && $oldStaff !== null && (string)$oldStaff === (string)$staffId) {
              $sigPath = $action_existing_signature_paths[$i];
            }

            // convert empty date/time to nulls for DB
            $dbDate = $ad === '' ? null : $ad;
            $dbTime = $at === '' ? null : $at;

            $okInsert = $insert->execute([$service_request_id, $dbDate, $dbTime, $det, $staffId, $sigPath]);
            if (!$okInsert || $insert->errorCode() !== '00000') {
              $save_error = 'Failed to insert action row ' . $i;
              $debug_info[] = ['insert_error' => $insert->errorInfo(), 'row_index' => $i, 'params' => [$service_request_id, $dbDate, $dbTime, $det, $staffId, $sigPath]];
              $pdo->rollBack();
              break;
            } else {
              $debug_info[] = ['inserted_row' => $i, 'lastInsertId' => $pdo->lastInsertId()];
            }
          }

          if (empty($save_error)) {
            $pdo->commit();
          }

        } catch (Exception $e) {
          try { $pdo->rollBack(); } catch (Exception $rb) {}
          $save_error = 'Exception saving actions: ' . $e->getMessage();
          $debug_info[] = ['exception' => $e->getMessage()];
          error_log('edit_requests actions save error: ' . $e->getMessage());
        }
      }
  } catch (Exception $e) {
    error_log('edit_requests actions save error: ' . $e->getMessage());
  }
  // redirect back to the same page to show saved values if there were no save errors
  if (empty($save_error)) {
    header('Location: edit_requests.php?id=' . urlencode($postId));
    exit;
  }
}
?>