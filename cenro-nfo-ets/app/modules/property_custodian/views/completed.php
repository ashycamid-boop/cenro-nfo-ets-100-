<?php require_once __DIR__ . '/../controllers/completed_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Completed Requests</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Service Desk specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/service-desk.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/completed.css?v=20260319-3">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-completed-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="propertyCustodianCompletedSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role">Property Custodian</div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
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
              <li class="active"><a href="completed.php">Completed</a></li>
              <li><a href="all_requests.php">All Requests</a></li>
            </ul>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="propertyCustodianCompletedSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Completed Requests</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid p-4">
          <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="mb-3">
              <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message']); ?>
              </div>
            </div>
          <?php endif; ?>
          <div class="cp-mobile-toolbar">
            <div class="cp-mobile-search-wrap">
              <i class="fa fa-search cp-mobile-search-icon" aria-hidden="true"></i>
              <input type="text" id="completedSearchMobile" class="form-control cp-mobile-search" placeholder="Search requests">
            </div>
            <button type="button" class="btn cp-mobile-filter-btn" data-bs-toggle="modal" data-bs-target="#completedFiltersModal">
              <i class="fa fa-sliders me-2" aria-hidden="true"></i>Filters
            </button>
          </div>

          <div class="cp-active-filters" id="completedActiveFilters" aria-label="Active filters"></div>

          <div class="row mb-4">
            <div class="col-12">
              <div class="d-flex gap-2 align-items-center">
                <input type="text" id="completedSearch" class="form-control" placeholder="Search" style="width: 250px;">
                <input type="text" id="date_from" class="form-control date-picker" placeholder="mm/dd/yyyy" style="width: 150px;" autocomplete="off">
                <input type="text" id="date_to" class="form-control date-picker" placeholder="mm/dd/yyyy" style="width: 150px;" autocomplete="off">
                <button id="applyFilter" class="btn btn-primary">Apply</button>
                <button id="clearFilter" class="btn btn-outline-secondary">Clear</button>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table id="completedRequestsTable" class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
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
                    <?php if (!empty($completed)): foreach ($completed as $r):
                      $start = '-';
                      $end = '-';
                      if ($earliestStmt) {
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

                      if ($latestStmt) {
                        try {
                          $latestStmt->execute(['id' => $r['id']]);
                          $le = $latestStmt->fetch(PDO::FETCH_ASSOC);
                          // If there is a last action row, use its date/time if present (use whatever fields are available)
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
                      <tr>
                        <td style="padding:8px; vertical-align: middle;" data-label="Ticket ID"><?php echo htmlspecialchars($r['ticket_no'] ?? $r['id'] ?? ''); ?></td>
                        <td style="padding:8px; vertical-align: middle;" data-label="Date Logged">
                          <?php
                            if (!empty($r['ticket_date'])) {
                              echo htmlspecialchars(date('m/d/Y', strtotime($r['ticket_date'])));
                            } elseif (!empty($r['created_at'])) {
                              echo htmlspecialchars(date('m/d/Y', strtotime($r['created_at'])));
                            } else {
                              echo '';
                            }
                          ?>
                        </td>
                        <td style="padding:8px; vertical-align: middle;" data-label="Requester"><?php echo htmlspecialchars($r['requester_name'] ?? ''); ?></td>
                        <td style="padding:8px; vertical-align: middle;" data-label="Position"><?php echo htmlspecialchars($r['requester_position'] ?? ''); ?></td>
                        <td style="padding:8px; vertical-align: middle;" data-label="Office Unit"><?php echo htmlspecialchars($r['requester_office'] ?? ''); ?></td>
                        <td style="padding:8px; vertical-align: middle;" data-label="Type of Request"><?php echo htmlspecialchars($r['request_type'] ?? ''); ?></td>
                        <td style="padding:8px; vertical-align: middle;" data-label="Start Date/Time"><?php echo htmlspecialchars($displayStart); ?></td>
                        <td style="padding:8px; vertical-align: middle;" data-label="End Date/Time"><?php echo htmlspecialchars($displayEnd); ?></td>
                        <td style="padding:8px; vertical-align: middle;" data-label="Status"><span class="badge bg-success text-white"><?php echo htmlspecialchars($r['status'] ?? ''); ?></span></td>
                        <td style="padding:8px; vertical-align: middle;" data-label="Details"><a href="request_details.php?id=<?php echo urlencode($r['id']); ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                      </tr>
                    <?php endforeach; else: ?>
                      <tr>
                        <td colspan="10" class="text-center">No completed requests.</td>
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

  <!-- Bootstrap 5 JS Bundle -->
  <!-- Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <!-- Admin JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260319-2"></script>
  <script src="../../../../public/assets/js/admin/completed.js"></script>

  <div class="modal fade" id="completedFiltersModal" tabindex="-1" aria-labelledby="completedFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable cp-filters-modal-dialog">
      <div class="modal-content cp-filters-modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="completedFiltersModalLabel">Filter Requests</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="cp-modal-field">
            <label for="completedSearchModal" class="form-label">Search</label>
            <input type="text" id="completedSearchModal" class="form-control" placeholder="Search requests">
          </div>
          <div class="cp-modal-field">
            <label for="completedDateFromModal" class="form-label">Date From</label>
            <input type="text" id="completedDateFromModal" class="form-control date-picker" placeholder="mm/dd/yyyy" autocomplete="off">
          </div>
          <div class="cp-modal-field">
            <label for="completedDateToModal" class="form-label">Date To</label>
            <input type="text" id="completedDateToModal" class="form-control date-picker" placeholder="mm/dd/yyyy" autocomplete="off">
          </div>
        </div>
        <div class="modal-footer cp-filters-modal-footer">
          <button type="button" id="clearFilterMobile" class="btn btn-outline-secondary">Clear All</button>
          <button type="button" id="applyFilterMobile" class="btn btn-primary" data-bs-dismiss="modal">Apply Filters</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
