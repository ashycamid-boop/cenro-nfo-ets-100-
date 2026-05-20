<?php
require_once __DIR__ . '/../controllers/assignments_backend.php';
require_once __DIR__ . '/../../../helpers/qr_url.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assignments</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/assignments.css?v=20260319-3">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/property_custodian/assignments.css?v=20260320-1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-assignments-page property-custodian-assignments-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <nav class="sidebar" id="propertyCustodianAssignmentsSidebar" role="navigation" aria-label="Main sidebar">
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
          </li>
        </ul>
      </nav>
    </nav>
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="propertyCustodianAssignmentsSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Assignments</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid">
          <div class="assignments-mobile-toolbar d-block d-sm-none mb-3">
            <div class="assignments-mobile-toolbar-row">
              <div class="assignments-mobile-search">
                <input type="text" class="form-control" id="searchInputMobile" placeholder="Search assignments">
              </div>
              <button class="btn assignments-mobile-filter-btn" type="button" id="openAssignmentsFiltersMobile">
                <i class="fa fa-sliders-h me-2"></i>Filters
              </button>
            </div>
            <div class="assignments-mobile-actions">
              <button class="btn btn-outline-dark" type="button" id="printAllQrMobile">
                <i class="fa fa-print me-2"></i>Print QR
              </button>
            </div>
            <div class="assignments-mobile-filter-chips" id="assignmentsActiveFilterChips"></div>
          </div>

          <div class="top-action-bar mb-4 assignments-desktop-controls d-none d-sm-block">
            <div class="row align-items-center">
              <div class="col-md-3">
                <div class="search-box">
                  <input type="text" class="form-control" id="searchInput" placeholder="Search">
                </div>
              </div>
              <div class="col-md-2">
                <select class="form-select" id="roleFilter">
                  <option value="">Role</option>
                  <option value="Enforcement Officer">Enforcement Officer</option>
                  <option value="Enforcer">Enforcer</option>
                  <option value="Property Custodian">Property Custodian</option>
                  <option value="Office Staff">Office Staff</option>
                </select>
              </div>
              <div class="col-md-2">
                <select class="form-select" id="officeUnitFilter">
                  <option value="">Select Office/Unit</option>
                  <option value="Antongalon ENR Monitoring Information and Assistance Center">Antongalon ENR Monitoring Information and Assistance Center</option>
                  <option value="BIT-OS ENR Monitoring Information and Assistance Center">BIT-OS ENR Monitoring Information and Assistance Center</option>
                  <option value="Bokbokon Anti-Illegal Logging Taskforce Checkpoint">Bokbokon Anti-Illegal Logging Taskforce Checkpoint</option>
                  <option value="Buenavista ENR Monitoring Information and Assistance Center">Buenavista ENR Monitoring Information and Assistance Center</option>
                  <option value="Camagong Anti-Environmental Crime Task Force (AECTF) Checkpoint">Camagong Anti-Environmental Crime Task Force (AECTF) Checkpoint</option>
                  <option value="CBFM">CBFM</option>
                  <option value="CRFMU">CRFMU</option>
                  <option value="Dankias ENR Monitoring Information and Assistance Center">Dankias ENR Monitoring Information and Assistance Center</option>
                  <option value="Foreshore Management Unit">Foreshore Management Unit</option>
                  <option value="Licensing and Permitting Unit">Licensing and Permitting Unit</option>
                  <option value="Lumbocan ENR Monitoring Information and Assistance Center">Lumbocan ENR Monitoring Information and Assistance Center</option>
                  <option value="Monitoring and Evaluation Unit">Monitoring and Evaluation Unit</option>
                  <option value="Nasipit Port ENR Monitoring Information and Assistance Center">Nasipit Port ENR Monitoring Information and Assistance Center</option>
                  <option value="NGP">NGP</option>
                  <option value="PABEU">PABEU</option>
                  <option value="Patents and Deeds Unit">Patents and Deeds Unit</option>
                  <option value="Planning Unit">Planning Unit</option>
                  <option value="Support Unit">Support Unit</option>
                  <option value="Survey and Mapping Unit">Survey and Mapping Unit</option>
                  <option value="WATERSHED">WATERSHED</option>
                  <option value="WRUS">WRUS</option>
                </select>
              </div>
              <div class="col-md-2">
                <div class="d-flex gap-2">
                  <button class="btn btn-primary" id="applyBtn">
                    <i class="fa fa-filter me-1"></i>Apply
                  </button>
                  <button class="btn btn-outline-secondary" id="clearBtn">Clear</button>
                </div>
              </div>
              <div class="col-md-3 text-end">
                <button class="btn btn-outline-dark" onclick="printAllQRCodes()">
                  <i class="fa fa-print me-2"></i>Print All QR Codes
                </button>
              </div>
            </div>
          </div>

          <div class="assignments-table-section">
            <div class="table-responsive">
              <table class="table table-bordered" id="assignmentsTable">
                <thead class="table-light">
                  <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Office/Unit</th>
                    <th>Devices</th>
                    <th>QR Code</th>
                    <th>Details</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                      <?php
                        $defaultAvatar = '../../../../public/assets/images/default-avatar.png';
                        $imgSrc = $defaultAvatar;
                        if (!empty($user['profile_picture'])) {
                          $stored = ltrim($user['profile_picture'], '/');
                          $fsPath = __DIR__ . '/../../../../' . $stored;
                          $imgSrc = file_exists($fsPath) ? ('../../../../' . $stored) : ('../../../../' . $stored);
                        }
                        $qrPayload = cenro_project_url('public/assigned_devices_view.php?user_id=' . urlencode($user['id']));
                        $qrData = urlencode($qrPayload);
                      ?>
                      <tr>
                        <td data-label="User ID"><?php echo htmlspecialchars($user['id']); ?></td>
                        <td data-label="Full Name">
                          <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Avatar" style="width:36px;height:36px;object-fit:cover;border-radius:50%;vertical-align:middle;margin-right:8px;">
                          <span><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></span>
                        </td>
                        <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td data-label="Role"><?php echo htmlspecialchars($user['role']); ?></td>
                        <td data-label="Office/Unit"><?php echo !empty($user['office_unit']) ? htmlspecialchars($user['office_unit']) : '-'; ?></td>
                        <td data-label="Devices" class="text-center"><a href="assigned_devices.php?user_id=<?php echo urlencode($user['id']); ?>"><?php echo (isset($user['device_count']) ? (int)$user['device_count'] : 0); ?></a></td>
                        <td data-label="QR Code"><img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo $qrData; ?>" class="qr-code-image" alt="QR"></td>
                        <td data-label="Details"><a href="assigned_devices.php?user_id=<?php echo urlencode($user['id']); ?>" class="btn btn-sm btn-outline-primary">Details</a></td>
                        <td data-label="Action" class="actions-cell">
                          <div class="d-flex gap-1 action-group">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="printAssignedDevices('<?php echo htmlspecialchars($user['id']); ?>')" aria-label="Print QR card">
                              <i class="fa fa-print"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="9" class="text-center text-muted py-3">No users found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="assignments-mobile-filter-modal d-sm-none" id="assignmentsMobileFilterModal" aria-hidden="true">
            <div class="assignments-mobile-filter-backdrop" data-close-assignments-filters="true"></div>
            <div class="assignments-mobile-filter-sheet" role="dialog" aria-modal="true" aria-labelledby="assignmentsMobileFilterTitle">
              <div class="assignments-mobile-filter-handle"></div>
              <div class="assignments-mobile-filter-header">
                <h5 id="assignmentsMobileFilterTitle">Filter Assignments</h5>
                <button type="button" class="btn-close" aria-label="Close filters" data-close-assignments-filters="true"></button>
              </div>
              <div class="assignments-mobile-filter-body">
                <div class="mb-3">
                  <label class="form-label" for="searchInputModal">Search</label>
                  <input type="text" class="form-control" id="searchInputModal" placeholder="Search assignments">
                </div>
                <div class="mb-3">
                  <label class="form-label" for="roleFilterModal">Role</label>
                  <select class="form-select" id="roleFilterModal">
                    <option value="">Role</option>
                    <option value="Enforcement Officer">Enforcement Officer</option>
                    <option value="Enforcer">Enforcer</option>
                    <option value="Property Custodian">Property Custodian</option>
                    <option value="Office Staff">Office Staff</option>
                  </select>
                </div>
                <div class="mb-0">
                  <label class="form-label" for="officeUnitFilterModal">Office/Unit</label>
                  <select class="form-select" id="officeUnitFilterModal">
                    <option value="">Select Office/Unit</option>
                    <option value="Antongalon ENR Monitoring Information and Assistance Center">Antongalon ENR Monitoring Information and Assistance Center</option>
                    <option value="BIT-OS ENR Monitoring Information and Assistance Center">BIT-OS ENR Monitoring Information and Assistance Center</option>
                    <option value="Bokbokon Anti-Illegal Logging Taskforce Checkpoint">Bokbokon Anti-Illegal Logging Taskforce Checkpoint</option>
                    <option value="Buenavista ENR Monitoring Information and Assistance Center">Buenavista ENR Monitoring Information and Assistance Center</option>
                    <option value="Camagong Anti-Environmental Crime Task Force (AECTF) Checkpoint">Camagong Anti-Environmental Crime Task Force (AECTF) Checkpoint</option>
                    <option value="CBFM">CBFM</option>
                    <option value="CRFMU">CRFMU</option>
                    <option value="Dankias ENR Monitoring Information and Assistance Center">Dankias ENR Monitoring Information and Assistance Center</option>
                    <option value="Foreshore Management Unit">Foreshore Management Unit</option>
                    <option value="Licensing and Permitting Unit">Licensing and Permitting Unit</option>
                    <option value="Lumbocan ENR Monitoring Information and Assistance Center">Lumbocan ENR Monitoring Information and Assistance Center</option>
                    <option value="Monitoring and Evaluation Unit">Monitoring and Evaluation Unit</option>
                    <option value="Nasipit Port ENR Monitoring Information and Assistance Center">Nasipit Port ENR Monitoring Information and Assistance Center</option>
                    <option value="NGP">NGP</option>
                    <option value="PABEU">PABEU</option>
                    <option value="Patents and Deeds Unit">Patents and Deeds Unit</option>
                    <option value="Planning Unit">Planning Unit</option>
                    <option value="Support Unit">Support Unit</option>
                    <option value="Survey and Mapping Unit">Survey and Mapping Unit</option>
                    <option value="WATERSHED">WATERSHED</option>
                    <option value="WRUS">WRUS</option>
                  </select>
                </div>
              </div>
              <div class="assignments-mobile-filter-footer">
                <button class="btn btn-outline-secondary" type="button" id="clearAssignmentsFiltersMobile">Clear All</button>
                <button class="btn btn-primary" type="button" id="applyAssignmentsFiltersMobile">Apply</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260320-1"></script>
  <script src="../../../../public/assets/js/admin/assignments.js?v=20260319-2"></script>
</body>
</html>
