<?php
require __DIR__ . '/app/config/db.php';

$before = (int)$pdo->query("SELECT COUNT(*) FROM equipment WHERE TRIM(COALESCE(status, '')) = ''")->fetchColumn();
$updated = $pdo->exec("UPDATE equipment SET status = 'Returned' WHERE TRIM(COALESCE(status, '')) = ''");
$after = (int)$pdo->query("SELECT COUNT(*) FROM equipment WHERE TRIM(COALESCE(status, '')) = ''")->fetchColumn();

echo "blank_before={$before}\n";
echo "updated={$updated}\n";
echo "blank_after={$after}\n";

$stmt = $pdo->query("SELECT id, property_number, equipment_type, status FROM equipment ORDER BY id DESC LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id'] . ' | ' . ($row['property_number'] ?? '') . ' | ' . ($row['equipment_type'] ?? '') . ' | ' . var_export($row['status'], true) . "\n";
}
