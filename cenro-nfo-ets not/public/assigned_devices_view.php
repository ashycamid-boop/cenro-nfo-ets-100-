<?php
require_once __DIR__ . '/../app/config/db.php';

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$user = null;
$devices = [];

if ($userId <= 0) {
    die('Invalid user ID');
}

try {
    $stmt = $pdo->prepare("SELECT id, full_name, email, role, office_unit, profile_picture, contact_number FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die('User not found');
    }

    $stmt = $pdo->prepare("
        SELECT id, property_number, equipment_type, brand, model, serial_number, year_acquired, status
        FROM equipment
        WHERE actual_user = :user_id
           OR actual_user = :full_name
           OR actual_user LIKE :like_name
        ORDER BY property_number ASC
    ");
    $stmt->execute([
        ':user_id' => (string) $userId,
        ':full_name' => $user['full_name'],
        ':like_name' => '%' . $user['full_name'] . '%',
    ]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

$assetBaseUrl = 'assets/images';
$defaultAvatar = $assetBaseUrl . '/default-avatar.png';
$imgSrc = $defaultAvatar;

if (!empty($user['profile_picture'])) {
    $stored = ltrim($user['profile_picture'], '/');
    $fsPath = __DIR__ . '/../' . $stored;
    if (file_exists($fsPath)) {
        $imgSrc = '../' . $stored;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Assigned Devices - <?php echo htmlspecialchars($user['full_name'] ?? ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }
        html {
            width: 100%;
            overflow-x: hidden;
        }
        body {
            background: #f4f6f8;
            min-height: 100vh;
            padding: clamp(8px, 3vw, 20px);
            overflow-x: hidden;
            -webkit-text-size-adjust: 100%;
        }
        .assignment-card {
            width: min(100%, 980px);
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .assignment-card,
        .card-header,
        .card-body,
        .header-title,
        .table,
        .table td,
        .table th {
            min-width: 0;
        }
        .card-header {
            background: #fff;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            border-bottom: 1px solid #e6e6e6;
        }
        .header-logo {
            flex: 0 0 auto;
            width: 76px;
            height: 76px;
            object-fit: contain;
        }
        .header-title {
            flex: 1;
            text-align: center;
        }
        .header-title h1 {
            font-size: 1.08rem;
            margin: 0;
            color: #083a93;
            font-weight: 700;
            line-height: 1.25;
        }
        .header-title p {
            margin: 3px 0 0;
            color: #556b8a;
            font-size: 0.9rem;
        }
        .card-body {
            padding: 28px;
        }
        .profile-photo {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e7edf5;
        }
        .section-title {
            color: #083a93;
            font-weight: 700;
            border-bottom: 2px solid #083a93;
            padding-bottom: 8px;
            margin: 24px 0 14px;
        }
        .table td,
        .table th {
            overflow-wrap: anywhere;
            word-break: break-word;
            vertical-align: middle;
        }
        .profile-table th {
            color: #555;
            font-weight: 700;
            white-space: nowrap;
        }
        .device-table th {
            white-space: nowrap;
        }
        @media (max-width: 700px) {
            .card-header {
                display: grid;
                grid-template-columns: 64px minmax(0, 1fr) 64px;
                align-items: center;
                justify-content: initial;
                text-align: center;
                column-gap: 10px;
            }
            .header-title {
                grid-column: 2;
            }
            .card-body {
                padding: 18px;
            }
            .header-logo {
                width: 64px;
                height: 64px;
            }
            .header-title h1 {
                font-size: 0.98rem;
            }
            .header-title p {
                font-size: 0.82rem;
            }
        }
        @media (max-width: 575.98px) {
            body {
                font-size: 0.95rem;
            }
            .assignment-card {
                width: 100%;
                border-radius: 10px;
                box-shadow: 0 6px 22px rgba(0,0,0,0.1);
            }
            .card-header {
                padding: 14px 12px;
                row-gap: 8px;
            }
            .card-body {
                padding: 18px 14px;
            }
            .row.align-items-center {
                --bs-gutter-x: 0;
            }
            .profile-photo {
                width: 84px;
                height: 84px;
            }
            .section-title {
                font-size: 1rem;
                margin: 20px 0 12px;
            }
            .profile-table,
            .profile-table tbody,
            .profile-table tr,
            .profile-table th,
            .profile-table td {
                display: block;
                width: 100% !important;
            }
            .profile-table {
                border: 1px solid #dee2e6;
            }
            .profile-table tr {
                border-bottom: 1px solid #dee2e6;
                padding: 8px 10px;
            }
            .profile-table tr:last-child {
                border-bottom: 0;
            }
            .profile-table th,
            .profile-table td {
                border: 0;
                padding: 2px 0;
            }
            .profile-table th {
                margin-top: 5px;
                font-size: 0.76rem;
                letter-spacing: 0.02em;
                text-transform: uppercase;
                color: #6c757d;
                white-space: normal;
            }
            .profile-table th:first-child {
                margin-top: 0;
            }
            .profile-table td {
                font-size: 0.96rem;
            }
            .devices-table-wrap {
                overflow: visible;
            }
            .device-table,
            .device-table thead,
            .device-table tbody,
            .device-table tr,
            .device-table td {
                display: block;
                width: 100%;
            }
            .device-table {
                border: 0;
                margin-bottom: 0;
            }
            .device-table thead {
                display: none;
            }
            .device-table tr {
                background: #fff;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                margin-bottom: 12px;
                padding: 8px 10px;
            }
            .device-table tbody tr:nth-of-type(odd) {
                --bs-table-accent-bg: transparent;
            }
            .device-table td {
                border: 0;
                border-bottom: 1px solid #eef1f4;
                display: grid;
                grid-template-columns: 1fr;
                gap: 3px;
                padding: 8px 0;
                text-align: left;
            }
            .device-table td:last-child {
                border-bottom: 0;
            }
            .device-table td::before {
                content: attr(data-label);
                color: #6c757d;
                font-size: 0.76rem;
                font-weight: 700;
                letter-spacing: 0.02em;
                text-transform: uppercase;
            }
            .device-table .empty-state {
                display: block;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                margin-bottom: 0;
                padding: 14px 10px;
            }
            .device-table .empty-state td {
                display: block;
                border: 0;
                padding: 0;
                text-align: center;
            }
            .device-table .empty-state td::before {
                content: none;
            }
        }
        @media (max-width: 360px) {
            body {
                font-size: 0.9rem;
            }
            .assignment-card {
                border-radius: 8px;
            }
            .card-header {
                padding: 12px 10px;
                grid-template-columns: 52px minmax(0, 1fr) 52px;
            }
            .header-logo {
                width: 52px;
                height: 52px;
            }
            .header-title h1 {
                font-size: 0.9rem;
            }
            .card-body {
                padding: 14px 10px;
            }
        }
        @media (max-width: 320px) {
            .card-header {
                grid-template-columns: 46px minmax(0, 1fr) 46px;
            }
            .header-logo {
                width: 46px;
                height: 46px;
            }
            .header-title h1 {
                font-size: 0.84rem;
            }
            .header-title p,
            .device-table td::before,
            .profile-table th {
                font-size: 0.72rem;
            }
            .card-body {
                padding: 12px 8px;
            }
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .assignment-card {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="assignment-card">
        <div class="card-header">
            <img class="header-logo" src="assets/images/denr-logo.png" alt="DENR Logo">
            <div class="header-title">
                <h1>Department of Environment and Natural Resources</h1>
                <p>Assigned Devices</p>
            </div>
            <img class="header-logo" src="assets/images/bagong-pilipinas-logo.png" alt="Bagong Pilipinas Logo">
        </div>

        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-md-2 text-center">
                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="User Photo" class="profile-photo">
                </div>
                <div class="col-md-10">
                    <table class="table table-bordered mb-0 profile-table">
                        <tbody>
                            <tr>
                                <th style="width: 18%;">Full Name</th>
                                <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                <th style="width: 18%;">Email</th>
                                <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Role</th>
                                <td><?php echo htmlspecialchars($user['role'] ?? '-'); ?></td>
                                <th>Office/Unit</th>
                                <td><?php echo htmlspecialchars($user['office_unit'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Mobile Number</th>
                                <td><?php echo htmlspecialchars($user['contact_number'] ?? '-'); ?></td>
                                <th>Number of Devices</th>
                                <td><?php echo count($devices); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <h5 class="section-title">Assigned Devices</h5>
            <div class="table-responsive devices-table-wrap">
                <table class="table table-bordered table-striped align-middle device-table">
                    <thead class="table-light">
                        <tr>
                            <th>Asset ID</th>
                            <th>Property No.</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Serial Number</th>
                            <th>Date Acquired</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($devices)): ?>
                            <?php foreach ($devices as $device): ?>
                                <tr>
                                    <td data-label="Asset ID"><?php echo htmlspecialchars($device['id'] ?? ''); ?></td>
                                    <td data-label="Property No."><?php echo htmlspecialchars($device['property_number'] ?? '-'); ?></td>
                                    <td data-label="Category"><?php echo htmlspecialchars($device['equipment_type'] ?? '-'); ?></td>
                                    <td data-label="Brand"><?php echo htmlspecialchars($device['brand'] ?? '-'); ?></td>
                                    <td data-label="Model"><?php echo htmlspecialchars($device['model'] ?? '-'); ?></td>
                                    <td data-label="Serial Number"><?php echo htmlspecialchars($device['serial_number'] ?? '-'); ?></td>
                                    <td data-label="Date Acquired"><?php echo htmlspecialchars($device['year_acquired'] ?? '-'); ?></td>
                                    <td data-label="Status"><?php echo htmlspecialchars($device['status'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-state">
                                <td colspan="8" class="text-center text-muted py-3">No assigned devices found for this user.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
