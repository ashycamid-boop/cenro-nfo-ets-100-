<?php require_once __DIR__ . '/../controllers/assigned_devices_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Assigned Devices</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Assigned Devices specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/assigned-devices.css?v=20260515-responsive">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/property_custodian/assigned_devices.css?v=20260515-responsive">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body>
  <?php
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $baseUrl = '';

  if ($scriptName !== '') {
    $appPos = strpos($scriptName, '/app/');

    if ($appPos !== false) {
      $baseUrl = substr($scriptName, 0, $appPos);
    } else {
      $baseUrl = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
    }
  }

  $assetBaseUrl = ($baseUrl !== '' ? $baseUrl : '') . '/public/assets/images';
  $uploadBaseUrl = $baseUrl !== '' ? $baseUrl . '/' : '/';
  $assetBaseUrl = preg_replace('#(?<!:)/{2,}#', '/', $assetBaseUrl);
  $uploadBaseUrl = preg_replace('#(?<!:)/{2,}#', '/', $uploadBaseUrl);
  ?>
  <div class="layout">
    <!-- Sidebar -->
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <nav class="sidebar" id="propertyCustodianAssignedDevicesSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role">Property Custodian</div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="equipment_management.php"><i class="fa fa-cogs"></i> Equipment Management</a></li>
          <li class="active"><a href="assignments.php"><i class="fa fa-tasks"></i> Assignments</a></li>
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" id="serviceDeskToggle" data-target="serviceDeskMenu">
              <i class="fa fa-headset"></i> Service Desk 
              <i class="fa fa-chevron-down dropdown-arrow"></i>
            </a>
            <ul class="dropdown-menu" id="serviceDeskMenu">
              <li><a href="new_requests.php">New Requests <span class="badge">2</span></a></li>
              <li><a href="ongoing_scheduled.php">Ongoing / Scheduled <span class="badge badge-blue">2</span></a></li>
              <li><a href="completed.php">Completed</a></li>
              <li><a href="all_requests.php">All Requests</a></li>
            </ul>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="propertyCustodianAssignedDevicesSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Assigned Devices</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        
        <div class="container-fluid">
          <div class="row mb-3 no-print">
            <div class="col-6">
              <a href="assignments.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left me-2"></i>Back
              </a>
            </div>
            <div class="col-6 text-end">
              <button class="btn btn-outline-dark" onclick="printForm()">
                <i class="fa fa-print me-2"></i>Print
              </button>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="row align-items-center mb-4 header-logos">
                <div class="col-md-2">
                  <img src="<?php echo htmlspecialchars($assetBaseUrl . '/denr-logo.png', ENT_QUOTES, 'UTF-8'); ?>" alt="DENR Logo" style="width: 100px; height: 100px; object-fit: contain;">
                </div>
                <div class="col-md-8 text-center">
                  <h6 class="mb-0"><strong>Department of Environment and Natural Resources</strong></h6>
                  <p class="mb-0"><strong>Kagawaran ng Kapaligiran at Likas na Yaman</strong></p>
                  <p class="mb-0"><strong>Caraga Region</strong></p>
                  <p class="mb-0"><strong>CENRO Nasipit, Agusan del Norte</strong></p>
                </div>
                <div class="col-md-2 text-end">
                  <img src="<?php echo htmlspecialchars($assetBaseUrl . '/bagong-pilipinas-logo.png', ENT_QUOTES, 'UTF-8'); ?>" alt="Bagong Pilipinas Logo" style="width: 100px; height: 100px; object-fit: contain;">
                </div>
              </div>

              <hr>

              <h5 class="text-center mb-4">Assigned Devices</h5>

              <?php
              // $user and $devices are prepared at the top of the file.
              $displayName = $user['full_name'] ?? '';
              $displayEmail = $user['email'] ?? '';
              $displayRole = $user['role'] ?? '';
              $displayOffice = $user['office_unit'] ?? '';
              $displayContact = $user['contact_number'] ?? '';

              // build avatar src
              $defaultAvatar = $assetBaseUrl . '/default-avatar.png';
              $imgSrc = $defaultAvatar;
              if (!empty($user['profile_picture'])) {
                $stored = ltrim($user['profile_picture'], '/');
                $fsPath = __DIR__ . '/../../../../' . $stored;
                if (file_exists($fsPath)) {
                  $imgSrc = $uploadBaseUrl . $stored;
                }
              }
              ?>

              <div class="row mb-4">
                <div class="col-md-2 profile-photo-container">
                  <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="User Photo" class="img-fluid rounded-circle profile-photo">
                </div>
                <div class="col-md-10">
                  <table class="table table-bordered user-info-table">
                    <tbody>
                      <tr>
                        <td class="info-label">Full Name</td>
                        <td><?php echo htmlspecialchars($displayName); ?></td>
                        <td class="info-label">Email</td>
                        <td><?php if (!empty($displayEmail)) { echo '<a href="mailto:' . htmlspecialchars($displayEmail) . '" class="text-decoration-underline">' . htmlspecialchars($displayEmail) . '</a>'; } else { echo '-'; } ?></td>
                        <td class="info-label">Mobile Number</td>
                        <td><?php echo !empty($displayContact) ? htmlspecialchars($displayContact) : '-'; ?></td>
                      </tr>
                      <tr>
                        <td class="info-label">Role</td>
                        <td><?php echo htmlspecialchars($displayRole); ?></td>
                        <td class="info-label">Office/Unit</td>
                        <td><?php echo htmlspecialchars($displayOffice); ?></td>
                        <td class="info-label">Number of Devices</td>
                        <td><?php echo count($devices); ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <h6 class="mb-3" style="color: #999;">Assigned Devices</h6>
              <div class="table-responsive">
                <table class="table table-bordered devices-table">
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
                        <?php if (!empty($devices) && count($devices) > 0): ?>
                          <?php foreach ($devices as $d): ?>
                            <tr>
                              <td data-label="Asset ID"><?php echo htmlspecialchars($d['id'] ?? ''); ?></td>
                              <td data-label="Property No."><?php echo htmlspecialchars($d['property_number'] ?? '-'); ?></td>
                              <td data-label="Category"><?php echo htmlspecialchars($d['equipment_type'] ?? '-'); ?></td>
                              <td data-label="Brand"><?php echo htmlspecialchars($d['brand'] ?? '-'); ?></td>
                              <td data-label="Model"><?php echo htmlspecialchars($d['model'] ?? '-'); ?></td>
                              <td data-label="Serial Number"><?php echo htmlspecialchars($d['serial_number'] ?? '-'); ?></td>
                              <td data-label="Date Acquired"><?php echo htmlspecialchars($d['year_acquired'] ?? '-'); ?></td>
                              <td data-label="Status"><?php echo htmlspecialchars($d['status'] ?? '-'); ?></td>
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

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <script src="../../../../public/assets/js/admin/navigation.js"></script>
  <script src="../../../../public/assets/js/admin/assigned-devices.js"></script>
  <script src="../../../../public/assets/js/property_custodian/assigned_devices.js"></script>
</body>
</html>
