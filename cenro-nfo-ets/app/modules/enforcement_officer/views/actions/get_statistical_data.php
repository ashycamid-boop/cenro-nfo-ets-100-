<?php
declare(strict_types=1);
header('Content-Type: application/json');

try {
  $dbPath = dirname(__DIR__, 4) . '/config/db.php';
  if (!file_exists($dbPath)) throw new Exception('db.php not found');
  require_once $dbPath; // provides $pdo
  if (!isset($pdo)) throw new Exception('PDO not available');

  $from = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
  $to = isset($_GET['to']) ? trim((string)$_GET['to']) : '';
  $granularity = isset($_GET['granularity']) ? trim((string)$_GET['granularity']) : 'monthly';
  if (!in_array($granularity, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'], true)) {
    $granularity = 'monthly';
  }
  $monthPattern = '/^\d{4}-(0[1-9]|1[0-2])$/';
  $datePattern = '/^\d{4}-(0[1-9]|1[0-2])-\d{2}$/';
  $hasExplicitRange = ($from !== '' || $to !== '');
  $fromDate = null;
  $toDate = null;

  // Fallback for callers without an explicit range:
  // 1. honor an older `months` parameter when present
  // 2. otherwise load all available records by default
  if ($from !== '' && $to !== '' && preg_match($datePattern, $from) && preg_match($datePattern, $to)) {
    $fromDate = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
    $toDate = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
  } elseif ($from !== '' && $to !== '' && preg_match($monthPattern, $from) && preg_match($monthPattern, $to)) {
    $fromMonth = DateTimeImmutable::createFromFormat('!Y-m', $from);
    $toMonth = DateTimeImmutable::createFromFormat('!Y-m', $to);
    $fromDate = $fromMonth ? $fromMonth : null;
    $toDate = $toMonth ? $toMonth->modify('last day of this month') : null;
  } elseif ($hasExplicitRange) {
    throw new Exception('Invalid date range format');
  } else {
    $monthsParam = isset($_GET['months']) ? (int)$_GET['months'] : 0;
    if ($monthsParam > 0) {
      $months = min($monthsParam, 240);
      $startTs = strtotime(date('Y-m-01') . ' -' . ($months - 1) . ' months');
      $fromDate = new DateTimeImmutable(date('Y-m-01', $startTs));
      $toDate = (new DateTimeImmutable('today'))->modify('last day of this month');
    } else {
      $minDates = [];

      $queries = [
        "SELECT MIN(COALESCE(incident_datetime, created_at)) FROM spot_reports",
        "SELECT MIN(created_at) FROM service_requests",
        "SELECT MIN(created_at) FROM equipment"
      ];

      foreach ($queries as $sql) {
        $value = $pdo->query($sql)->fetchColumn();
        if (!empty($value)) {
          $minDates[] = $value;
        }
      }

      if (!empty($minDates)) {
        sort($minDates);
        $fromDate = new DateTimeImmutable(date('Y-m-d', strtotime((string)$minDates[0])));
      } else {
        $fromDate = new DateTimeImmutable('today');
      }

      $toDate = new DateTimeImmutable('today');
    }
  }

  if (!$fromDate || !$toDate) throw new Exception('Invalid date range format');
  if ($fromDate > $toDate) throw new Exception('From date cannot be later than To date');

  $periodGranularity = in_array($granularity, ['daily', 'weekly'], true) ? $granularity : 'monthly';
  $labels = [];
  if ($periodGranularity === 'daily') {
    $cursor = $fromDate;
    while ($cursor <= $toDate) {
      $labels[] = $cursor->format('Y-m-d');
      $cursor = $cursor->modify('+1 day');
    }
  } elseif ($periodGranularity === 'weekly') {
    $cursor = $fromDate->modify('monday this week');
    while ($cursor <= $toDate) {
      $labels[] = $cursor->format('Y-m-d');
      $cursor = $cursor->modify('+1 week');
    }
  } else {
    $cursor = $fromDate->modify('first day of this month');
    $lastMonth = $toDate->modify('first day of this month');
    while ($cursor <= $lastMonth) {
      $labels[] = $cursor->format('Y-m');
      $cursor = $cursor->modify('+1 month');
    }
  }
  $maxLabels = $periodGranularity === 'daily' ? 800 : ($periodGranularity === 'weekly' ? 260 : 240);
  if (count($labels) > $maxLabels) throw new Exception('Date range too large for the selected granularity.');

  $startDate = $fromDate->format('Y-m-d 00:00:00');
  $endDateExclusive = $toDate->modify('+1 day')->format('Y-m-d 00:00:00');

  $periodExpr = function(string $column) use ($periodGranularity): string {
    if ($periodGranularity === 'daily') {
      return "DATE_FORMAT({$column}, '%Y-%m-%d')";
    }
    if ($periodGranularity === 'weekly') {
      return "DATE_FORMAT(DATE_SUB(DATE({$column}), INTERVAL WEEKDAY({$column}) DAY), '%Y-%m-%d')";
    }
    return "DATE_FORMAT({$column}, '%Y-%m')";
  };
  $spotPeriod = $periodExpr('COALESCE(incident_datetime, created_at)');
  $reportPeriod = $periodExpr('COALESCE(r.incident_datetime, r.created_at)');
  $servicePeriod = $periodExpr('created_at');
  $equipmentPeriod = $periodExpr('created_at');

  // Session may hold current user id/email; attempt to load it for per-user counts
  if (session_status() === PHP_SESSION_NONE) {
    @session_start();
  }
  $sessionUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
  $sessionUserEmail = $_SESSION['email'] ?? null;

  // Helper to zero-fill series
  $fill = function(array $rows) use ($labels) {
    $map = [];
    foreach ($rows as $r) $map[$r['ym']] = (int)$r['cnt'];
    $out = [];
    foreach ($labels as $l) $out[] = $map[$l] ?? 0;
    return $out;
  };

  // Spot reports time series (by selected period; incident date falls back to created_at)
  $stmt = $pdo->prepare("SELECT {$spotPeriod} AS ym, COUNT(*) AS cnt FROM spot_reports WHERE COALESCE(incident_datetime, created_at) >= ? AND COALESCE(incident_datetime, created_at) < ? GROUP BY ym ORDER BY ym ASC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $spotRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $spotSeries = $fill($spotRows);

  // Cases (approved spot_reports) - exclude reports whose case_status is 'resolved' (closed)
  $stmt = $pdo->prepare("SELECT {$spotPeriod} AS ym, COUNT(*) AS cnt FROM spot_reports WHERE LOWER(TRIM(COALESCE(status,''))) = 'approved' AND LOWER(TRIM(COALESCE(case_status,''))) != 'resolved' AND COALESCE(incident_datetime, created_at) >= ? AND COALESCE(incident_datetime, created_at) < ? GROUP BY ym ORDER BY ym ASC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $caseRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $caseSeries = $fill($caseRows);

  // Apprehended: persons/vehicles/items (counted by parent report created_at for approved reports)
  $stmt = $pdo->prepare("SELECT {$reportPeriod} AS ym, COUNT(*) AS cnt FROM spot_report_persons p JOIN spot_reports r ON r.id = p.report_id WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'approved' AND COALESCE(r.incident_datetime, r.created_at) >= ? AND COALESCE(r.incident_datetime, r.created_at) < ? GROUP BY ym ORDER BY ym ASC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $personRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $personsSeries = $fill($personRows);

  $stmt = $pdo->prepare("SELECT {$reportPeriod} AS ym, COUNT(*) AS cnt FROM spot_report_vehicles v JOIN spot_reports r ON r.id = v.report_id WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'approved' AND COALESCE(r.incident_datetime, r.created_at) >= ? AND COALESCE(r.incident_datetime, r.created_at) < ? GROUP BY ym ORDER BY ym ASC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $vehRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $vehiclesSeries = $fill($vehRows);

  $stmt = $pdo->prepare("SELECT {$reportPeriod} AS ym, COUNT(*) AS cnt FROM spot_report_items i JOIN spot_reports r ON r.id = i.report_id WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'approved' AND COALESCE(r.incident_datetime, r.created_at) >= ? AND COALESCE(r.incident_datetime, r.created_at) < ? GROUP BY ym ORDER BY ym ASC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $itemRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $itemsSeries = $fill($itemRows);

  // Breakdown: persons by role
  $stmt = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(p.role),''),'Unknown') AS role, COUNT(*) AS cnt FROM spot_report_persons p JOIN spot_reports r ON r.id = p.report_id WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'approved' AND COALESCE(r.incident_datetime, r.created_at) >= ? AND COALESCE(r.incident_datetime, r.created_at) < ? GROUP BY role ORDER BY cnt DESC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $rolesBy = [];
  foreach ($rows as $r) { $rolesBy[$r['role']] = (int)$r['cnt']; }

  // Breakdown: persons by gender
  $stmt = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(p.gender),''),'Unknown') AS gender, COUNT(*) AS cnt FROM spot_report_persons p JOIN spot_reports r ON r.id = p.report_id WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'approved' AND COALESCE(r.incident_datetime, r.created_at) >= ? AND COALESCE(r.incident_datetime, r.created_at) < ? GROUP BY gender ORDER BY cnt DESC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $genderBy = [];
  foreach ($rows as $r) { $genderBy[$r['gender']] = (int)$r['cnt']; }

  // Vehicles by make (no explicit status column on vehicles table)
  $stmt = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(v.make),''),'Unknown') AS make, COUNT(*) AS cnt FROM spot_report_vehicles v JOIN spot_reports r ON r.id = v.report_id WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'approved' AND COALESCE(r.incident_datetime, r.created_at) >= ? AND COALESCE(r.incident_datetime, r.created_at) < ? GROUP BY make ORDER BY cnt DESC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $vehiclesByMake = [];
  foreach ($rows as $r) { $vehiclesByMake[$r['make']] = (int)$r['cnt']; }

  // Items by type
  $stmt = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(i.type),''),'Unknown') AS type, COUNT(*) AS cnt FROM spot_report_items i JOIN spot_reports r ON r.id = i.report_id WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'approved' AND COALESCE(r.incident_datetime, r.created_at) >= ? AND COALESCE(r.incident_datetime, r.created_at) < ? GROUP BY type ORDER BY cnt DESC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $itemsByType = [];
  foreach ($rows as $r) { $itemsByType[$r['type']] = (int)$r['cnt']; }

  // Spot reports by location
  $stmt = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(location),''),'Unknown') AS location_name, COUNT(*) AS cnt FROM spot_reports WHERE COALESCE(incident_datetime, created_at) >= ? AND COALESCE(incident_datetime, created_at) < ? GROUP BY location_name ORDER BY cnt DESC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $locationsBy = [];
  foreach ($rows as $r) { $locationsBy[$r['location_name']] = (int)$r['cnt']; }

  // Spot by status
  $stmt = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(status,''))) AS status, COUNT(*) AS cnt FROM spot_reports WHERE COALESCE(incident_datetime, created_at) >= ? AND COALESCE(incident_datetime, created_at) < ? GROUP BY LOWER(TRIM(COALESCE(status,'')))");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $spotBy = [];
  foreach ($rows as $r) { $k = $r['status'] !== '' ? $r['status'] : 'unknown'; $spotBy[$k] = (int)$r['cnt']; }

  // Case statuses (case_status on approved reports)
  $stmt = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(case_status,''))) AS status, COUNT(*) AS cnt FROM spot_reports WHERE LOWER(TRIM(COALESCE(status,''))) = 'approved' AND COALESCE(incident_datetime, created_at) >= ? AND COALESCE(incident_datetime, created_at) < ? GROUP BY LOWER(TRIM(COALESCE(case_status,'')))");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $caseBy = [];
  foreach ($rows as $r) { $k = $r['status'] !== '' ? $r['status'] : 'unknown'; $caseBy[$k] = (int)$r['cnt']; }

  // Service requests by status
  $stmt = $pdo->prepare("SELECT {$servicePeriod} AS ym, COUNT(*) AS cnt FROM service_requests WHERE created_at >= ? AND created_at < ? GROUP BY ym ORDER BY ym ASC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $svcRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $svcSeries = $fill($svcRows);

  $stmt = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(status,''))) AS status, COUNT(*) AS cnt FROM service_requests WHERE created_at >= ? AND created_at < ? GROUP BY LOWER(TRIM(COALESCE(status,'')))");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $svcBy = [];
  foreach ($rows as $r) { $k = $r['status'] !== '' ? $r['status'] : 'unknown'; $svcBy[$k] = (int)$r['cnt']; }

  // Per-user service request counts (if session user id or email available)
  $svcByUser = [];
  // initialize svcByUser with overall status keys (defaults to 0) so frontend can rely on it
  foreach ($svcBy as $k => $v) { $svcByUser[$k] = 0; }
  if (!empty($sessionUserId) || !empty($sessionUserEmail)) {
    if (!empty($sessionUserId)) {
      $stmt = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(status,''))) AS status, COUNT(*) AS cnt FROM service_requests WHERE created_by = ? AND created_at >= ? AND created_at < ? GROUP BY LOWER(TRIM(COALESCE(status,'')))");
      $stmt->execute([$sessionUserId, $startDate, $endDateExclusive]);
    } else {
      $stmt = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(status,''))) AS status, COUNT(*) AS cnt FROM service_requests WHERE LOWER(TRIM(COALESCE(requester_email,''))) = LOWER(TRIM(?)) AND created_at >= ? AND created_at < ? GROUP BY LOWER(TRIM(COALESCE(status,'')))");
      $stmt->execute([$sessionUserEmail, $startDate, $endDateExclusive]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) { $k = $r['status'] !== '' ? $r['status'] : 'unknown'; $svcByUser[$k] = (int)$r['cnt']; }
  }

  // Service requests by type (top 10)
  $stmt = $pdo->prepare("SELECT COALESCE(request_type,'Unknown') AS type, COUNT(*) AS cnt FROM service_requests WHERE created_at >= ? AND created_at < ? GROUP BY COALESCE(request_type,'Unknown') ORDER BY cnt DESC LIMIT 10");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $svcTypes = [];
  foreach ($rows as $r) { $svcTypes[] = ['label'=>$r['type'],'count'=>(int)$r['cnt']]; }

  // Equipment by month/status/type
  $stmt = $pdo->prepare("SELECT {$equipmentPeriod} AS ym, COUNT(*) AS cnt FROM equipment WHERE created_at >= ? AND created_at < ? GROUP BY ym ORDER BY ym ASC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $equipmentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $equipmentSeries = $fill($equipmentRows);

  $stmt = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(status,''))) AS status, COUNT(*) AS cnt FROM equipment WHERE created_at >= ? AND created_at < ? GROUP BY LOWER(TRIM(COALESCE(status,''))) ORDER BY cnt DESC");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $equipmentByStatus = [];
  foreach ($rows as $r) { $k = $r['status'] !== '' ? $r['status'] : 'unknown'; $equipmentByStatus[$k] = (int)$r['cnt']; }

  $stmt = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(equipment_type),''),'Unknown') AS equipment_type, COUNT(*) AS cnt FROM equipment WHERE created_at >= ? AND created_at < ? GROUP BY equipment_type ORDER BY cnt DESC LIMIT 10");
  $stmt->execute([$startDate, $endDateExclusive]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $equipmentByType = [];
  foreach ($rows as $r) { $equipmentByType[$r['equipment_type']] = (int)$r['cnt']; }

  // Assignments snapshot by role/office unit (users with at least one assigned device)
  $assignedUsersTotal = 0;
  $assignedByRole = [];
  $assignedByOffice = [];
  $assignmentScopeSql = "EXISTS (
      SELECT 1
      FROM equipment e
      WHERE e.actual_user = CAST(u.id AS CHAR)
         OR e.actual_user = u.full_name
         OR e.actual_user LIKE CONCAT('%', u.full_name, '%')
    )";

  $stmt = $pdo->query("SELECT COUNT(DISTINCT e.id) AS cnt
    FROM equipment e
    WHERE LOWER(TRIM(COALESCE(e.status,''))) IN ('assigned','in use')");
  $assignedEquipmentTotal = (int)$stmt->fetchColumn();

  $stmt = $pdo->query("SELECT COUNT(*) AS cnt
    FROM users u
    WHERE u.role <> 'Admin' AND {$assignmentScopeSql}");
  $assignedUsersTotal = (int)$stmt->fetchColumn();

  $stmt = $pdo->query("SELECT COALESCE(NULLIF(TRIM(u.role),''),'Unknown') AS role, COUNT(*) AS cnt
    FROM users u
    WHERE u.role <> 'Admin' AND {$assignmentScopeSql}
    GROUP BY role
    ORDER BY cnt DESC");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) { $assignedByRole[$r['role']] = (int)$r['cnt']; }

  $stmt = $pdo->query("SELECT COALESCE(NULLIF(TRIM(u.office_unit),''),'Unknown') AS office_unit, COUNT(*) AS cnt
    FROM users u
    WHERE u.role <> 'Admin' AND {$assignmentScopeSql}
    GROUP BY office_unit
    ORDER BY cnt DESC");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) { $assignedByOffice[$r['office_unit']] = (int)$r['cnt']; }

  echo json_encode([
    'ok' => true,
    'labels' => $labels,
    'spot' => ['series' => $spotSeries, 'by_status' => $spotBy],
    'cases' => ['series' => $caseSeries, 'by_status' => $caseBy],
    'apprehended' => ['persons' => $personsSeries, 'vehicles' => $vehiclesSeries, 'items' => $itemsSeries],
    'equipment' => [
      'series' => $equipmentSeries,
      'by_status' => $equipmentByStatus,
      'by_type' => $equipmentByType
    ],
    'assignments' => [
      'assigned_users_total' => $assignedUsersTotal,
      'assigned_equipment_total' => $assignedEquipmentTotal,
      'by_role' => $assignedByRole,
      'by_office' => $assignedByOffice
    ],
    'service_requests' => ['series' => $svcSeries, 'by_status' => $svcBy, 'by_status_user' => $svcByUser, 'by_type' => $svcTypes],
    'breakdowns' => [
      'roles' => $rolesBy,
      'genders' => $genderBy,
      'vehicles_make' => $vehiclesByMake,
      'items_type' => $itemsByType,
      'locations' => $locationsBy
    ]
  ]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  exit;
}
