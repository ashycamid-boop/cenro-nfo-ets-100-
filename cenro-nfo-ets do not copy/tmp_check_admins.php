<?php
require __DIR__ . '/app/config/db.php';
$stmt = $pdo->query("SELECT id, full_name, role, status FROM users ORDER BY id");
foreach ($stmt as $r) {
  echo $r['id'] . " | " . $r['full_name'] . " | " . $r['role'] . " | " . $r['status'] . PHP_EOL;
}
?>
