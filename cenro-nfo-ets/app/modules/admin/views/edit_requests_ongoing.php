<?php require_once __DIR__ . '/../controllers/edit_requests_ongoing_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Details</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/admin_signature_helper.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Service Desk specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/service-desk.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/edit_requests_ongoing.css?v=20260515-mobile">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page admin-edit-requests-ongoing-page service-request-form-page" data-current-user-id="<?php echo htmlspecialchars((string)($sessionUserId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="adminEditRequestsOngoingSidebar" role="navigation" aria-label="Main sidebar">
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
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminEditRequestsOngoingSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Edit Details</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid p-4">
          
          <?php
          $request_id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';

          // Load request from DB
          require_once __DIR__ . '/../../../config/db.php';
          // Ensure PDO throws exceptions for easier debugging when available
          if (isset($pdo) && is_object($pdo)) {
            try { $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); } catch (Exception $e) { error_log('Could not set PDO ERRMODE: ' . $e->getMessage()); }
          }
          $request = null;
          $request_found = false;
          if (!empty($request_id)) {
            try {
              // Accept both numeric id and ticket number in one query for safer lookup.
              $stmt = $pdo->prepare('SELECT * FROM service_requests WHERE id = ? OR ticket_no = ? LIMIT 1');
              $stmt->execute([ctype_digit((string)$request_id) ? (int)$request_id : 0, $request_id]);
              $request = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
              $request_found = !empty($request);
            } catch (Exception $e) {
              error_log('request_details fetch error: ' . $e->getMessage());
              $request = null;
              $request_found = false;
            }
          }

          // Populate display variables (fallback to empty)
          $ticket_no = $request['ticket_no'] ?? $request_id ?? '';
          $ticket_date = '';
          if (!empty($request['ticket_date'])) {
            $ticket_date = date('m/d/Y', strtotime($request['ticket_date']));
          } else if (!empty($request['date'])) {
            $ticket_date = date('m/d/Y', strtotime($request['date']));
          } else if (!empty($request['created_at'])) {
            $ticket_date = date('m/d/Y', strtotime($request['created_at']));
          }

          $requester_name = $request['requester_name'] ?? $request['name'] ?? '';
          $requester_position = $request['requester_position'] ?? $request['position'] ?? '';
          $requester_office = $request['requester_office'] ?? $request['office'] ?? '';
          $requester_division = $request['requester_division'] ?? $request['division'] ?? $request['section'] ?? '';
          $requester_phone = $request['requester_phone'] ?? $request['phone'] ?? '';
          $requester_email = $request['requester_email'] ?? $request['email'] ?? '';
          $request_type = $request['request_type'] ?? $request['type'] ?? '';
          $request_description = $request['request_description'] ?? $request['description'] ?? $request['details'] ?? '';

          // Optional auth fields (may be empty if not stored)
          // Do not default to a specific person/position here Ã¢â‚¬â€ show DB values or blank
          $auth1_name = $request['auth1_name'] ?? $request['auth1_fullname'] ?? '';
          $auth1_position = $request['auth1_position'] ?? '';
          $auth2_name = $request['auth2_name'] ?? $request['auth2_fullname'] ?? '';
          $auth2_position = $request['auth2_position'] ?? '';

          // Signature URLs (saved as public/uploads/... in DB)
          $requester_sig_url = !empty($request['requester_signature_path']) ? admin_signature_proxy_url($request['requester_signature_path']) : '';
          $auth1_sig_url = !empty($request['auth1_signature_path']) ? admin_signature_proxy_url($request['auth1_signature_path']) : '';
          $auth2_sig_url = !empty($request['auth2_signature_path']) ? admin_signature_proxy_url($request['auth2_signature_path']) : '';

          // Load existing action rows for this request so they can be displayed in the form
          $request_actions = [];
          try {
            $service_request_id_for_actions = null;
            if (!empty($request['id'])) {
              $service_request_id_for_actions = (int)$request['id'];
            } else if (!empty($request_id) && ctype_digit((string)$request_id)) {
              $service_request_id_for_actions = (int)$request_id;
            } else if (!empty($request_id)) {
              $tstmt = $pdo->prepare('SELECT id FROM service_requests WHERE ticket_no = ? LIMIT 1');
              $tstmt->execute([$request_id]);
              $service_request_id_for_actions = $tstmt->fetchColumn();
            }
            if (!empty($service_request_id_for_actions)) {
              $as = $pdo->prepare('SELECT * FROM service_request_actions WHERE service_request_id = ? ORDER BY created_at ASC');
              $as->execute([$service_request_id_for_actions]);
              $request_actions = $as->fetchAll(PDO::FETCH_ASSOC);
            }
          } catch (Exception $e) {
            error_log('fetch request actions error: ' . $e->getMessage());
            $request_actions = [];
          }
          ?>

          <?php if (!empty($save_error) || !empty($debug_info)): ?>
            <div class="mb-3">
              <div class="alert alert-warning" role="alert">
                <strong>Save diagnostics:</strong>
                <?php if (!empty($save_error)): ?>
                  <div><?php echo htmlspecialchars($save_error); ?></div>
                <?php endif; ?>
                <?php if (!empty($debug_info)): ?>
                  <pre style="white-space:pre-wrap; margin-top:8px; background:#fff; color:#000; padding:8px; border:1px solid #ddd"><?php echo htmlspecialchars(print_r($debug_info, true)); ?></pre>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if (!empty($request_id) && !$request_found): ?>
            <div class="mb-3">
              <div class="alert alert-danger" role="alert">
                Request not found for ID/Ticket: <strong><?php echo htmlspecialchars($request_id); ?></strong>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Professional toolbar: Back left, actions right -->
          <div class="row mb-3">
            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <a href="ongoing_scheduled.php" class="btn btn-outline-secondary btn-sm back-left">
                    <i class="fa fa-arrow-left me-2"></i>Back
                  </a>
                </div>
                <div>
                  <?php if (!empty($request_id) && !empty($request)): ?>
                        <!-- Approve button removed per request -->
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Service Request Form: only staff action rows are editable on ongoing requests. -->
          <form id="editForm" method="post" action="edit_requests_ongoing.php?id=<?php echo urlencode($request_id); ?>" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($request_id); ?>">
            <input type="hidden" name="save_changes" value="1">
            <div style="max-width: 1100px; margin: 0 auto; background: white; font-family: Arial, sans-serif; font-size: 14px;">
            
            <!-- Header Section with Border -->
            <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
              <tr>
                <td rowspan="2" style="width: 100px; text-align: center; vertical-align: middle; padding: 8px; border-right: 1px solid black;">
                  <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo" style="width: 70px; height: 70px;">
                </td>
                <td style="text-align: center; vertical-align: middle; padding: 12px; border-right: 1px solid black; border-bottom: 1px solid black;">
                  <div style="font-size: 16px; font-weight: bold; margin-bottom: 3px;">DENR-PENRO AGUSAN DEL NORTE</div>
                  <div style="font-size: 12px;">Information and Communication Technology Unit (ICTU)</div>
                </td>
                <td style="width: 200px; padding: 0; border-bottom: 1px solid black;">
                  <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                    <tr>
                      <td style="border-bottom: 1px solid black; border-right: 1px solid black; padding: 4px; font-weight: bold; width: 60%;">Department ID No.</td>
                      <td style="border-bottom: 1px solid black; padding: 4px; text-align: center;">R13-CN-FO-003</td>
                    </tr>
                    <tr>
                      <td style="border-right: 1px solid black; padding: 4px; font-weight: bold;">Revision No.</td>
                      <td style="padding: 4px; text-align: center;">1</td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td style="text-align: center; vertical-align: middle; padding: 12px; border-right: 1px solid black; font-size: 14px; font-weight: bold;">
                  SERVICE REQUEST FROM (SRF)
                </td>
                <td style="width: 200px; padding: 0;">
                  <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                    <tr>
                      <td style="border-right: 1px solid black; padding: 4px; font-weight: bold; width: 60%;">Effectivity</td>
                      <td style="padding: 4px; text-align: center;">9/1/2022</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- Reminder Section -->
            <div style="padding: 10px; border-left: 1px solid black; border-right: 1px solid black;">
              <p style="margin-bottom: 10px; font-size: 9px; line-height: 1.2; text-align: justify;">
                <strong>Reminder:</strong> Please complete this form and submit it at the PENRO ICT Unit Service Desk located on the ground floor PENRO Agusan del Norte Building, Tiniwisan, Butuan City or email a scanned a copy to <span style="color: blue;">ictu@denr.gov.ph</span>. Once processed, a Technical Support Representative will contact you to schedule service.
              </p>
              
              <table style="width: 100%; margin-bottom: 10px;">
                <tr>
                  <td style="width: 50%;"><strong style="font-size: 10px;">Ticket No: <?php echo htmlspecialchars($ticket_no); ?></strong></td>
                  <td style="width: 50%; text-align: right;"><strong style="font-size: 10px;">Date (mm/dd/yyyy): <?php echo htmlspecialchars($ticket_date); ?></strong></td>
                </tr>
              </table>
            </div>

            <!-- Requester's Information -->
            <div style="border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black;">
              <table style="width: 100%; border-collapse: collapse;">
                <tr>
                  <td style="background-color: #f0f0f0; padding: 5px 10px; border-bottom: 1px solid black; font-weight: bold; font-size: 10px;">
                    Requester's Information
                  </td>
                </tr>
              </table>
              <table style="width: 100%; border-collapse: collapse;">
                <tr>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; font-weight: bold; width: 12%; font-size: 9px;">Name:</td>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; width: 38%; font-size: 9px;">
                    <?php echo htmlspecialchars($requester_name); ?>
                  </td>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; font-weight: bold; width: 12%; font-size: 9px;">Position:</td>
                  <td style="border-bottom: 1px solid black; padding: 5px; width: 38%; font-size: 9px;"><?php echo htmlspecialchars($requester_position); ?></td>
                </tr>
                <tr>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; font-weight: bold; font-size: 9px;">Office:</td>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; font-size: 9px;"><?php echo htmlspecialchars($requester_office); ?></td>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; font-weight: bold; font-size: 9px;">Division/Section:</td>
                  <td style="border-bottom: 1px solid black; padding: 5px; font-size: 9px;"><?php echo htmlspecialchars($requester_division); ?></td>
                </tr>
                <tr>
                  <td style="border-right: 1px solid black; padding: 5px; font-weight: bold; font-size: 9px;">Phone Number:</td>
                  <td style="border-right: 1px solid black; padding: 5px; font-size: 9px;">
                    <?php echo htmlspecialchars($requester_phone); ?>
                  </td>
                  <td style="border-right: 1px solid black; padding: 5px; font-weight: bold; font-size: 9px;">Email Address:</td>
                  <td style="padding: 5px; font-size: 9px;"><?php echo htmlspecialchars($requester_email); ?></td>
                </tr>
              </table>
            </div>

            <!-- Request Information -->
            <div style="border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black;">
              <table style="width: 100%; border-collapse: collapse;">
                <tr>
                  <td style="background-color: #f0f0f0; padding: 5px 10px; border-bottom: 1px solid black; font-weight: bold; font-size: 10px;">
                    Request Information
                  </td>
                </tr>
                <tr>
                  <td style="border-bottom: 1px solid black; padding: 5px;">
                    <table style="width: 100%; border-collapse: collapse;">
                      <tr>
                        <td style="border-right: 1px solid black; padding: 5px; font-weight: bold; width: 20%; font-size: 9px;">Type of Request:</td>
                        <td style="padding: 5px; font-weight: bold; font-size: 9px;"><?php echo htmlspecialchars($request_type); ?></td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              
              <div style="padding: 8px;">
                <div style="font-weight: bold; margin-bottom: 5px; font-size: 9px;">DESCRIPTION OF REQUEST (Please clearly write down the details of the request.)</div>
                <div style="border: 1px solid black; padding: 12px; min-height: 100px; position: relative;">
                  <div style="font-size: 9px;">
                    <?php echo nl2br(htmlspecialchars($request_description)); ?>
                  </div>
                    <div style="position: absolute; bottom: 12px; right: 15px; text-align: center;">
                    <?php if (!empty($requester_sig_url)): ?>
                      <img src="<?php echo htmlspecialchars($requester_sig_url); ?>" alt="Requester Signature" style="max-width:140px; height:auto; display:block; margin-bottom:4px;" />
                    <?php else: ?>
                      <div style="font-family: 'Brush Script MT', cursive; font-size: 12px; font-style: italic; color: #003366; text-align: center; margin-bottom: 3px;">
                        <?php echo htmlspecialchars($requester_name); ?>
                      </div>
                      <div style="border-bottom: 1px solid black; width: 100px; margin-bottom: 2px;"></div>
                    <?php endif; ?>
                    <div style="font-size: 8px;">Requester Signature</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Authorization Section -->
            <div style="border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black;">
              <table style="width: 100%; border-collapse: collapse;">
                <tr>
                  <td style="background-color: #f0f0f0; padding: 5px 10px; border-bottom: 1px solid black; font-weight: bold; font-size: 10px;">
                    Authorization
                  </td>
                </tr>
              </table>
              
              <div style="padding: 6px;">
                <p style="font-size: 8px; margin: 0 0 6px 0; line-height: 1.1; text-align: justify;">
                  All requests for service must be approved by the appropriate manager/supervisor (at least division chief, OIC, immediate supervisor or head clerk staff of the requester). By signing below, the manager/supervisor certifies that the service is required.
                </p>
              </div>
              
              <table style="width: 100%; border-collapse: collapse; border-top: 1px solid black; border-bottom: 1px solid black;">
                <tr>
                  <td style="border-right: 1px solid black; padding: 4px; font-weight: bold; width: 15%; font-size: 9px;">Full Name:</td>
                  <td style="border-right: 1px solid black; padding: 2px; width: 35%;">
                    <div style="width: 100%; font-size: 9px; padding: 2px;"><?php echo htmlspecialchars($auth1_name); ?></div>
                  </td>
                  <td style="border-right: 1px solid black; padding: 4px; font-weight: bold; width: 20%; font-size: 9px;">Title/Position:</td>
                  <td style="padding: 2px; width: 30%;">
                    <div style="width: 100%; font-size: 9px; padding: 2px;"><?php echo htmlspecialchars($auth1_position); ?></div>
                  </td>
                </tr>
              </table>

              <table style="width: 100%; border-collapse: collapse;">
                <tr>
                  <td style="width: 50%; padding: 4px; border-right: 1px solid black;">
                    <div style="border: 1px solid black; text-align: center; height: 50px; display: flex; align-items: center; justify-content: center; padding: 6px;">
                      <?php if (!empty($auth1_sig_url)): ?>
                        <img id="auth1_preview" src="<?php echo htmlspecialchars($auth1_sig_url); ?>" alt="Auth1 Signature" style="max-height:48px; max-width:100%; display:block;" />
                      <?php else: ?>
                        <div id="auth1_preview" style="width:100%; height:100%;"></div>
                      <?php endif; ?>
                    </div>
                    <div style="text-align:center; font-size:8px; margin-top:6px;">
                      <div style="border-bottom:1px solid #000; width:140px; margin:0 auto 4px; height:0;"></div>
                      <div style="font-size:9px;">Signature (Manager/Supervisor)</div>
                    </div>
                  </td>
                  <td style="width: 50%; padding: 4px;">
                    <div style="border: 1px solid black; text-align: center; height: 50px; display: flex; align-items: center; justify-content: center; padding: 2px;">
                      <div style="font-size: 8px; text-align: center; width: 100%;"><?php echo !empty($request['auth1_date']) ? htmlspecialchars($request['auth1_date']) : ''; ?></div>
                    </div>
                  </td>
                </tr>
              </table>
            </div>

            <!-- Infrastructure Service Authorization -->
            <div style="border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black;">
              <table style="width: 100%; border-collapse: collapse;">
                <tr>
                  <td style="background-color: #f0f0f0; padding: 5px 10px; border-bottom: 1px solid black; font-weight: bold; font-size: 10px;">
                    Infrastructure Service Authorization
                  </td>
                </tr>
              </table>
              
              <div style="padding: 6px;">
                <p style="font-size: 8px; margin: 0 0 6px 0; line-height: 1.1; text-align: justify;">
                  All requests for service must be approved by the appropriate manager/supervisor (at least division chief, OIC, immediate supervisor or head clerk staff of the requester). By signing below, the manager/supervisor certifies that the service is required.
                </p>
              </div>
              
              <table style="width: 100%; border-collapse: collapse; border-top: 1px solid black; border-bottom: 1px solid black;">
                <tr>
                  <td style="border-right: 1px solid black; padding: 4px; font-weight: bold; width: 15%; font-size: 9px;">Full Name:</td>
                  <td style="border-right: 1px solid black; padding: 2px; width: 35%;">
                    <div style="width: 100%; font-size: 9px; padding: 2px;"><?php echo htmlspecialchars($auth2_name); ?></div>
                  </td>
                  <td style="border-right: 1px solid black; padding: 4px; font-weight: bold; width: 20%; font-size: 9px;">Title/Position:</td>
                  <td style="padding: 2px; width: 30%;">
                    <div style="width: 100%; font-size: 9px; padding: 2px;"><?php echo htmlspecialchars($auth2_position); ?></div>
                  </td>
                </tr>
              </table>

              <table style="width: 100%; border-collapse: collapse; border-bottom: 1px solid black;">
                <tr>
                  <td style="width: 50%; padding: 4px; border-right: 1px solid black;">
                    <div style="border: 1px solid black; text-align: center; height: 50px; display: flex; align-items: center; justify-content: center; padding: 6px;">
                      <?php if (!empty($auth2_sig_url)): ?>
                        <img id="auth2_preview" src="<?php echo htmlspecialchars($auth2_sig_url); ?>" alt="Auth2 Signature" style="max-height:48px; max-width:100%; display:block;" />
                      <?php else: ?>
                        <div id="auth2_preview" style="width:100%; height:100%;"></div>
                      <?php endif; ?>
                    </div>
                    <div style="text-align:center; font-size:8px; margin-top:6px;">
                      <div style="border-bottom:1px solid #000; width:140px; margin:0 auto 4px; height:0;"></div>
                      <div style="font-size:9px;">Signature (Manager/Supervisor)</div>
                    </div>
                  </td>
                  <td style="width: 50%; padding: 4px;">
                    <div style="border: 1px solid black; text-align: center; height: 50px; display: flex; align-items: center; justify-content: center; padding: 2px;">
                      <div style="font-size: 8px; text-align: center; width: 100%;"><?php echo !empty($request['auth2_date']) ? htmlspecialchars($request['auth2_date']) : ''; ?></div>
                    </div>
                  </td>
                </tr>
              </table>

              <div style="padding: 6px;">
                <p style="font-weight: bold; font-size: 9px;">For PENRO ICT Staff only (Use back of the Form or Separate sheet if necessary)</p>
              </div>
            </div>

            <!-- Staff Table -->
            <?php
            // Fetch available staff, plus any previously assigned staff so saved names do not disappear.
            try {
              $stmt = $pdo->prepare("
                SELECT id, full_name, role
                FROM users
                WHERE full_name IS NOT NULL
                  AND TRIM(full_name) <> ''
                  AND (
                    (status = 1 AND REPLACE(TRIM(LOWER(role)), ' ', '_') IN ('admin', 'property_custodian'))
                    OR id IN (
                      SELECT DISTINCT action_staff_id
                      FROM service_request_actions
                      WHERE service_request_id = ?
                        AND action_staff_id IS NOT NULL
                    )
                  )
                ORDER BY full_name ASC
              ");
              $stmt->execute([(int)($service_request_id_for_actions ?? 0)]);
              $staff_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
              $staff_users = [];
              error_log('fetch staff users error: ' . $e->getMessage());
            }
            ?>
            <div style="border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black;">
              <table style="width: 100%; border-collapse: collapse;">
                <thead>
                  <tr>
                    <th style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 4px; width: 15%; text-align: center; font-weight: bold; font-size: 9px;">Date</th>
                    <th style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 4px; width: 15%; text-align: center; font-weight: bold; font-size: 9px;">Time</th>
                    <th style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 4px; width: 40%; text-align: center; font-weight: bold; font-size: 9px;">Action Details</th>
                    <th style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 4px; width: 15%; text-align: center; font-weight: bold; font-size: 9px;">Action Staff</th>
                    <th style="border-bottom: 1px solid black; padding: 4px; width: 15%; text-align: center; font-weight: bold; font-size: 9px;">Signature</th>
                  </tr>
                </thead>
                <tbody id="actions_tbody">
                  <?php if (!empty($request_actions)): ?>
                    <?php foreach ($request_actions as $idx => $act): $i = $idx + 1; ?>
                      <tr data-action-row="<?php echo $i; ?>">
                        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px; height: 25px;">
                          <input type="date" name="action_date[]" value="<?php echo htmlspecialchars($act['action_date']); ?>" style="width: 100%; border: none; font-size: 8px; padding: 2px;" />
                        </td>
                        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px;">
                          <input type="time" name="action_time[]" value="<?php echo htmlspecialchars($act['action_time']); ?>" style="width: 100%; border: none; font-size: 8px; padding: 2px;" />
                        </td>
                        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px;">
                          <textarea name="action_details[]" style="width: 100%; border: none; font-size: 8px; padding: 2px; height: 20px; resize: none;" placeholder="Action details..."><?php echo htmlspecialchars($act['action_details']); ?></textarea>
                        </td>
                        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px;">
                          <select name="action_staff[]" class="form-select form-select-sm" style="width:100%; border:none; font-size:8px; padding:2px;">
                            <option value="">-- Select staff --</option>
                            <?php foreach ($staff_users as $su): ?>
                              <option value="<?php echo htmlspecialchars($su['id']); ?>" <?php echo ($su['id'] == $act['action_staff_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($su['full_name']); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </td>
                        <td style="border-bottom: 1px solid black; padding: 2px;">
                          <div class="signature-box" data-field="action_sig_<?php echo $i; ?>" data-staff-id="<?php echo htmlspecialchars($act['action_staff_id']); ?>" style="border: 1px solid #000; height:40px; display:flex; align-items:center; justify-content:center; padding:4px; cursor:pointer;">
                            <?php if (!empty($act['action_signature_path'])): ?>
                              <img id="action_sig_<?php echo $i; ?>_preview" src="<?php echo htmlspecialchars(admin_signature_proxy_url($act['action_signature_path'])); ?>" style="max-height:48px; max-width:100%; display:block;" />
                            <?php else: ?>
                              <div id="action_sig_<?php echo $i; ?>_preview" style="width:100%; height:100%;"></div>
                            <?php endif; ?>
                          </div>
                          <input type="hidden" name="action_signature_data[]" id="action_sig_<?php echo $i; ?>_signature_data" value="">
                          <input type="hidden" name="action_existing_signature_path[]" value="<?php echo htmlspecialchars($act['action_signature_path']); ?>">
                          <input type="hidden" name="action_old_staff_id[]" value="<?php echo htmlspecialchars($act['action_staff_id']); ?>">
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <?php for ($i=1; $i<=4; $i++): ?>
                      <tr data-action-row="<?php echo $i; ?>">
                        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px; height: 25px;">
                          <input type="date" name="action_date[]" style="width: 100%; border: none; font-size: 8px; padding: 2px;" />
                        </td>
                        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px;">
                          <input type="time" name="action_time[]" style="width: 100%; border: none; font-size: 8px; padding: 2px;" />
                        </td>
                        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px;">
                          <textarea name="action_details[]" style="width: 100%; border: none; font-size: 8px; padding: 2px; height: 20px; resize: none;" placeholder="Action details..."></textarea>
                        </td>
                        <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 2px;">
                          <select name="action_staff[]" class="form-select form-select-sm" style="width:100%; border:none; font-size:8px; padding:2px;">
                            <option value="">-- Select staff --</option>
                            <?php foreach ($staff_users as $su): ?>
                              <option value="<?php echo htmlspecialchars($su['id']); ?>"><?php echo htmlspecialchars($su['full_name']); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </td>
                        <td style="border-bottom: 1px solid black; padding: 2px;">
                          <div class="signature-box" data-field="action_sig_<?php echo $i; ?>" data-staff-id="" style="border: 1px solid #000; height:40px; display:flex; align-items:center; justify-content:center; padding:4px; cursor:pointer;">
                            <div id="action_sig_<?php echo $i; ?>_preview" style="width:100%; height:100%;"></div>
                          </div>
                          <input type="hidden" name="action_signature_data[]" id="action_sig_<?php echo $i; ?>_signature_data" value="">
                          <input type="hidden" name="action_existing_signature_path[]" value="">
                          <input type="hidden" name="action_old_staff_id[]" value="">
                        </td>
                      </tr>
                    <?php endfor; ?>
                  <?php endif; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="5" style="padding:8px; text-align:left;">
                      <button type="button" id="addActionRow" class="btn btn-sm btn-outline-secondary">+ Add Row</button>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Feedback Section -->
            <div style="border-left: 1px solid black; border-right: 1px solid black;">
              <table style="width: 100%; border-collapse: collapse;">
                <tr>
                  <td style="padding: 8px;">
                    <div style="font-size: 9px; margin-bottom: 6px;">
                      <strong>Feedback Rating:</strong> 
                      <input type="checkbox" id="excellent" name="feedback_rating" value="excellent" disabled>
                      <label for="excellent"> Excellent</label>
                      <input type="checkbox" id="very_satisfactory_feed" name="feedback_rating" value="very_satisfactory" style="margin-left: 10px;" disabled>
                      <label for="very_satisfactory_feed"> Very Satisfactory</label>
                      <input type="checkbox" id="below_satisfactory" name="feedback_rating" value="below_satisfactory" style="margin-left: 10px;" disabled>
                      <label for="below_satisfactory"> Below Satisfactory</label>
                      <input type="checkbox" id="poor" name="feedback_rating" value="poor" style="margin-left: 10px;" disabled>
                      <label for="poor"> Poor</label>
                    </div>
                    <div style="margin-bottom: 6px; font-size: 9px;">
                      <?php $isCompleted = !empty($request['status']) && strtolower($request['status']) === 'completed'; ?>
                      <input type="checkbox" id="completed_checkbox" name="completed_checkbox" value="1" <?php echo $isCompleted ? 'checked disabled' : ''; ?> />
                      <label for="completed_checkbox"> Completed</label>
                      <!-- completeForm moved below outside the main edit form to avoid accidental submission -->
                    </div>
                    <div style="font-weight: bold; font-size: 9px; margin-bottom: 6px;">Acknowledged by:</div>
                  </td>
                </tr>
              </table>
            </div>

            <!-- Footer -->
            <div style="border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black; padding: 12px;">
              <table style="width: 100%; margin-bottom: 10px;">
                <tr>
                  <td style="width: 50%; padding-right: 10px;">
                    <div style="border-bottom: 1px solid black; height: 20px; margin-bottom: 2px; position: relative;">
                      <span style="position: absolute; bottom: 2px; left: 0; width: 100%; font-size: 10px; font-weight:600; color: #222;">
                        <?php echo htmlspecialchars($requester_name ?: '[Client will sign here]'); ?>
                      </span>
                    </div>
                    <div style="font-size: 8px;">Signature over printed name</div>
                  </td>
                  <td style="width: 50%; padding-left: 10px;">
                    <div style="border-bottom: 1px solid black; height: 20px; margin-bottom: 2px; position: relative;">
                      <input type="datetime-local" style="position: absolute; bottom: 2px; left: 0; width: 100%; border: none; font-size: 8px; background: transparent;" />
                    </div>
                    <div style="font-size: 8px;">Date/Time</div>
                  </td>
                </tr>
              </table>

              <div style="text-align: right;">
                <div style="font-size: 8px; font-weight: bold;">Ref: NIMD Service Request Form 22 March 2021</div>
              </div>
            </div>

            <div class="d-flex justify-content-end mt-2 mb-4">
              <button form="editForm" type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            </div>

          </div>
          </form>

            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Signature Pad -->
  <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
  <!-- Admin Dashboard JavaScript -->
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260319-2"></script>

  <!-- Hidden complete form (kept outside editForm so Save Changes doesn't submit status_action) -->
  <form id="completeForm" method="post" style="display:none;">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($request_id); ?>" />
    <input type="hidden" name="status_action" value="complete" />
  </form>

  <template id="actionStaffOptionsTemplate">
    <option value="">-- Select staff --</option>
    <?php foreach ($staff_users as $su): ?>
      <option value="<?php echo htmlspecialchars($su['id']); ?>"><?php echo htmlspecialchars($su['full_name']); ?></option>
    <?php endforeach; ?>
  </template>

  <!-- Signature Pad Modal + Handler -->
  <div class="modal fade" id="signatureModal" tabindex="-1" aria-labelledby="signatureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="signatureModalLabel">Draw Signature</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <canvas id="sigCanvas" style="border:1px solid #ccc; width:100%; height:200px;"></canvas>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" id="sigClear">Clear</button>
          <button type="button" class="btn btn-primary btn-sm" id="sigSave">Save</button>
        </div>
      </div>
    </div>
  </div>
  <script src="../../../../public/assets/js/admin/edit_requests_ongoing.js?v=20260517-action-staff-sign"></script>
</body>
</html>
