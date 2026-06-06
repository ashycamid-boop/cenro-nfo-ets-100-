<?php
require __DIR__ . '/app/config/db.php';
foreach ($pdo->query("SHOW FULL COLUMNS FROM equipment") as $row) {
  if (($row['Field'] ?? '') === 'status') {
    var_export($row);
    echo "\n";
  }
}
