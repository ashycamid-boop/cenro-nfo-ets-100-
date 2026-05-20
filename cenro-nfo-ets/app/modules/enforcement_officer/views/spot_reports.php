<?php require_once __DIR__ . '/../controllers/spot_reports_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Spot Reports - CENRO NASIPIT</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Spot Reports specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/spot_reports.css?v=20260315-1">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/enforcement_officer/spot_reports.css?v=20260315-1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-spot-reports-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="officerSpotReportsSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role">Enforcement Officer</div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li class="active"><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_spot_report_sidebar_badge(); ?></a></li>
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
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="officerSpotReportsSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Spot Report</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <!-- Spot Reports Content -->
        <div class="container-fluid p-4">
          <!-- Search and Filter Section -->
          <div class="filter-section mb-4">
            <div class="row g-3 align-items-end">
              <div class="col-12 col-md-3">
                <input type="text" class="form-control" placeholder="Search" id="searchInput">
              </div>
              <div class="col-12 d-md-none">
                <button class="btn btn-outline-secondary w-100 mobile-filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#spotReportMobileFiltersOfficer" aria-expanded="false" aria-controls="spotReportMobileFiltersOfficer">
                  <i class="fa fa-sliders-h me-2"></i>More Filters
                </button>
              </div>
              <div class="col-12 col-md-9">
                <div class="collapse d-md-block" id="spotReportMobileFiltersOfficer">
                  <div class="row g-3 align-items-end mobile-filter-panel">
                    <div class="col-12 col-md-3">
                      <input type="date" class="form-control" placeholder="dd/mm/yyyy" id="dateFrom">
                    </div>
                    <div class="col-12 col-md-3">
                      <input type="date" class="form-control" placeholder="dd/mm/yyyy" id="dateTo">
                    </div>
                    <div class="col-12 col-md-3">
                      <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                      </select>
                    </div>
                    <div class="col-6 col-md-2">
                      <button class="btn btn-primary w-100" id="applyFilter">
                        <i class="fa fa-filter"></i> Apply
                      </button>
                    </div>
                    <div class="col-6 col-md-1">
                      <button class="btn btn-outline-secondary w-100" id="clearFilter">Clear</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Summary Cards -->

          <div class="row mb-3">
            <div class="col-md-6 mb-3">
              <div class="summary-card">
                <div class="summary-label">Total</div>
                <div id="summaryTotal" class="summary-value"><?php echo $totalReports; ?></div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="summary-card">
                <div class="summary-label">Est. Value</div>
                <div id="summaryEst" class="summary-value"><?php echo $totalEstFormatted; ?></div>
              </div>
            </div>
          </div>

          <!-- Reports Table -->
          <div class="card">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Ref #</th>
                      <th>Incident Date</th>
                      <th>Location</th>
                      <th>Items</th>
                      <th>Team Leader</th>
                      <th>Member</th>
                      <th>Submitted By</th>
                      <th>Status</th>
                      <th>Est. Value</th>
                      <th>Details</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (!empty($rows)) {
                      foreach ($rows as $r) {
                        $ref = htmlspecialchars($r['reference_no']);
                        $inc = $r['incident_datetime'] ? htmlspecialchars($r['incident_datetime']) : '-';
                        $loc = htmlspecialchars($r['location'] ?? '');
                        $sum = htmlspecialchars(short_text($r['summary'] ?? ''));
                        $tl = htmlspecialchars($r['team_leader'] ?? '');
                        $cust = htmlspecialchars($r['custodian'] ?? '');
                        $submittedBy = htmlspecialchars($r['submitted_by_name'] ?? '-');
                        $statusRaw = strtolower(trim($r['status'] ?? ''));
                        $commentRaw = isset($r['status_comment']) ? $r['status_comment'] : '';
                        $badgeClass = 'bg-secondary';
                        if ($statusRaw === 'approved') $badgeClass = 'bg-success';
                        elseif ($statusRaw === 'pending') $badgeClass = 'bg-warning';
                        elseif ($statusRaw === 'rejected') $badgeClass = 'bg-danger';
                        elseif ($statusRaw === 'under_review' || $statusRaw === 'under review') $badgeClass = 'bg-info';
                        $status = htmlspecialchars($r['status'] ?? '');
                        $commentAttr = htmlspecialchars($commentRaw, ENT_QUOTES);
                        $estRaw = isset($r['est_value']) ? $r['est_value'] : null;
                        $est = ($estRaw !== null && $estRaw !== '') ? ('&#8369; ' . number_format((float)$estRaw, 2)) : '-';
                        $viewUrl = 'view_spot_report.php?ref=' . urlencode($r['reference_no']);
                        echo "<tr>\n";
                        echo "  <td data-label=\"Ref #\"><a href=\"$viewUrl\">$ref</a></td>\n";
                        echo "  <td data-label=\"Incident Date\">$inc</td>\n";
                        echo "  <td data-label=\"Location\">$loc</td>\n";
                        echo "  <td data-label=\"Items\">$sum</td>\n";
                        echo "  <td data-label=\"Team Leader\">$tl</td>\n";
                        echo "  <td data-label=\"Member\">$cust</td>\n";
                        echo "  <td data-label=\"Submitted By\">$submittedBy</td>\n";
                        $statusHtml = "<span class=\"badge $badgeClass\">$status</span>";
                        if (strpos(strtolower($status), 'rejected') !== false && $commentRaw !== '') {
                          $statusHtml .= " <button type=\"button\" class=\"status-comment-btn\" data-comment=\"{$commentAttr}\" title=\"View comment\" style=\"display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;margin-left:8px;vertical-align:middle;border:1px solid #ced4da;border-radius:6px;background:#fff;color:#495057;padding:0;\"><i class=\"fa fa-question-circle\"></i></button>";
                        }
                        echo "  <td data-label=\"Status\">$statusHtml</td>\n";
                        echo "  <td data-label=\"Est. Value\">$est</td>\n";
                        echo "  <td data-label=\"Details\"><a class=\"btn btn-sm btn-outline-primary\" href=\"$viewUrl\">Details</a></td>\n";
                        echo "  <td data-label=\"Actions\"><button class=\"btn btn-sm btn-outline-secondary\" onclick=\"editSpotReportStatus('$ref')\">Edit</button></td>\n";
                        echo "</tr>\n";
                      }
                    } else {
                      echo '<tr><td colspan="11" class="text-center">No spot reports found.</td></tr>';
                    }
                    ?>
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
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260315-6"></script>
  <!-- Spot Reports Action Functionality -->
  <script src="../../../../public/assets/js/enforcement_officer/spot_reports.js?v=20260315-2"></script>
</body>
</html>
