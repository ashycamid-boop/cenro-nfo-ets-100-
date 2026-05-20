<?php require_once __DIR__ . '/../controllers/apprehended_items_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apprehended Items</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Apprehended Items specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/apprehended_items.css?v=20260319-4">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-apprehended-items-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <nav class="sidebar" id="adminApprehendedItemsSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo htmlspecialchars($sidebarRole, ENT_QUOTES, 'UTF-8'); ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="user_management.php"><i class="fa fa-users"></i> User Management</a></li>
          <li><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_spot_report_sidebar_badge(); ?></a></li>
          <li><a href="case_management.php"><i class="fa fa-briefcase"></i> Case Management</a></li>
          <li class="active"><a href="apprehended_items.php"><i class="fa fa-archive"></i> Apprehended Items</a></li>
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
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
            <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminApprehendedItemsSidebar">
              <i class="fa fa-bars"></i>
            </button>
            <div class="topbar-title">Apprehended Items</div>
            <?php include_once __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid">
          <div class="ai-mobile-toolbar">
            <div class="ai-mobile-search-wrap">
              <i class="fa fa-search ai-mobile-search-icon" aria-hidden="true"></i>
              <input type="text" class="form-control ai-mobile-search" id="searchInputMobile" placeholder="Search">
            </div>
            <button type="button" class="btn ai-mobile-filter-btn" data-bs-toggle="modal" data-bs-target="#apprehendedItemsFiltersModalAdmin">
              <i class="fa fa-sliders-h me-2" aria-hidden="true"></i>Filters
            </button>
          </div>

          <div class="ai-active-filters" id="apprehendedItemsActiveFiltersAdmin" aria-label="Active filters"></div>
          
          <!-- Search and Filter Section -->
          <div class="search-filter-section mb-4 ai-filters-desktop">
            <div class="row g-3 align-items-center">
              <div class="col-12 col-md-6">
                <div class="search-box">
                  <input type="text" class="form-control" id="searchInput" placeholder="Search">
                  <i class="fa fa-search search-icon"></i>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="d-none d-sm-flex filter-buttons gap-2">
                  <button class="btn btn-filter active" data-filter="all">All</button>
                  <button class="btn btn-filter" data-filter="vehicle">Vehicle</button>
                  <button class="btn btn-filter" data-filter="item">Items</button>
                </div>
                <div class="d-sm-none">
                  <select class="form-select mobile-item-filter" id="mobileItemFilter" aria-label="Filter apprehended items">
                    <option value="all">All</option>
                    <option value="vehicle">Vehicle</option>
                    <option value="item">Items</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Items Table -->
          <div class="items-table-section">
            <div class="table-responsive">
              <table class="table table-hover" id="itemsTable">
                <thead class="table-light">
                  <tr>
                    <th>Reference No.</th>
                    <th>Item Type</th>
                    <th>Description</th>
                    <th>Dimension (T &times; W &times; L)</th>
                    <th>Quantity</th>
                    <th>Volume</th>
                    <th>Evidence</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($items) && is_array($items)): ?>
                    <?php foreach ($items as $item): ?>
                      <?php
                        $type = strtolower(trim($item['type'] ?? ''));
                        // Only display vehicles and seizure items (map other allowed types to 'item')
                        $allowedItemTypes = ['equipment', 'forest-product', 'item', 'seizure', 'seizure-item'];
                        if ($type !== 'vehicle' && !in_array($type, $allowedItemTypes, true)) {
                          continue;
                        }
                        $rowType = ($type === 'vehicle') ? 'vehicle' : 'item';
                      ?>
                      <tr data-type="<?php echo $rowType; ?>">
                        <td data-label="Reference No."><?php echo htmlspecialchars($item['reference_no'] ?? ''); ?></td>
                        <td data-label="Item Type"><?php echo htmlspecialchars($item['type_label'] ?? ($type === 'vehicle' ? 'Vehicle' : 'Item')); ?></td>
                        <td data-label="Description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                        <td data-label="Dimension">
                          <?php
                            $t = $item['thickness_in'] ?? $item['thickness'] ?? null;
                            $w = $item['width_in'] ?? $item['width'] ?? null;
                            $l = $item['length_ft'] ?? $item['length'] ?? null;
                            if ($t !== null || $w !== null || $l !== null) {
                              $parts = [];
                              $parts[] = ($t !== null && $t !== '') ? rtrim(rtrim(number_format((float)$t, 3, '.', ''), '0'), '.') : '-';
                              $parts[] = ($w !== null && $w !== '') ? rtrim(rtrim(number_format((float)$w, 3, '.', ''), '0'), '.') : '-';
                              $parts[] = ($l !== null && $l !== '') ? rtrim(rtrim(number_format((float)$l, 3, '.', ''), '0'), '.') : '-';
                              echo htmlspecialchars(implode(' x ', $parts) . ' (in x in x ft)');
                            } else {
                              echo '<span class="text-muted">-</span>';
                            }
                          ?>
                        </td>
                        <td data-label="Quantity"><?php echo htmlspecialchars($item['quantity'] ?? ''); ?></td>
                        <td data-label="Volume">
                          <?php
                            if (isset($item['volume_bdft']) && $item['volume_bdft'] !== null && $item['volume_bdft'] !== '') {
                              echo htmlspecialchars(number_format((float)$item['volume_bdft'], 3));
                            } elseif (!empty($item['volume'])) {
                              echo htmlspecialchars($item['volume']);
                            } else {
                              echo '<span class="text-muted">-</span>';
                            }
                          ?>
                        </td>
                        <td data-label="Evidence"><?php echo $item['evidence'] ?? ''; ?></td>
                        <td data-label="Status"><span class="badge <?php echo htmlspecialchars($item['status_class'] ?? ''); ?>"><?php echo htmlspecialchars($item['status_label'] ?? ''); ?></span></td>
                        <?php
                          $luRaw = $item['last_updated'] ?? '';
                          $lastUpdatedDisplay = '-';
                          if (!empty($luRaw)) {
                            // If numeric timestamp, convert to int; else try strtotime
                            if (is_numeric($luRaw)) {
                              $ts = (int)$luRaw;
                            } else {
                              $ts = strtotime($luRaw);
                            }
                            if ($ts !== false && $ts > 0) {
                              $lastUpdatedDisplay = date('M d, Y g:i a', $ts);
                            } else {
                              // Fallback to raw string if parsing failed
                              $lastUpdatedDisplay = $luRaw;
                            }
                          }
                        ?>
                        <td data-label="Last Updated"><?php echo htmlspecialchars($lastUpdatedDisplay); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="9" class="text-center">No apprehended items found.</td>
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

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260315-2"></script>
  <!-- Apprehended Items JavaScript -->
  <script src="../../../../public/assets/js/admin/apprehended_items.js?v=20260404-1"></script>

  <div class="modal fade" id="apprehendedItemsFiltersModalAdmin" tabindex="-1" aria-labelledby="apprehendedItemsFiltersModalAdminLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ai-filters-modal-dialog">
      <div class="modal-content ai-filters-modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="apprehendedItemsFiltersModalAdminLabel">Filter Apprehended Items</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="ai-modal-field">
            <label for="searchInputModal" class="form-label">Search</label>
            <input type="text" class="form-control" id="searchInputModal" placeholder="Search">
          </div>
          <div class="ai-modal-field">
            <label for="mobileItemFilterModal" class="form-label">Type</label>
            <select class="form-select" id="mobileItemFilterModal" aria-label="Filter apprehended items">
              <option value="all">All</option>
              <option value="vehicle">Vehicle</option>
              <option value="item">Items</option>
            </select>
          </div>
        </div>
        <div class="modal-footer ai-filters-modal-footer">
          <button type="button" id="clearApprehendedFiltersMobile" class="btn btn-outline-secondary">Clear All</button>
          <button type="button" id="applyApprehendedFiltersMobile" class="btn btn-primary" data-bs-dismiss="modal">Apply Filters</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>


