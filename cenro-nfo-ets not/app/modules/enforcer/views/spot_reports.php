<?php require_once __DIR__ . '/../controllers/spot_reports_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Spot Reports</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Dashboard specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/dashboard.css?v=20260320-1">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/spot_reports.css?v=20260319-2">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/enforcer/spot_reports.css?v=20260320-2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-spot-reports-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="enforcerSpotReportsSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo htmlspecialchars((string)($sidebarRole ?? 'Enforcer'), ENT_QUOTES, 'UTF-8'); ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li class="active"><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_enforcer_spot_report_sidebar_badge(); ?></a></li>
          <li><a href="service_requests.php"><i class="fa fa-cog"></i> Service Requests</a></li>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="enforcerSpotReportsSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Spot Reports</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid p-4">
          <div class="sr-mobile-toolbar">
            <div class="sr-mobile-search-wrap">
              <i class="fa fa-search sr-mobile-search-icon" aria-hidden="true"></i>
              <input type="text" class="form-control sr-mobile-search" placeholder="Search" id="searchInputMobile">
            </div>
            <button class="btn sr-mobile-filter-btn" type="button" data-bs-toggle="modal" data-bs-target="#spotReportsFiltersModal">
              <i class="fa fa-sliders-h me-2"></i>Filters
            </button>
          </div>

          <div class="sr-active-filters" id="spotReportsActiveFilters" aria-label="Active filters"></div>

          <!-- Filter Controls -->
          <div class="filter-section mb-4 sr-filters-desktop">
            <div class="row g-3 align-items-end">
              <div class="col-12 col-md-3">
                <input id="searchInput" type="text" class="form-control spot-filter-control" placeholder="Search">
              </div>
              <div class="col-12 d-md-none">
                <button class="btn btn-outline-secondary w-100 mobile-filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#enforcerSpotReportMobileFilters" aria-expanded="false" aria-controls="enforcerSpotReportMobileFilters">
                  <i class="fa fa-sliders-h me-2"></i>More Filters
                </button>
              </div>
              <div class="col-12 col-md-9">
                <div class="collapse d-md-block" id="enforcerSpotReportMobileFilters">
                  <div class="row g-3 align-items-end mobile-filter-panel">
                    <div class="col-12 col-md-3">
                      <input id="dateFrom" type="date" class="form-control spot-filter-control" placeholder="dd/mm/yyyy">
                    </div>
                    <div class="col-12 col-md-3">
                      <input id="dateTo" type="date" class="form-control spot-filter-control" placeholder="dd/mm/yyyy">
                    </div>
                    <div class="col-12 col-md-3">
                      <select id="statusFilter" class="form-select spot-filter-control">
                        <option value="">All Status</option>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                      </select>
                    </div>
                    <div class="col-6 col-md-2">
                      <button id="applyFilter" class="btn btn-primary w-100 spot-btn-apply">Apply</button>
                    </div>
                    <div class="col-6 col-md-1">
                      <button id="clearFilter" class="btn btn-outline-secondary w-100 spot-btn-clear">Clear</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- New Spot Report Button -->
          <div class="row mb-3">
            <div class="col-12 d-flex justify-content-end">
              <a href="new_spot_report.php" class="btn btn-primary spot-btn-new-report">
                New Spot Report
              </a>
            </div>
          </div>

          <!-- Data Table -->
          <div class="row">
            <div class="col-12">
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
                          <th>Check</th>
                        </tr>
                      </thead>
                      <tbody>
<?php
if (!empty($rows)) {
  foreach ($rows as $r) {
    $ref = htmlspecialchars($r['reference_no']);
    $inc = $r['incident_datetime'] ? htmlspecialchars($r['incident_datetime']) : '-';
    $loc = htmlspecialchars($r['location'] ?? '');
    $sum = htmlspecialchars(enforcer_spot_short($r['summary'] ?? ''));
    $tl = htmlspecialchars($r['team_leader'] ?? '');
    $cust = htmlspecialchars($r['custodian'] ?? '');
    $submittedBy = htmlspecialchars($r['submitted_by_name'] ?? '-');
    $stRaw = $r['status'] ?? '';
    $status = htmlspecialchars($stRaw);
    $commentRaw = isset($r['status_comment']) ? $r['status_comment'] : '';
    $commentAttr = htmlspecialchars($commentRaw, ENT_QUOTES);
    $badge = 'secondary';
    $stTrim = trim($stRaw);
    if (strcasecmp($stTrim, 'Draft') === 0 || strcasecmp($stTrim, 'Pending') === 0) $badge = 'warning';
    elseif (strcasecmp($stTrim, 'Approved') === 0) $badge = 'success';
    elseif (strcasecmp($stTrim, 'Rejected') === 0) $badge = 'danger';
    elseif (strcasecmp($stTrim, 'Under Review') === 0 || strcasecmp($stTrim, 'Under_Review') === 0) $badge = 'info';
    // Map to a specific CSS class for custom palette
    $statusClass = 'status-secondary';
    if ($badge === 'success') $statusClass = 'status-approved';
    elseif ($badge === 'warning') $statusClass = 'status-warning';
    elseif ($badge === 'danger') $statusClass = 'status-danger';
    elseif ($badge === 'info') $statusClass = 'status-info';
    $estRaw = isset($r['est_value']) ? $r['est_value'] : null;
    $est = ($estRaw !== null && $estRaw !== '') ? ('₱ ' . number_format((float)$estRaw, 2)) : '-';
    $viewUrl = 'view_spot_report.php?ref=' . urlencode($r['reference_no']);
    echo "<tr>\n";
    echo "  <td data-label=\"Ref #\"><a href=\"$viewUrl\">$ref</a></td>\n";
    echo "  <td data-label=\"Incident Date\">$inc</td>\n";
    echo "  <td data-label=\"Location\">$loc</td>\n";
    // Display summary only in the 'Items' column as requested
    echo "  <td data-label=\"Items\">$sum</td>\n";
    echo "  <td data-label=\"Team Leader\">$tl</td>\n";
    echo "  <td data-label=\"Member\">$cust</td>\n";
    echo "  <td data-label=\"Submitted By\">" . $submittedBy . "</td>\n";
    // Show question icon when rejected and a comment exists
    $statusCellHtml = "<span class=\"badge bg-{$badge} fs-6\">$status</span>";
    if (strcasecmp(trim($stRaw), 'Rejected') === 0 && $commentRaw !== '') {
      $statusCellHtml .= " <button type=\"button\" class=\"status-comment-btn\" data-comment=\"{$commentAttr}\" title=\"View comment\" style=\"display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;margin-left:8px;vertical-align:middle;border:1px solid #ced4da;border-radius:6px;background:#fff;color:#495057;padding:0;\"><i class=\"fa fa-question-circle\"></i></button>";
    }
    echo "  <td data-label=\"Status\">$statusCellHtml</td>\n";
    echo "  <td data-label=\"Est. Value\">$est</td>\n";
    echo "  <td data-label=\"Check\"><a class=\"btn btn-sm btn-outline-primary\" href=\"$viewUrl\">Open</a></td>\n";
    echo "</tr>\n";
  }
} else {
  echo '<tr><td colspan="10" class="text-center">No spot reports found.</td></tr>';
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
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260320-1"></script>
  <script src="../../../../public/assets/js/enforcer/spot_reports.js?v=20260320-1"></script>

  <div class="modal fade" id="spotReportsFiltersModal" tabindex="-1" aria-labelledby="spotReportsFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable sr-filters-modal-dialog">
      <div class="modal-content sr-filters-modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="spotReportsFiltersModalLabel">Filter Spot Reports</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="sr-modal-field">
            <label for="searchInputModal" class="form-label">Search</label>
            <input type="text" class="form-control" id="searchInputModal" placeholder="Search">
          </div>
          <div class="sr-modal-field">
            <label for="dateFromModal" class="form-label">Date From</label>
            <input type="date" class="form-control" id="dateFromModal">
          </div>
          <div class="sr-modal-field">
            <label for="dateToModal" class="form-label">Date To</label>
            <input type="date" class="form-control" id="dateToModal">
          </div>
          <div class="sr-modal-field">
            <label for="statusFilterModal" class="form-label">Status</label>
            <select class="form-select" id="statusFilterModal">
              <option value="">All Status</option>
              <option value="approved">Approved</option>
              <option value="pending">Pending</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>
        <div class="modal-footer sr-filters-modal-footer">
          <button type="button" id="clearFilterMobile" class="btn btn-outline-secondary">Clear All</button>
          <button type="button" id="applyFilterMobile" class="btn btn-primary" data-bs-dismiss="modal">Apply Filters</button>
        </div>
      </div>
    </div>
  </div>
  </body>
</html>
