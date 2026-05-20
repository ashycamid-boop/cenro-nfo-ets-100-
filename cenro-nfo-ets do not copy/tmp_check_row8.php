<?php
require __DIR__ . '/app/config/db.php';
$stmt = $pdo->prepare("UPDATE equipment SET status = 'Returned' WHERE id = 8");
$ok = $stmt->execute();
echo 'ok=' . ($ok ? '1' : '0') . "\n";
echo 'rowCount=' . $stmt->rowCount() . "\n";
print_r($stmt->errorInfo());
$row = $pdo->query("SELECT id, property_number, status FROM equipment WHERE id = 8")->fetch(PDO::FETCH_ASSOC);
var_export($row);
