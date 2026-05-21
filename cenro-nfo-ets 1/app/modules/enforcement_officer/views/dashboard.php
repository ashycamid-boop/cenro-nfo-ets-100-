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
  <link rel="stylesheet" href="../../../../public/assets/css/modules/enforcement_officer/dashboard.css?v=20260320-1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page enforcement-officer-dashboard-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="roleDashboardSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo htmlspecialchars((string)($sidebarRole ?? 'Enforcement Officer'), ENT_QUOTES, 'UTF-8'); ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li class="active"><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_spot_report_sidebar_badge(); ?></a></li>
          <li><a href="case_management.php"><i class="fa fa-briefcase"></i> Case Management</a></li>
          <li><a href="apprehended_items.php"><i class="fa fa-archive"></i> Apprehended Items</a></li>
          <li><a href="service_requests.php"><i class="fa fa-cog"></i> Service Requests</a></li>
          <li><a href="statistical_report.php"><i class="fa fa-chart-bar"></i> Statistical Report</a></li>
        </ul>
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
          <!-- Top Statistics Cards -->
          <div class="row mb-4 eo-dashboard-stats-row">
            <!-- Spot Reports -->
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden eo-dashboard-stat-card" style="border-radius: 15px !important; transition: all 0.3s ease;">
                <div class="card-body p-4 position-relative">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <h6 class="text-muted mb-1 fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">SPOT REPORTS</h6>
                      <h1 class="mb-0 fw-bold" style="font-size: 2.5rem; color: #2c3e50;"><span id="spotReportsCount">â€”</span></h1>
                    </div>
                    <div class="rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                      <i class="fa fa-file-alt text-white" style="font-size: 24px;"></i>
                    </div>
                  </div>
                  <div class="mt-3">
                    <div class="d-flex align-items-center mb-2">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #28a745;"></div>
                      <small class="text-muted">Approved: <span id="spotApprovedCount" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #ffc107;"></div>
                      <small class="text-muted">Pending: <span id="spotPendingCount" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #dc3545;"></div>
                      <small class="text-muted">Rejected: <span id="spotRejectedCount" class="fw-bold text-dark">â€”</span></small>
                    </div>
                  </div>
                  <div class="position-absolute" style="top: -20px; right: -20px; width: 100px; height: 100px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 50%; opacity: 0.1;"></div>
                </div>
              </div>
            </div>

            <!-- Case Management -->
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden eo-dashboard-stat-card" style="border-radius: 15px !important; transition: all 0.3s ease;">
                <div class="card-body p-4 position-relative">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <h6 class="text-muted mb-1 fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">CASE MANAGEMENT</h6>
                      <h1 class="mb-0 fw-bold" style="font-size: 2.5rem; color: #2c3e50;"><span id="caseManagementCount">â€”</span></h1>
                    </div>
                    <div class="rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                      <i class="fa fa-briefcase text-white" style="font-size: 24px;"></i>
                    </div>
                  </div>
                    <div class="mt-3">
                    <div class="d-flex align-items-center mb-1">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #0d6efd;"></div>
                      <small class="text-muted">Under Investigation: <span id="caseUnderInvestigation" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #ffc107;"></div>
                      <small class="text-muted">Pending Review: <span id="casePendingReview" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #ffc107;"></div>
                      <small class="text-muted">For Filing: <span id="caseForFiling" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #6c757d;"></div>
                      <small class="text-muted">Filed in Court: <span id="caseFiledInCourt" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #0dcaf0;"></div>
                      <small class="text-muted">Ongoing Trial: <span id="caseOnGoing" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #198754;"></div>
                      <small class="text-muted">Resolved: <span id="caseResolved" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #dc3545;"></div>
                      <small class="text-muted">Dismissed: <span id="caseDismissed" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #212529;"></div>
                      <small class="text-muted">Archived: <span id="caseArchived" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-1">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #dc3545;"></div>
                      <small class="text-muted">On Hold: <span id="caseOnHold" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #20c997;"></div>
                      <small class="text-muted">Under Appeal: <span id="caseUnderAppeal" class="fw-bold text-dark">â€”</span></small>
                    </div>
                  </div>
                  <div class="position-absolute" style="top: -20px; right: -20px; width: 100px; height: 100px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 50%; opacity: 0.1;"></div>
                </div>
              </div>
            </div>

            <!-- Apprehended -->
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden eo-dashboard-stat-card" style="border-radius: 15px !important; transition: all 0.3s ease;">
                <div class="card-body p-4 position-relative">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                      <h6 class="text-muted mb-1 fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">APPREHENDED</h6>
                      <h1 class="mb-0 fw-bold" style="font-size: 2.5rem; color: #2c3e50;"><span id="apprehendedCount">â€”</span></h1>
                    </div>
                    <div class="rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                      <i class="fa fa-exclamation-triangle text-white" style="font-size: 24px;"></i>
                    </div>
                  </div>
                  <div class="mt-3">
                    <div class="d-flex align-items-center mb-2">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #fd7e14;"></div>
                      <small class="text-muted">Person: <span id="apprehendedPersonCount" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #dc3545;"></div>
                      <small class="text-muted">Vehicles: <span id="apprehendedVehiclesCount" class="fw-bold text-dark">â€”</span></small>
                    </div>
                    <div class="d-flex align-items-center">
                      <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #6f42c1;"></div>
                      <small class="text-muted">Items: <span id="apprehendedItemsCount" class="fw-bold text-dark">â€”</span></small>
                    </div>
                  </div>
                  <div class="position-absolute" style="top: -20px; right: -20px; width: 100px; height: 100px; background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); border-radius: 50%; opacity: 0.1;"></div>
                </div>
              </div>
            </div>

          </div>

          <!-- Charts Section -->
          <div class="row eo-dashboard-charts-row">
            <!-- Current Spot Reports Status -->
            <div class="col-lg-6 mb-4">
              <div class="card h-100 border-0 shadow-sm eo-dashboard-chart-card">
                <div class="card-header bg-transparent border-0 d-flex align-items-center">
                  <i class="fa fa-chart-bar text-success me-2"></i>
                  <h6 class="mb-0">Current Spot Reports Status</h6>
                </div>
                <div class="card-body">
                  <div class="eo-dashboard-chart-frame eo-dashboard-chart-frame-bar" style="height: 300px; position: relative;">
                    <canvas id="spotReportsChart" style="position: absolute; width: 100%; height: 100%;"></canvas>
                  </div>
                </div>
              </div>
            </div>

            <!-- Case Status Distribution -->
            <div class="col-lg-6 mb-4">
              <div class="card h-100 border-0 shadow-sm eo-dashboard-chart-card">
                <div class="card-header bg-transparent border-0 d-flex align-items-center">
                  <i class="fa fa-chart-pie text-warning me-2"></i>
                  <h6 class="mb-0">Case Status Distribution</h6>
                </div>
                <div class="card-body">
                  <div class="eo-dashboard-chart-frame eo-dashboard-chart-frame-pie" style="height: 300px; position: relative;">
                    <canvas id="caseStatusChart" style="position: absolute; width: 100%; height: 100%;"></canvas>
                  </div>
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
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Chart.js DataLabels Plugin -->
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260315-6"></script>  <!-- Dashboard functionality -->
  <script src="../../../../public/assets/js/enforcement_officer/dashboard.js"></script>
</body>
</html>
