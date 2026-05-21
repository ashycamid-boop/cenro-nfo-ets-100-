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
  <title>Case Management</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Case Management specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/case_management.css?v=20260319-2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
  
</head>
<body class="admin-dashboard-page admin-case-management-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="adminCaseManagementSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo $sidebarRole; ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="user_management.php"><i class="fa fa-users"></i> User Management</a></li>
          <li><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_spot_report_sidebar_badge(); ?></a></li>
          <li class="active"><a href="case_management.php"><i class="fa fa-briefcase"></i> Case Management</a></li>
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
            <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminCaseManagementSidebar">
              <i class="fa fa-bars"></i>
            </button>
            <div class="topbar-title">Case Management</div>
            <?php include_once __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        
        </div>
      </div>
      <div class="main-content">
        <!-- Case Management Content -->
        <div class="container-fluid p-4">
          <div class="sr-mobile-toolbar">
            <div class="sr-mobile-search-wrap">
              <i class="fa fa-search sr-mobile-search-icon" aria-hidden="true"></i>
              <input type="text" class="form-control sr-mobile-search" placeholder="Search" id="searchInputMobile">
            </div>
            <button class="btn sr-mobile-filter-btn" type="button" data-bs-toggle="modal" data-bs-target="#caseManagementFiltersModalAdmin">
              <i class="fa fa-sliders-h me-2"></i>Filters
            </button>
          </div>

          <div class="sr-active-filters" id="caseManagementActiveFiltersAdmin" aria-label="Active filters"></div>

          <!-- Search and Filter Section -->
          <div class="filter-section mb-4 sr-filters-desktop">
            <div class="row g-3 align-items-end">
              <div class="col-12 col-md-3">
                <input type="text" class="form-control" placeholder="Search" id="searchInput">
              </div>
              <div class="col-12 d-md-none">
                <button class="btn btn-outline-secondary w-100 mobile-filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#caseManagementMobileFiltersAdmin" aria-expanded="false" aria-controls="caseManagementMobileFiltersAdmin">
                  <i class="fa fa-sliders-h me-2"></i>More Filters
                </button>
              </div>
              <div class="col-12 col-md-9">
                <div class="collapse d-md-block" id="caseManagementMobileFiltersAdmin">
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
                        <option value="under-investigation">Under Investigation</option>
                        <option value="for-filing">For Filing</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="dismissed">Dismissed</option>
                        <option value="resolved">Resolved</option>
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
          // Load approved spot reports and status counts for Case Management
          try {
            require_once dirname(dirname(dirname(__DIR__))) . '/config/db.php'; // loads $pdo

            // Initialize counts for compact status summary (admin)
            $counts = [
              'under-investigation' => 0,
              'pending-review' => 0,
              'for-filing' => 0,
              'filed-in-court' => 0,
              'ongoing-trial' => 0,
              'resolved' => 0,
              'dismissed' => 0,
              'archived' => 0,
              'on-hold' => 0,
              'under-appeal' => 0
            ];

            // Count CASE statuses only for reports that were approved (i.e. promoted to cases)
            $stmtCounts = $pdo->query("SELECT LOWER(TRIM(case_status)) AS status, COUNT(*) AS cnt FROM spot_reports WHERE LOWER(TRIM(status)) = 'approved' GROUP BY LOWER(TRIM(case_status))");
            while ($r = $stmtCounts->fetch(PDO::FETCH_ASSOC)) {
              $s = strtolower(trim($r['status'] ?? ''));
              $c = (int)$r['cnt'];
              if ($s === '') {
                $counts['under-investigation'] += $c;
              } elseif (strpos($s, 'under') !== false && (strpos($s, 'invest') !== false || strpos($s, 'review') === false)) {
                $counts['under-investigation'] += $c;
              } elseif (strpos($s, 'pending') !== false || strpos($s, 'pending review') !== false) {
                $counts['pending-review'] += $c;
              } elseif (strpos($s, 'for filing') !== false || strpos($s, 'for-filing') !== false) {
                $counts['for-filing'] += $c;
              } elseif (strpos($s, 'filed') !== false || strpos($s, 'filed in court') !== false || strpos($s, 'filed-in-court') !== false) {
                $counts['filed-in-court'] += $c;
              } elseif (strpos($s, 'ongoing') !== false || strpos($s, 'trial') !== false) {
                $counts['ongoing-trial'] += $c;
              } elseif (strpos($s, 'dismiss') !== false) {
                $counts['dismissed'] += $c;
              } elseif (strpos($s, 'resolv') !== false || strpos($s, 'resolved') !== false) {
                $counts['resolved'] += $c;
              } elseif (strpos($s, 'archiv') !== false) {
                $counts['archived'] += $c;
              } elseif (strpos($s, 'hold') !== false) {
                $counts['on-hold'] += $c;
              } elseif (strpos($s, 'appeal') !== false) {
                $counts['under-appeal'] += $c;
              } else {
                $counts['under-investigation'] += $c;
              }
            }

            // Fetch approved spot reports to show as cases
            $stmt = $pdo->prepare("SELECT s.id, s.reference_no, s.incident_datetime, s.location, s.team_leader, u.full_name AS submitted_by_name, s.status, s.case_status, (SELECT SUM(value) FROM spot_report_items WHERE report_id = s.id) AS est_value FROM spot_reports s LEFT JOIN users u ON u.id = s.submitted_by WHERE LOWER(TRIM(s.status)) = 'approved' ORDER BY s.created_at DESC");
            $stmt->execute();
            $approvedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } catch (Exception $e) {
            $counts = array_fill_keys(array_keys($counts), 0);
            $approvedRows = [];
          }
          ?>
          <div class="summary-pills mb-3">
            <div class="summary-pill"><div class="pill-label">Under Inv.</div><div class="pill-count" id="count-under-investigation"><?php echo htmlspecialchars($counts['under-investigation']); ?></div></div>
            <div class="summary-pill"><div class="pill-label">Pend. Rev.</div><div class="pill-count" id="count-pending-review"><?php echo htmlspecialchars($counts['pending-review']); ?></div></div>
            <div class="summary-pill"><div class="pill-label">For Filing</div><div class="pill-count" id="count-for-filing"><?php echo htmlspecialchars($counts['for-filing']); ?></div></div>
            <div class="summary-pill"><div class="pill-label">Filed Ct.</div><div class="pill-count" id="count-filed-in-court"><?php echo htmlspecialchars($counts['filed-in-court']); ?></div></div>
            <div class="summary-pill"><div class="pill-label">Ong. Trial</div><div class="pill-count" id="count-ongoing-trial"><?php echo htmlspecialchars($counts['ongoing-trial']); ?></div></div>
            <div class="summary-pill"><div class="pill-label">Resolved</div><div class="pill-count" id="count-resolved"><?php echo htmlspecialchars($counts['resolved']); ?></div></div>
            <div class="summary-pill"><div class="pill-label">Dismissed</div><div class="pill-count" id="count-dismissed"><?php echo htmlspecialchars($counts['dismissed']); ?></div></div>
            <div class="summary-pill"><div class="pill-label">Archived</div><div class="pill-count" id="count-archived"><?php echo htmlspecialchars($counts['archived']); ?></div></div>
            <div class="summary-pill"><div class="pill-label">On Hold</div><div class="pill-count" id="count-on-hold"><?php echo htmlspecialchars($counts['on-hold']); ?></div></div>
            <div class="summary-pill"><div class="pill-label">Under Appeal</div><div class="pill-count" id="count-under-appeal"><?php echo htmlspecialchars($counts['under-appeal']); ?></div></div>
          </div>

          <!-- Cases Table -->
          <div class="card">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Ref No.</th>
                      <th>Incident Date</th>
                      <th>Location</th>
                      <th>Team Leader</th>
                      <th>Submitted By</th>
                      <th>Review</th>
                      <th>Status</th>
                      <th>Est. Value</th>
                      <th>Details</th>
                    </tr>
                  </thead>
                  <tbody id="casesTableBody">
                    <?php
require_once dirname(__DIR__, 3) . '/config/app.php';
                    if (!empty($approvedRows)) {
                      foreach ($approvedRows as $r) {
                        $ref = htmlspecialchars($r['reference_no'] ?? '');
                        if (!empty($r['incident_datetime'])) {
                          try {
                            $dt = new DateTime($r['incident_datetime']);
                            $inc = htmlspecialchars($dt->format('m/d/Y h:i A'));
                          } catch (Exception $e) {
                            $inc = htmlspecialchars($r['incident_datetime']);
                          }
                        } else {
                          $inc = '-';
                        }
                        $loc = htmlspecialchars($r['location'] ?? '');
                        $tl = htmlspecialchars($r['team_leader'] ?? '');
                        $submittedBy = htmlspecialchars($r['submitted_by_name'] ?? '-');
                        $statusRaw = strtolower(trim($r['status'] ?? ''));
                        $caseStatusRaw = strtolower(trim($r['case_status'] ?? ''));

                        // Review badge (report approval status)
                        $reviewBadgeClass = 'bg-secondary';
                        if ($statusRaw === 'approved') {
                          $reviewBadgeClass = 'bg-success';
                        } elseif (in_array($statusRaw, ['pending', 'for review', 'under review'])) {
                          $reviewBadgeClass = 'bg-warning';
                        } elseif (in_array($statusRaw, ['rejected', 'denied'])) {
                          $reviewBadgeClass = 'bg-danger';
                        }

                        // Case status badge (case lifecycle)
                        $caseBadgeClass = 'bg-secondary';
                        $hasCaseStatus = isset($r['case_status']) && trim((string)$r['case_status']) !== '';
                        if ($hasCaseStatus) {
                          if (in_array($caseStatusRaw, ['under investigation','under-investigation','under_review','under review'])) {
                            $caseBadgeClass = 'bg-primary';
                          } elseif (in_array($caseStatusRaw, ['for filing','for-filing'])) {
                            $caseBadgeClass = 'bg-warning';
                          } elseif (in_array($caseStatusRaw, ['ongoing','ongoing-trial','ongoing trial'])) {
                            $caseBadgeClass = 'bg-info';
                          } elseif (in_array($caseStatusRaw, ['filed in court','filed-in-court','filed'])) {
                            $caseBadgeClass = 'bg-secondary';
                          } elseif ($caseStatusRaw === 'dismissed') {
                            $caseBadgeClass = 'bg-danger';
                          } elseif ($caseStatusRaw === 'resolved') {
                            $caseBadgeClass = 'bg-success';
                          } elseif ($caseStatusRaw === 'archived') {
                            $caseBadgeClass = 'bg-dark';
                          }
                        } else {
                          // No explicit case_status set: if the report itself is already approved,
                          // show the default case lifecycle color (Under Investigation = blue)
                          if ($statusRaw === 'approved') {
                            $caseBadgeClass = 'bg-primary';
                          } else {
                            $caseBadgeClass = 'bg-secondary';
                          }
                        }
                        $estRaw = isset($r['est_value']) ? $r['est_value'] : null;
                        $est = ($estRaw !== null && $estRaw !== '') ? ('â‚± ' . number_format((float)$estRaw, 2)) : '-';
                        $est = ($estRaw !== null && $estRaw !== '') ? ('&#8369; ' . number_format((float)$estRaw, 2)) : '-';
                        $viewUrl = 'case_details.php?ref=' . urlencode($r['reference_no']);
                        $editUrl = 'case_detailsupdate.php?id=' . urlencode($r['reference_no']);
                        echo "<tr>\n";
                        echo "  <td data-label=\"Ref No.\">$ref</td>\n";
                        echo "  <td data-label=\"Incident Date\">" . $inc . "</td>\n";
                        echo "  <td data-label=\"Location\">" . $loc . "</td>\n";
                        echo "  <td data-label=\"Team Leader\">" . $tl . "</td>\n";
                        echo "  <td data-label=\"Submitted By\">" . $submittedBy . "</td>\n";
                        echo "  <td data-label=\"Review\"><span class=\"badge $reviewBadgeClass\">" . htmlspecialchars($r['status'] ?? '') . "</span></td>\n";
                        // Show the case's official status (if set), otherwise default to 'Under Investigation'
                        $displayCaseStatus = trim((string)($r['case_status'] ?? '')) !== '' ? $r['case_status'] : 'Under Investigation';
                        echo "  <td data-label=\"Status\"><span class=\"badge $caseBadgeClass\">" . htmlspecialchars($displayCaseStatus) . "</span></td>\n";
                        echo "  <td data-label=\"Est. Value\">" . $est . "</td>\n";
                        echo "  <td data-label=\"Details\" class=\"actions-cell\"><a href=\"$viewUrl\" class=\"btn btn-sm btn-outline-primary\" title=\"View Details\">View</a></td>\n";
                        echo "</tr>\n";
                      }
                    } else {
                      echo '<tr><td colspan="9" class="text-center">No approved cases found.</td></tr>';
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
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260315-2"></script>
  <!-- Case Management JavaScript -->
  <script src="../../../../public/assets/js/admin/case_management.js?v=20260404-1"></script>

  <div class="modal fade" id="caseManagementFiltersModalAdmin" tabindex="-1" aria-labelledby="caseManagementFiltersModalAdminLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable sr-filters-modal-dialog">
      <div class="modal-content sr-filters-modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="caseManagementFiltersModalAdminLabel">Filter Cases</h5>
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
              <option value="under-investigation">Under Investigation</option>
              <option value="for-filing">For Filing</option>
              <option value="ongoing">Ongoing</option>
              <option value="dismissed">Dismissed</option>
              <option value="resolved">Resolved</option>
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
