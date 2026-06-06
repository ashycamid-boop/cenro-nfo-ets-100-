<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Equipment.php';
require_once __DIR__ . '/../../helpers/qr_url.php';

// Simple API for equipment management
// Supported actions: getAll, getById, create, update, delete

$action = isset($_GET['action']) ? $_GET['action'] : null;

$input = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if ($raw && $input === null) {
        // invalid JSON
        echo json_encode(['error' => 'Invalid JSON payload']);
        exit;
    }
}

// Helper: convert camelCase or PascalCase to snake_case
function to_snake_case($str) {
    if (!is_string($str) || $str === '') return '';
    // replace spaces and hyphens with underscore
    $str = preg_replace('/[\s\-]+/', '_', $str);
    // insert underscore before uppercase letters, then lowercase
    $snake = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '\\1_\\2', $str));
    // cleanup non-alphanumeric/_
    $snake = preg_replace('/[^a-z0-9_]/', '', $snake);
    return $snake;
}

function normalize_status_value($status) {
    $raw = strtolower(trim((string)$status));
    if ($raw === 'assigned' || $raw === 'in use') return 'In Use';
    if ($raw === 'available') return 'Available';
    if ($raw === 'returned') return 'Returned';
    if ($raw === 'under maintenance') return 'Under Maintenance';
    if ($raw === 'missing') return 'Missing';
    if ($raw === 'damaged') return 'Damaged';
    if ($raw === 'out of service') return 'Out of Service';
    return $status;
}

function requires_actual_user($status) {
    return normalize_status_value($status) === 'In Use';
}

function ensure_equipment_actual_user_history_table(PDO $pdo) {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS equipment_actual_user_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipment_id INT NOT NULL,
                previous_actual_user VARCHAR(255) NULL,
                new_actual_user VARCHAR(255) NULL,
                date_assigned DATETIME NULL,
                date_moved DATETIME NULL,
                status VARCHAR(100) NULL,
                changed_by INT NULL,
                changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_equipment_actual_user_history_equipment_id (equipment_id),
                INDEX idx_equipment_actual_user_history_changed_by (changed_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $columns = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM equipment_actual_user_history");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = true;
        }
        if (!isset($columns['date_assigned'])) {
            $pdo->exec("ALTER TABLE equipment_actual_user_history ADD COLUMN date_assigned DATETIME NULL AFTER new_actual_user");
        }
        if (!isset($columns['date_moved'])) {
            $pdo->exec("ALTER TABLE equipment_actual_user_history ADD COLUMN date_moved DATETIME NULL AFTER date_assigned");
        }
        if (!isset($columns['status'])) {
            $pdo->exec("ALTER TABLE equipment_actual_user_history ADD COLUMN status VARCHAR(100) NULL AFTER date_moved");
        }
    } catch (Exception $e) {
        error_log('ensure equipment actual user history table failed: ' . $e->getMessage());
    }
}

function normalize_history_user_value($value) {
    $value = trim((string)($value ?? ''));
    return $value === '' ? null : $value;
}

function log_actual_user_history(PDO $pdo, $equipmentId, $previousActualUser, $newActualUser, $status = null) {
    $equipmentId = (int)$equipmentId;
    if ($equipmentId <= 0) return;

    $previousActualUser = normalize_history_user_value($previousActualUser);
    $newActualUser = normalize_history_user_value($newActualUser);
    if ((string)$previousActualUser === (string)$newActualUser) return;
    $status = normalize_history_user_value($status);
    $dateAssigned = ($previousActualUser === null && $newActualUser !== null) ? date('Y-m-d H:i:s') : null;
    $dateMoved = ($previousActualUser !== null) ? date('Y-m-d H:i:s') : null;

    try {
        ensure_equipment_actual_user_history_table($pdo);
        $changedBy = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
        $stmt = $pdo->prepare("
            INSERT INTO equipment_actual_user_history
                (equipment_id, previous_actual_user, new_actual_user, date_assigned, date_moved, status, changed_by)
            VALUES
                (:equipment_id, :previous_actual_user, :new_actual_user, :date_assigned, :date_moved, :status, :changed_by)
        ");
        $stmt->bindValue(':equipment_id', $equipmentId, PDO::PARAM_INT);
        $stmt->bindValue(':previous_actual_user', $previousActualUser);
        $stmt->bindValue(':new_actual_user', $newActualUser);
        $stmt->bindValue(':date_assigned', $dateAssigned);
        $stmt->bindValue(':date_moved', $dateMoved);
        $stmt->bindValue(':status', $status);
        if ($changedBy) {
            $stmt->bindValue(':changed_by', $changedBy, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':changed_by', null, PDO::PARAM_NULL);
        }
        $stmt->execute();
    } catch (Exception $e) {
        error_log('log equipment actual user history failed: ' . $e->getMessage());
    }
}

function resolve_user_display_names(PDO $pdo, array $values) {
    $ids = [];
    foreach ($values as $value) {
        if ($value !== null && $value !== '' && is_numeric($value)) {
            $ids[] = (int)$value;
        }
    }

    $map = [];
    if (!count($ids)) return $map;

    try {
        $ids = array_values(array_unique($ids));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id IN ($placeholders)");
        foreach ($ids as $i => $id) $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $user) {
            $map[(string)(int)$user['id']] = $user['full_name'];
        }
    } catch (Exception $e) {
        error_log('resolve user display names failed: ' . $e->getMessage());
    }

    return $map;
}

// Improved history logger which closes any existing open assignment
function log_actual_user_history_v2(PDO $pdo, $equipmentId, $previousActualUser, $newActualUser, $status = null) {
    $equipmentId = (int)$equipmentId;
    if ($equipmentId <= 0) return;

    $previousActualUser = normalize_history_user_value($previousActualUser);
    $newActualUser = normalize_history_user_value($newActualUser);
    if ((string)$previousActualUser === (string)$newActualUser) return;
    $status = normalize_history_user_value($status);
    $now = date('Y-m-d H:i:s');

    try {
        ensure_equipment_actual_user_history_table($pdo);
        $changedBy = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;

        try {
            $pdo->beginTransaction();

            // Close any open assignment rows for this equipment
            $closeStmt = $pdo->prepare("UPDATE equipment_actual_user_history
                SET date_moved = :date_moved,
                    status = :transferred
                WHERE equipment_id = :equipment_id
                  AND date_moved IS NULL
                  AND new_actual_user IS NOT NULL");
            $transferredLabel = 'Transferred';
            $closeStmt->bindValue(':date_moved', $now);
            $closeStmt->bindValue(':transferred', $transferredLabel);
            $closeStmt->bindValue(':equipment_id', $equipmentId, PDO::PARAM_INT);
            $closeStmt->execute();

            // Only insert a new history record when there is a new actual user.
            // For unassignment (newActualUser === null) we only close the previous row.
            if ($newActualUser !== null) {
                $dateAssigned = $now;
                $stmt = $pdo->prepare("INSERT INTO equipment_actual_user_history
                    (equipment_id, previous_actual_user, new_actual_user, date_assigned, date_moved, status, changed_by)
                    VALUES
                    (:equipment_id, :previous_actual_user, :new_actual_user, :date_assigned, :date_moved, :status, :changed_by)");
                $stmt->bindValue(':equipment_id', $equipmentId, PDO::PARAM_INT);
                $stmt->bindValue(':previous_actual_user', $previousActualUser);
                $stmt->bindValue(':new_actual_user', $newActualUser);
                $stmt->bindValue(':date_assigned', $dateAssigned);
                $stmt->bindValue(':date_moved', null, PDO::PARAM_NULL);
                $stmt->bindValue(':status', $status);
                if ($changedBy) {
                    $stmt->bindValue(':changed_by', $changedBy, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':changed_by', null, PDO::PARAM_NULL);
                }
                $stmt->execute();
            }

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        error_log('log_actual_user_history_v2 failed: ' . $e->getMessage());
    }
}

function user_display_value($value, array $map) {
    $value = normalize_history_user_value($value);
    if ($value === null) return 'Unassigned';
    return (is_numeric($value) && isset($map[(string)(int)$value])) ? $map[(string)(int)$value] : $value;
}

function ensure_equipment_status_column_supports_missing(PDO $pdo) {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM equipment LIKE 'status'");
        $column = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        $type = isset($column['Type']) ? (string)$column['Type'] : '';
        if (stripos($type, 'enum(') !== 0) return;

        preg_match_all("/'((?:''|[^'])*)'/", $type, $matches);
        $values = array_map(function($value) {
            return str_replace("''", "'", $value);
        }, $matches[1] ?? []);

        $hasMissing = false;
        foreach ($values as $value) {
            if (strcasecmp($value, 'Missing') === 0) {
                $hasMissing = true;
                break;
            }
        }
        if ($hasMissing) return;

        $insertAfter = array_search('Under Maintenance', $values, true);
        if ($insertAfter === false) {
            $values[] = 'Missing';
        } else {
            array_splice($values, $insertAfter + 1, 0, ['Missing']);
        }

        $enumSql = 'ENUM(' . implode(',', array_map([$pdo, 'quote'], $values)) . ')';
        $nullSql = (isset($column['Null']) && strtoupper((string)$column['Null']) === 'YES') ? 'NULL' : 'NOT NULL';
        $defaultSql = array_key_exists('Default', $column) && $column['Default'] !== null
            ? ' DEFAULT ' . $pdo->quote((string)$column['Default'])
            : '';

        $pdo->exec("ALTER TABLE equipment MODIFY status {$enumSql} {$nullSql}{$defaultSql}");
    } catch (Exception $e) {
        error_log('ensure equipment status enum supports Missing failed: ' . $e->getMessage());
    }
}

function equipment_matches_search(array $row, $search) {
    $needle = strtolower(trim((string)$search));
    if ($needle === '') return true;
    $numericNeedle = preg_replace('/\D+/', '', $needle);

    $statusRaw = isset($row['status']) ? (string)$row['status'] : '';
    $statusNormalized = normalize_status_value($statusRaw);
    $statusDisplay = strtolower($statusNormalized) === 'in use' ? 'Assigned' : $statusNormalized;
    $assetId = $row['asset_id'] ?? ($row['id'] ?? '');
    $assetIdNumber = preg_replace('/\D+/', '', (string)$assetId);
    $assetIdVariants = [$assetId];
    if ($assetIdNumber !== '') {
        $assetIdVariants[] = (string)((int)$assetIdNumber);
        $assetIdVariants[] = str_pad((string)((int)$assetIdNumber), 3, '0', STR_PAD_LEFT);
        $assetIdVariants[] = str_pad((string)((int)$assetIdNumber), 4, '0', STR_PAD_LEFT);
        $assetIdVariants[] = str_pad((string)((int)$assetIdNumber), 5, '0', STR_PAD_LEFT);
        $assetIdVariants[] = str_pad((string)((int)$assetIdNumber), 6, '0', STR_PAD_LEFT);
    }

    $haystacks = [
        ...$assetIdVariants,
        $row['property_number'] ?? '',
        $row['equipment_type'] ?? '',
        $row['brand'] ?? '',
        $row['actual_user'] ?? '',
        $row['accountable_person'] ?? '',
        $row['year_acquired'] ?? '',
        $statusRaw,
        $statusNormalized,
        $statusDisplay,
    ];

    foreach ($haystacks as $value) {
        if ($value !== null && stripos((string)$value, $needle) !== false) {
            return true;
        }
    }

    if ($numericNeedle !== '' && $assetIdNumber !== '' && (int)$numericNeedle === (int)$assetIdNumber) {
        return true;
    }

    return false;
}

$equipment = new Equipment($pdo);
ensure_equipment_status_column_supports_missing($pdo);
ensure_equipment_actual_user_history_table($pdo);

// Helper: generate QR image (uses api.qrserver.com) that points to public QR view page
function generate_qr_for_equipment($pdo, $equipmentId) {
    $equipmentId = (int)$equipmentId;
    if ($equipmentId <= 0) return false;
//Session expired or server returned an authentication page. You will be redirected to the login page.
    $publicLink = cenro_project_url('public/qr_view.php?id=' . urlencode($equipmentId));

    // Call external QR API to get PNG
    $qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($publicLink);

    // prepare upload directory
    $uploadDir = __DIR__ . '/../../../public/uploads/qr';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    $filePath = $uploadDir . '/eq-' . $equipmentId . '.png';

    // fetch image (try file_get_contents, fallback to cURL)
    $img = false;
    if (ini_get('allow_url_fopen')) {
        $img = @file_get_contents($qrApi);
    }
    if ($img === false) {
        // try cURL
        if (function_exists('curl_init')) {
            $ch = curl_init($qrApi);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $img = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode < 200 || $httpCode >= 300) $img = false;
        }
    }
    if ($img === false) return false;

    $saved = @file_put_contents($filePath, $img);
    if ($saved === false) return false;

    // save relative path to DB
    $relative = 'uploads/qr/eq-' . $equipmentId . '.png';
    try {
        $stmt = $pdo->prepare('UPDATE equipment SET qr_code_path = :p WHERE id = :id');
        $stmt->bindParam(':p', $relative);
        $stmt->bindParam(':id', $equipmentId);
        $stmt->execute();
    } catch (Exception $e) {
        // ignore DB update errors
    }

    return $relative;
}

function BASE_url_safe($u) {
    // ensure no trailing slash
    return rtrim($u, '/');
}
//generate qer code for equipment
try {
    switch ($action) {
        //invalid regenerate qr code for equipment
        case 'generateQR':
            // Accept id via GET or POST JSON
            $id = 0;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['id'])) $id = (int)$input['id'];
            if (!$id && isset($_GET['id'])) $id = (int)$_GET['id'];
            if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid id']); exit; }

            $res = generate_qr_for_equipment($pdo, $id);
            if ($res) {
                echo json_encode(['success' => true, 'qr_path' => $res]);
            } else {
                echo json_encode(['success' => false, 'error' => 'QR generation failed']);
            }
            break;
        case 'getAll':
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : 'All';
//fetch equipmeny List
            $stmt = $pdo->prepare('SELECT * FROM equipment ORDER BY id DESC');
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Optionally filter by status on server side
            if ($status !== 'All') {
                $rows = array_values(array_filter($rows, function($r) use ($status) {
                    return isset($r['status']) && strtolower($r['status']) === strtolower($status);
                }));
            }

            // Map user IDs to names for accountable_person and actual_user
            $userIds = [];
            foreach ($rows as $r) {
                if (isset($r['accountable_person']) && is_numeric($r['accountable_person'])) $userIds[] = (int)$r['accountable_person'];
                if (isset($r['actual_user']) && is_numeric($r['actual_user'])) $userIds[] = (int)$r['actual_user'];
            }
            $userMap = [];
            if (count($userIds)) {
                $userIds = array_values(array_unique($userIds));
                // Build placeholders
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $ustmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id IN ($placeholders)");
                foreach ($userIds as $i => $uid) $ustmt->bindValue($i+1, $uid, PDO::PARAM_INT);
                $ustmt->execute();
                $users = $ustmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($users as $u) {
                    $userMap[(int)$u['id']] = $u['full_name'];
                }
            }

            // Apply mapping and expose *_id fields
            foreach ($rows as &$r) {
                if (isset($r['accountable_person']) && is_numeric($r['accountable_person'])) {
                    $id = (int)$r['accountable_person'];
                    $r['accountable_person_id'] = $id;
                    $r['accountable_person'] = isset($userMap[$id]) ? $userMap[$id] : $r['accountable_person'];
                }
                if (isset($r['actual_user']) && is_numeric($r['actual_user'])) {
                    $id = (int)$r['actual_user'];
                    $r['actual_user_id'] = $id;
                    $r['actual_user'] = isset($userMap[$id]) ? $userMap[$id] : $r['actual_user'];
                }
            }
            unset($r);

            if ($search !== '') {
                $rows = array_values(array_filter($rows, function($r) use ($search) {
                    return equipment_matches_search($r, $search);
                }));
            }

            echo json_encode($rows);
            break;

        case 'getById':
        case 'read_one':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) { echo json_encode(['error' => 'Invalid id']); exit; }
            $equipment->id = $id;
            if ($equipment->readOne()) {
                // convert object properties to array
                $data = [];
                foreach (get_object_vars($equipment) as $k => $v) {
                    if ($k === 'conn' || $k === 'table_name') continue;
                    $data[$k] = $v;
                }

                // If accountable_person/actual_user are numeric IDs, resolve to names
                $toResolve = [];
                if (isset($data['accountable_person']) && is_numeric($data['accountable_person'])) $toResolve[] = (int)$data['accountable_person'];
                if (isset($data['actual_user']) && is_numeric($data['actual_user'])) $toResolve[] = (int)$data['actual_user'];
                if (count($toResolve)) {
                    $toResolve = array_values(array_unique($toResolve));
                    $placeholders = implode(',', array_fill(0, count($toResolve), '?'));
                    $ust = $pdo->prepare("SELECT id, full_name FROM users WHERE id IN ($placeholders)");
                    foreach ($toResolve as $i => $uid) $ust->bindValue($i+1, $uid, PDO::PARAM_INT);
                    $ust->execute();
                    $users = $ust->fetchAll(PDO::FETCH_ASSOC);
                    $map = [];
                    foreach ($users as $u) $map[(int)$u['id']] = $u['full_name'];

                    if (isset($data['accountable_person']) && is_numeric($data['accountable_person'])) {
                        $id = (int)$data['accountable_person'];
                        $data['accountable_person_id'] = $id;
                        $data['accountable_person'] = isset($map[$id]) ? $map[$id] : $data['accountable_person'];
                    }
                    if (isset($data['actual_user']) && is_numeric($data['actual_user'])) {
                        $id = (int)$data['actual_user'];
                        $data['actual_user_id'] = $id;
                        $data['actual_user'] = isset($map[$id]) ? $map[$id] : $data['actual_user'];
                    }
                }

                echo json_encode($data);
            } else {
                echo json_encode(['error' => 'Equipment not found']);
            }
            break;

        case 'getActualUserHistory':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) { echo json_encode(['error' => 'Invalid id']); exit; }

            try {
                $stmt = $pdo->prepare("
                    SELECT h.id,
                           h.equipment_id,
                           h.previous_actual_user,
                           h.new_actual_user,
                           h.date_assigned,
                           h.date_moved,
                           h.status,
                           h.changed_by,
                           h.changed_at,
                           u.full_name AS changed_by_name,
                           e.status AS current_status
                    FROM equipment_actual_user_history h
                    LEFT JOIN users u ON u.id = h.changed_by
                    LEFT JOIN equipment e ON e.id = h.equipment_id
                    WHERE h.equipment_id = :equipment_id
                    ORDER BY h.changed_at DESC, h.id DESC
                ");
                $stmt->bindValue(':equipment_id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!count($rows)) {
                    $currentStmt = $pdo->prepare("
                        SELECT actual_user,
                               status,
                               created_by,
                               created_at
                        FROM equipment
                        WHERE id = :id
                        LIMIT 1
                    ");
                    $currentStmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $currentStmt->execute();
                    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

                    if ($current && normalize_history_user_value($current['actual_user'] ?? null) !== null) {
                        $changedBy = isset($current['created_by']) && is_numeric($current['created_by'])
                            ? (int)$current['created_by']
                            : null;
                        $changedByName = null;
                        if ($changedBy) {
                            $userStmt = $pdo->prepare('SELECT full_name FROM users WHERE id = :id LIMIT 1');
                            $userStmt->bindValue(':id', $changedBy, PDO::PARAM_INT);
                            $userStmt->execute();
                            $changedByName = $userStmt->fetchColumn() ?: null;
                        }

                        $rows[] = [
                            'id' => null,
                            'equipment_id' => $id,
                            'previous_actual_user' => null,
                            'new_actual_user' => $current['actual_user'],
                            'date_assigned' => $current['created_at'] ?? null,
                            'date_moved' => null,
                            'status' => $current['status'] ?? null,
                            'changed_by' => $changedBy,
                            'changed_at' => $current['created_at'] ?? null,
                            'changed_by_name' => $changedByName
                        ];
                    }
                }

                $values = [];
                foreach ($rows as $row) {
                    $values[] = $row['previous_actual_user'] ?? null;
                    $values[] = $row['new_actual_user'] ?? null;
                }
                $userMap = resolve_user_display_names($pdo, $values);

                foreach ($rows as &$row) {
                    if (empty($row['date_assigned']) && empty($row['date_moved']) && !empty($row['changed_at'])) {
                        if (normalize_history_user_value($row['previous_actual_user'] ?? null) === null) {
                            $row['date_assigned'] = $row['changed_at'];
                        } else {
                            $row['date_moved'] = $row['changed_at'];
                        }
                    }
                    // If the history row has no explicit status, prefer 'Transferred' for closed rows,
                    // otherwise fall back to the equipment's current status for active rows.
                    if (empty($row['status'])) {
                        if (!empty($row['date_moved'])) {
                            $row['status'] = 'Transferred';
                        } elseif (!empty($row['current_status'])) {
                            $row['status'] = $row['current_status'];
                        }
                    }
                    $row['previous_actual_user_display'] = user_display_value($row['previous_actual_user'] ?? null, $userMap);
                    $row['new_actual_user_display'] = user_display_value($row['new_actual_user'] ?? null, $userMap);
                    $row['changed_by_display'] = $row['changed_by_name'] ?: 'System';
                }
                unset($row);

                echo json_encode(['success' => true, 'data' => $rows]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to load actual user history']);
            }
            break;

        case 'create':
            if (!$input) { echo json_encode(['error' => 'Missing payload']); exit; }

            // Map incoming fields (convert camelCase -> snake_case)
            foreach ($input as $k => $v) {
                $prop = to_snake_case($k);
                if (property_exists($equipment, $prop)) {
                    $equipment->$prop = $v;
                }
            }

            // default status
            if (empty($equipment->status)) $equipment->status = 'Available';
            $equipment->status = normalize_status_value($equipment->status);
            $equipment->actual_user = trim((string)($equipment->actual_user ?? ''));

            // Validate required fields
            if (empty($equipment->property_number)) {
                echo json_encode(['error' => "Validation failed: property_number is required"]);
                exit;
            }
            if (requires_actual_user($equipment->status) && $equipment->actual_user === '') {
                echo json_encode(['error' => "Validation failed: actual_user is required when status is Assigned"]);
                exit;
            }

            $duplicateStmt = $pdo->prepare('SELECT id FROM equipment WHERE property_number = :property_number LIMIT 1');
            $duplicateStmt->bindValue(':property_number', $equipment->property_number);
            $duplicateStmt->execute();
            $existingId = (int)($duplicateStmt->fetchColumn() ?: 0);
            if ($existingId > 0) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Duplicate property number. This equipment already exists.',
                    'duplicate_id' => $existingId
                ]);
                exit;
            }

            if ($equipment->create()) {
                $id = (int)$pdo->lastInsertId();
                // Fallback when DB schema is misconfigured and lastInsertId() returns 0.
                if ($id <= 0) {
                    try {
                        $fallback = $pdo->prepare("
                            SELECT id
                            FROM equipment
                            WHERE property_number = :property_number
                            ORDER BY created_at DESC, id DESC
                            LIMIT 1
                        ");
                        $fallback->bindValue(':property_number', $equipment->property_number);
                        $fallback->execute();
                        $id = (int)($fallback->fetchColumn() ?: 0);
                    } catch (Exception $e) {
                        $id = 0;
                    }
                }
                if ($id <= 0) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Created equipment but failed to resolve a valid ID. Please fix equipment.id AUTO_INCREMENT schema.'
                    ]);
                    exit;
                }

                // try generate QR and update path (best-effort)
                try { generate_qr_for_equipment($pdo, $id); } catch (Exception $e) {}
                log_actual_user_history_v2($pdo, $id, null, $equipment->actual_user, $equipment->status ?? null);

                echo json_encode(['success' => true, 'id' => $id]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create equipment']);
            }
            break;

        case 'update':
            if (!$input) { echo json_encode(['error' => 'Missing payload']); exit; }
            $id = isset($input['id']) ? (int)$input['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
            if (!$id) { echo json_encode(['error' => 'Invalid id']); exit; }

            $equipment->id = $id;
            $previousActualUser = null;
            $previousStatus = null;
            try {
                $prevStmt = $pdo->prepare('SELECT actual_user, status FROM equipment WHERE id = :id LIMIT 1');
                $prevStmt->bindValue(':id', $id, PDO::PARAM_INT);
                $prevStmt->execute();
                $previousRow = $prevStmt->fetch(PDO::FETCH_ASSOC);
                $previousActualUser = $previousRow['actual_user'] ?? null;
                $previousStatus = $previousRow['status'] ?? null;
            } catch (Exception $e) {
                $previousActualUser = null;
                $previousStatus = null;
            }

            // assign fields (convert camelCase -> snake_case)
            foreach ($input as $k => $v) {
                if ($k === 'id') continue;
                $prop = to_snake_case($k);
                if (property_exists($equipment, $prop)) {
                    $equipment->$prop = $v;
                }
            }

            // simple validation
            if (isset($equipment->property_number) && $equipment->property_number === '') {
                echo json_encode(['error' => "Validation failed: property_number cannot be empty"]);
                exit;
            }
            if (isset($equipment->status)) {
                $equipment->status = normalize_status_value($equipment->status);
            }
            if (isset($equipment->actual_user)) {
                $equipment->actual_user = trim((string)$equipment->actual_user);
            }
            if (requires_actual_user($equipment->status ?? '') && (($equipment->actual_user ?? '') === '')) {
                echo json_encode(['error' => "Validation failed: actual_user is required when status is Assigned"]);
                exit;
            }

            // Debugging: log incoming input and mapped equipment properties (append-only)
            try {
                $dbgPath = __DIR__ . '/../../../storage/logs/equipment_update_debug.log';
                $dbg = "[" . date('Y-m-d H:i:s') . "] update request for id={$id}\n";
                $dbg .= "INPUT: " . json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
                $mapped = [];
                foreach (get_object_vars($equipment) as $k => $v) {
                    if ($k === 'conn' || $k === 'table_name') continue;
                    $mapped[$k] = $v;
                }
                $dbg .= "MAPPED: " . json_encode($mapped, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
                @file_put_contents($dbgPath, $dbg, FILE_APPEND | LOCK_EX);
            } catch (Exception $e) {
                // ignore logging failures
            }

            if ($equipment->update()) {
                // regenerate QR if property_number changed or no qr exists
                try { generate_qr_for_equipment($pdo, $id); } catch (Exception $e) {}
                log_actual_user_history_v2($pdo, $id, $previousActualUser, $equipment->actual_user ?? null, $equipment->status ?? $previousStatus);

                // Return mapped properties so client can verify what was saved
                $after = [];
                try {
                    $equipment->readOne();
                    foreach (get_object_vars($equipment) as $k => $v) {
                        if ($k === 'conn' || $k === 'table_name') continue;
                        $after[$k] = $v;
                    }
                } catch (Exception $e) {}

                echo json_encode(['success' => true, 'id' => $id, 'saved' => $after]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update equipment']);
            }
            break;

        case 'delete':
            // accept POST JSON or GET id
            $id = 0;
            if ($input && isset($input['id'])) $id = (int)$input['id'];
            if (!$id && isset($_GET['id'])) $id = (int)$_GET['id'];
            if (!$id) { echo json_encode(['error' => 'Invalid id']); exit; }

            $equipment->id = $id;
            if ($equipment->delete()) {
                echo json_encode(['success' => true, 'deleted' => $id]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete equipment']);
            }
            break;

        case 'getUsers':
            // Return active users for dropdowns; support schemas with/without `sex` column.
            try {
                $hasSexColumn = false;
                try {
                    $colStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'sex'");
                    $hasSexColumn = (bool)$colStmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $hasSexColumn = false;
                }

                $sql = $hasSexColumn
                    ? "SELECT id, full_name, sex FROM users WHERE status = 1 ORDER BY full_name ASC"
                    : "SELECT id, full_name, '' AS sex FROM users WHERE status = 1 ORDER BY full_name ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $users, 'users' => $users]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

?>
