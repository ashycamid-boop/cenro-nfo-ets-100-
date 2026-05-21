<?php
require_once __DIR__ . '/app.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cenro_nasipit');
define('DB_USER', 'root');
define('DB_PASS', '');

//charset=utf8mb4 is important for proper Unicode support, including emojis and certain non-Latin characters. It ensures that the database can store a wide range of characters without issues.
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());

    http_response_code(500);

    header('Content-Type: application/json');
    die(json_encode([
        'error' => 'Database connection failed.',
        'message' => $e->getMessage(),
    ]));
}

?>
