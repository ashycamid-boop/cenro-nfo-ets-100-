<?php

session_start();

header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode([
        'exists' => false,
        'message' => 'Unauthorized.',
    ]);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/contact_number_helper.php';

$contactNumber = trim((string) ($_GET['contact_number'] ?? ''));
$excludeUserId = isset($_GET['exclude_user_id']) ? (int) $_GET['exclude_user_id'] : null;

echo json_encode([
    'exists' => userContactNumberExists($pdo, $contactNumber, $excludeUserId),
    'normalized' => normalizeContactNumber($contactNumber),
]);
