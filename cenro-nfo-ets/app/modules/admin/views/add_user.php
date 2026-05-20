<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();
require_once __DIR__ . '/../../../../app/config/db.php';
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ' . app_url('index.php'));
    exit;
}


// Prepare topbar profile using logged-in user's DB values (fallbacks for prototype)
$topImg = '../../../../public/assets/images/default-avatar.png';
$topName = 'Guest';
$topRole = 'User'; 
$sessionUserId = $_SESSION['uid'] ?? $_SESSION['id'] ?? null;
$sessionUserEmail = $_SESSION['email'] ?? null;
try {
  $r = null;
  if (!empty($sessionUserId)) {
    $stmt = $pdo->prepare('SELECT id, full_name, profile_picture, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$sessionUserId]);
    $r = $stmt->fetch();
  } elseif (!empty($sessionUserEmail)) {
    $stmt = $pdo->prepare('SELECT id, full_name, profile_picture, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$sessionUserEmail]);
    $r = $stmt->fetch();
  }
  if (!empty($r)) {
    $topName = !empty($r['full_name']) ? $r['full_name'] : $topName;
    $topRole = !empty($r['role']) ? $r['role'] : $topRole;
    if (!empty($r['profile_picture'])) {
      $stored = ltrim($r['profile_picture'], '/');
      $fsPath = __DIR__ . '/../../../../' . $stored;
      if (file_exists($fsPath)) {
        $topImg = '../../../../' . $stored;
      }
    }
  }
} catch (Exception $e) {
  // silent fallback to defaults
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New User</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Add User specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/add_user.css?v=20260315-1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <nav class="sidebar" id="adminAddUserSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role">Administrator</div>
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
    <div class="main">
      <div class="topbar">
          <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminAddUserSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Add New User</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid p-4">
          <div>
            <div>
              <h1 class="page-title">
              </h1>
            </div>
          </div>

          <?php if (!empty($_GET['err'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fa fa-exclamation-triangle me-2"></i>
              <?php echo htmlspecialchars((string) $_GET['err'], ENT_QUOTES, 'UTF-8'); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          
          <form id="addUserForm" method="post" enctype="multipart/form-data" action="../../../auth/register.php">
          <input type="hidden" name="cropped_profile_picture" id="cropped_profile_picture">
          <div class="row g-4">
            
            <div class="col-12 col-lg-4">
              <div class="card form-card">
                <div class="card-header-simple">
                  <h5>
                    <i class="card-icon fa fa-camera"></i>
                    Profile Picture
                  </h5>
                </div>
                <div class="profile-upload-section">
                  <div class="profile-placeholder" id="profilePreview" onclick="document.getElementById('profile_picture').click()">
                    <i class="fa fa-user"></i>
                  </div>
                  <input type="file" name="profile_picture" id="profile_picture" accept="image/png, image/jpeg" required style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;" onchange="previewProfile(event)">
                  <button type="button" class="upload-btn" onclick="document.getElementById('profile_picture').click()">
                    <i class="fa fa-upload me-2"></i>Choose Photo
                  </button>
                  <p class="form-help mt-2">
                    JPG, PNG format. Max 2MB
                  </p>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-8">
              <div class="card form-card">
                <div class="card-header-simple">
                  <h5>
                    <i class="card-icon fa fa-user-edit"></i>
                    User Information
                  </h5>
                </div>
                <div class="form-section">
                  
                    <div class="form-section-spacing">
                      <div class="row g-3">
                        <div class="col-12 col-md-3">
                          <div class="form-group-clean">
                            <label for="firstName" class="form-label-clean">
                              First Name<span class="required-mark">*</span>
                            </label>
                            <input type="text" name="firstName" class="form-control-clean" id="firstName" required placeholder="Enter first name" autocapitalize="words">
                          </div>
                        </div>
                        <div class="col-12 col-md-3">
                          <div class="form-group-clean">
                            <label for="middleName" class="form-label-clean">Middle Name</label>
                            <input type="text" name="middleName" class="form-control-clean" id="middleName" placeholder="Enter middle name" autocapitalize="words">
                          </div>
                        </div>
                        <div class="col-12 col-md-3">
                          <div class="form-group-clean">
                            <label for="lastName" class="form-label-clean">
                              Last Name<span class="required-mark">*</span>
                            </label>
                            <input type="text" name="lastName" class="form-control-clean" id="lastName" required placeholder="Enter last name" autocapitalize="words">
                          </div>
                        </div>
                        <div class="col-12 col-md-3">
                          <div class="form-group-clean">
                            <label for="suffix" class="form-label-clean">Suffix</label>
                            <input type="text" name="suffix" class="form-control-clean" id="suffix" placeholder="Jr., Sr., III" autocapitalize="words">
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="form-section-spacing">
                      <h6 class="section-title">
                        <i class="section-icon fa fa-phone"></i>
                        Contact Information
                      </h6>
                      <div class="row g-3">
                        <div class="col-12 col-md-6">
                          <div class="form-group-clean">
                            <label for="email" class="form-label-clean">
                              Email Address<span class="required-mark">*</span>
                            </label>
                            <input type="email" name="email" class="form-control-clean" id="email" required placeholder="">
                            <div class="form-help"></div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group-clean">
                            <label for="contactNumber" class="form-label-clean">
                              Contact Number<span class="required-mark">*</span>
                            </label>
                            <input type="tel" name="contactNumber" class="form-control-clean" id="contactNumber" required placeholder="">
                            <div class="form-help"></div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="form-section-spacing">
                      <h6 class="section-title">
                        <i class="section-icon fa fa-lock"></i>
                        Account Security
                      </h6>
                      <div class="row g-3">
                        <div class="col-12 col-md-6">
                          <div class="form-group-clean">
                            <label for="password" class="form-label-clean">
                              Password<span class="required-mark">*</span>
                            </label>
                            <input type="password" name="password" class="form-control-clean" id="password" required placeholder="Create secure password">
                            <div class="form-help">Password must be at least 8 characters long and contain a mix of letters and numbers.</div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group-clean">
                            <label for="confirmPassword" class="form-label-clean">
                              Confirm Password<span class="required-mark">*</span>
                            </label>
                            <input type="password" name="confirmPassword" class="form-control-clean" id="confirmPassword" required placeholder="Repeat password">
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="form-section-spacing">
                      <h6 class="section-title">
                        <i class="section-icon fa fa-building"></i>
                        Work Assignment
                      </h6>
                      <div class="row g-3">
                        <div class="col-12 col-md-6">
                          <div class="form-group-clean">
                            <label for="officeUnit" class="form-label-clean">
                              Office/Unit<span class="required-mark">*</span>
                            </label>
                            <select class="form-select-clean" name="officeUnit" id="officeUnit" required>
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
                        <div class="col-12 col-md-6">
                          <div class="form-group-clean">
                            <label for="role" class="form-label-clean">
                              Role<span class="required-mark">*</span>
                            </label>
                            <select class="form-select-clean" name="role" id="role" required>
                              <option value="">Select Role</option>
                              <option value="Enforcement Officer">Enforcement Officer</option>
                              <option value="Enforcer">Enforcer</option>
                              <option value="Property Custodian">Property Custodian</option>
                              <option value="Office Staff">Office Staff</option>
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="form-section-spacing">
                      <h6 class="section-title">
                        <i class="section-icon fa fa-id-badge"></i>
                        Position
                      </h6>
                      <div class="row g-3">
                        <div class="col-12 col-md-6">
                          <div class="form-group-clean">
                            <label for="position" class="form-label-clean">
                              Position<span class="required-mark">*</span>
                            </label>
                            <input type="text" name="position" class="form-control-clean" id="position" required placeholder="Enter position" autocapitalize="words">
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="action-buttons">
                      <button type="button" class="btn-cancel" onclick="window.location.href='user_management.php'">
                        <i class="fa fa-times me-2"></i>Cancel
                      </button>
                      <button type="submit" class="btn-create">
                        <i class="fa fa-user-plus me-2"></i>Create Account
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260315-6"></script>
  <script src="../../../../public/assets/js/shared/profile-image-cropper.js"></script>
  <script src="../../../../public/assets/js/admin/add_user.js?v=20260404-2"></script>


</body>
</html>
