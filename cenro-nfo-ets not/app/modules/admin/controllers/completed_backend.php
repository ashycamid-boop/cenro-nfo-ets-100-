<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ' . app_url('index.php'));
    exit;
}

$sidebarRole = 'Administrator';

require_once __DIR__ . '/../../../config/db.php';
$completed = [];
try {
    $cstmt = $pdo->prepare("SELECT * FROM service_requests WHERE LOWER(status) = 'completed' ORDER BY updated_at DESC");
    $cstmt->execute();
    $completed = $cstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('completed fetch error: ' . $e->getMessage());
    $completed = [];
}

try {
    $earliestStmt = $pdo->prepare("SELECT action_date, action_time FROM service_request_actions WHERE service_request_id = :id AND action_date IS NOT NULL ORDER BY action_date ASC, action_time ASC LIMIT 1");
    $latestStmt = $pdo->prepare("SELECT action_date, action_time FROM service_request_actions WHERE service_request_id = :id ORDER BY id DESC LIMIT 1");
} catch (Exception $e) {
    error_log('prepare action stmts error: ' . $e->getMessage());
    $earliestStmt = $latestStmt = null;
}
