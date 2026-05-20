<?php require_once __DIR__ . '/../controllers/new_spot_report_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Spot Report - CENRO NASIPIT</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- View Spot Report specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/view_spot_report.css?v=20260320-1">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/enforcer/new_spot_report.css?v=20260320-4">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
  
</head>
<body class="admin-dashboard-page enforcer-new-spot-report-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="enforcerNewSpotReportSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo htmlspecialchars((string)($sidebarRole ?? 'Enforcer'), ENT_QUOTES, 'UTF-8'); ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_enforcer_spot_report_sidebar_badge(); ?></a></li>
          <li><a href="service_requests.php"><i class="fa fa-cog"></i> Service Requests</a></li>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="enforcerNewSpotReportSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">New Spot Report</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <!-- Action Buttons -->
        <div class="action-buttons mb-3 px-4 enforcer-new-spot-actions">
          <button type="button" class="btn btn-secondary enforcer-new-spot-back-btn" onclick="window.history.back()">Back</button>
        </div>
        <!-- New Spot Report Form -->
        <div class="container-fluid p-4 enforcer-new-spot-content">
          <form id="spotReportForm" method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" onsubmit="return handleSubmit(event)" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="">
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                  <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
            <div class="enforcer-new-spot-form-wrap">
            <!-- Header Section -->
            <div class="report-header text-center mb-4">
              <div class="d-flex justify-content-between align-items-start">
                <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo" class="logo-left">
                <div class="header-content">
                  <h6>Department of Environment and Natural Resources</h6>
                  <h6>Kagawaran ng Kapaligiran at Likas Yaman</h6>
                  <h6>Caraga Region</h6>
                  <h6>CENRO Nasipit, Agusan del Norte</h6>
                  <hr style="border-top: 2px solid #ff0000; margin: 5px 0 10px 0;">
                  <h4 class="mt-3">New Spot Report</h4>
                </div>
                <img src="../../../../public/assets/images/bagong-pilipinas-logo.png" alt="Bagong Pilipinas Logo" class="logo-right">
              </div>
            </div>

            <!-- Incident Details Section -->
            <div class="report-section mb-4">
              <h5>Incident Details</h5>
              <div class="row mb-3">
                <div class="col-md-4">
                  <label for="incidentDateTime" class="form-label">Incident Date & Time:</label>
                  <input type="datetime-local" class="form-control" id="incidentDateTime" name="incident_datetime" value="<?php echo htmlspecialchars($old['incident_datetime'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                  <label for="memoDate" class="form-label">Memo Date:</label>
                  <input type="datetime-local" class="form-control" id="memoDate" name="memo_date" value="<?php echo htmlspecialchars($old['memo_date'] ?? ''); ?>" required>
                </div>
                <div class="col-md-4">
                  <label for="referenceNo" class="form-label">Reference No.:</label>
                  <input type="text" class="form-control" id="referenceNo" name="reference_no" value="<?php echo htmlspecialchars($displayRef); ?>" readonly>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-12">
                  <label for="location" class="form-label">Location:</label>
                  <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Brgy Rizal, Buenavista, Agusan del Norte" value="<?php echo htmlspecialchars($old['location'] ?? ''); ?>" required>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-12">
                  <label for="summary" class="form-label">Summary:</label>
                  <textarea class="form-control" id="summary" name="summary" rows="4" placeholder="Detailed description of the incident..." required><?php echo htmlspecialchars($old['summary'] ?? ''); ?></textarea>
                </div>
              </div>
            </div>

            <!-- Personnel Section -->
            <div class="report-section mb-4">
              <h5>Personnel Information</h5>
              <div class="row">
                <div class="col-md-6">
                  <label for="teamLeader" class="form-label">Team Leader:</label>
                  <input type="text" class="form-control" id="teamLeader" name="team_leader" placeholder="Enter team leader name" value="<?php echo htmlspecialchars($old['team_leader'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="custodian" class="form-label">Member:</label>
                  <input type="text" class="form-control" id="custodian" name="custodian" placeholder="Enter member name" value="<?php echo htmlspecialchars($old['custodian'] ?? ''); ?>" required>
                </div>
              </div>
            </div>

            <!-- Apprehended Persons Section -->
            <div class="report-section mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Apprehended Person(s)</h5>
                <button type="button" class="btn btn-primary btn-sm" onclick="addPersonRow()">
                  <i class="fa fa-plus"></i> Add Person
                </button>
              </div>
              <div class="table-responsive">
                <table class="table table-bordered" id="personsTable">
                  <thead>
                    <tr>
                      <th>Full Name</th>
                      <th>Age</th>
                      <th>Gender</th>
                      <th>Address</th>
                      <th>Contact No.</th>
                      <th>Role</th>
                      <th>Status</th>
                      <th>Evidence</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody id="personsTableBody">
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Vehicles Section -->
            <div class="report-section mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Vehicle(s)</h5>
                <button type="button" class="btn btn-primary btn-sm" onclick="addVehicleRow()">
                  <i class="fa fa-plus"></i> Add Vehicle
                </button>
              </div>
              <div class="table-responsive">
                <table class="table table-bordered" id="vehiclesTable">
                  <thead>
                    <tr>
                      <th>Plate No.</th>
                      <th>Make/Model</th>
                      <th>Color</th>
                      <th>Registered Owner Name</th>
                      <th>Contact No.</th>
                      <th>Engine/Chassis No.</th>
                      <th>Remarks</th>
                      <th>Status</th>
                      <th>Evidence</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody id="vehiclesTableBody">
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Seizure Items Section -->
            <div class="report-section mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Seizure Items</h5>
                <button type="button" class="btn btn-primary btn-sm" onclick="addSeizureRow()">
                  <i class="fa fa-plus"></i> Add Item
                </button>
              </div>
              <div class="table-responsive">
                <table class="table table-bordered" id="seizureTable">
                  <thead>
                    <tr>
                      <th>Item No.</th>
                      <th>Item Type</th>
                      <th>Description</th>
                      <th>Quantity</th>
                      <th>Dimension (T × W × L)</th>
                      <th>Volume (Bd.ft./cu.m.)</th>
                      <th>Estimated Value (₱)</th>
                      <th>Remarks</th>
                      <th>Status</th>
                      <th>Evidence</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody id="seizureTableBody">
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Evidence Section -->
            <div class="report-section mb-4">
              <h5>File Attachments</h5>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="evidenceFiles" class="form-label">Evidence Files</label>
                    <input type="file" class="form-control" id="evidenceFiles" name="evidence_files[]" 
                           multiple onchange="updateFileList('evidenceFiles', 'evidenceList')">
                    <div class="form-text">You can select multiple files. Any file format is accepted; upload size follows the server limit.</div>
                    <div id="evidenceList" class="mt-2"></div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="pdfFiles" class="form-label">Documents (PDF)</label>
                    <input type="file" class="form-control" id="pdfFiles" name="pdf_files[]" 
                           accept=".pdf" multiple onchange="updateFileList('pdfFiles', 'pdfList')">
                    <div class="form-text">PDF documents only. Max size: 10MB per file.</div>
                    <div id="pdfList" class="mt-2"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Section -->
            <div class="report-section mb-4">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6>Status: <span class="badge bg-warning">Draft</span></h6>
                </div>
                <div class="enforcer-new-spot-submit-actions">
                  <button type="button" class="btn btn-secondary me-2" onclick="saveDraft()">
                    <i class="fa fa-save"></i> Save as Draft
                  </button>
                  <button type="submit" class="btn btn-primary">
                    <i class="fa fa-paper-plane"></i> Submit Report
                  </button>
                </div>
              </div>
            </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Admin Dashboard JavaScript -->
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260320-1"></script>

    <!-- Spot Report Form JavaScript -->
  <script src="../../../../public/assets/js/enforcer/new_spot_report.js?v=20260521-vehicle-impounded"></script>
</body>
</html>
