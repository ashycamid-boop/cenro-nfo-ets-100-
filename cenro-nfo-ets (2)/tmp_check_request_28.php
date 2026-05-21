<?php
require 'app/config/db.php';
$stmt = $pdo->prepare('SELECT id, ticket_no, requester_signature_path, auth1_signature_path, auth2_signature_path, ack_signature_path FROM service_requests WHERE id = ? LIMIT 1');
$stmt->execute([28]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
var_export($row);
echo PHP_EOL;
if ($row) {
    foreach (['requester_signature_path','auth1_signature_path','auth2_signature_path','ack_signature_path'] as $col) {
        $path = $row[$col] ?? '';
        $full = $path ? getcwd() . '/' . str_replace('\\', '/', ltrim($path, '/')) : '';
        echo $col . ': ' . ($path ?: '[empty]') . PHP_EOL;
        if ($path) {
            echo 'exists=' . (is_file($full) ? 'yes' : 'no') . ' full=' . $full . PHP_EOL;
        }
    }
}
?>
