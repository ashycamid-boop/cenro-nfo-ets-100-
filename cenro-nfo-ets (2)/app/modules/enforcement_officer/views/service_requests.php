<?php require_once __DIR__ . '/../controllers/service_requests_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Service Requests</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Dashboard specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/dashboard.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/enforcement_officer/service_requests.css?v=20260517-responsive">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page enforcement-service-requests-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="enforcementServiceRequestsSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo htmlspecialchars((string)($sidebarRole ?? 'Enforcement Officer'), ENT_QUOTES, 'UTF-8'); ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_spot_report_sidebar_badge(); ?></a></li>
          <li><a href="case_management.php"><i class="fa fa-briefcase"></i> Case Management</a></li>
          <li><a href="apprehended_items.php"><i class="fa fa-archive"></i> Apprehended Items</a></li>
          <li class="active"><a href="service_requests.php"><i class="fa fa-cog"></i> Service Requests</a></li>
          <li><a href="statistical_report.php"><i class="fa fa-chart-bar"></i> Statistical Report</a></li>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="enforcementServiceRequestsSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Service Requests</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid p-2">

          <div class="sr-mobile-toolbar">
            <form method="get" action="service_requests.php" class="sr-mobile-search-form">
              <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
              <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
              <div class="sr-mobile-search-wrap">
                <i class="fa fa-search sr-mobile-search-icon" aria-hidden="true"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control sr-control sr-mobile-search" placeholder="Search requests">
              </div>
            </form>
            <button type="button" class="btn sr-mobile-filter-btn" data-bs-toggle="modal" data-bs-target="#serviceRequestFiltersModal">
              <i class="fa fa-sliders me-2" aria-hidden="true"></i>Filters
            </button>
          </div>

          <div class="sr-active-filters" id="serviceRequestActiveFilters" aria-label="Active filters" style="display:none;">
            <?php if ($search !== ''): ?>
              <span class="sr-filter-chip"><i class="fa fa-search" aria-hidden="true"></i><?php echo htmlspecialchars($search); ?></span>
            <?php endif; ?>
            <?php if ($dateFrom !== ''): ?>
              <span class="sr-filter-chip"><i class="fa fa-calendar" aria-hidden="true"></i>From: <?php echo htmlspecialchars($dateFrom); ?></span>
            <?php endif; ?>
            <?php if ($dateTo !== ''): ?>
              <span class="sr-filter-chip"><i class="fa fa-calendar" aria-hidden="true"></i>To: <?php echo htmlspecialchars($dateTo); ?></span>
            <?php endif; ?>
          </div>

          <!-- Filter Controls -->
          <form method="get" action="service_requests.php" class="sr-filters-desktop">
          <div class="row mb-4 g-3 align-items-end sr-filters">
            <!-- Search -->
            <div class="col-md-3">
              <input type="text" id="srDesktopSearch" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control sr-control" placeholder="Search">
            </div>
            <!-- Date From -->
            <div class="col-md-2">
              <div class="sr-date-field">
                <input type="date" id="srDesktopDateFrom" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="form-control sr-control" onkeydown="return false;" onpaste="return false;" onclick="this.showPicker && this.showPicker()">
                <span class="sr-date-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
            <!-- Date To -->
            <div class="col-md-2">
              <div class="sr-date-field">
                <input type="date" id="srDesktopDateTo" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="form-control sr-control" onkeydown="return false;" onpaste="return false;" onclick="this.showPicker && this.showPicker()">
                <span class="sr-date-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
            <!-- Apply Button -->
            <div class="col-md-1">
              <button type="submit" class="btn btn-primary w-100 sr-btn-primary">Apply</button>
            </div>
            <!-- Clear Button -->
            <div class="col-md-1">
              <a href="service_requests.php" class="btn btn-outline-secondary w-100 sr-btn-clear">Clear</a>
            </div>
          </div>
          </form>

          <!-- New Request Button -->
          <div class="row mb-3 sr-actions-row">
            <div class="col-12 d-flex justify-content-end">
              <a href="new_requests.php" class="btn btn-primary sr-btn-new-request">
                + New Request
              </a>
            </div>
          </div>

          <!-- Data Table -->
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0 sr-table">
                      <thead class="table-light">
                        <tr>
                          <th>Ticket ID</th>
                          <th>Date Logged</th>
                          <th>Type of Request</th>
                          <th>Description of Request</th>
                          <th>Status</th>
                          <th>Details</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (!empty($service_requests) && is_array($service_requests)): ?>
                          <?php foreach ($service_requests as $req): ?>
                            <?php
                              $ticketId = (string)($req['ticket_no'] ?? $req['id'] ?? '');
                              $loggedDate = !empty($req['ticket_date'])
                                ? date('Y-m-d', strtotime($req['ticket_date']))
                                : (!empty($req['created_at']) ? date('Y-m-d', strtotime($req['created_at'])) : '');
                              $requestType = (string)($req['request_type'] ?? '');
                              $requestDescription = (string)($req['request_description'] ?? '');
                              $requestStatusRaw = strtolower(trim((string)($req['status'] ?? '')));
                              if ($requestStatusRaw === 'open') {
                                $requestStatusRaw = 'pending';
                              }
                              $rowSearchText = strtolower(trim(implode(' ', array_filter([
                                $ticketId,
                                $loggedDate,
                                $requestType,
                                $requestDescription,
                                $requestStatusRaw,
                                $req['requester_name'] ?? '',
                                $req['requester_email'] ?? '',
                                $req['requester_phone'] ?? '',
                                $req['requester_office'] ?? '',
                                $req['requester_division'] ?? '',
                                $req['request_type_other'] ?? '',
                                $req['ticket_date'] ?? '',
                                $req['created_at'] ?? '',
                              ], static function ($value) {
                                return (string)$value !== '';
                              }))));
                            ?>
                            <tr data-search="<?php echo htmlspecialchars($rowSearchText, ENT_QUOTES, 'UTF-8'); ?>" data-date="<?php echo htmlspecialchars($loggedDate, ENT_QUOTES, 'UTF-8'); ?>">
                              <td data-label="Ticket ID"><?php echo htmlspecialchars($ticketId); ?></td>
                              <td data-label="Date Logged"><?php echo htmlspecialchars($loggedDate); ?></td>
                              <td data-label="Type of Request"><?php echo htmlspecialchars($requestType); ?></td>
                              <td data-label="Description of Request"><?php echo htmlspecialchars($requestDescription); ?></td>
                              <td data-label="Status">
                                <?php
                                  // Normalize status values: map legacy 'open' to 'pending'
                                  $rawStatus = strtolower(trim($req['status'] ?? ''));
                                  if ($rawStatus === 'open') $rawStatus = 'pending';
                                  $displayStatus = ucfirst($rawStatus ?: '');
                                  $badgeColor = ($rawStatus === 'pending') ? '#ffc107' : (($rawStatus === 'completed') ? '#28a745' : '#6c757d');
                                ?>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($badgeColor); ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($displayStatus); ?></span>
                              </td>
                              <td data-label="Details">
                                <a href="request_details.php?id=<?php echo urlencode($req['id'] ?? ''); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                <?php if ($rawStatus === 'completed'): ?>
                                  <a href="rate_request.php?id=<?php echo urlencode($req['id'] ?? ''); ?>" class="btn btn-sm btn-warning text-dark ms-2"><i class="fa fa-star me-1"></i>Rate</a>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="6" class="text-center">No service requests found.</td>
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
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260319-2"></script>
  <!-- Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="../../../../public/assets/js/enforcement_officer/service_requests.js?v=20260404-1"></script>

  <div class="modal fade" id="serviceRequestFiltersModal" tabindex="-1" aria-labelledby="serviceRequestFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable sr-filters-modal-dialog">
      <div class="modal-content sr-filters-modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="serviceRequestFiltersModalLabel">Filter Requests</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="get" action="service_requests.php">
          <div class="modal-body">
            <div class="sr-modal-field">
              <label for="srMobileSearch" class="form-label">Search</label>
              <input type="text" id="srMobileSearch" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control sr-control" placeholder="Search requests">
            </div>
            <div class="sr-modal-field">
              <label for="srMobileDateFrom" class="form-label">Date From</label>
              <div class="sr-date-field">
                <input type="date" id="srMobileDateFrom" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="form-control sr-control" onkeydown="return false;" onpaste="return false;" onclick="this.showPicker && this.showPicker()">
                <span class="sr-date-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
            <div class="sr-modal-field">
              <label for="srMobileDateTo" class="form-label">Date To</label>
              <div class="sr-date-field">
                <input type="date" id="srMobileDateTo" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="form-control sr-control" onkeydown="return false;" onpaste="return false;" onclick="this.showPicker && this.showPicker()">
                <span class="sr-date-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
          </div>
          <div class="modal-footer sr-filters-modal-footer">
            <a href="service_requests.php" class="btn btn-outline-secondary sr-btn-clear" id="srMobileClearAll">Clear All</a>
            <button type="submit" class="btn btn-primary sr-btn-primary" id="srMobileApplyFilters">Apply Filters</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
