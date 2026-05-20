<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'office_staff') {
  header('Location: ' . app_url('index.php'));
  exit;
}

// Load DB and fetch user's service requests with optional filters (search, date range)
require_once __DIR__ . '/../../../config/db.php';
$currentUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$currentUserEmail = $_SESSION['email'] ?? null;
$service_requests = [];

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
  $where = [];
  $params = [];

  if (!empty($currentUserId)) {
    $where[] = 'created_by = ?';
    $params[] = $currentUserId;
  } elseif (!empty($currentUserEmail)) {
    $where[] = 'requester_email = ?';
    $params[] = $currentUserEmail;
  } else {
    // no owner info -> return empty
    $service_requests = [];
  }

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

  if (!empty($where)) {
    $sql = 'SELECT * FROM service_requests WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $service_requests = $stmt->fetchAll();
  } else {
    // No additional filters beyond ownership: fetch all for the user
    if (!empty($currentUserId)) {
      $stmt = $pdo->prepare('SELECT * FROM service_requests WHERE created_by = ? ORDER BY created_at DESC');
      $stmt->execute([$currentUserId]);
      $service_requests = $stmt->fetchAll();
    } elseif (!empty($currentUserEmail)) {
      $stmt = $pdo->prepare('SELECT * FROM service_requests WHERE requester_email = ? ORDER BY created_at DESC');
      $stmt->execute([$currentUserEmail]);
      $service_requests = $stmt->fetchAll();
    }
  }
} catch (Exception $e) {
  error_log('service_requests fetch error: ' . $e->getMessage());
  $service_requests = [];
}

