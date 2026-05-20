<?php
require_once __DIR__ . '/../controllers/equipment_management_backend.php';
require_once __DIR__ . '/../../../helpers/qr_url.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Equipment Management</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Equipment Management specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/equipment_management.css?v=20260521-print-fit">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-equipment-management-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="adminEquipmentManagementSidebar" role="navigation" aria-label="Main sidebar">
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
          <li><a href="apprehended_items.php"><i class="fa fa-archive"></i> Apprehended Items</a></li>
          <li class="active"><a href="equipment_management.php"><i class="fa fa-cogs"></i> Equipment Management</a></li>
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
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminEquipmentManagementSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Equipment Management</div>
          <?php include_once __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid">
          
          <div class="equipment-mobile-toolbar d-block d-sm-none mb-3">
            <div class="equipment-mobile-toolbar-row">
              <div class="equipment-mobile-search">
                <input type="text" class="form-control" id="searchInputMobile" placeholder="Search equipment">
              </div>
              <button class="btn equipment-mobile-filter-btn" type="button" id="openEquipmentFiltersMobile">
                <i class="fa fa-sliders-h me-2"></i>Filters
              </button>
            </div>
            <div class="equipment-mobile-actions">
              <button class="btn btn-success" type="button" id="printEquipmentListBtnMobile">
                <i class="fa fa-print me-2"></i>Print
              </button>
              <button class="btn btn-outline-success" type="button" id="exportEquipmentExcelBtnMobile">
                <i class="fa fa-file-excel me-2"></i>Export Excel
              </button>
              <button class="btn btn-outline-dark" type="button" id="printQRCodesBtnMobile">
                <i class="fa fa-print me-2"></i>Print QR
              </button>
              <button class="btn btn-primary" type="button" id="addNewDeviceBtnMobile">
                <i class="fa fa-plus me-2"></i>Add New Device
              </button>
            </div>
            <div class="equipment-mobile-filter-chips" id="equipmentActiveFilterChips"></div>
          </div>

          <!-- Top Action Bar -->
          <div class="top-action-bar mb-4 equipment-desktop-controls d-none d-sm-block">
            <div class="row g-3 align-items-center">
              <div class="col-12 col-xl-8">
                <div class="d-flex gap-2">
                  <div class="search-box flex-grow-1">
                    <div class="input-group">
                      <input type="text" class="form-control" id="searchInput" placeholder="Search">
                      <span class="input-group-text d-none d-md-inline"><i class="fa fa-search"></i></span>
                      <select id="statusFilter" class="form-select" style="max-width:220px;" aria-label="Filter by status">
                        <option value="All">All Status</option>
                        <option value="Available">Available</option>
                        <option value="Assigned">Assigned</option>
                        <option value="Returned">Returned</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Missing">Missing</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Out of Service">Out of Service</option>
                      </select>
                      <button class="btn btn-outline-secondary" type="button" id="clearFiltersBtn" title="Clear filters">Clear</button>
                    </div>
                  </div>
                  <button class="btn btn-outline-dark" id="printQRCodesBtn">
                    <i class="fa fa-print me-2"></i>Print All QR Codes
                  </button>
                  <select id="printTypeFilter" class="form-select" style="max-width:220px;" aria-label="Print by equipment type">
                    <option value="All">Select Equipment Type</option>
                  </select>
                  <button class="btn btn-success" type="button" id="printEquipmentListBtn" onclick="window.__adminEquipmentTopActions && window.__adminEquipmentTopActions.printList()">
                    <i class="fa fa-print me-2"></i>Print
                  </button>
                  <button class="btn btn-outline-success" type="button" id="exportEquipmentExcelBtn" onclick="window.__adminEquipmentTopActions && window.__adminEquipmentTopActions.exportExcel()">
                    <i class="fa fa-file-excel me-2"></i>Export Excel
                  </button>
                </div>
              </div>
              <div class="col-12 col-xl-4 text-xl-end">
                <button class="btn btn-primary" id="addNewDeviceBtn">
                  <i class="fa fa-plus me-2"></i>Add New Device
                </button>
              </div>
            </div>
          </div>

          <!-- Equipment Table -->
          <div class="equipment-table-section">
            <div class="table-responsive">
              <table class="table table-hover" id="equipmentTable">
                <thead class="table-light">
                  <tr>
                    <th>Asset ID</th>
                    <th>Property No.</th>
                    <th>Equipment Type</th>
                    <th>Brand</th>
                    <th>Year Acquired</th>
                    <th>Actual User</th>
                    <th>Accountable Person</th>
                    <th>Status</th>
                    <th>QR Code</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Equipment data will be populated dynamically via JavaScript/AJAX -->
                </tbody>
              </table>
            </div>
          </div>

          <div class="equipment-mobile-filter-modal d-sm-none" id="equipmentMobileFilterModal" aria-hidden="true">
            <div class="equipment-mobile-filter-backdrop" data-close-equipment-filters="true"></div>
            <div class="equipment-mobile-filter-sheet" role="dialog" aria-modal="true" aria-labelledby="equipmentMobileFilterTitle">
              <div class="equipment-mobile-filter-handle"></div>
              <div class="equipment-mobile-filter-header">
                <h5 id="equipmentMobileFilterTitle">Filter Equipment</h5>
                <button type="button" class="btn-close" aria-label="Close filters" data-close-equipment-filters="true"></button>
              </div>
              <div class="equipment-mobile-filter-body">
                <div class="mb-3">
                  <label class="form-label" for="searchInputModal">Search</label>
                  <input type="text" class="form-control" id="searchInputModal" placeholder="Search equipment">
                </div>
                <div class="mb-3">
                  <label class="form-label" for="statusFilterModal">Status</label>
                  <select id="statusFilterModal" class="form-select" aria-label="Filter by status">
                    <option value="All">All Status</option>
                    <option value="Available">Available</option>
                    <option value="Assigned">Assigned</option>
                    <option value="Returned">Returned</option>
                    <option value="Under Maintenance">Under Maintenance</option>
                    <option value="Missing">Missing</option>
                    <option value="Damaged">Damaged</option>
                    <option value="Out of Service">Out of Service</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="printTypeFilterModal">Print Type</label>
                  <select id="printTypeFilterModal" class="form-select" aria-label="Print by equipment type">
                    <option value="All">Select Equipment Type</option>
                  </select>
                </div>
              </div>
              <div class="equipment-mobile-filter-footer">
                <button class="btn btn-outline-secondary" type="button" id="clearEquipmentFiltersMobile">Clear All</button>
                <button class="btn btn-primary" type="button" id="applyEquipmentFiltersMobile">Apply</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Add New Device Modal -->
  <div class="add-device-modal" id="addDeviceModal">
    <div class="modal-content" style="max-width: 900px; width: 90%;">
      <div class="modal-header">
        <h5 class="modal-title">Add New Equipment</h5>
        <button type="button" class="btn-close" id="closeAddDeviceModal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="addDeviceForm">
          <!-- Basic Information -->
          <h6 class="section-title">Basic Information</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label for="officeDevision" class="form-label">Office/Division</label>
                <input type="text" class="form-control" id="officeDevision" name="officeDevision">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="equipmentType" class="form-label">Type of Equipment</label>
                <select class="form-select" id="equipmentType" name="equipmentType">
                  <option value="">Select Equipment Type</option>
                  <option value="Desktop">Desktop</option>
                  <option value="UPS">UPS</option>
                  <option value="Laptop">Laptop</option>
                  <option value="Printers">Printers</option>
                  <option value="Scanners">Scanners</option>
                  <option value="Storage Devices">Storage Devices</option>
                  <option value="Tablet Computer">Tablet Computer</option>
                  <option value="Geotagging Devices">Geotagging Devices</option>
                  <option value="Cameras">Cameras</option>
                  <option value="Communication Equipment">Communication Equipment</option>
                  <option value="Video / Audio Controller">Video / Audio Controller</option>
                  <option value="Access Points">Access Points</option>
                  <option value="Adapter">Adapter</option>
                  <option value="Airfiber">Airfiber</option>
                  <option value="Network Switches">Network Switches</option>
                  <option value="Patch Panel">Patch Panel</option>
                  <option value="NVR">NVR</option>
                  <option value="CCTV / IP Camera">CCTV / IP Camera</option>
                  <option value="Routers">Routers</option>
                  <option value="Servers">Servers</option>
                  <option value="LCD Projectors">LCD Projectors</option>
                  <option value="Photo Copier">Photo Copier</option>
                  <option value="Drones">Drones</option>
                  <option value="Interactive Kiosk / SmartTV">Interactive Kiosk / SmartTV</option>
                  <option value="Biometric Devices">Biometric Devices</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="yearAcquired" class="form-label">Year Acquired</label>
                <input type="number" class="form-control" id="yearAcquired" name="yearAcquired"
                  min="1900" max="<?php echo (int)date('Y'); ?>" step="1" inputmode="numeric" placeholder="e.g. <?php echo (int)date('Y'); ?>">
                <div class="form-text">Enter the year manually.</div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label for="shelfLife" class="form-label">Shelf Life</label>
                <select class="form-select" id="shelfLife" name="shelfLife">
                  <option value="">Select Shelf Life</option>
                  <option value="Beyond 5 Years">Beyond 5 Years</option>
                  <option value="Within 5 Years">Within 5 Years</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="brand" class="form-label">Brand</label>
                <input type="text" class="form-control" id="brand" name="brand">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="model" class="form-label">Model</label>
                <input type="text" class="form-control" id="model" name="model">
              </div>
            </div>
          </div>

          <!-- Computer Specifications (For Desktop & Laptop) -->
          <h6 class="section-title">Computer Specifications (For Desktop & Laptop)</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label for="processor" class="form-label">Processor</label>
                <input type="text" class="form-control" id="processor" name="processor" placeholder="e.g. i7-10700T">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="ramSize" class="form-label">Installed Memory RAM Size</label>
                <input type="text" class="form-control" id="ramSize" name="ramSize" placeholder="e.g. 8GB DDR4">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="gpu" class="form-label">Installed GPU</label>
                <input type="text" class="form-control" id="gpu" name="gpu" placeholder="e.g. nvidia, shared graphics">
              </div>
            </div>
          </div>

          <!-- Software Information -->
          <h6 class="section-title">Software Information</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label for="osVersion" class="form-label">Operating System Version</label>
                <input type="text" class="form-control" id="osVersion" name="osVersion" placeholder="Enter OS version">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="officeProductivity" class="form-label">Office Productivity</label>
                <select class="form-select" id="officeProductivity" name="officeProductivity">
                  <option value="">Select Office Suite</option>
                  <option value="Microsoft Office 2016">Microsoft Office 2016</option>
                  <option value="Microsoft Office 2019">Microsoft Office 2019</option>
                  <option value="Microsoft Office 2021">Microsoft Office 2021</option>
                  <option value="Microsoft 365 (Office 365)">Microsoft 365 (Office 365)</option>
                  <option value="LibreOffice">LibreOffice</option>
                  <option value="Apache OpenOffice">Apache OpenOffice</option>
                  <option value="WPS Office (Free)">WPS Office (Free)</option>
                  <option value="Google Workspace (Docs, Sheets, Slides)">Google Workspace (Docs, Sheets, Slides)</option>
                  <option value="Trial Version">Trial Version</option>
                  <option value="Unactivated Office">Unactivated Office</option>
                  <option value="Crack / Counterfeit">Crack / Counterfeit</option>
                  <option value="None / N/A">None / N/A</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="endpointProtection" class="form-label">Endpoint Protection</label>
                <select class="form-select" id="endpointProtection" name="endpointProtection">
                  <option value="">Select Protection</option>
                  <option value="Windows Defender / Windows Firewall">Windows Defender / Windows Firewall</option>
                  <option value="Trend Micro">Trend Micro</option>
                  <option value="McAfee">McAfee</option>
                  <option value="Avast">Avast</option>
                  <option value="AVG">AVG</option>
                  <option value="Kaspersky">Kaspersky</option>
                  <option value="Norton / Symantec">Norton / Symantec</option>
                  <option value="Bitdefender">Bitdefender</option>
                  <option value="ESET NOD32">ESET NOD32</option>
                  <option value="None / N/A">None / N/A</option>
                  <option value="Expired License">Expired License</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Property Information -->
          <h6 class="section-title">Property Information</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label for="computerName" class="form-label">Computer Name</label>
                <input type="text" class="form-control" id="computerName" name="computerName">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="serialNumber" class="form-label">Serial Number</label>
                <input type="text" class="form-control" id="serialNumber" name="serialNumber">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="propertyNumber" class="form-label">Property Number</label>
                <input type="text" class="form-control" id="propertyNumber" name="propertyNumber">
              </div>
            </div>
          </div>
          <!-- Accountable Person -->
          <h6 class="section-title">Accountable Person</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label for="accountablePerson" class="form-label">Accountable Person</label>
                <select class="form-select" id="accountablePerson" name="accountablePerson">
                  <option value="">Select Person</option>
                  <!-- options will be populated dynamically by EquipmentService.getUsers() -->
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="accountableSex" class="form-label">Sex</label>
                <select class="form-select" id="accountableSex" name="accountableSex">
                  <option value="">Select Sex</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label for="accountableEmployment" class="form-label">Status of Employment</label>
                <select class="form-select" id="accountableEmployment" name="accountableEmployment">
                  <option value="">Select Status</option>
                  <option value="Permanent">Permanent</option>
                  <option value="Contract of Service / Job Order">Contract of Service / Job Order</option>
                  <option value="Job Order">Job Order</option>
                  <option value="Casual">Casual</option>
                  <option value="Probationary">Probationary</option>
                  <option value="Temporary">Temporary</option>
                  <option value="Part-Time">Part-Time</option>
                  <option value="Project-Based">Project-Based</option>
                  <option value="Consultant">Consultant</option>
                  <option value="Intern / OJT">Intern / OJT</option>
                  <option value="N/A">N/A</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Actual User -->
          <h6 class="section-title">Actual User</h6>
          <div class="row">
            <div class="col-md-3">
              <div class="mb-3">
                <label for="actualUser" class="form-label">Actual User</label>
                <select class="form-select" id="actualUser" name="actualUser">
                  <option value="">Select User</option>
                  <!-- options will be populated dynamically by EquipmentService.getUsers() -->
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label for="actualUserSex" class="form-label">Sex</label>
                <select class="form-select" id="actualUserSex" name="actualUserSex">
                  <option value="">Select Sex</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label for="actualUserEmployment" class="form-label">Status of Employment</label>
                <select class="form-select" id="actualUserEmployment" name="actualUserEmployment">
                  <option value="">Select Status</option>
                  <option value="Permanent">Permanent</option>
                  <option value="Contractual">Contractual</option>
                  <option value="Job Order">Job Order</option>
                  <option value="Casual">Casual</option>
                  <option value="Probationary">Probationary</option>
                  <option value="Temporary">Temporary</option>
                  <option value="Part-Time">Part-Time</option>
                  <option value="Project-Based">Project-Based</option>
                  <option value="Consultant">Consultant</option>
                  <option value="Intern / OJT">Intern / OJT</option>
                  <option value="N/A">N/A</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label for="natureOfWork" class="form-label">Nature of Work</label>
                <select class="form-select" id="natureOfWork" name="natureOfWork">
                  <option value="">Select Nature</option>
                  <option value="Administrative Works / Clerical">Administrative Works / Clerical</option>
                  <option value="Technical Works">Technical Works</option>
                  <option value="Field Works / Inspection">Field Works / Inspection</option>
                  <option value="Supervisory / Managerial">Supervisory / Managerial</option>
                  <option value="IT-Related / Computer-Based Tasks">IT-Related / Computer-Based Tasks</option>
                  <option value="Maintenance / Utility">Maintenance / Utility</option>
                  <option value="Research / Planning">Research / Planning</option>
                  <option value="Finance / Accounting">Finance / Accounting</option>
                  <option value="Human Resource / Personnel">Human Resource / Personnel</option>
                  <option value="Procurement / Supply">Procurement / Supply</option>
                  <option value="Customer Service / Frontline">Customer Service / Frontline</option>
                  <option value="Legal / Compliance">Legal / Compliance</option>
                  <option value="Training / Education">Training / Education</option>
                  <option value="N/A">N/A</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Remarks -->
          <div class="row">
            <div class="col-12">
              <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
              </div>
            </div>
          </div>
          <!-- Status (Add/Edit) - moved below Remarks -->
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                  <option value="Assigned" selected>Assigned</option>
                  <option value="Available">Available</option>
                  <option value="Returned">Returned</option>
                  <option value="Under Maintenance">Under Maintenance</option>
                  <option value="Missing">Missing</option>
                  <option value="Damaged">Damaged</option>
                  <option value="Out of Service">Out of Service</option>
                </select>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="cancelAddDeviceBtn">Cancel</button>
        <button type="button" class="btn btn-primary" id="addDeviceBtn">Add Equipment</button>
      </div>
    </div>
  </div>

  <!-- Equipment Details Modal -->
  <div class="equipment-details-modal" id="equipmentDetailsModal">
    <div class="modal-content" style="max-width: 1000px; width: 95%;">
      <div class="modal-header">
        <div class="d-flex align-items-center">
          <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo" class="modal-logo me-3" style="width: 50px; height: 50px;">
          <div class="header-text">
            <div class="dept-name" style="font-size: 14px; font-weight: bold;">Department of Environment and Natural Resources</div>
            <div class="dept-name" style="font-size: 12px;">Kagawaran ng Kapaligiran at Likas Yaman</div>
            <div class="region" style="font-size: 12px; color: #666;">Caraga Region</div>
            <div class="office" style="font-size: 12px; color: #666;">CENRO Nasipit, Agusan del Norte</div>
          </div>
        </div>
        <button type="button" class="btn-close" id="closeModal">&times;</button>
      </div>
      <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
        <div class="property-title" style="text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #333;">
          GOVERNMENT PROPERTY DETAILS
        </div>

        <!-- Read-only form layout matching Add New Equipment -->
        <form id="equipmentDetailsForm" autocomplete="off" novalidate>
          <!-- Basic Information -->
          <h6 class="section-title">Basic Information</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Asset ID</label>
                <input type="text" id="detailAssetId" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Property Number</label>
                <input type="text" id="detailPropertyNumber" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Office/Division</label>
                <input type="text" id="detailOfficeDevision" class="form-control" disabled>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Equipment Type</label>
                <input type="text" id="detailEquipmentType" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Year Acquired</label>
                <input type="text" id="detailYearAcquired" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Shelf Life</label>
                <input type="text" id="detailShelfLife" class="form-control" disabled>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Brand</label>
                <input type="text" id="detailBrand" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Model</label>
                <input type="text" id="detailModel" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Serial Number</label>
                <input type="text" id="detailSerialNumber" class="form-control" disabled>
              </div>
            </div>
          </div>

          <!-- Computer Specifications (For Desktop & Laptop) -->
          <h6 class="section-title">Computer Specifications (For Desktop & Laptop)</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Processor</label>
                <input type="text" id="detailProcessor" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Installed Memory RAM Size</label>
                <input type="text" id="detailRamSize" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Installed GPU</label>
                <input type="text" id="detailGpu" class="form-control" disabled>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Range Category</label>
                <input type="text" id="detailRangeCategory" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Computer Name</label>
                <input type="text" id="detailComputerName" class="form-control" disabled>
              </div>
            </div>
          </div>

          <!-- Software Information -->
          <h6 class="section-title">Software Information</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Operating System Version</label>
                <input type="text" id="detailOsVersion" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Office Productivity</label>
                <input type="text" id="detailOfficeProductivity" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Endpoint Protection</label>
                <input type="text" id="detailEndpointProtection" class="form-control" disabled>
              </div>
            </div>
          </div>

          <!-- Property Information -->
          <h6 class="section-title">Property Information</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Property Number (readonly)</label>
                <input type="text" id="detailPropertyNumber2" class="form-control" disabled style="display:none;">
                <!-- kept for exact structure parity; primary prop shown above -->
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Year Acquired (readonly)</label>
                <input type="text" id="detailYearAcquired2" class="form-control" disabled style="display:none;">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Shelf Life (readonly)</label>
                <input type="text" id="detailShelfLife2" class="form-control" disabled style="display:none;">
              </div>
            </div>
          </div>

          <!-- Accountable Person -->
          <h6 class="section-title">Accountable Person</h6>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Accountable Person</label>
                <input type="text" id="detailAccountablePerson" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Sex</label>
                <input type="text" id="detailAccountableSex" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Status of Employment</label>
                <input type="text" id="detailAccountableEmployment" class="form-control" disabled>
              </div>
            </div>
          </div>

          <!-- Actual User -->
          <h6 class="section-title">Actual User</h6>
          <div class="row">
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label">Actual User</label>
                <input type="text" id="detailActualUser" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label">Sex</label>
                <input type="text" id="detailActualUserSex" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label">Status of Employment</label>
                <input type="text" id="detailActualUserEmployment" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label">Nature of Work</label>
                <input type="text" id="detailNatureOfWork" class="form-control" disabled>
              </div>
            </div>
          </div>

          <!-- Remarks -->
          <h6 class="section-title">Remarks</h6>
          <div class="row">
            <div class="col-12">
              <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea id="detailRemarks" class="form-control" rows="3" disabled></textarea>
              </div>
            </div>
          </div>

          <!-- QR Code Section -->
          <div class="details-section text-center">
            <h6 class="section-header">QR Code</h6>
            <img src="../../../../public/assets/images/QR_Code.png" alt="Equipment QR Code" style="width: 150px; height: 150px;" id="detailQrCode">
            <div class="mt-2">
              <button class="btn btn-sm btn-outline-primary" type="button" onclick="printQRCode()">
                <i class="fa fa-print me-1"></i>Print QR Code
              </button>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeEquipmentDetails()">Close</button>
        <button type="button" class="btn btn-primary" onclick="printEquipmentDetails()">
          <i class="fa fa-print me-1"></i>Print Details
        </button>
      </div>
    </div>
  </div>

  <!-- Print Container (Hidden) -->
  <div class="print-container" id="printContainer">
    <div class="print-header">
      <div class="print-logo-section">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo" class="print-logo print-logo-left">
        <div class="print-header-text">
          <div class="line-small">Republic of the Philippines</div>
          <div class="line">Department of Environment and Natural Resources</div>
          <div class="line-small">Caraga Region - CENRO Nasipit</div>
          <div class="line-small">Equipment Management Inventory</div>
        </div>
        <img src="../../../../public/assets/images/bagong-pilipinas-logo.png" alt="Bagong Pilipinas Logo" class="print-logo print-logo-right">
      </div>
      <div class="print-band">RP Government Property</div>
      <div class="print-subband">Equipment List Report</div>
      <div class="print-meta">
        <span>Form Code: CENRO-ICT-INV-01</span>
        <span>Document Type: Government Property Inventory</span>
      </div>
    </div>

    <table class="print-table" id="printTable">
      <thead>
        <tr>
          <th style="width:4%">Asset ID</th>
          <th style="width:6%">Property No.</th>
          <th style="width:5%">Type</th>
          <th style="width:7%">Brand / Model</th>
          <th style="width:4%">Year</th>
          <th style="width:6%">Office/Division</th>
          <th style="width:7%">Accountable Person</th>
          <th style="width:3%">A. Sex</th>
          <th style="width:5%">A. Employment</th>
          <th style="width:6%">Actual User</th>
          <th style="width:3%">U. Sex</th>
          <th style="width:5%">U. Employment</th>
          <th style="width:6%">Nature of Work</th>
          <th style="width:7%">Specs (Proc / RAM / GPU)</th>
          <th style="width:7%">Software / Protection</th>
          <th style="width:5%">Serial No.</th>
          <th style="width:4%">Shelf Life</th>
          <th style="width:4%">Status</th>
          <th style="width:6%">Remarks</th>
        </tr>
      </thead>
      <tbody id="printTableBody">
      </tbody>
    </table>

    <div class="print-footer" style="margin-top:12px;">
      <div style="font-size:11px;">
        <strong>Total Equipment Count:</strong> <span id="totalCount"></span>
        &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Status Filter:</strong> <span id="footerFilter">All</span>
        &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Type Filter:</strong> <span id="footerTypeFilter">All</span>
        &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Generated:</strong> <span id="footerDate"></span>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260315-2"></script>
  <!-- Equipment Service -->
  <script src="../../../../public/assets/js/admin/equipment-service.js"></script>
  <script>
    window.CENRO_QR_VIEW_BASE_URL = <?php echo json_encode(cenro_project_url('public/qr_view.php?id=')); ?>;
  </script>
  <!-- Equipment Management JavaScript -->
  <script src="../../../../public/assets/js/admin/equipment_management.js?v=20260520-table-search"></script>
</body>
</html>
