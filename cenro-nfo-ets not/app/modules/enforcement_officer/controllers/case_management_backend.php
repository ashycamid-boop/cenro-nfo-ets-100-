<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sidebarRole = 'Enforcement Officer';

$counts = [
    'under-investigation' => 0,
    'pending-review' => 0,
    'for-filing' => 0,
    'filed-in-court' => 0,
    'ongoing-trial' => 0,
    'resolved' => 0,
    'dismissed' => 0,
    'archived' => 0,
    'on-hold' => 0,
    'under-appeal' => 0
];
$approvedRows = [];

try {
    require_once __DIR__ . '/../../../config/db.php';

    $stmtCounts = $pdo->query("SELECT LOWER(TRIM(case_status)) AS status, COUNT(*) AS cnt FROM spot_reports WHERE LOWER(TRIM(status)) = 'approved' GROUP BY LOWER(TRIM(case_status))");
    while ($r = $stmtCounts->fetch(PDO::FETCH_ASSOC)) {
        $s = strtolower(trim($r['status'] ?? ''));
        $c = (int)$r['cnt'];
        if ($s === '') {
            $counts['under-investigation'] += $c;
        } elseif (strpos($s, 'under') !== false && (strpos($s, 'invest') !== false || strpos($s, 'review') === false)) {
            $counts['under-investigation'] += $c;
        } elseif (strpos($s, 'pending') !== false || strpos($s, 'pending review') !== false) {
            $counts['pending-review'] += $c;
        } elseif (strpos($s, 'for filing') !== false || strpos($s, 'for-filing') !== false) {
            $counts['for-filing'] += $c;
        } elseif (strpos($s, 'filed') !== false || strpos($s, 'filed in court') !== false || strpos($s, 'filed-in-court') !== false) {
            $counts['filed-in-court'] += $c;
        } elseif (strpos($s, 'ongoing') !== false || strpos($s, 'trial') !== false) {
            $counts['ongoing-trial'] += $c;
        } elseif (strpos($s, 'dismiss') !== false) {
            $counts['dismissed'] += $c;
        } elseif (strpos($s, 'resolv') !== false || strpos($s, 'resolved') !== false) {
            $counts['resolved'] += $c;
        } elseif (strpos($s, 'archiv') !== false) {
            $counts['archived'] += $c;
        } elseif (strpos($s, 'hold') !== false) {
            $counts['on-hold'] += $c;
        } elseif (strpos($s, 'appeal') !== false) {
            $counts['under-appeal'] += $c;
        } else {
            $counts['under-investigation'] += $c;
        }
    }

    $stmt = $pdo->prepare("SELECT s.id, s.reference_no, s.incident_datetime, s.location, s.team_leader, u.full_name AS submitted_by_name, s.status, s.case_status, (SELECT SUM(value) FROM spot_report_items WHERE report_id = s.id) AS est_value FROM spot_reports s LEFT JOIN users u ON u.id = s.submitted_by WHERE LOWER(TRIM(s.status)) = 'approved' ORDER BY s.created_at DESC");
    $stmt->execute();
    $approvedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $counts = array_fill_keys(array_keys($counts), 0);
    $approvedRows = [];
}
