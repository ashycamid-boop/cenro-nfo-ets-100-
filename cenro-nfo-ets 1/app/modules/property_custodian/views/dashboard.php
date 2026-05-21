<?php require_once __DIR__ . '/../controllers/dashboard_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/dashboard.css?v=20260315-5">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/property_custodian/dashboard.css?v=20260320-1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page property-custodian-dashboard-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="roleDashboardSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role">Property Custodian</div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li class="active"><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="equipment_management.php"><i class="fa fa-cogs"></i> Equipment Management</a></li>
          <li><a href="assignments.php"><i class="fa fa-tasks"></i> Assignments</a></li>
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" id="serviceDeskToggle" data-target="serviceDeskMenu">
              <i class="fa fa-headset"></i> Service Desk 
              <i class="fa fa-chevron-down dropdown-arrow"></i>
            </a>
            <ul class="dropdown-menu" id="serviceDeskMenu">
              <li><a href="new_requests.php">New Requests</a></li>
              <li><a href="ongoing_scheduled.php">Ongoing / Scheduled</a></li>
              <li><a href="completed.php">Completed</a></li>
              <li><a href="all_requests.php">All Requests</a></li>
            </ul>
          </li>
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
          <div class="row mb-4 pc-dashboard-stats-row">
            <!-- Total Equipment -->
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden pc-dashboard-stat-card" style="border-radius: 15px !important;">
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 1px;">TOTAL EQUIPMENT</div>
                      <h2 class="mb-0 fw-bold" style="color: #2c3e50;"><span id="totalEquipmentCount">0</span></h2>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: linear-gradient(135deg, #4285f4, #34a853);">
                      <i class="fa fa-cogs text-white" style="font-size: 20px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Assigned Devices -->
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden pc-dashboard-stat-card" style="border-radius: 15px !important;">
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 1px;">ASSIGNED DEVICES</div>
                      <h2 class="mb-0 fw-bold" style="color: #2c3e50;"><span id="assignedDevicesCount">0</span></h2>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: linear-gradient(135deg, #00d4aa, #00b894);">
                      <i class="fa fa-check text-white" style="font-size: 20px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pending Requests -->
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden pc-dashboard-stat-card" style="border-radius: 15px !important;">
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 1px;">PENDING REQUESTS</div>
                      <h2 class="mb-0 fw-bold" style="color: #2c3e50;"><span id="pendingRequestsCount">0</span></h2>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffa726, #ff9800);">
                      <i class="fa fa-clock text-white" style="font-size: 20px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Available Equipment -->
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden pc-dashboard-stat-card" style="border-radius: 15px !important;">
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 1px;">AVAILABLE EQUIPMENT</div>
                      <h2 class="mb-0 fw-bold" style="color: #2c3e50;"><span id="availableEquipmentCount">0</span></h2>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: linear-gradient(135deg, #9c27b0, #8e24aa);">
                      <i class="fa fa-box text-white" style="font-size: 20px;"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Charts Section -->
          <div class="row pc-dashboard-charts-row">
            <!-- Current Requests by Department -->
            <div class="col-lg-7 mb-4">
              <div class="card h-100 border-0 shadow-sm pc-dashboard-chart-card" style="border-radius: 15px !important;">
                <div class="card-header bg-white border-0 pb-0">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                      <i class="fa fa-chart-bar text-muted me-2"></i>
                      <h6 class="mb-0 fw-bold">Current Requests by Department</h6>
                    </div>
                    <small class="text-muted">As of Today</small>
                  </div>
                </div>
                <div class="card-body">
                  <div class="pc-dashboard-chart-frame pc-dashboard-chart-frame-wide" style="height: 350px; position: relative;">
                    <canvas id="departmentRequestsChart"></canvas>
                  </div>
                </div>
              </div>
            </div>

            <!-- Current Equipment Status -->
            <div class="col-lg-5 mb-4">
              <div class="card h-100 border-0 shadow-sm pc-dashboard-chart-card" style="border-radius: 15px !important;">
                <div class="card-header bg-white border-0 pb-0">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                      <i class="fa fa-chart-pie text-muted me-2"></i>
                      <h6 class="mb-0 fw-bold">Current Equipment Status</h6>
                    </div>
                    <small class="text-muted">Real-time Status</small>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row align-items-center pc-dashboard-status-layout">
                    <!-- Pie Chart -->
                    <div class="col-7 pc-dashboard-status-chart-col">
                      <div class="pc-dashboard-chart-frame pc-dashboard-chart-frame-status" style="height: 250px; position: relative;">
                        <canvas id="equipmentStatusChart"></canvas>
                      </div>
                    </div>
                    <!-- Legend -->
                    <div class="col-5 pc-dashboard-status-legend-col">
                      <div class="d-flex flex-column gap-3 pc-dashboard-status-legend">
                        <div class="d-flex align-items-center">
                          <div class="rounded-circle me-3" style="width: 14px; height: 14px; background-color: #0d6efd;"></div>
                          <div>
                            <div class="fw-semibold text-dark" style="font-size: 14px;">Available</div>
                            <small class="text-muted"><span id="legendAvailableCount">0</span> units</small>
                          </div>
                        </div>
                        <div class="d-flex align-items-center">
                          <div class="rounded-circle me-3" style="width: 14px; height: 14px; background-color: #198754;"></div>
                          <div>
                            <div class="fw-semibold text-dark" style="font-size: 14px;">Assigned</div>
                            <small class="text-muted"><span id="legendAssignedCount">0</span> units</small>
                          </div>
                        </div>
                        <div class="d-flex align-items-center">
                          <div class="rounded-circle me-3" style="width: 14px; height: 14px; background-color: #0dcaf0;"></div>
                          <div>
                            <div class="fw-semibold text-dark" style="font-size: 14px;">Returned</div>
                            <small class="text-muted"><span id="legendReturnedCount">0</span> units</small>
                          </div>
                        </div>
                        <div class="d-flex align-items-center">
                          <div class="rounded-circle me-3" style="width: 14px; height: 14px; background-color: #ffc107;"></div>
                          <div>
                            <div class="fw-semibold text-dark" style="font-size: 14px;">Under Maintenance</div>
                            <small class="text-muted"><span id="legendUnderMaintenanceCount">0</span> units</small>
                          </div>
                        </div>
                        <div class="d-flex align-items-center">
                          <div class="rounded-circle me-3" style="width: 14px; height: 14px; background-color: #6f42c1;"></div>
                          <div>
                            <div class="fw-semibold text-dark" style="font-size: 14px;">Missing</div>
                            <small class="text-muted"><span id="legendMissingCount">0</span> units</small>
                          </div>
                        </div>
                        <div class="d-flex align-items-center">
                          <div class="rounded-circle me-3" style="width: 14px; height: 14px; background-color: #dc3545;"></div>
                          <div>
                            <div class="fw-semibold text-dark" style="font-size: 14px;">Damaged</div>
                            <small class="text-muted"><span id="legendDamagedCount">0</span> units</small>
                          </div>
                        </div>
                        <div class="d-flex align-items-center">
                          <div class="rounded-circle me-3" style="width: 14px; height: 14px; background-color: #6c757d;"></div>
                          <div>
                            <div class="fw-semibold text-dark" style="font-size: 14px;">Out of Service</div>
                            <small class="text-muted"><span id="legendOutOfServiceCount">0</span> units</small>
                          </div>
                        </div>
                      </div>
                    </div>
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
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260320-1"></script>
  
  <!-- Equipment Service (used to fetch equipment list for counts) -->
  <script src="../../../../public/assets/js/admin/equipment-service.js"></script>
  <script src="../../../../public/assets/js/property_custodian/dashboard.js?v=20260520-missing-purple"></script>
</body>
</html>
