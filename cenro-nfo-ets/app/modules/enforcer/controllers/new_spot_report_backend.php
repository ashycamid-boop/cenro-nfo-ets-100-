<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'enforcer') {
    header('Location: ' . app_url('index.php'));
    exit;
}

$sidebarRole = 'Enforcer';

// Server-side handler to save spot report as JSON and store uploaded files.
// Saves JSON to storage/spot_reports/{ref}.json and files to public/uploads/spot_reports/{ref}/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_once dirname(__DIR__, 3) . '/config/db.php'; // loads $pdo

  // Determine public uploads base robustly. Prefer DOCUMENT_ROOT when available.
  $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) : '';
  error_log('SpotReport: DOCUMENT_ROOT=' . $docRoot);
  $publicRoot = '';
  if ($docRoot && is_dir($docRoot)) {
    // If document root already points to public (has uploads), use it
    if (is_dir($docRoot . DIRECTORY_SEPARATOR . 'uploads')) {
      $publicRoot = $docRoot;
    } elseif (is_dir($docRoot . DIRECTORY_SEPARATOR . 'public')) {
      $publicRoot = $docRoot . DIRECTORY_SEPARATOR . 'public';
    }
  }
  // Fallback to previous heuristic if document root wasn't helpful
  if ($publicRoot === '') {
    $publicRoot = realpath(__DIR__ . '/../../../../public');
    if ($publicRoot === false) {
      $publicRoot = dirname(dirname(dirname(dirname(__DIR__))));
      $publicRoot .= DIRECTORY_SEPARATOR . 'public';
    }
  }

  $uploadBase = $publicRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'spot_reports';
  if (!is_dir($uploadBase)) {
    // create with permissive mode for development; tighten in production
    @mkdir($uploadBase, 0777, true);
  }
  error_log(sprintf('SpotReport uploadBase=%s exists=%s writable=%s', $uploadBase, is_dir($uploadBase) ? '1' : '0', is_writable($uploadBase) ? '1' : '0'));

  // Generate server-side reference number with format YYYY-MM-DD-0001 (sequence per day)
  $today = date('Y-m-d');
  $base = $today . '-';
  // count existing today refs to start sequence
  $countStmt = $pdo->prepare('SELECT COUNT(*) AS c FROM spot_reports WHERE reference_no LIKE ?');
  $countStmt->execute([$base . '%']);
  $cntRow = $countStmt->fetch(PDO::FETCH_ASSOC);
  $seq = ($cntRow && isset($cntRow['c'])) ? ((int)$cntRow['c'] + 1) : 1;
  // ensure uniqueness by incrementing if collision found
  $checkStmt = $pdo->prepare('SELECT 1 FROM spot_reports WHERE reference_no = ?');
  while (true) {
    $ref = $base . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    $checkStmt->execute([$ref]);
    if (!$checkStmt->fetch()) break;
    $seq++;
  }

  $incident_datetime = isset($_POST['incident_datetime']) ? $_POST['incident_datetime'] : null;
  $memo_date = isset($_POST['memo_date']) ? $_POST['memo_date'] : null;
  $location = isset($_POST['location']) ? trim((string)$_POST['location']) : '';
  $summary = isset($_POST['summary']) ? trim((string)$_POST['summary']) : '';
  $team_leader = isset($_POST['team_leader']) ? trim((string)$_POST['team_leader']) : '';
  $custodian = isset($_POST['custodian']) ? trim((string)$_POST['custodian']) : '';
  $status = ((isset($_POST['action']) ? $_POST['action'] : '') === 'save_draft') ? 'Draft' : 'Pending';
  $sessionUserId = $_SESSION['uid'] ?? $_SESSION['id'] ?? null;

  // Keep posted values for redisplay if validation fails
  $old = $_POST;

  // Server-side validation for required fields
  $errors = array();
  if (!$incident_datetime) $errors[] = 'Incident date & time is required.';
  if (!$memo_date) $errors[] = 'Memo date is required.';
  if ($location === '') $errors[] = 'Location is required.';
  if ($summary === '') $errors[] = 'Summary is required.';
  if ($team_leader === '') $errors[] = 'Team leader is required.';
  if ($custodian === '') $errors[] = 'Custodian is required.';

  if (empty($errors)) {
    try {
      $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO spot_reports (reference_no, incident_datetime, memo_date, location, summary, team_leader, custodian, status, submitted_by, created_at) VALUES (:ref, :incident, :memo, :location, :summary, :team_leader, :custodian, :status, :submitted_by, NOW())');
    $stmt->execute([
      ':ref' => $ref,
      ':incident' => $incident_datetime ?: null,
      ':memo' => $memo_date ?: null,
      ':location' => $location,
      ':summary' => $summary,
      ':team_leader' => $team_leader,
      ':custodian' => $custodian,
      ':status' => $status,
      ':submitted_by' => $sessionUserId ?: null,
    ]);

    $reportId = (int)$pdo->lastInsertId();

    // persons
    $names = isset($_POST['person_name']) ? $_POST['person_name'] : array();
    $ages = isset($_POST['person_age']) ? $_POST['person_age'] : array();
    $genders = isset($_POST['person_gender']) ? $_POST['person_gender'] : array();
    $addresses = isset($_POST['person_address']) ? $_POST['person_address'] : array();
    $contacts = isset($_POST['person_contact']) ? $_POST['person_contact'] : array();
    $roles = isset($_POST['person_role']) ? $_POST['person_role'] : array();
    $pstatuses = isset($_POST['person_status']) ? $_POST['person_status'] : array();

    $pStmt = $pdo->prepare('INSERT INTO spot_report_persons (report_id, name, age, gender, address, contact, role, status) VALUES (:rid, :name, :age, :gender, :address, :contact, :role, :status)');
    for ($i = 0; $i < count($names); $i++) {
      $n = trim((string)(isset($names[$i]) ? $names[$i] : ''));
      $a = trim((string)(isset($ages[$i]) ? $ages[$i] : ''));
      $g = trim((string)(isset($genders[$i]) ? $genders[$i] : ''));
      $ad = trim((string)(isset($addresses[$i]) ? $addresses[$i] : ''));
      $c = trim((string)(isset($contacts[$i]) ? $contacts[$i] : ''));
      $r = trim((string)(isset($roles[$i]) ? $roles[$i] : ''));
      $ps = trim((string)(isset($pstatuses[$i]) ? $pstatuses[$i] : ''));
      if ($n === '' && $ad === '') continue;
      $pStmt->execute([':rid' => $reportId, ':name' => $n, ':age' => $a, ':gender' => $g, ':address' => $ad, ':contact' => $c, ':role' => $r, ':status' => $ps]);
    }

    // vehicles
    $plates = isset($_POST['vehicle_plate']) ? $_POST['vehicle_plate'] : array();
    $makes = isset($_POST['vehicle_make']) ? $_POST['vehicle_make'] : array();
    $custom_makes = isset($_POST['vehicle_make_custom']) ? $_POST['vehicle_make_custom'] : array();
    $colors = isset($_POST['vehicle_color']) ? $_POST['vehicle_color'] : array();
    $owners = isset($_POST['vehicle_owner']) ? $_POST['vehicle_owner'] : array();
    $vcontacts = isset($_POST['vehicle_contact']) ? $_POST['vehicle_contact'] : array();
    $engines = isset($_POST['vehicle_engine']) ? $_POST['vehicle_engine'] : array();
    $vehicle_remarks = isset($_POST['vehicle_remarks']) ? $_POST['vehicle_remarks'] : array();
    $vehicle_status = isset($_POST['vehicle_status']) ? $_POST['vehicle_status'] : array();

    $vStmt = $pdo->prepare('INSERT INTO spot_report_vehicles (report_id, plate, make, color, owner, contact, engine, status, remarks) VALUES (:rid, :plate, :make, :color, :owner, :contact, :engine, :status, :remarks)');
    for ($i = 0; $i < count($plates); $i++) {
      $pl = trim((string)(isset($plates[$i]) ? $plates[$i] : ''));
      $ow = trim((string)(isset($owners[$i]) ? $owners[$i] : ''));
      if ($pl === '' && $ow === '') continue;
      $vrem = trim((string)(isset($vehicle_remarks[$i]) ? $vehicle_remarks[$i] : ''));
      $vstat = trim((string)(isset($vehicle_status[$i]) ? $vehicle_status[$i] : ''));
      $vStmt->execute([
        ':rid' => $reportId,
        ':plate' => $pl,
        ':make' => (function() use ($makes, $custom_makes, $i) {
          $selected = trim((string)(isset($makes[$i]) ? $makes[$i] : ''));
          $custom = trim((string)(isset($custom_makes[$i]) ? $custom_makes[$i] : ''));
          return ($selected === '__custom__') ? $custom : $selected;
        })(),
        ':color' => trim((string)(isset($colors[$i]) ? $colors[$i] : '')),
        ':owner' => $ow,
        ':contact' => trim((string)(isset($vcontacts[$i]) ? $vcontacts[$i] : '')),
        ':engine' => trim((string)(isset($engines[$i]) ? $engines[$i] : '')),
        ':status' => $vstat,
        ':remarks' => $vrem
      ]);
    }

    // items
    $item_nos = isset($_POST['item_no']) ? $_POST['item_no'] : array();
    $item_types = isset($_POST['item_type']) ? $_POST['item_type'] : array();
    $item_descs = isset($_POST['item_description']) ? $_POST['item_description'] : array();
    $item_qty = isset($_POST['item_quantity']) ? $_POST['item_quantity'] : array();
    $item_thickness = isset($_POST['item_thickness']) ? $_POST['item_thickness'] : array();
    $item_width = isset($_POST['item_width']) ? $_POST['item_width'] : array();
    $item_length = isset($_POST['item_length']) ? $_POST['item_length'] : array();
    $item_vol = isset($_POST['item_volume']) ? $_POST['item_volume'] : array();
    $item_val = isset($_POST['item_value']) ? $_POST['item_value'] : array();
    $item_rem = isset($_POST['item_remarks']) ? $_POST['item_remarks'] : array();
    $item_status = isset($_POST['item_status']) ? $_POST['item_status'] : array();

    $normalizeDecimal = static function ($value) {
      if ($value === null) return null;
      $value = trim((string)$value);
      if ($value === '') return null;
      $value = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $value));
      if ($value === '' || !is_numeric($value)) return null;
      return (float)$value;
    };

    $extractQuantityNumber = static function ($value) {
      if ($value === null) return null;
      $value = trim((string)$value);
      if ($value === '') return null;
      if (preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '', $value), $m)) {
        return (float)$m[0];
      }
      return null;
    };

    $iStmt = $pdo->prepare('INSERT INTO spot_report_items (report_id, item_no, type, description, quantity, thickness_in, width_in, length_ft, volume_bdft, volume, value, remarks, status) VALUES (:rid, :no, :type, :description, :quantity, :thickness_in, :width_in, :length_ft, :volume_bdft, :volume, :value, :remarks, :status)');
    for ($i = 0; $i < count($item_nos); $i++) {
      $desc = trim((string)(isset($item_descs[$i]) ? $item_descs[$i] : ''));
      if ($desc === '') continue;
      $thickness = $normalizeDecimal(isset($item_thickness[$i]) ? $item_thickness[$i] : null);
      $width = $normalizeDecimal(isset($item_width[$i]) ? $item_width[$i] : null);
      $length = $normalizeDecimal(isset($item_length[$i]) ? $item_length[$i] : null);
      $volumeBdft = $normalizeDecimal(isset($item_vol[$i]) ? $item_vol[$i] : null);
      $quantityNumber = $extractQuantityNumber(isset($item_qty[$i]) ? $item_qty[$i] : null);
      if ($volumeBdft === null && $thickness !== null && $width !== null && $length !== null) {
        $volumeBdft = (($thickness * $width * $length) / 12) * ($quantityNumber ?: 1);
      }
      $volumeText = $volumeBdft !== null ? number_format($volumeBdft, 2, '.', '') . ' Bd.ft.' : trim((string)(isset($item_vol[$i]) ? $item_vol[$i] : ''));
      $iStmt->execute([
        ':rid' => $reportId,
        ':no' => trim((string)(isset($item_nos[$i]) ? $item_nos[$i] : '')),
        ':type' => trim((string)(isset($item_types[$i]) ? $item_types[$i] : '')),
        ':description' => $desc,
        ':quantity' => trim((string)(isset($item_qty[$i]) ? $item_qty[$i] : '')),
        ':thickness_in' => $thickness,
        ':width_in' => $width,
        ':length_ft' => $length,
        ':volume_bdft' => $volumeBdft,
        ':volume' => $volumeText !== '' ? $volumeText : null,
        ':value' => $normalizeDecimal(isset($item_val[$i]) ? $item_val[$i] : null),
        ':remarks' => trim((string)(isset($item_rem[$i]) ? $item_rem[$i] : '')),
        ':status' => trim((string)(isset($item_status[$i]) ? $item_status[$i] : ''))
      ]);
    }

    // files
    $safeRef = preg_replace('/[^a-zA-Z0-9\-_.]/', '_', $ref);
    $uploadDir = $uploadBase . '/' . $safeRef;
    if (!is_dir($uploadDir)) {
      // create with permissive mode for development; tighten in production
      @mkdir($uploadDir, 0777, true);
    }
    error_log(sprintf('SpotReport uploadDir=%s exists=%s writable=%s', $uploadDir, is_dir($uploadDir) ? '1' : '0', is_writable($uploadDir) ? '1' : '0'));

    // Debug: log incoming files and environment for troubleshooting upload issues
    error_log('SpotReport upload - $_FILES: ' . print_r($_FILES, true));
    error_log(sprintf('SpotReport uploadBase=%s exists=%s writable=%s', $uploadBase, is_dir($uploadBase) ? '1' : '0', is_writable($uploadBase) ? '1' : '0'));
    error_log(sprintf('SpotReport uploadDir=%s exists=%s writable=%s', $uploadDir, is_dir($uploadDir) ? '1' : '0', is_writable($uploadDir) ? '1' : '0'));
    error_log('PHP settings: file_uploads=' . ini_get('file_uploads') . ', upload_max_filesize=' . ini_get('upload_max_filesize') . ', post_max_size=' . ini_get('post_max_size') . ', max_file_uploads=' . ini_get('max_file_uploads') . ', memory_limit=' . ini_get('memory_limit'));
    error_log('open_basedir=' . ini_get('open_basedir'));

    function uploadErrorText($code) {
      $map = [
        UPLOAD_ERR_OK => 'OK',
        UPLOAD_ERR_INI_SIZE => 'INI_SIZE (upload_max_filesize)',
        UPLOAD_ERR_FORM_SIZE => 'FORM_SIZE (MAX_FILE_SIZE)',
        UPLOAD_ERR_PARTIAL => 'PARTIAL',
        UPLOAD_ERR_NO_FILE => 'NO_FILE',
        UPLOAD_ERR_NO_TMP_DIR => 'NO_TMP_DIR',
        UPLOAD_ERR_CANT_WRITE => 'CANT_WRITE',
        UPLOAD_ERR_EXTENSION => 'EXTENSION_BLOCKED',
      ];
      return isset($map[$code]) ? $map[$code] : ('UNKNOWN_' . $code);
    }

    // handle per-row evidence files (persons, vehicles, items)
    $perFileStmt = $pdo->prepare('INSERT INTO spot_report_files (report_id, file_type, file_path, orig_name) VALUES (:rid, :type, :path, :orig)');

    if (!empty($_FILES['person_evidence']) && is_array($_FILES['person_evidence']['name'])) {
      for ($i = 0; $i < count($_FILES['person_evidence']['name']); $i++) {
        $err = isset($_FILES['person_evidence']['error'][$i]) ? $_FILES['person_evidence']['error'][$i] : UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
          error_log(sprintf('person_evidence[%d] upload error=%s (%s)', $i, $err, uploadErrorText($err)));
          continue;
        }
        $orig = basename($_FILES['person_evidence']['name'][$i]);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $filename = uniqid('person_') . '.' . $ext;
        $target = $uploadDir . '/' . $filename;
        $tmp = isset($_FILES['person_evidence']['tmp_name'][$i]) ? $_FILES['person_evidence']['tmp_name'][$i] : '';
        if (move_uploaded_file($tmp, $target)) {
          $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
          $perFileStmt->execute([':rid' => $reportId, ':type' => 'person_evidence', ':path' => $webPath, ':orig' => 'person#' . $i . ':' . $orig]);
        } else {
          if ($tmp && file_exists($tmp)) {
            // Attempt fallback: rename or copy
            if (@rename($tmp, $target) || (@copy($tmp, $target) && @unlink($tmp))) {
              error_log(sprintf('person_evidence[%d] moved via fallback rename/copy tmp=%s target=%s', $i, $tmp, $target));
              $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
              $perFileStmt->execute([':rid' => $reportId, ':type' => 'person_evidence', ':path' => $webPath, ':orig' => 'person#' . $i . ':' . $orig]);
            } else {
              error_log(sprintf('Failed to move (and fallback) person_evidence tmp=%s target=%s is_uploaded=%s err=%s', $tmp, $target, is_uploaded_file($tmp) ? '1' : '0', isset($_FILES['person_evidence']['error'][$i]) ? $_FILES['person_evidence']['error'][$i] : 'n/a'));
            }
          } else {
            error_log(sprintf('Failed to move person_evidence tmp missing=%s target=%s err=%s', $tmp, $target, isset($_FILES['person_evidence']['error'][$i]) ? $_FILES['person_evidence']['error'][$i] : 'n/a'));
          }
        }
      }
    }

    if (!empty($_FILES['vehicle_evidence']) && is_array($_FILES['vehicle_evidence']['name'])) {
      for ($i = 0; $i < count($_FILES['vehicle_evidence']['name']); $i++) {
        $err = isset($_FILES['vehicle_evidence']['error'][$i]) ? $_FILES['vehicle_evidence']['error'][$i] : UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
          error_log(sprintf('vehicle_evidence[%d] upload error=%s (%s)', $i, $err, uploadErrorText($err)));
          continue;
        }
        $orig = basename($_FILES['vehicle_evidence']['name'][$i]);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $filename = uniqid('vehicle_') . '.' . $ext;
        $target = $uploadDir . '/' . $filename;
        $tmp = isset($_FILES['vehicle_evidence']['tmp_name'][$i]) ? $_FILES['vehicle_evidence']['tmp_name'][$i] : '';
        if (move_uploaded_file($tmp, $target)) {
          $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
          $perFileStmt->execute([':rid' => $reportId, ':type' => 'vehicle_evidence', ':path' => $webPath, ':orig' => 'vehicle#' . $i . ':' . $orig]);
        } else {
          if ($tmp && file_exists($tmp)) {
            if (@rename($tmp, $target) || (@copy($tmp, $target) && @unlink($tmp))) {
              error_log(sprintf('vehicle_evidence[%d] moved via fallback rename/copy tmp=%s target=%s', $i, $tmp, $target));
              $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
              $perFileStmt->execute([':rid' => $reportId, ':type' => 'vehicle_evidence', ':path' => $webPath, ':orig' => 'vehicle#' . $i . ':' . $orig]);
            } else {
              error_log(sprintf('Failed to move (and fallback) vehicle_evidence tmp=%s target=%s is_uploaded=%s err=%s', $tmp, $target, is_uploaded_file($tmp) ? '1' : '0', isset($_FILES['vehicle_evidence']['error'][$i]) ? $_FILES['vehicle_evidence']['error'][$i] : 'n/a'));
            }
          } else {
            error_log(sprintf('Failed to move vehicle_evidence tmp missing=%s target=%s err=%s', $tmp, $target, isset($_FILES['vehicle_evidence']['error'][$i]) ? $_FILES['vehicle_evidence']['error'][$i] : 'n/a'));
          }
        }
      }
    }

    if (!empty($_FILES['item_evidence']) && is_array($_FILES['item_evidence']['name'])) {
      for ($i = 0; $i < count($_FILES['item_evidence']['name']); $i++) {
        $err = isset($_FILES['item_evidence']['error'][$i]) ? $_FILES['item_evidence']['error'][$i] : UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
          error_log(sprintf('item_evidence[%d] upload error=%s (%s)', $i, $err, uploadErrorText($err)));
          continue;
        }
        $orig = basename($_FILES['item_evidence']['name'][$i]);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $filename = uniqid('item_') . '.' . $ext;
        $target = $uploadDir . '/' . $filename;
        $tmp = isset($_FILES['item_evidence']['tmp_name'][$i]) ? $_FILES['item_evidence']['tmp_name'][$i] : '';
        if (move_uploaded_file($tmp, $target)) {
          $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
          $perFileStmt->execute([':rid' => $reportId, ':type' => 'item_evidence', ':path' => $webPath, ':orig' => 'item#' . $i . ':' . $orig]);
        } else {
          if ($tmp && file_exists($tmp)) {
            if (@rename($tmp, $target) || (@copy($tmp, $target) && @unlink($tmp))) {
              error_log(sprintf('item_evidence[%d] moved via fallback rename/copy tmp=%s target=%s', $i, $tmp, $target));
              $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
              $perFileStmt->execute([':rid' => $reportId, ':type' => 'item_evidence', ':path' => $webPath, ':orig' => 'item#' . $i . ':' . $orig]);
            } else {
              error_log(sprintf('Failed to move (and fallback) item_evidence tmp=%s target=%s is_uploaded=%s err=%s', $tmp, $target, is_uploaded_file($tmp) ? '1' : '0', isset($_FILES['item_evidence']['error'][$i]) ? $_FILES['item_evidence']['error'][$i] : 'n/a'));
            }
          } else {
            error_log(sprintf('Failed to move item_evidence tmp missing=%s target=%s err=%s', $tmp, $target, isset($_FILES['item_evidence']['error'][$i]) ? $_FILES['item_evidence']['error'][$i] : 'n/a'));
          }
        }
      }
    }

    $fStmt = $pdo->prepare('INSERT INTO spot_report_files (report_id, file_type, file_path, orig_name) VALUES (:rid, :type, :path, :orig)');

    if (!empty($_FILES['evidence_files']) && is_array($_FILES['evidence_files']['name'])) {
      for ($i = 0; $i < count($_FILES['evidence_files']['name']); $i++) {
        $err = isset($_FILES['evidence_files']['error'][$i]) ? $_FILES['evidence_files']['error'][$i] : UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
          error_log(sprintf('evidence_files[%d] upload error=%s (%s)', $i, $err, uploadErrorText($err)));
          continue;
        }
        $orig = basename($_FILES['evidence_files']['name'][$i]);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $filename = uniqid('evi_') . '.' . $ext;
        $target = $uploadDir . '/' . $filename;
        $tmp = isset($_FILES['evidence_files']['tmp_name'][$i]) ? $_FILES['evidence_files']['tmp_name'][$i] : '';
        if (move_uploaded_file($tmp, $target)) {
          $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
          $fStmt->execute([':rid' => $reportId, ':type' => 'evidence', ':path' => $webPath, ':orig' => $orig]);
        } else {
          if ($tmp && file_exists($tmp)) {
            if (@rename($tmp, $target) || (@copy($tmp, $target) && @unlink($tmp))) {
              error_log(sprintf('evidence_files[%d] moved via fallback rename/copy tmp=%s target=%s', $i, $tmp, $target));
              $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
              $fStmt->execute([':rid' => $reportId, ':type' => 'evidence', ':path' => $webPath, ':orig' => $orig]);
            } else {
              error_log(sprintf('Failed to move (and fallback) evidence_files tmp=%s target=%s is_uploaded=%s err=%s', $tmp, $target, is_uploaded_file($tmp) ? '1' : '0', isset($_FILES['evidence_files']['error'][$i]) ? $_FILES['evidence_files']['error'][$i] : 'n/a'));
            }
          } else {
            error_log(sprintf('Failed to move evidence_files tmp missing=%s target=%s err=%s', $tmp, $target, isset($_FILES['evidence_files']['error'][$i]) ? $_FILES['evidence_files']['error'][$i] : 'n/a'));
          }
        }
      }
    }

    if (!empty($_FILES['pdf_files']) && is_array($_FILES['pdf_files']['name'])) {
      for ($i = 0; $i < count($_FILES['pdf_files']['name']); $i++) {
        $err = isset($_FILES['pdf_files']['error'][$i]) ? $_FILES['pdf_files']['error'][$i] : UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
          error_log(sprintf('pdf_files[%d] upload error=%s (%s)', $i, $err, uploadErrorText($err)));
          continue;
        }
        $orig = basename($_FILES['pdf_files']['name'][$i]);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $filename = uniqid('doc_') . '.' . $ext;
        $target = $uploadDir . '/' . $filename;
        $tmp = isset($_FILES['pdf_files']['tmp_name'][$i]) ? $_FILES['pdf_files']['tmp_name'][$i] : '';
        if (move_uploaded_file($tmp, $target)) {
          $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
          $fStmt->execute([':rid' => $reportId, ':type' => 'pdf', ':path' => $webPath, ':orig' => $orig]);
        } else {
          if ($tmp && file_exists($tmp)) {
            if (@rename($tmp, $target) || (@copy($tmp, $target) && @unlink($tmp))) {
              error_log(sprintf('pdf_files[%d] moved via fallback rename/copy tmp=%s target=%s', $i, $tmp, $target));
              $webPath = '/uploads/spot_reports/' . $safeRef . '/' . $filename;
              $fStmt->execute([':rid' => $reportId, ':type' => 'pdf', ':path' => $webPath, ':orig' => $orig]);
            } else {
              error_log(sprintf('Failed to move (and fallback) pdf_files tmp=%s target=%s is_uploaded=%s err=%s', $tmp, $target, is_uploaded_file($tmp) ? '1' : '0', isset($_FILES['pdf_files']['error'][$i]) ? $_FILES['pdf_files']['error'][$i] : 'n/a'));
            }
          } else {
            error_log(sprintf('Failed to move pdf_files tmp missing=%s target=%s err=%s', $tmp, $target, isset($_FILES['pdf_files']['error'][$i]) ? $_FILES['pdf_files']['error'][$i] : 'n/a'));
          }
        }
      }
    }

      $pdo->commit();

      header('Location: view_spot_report.php?ref=' . urlencode($ref));
      exit;
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      error_log('Spot report save error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
      // Show detailed error during development. Escape output to avoid XSS.
      http_response_code(500);
      echo '<h3>An error occurred while saving the report</h3>';
      echo '<pre>' . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
      exit;
    }
  } // end if no validation errors
}

?>
<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
// Pre-compute next reference for display (GET). If DB not available, fallback to YYYY-MM-DD-0001
$nextRef = date('Y-m-d') . '-0001';
try {
  require_once dirname(__DIR__, 3) . '/config/db.php';
  $today = date('Y-m-d');
  $base = $today . '-';
  $countStmt = $pdo->prepare('SELECT COUNT(*) AS c FROM spot_reports WHERE reference_no LIKE ?');
  $countStmt->execute([$base . '%']);
  $cntRow = $countStmt->fetch(PDO::FETCH_ASSOC);
  $seq = ($cntRow && isset($cntRow['c'])) ? ((int)$cntRow['c'] + 1) : 1;
  $nextRef = $base . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
} catch (Exception $e) {
  // leave fallback
}

// ensure $old and $errors are defined for form rendering
if (!isset($old) || !is_array($old)) $old = array();
if (!isset($errors) || !is_array($errors)) $errors = array();

// choose which reference to display: freshly generated $ref (on POST) or $nextRef
$displayRef = $nextRef;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($ref) && $ref) {
  $displayRef = $ref;
}
?>

