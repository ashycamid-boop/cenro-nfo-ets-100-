<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();
require_once __DIR__ . '/../../../../app/config/db.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ' . app_url('index.php'));
    exit;
}

$sidebarRole = (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin')
    ? 'Administrator'
    : (isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Administrator');
// Fetch all users from database
$users = [];
try {
  // Exclude Admin role from listing so administrator accounts are not shown here
  // include profile_picture so avatar can be displayed in the table
  $stmt = $pdo->query("SELECT id, email, full_name, role, status, contact_number, office_unit, created_at, profile_picture FROM users WHERE role <> 'Admin' ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    // Log error silently if needed
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- User Management specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/user_management.css?v=20260319-2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="adminUserManagementSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo $sidebarRole; ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li class="active"><a href="user_management.php"><i class="fa fa-users"></i> User Management</a></li>
          <li><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_spot_report_sidebar_badge(); ?></a></li>
          <li><a href="case_management.php"><i class="fa fa-briefcase"></i> Case Management</a></li>
          <li><a href="apprehended_items.php"><i class="fa fa-archive"></i> Apprehended Items</a></li>
          <li><a href="equipment_management.php"><i class="fa fa-cogs"></i> Equipment Management</a></li>
          <li><a href="assignments.php"><i class="fa fa-tasks"></i> Assignments</a></li>
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
          <li><a href="statistical_report.php"><i class="fa fa-chart-bar"></i> Statistical Report</a></li>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminUserManagementSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">User Management</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <!-- User Management Content -->
        <div class="container-fluid p-4">
          <div class="um-mobile-toolbar">
            <div class="um-mobile-search-wrap">
              <i class="fa fa-search um-mobile-search-icon" aria-hidden="true"></i>
              <input type="text" class="form-control um-mobile-search" placeholder="Search users..." id="searchInputMobile">
            </div>
            <div class="um-mobile-actions">
              <button type="button" class="btn um-mobile-filter-btn" data-bs-toggle="modal" data-bs-target="#userManagementFiltersModal">
                <i class="fa fa-sliders me-2" aria-hidden="true"></i>Filters
              </button>
              <button type="button" class="btn um-mobile-add-btn" onclick="window.location.href='add_user.php'">
                <i class="fa fa-plus me-2" aria-hidden="true"></i>Add User
              </button>
            </div>
          </div>

          <div class="um-active-filters" id="userManagementActiveFilters" aria-label="Active filters"></div>

          <!-- Search and Filter Section -->
          <div class="row g-3 mb-4 um-filters-desktop">
            <div class="col-12 col-lg-4">
              <div class="input-group">
                <input type="text" class="form-control" placeholder="Search users..." id="searchInput">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
              </div>
            </div>
            <div class="col-12 col-lg-8">
              <div class="d-flex flex-column flex-sm-row gap-2 align-items-start align-items-sm-center">
                <span class="me-2 fw-bold d-none d-sm-inline">Filters:</span>
                <div class="w-100 d-sm-none">
                  <select class="form-select form-select-sm mobile-role-filter" id="mobileRoleFilter" aria-label="Filter users by role">
                    <option value="all">All Roles</option>
                    <option value="Enforcement Officer">Enforcement Officer</option>
                    <option value="Enforcer">Enforcer</option>
                    <option value="Property Custodian">Property Custodian</option>
                    <option value="Office Staff">Office Staff</option>
                  </select>
                </div>
                <div class="d-none d-sm-flex flex-wrap gap-2 flex-grow-1">
                  <button class="btn btn-primary btn-sm filter-btn active" data-role="all">All</button>
                  <button class="btn btn-outline-secondary btn-sm filter-btn d-none d-md-inline-block" data-role="Enforcement Officer">Enforcement Officer</button>
                  <button class="btn btn-outline-secondary btn-sm filter-btn d-md-none" data-role="Enforcement Officer">Officer</button>
                  <button class="btn btn-outline-secondary btn-sm filter-btn" data-role="Enforcer">Enforcer</button>
                  <button class="btn btn-outline-secondary btn-sm filter-btn d-none d-lg-inline-block" data-role="Property Custodian">Property Custodian</button>
                  <button class="btn btn-outline-secondary btn-sm filter-btn d-lg-none" data-role="Property Custodian">Custodian</button>
                  <button class="btn btn-outline-secondary btn-sm filter-btn d-none d-md-inline-block" data-role="Office Staff">Office Staff</button>
                  <button class="btn btn-outline-secondary btn-sm filter-btn d-md-none" data-role="Office Staff">Staff</button>
                </div>
                <button class="btn btn-success btn-sm align-self-end align-self-sm-center" onclick="window.location.href='add_user.php'">
                  <i class="fa fa-plus me-1"></i><span class="d-none d-sm-inline">Add User</span><span class="d-sm-none">Add</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Users Table -->
          <div class="card shadow-sm">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0" id="usersTable">
                  <thead class="table-light">
                    <tr>
                      <th class="d-none d-md-table-cell">ID</th>
                      <th>FULL NAME</th>
                      <th class="d-none d-lg-table-cell">EMAIL</th>
                      <th>ROLE</th>
                      <th class="d-none d-xl-table-cell">OFFICE/UNIT</th>
                      <th class="d-none d-lg-table-cell">CONTACT NUMBER</th>
                      <th>STATUS</th>
                      <th class="d-none d-md-table-cell">CREATED AT</th>
                      <th>ACTIONS</th>
                    </tr>
                  </thead>
                  <tbody id="usersTableBody">
                    <?php if (count($users) > 0): ?>
                      <?php foreach ($users as $user): ?>
                        <?php
require_once dirname(__DIR__, 3) . '/config/app.php';
                          // Build avatar src per-user with fallback to default avatar
                          $defaultAvatar = '../../../../public/assets/images/default-avatar.png';
                          $imgSrc = $defaultAvatar;
                          if (!empty($user['profile_picture'])) {
                              $stored = ltrim($user['profile_picture'], '/'); // e.g. 'public/uploads/..'
                              // prefer filesystem-backed file if present; otherwise still try the relative URL
                              $fsPath = __DIR__ . '/../../../../' . $stored;
                              $imgSrc = file_exists($fsPath) ? ('../../../../' . $stored) : ('../../../../' . $stored);
                          }
                          // ensure escaped when printed
                        ?>
                         <tr data-role="<?php echo htmlspecialchars($user['role']); ?>" data-user-id="<?php echo htmlspecialchars($user['id']); ?>" data-user-name="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                           <td class="d-none d-md-table-cell" data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                           <td class="full-name-cell" data-label="Full Name">
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Avatar">
                            <span><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></span>
                           </td>
                           <td class="d-none d-lg-table-cell" data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                           <td data-label="Role"><?php echo htmlspecialchars($user['role']); ?></td>
                           <td class="d-none d-xl-table-cell" data-label="Office/Unit"><?php echo !empty($user['office_unit']) ? htmlspecialchars($user['office_unit']) : '-'; ?></td>
                           <td class="d-none d-lg-table-cell" data-label="Contact Number"><?php echo !empty($user['contact_number']) ? htmlspecialchars($user['contact_number']) : '-'; ?></td>
                           <td data-label="Status"><span class="status-badge <?php echo ((int)$user['status'] === 1) ? 'status-enable' : 'status-disable'; ?>">
                             <?php echo ((int)$user['status'] === 1) ? 'Enable' : 'Disabled'; ?>
                           </span></td>
                           <td class="d-none d-md-table-cell" data-label="Created At"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                           <td class="actions-cell" data-label="Actions">
                             <div class="d-flex gap-1">
                               <button class="btn-edit">
                                 <i class="fa fa-edit"></i><span class="d-none d-sm-inline"> Edit</span>
                               </button>
                               <button class="btn-disable" data-current-status="<?php echo ((int)$user['status'] === 1) ? '1' : '0'; ?>">
                                 <i class="fa <?php echo ((int)$user['status'] === 1) ? 'fa-ban' : 'fa-check-circle'; ?>"></i><span class="d-none d-sm-inline"> <?php echo ((int)$user['status'] === 1) ? 'Disable' : 'Enable'; ?></span>
                               </button>
                             </div>
                           </td>
                         </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="9" class="text-center text-muted py-3">No users found. <a href="add_user.php">Create the first user</a>.</td>
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

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260315-7"></script>
  <script src="../../../../public/assets/js/admin/user_management.js?v=20260319-4"></script>

  <div class="modal fade" id="userManagementFiltersModal" tabindex="-1" aria-labelledby="userManagementFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable um-filters-modal-dialog">
      <div class="modal-content um-filters-modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="userManagementFiltersModalLabel">Filter Users</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="um-modal-field">
            <label for="searchInputModal" class="form-label">Search</label>
            <input type="text" class="form-control" placeholder="Search users..." id="searchInputModal">
          </div>
          <div class="um-modal-field">
            <label for="mobileRoleFilterModal" class="form-label">Role</label>
            <select class="form-select" id="mobileRoleFilterModal" aria-label="Filter users by role">
              <option value="all">All Roles</option>
              <option value="Enforcement Officer">Enforcement Officer</option>
              <option value="Enforcer">Enforcer</option>
              <option value="Property Custodian">Property Custodian</option>
              <option value="Office Staff">Office Staff</option>
            </select>
          </div>
        </div>
        <div class="modal-footer um-filters-modal-footer">
          <button type="button" id="clearUserFiltersMobile" class="btn btn-outline-secondary">Clear All</button>
          <button type="button" id="applyUserFiltersMobile" class="btn btn-primary" data-bs-dismiss="modal">Apply Filters</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
