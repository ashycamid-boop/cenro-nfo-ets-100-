<?php
require __DIR__ . '/app/config/db.php';
$stmt = $pdo->query("SELECT id, property_number, equipment_type, status FROM equipment ORDER BY id DESC LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo $row['id'] . ' | ' . ($row['property_number'] ?? '') . ' | ' . ($row['equipment_type'] ?? '') . ' | ' . var_export($row['status'], true) . "\n";
}
?>
