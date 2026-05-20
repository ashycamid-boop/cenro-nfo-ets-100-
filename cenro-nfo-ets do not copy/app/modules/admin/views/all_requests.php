<?php require_once __DIR__ . '/../controllers/all_requests_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Requests</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/service-desk.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/all_requests.css?v=20260319-1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
  <!-- Flatpickr datepicker CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="admin-dashboard-page admin-all-requests-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <nav class="sidebar" id="adminAllRequestsSidebar" role="navigation" aria-label="Main sidebar">
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
          <li class="dropdown active">
            <a href="#" class="dropdown-toggle active" id="serviceDeskToggle" data-target="serviceDeskMenu">
              <i class="fa fa-headset"></i> Service Desk 
              <i class="fa fa-chevron-down dropdown-arrow rotated"></i>
            </a>
            <ul class="dropdown-menu show" id="serviceDeskMenu">
              <li><a href="new_requests.php">New Requests <span class="badge">2</span></a></li>
              <li><a href="ongoing_scheduled.php">Ongoing / Scheduled <span class="badge badge-blue">2</span></a></li>
              <li><a href="completed.php">Completed</a></li>
              <li class="active"><a href="all_requests.php">All Requests</a></li>
            </ul>
          </li>
          <li><a href="statistical_report.php"><i class="fa fa-chart-bar"></i> Statistical Report</a></li>
        </ul>
      </nav>
    </nav>
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminAllRequestsSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">All Requests</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid p-4">
          <div class="ar-mobile-toolbar">
            <div class="ar-mobile-search-wrap">
              <i class="fa fa-search ar-mobile-search-icon" aria-hidden="true"></i>
              <input type="text" id="allRequestsSearchMobile" class="form-control ar-mobile-search" placeholder="Search requests">
            </div>
            <button type="button" class="btn ar-mobile-filter-btn" data-bs-toggle="modal" data-bs-target="#allRequestsFiltersModal">
              <i class="fa fa-sliders me-2" aria-hidden="true"></i>Filters
            </button>
          </div>

          <div class="ar-active-filters" id="allRequestsActiveFilters" aria-label="Active filters"></div>

          <div class="row mb-4 align-items-center">
            <div class="col-12">
              <div class="d-flex gap-2 align-items-center all-requests-filters">
                <input type="text" id="allRequestsSearch" class="form-control" placeholder="Search" style="width: 250px;">
                <input type="text" id="date_from" class="form-control date-picker" placeholder="mm/dd/yyyy" style="width: 150px;" autocomplete="off">
                <input type="text" id="date_to" class="form-control date-picker" placeholder="mm/dd/yyyy" style="width: 150px;" autocomplete="off">
                <button id="applyFilter" class="btn btn-primary">Apply</button>
                <button id="clearFilter" class="btn btn-outline-secondary">Clear</button>
              </div>
            </div>
          </div>

          <div class="card all-requests-table-section">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table id="allRequestsTable" class="table table-sm table-hover mb-0 all-requests-table" style="font-size: 0.85rem;">
                  <thead class="table-light">
                    <tr>
                      <th style="padding: 8px;">Ticket ID</th>
                      <th style="padding: 8px;">Date Logged</th>
                      <th style="padding: 8px;">Requester</th>
                      <th style="padding: 8px;">Position</th>
                      <th style="padding: 8px;">Office Unit</th>
                      <th style="padding: 8px;">Type of Request</th>
                      <th style="padding: 8px;">Start Date/Time</th>
                      <th style="padding: 8px;">End Date/Time</th>
                      <th style="padding: 8px;">Status</th>
                      <th style="padding: 8px;">Details</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($requests)): foreach ($requests as $r): ?>
                      <tr>
                        <td style="padding:8px;" data-label="Ticket ID"><?php echo htmlspecialchars($r['ticket_no'] ?? $r['id'] ?? ''); ?></td>
                        <td style="padding:8px;" data-label="Date Logged"><?php echo !empty($r['created_at']) ? date('m/d/Y h:i A', strtotime($r['created_at'])) : ''; ?></td>
                        <td style="padding:8px;" data-label="Requester"><?php echo htmlspecialchars($r['requester_name'] ?? ''); ?></td>
                        <td style="padding:8px;" data-label="Position"><?php echo htmlspecialchars($r['requester_position'] ?? ''); ?></td>
                        <td style="padding:8px;" data-label="Office Unit"><?php echo htmlspecialchars($r['requester_office'] ?? ''); ?></td>
                        <td style="padding:8px;" data-label="Type of Request"><?php echo htmlspecialchars($r['devices'] ?? $r['request_type'] ?? ''); ?></td>
                        <?php
                          $start = '-';
                          $end = '-';
                          if (isset($earliestStmt) && $earliestStmt) {
                            try {
                              $earliestStmt->execute(['id' => $r['id']]);
                              $er = $earliestStmt->fetch(PDO::FETCH_ASSOC);
                              if (!empty($er) && !empty($er['action_date'])) {
                                $start = $er['action_date'];
                                if (!empty($er['action_time'])) {
                                  $t_ts = strtotime($er['action_time']);
                                  $t_disp = $t_ts !== false ? date('h:i A', $t_ts) : $er['action_time'];
                                  $start .= ' ' . $t_disp;
                                }
                              } elseif (!empty($r['start_datetime'])) {
                                $start = $r['start_datetime'];
                              }
                            } catch (Exception $e) { error_log('earliest fetch error: ' . $e->getMessage()); }
                          } else {
                            if (!empty($r['start_datetime'])) $start = $r['start_datetime'];
                          }

                          if (isset($latestStmt) && $latestStmt) {
                            try {
                              $latestStmt->execute(['id' => $r['id']]);
                              $le = $latestStmt->fetch(PDO::FETCH_ASSOC);
                              if (!empty($le)) {
                                $ad = $le['action_date'] ?? '';
                                $at = $le['action_time'] ?? '';
                                if ($ad !== '' || $at !== '') {
                                  $end = trim(($ad !== '' ? $ad : '') . (!empty($at) ? ' ' . $at : ''));
                                } elseif (!empty($r['updated_at'])) {
                                  $end = $r['updated_at'];
                                }
                              } elseif (!empty($r['updated_at'])) {
                                $end = $r['updated_at'];
                              }
                            } catch (Exception $e) { error_log('latest fetch error: ' . $e->getMessage()); }
                          } else {
                            if (!empty($r['updated_at'])) $end = $r['updated_at'];
                          }

                          $displayStart = ($start !== '-' && strtotime($start) !== false) ? date('m/d/Y h:i A', strtotime($start)) : $start;
                          $displayEnd = ($end !== '-' && strtotime($end) !== false) ? date('m/d/Y h:i A', strtotime($end)) : $end;
                        ?>
                        <td style="padding:8px;" data-label="Start Date/Time"><?php echo htmlspecialchars($displayStart); ?></td>
                        <td style="padding:8px;" data-label="End Date/Time"><?php echo htmlspecialchars($displayEnd); ?></td>
                        <td style="padding:8px; font-weight:600;" data-label="Status">
                          <?php
                            $st = $r['status'] ?? '';
                            $s = strtolower(trim($st));
                            $badgeClass = 'badge bg-light text-dark';
                            if ($s === 'pending' || $s === 'open') {
                              $badgeClass = 'badge bg-warning text-dark';
                            } elseif ($s === 'ongoing' || $s === 'scheduled') {
                              $badgeClass = 'badge bg-info text-dark';
                            } elseif ($s === 'completed') {
                              $badgeClass = 'badge bg-success text-white';
                            } elseif (in_array($s, ['closed', 'cancelled', 'rejected', 'declined'])) {
                              $badgeClass = 'badge bg-secondary text-white';
                            }
                          ?>
                          <span class="<?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($st); ?></span>
                        </td>
                        <td style="padding:8px;" data-label="Details"><a href="request_details.php?id=<?php echo urlencode($r['id'] ?? $r['ticket_no']); ?>" class="btn btn-sm btn-outline-primary">Details</a></td>
                      </tr>
                    <?php endforeach; else: ?>
                      <tr>
                        <td colspan="10" class="text-center py-3">No requests found.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <script src="../../../../public/assets/js/admin/all_requests.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260319-2"></script>

  <div class="modal fade" id="allRequestsFiltersModal" tabindex="-1" aria-labelledby="allRequestsFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ar-filters-modal-dialog">
      <div class="modal-content ar-filters-modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="allRequestsFiltersModalLabel">Filter Requests</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="ar-modal-field">
            <label for="allRequestsSearchModal" class="form-label">Search</label>
            <input type="text" id="allRequestsSearchModal" class="form-control" placeholder="Search requests">
          </div>
          <div class="ar-modal-field">
            <label for="allRequestsDateFromModal" class="form-label">Date From</label>
            <input type="text" id="allRequestsDateFromModal" class="form-control date-picker" placeholder="mm/dd/yyyy" autocomplete="off">
          </div>
          <div class="ar-modal-field">
            <label for="allRequestsDateToModal" class="form-label">Date To</label>
            <input type="text" id="allRequestsDateToModal" class="form-control date-picker" placeholder="mm/dd/yyyy" autocomplete="off">
          </div>
        </div>
        <div class="modal-footer ar-filters-modal-footer">
          <button type="button" id="clearFilterMobile" class="btn btn-outline-secondary">Clear All</button>
          <button type="button" id="applyFilterMobile" class="btn btn-primary" data-bs-dismiss="modal">Apply Filters</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
