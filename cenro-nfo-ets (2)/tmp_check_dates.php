<?php
require __DIR__ . '/app/config/db.php';
$st = $pdo->query("SELECT id,ticket_no,auth1_date,auth2_date,status FROM service_requests ORDER BY id DESC LIMIT 10");
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { echo json_encode($r, JSON_UNESCAPED_SLASHES) . PHP_EOL; }
