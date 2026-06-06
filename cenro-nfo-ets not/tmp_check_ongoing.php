<?php
require __DIR__ . '/app/config/db.php';
$st = $pdo->query("SELECT id,ticket_no,status,requester_name,requester_position,requester_office,requester_division,requester_phone,requester_email,request_type,LEFT(request_description,80) as req_desc,auth1_name,auth2_name,requester_signature_path,auth1_signature_path,auth2_signature_path,created_at FROM service_requests WHERE LOWER(status) IN ('ongoing','scheduled') ORDER BY id DESC LIMIT 8");
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
  echo json_encode($r, JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
