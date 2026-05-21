<?php require_once __DIR__ . '/../controllers/statistical_report_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistical Report</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Chart.js for data visualization -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <!-- Chart.js DataLabels Plugin for percentage display -->
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Statistical Report specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/statistical_report.css?v=20260521-pdf-logo">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-statistical-report-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="adminStatisticalReportSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo htmlspecialchars($sidebarRole ?? 'Administrator', ENT_QUOTES, 'UTF-8'); ?></div>
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
          <li class="active"><a href="statistical_report.php"><i class="fa fa-chart-bar"></i> Statistical Report</a></li>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar topbar-header">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminStatisticalReportSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Statistical Report</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <!-- Top Controls -->
      <div class="topbar">
        <div class="topbar-card"> 
          <div class="controls">
            <label>From:</label>
            <input type="month" id="from">
            <label>To:</label>
            <input type="month" id="to">
            <label>Granularity:</label>
            <select id="granularity">
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="yearly">Yearly</option>
            </select>
            <button id="generate">Generate</button>
            <div class="toggle">
              <input type="checkbox" id="showAll">
              <label for="showAll">Show All</label>
            </div>
            <button id="printBtn">Print</button>
            <button id="exportPdf">Export PDF</button>
            <button id="exportCsv">Export Excel</button>
          </div>
        </div>
      </div>
      <div class="main-content">
        <!-- Statistical Report Content -->
        <div class="report-content">
          <div id="printMeta" aria-hidden="true">
            <div class="print-brand">
              <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
              <div class="print-brand-center">
                <div class="line1">Department of Environment and Natural Resources</div>
                <div class="line2">Kagawaran ng Kapaligiran at Likas Yaman</div>
                <div class="line2">Caraga Region</div>
                <div class="line3">CENRO Nasipit, Agusan del Norte</div>
              </div>
              <img src="../../../../public/assets/images/bagong-pilipinas-logo.png" alt="Bagong Pilipinas Logo">
            </div>
            <p class="print-title">CENRO Nasipit - Statistical Report</p>
            <p class="print-sub" id="printMetaDetails"></p>
          </div>

          <!-- Tabs (hidden when Show All) -->
          <div class="tabs" id="tabs">
            <div class="tabbar" id="tabbar">
              <div class="tab active" data-tab="spot">Spot Reports</div>
              <div class="tab" data-tab="cases">Case Management</div>
              <div class="tab" data-tab="app_individuals">Apprehended Individuals</div>
              <div class="tab" data-tab="app_vehicles">Apprehended Vehicles</div>
              <div class="tab" data-tab="app_items">Apprehended Items</div>
              <div class="tab" data-tab="equipment">Equipment Management</div>
              <div class="tab" data-tab="assignments">Assignments</div>
              <div class="tab" data-tab="service_desk">Service Desk</div>
            </div>
          </div>

          <!-- KPIs -->
          <div class="kpis" id="kpis"></div>

          <!-- Grid or Sections (JS will render here) -->
          <div id="host"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Dashboard JavaScript -->
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260320-1"></script>
  <script src="../../../../public/assets/js/admin/statistical_report.js?v=20260521-pdf-logo"></script>
</body>
</html>
