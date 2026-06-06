<?php

if (!function_exists('get_pending_spot_report_count')) {
    function get_pending_spot_report_count(): int
    {
        static $pendingCount = null;

        if ($pendingCount !== null) {
            return $pendingCount;
        }

        $pendingCount = 0;

        try {
            global $pdo;

            if (!isset($pdo) || !($pdo instanceof PDO)) {
                require_once __DIR__ . '/../../config/db.php';
            }

            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*)
                     FROM spot_reports s
                     INNER JOIN users u ON u.id = s.submitted_by
                     WHERE LOWER(TRIM(COALESCE(s.status, ''))) = 'pending'
                       AND LOWER(TRIM(COALESCE(u.role, ''))) = 'enforcer'"
                );
                $stmt->execute();
                $pendingCount = (int) $stmt->fetchColumn();
            }
        } catch (Throwable $e) {
            $pendingCount = 0;
        }

        return $pendingCount;
    }
}

if (!function_exists('render_spot_report_sidebar_badge')) {
    function render_spot_report_sidebar_badge(): string
    {
        return ' <span class="badge">' . htmlspecialchars((string) get_pending_spot_report_count(), ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (!function_exists('get_enforcer_spot_report_attention_counts')) {
    function get_enforcer_spot_report_attention_counts(): array
    {
        static $attentionCounts = null;

        if ($attentionCounts !== null) {
            return $attentionCounts;
        }

        $attentionCounts = [
            'pending' => 0,
            'rejected' => 0,
        ];

        try {
            global $pdo;

            if (!isset($pdo) || !($pdo instanceof PDO)) {
                require_once __DIR__ . '/../../config/db.php';
            }

            $sessionUid = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
            if ($sessionUid > 0 && isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare(
                    "SELECT LOWER(TRIM(COALESCE(status, ''))) AS status_name, COUNT(*) AS total_count
                     FROM spot_reports
                     WHERE submitted_by = ?
                       AND LOWER(TRIM(COALESCE(status, ''))) IN ('pending', 'rejected')
                     GROUP BY LOWER(TRIM(COALESCE(status, '')))"
                );
                $stmt->execute([$sessionUid]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $statusName = $row['status_name'] ?? '';
                    if (array_key_exists($statusName, $attentionCounts)) {
                        $attentionCounts[$statusName] = (int) ($row['total_count'] ?? 0);
                    }
                }
            }
        } catch (Throwable $e) {
            $attentionCounts = [
                'pending' => 0,
                'rejected' => 0,
            ];
        }

        return $attentionCounts;
    }
}

if (!function_exists('render_enforcer_spot_report_sidebar_badge')) {
    function render_enforcer_spot_report_sidebar_badge(): string
    {
        $counts = get_enforcer_spot_report_attention_counts();
        $badges = '';

        if (($counts['pending'] ?? 0) > 0) {
            $badges .= ' <span class="badge">' . htmlspecialchars((string) $counts['pending'], ENT_QUOTES, 'UTF-8') . '</span>';
        }

        if (($counts['rejected'] ?? 0) > 0) {
            $badges .= ' <span class="badge badge-red" style="background:#dc3545 !important;color:#fff !important;">' . htmlspecialchars((string) $counts['rejected'], ENT_QUOTES, 'UTF-8') . '</span>';
        }

        return $badges;
    }
}
