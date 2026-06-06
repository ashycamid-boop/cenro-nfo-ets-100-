<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();
require_once __DIR__ . '/../../../../app/config/db.php';
require_once __DIR__ . '/../../../../app/helpers/profile_image_helper.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ' . app_url('index.php'));
    exit;
}

// --- REPLACED START: robust session / user selection logic ---
// Accept the session keys used by the app (login.php sets `uid`, `email`, `full_name`)
$sessionUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$sessionUserEmail = $_SESSION['email'] ?? null;
$sessionFullName = $_SESSION['full_name'] ?? null;

// Load logged-in user's full row early (if available). If DB fetch fails, fall back to session values.
$loggedInUser = null;
if (!empty($sessionUserId)) {
  try {
    $stmt = $pdo->prepare('SELECT id, email, full_name, contact_number, office_unit, position, profile_picture, role, status, created_at, updated_at, last_login FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$sessionUserId]);
    $loggedInUser = $stmt->fetch();
  } catch (Exception $e) {
    $loggedInUser = false;
  }

  // If DB query returned nothing, use minimal info from session so topbar/profile can still show name/email
  if (!$loggedInUser || empty($loggedInUser['id'])) {
    $loggedInUser = [
      'id' => (int)$sessionUserId,
      'email' => $sessionUserEmail ?? '',
      'full_name' => $sessionFullName ?? 'User',
      'contact_number' => '',
      'office_unit' => '',
      'position' => '',
      'profile_picture' => $_SESSION['profile_picture'] ?? '',
      'role' => $_SESSION['role'] ?? '',
      'status' => $_SESSION['status'] ?? 0,
    ];
  }
}

// Determine which user to show: ?id= or logged-in user
$viewUserId = null;
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
  $viewUserId = (int) $_GET['id'];
} elseif (!empty($loggedInUser['id'])) {
  $viewUserId = (int) $loggedInUser['id'];
}

$user = null;
$notFound = false;
if ($viewUserId) {
  try {
    // include updated_at and last_login so profile displays all DB fields
    $stmt = $pdo->prepare('SELECT id, email, full_name, contact_number, office_unit, position, profile_picture, role, status, created_at, updated_at, last_login FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$viewUserId]);
    $user = $stmt->fetch();
  } catch (Exception $e) {
    $user = false;
  }
}

// If requested user not found but we have a logged-in user, show logged-in user's profile instead
if (!$user && !empty($loggedInUser['id'])) {
  $user = $loggedInUser;
  $notFound = false;
  $viewUserId = (int)$loggedInUser['id'];
}

// If still no user, show placeholder
if (!$user) {
  $notFound = true;
  $user = [
    'id' => '',
    'email' => '',
    'full_name' => 'User not found',
    'contact_number' => '',
    'office_unit' => '',
    'position' => '',
    'profile_picture' => '',
    'role' => '',
    'status' => 0,
    'created_at' => null,
  ];
}

// Determine ownership
$isOwnProfile = false;
if (!empty($loggedInUser['id']) && $viewUserId && ((int)$loggedInUser['id'] === (int)$viewUserId)) {
  $isOwnProfile = true;
}
// --- REPLACED END ---

$profileMessage = '';
$profileError = '';

if (!function_exists('formatRoleLabel')) {
  function formatRoleLabel($role) {
    $r = trim((string)$role);
    if (strcasecmp($r, 'Admin') === 0) {
      return 'Administrator';
    }
    return $r;
  }
}

// Handle profile update when the owner submits the form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $isOwnProfile) {
  try {
    // Preserve existing profile_picture unless a new one uploaded
    $profile_picture = null;
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = :id");
    $stmt->execute([':id' => $loggedInUser['id']]);
    $existing = $stmt->fetch();
    $profile_picture = $existing['profile_picture'] ?? null;

    // Handle profile picture upload (optional) - prefer cropped image
    if (!empty($_POST['cropped_profile_picture'])) {
      $profile_picture = saveProfileImageFromDataUrl($_POST['cropped_profile_picture'], (int) $loggedInUser['id']);
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
      $uploadedPath = saveUploadedProfileImageFile($_FILES['profile_picture'], (int) $loggedInUser['id']);
      if ($uploadedPath !== null) {
        $profile_picture = $uploadedPath;
      }
    }
    // If this submission is picture-only, update only the profile_picture column
    if (!empty($_POST['profile_only']) && $_POST['profile_only'] == '1') {
      $stmt = $pdo->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :id");
      $stmt->execute([':profile_picture' => $profile_picture, ':id' => $loggedInUser['id']]);
      $profileMessage = 'Profile picture updated successfully.';
    } else {
      // Build full name
      $firstName = trim($_POST['firstName'] ?? '');
      $middleName = trim($_POST['middleName'] ?? '');
      $lastName = trim($_POST['lastName'] ?? '');
      $suffix = trim($_POST['suffix'] ?? '');
      $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName . ' ' . $suffix);
      $fullName = preg_replace('/\s+/', ' ', $fullName);

      // Password update (optional)
      $updatePassword = false;
      if (!empty($_POST['password'])) {
        if ($_POST['password'] !== ($_POST['confirmPassword'] ?? '')) {
          throw new Exception('Passwords do not match.');
        }
        $updatePassword = true;
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
      }

      if ($updatePassword) {
        $stmt = $pdo->prepare("\n        UPDATE users SET full_name = :full_name, email = :email, contact_number = :contact_number,\n        office_unit = :office_unit, profile_picture = :profile_picture, password_hash = :password_hash WHERE id = :id\n      ");
        $stmt->execute([
          ':full_name' => $fullName,
          ':email' => $_POST['email'],
          ':contact_number' => $_POST['contactNumber'] ?? '',
          ':office_unit' => $_POST['officeUnit'] ?? '',
          ':profile_picture' => $profile_picture,
          ':password_hash' => $hashedPassword,
          ':id' => $loggedInUser['id']
        ]);
      } else {
        $stmt = $pdo->prepare("\n        UPDATE users SET full_name = :full_name, email = :email, contact_number = :contact_number,\n        office_unit = :office_unit, profile_picture = :profile_picture WHERE id = :id\n      ");
        $stmt->execute([
          ':full_name' => $fullName,
          ':email' => $_POST['email'],
          ':contact_number' => $_POST['contactNumber'] ?? '',
          ':office_unit' => $_POST['officeUnit'] ?? '',
          ':profile_picture' => $profile_picture,
          ':id' => $loggedInUser['id']
        ]);
      }

      $profileMessage = 'Profile updated successfully.';
    }
    // refresh $user and $loggedInUser display data
    $stmt = $pdo->prepare('SELECT id, email, full_name, contact_number, office_unit, position, profile_picture, role, status, created_at, updated_at, last_login FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$loggedInUser['id']]);
    $user = $stmt->fetch();
    // refresh loggedInUser minimal info
    $stmt = $pdo->prepare('SELECT id, full_name, profile_picture, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$loggedInUser['id']]);
    $loggedInUser = $stmt->fetch();
  } catch (Exception $e) {
    $profileError = 'Failed to update profile: ' . $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Add User specific styles (reuse for profile) -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/add_user.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/profile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-profile-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="adminProfileSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <!-- show logged-in user's role like add_user.php (fallback to viewed user) -->
      <div class="sidebar-role"><?php echo htmlspecialchars(formatRoleLabel(!empty($loggedInUser['role']) ? $loggedInUser['role'] : (!empty($user['role']) ? $user['role'] : ''))); ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="user_management.php"><i class="fa fa-users"></i> User Management</a></li>
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
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminProfileSidebar">
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
require_once dirname(__DIR__, 3) . '/config/app.php';
                            $imgSrc = '../../../../public/assets/images/default-avatar.png';
                            if (!empty($user['profile_picture'])) {
                                $imgSrc = '../../../../' . ltrim($user['profile_picture'], '/');
                            }
                          ?>
                          <img src="<?php echo $imgSrc; ?>" alt="Profile Picture" class="profile-image" id="mainProfileImage">
                        </div>
                        <input type="file" name="profile_picture" id="profile_picture_input" class="profile-file-input" accept="image/png, image/jpeg" onchange="previewProfileAndSubmit(event)">
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
require_once dirname(__DIR__, 3) . '/config/app.php';
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
  <script src="../../../../public/assets/js/admin/profile.js"></script>
</html>
