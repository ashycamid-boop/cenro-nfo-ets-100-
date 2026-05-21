<?php
session_start();
$_SESSION['role'] = 'Admin';
$_SESSION['user_role'] = 'property_custodian';
$_GET['id'] = '20';
ob_start();
include __DIR__ . '/app/modules/admin/views/edit_requests_ongoing.php';
$out = ob_get_clean();
$needles = ['Joel A. Caluya','Administrative Assistant II','Set up projector and sound system','CENRO Nasipit','2026-03-0001'];
foreach ($needles as $n) {
  echo $n . ' => ' . (strpos($out, $n) !== false ? 'FOUND' : 'MISSING') . PHP_EOL;
}
