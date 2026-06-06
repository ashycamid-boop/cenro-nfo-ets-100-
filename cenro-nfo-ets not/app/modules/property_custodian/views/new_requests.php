<?php require_once __DIR__ . '/../controllers/new_requests_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Requests</title>
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
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/new_requests.css?v=20260521-no-apply">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-new-requests-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="propertyCustodianNewRequestsSidebar" role="navigation" aria-label="Main sidebar">
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
              <li class="active"><a href="new_requests.php">New Requests <span class="badge">2</span></a></li>
              <li><a href="ongoing_scheduled.php">Ongoing / Scheduled <span class="badge badge-blue">2</span></a></li>
              <li><a href="completed.php">Completed</a></li>
              <li><a href="all_requests.php">All Requests</a></li>
            </ul>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="propertyCustodianNewRequestsSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">New Requests</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <!-- New Requests Content -->
        <div class="container-fluid p-4">
          <div class="nr-mobile-toolbar">
            <div class="nr-mobile-search-wrap">
              <i class="fa fa-search nr-mobile-search-icon" aria-hidden="true"></i>
              <input type="text" id="newRequestsSearchMobile" class="form-control nr-mobile-search" placeholder="Search requests">
            </div>
            <button type="button" class="btn nr-mobile-filter-btn" data-bs-toggle="modal" data-bs-target="#newRequestsFiltersModal">
              <i class="fa fa-sliders me-2" aria-hidden="true"></i>Filters
            </button>
          </div>

          <div class="nr-active-filters" id="newRequestsActiveFilters" aria-label="Active filters"></div>

          <!-- Top Controls -->
          <div class="row mb-4 align-items-center nr-filters-desktop">
            <div class="col-md-2">
              <input type="text" id="newRequestsSearch" class="form-control" placeholder="Search">
            </div>
            <div class="col-md-2">
              <input type="text" id="date_from" class="form-control date-picker" placeholder="mm/dd/yyyy" autocomplete="off">
            </div>
            <div class="col-md-2">
              <input type="text" id="date_to" class="form-control date-picker" placeholder="mm/dd/yyyy" autocomplete="off">
            </div>
            <div class="col-md-3">
              <div class="filter-buttons">
                <button id="clearFilter" class="btn btn-outline-secondary">Clear</button>
              </div>
            </div>
          </div>

          <!-- New Requests Table -->
          <div class="new-requests-table-section">
            <div class="table-responsive">
              <table class="table table-bordered table-sm new-requests-table" id="newRequestsTable">
                <thead class="table-light">
                  <tr>
                    <th class="new-requests-cell-head">Ticket ID</th>
                    <th class="new-requests-cell-head">Date Logged</th>
                    <th class="new-requests-cell-head">Requester</th>
                    <th class="new-requests-cell-head">Position</th>
                    <th class="new-requests-cell-head">Office/Unit</th>
                    <th class="new-requests-cell-head">Type of Request</th>
                    <th class="new-requests-cell-head">Status</th>
                    <th class="new-requests-cell-head">Details</th>
                    <th class="new-requests-cell-head">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($pending)):
                    foreach ($pending as $r):
                  ?>
                    <tr>
                      <td class="new-requests-cell-body" data-label="Ticket ID"><?php echo htmlspecialchars($r['ticket_no'] ?? $r['id'] ?? ''); ?></td>
                      <td class="new-requests-cell-body" data-label="Date Logged">
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
                      <td class="new-requests-cell-body" data-label="Requester"><?php echo htmlspecialchars($r['requester_name'] ?? ''); ?></td>
                      <td class="new-requests-cell-body" data-label="Position"><?php echo htmlspecialchars($r['requester_position'] ?? ''); ?></td>
                      <td class="new-requests-cell-body" data-label="Office/Unit"><?php echo htmlspecialchars($r['requester_office'] ?? ''); ?></td>
                      <td class="new-requests-cell-body" data-label="Type of Request"><?php echo htmlspecialchars($r['request_type'] ?? ''); ?></td>
                      <td class="new-requests-cell-body" data-label="Status"><span class="badge bg-warning text-dark">Pending</span></td>
                      <td class="new-requests-cell-body" data-label="Details"><a href="request_details.php?id=<?php echo urlencode($r['id']); ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                      <td class="new-requests-cell-body" data-label="Actions">
                        <a href="edit_requests.php?id=<?php echo urlencode($r['id']); ?>" class="btn btn-sm btn-primary">Edit</a>
                      </td>
                    </tr>
                  <?php
                    endforeach;
                  else:
                  ?>
                    <tr>
                      <td colspan="9" class="text-center">No pending requests.</td>
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Dashboard JavaScript -->
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260319-2"></script>
  <script src="../../../../public/assets/js/admin/new_requests.js?v=20260521-no-apply"></script>

  <div class="modal fade" id="newRequestsFiltersModal" tabindex="-1" aria-labelledby="newRequestsFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable nr-filters-modal-dialog">
      <div class="modal-content nr-filters-modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="newRequestsFiltersModalLabel">Filter Requests</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="nr-modal-field">
            <label for="newRequestsSearchModal" class="form-label">Search</label>
            <input type="text" id="newRequestsSearchModal" class="form-control" placeholder="Search requests">
          </div>
          <div class="nr-modal-field">
            <label for="newRequestsDateFromModal" class="form-label">Date From</label>
            <input type="text" id="newRequestsDateFromModal" class="form-control date-picker" placeholder="mm/dd/yyyy" autocomplete="off">
          </div>
          <div class="nr-modal-field">
            <label for="newRequestsDateToModal" class="form-label">Date To</label>
            <input type="text" id="newRequestsDateToModal" class="form-control date-picker" placeholder="mm/dd/yyyy" autocomplete="off">
          </div>
        </div>
        <div class="modal-footer nr-filters-modal-footer">
          <button type="button" id="clearFilterMobile" class="btn btn-outline-secondary">Clear All</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
