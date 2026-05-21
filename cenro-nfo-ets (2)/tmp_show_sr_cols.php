<?php
require __DIR__ . '/app/config/db.php';
$st = $pdo->query("SHOW COLUMNS FROM service_requests");
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { echo $r['Field'] . PHP_EOL; }
