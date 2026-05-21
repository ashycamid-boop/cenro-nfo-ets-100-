<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();

// Enable verbose error reporting for debugging while we troubleshoot save failures
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// holder for save error diagnostics (shown in-page during debugging)
$save_error = null;
$debug_info = [];

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ' . app_url('index.php'));
    exit;
}
$sidebarRole = 'Administrator';
// current session user id (supports multiple session key conventions)
$sessionUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
// Handle approve/reject POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id']) && !empty($_POST['status_action'])) {
  $postId = $_POST['id'];
  $action = $_POST['status_action']; // 'approve', 'reject', 'complete'
  require_once __DIR__ . '/../../../config/db.php';
  // Map action to status
  if ($action === 'approve') {
    $newStatus = 'Ongoing';
  } elseif ($action === 'complete') {
    $newStatus = 'Completed';
  } elseif ($action === 'reject') {
    $newStatus = 'Rejected';
  } else {
    $newStatus = null;
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
      header('Location: edit_requests_ongoing.php?id=' . urlencode($postId));
    }
    exit;
  }
}
// Handle saving edits
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id']) && !empty($_POST['save_changes'])) {
  $postId = $_POST['id'];
  require_once __DIR__ . '/../../../config/db.php';
  if (is_object($pdo)) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  // ---- Persist action rows (staff actions) if any were submitted ----
  try {
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
            // only when the assigned staff for this row did not change (security / avoid accidental permanence)
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
    // If the Completed checkbox was checked when saving, update status to Completed
    if (!empty($_POST['completed_checkbox'])) {
      // Ensure at least one action signature exists and belongs to the assigned Action Staff (current user)
      $action_staff_post = $_POST['action_staff'] ?? [];
      $action_signatures_post = $_POST['action_signature_data'] ?? [];
      $action_existing_sig_post = $_POST['action_existing_signature_path'] ?? [];
      $hasValidSigner = false;
      $maxCount = max(count($action_staff_post), count($action_signatures_post), count($action_existing_sig_post));
      for ($j = 0; $j < $maxCount; $j++) {
        $as = $action_staff_post[$j] ?? null;
        $newSig = trim($action_signatures_post[$j] ?? '');
        $existingSig = trim($action_existing_sig_post[$j] ?? '');
        if (!empty($as) && (string)$as === (string)$sessionUserId && (!empty($newSig) || !empty($existingSig))) {
          $hasValidSigner = true;
          break;
        }
      }
      if (!$hasValidSigner) {
        $save_error = 'Cannot mark as Completed: a signature from the assigned Action Staff (you) is required.';
        $debug_info[] = ['completion_requires_assigned_signature' => true, 'sessionUserId' => $sessionUserId];
      } else {
        try {
          if (ctype_digit((string)$postId)) {
            $updateStmt = $pdo->prepare('UPDATE service_requests SET status = :status, updated_at = NOW() WHERE id = :id');
            $updateStmt->execute([':status' => 'Completed', ':id' => $postId]);
          } else {
            $updateStmt = $pdo->prepare('UPDATE service_requests SET status = :status, updated_at = NOW() WHERE ticket_no = :ticket_no');
            $updateStmt->execute([':status' => 'Completed', ':ticket_no' => $postId]);
          }
        } catch (Exception $e) {
          error_log('Failed to update status to Completed in edit_requests_ongoing.php: ' . $e->getMessage());
          $debug_info[] = ['complete_update_error' => $e->getMessage()];
        }
      }
    }

    if (!empty($_POST['completed_checkbox']) && empty($save_error)) {
      // After marking Completed via Save (and no validation errors), take user to the Completed list
      header('Location: completed.php');
      exit;
    }

    header('Location: edit_requests_ongoing.php?id=' . urlencode($postId));
    exit;
  }
}
?>
