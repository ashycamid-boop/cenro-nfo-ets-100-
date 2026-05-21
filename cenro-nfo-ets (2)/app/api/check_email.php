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
require_once __DIR__ . '/../helpers/email_helper.php';

$email = trim((string) ($_GET['email'] ?? ''));
$excludeUserId = isset($_GET['exclude_user_id']) ? (int) $_GET['exclude_user_id'] : null;

echo json_encode([
    'exists' => userEmailExists($pdo, $email, $excludeUserId),
    'normalized' => normalizeEmail($email),
]);
