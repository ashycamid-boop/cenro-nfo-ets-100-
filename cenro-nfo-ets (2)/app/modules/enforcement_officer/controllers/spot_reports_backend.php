<?php
try {
    require_once __DIR__ . '/../../../config/db.php';
    $stmt = $pdo->prepare("SELECT s.id, s.reference_no, s.incident_datetime, s.location, s.summary, s.team_leader, s.custodian, s.status, s.status_comment, u.full_name AS submitted_by_name, (SELECT SUM(value) FROM spot_report_items WHERE report_id = s.id) AS est_value FROM spot_reports s LEFT JOIN users u ON u.id = s.submitted_by WHERE u.role = 'Enforcer' ORDER BY s.created_at DESC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows = array();
}

function short_text($s, $len = 120) {
    $s = trim(strip_tags((string)$s));
    if (mb_strlen($s) <= $len) return $s;
    return mb_substr($s, 0, $len) . '...';
}

$totalReports = count($rows);
$totalEst = 0.0;
foreach ($rows as $r) {
    $estRaw = isset($r['est_value']) ? $r['est_value'] : null;
    $totalEst += ($estRaw !== null && $estRaw !== '') ? (float)$estRaw : 0.0;
}
$totalEstFormatted = $totalReports > 0 ? '&#8369; ' . number_format($totalEst, 2) : '-';
