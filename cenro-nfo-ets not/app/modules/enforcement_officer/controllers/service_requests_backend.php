<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$sidebarRole = 'Enforcement Officer';
$service_requests = [];

// Load DB and fetch user's service requests with optional filters (search, date range)
require_once dirname(__DIR__, 3) . '/config/db.php';
$currentUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$currentUserEmail = $_SESSION['email'] ?? null;

// Filter inputs (GET)
$search = trim($_GET['search'] ?? '');
$rawDateFrom = trim($_GET['date_from'] ?? '');
$rawDateTo = trim($_GET['date_to'] ?? '');

// Normalize dates: accept MM-DD-YYYY (display) and convert to YYYY-MM-DD for SQL
$dateFrom = '';
$dateTo = '';
$displayDateFrom = '';
$displayDateTo = '';

if ($rawDateFrom !== '') {
  $dt = DateTime::createFromFormat('m-d-Y', $rawDateFrom);
  if ($dt && $dt->format('m-d-Y') === $rawDateFrom) {
    $dateFrom = $dt->format('Y-m-d');
    $displayDateFrom = $rawDateFrom;
  } else {
    $dt2 = DateTime::createFromFormat('Y-m-d', $rawDateFrom);
    if ($dt2 && $dt2->format('Y-m-d') === $rawDateFrom) {
      $dateFrom = $dt2->format('Y-m-d');
      $displayDateFrom = $dt2->format('m-d-Y');
    }
  }
}

if ($rawDateTo !== '') {
  $dt = DateTime::createFromFormat('m-d-Y', $rawDateTo);
  if ($dt && $dt->format('m-d-Y') === $rawDateTo) {
    $dateTo = $dt->format('Y-m-d');
    $displayDateTo = $rawDateTo;
  } else {
    $dt2 = DateTime::createFromFormat('Y-m-d', $rawDateTo);
    if ($dt2 && $dt2->format('Y-m-d') === $rawDateTo) {
      $dateTo = $dt2->format('Y-m-d');
      $displayDateTo = $dt2->format('m-d-Y');
    }
  }
}

try {
  // Enforce ownership: require authenticated user (created_by) or matching requester_email.
  // If neither is present, do not run any queries and return empty results.
  if (empty($currentUserId) && empty($currentUserEmail)) {
    $service_requests = [];
  } else {
    $where = [];
    $params = [];

    // Owner constraint is mandatory and always applied
    if (!empty($currentUserId)) {
      $where[] = 'created_by = ?';
      $params[] = $currentUserId;
    } else {
      $where[] = 'requester_email = ?';
      $params[] = $currentUserEmail;
    }

    // Additional optional filters (search, date range)
    if ($search !== '') {
      $where[] = '(ticket_no LIKE ? OR request_type LIKE ? OR request_description LIKE ? OR requester_name LIKE ?)';
      $like = '%' . $search . '%';
      $params[] = $like;
      $params[] = $like;
      $params[] = $like;
      $params[] = $like;
    }

    if ($dateFrom !== '') {
      $where[] = 'DATE(COALESCE(ticket_date, created_at)) >= ?';
      $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
      $where[] = 'DATE(COALESCE(ticket_date, created_at)) <= ?';
      $params[] = $dateTo;
    }

    // Build and execute query - ownership condition is guaranteed to be present
    $sql = 'SELECT * FROM service_requests WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $service_requests = $stmt->fetchAll();
  }
} catch (Exception $e) {
  error_log('service_requests fetch error: ' . $e->getMessage());
  $service_requests = [];
}

