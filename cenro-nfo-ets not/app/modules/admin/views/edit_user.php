<?php require_once __DIR__ . '/../controllers/edit_user_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Add User specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/add_user.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/edit_user.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-edit-user-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <nav class="sidebar" id="adminEditUserSidebar" role="navigation" aria-label="Main sidebar">
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
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminEditUserSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Edit User</div>
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

          <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fa fa-check-circle me-2"></i>
              <?php echo htmlspecialchars($message); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fa fa-exclamation-circle me-2"></i>
              <?php echo htmlspecialchars($error); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if ($user): ?>
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
                    <?php 
                      $imgSrc = '../../../../public/assets/images/default-avatar.png';
                      if (!empty($user['profile_picture'])) {
                          $imgSrc = '../../../../' . ltrim($user['profile_picture'], '/');
                      }
                    ?>
                    <img src="<?php echo $imgSrc; ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:block;">
                  </div>
                  <button type="button" class="upload-btn" onclick="document.getElementById('profile_picture').click()">
                    <i class="fa fa-upload me-2"></i>Change Photo
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
                  <form id="editUserForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                    <input type="hidden" name="update_user" value="1">
                    <input type="hidden" name="cropped_profile_picture" id="cropped_profile_picture">

                    <input type="file" name="profile_picture" id="profile_picture" accept="image/png, image/jpeg" style="display:none" onchange="previewProfile(event)">

                    <div class="form-section-spacing">
                      <div class="row g-3">
                        <div class="col-12 col-md-3">
                          <div class="form-group-clean">
                            <label for="firstName" class="form-label-clean">
                              First Name<span class="required-mark">*</span>
                            </label>
                            <input type="text" name="firstName" class="form-control-clean" id="firstName" required placeholder="Enter first name" value="<?php echo htmlspecialchars($firstName); ?>">
                          </div>
                        </div>
                        <div class="col-12 col-md-3">
                          <div class="form-group-clean">
                            <label for="middleName" class="form-label-clean">Middle Name</label>
                            <input type="text" name="middleName" class="form-control-clean" id="middleName" placeholder="Enter middle name" value="<?php echo htmlspecialchars($middleName); ?>">
                          </div>
                        </div>
                        <div class="col-12 col-md-3">
                          <div class="form-group-clean">
                            <label for="lastName" class="form-label-clean">
                              Last Name<span class="required-mark">*</span>
                            </label>
                            <input type="text" name="lastName" class="form-control-clean" id="lastName" required placeholder="Enter last name" value="<?php echo htmlspecialchars($lastName); ?>">
                          </div>
                        </div>
                        <div class="col-12 col-md-3">
                          <div class="form-group-clean">
                            <label for="suffix" class="form-label-clean">Suffix</label>
                            <input type="text" name="suffix" class="form-control-clean" id="suffix" placeholder="Jr., Sr., III" value="<?php echo htmlspecialchars($suffix); ?>">
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
                            <input type="email" name="email" class="form-control-clean" id="email" required placeholder="" value="<?php echo htmlspecialchars($user['email']); ?>">
                            <div class="form-help"></div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group-clean">
                            <label for="contactNumber" class="form-label-clean">
                              Contact Number<span class="required-mark">*</span>
                            </label>
                            <input type="tel" name="contactNumber" class="form-control-clean" id="contactNumber" required placeholder="" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">
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
                              Password
                            </label>
                            <input type="password" name="password" class="form-control-clean" id="password" placeholder="Leave blank to keep current password">
                            <div class="form-help">Password must be at least 8 characters long and contain a mix of letters and numbers.</div>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group-clean">
                            <label for="confirmPassword" class="form-label-clean">
                              Confirm Password
                            </label>
                            <input type="password" name="confirmPassword" class="form-control-clean" id="confirmPassword" placeholder="Repeat password">
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
                              <option value="Antongalon ENR Monitoring Information and Assistance Center" <?php echo ($user['office_unit'] ?? '') === 'Antongalon ENR Monitoring Information and Assistance Center' ? 'selected' : ''; ?>>Antongalon ENR Monitoring Information and Assistance Center</option>
                              <option value="BIT-OS ENR Monitoring Information and Assistance Center" <?php echo ($user['office_unit'] ?? '') === 'BIT-OS ENR Monitoring Information and Assistance Center' ? 'selected' : ''; ?>>BIT-OS ENR Monitoring Information and Assistance Center</option>
                              <option value="Bokbokon Anti-Illegal Logging Taskforce Checkpoint" <?php echo ($user['office_unit'] ?? '') === 'Bokbokon Anti-Illegal Logging Taskforce Checkpoint' ? 'selected' : ''; ?>>Bokbokon Anti-Illegal Logging Taskforce Checkpoint</option>
                              <option value="Buenavista ENR Monitoring Information and Assistance Center" <?php echo ($user['office_unit'] ?? '') === 'Buenavista ENR Monitoring Information and Assistance Center' ? 'selected' : ''; ?>>Buenavista ENR Monitoring Information and Assistance Center</option>
                              <option value="Camagong Anti-Environmental Crime Task Force (AECTF) Checkpoint" <?php echo ($user['office_unit'] ?? '') === 'Camagong Anti-Environmental Crime Task Force (AECTF) Checkpoint' ? 'selected' : ''; ?>>Camagong Anti-Environmental Crime Task Force (AECTF) Checkpoint</option>
                              <option value="CBFM" <?php echo ($user['office_unit'] ?? '') === 'CBFM' ? 'selected' : ''; ?>>CBFM</option>
                              <option value="CRFMU" <?php echo ($user['office_unit'] ?? '') === 'CRFMU' ? 'selected' : ''; ?>>CRFMU</option>
                              <option value="Dankias ENR Monitoring Information and Assistance Center" <?php echo ($user['office_unit'] ?? '') === 'Dankias ENR Monitoring Information and Assistance Center' ? 'selected' : ''; ?>>Dankias ENR Monitoring Information and Assistance Center</option>
                              <option value="Foreshore Management Unit" <?php echo ($user['office_unit'] ?? '') === 'Foreshore Management Unit' ? 'selected' : ''; ?>>Foreshore Management Unit</option>
                              <option value="Licensing and Permitting Unit" <?php echo ($user['office_unit'] ?? '') === 'Licensing and Permitting Unit' ? 'selected' : ''; ?>>Licensing and Permitting Unit</option>
                              <option value="Lumbocan ENR Monitoring Information and Assistance Center" <?php echo ($user['office_unit'] ?? '') === 'Lumbocan ENR Monitoring Information and Assistance Center' ? 'selected' : ''; ?>>Lumbocan ENR Monitoring Information and Assistance Center</option>
                              <option value="Monitoring and Evaluation Unit" <?php echo ($user['office_unit'] ?? '') === 'Monitoring and Evaluation Unit' ? 'selected' : ''; ?>>Monitoring and Evaluation Unit</option>
                              <option value="Nasipit Port ENR Monitoring Information and Assistance Center" <?php echo ($user['office_unit'] ?? '') === 'Nasipit Port ENR Monitoring Information and Assistance Center' ? 'selected' : ''; ?>>Nasipit Port ENR Monitoring Information and Assistance Center</option>
                              <option value="NGP" <?php echo ($user['office_unit'] ?? '') === 'NGP' ? 'selected' : ''; ?>>NGP</option>
                              <option value="PABEU" <?php echo ($user['office_unit'] ?? '') === 'PABEU' ? 'selected' : ''; ?>>PABEU</option>
                              <option value="Patents and Deeds Unit" <?php echo ($user['office_unit'] ?? '') === 'Patents and Deeds Unit' ? 'selected' : ''; ?>>Patents and Deeds Unit</option>
                              <option value="Planning Unit" <?php echo ($user['office_unit'] ?? '') === 'Planning Unit' ? 'selected' : ''; ?>>Planning Unit</option>
                              <option value="Support Unit" <?php echo ($user['office_unit'] ?? '') === 'Support Unit' ? 'selected' : ''; ?>>Support Unit</option>
                              <option value="Survey and Mapping Unit" <?php echo ($user['office_unit'] ?? '') === 'Survey and Mapping Unit' ? 'selected' : ''; ?>>Survey and Mapping Unit</option>
                              <option value="WATERSHED" <?php echo ($user['office_unit'] ?? '') === 'WATERSHED' ? 'selected' : ''; ?>>WATERSHED</option>
                              <option value="WRUS" <?php echo ($user['office_unit'] ?? '') === 'WRUS' ? 'selected' : ''; ?>>WRUS</option>
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
                              <option value="Enforcement Officer" <?php echo $user['role'] === 'Enforcement Officer' ? 'selected' : ''; ?>>Enforcement Officer</option>
                              <option value="Enforcer" <?php echo $user['role'] === 'Enforcer' ? 'selected' : ''; ?>>Enforcer</option>
                              <option value="Property Custodian" <?php echo $user['role'] === 'Property Custodian' ? 'selected' : ''; ?>>Property Custodian</option>
                              <option value="Office Staff" <?php echo $user['role'] === 'Office Staff' ? 'selected' : ''; ?>>Office Staff</option>
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
                              Position
                            </label>
                            <input type="text" name="position" class="form-control-clean" id="position" placeholder="Enter position" value="<?php echo htmlspecialchars($position); ?>">
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="action-buttons">
                      <button type="button" class="btn-cancel" onclick="window.location.href='user_management.php'">
                        <i class="fa fa-times me-2"></i>Cancel
                      </button>
                      <button type="submit" class="btn-create">
                        <i class="fa fa-save me-2"></i>Save Changes
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <?php else: ?>
          <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle me-2"></i>
            User not found. <a href="user_management.php">Back to users</a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <script src="../../../../public/assets/js/admin/navigation.js"></script>
  <script src="../../../../public/assets/js/shared/profile-image-cropper.js"></script>

  <script src="../../../../public/assets/js/admin/edit_user.js?v=20260404-2"></script>
</body>
</html>
