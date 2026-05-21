<?php require_once __DIR__ . '/../controllers/dashboard_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Dashboard specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/dashboard.css?v=20260315-5">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/enforcer/dashboard.css?v=20260403-1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="roleDashboardSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo htmlspecialchars((string)($sidebarRole ?? 'Enforcer'), ENT_QUOTES, 'UTF-8'); ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li class="active"><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_enforcer_spot_report_sidebar_badge(); ?></a></li>
          <li><a href="service_requests.php"><i class="fa fa-cog"></i> Service Requests</a></li>
        </ul>
      </nav>
    </nav>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="roleDashboardSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Dashboard</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid p-4">
          <!-- Spot Reports Statistics -->
          <div class="row mb-5 g-4">
            <div class="col-lg-3 col-md-6">
              <div class="stats-card primary h-100">
                <div class="p-4 position-relative">
                  <div class="stats-label">Total Spot Reports</div>
                  <div class="stats-number"><?php echo htmlspecialchars((string)$totalSpot); ?></div>
                  <i class="fa fa-file-text stats-icon"></i>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="stats-card success h-100">
                <div class="p-4 position-relative">
                  <div class="stats-label">Approved Spot Reports</div>
                  <div class="stats-number"><?php echo htmlspecialchars((string)$approvedSpot); ?></div>
                  <i class="fa fa-check-circle stats-icon"></i>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="stats-card warning h-100">
                <div class="p-4 position-relative">
                  <div class="stats-label">Pending Spot Reports</div>
                  <div class="stats-number"><?php echo htmlspecialchars((string)$pendingSpot); ?></div>
                  <i class="fa fa-clock stats-icon"></i>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="stats-card danger h-100">
                <div class="p-4 position-relative">
                  <div class="stats-label">Rejected Spot Reports</div>
                  <div class="stats-number"><?php echo htmlspecialchars((string)$rejectedSpot); ?></div>
                  <i class="fa fa-times-circle stats-icon"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Service Requests Statistics -->
          <div class="row mb-5 g-4">
            <div class="col-lg-3 col-md-6">
              <div class="stats-card info h-100">
                <div class="p-4 position-relative">
                  <div class="stats-label">Total Service Requests</div>
                  <div class="stats-number"><?php echo htmlspecialchars((string)$totalReq); ?></div>
                  <i class="fa fa-cog stats-icon"></i>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="stats-card success h-100">
                <div class="p-4 position-relative">
                  <div class="stats-label">Completed Service Requests</div>
                  <div class="stats-number"><?php echo htmlspecialchars((string)$completedReq); ?></div>
                  <i class="fa fa-check stats-icon"></i>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="stats-card warning h-100">
                <div class="p-4 position-relative">
                  <div class="stats-label">Pending Service Requests</div>
                  <div class="stats-number"><?php echo htmlspecialchars((string)$pendingReq); ?></div>
                  <i class="fa fa-hourglass-half stats-icon"></i>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="stats-card info h-100">
                <div class="p-4 position-relative">
                  <div class="stats-label">Ongoing / Scheduled Service Requests</div>
                  <div class="stats-number"><?php echo htmlspecialchars((string)$ongoingScheduledReq); ?></div>
                  <i class="fa fa-calendar-check stats-icon"></i>
                </div>
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
  <script src="../../../../public/assets/js/admin/navigation.js"></script>
  <script src="../../../../public/assets/js/enforcer/dashboard.js"></script>
  </body>
</html>
