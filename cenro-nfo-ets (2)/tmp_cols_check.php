<?php
require __DIR__ . '/app/config/db.php';
$st = $pdo->query("SHOW COLUMNS FROM service_request_actions");
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) { echo $r['Field'] . PHP_EOL; }
