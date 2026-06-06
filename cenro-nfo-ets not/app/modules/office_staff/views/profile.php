<?php require_once __DIR__ . '/../controllers/profile_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - CENRO NASIPIT</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Add User specific styles (reuse for profile) -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/add_user.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/office_staff/profile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-profile-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="officeStaffProfileSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role">Office Staff</div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="service_requests.php"><i class="fa fa-cog"></i> Service Requests</a></li>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="officeStaffProfileSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">My Profile</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <!-- Profile Content -->
        <div class="container-fluid p-4">
          <!-- Page Header (title removed) -->

          <!-- Profile Display -->
          <?php if ($notFound): ?>
            <div class="alert alert-warning">User not found. <a href="user_management.php">Return to User Management</a></div>
          <?php else: ?>
            <?php if ($isOwnProfile): ?>
              <div class="row g-4">
                <div class="col-12">
                  <?php if ($profileMessage): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($profileMessage); ?></div>
                  <?php endif; ?>
                  <?php if ($profileError): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($profileError); ?></div>
                  <?php endif; ?>
                </div>

                <!-- left: profile picture -->
                <div class="col-12 col-lg-4">
                  <div class="card form-card">
                    <div class="card-header-simple">
                      <h5><i class="card-icon fa fa-image"></i>Profile Picture</h5>
                    </div>
                    <div class="profile-upload-section">
                      <form id="profilePicForm" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="update_profile" value="1">
                        <input type="hidden" name="profile_only" value="1">
                        <input type="hidden" name="cropped_profile_picture" id="cropped_profile_picture">
                        <div class="profile-display">
                          <?php
                            $imgSrc = '../../../../public/assets/images/default-avatar.png';
                            if (!empty($user['profile_picture'])) {
                                $imgSrc = '../../../../' . ltrim($user['profile_picture'], '/');
                            }
                          ?>
                          <img src="<?php echo $imgSrc; ?>" alt="Profile Picture" class="profile-image" id="mainProfileImage">
                        </div>
                        <input type="file" name="profile_picture" id="profile_picture_input" accept="image/png, image/jpeg" style="display:none" onchange="previewProfileAndSubmit(event)">
                        <div class="d-flex justify-content-center">
                          <button type="button" class="upload-btn" onclick="triggerProfilePicInput()"><i class="fa fa-camera me-2"></i>Change Photo</button>
                        </div>
                        <p class="form-help mt-2">JPG, PNG format. Max 2MB</p>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- right: editable fields -->
                <div class="col-12 col-lg-8">
                  <div class="card form-card">
                    <div class="card-header-simple">
                      <h5><i class="card-icon fa fa-user-circle"></i> Personal Information</h5>
                    </div>
                    <div class="form-section">
                      <div class="form-section-spacing">
                        <div class="row g-3">
                          <div class="col-12 col-md-6">
                            <div class="info-group">
                              <label class="info-label">Full Name</label>
                              <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            </div>
                          </div>
                          <div class="col-12 col-md-6">
                            <div class="info-group">
                              <label class="info-label">User ID</label>
                              <div class="info-value"><?php echo htmlspecialchars($user['id']); ?></div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="form-section-spacing">
                        <h6 class="section-title"><i class="section-icon fa fa-envelope"></i> Contact Information</h6>
                        <div class="row g-3">
                          <div class="col-12 col-md-6">
                            <div class="info-group">
                              <label class="info-label">Email</label>
                              <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                          </div>
                          <div class="col-12 col-md-6">
                            <div class="info-group">
                              <label class="info-label">Contact Number</label>
                              <div class="info-value"><?php echo !empty($user['contact_number']) ? htmlspecialchars($user['contact_number']) : '-'; ?></div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="form-section-spacing">
                        <h6 class="section-title"><i class="section-icon fa fa-building"></i> Work Assignment</h6>
                        <div class="row g-3">
                          <div class="col-12 col-md-6">
                            <div class="info-group">
                              <label class="info-label">Role</label>
                              <div class="info-value"><?php echo htmlspecialchars($user['role']); ?></div>
                            </div>
                          </div>
                          <div class="col-12 col-md-6">
                            <div class="info-group">
                              <label class="info-label">Office Unit</label>
                              <div class="info-value"><?php echo !empty($user['office_unit']) ? htmlspecialchars($user['office_unit']) : '-'; ?></div>
                            </div>
                          </div>
                          <div class="col-12 col-md-6">
                            <div class="info-group">
                              <label class="info-label">Position</label>
                              <div class="info-value"><?php echo !empty($user['position']) ? htmlspecialchars($user['position']) : '-'; ?></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              

            <?php else: ?>
              <div class="row g-4">
                <!-- Profile Picture Section -->
                <div class="col-12 col-lg-4">
                  <div class="card form-card">
                    <div class="card-header-simple">
                      <h5>
                        <i class="card-icon fa fa-image"></i>
                        Profile Picture
                      </h5>
                    </div>
                    <div class="profile-upload-section">
                      <div class="profile-display">
                        <?php
                          $imgSrc = '../../../../public/assets/images/default-avatar.png';
                          if (!empty($user['profile_picture'])) {
                              $imgSrc = '../../../../' . ltrim($user['profile_picture'], '/');
                          }
                        ?>
                        <img src="<?php echo $imgSrc; ?>" alt="Profile Picture" class="profile-image">
                        <?php if (!empty($user['profile_picture'])): ?>
                          <div class="small text-muted mt-2">Stored path: <?php echo htmlspecialchars($user['profile_picture']); ?></div>
                        <?php endif; ?>
                      </div>
                      <button type="button" class="upload-btn" onclick="changeProfilePicture()">
                        <i class="fa fa-camera me-2"></i>Change Photo
                      </button>
                      <p class="form-help mt-2">
                        JPG, PNG format. Max 2MB
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Profile Information Section -->
                <div class="col-12 col-lg-8">
                  <div class="card form-card">
                    <div class="card-header-simple">
                      <h5>
                        <i class="card-icon fa fa-user-circle"></i>
                        Personal Information
                      </h5>
                    </div>
                    <div class="form-section">
                      <!-- Personal Information -->
                      <div class="form-section-spacing">
                        <div class="row g-3">
                          <div class="col-12 col-md-6">
                              <div class="info-group">
                              <label class="info-label">Full Name</label>
                              <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            </div>
                          </div>
                          <div class="col-12 col-md-6">
                              <div class="info-group">
                              <label class="info-label">User ID</label>
                              <div class="info-value"><?php echo htmlspecialchars($user['id']); ?></div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Contact Information -->
                      <div class="form-section-spacing">
                        <h6 class="section-title">
                          <i class="section-icon fa fa-envelope"></i>
                          Contact Information
                        </h6>
                        <div class="row g-3">
                          <div class="col-12 col-md-6">
                              <div class="info-group">
                              <label class="info-label">Email</label>
                              <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                          </div>
                          <div class="col-12 col-md-6">
                              <div class="info-group">
                              <label class="info-label">Contact Number</label>
                              <div class="info-value"><?php echo !empty($user['contact_number']) ? htmlspecialchars($user['contact_number']) : '-'; ?></div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Account Information -->
                      <div class="form-section-spacing">
                        <h6 class="section-title">
                          <i class="section-icon fa fa-user-cog"></i>
                          Account Information
                        </h6>
                        <div class="row g-3">
                          <div class="col-12 col-md-6">
                                <div class="info-group">
                              <label class="info-label">Status</label>
                              <div class="info-value">
                                <?php if ((int)$user['status'] === 1): ?>
                                  <span class="badge badge-success"><i class="fa fa-check-circle me-1"></i>Active</span>
                                <?php else: ?>
                                  <span class="badge badge-secondary">Disabled</span>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>
                          <div class="col-12 col-md-6">
                              <div class="info-group">
                              <label class="info-label">Created At</label>
                              <div class="info-value"><?php echo $user['created_at'] ? date('F j, Y', strtotime($user['created_at'])) : '-'; ?></div>
                            </div>
                            <div class="info-group">
                              <label class="info-label">Last Login</label>
                              <div class="info-value"><?php echo !empty($user['last_login']) ? date('F j, Y g:i A', strtotime($user['last_login'])) : '-'; ?></div>
                            </div>
                            <div class="info-group">
                              <label class="info-label">Last Updated</label>
                              <div class="info-value"><?php echo $user['updated_at'] ? date('F j, Y g:i A', strtotime($user['updated_at'])) : '-'; ?></div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Role & Office Information -->
                      <div class="form-section-spacing">
                        <h6 class="section-title">
                          <i class="section-icon fa fa-id-badge"></i>
                          Role & Office Information
                        </h6>
                        <div class="row g-3">
                          <div class="col-12 col-md-6">
                              <div class="info-group">
                              <label class="info-label">Role</label>
                              <div class="info-value">
                                <span class="badge badge-primary">
                                  <i class="fa fa-user-shield me-1"></i><?php echo htmlspecialchars($user['role']); ?>
                                </span>
                              </div>
                            </div>
                          </div>
                          <div class="col-12 col-md-6">
                              <div class="info-group">
                              <label class="info-label">Office Unit</label>
                              <div class="info-value"><?php echo !empty($user['office_unit']) ? htmlspecialchars($user['office_unit']) : '-'; ?></div>
                            </div>
                          </div>
                          <div class="col-12 col-md-6">
                            <div class="info-group">
                              <label class="info-label">Position</label>
                              <div class="info-value"><?php echo !empty($user['position']) ? htmlspecialchars($user['position']) : '-'; ?></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>



  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Dashboard JavaScript -->
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js"></script>
  <script src="../../../../public/assets/js/shared/profile-image-cropper.js"></script>
  <script src="../../../../public/assets/js/office_staff/profile.js"></script>
  
  <!-- Profile JavaScript -->
  

  
</body>
</html>
