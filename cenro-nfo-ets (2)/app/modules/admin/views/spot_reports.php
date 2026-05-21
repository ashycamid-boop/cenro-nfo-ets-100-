<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ' . app_url('index.php'));
    exit;
}

$sidebarRole = (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin')
    ? 'Administrator'
    : (isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Administrator');

?>
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
  <!-- Spot Reports specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/spot_reports.css?v=20260319-2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-spot-reports-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="adminSpotReportsSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo $sidebarRole; ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="user_management.php"><i class="fa fa-users"></i> User Management</a></li>
          <li class="active"><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_spot_report_sidebar_badge(); ?></a></li>
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
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminSpotReportsSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Spot Reports</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <!-- Spot Reports Content -->
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

          <!-- Search and Filter Section -->
          <div class="filter-section mb-4 sr-filters-desktop">
            <div class="row g-3 align-items-end">
              <div class="col-12 col-md-3">
                <input type="text" class="form-control" placeholder="Search" id="searchInput">
              </div>
              <div class="col-12 d-md-none">
                <button class="btn btn-outline-secondary w-100 mobile-filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#spotReportMobileFiltersAdmin" aria-expanded="false" aria-controls="spotReportMobileFiltersAdmin">
                  <i class="fa fa-sliders-h me-2"></i>More Filters
                </button>
              </div>
              <div class="col-12 col-md-9">
                <div class="collapse d-md-block" id="spotReportMobileFiltersAdmin">
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
          <?php
require_once dirname(__DIR__, 3) . '/config/app.php';
          try {
            require_once dirname(dirname(dirname(__DIR__))) . '/config/db.php'; // loads $pdo
            $stmt = $pdo->prepare("SELECT s.id, s.reference_no, s.incident_datetime, s.location, s.summary, s.team_leader, s.custodian, s.status, s.status_comment, u.full_name AS submitted_by_name, (SELECT SUM(value) FROM spot_report_items WHERE report_id = s.id) AS est_value FROM spot_reports s LEFT JOIN users u ON u.id = s.submitted_by WHERE u.role = 'Enforcer' ORDER BY s.created_at DESC");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (Exception $e) {
            $rows = array();
          }

          function short_text($s, $len = 120) {
            $s = trim(strip_tags((string)$s));
            if (mb_strlen($s) <= $len) return $s;
            return mb_substr($s, 0, $len) . '...';
          }

          $totalReports = count($rows);
          $totalEst = 0.0;
          foreach ($rows as $r) {
            $estRaw = isset($r['est_value']) ? $r['est_value'] : null;
            $totalEst += ($estRaw !== null && $estRaw !== '') ? (float)$estRaw : 0.0;
          }
          $totalEstFormatted = $totalReports > 0 ? 'â‚± ' . number_format($totalEst, 2) : '-';
          $totalEstFormatted = $totalReports > 0 ? '&#8369; ' . number_format($totalEst, 2) : '-';
          ?>

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
                      <!-- Actions column removed -->
                    </tr>
                  </thead>
                  <tbody>
                    <?php
require_once dirname(__DIR__, 3) . '/config/app.php';
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
                        $badgeClass = 'bg-secondary';
                        if ($statusRaw === 'approved') $badgeClass = 'bg-success';
                        elseif ($statusRaw === 'pending') $badgeClass = 'bg-warning';
                        elseif ($statusRaw === 'rejected') $badgeClass = 'bg-danger';
                        elseif ($statusRaw === 'under_review' || $statusRaw === 'under review') $badgeClass = 'bg-info';
                        $status = htmlspecialchars($r['status'] ?? '');
                        $statusComment = isset($r['status_comment']) ? $r['status_comment'] : '';
                        $estRaw = isset($r['est_value']) ? $r['est_value'] : null;
                        $est = ($estRaw !== null && $estRaw !== '') ? ('â‚± ' . number_format((float)$estRaw, 2)) : '-';
                        $viewUrl = 'view_spot_report.php?ref=' . urlencode($r['reference_no']);
                        if ($estRaw !== null && $estRaw !== '') {
                          $est = '&#8369; ' . number_format((float)$estRaw, 2);
                        }
                        echo "<tr>\n";
                        echo "  <td data-label=\"Ref #\"><a href=\"$viewUrl\">$ref</a></td>\n";
                        echo "  <td data-label=\"Incident Date\">$inc</td>\n";
                        echo "  <td data-label=\"Location\">$loc</td>\n";
                        echo "  <td data-label=\"Items\">$sum</td>\n";
                        echo "  <td data-label=\"Team Leader\">$tl</td>\n";
                        echo "  <td data-label=\"Member\">$cust</td>\n";
                        echo "  <td data-label=\"Submitted By\">$submittedBy</td>\n";
                        // If there's a status comment, show a small '?' button next to the badge
                        $commentHtml = '';
                        if (!empty($statusComment)) {
                          $commentAttr = htmlspecialchars($statusComment, ENT_QUOTES);
                          $commentHtml = " <button type=\"button\" class=\"status-comment-btn\" data-comment=\"{$commentAttr}\" title=\"View comment\"><i class=\"fa fa-question-circle\"></i></button>";
                        }
                        echo "  <td data-label=\"Status\"><span class=\"badge $badgeClass\">$status</span>$commentHtml</td>\n";
                        echo "  <td data-label=\"Est. Value\">$est</td>\n";
                        echo "  <td data-label=\"Details\"><a class=\"btn btn-sm btn-outline-primary\" href=\"$viewUrl\">Details</a></td>\n";
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

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260315-6"></script>
  <!-- Spot Reports Action Functionality -->
  <script src="../../../../public/assets/js/admin/spot_reports.js?v=20260404-1"></script>

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
            <input type="text" class="form-control" placeholder="Search" id="searchInputModal">
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
          <button class="btn btn-outline-secondary" type="button" id="clearFilterMobile">Clear All</button>
          <button class="btn btn-primary" type="button" id="applyFilterMobile" data-bs-dismiss="modal">Apply Filters</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
