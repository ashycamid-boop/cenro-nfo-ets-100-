<?php require_once __DIR__ . '/../controllers/new_requests_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Details</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Service Desk specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/service-desk.css">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/office_staff/new_requests.css?v=20260320-1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard-page service-request-form-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="officeStaffNewRequestsSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role">Office Staff</div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="service_requests.php"><i class="fa fa-cog"></i> Service Requests</a></li>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="officeStaffNewRequestsSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">New Request</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <div class="container-fluid p-4 eo-new-request-content">
          <!-- Back Button -->
          <div class="row mb-3 eo-new-request-back-row">
            <div class="col-12">
              <a href="service_requests.php" class="btn btn-secondary eo-new-request-back-btn">
                <i class="fa fa-arrow-left me-2"></i>Back
              </a>
            </div>
          </div>

          <!-- Service Request Form -->
          <form id="serviceRequestForm" method="POST" action="../controllers/save_request.php" enctype="multipart/form-data">
            <input type="hidden" name="ticket_no" id="ticketNoInput" value="<?php echo htmlspecialchars($ticket_no); ?>">
            <input type="hidden" name="ticket_date" id="ticketDateInput" value="<?php echo htmlspecialchars($current_date); ?>">
          <div class="service-request-sheet-wrap">
          <div class="service-request-sheet" style="max-width: 1100px; margin: 0 auto; background: white; font-family: Arial, sans-serif; font-size: 13px;">
            
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
                  <td style="width: 50%; text-align: right;"><strong style="font-size: 10px;">Auto-generated: <?php echo htmlspecialchars($current_date); ?></strong></td>
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
                    <input type="text" name="requester_name" style="width: 100%; border: none; font-size: 9px; padding: 2px;" value="<?php echo htmlspecialchars($requester_name ?? ''); ?>" required />
                  </td>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; font-weight: bold; width: 12%; font-size: 9px;">Position:</td>
                  <td style="border-bottom: 1px solid black; padding: 5px; width: 38%; font-size: 9px;">
                    <input type="text" name="requester_position" style="width: 100%; border: none; font-size: 9px; padding: 2px;" placeholder="Enter position" required />
                  </td>
                </tr>
                <tr>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; font-weight: bold; font-size: 9px;">Office:</td>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; font-size: 9px;">
                    <input type="text" name="requester_office" style="width: 100%; border: none; font-size: 9px; padding: 2px;" value="" required />
                  </td>
                  <td style="border-right: 1px solid black; border-bottom: 1px solid black; padding: 5px; font-weight: bold; font-size: 9px;">Division/Section:</td>
                  <td style="border-bottom: 1px solid black; padding: 5px; font-size: 9px;">
                    <input type="text" name="requester_division" style="width: 100%; border: none; font-size: 9px; padding: 2px;" value="" required />
                  </td>
                </tr>
                <tr>
                  <td style="border-right: 1px solid black; padding: 5px; font-weight: bold; font-size: 9px;">Phone Number:</td>
                  <td style="border-right: 1px solid black; padding: 5px; font-size: 9px;">
                    <input type="tel" name="requester_phone" style="width: 100%; border: none; font-size: 9px; padding: 2px;" value="<?php echo htmlspecialchars($requester_phone ?? ''); ?>" required />
                  </td>
                  <td style="border-right: 1px solid black; padding: 5px; font-weight: bold; font-size: 9px;">Email Address:</td>
                  <td style="padding: 5px; font-size: 9px;">
                    <input type="email" name="requester_email" style="width: 100%; border: none; font-size: 9px; padding: 2px;" value="<?php echo htmlspecialchars($requester_email ?? ''); ?>" required />
                  </td>
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
                        <td style="padding: 5px; font-size: 9px;">
                          <input type="text" name="request_type" style="width: 100%; border: none; font-size: 9px; padding: 2px; font-weight: bold;" placeholder="Enter type of request" required />
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              
              <div style="padding: 8px;">
                <div style="font-weight: bold; margin-bottom: 5px; font-size: 9px;">DESCRIPTION OF REQUEST (Please clearly write down the details of the request.)</div>
                  <div style="border: 1px solid black; padding: 12px; min-height: 100px; position: relative;">
                  <textarea name="request_description" style="width: 100%; height: 80px; border: none; font-size: 9px; resize: none; outline: none;" placeholder="Enter detailed description of the request..." required></textarea>
                  <div style="position: absolute; bottom: 12px; right: 15px;">
                    <div style="border: 1px solid black; width: 100px; height: 40px; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative; background: #f9f9f9;">
                      <canvas id="requester_signature_pad" style="width:100px; height:40px; touch-action: none;"></canvas>
                      <input type="hidden" name="requester_signature_data" id="requester_signature_data" />
                      <div style="position: absolute; right: 4px; top: 4px;">
                        <button type="button" id="requester_sig_clear" class="btn btn-sm btn-link" style="font-size:8px;padding:0;color:blue;">Clear</button>
                      </div>
                    </div>
                    <div style="font-size: 8px; text-align: center; margin-top: 2px;">Requester Signature</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="d-flex justify-content-end my-3 eo-new-request-actions" style="max-width:1100px; margin:0 auto;">
              <input type="hidden" name="save_draft" id="save_draft" value="">
              <button type="button" class="btn btn-secondary me-2" id="saveDraftBtn">Save Draft</button>
              <button type="submit" class="btn btn-primary" id="submitBtn">Submit Request</button>
            </div>
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
  <!-- Admin Dashboard JavaScript -->
  <script src="../../../../public/assets/js/admin/dashboard.js"></script>
  <!-- Admin Navigation JavaScript -->
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260319-2"></script>
  <!-- Signature Pad library -->
  <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

  <!-- Signature Modal -->
  <div class="modal fade" id="signatureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Please sign below</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div style="width:100%; height:300px; border:1px solid #ddd;">
            <canvas id="signature_modal_canvas" style="width:100%; height:100%; touch-action: none;"></canvas>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="modal_clear">Clear</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="modal_cancel">Cancel</button>
          <button type="button" class="btn btn-primary" id="modal_save">Save Signature</button>
        </div>
      </div>
    </div>
  </div>

  <script src="../../../../public/assets/js/office_staff/new_requests.js?v=20260520-signature-required"></script>
</body>
</html>
