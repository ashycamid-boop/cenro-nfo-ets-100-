<?php
// Backfill script to fix equipment_actual_user_history inconsistencies
require_once __DIR__ . '/../app/config/db.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1) Populate missing date_assigned for rows that have a new_actual_user
    $stmt1 = $pdo->prepare("UPDATE equipment_actual_user_history
        SET date_assigned = COALESCE(date_assigned, changed_at)
        WHERE new_actual_user IS NOT NULL
          AND (date_assigned IS NULL OR TRIM(COALESCE(date_assigned, '')) = '')");
    $stmt1->execute();
    $cnt1 = $stmt1->rowCount();

    // 2) Set status = 'Transferred' for rows that have date_moved but are not marked transferred
    $stmt2 = $pdo->prepare("UPDATE equipment_actual_user_history
        SET status = 'Transferred'
        WHERE date_moved IS NOT NULL
          AND (status IS NULL OR TRIM(COALESCE(status, '')) != 'Transferred')");
    $stmt2->execute();
    $cnt2 = $stmt2->rowCount();

    echo "Backfill completed. date_assigned rows updated: {$cnt1}. status updated to Transferred: {$cnt2}.\n";
} catch (Exception $e) {
    echo "Backfill failed: " . $e->getMessage() . "\n";
}

?>
