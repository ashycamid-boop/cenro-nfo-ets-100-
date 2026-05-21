<?php
require __DIR__ . '/app/config/db.php';
$st = $pdo->query("SHOW TABLES LIKE 'service_request_actions'");
$row = $st->fetch(PDO::FETCH_NUM);
echo $row ? 'EXISTS' : 'MISSING';
