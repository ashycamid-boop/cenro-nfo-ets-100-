<?php
require __DIR__ . '/app/config/db.php';
$pdo->exec("ALTER TABLE equipment MODIFY COLUMN status ENUM('Available','In Use','Returned','Under Maintenance','Damaged','Out of Service','Disposed') NOT NULL DEFAULT 'In Use'");
$pdo->exec("UPDATE equipment SET status = 'Returned' WHERE id = 8 AND TRIM(COALESCE(status, '')) = ''");
foreach ($pdo->query("SHOW FULL COLUMNS FROM equipment") as $row) {
  if (($row['Field'] ?? '') === 'status') {
    echo 'enum=' . $row['Type'] . "\n";
  }
}
$row = $pdo->query("SELECT id, property_number, status FROM equipment WHERE id = 8")->fetch(PDO::FETCH_ASSOC);
echo 'row8=' . json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
?>
