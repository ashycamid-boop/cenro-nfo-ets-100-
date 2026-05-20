<?php require_once __DIR__ . '/../controllers/view_spot_report_backend.php'; ?>
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
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/view_spot_report.css?v=20260515-mobile4">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/enforcement_officer/view_spot_report.css?v=20260515-mobile2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">
<!-- Layout override: enlarge main content like admin case_details -->

</head>
<body class="admin-dashboard-page enforcement-officer-view-spot-report-page">
  <div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="enforcementOfficerViewSpotReportSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo htmlspecialchars((string)($sidebarRole ?? 'Enforcement Officer'), ENT_QUOTES, 'UTF-8'); ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li class="active"><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_spot_report_sidebar_badge(); ?></a></li>
          <li><a href="case_management.php"><i class="fa fa-briefcase"></i> Case Management</a></li>
          <li><a href="apprehended_items.php"><i class="fa fa-archive"></i> Apprehended Items</a></li>
          <li><a href="service_requests.php"><i class="fa fa-cog"></i> Service Requests</a></li>
          <li><a href="statistical_report.php"><i class="fa fa-chart-bar"></i> Statistical Report</a></li>
        </ul>
      </nav>
    </nav>
    <div class="main">
      <div class="topbar">
          <div class="topbar-card">
            <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="enforcementOfficerViewSpotReportSidebar">
              <i class="fa fa-bars"></i>
            </button>
            <div class="topbar-title">Spot Reports Details</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>
        </div>
      </div>
      <div class="main-content">
        <!-- Action Buttons -->
        <div class="action-buttons mb-3 px-4">
          <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">Back</button>
        </div>
        <!-- View Spot Report Content -->
        <div class="container-fluid p-4">
          <!-- Header Section -->
          <div class="report-header text-center mb-4">
            <div class="d-flex justify-content-between align-items-start">
              <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo" class="logo-left">
              <div class="header-content">
                <h6>Department of Environment and Natural Resources</h6>
                <h6>Kagawaran ng Kapaligiran at Likas Yaman</h6>
                <h6>Caraga Region</h6>
                <h6>CENRO Nasipit, Agusan del Norte</h6>
                <hr style="border-top: 2px solid #ff0000ff; margin: 0px 0 0px 0;">
                <h4 class="mt-3">Spot Report</h4>
              </div>
              <img src="../../../../public/assets/images/bagong-pilipinas-logo.png" alt="Bagong Pilipinas Logo" class="logo-right">
            </div>
          </div>

          <!-- Incident Details Section -->
          <div class="report-section mb-4">
            <table class="table table-bordered">
              <?php if ($report): ?>
              <tr>
                <td class="field-label">Incident Date & Time:</td>
                <td><?= htmlspecialchars(fmt_dt($report['incident_datetime'] ?? '')) ?></td>
                <td class="field-label">Memo Date:</td>
                <td><?= htmlspecialchars(fmt_dt($report['memo_date'] ?? '')) ?></td>
                <td class="field-label">Reference No.:</td>
                <td><?= htmlspecialchars($report['reference_no'] ?? '') ?></td>
              </tr>
              <tr>
                <td class="field-label">Location:</td>
                <td colspan="5"><?= nl2br(htmlspecialchars($report['location'] ?? '')) ?></td>
              </tr>
              <tr>
                <td class="field-label">Summary:</td>
                <td colspan="5"><?= nl2br(htmlspecialchars($report['summary'] ?? '')) ?></td>
              </tr>
              <?php else: ?>
              <tr>
                <td colspan="6">Report not found. Provide a valid reference.</td>
              </tr>
              <?php endif; ?>
            </table>
          </div>

          <!-- Personnel Section -->
          <div class="report-section mb-4">
            <div class="row">
              <div class="col-md-6">
                <table class="table table-bordered">
                  <?php if ($report): ?>
                  <tr>
                    <td class="field-label">Team Leader:</td>
                    <td><?= htmlspecialchars($report['team_leader'] ?? '') ?></td>
                  </tr>
                  <?php endif; ?>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-bordered">
                  <?php if ($report): ?>
                  <tr>
                    <td class="field-label">Member:</td>
                    <td><?= htmlspecialchars($report['custodian'] ?? '') ?></td>
                  </tr>
                  <?php endif; ?>
                </table>
              </div>
            </div>
          </div>

          <!-- Apprehended Persons Section -->
          <div class="report-section mb-4">
            <h6>Apprehended Person(s)</h6>
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Full Name</th>
                  <th>Age</th>
                  <th>Gender</th>
                  <th>Address</th>
                  <th>Contact No.</th>
                  <th>Role/Remarks</th>
                  <th>Status</th>
                  <th>Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($report && !empty($report['persons'])): ?>
                  <?php for ($pi = 0; $pi < count($report['persons']); $pi++): $p = $report['persons'][$pi]; ?>
                    <tr>
                      <td><?= htmlspecialchars($p['name'] ?? '') ?></td>
                      <td><?= htmlspecialchars($p['age'] ?? '') ?></td>
                      <td><?= htmlspecialchars($p['gender'] ?? '') ?></td>
                      <td><?= htmlspecialchars($p['address'] ?? '') ?></td>
                      <td><?= htmlspecialchars($p['contact'] ?? '') ?></td>
                      <td><?= htmlspecialchars($p['role'] ?? '') ?></td>
                      <td><span class="badge bg-light text-dark"><?= htmlspecialchars($p['status'] ?? '-') ?></span></td>
                      <td>
                        <?php
                          $pf = $report['person_files'][$pi] ?? array();
                          if (!empty($pf)) {
                            foreach ($pf as $f) {
                              $icon = file_icon_class($f['path']);
                              $href = web_href($f['path']);
                              $label = !empty($f['orig']) ? $f['orig'] : basename($f['path']);
                              echo '<div class="file-item mb-1">';
                              echo '<a href="' . htmlspecialchars($href) . '" target="_blank" title="' . htmlspecialchars($label) . '"><i class="fa ' . htmlspecialchars($icon) . ' fa-lg"></i></a>';
                              echo '</div>';
                            }
                          } else {
                            echo '<span class="text-muted">-</span>';
                          }
                        ?>
                      </td>
                    </tr>
                  <?php endfor; ?>
                <?php else: ?>
                  <tr><td colspan="8">No apprehended persons recorded.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Vehicles Section -->
          <div class="report-section mb-4">
            <h6>Vehicle(s)</h6>
            <table class="table table-bordered">
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
                </tr>
              </thead>
              <tbody>
                <?php if ($report && !empty($report['vehicles'])): ?>
                  <?php for ($vi = 0; $vi < count($report['vehicles']); $vi++): $v = $report['vehicles'][$vi]; ?>
                    <tr>
                      <td><?= htmlspecialchars($v['plate'] ?? '') ?></td>
                      <td><?= htmlspecialchars($v['make'] ?? '') ?></td>
                      <td><?= htmlspecialchars($v['color'] ?? '') ?></td>
                      <td><?= htmlspecialchars($v['owner'] ?? '') ?></td>
                      <td><?= htmlspecialchars($v['contact'] ?? '') ?></td>
                      <td><?= htmlspecialchars($v['engine'] ?? '') ?></td>
                      <td><?= htmlspecialchars($v['remarks'] ?? '') ?></td>
                      <td><?= htmlspecialchars($v['status'] ?? '') ?></td>
                      <td>
                        <?php
                          $vf = $report['vehicle_files'][$vi] ?? array();
                          if (!empty($vf)) {
                            foreach ($vf as $f) {
                              $icon = file_icon_class($f['path']);
                              $href = web_href($f['path']);
                              $label = !empty($f['orig']) ? $f['orig'] : basename($f['path']);
                              echo '<div class="file-item mb-1">';
                              echo '<a href="' . htmlspecialchars($href) . '" target="_blank" title="' . htmlspecialchars($label) . '"><i class="fa ' . htmlspecialchars($icon) . ' fa-lg"></i></a>';
                              echo '</div>';
                            }
                          } else {
                            echo '<span class="text-muted">-</span>';
                          }
                        ?>
                      </td>
                    </tr>
                  <?php endfor; ?>
                <?php else: ?>
                  <tr><td colspan="9">No vehicles recorded.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Seizure Items Section -->
          <div class="report-section mb-4">
            <h6>Seizure Items</h6>
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Item No.</th>
                  <th>Item Type</th>
                  <th>Description</th>
                  <th>Quantity</th>
                  <th>Dimension (T &times; W &times; L)</th>
                  <th>Volume (Bd.ft./cu.m.)</th>
                  <th>Estimated Value (&#8369;)</th>
                  <th>Remarks No.</th>
                  <th>Status</th>
                  <th>Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($report && !empty($report['items'])): ?>
                  <?php for ($ii = 0; $ii < count($report['items']); $ii++): $it = $report['items'][$ii]; ?>
                    <tr>
                      <td><?= htmlspecialchars($it['no'] ?? '') ?></td>
                      <td><?= htmlspecialchars($it['type'] ?? '') ?></td>
                      <td><?= htmlspecialchars($it['description'] ?? '') ?></td>
                      <td><?= htmlspecialchars($it['quantity'] ?? '') ?></td>
                      <td>
                        <?php
                          $t = $it['thickness'] ?? null;
                          $w = $it['width'] ?? null;
                          $l = $it['length'] ?? null;
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
                      <td>
                        <?php
                          if (isset($it['volume_bdft']) && $it['volume_bdft'] !== null && $it['volume_bdft'] !== '') {
                            echo htmlspecialchars(number_format((float)$it['volume_bdft'], 3));
                          } else {
                            $t2 = $it['thickness_in'] ?? $it['thickness'] ?? null;
                            $w2 = $it['width_in'] ?? $it['width'] ?? null;
                            $l2 = $it['length_ft'] ?? $it['length'] ?? null;
                            $qtyRaw2 = $it['quantity'] ?? null;
                            $qty2 = null;
                            if ($qtyRaw2 !== null && $qtyRaw2 !== '') {
                              if (is_numeric($qtyRaw2)) $qty2 = (float)$qtyRaw2;
                              else {
                                $qnum2 = preg_replace('/[^0-9\.\-]/', '', (string)$qtyRaw2);
                                if ($qnum2 !== '') $qty2 = (float)$qnum2;
                              }
                            }
                            if ($t2 !== null && $w2 !== null && $l2 !== null && $qty2 !== null && is_numeric($t2) && is_numeric($w2) && is_numeric($l2)) {
                              $computed2 = ((float)$t2 * (float)$w2 * (float)$l2) / 12 * (float)$qty2;
                              echo htmlspecialchars(number_format($computed2, 3));
                            } elseif (!empty($it['volume'])) {
                              echo htmlspecialchars($it['volume']);
                            } else {
                              echo '<span class="text-muted">-</span>';
                            }
                          }
                        ?>
                      </td>
                      <td><?= htmlspecialchars($it['value'] ?? '') ?></td>
                      <td><?= htmlspecialchars($it['remarks'] ?? '') ?></td>
                      <td><?= htmlspecialchars($it['status'] ?? '') ?></td>
                      <td>
                        <?php
                          $itf = $report['item_files'][$ii] ?? array();
                          if (!empty($itf)) {
                            foreach ($itf as $f) {
                              $icon = file_icon_class($f['path']);
                              $href = web_href($f['path']);
                              $label = !empty($f['orig']) ? $f['orig'] : basename($f['path']);
                              echo '<div class="file-item mb-1">';
                              echo '<a href="' . htmlspecialchars($href) . '" target="_blank" title="' . htmlspecialchars($label) . '"><i class="fa ' . htmlspecialchars($icon) . ' fa-lg"></i></a>';
                              echo '</div>';
                            }
                          } else {
                            echo '<span class="text-muted">-</span>';
                          }
                        ?>
                      </td>
                    </tr>
                  <?php endfor; ?>
                <?php else: ?>
                  <tr><td colspan="9">No seizure items recorded.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Evidence Section -->
          <div class="report-section mb-4">
            <div class="row">
              <div class="col-md-6">
                <h6>Evidence(s)</h6>
                <div class="evidence-files">
                  <?php if ($report && !empty($report['evidence_files'])): ?>
                    <?php foreach ($report['evidence_files'] as $ef): ?>
                        <div class="file-item mb-2">
                          <?php $icon = file_icon_class($ef); $href = web_href($ef); $label = basename($ef); ?>
                          <a href="<?= htmlspecialchars($href) ?>" target="_blank" title="<?= htmlspecialchars($label) ?>"><i class="fa <?= htmlspecialchars($icon) ?> fa-lg"></i></a>
                        </div>
                      <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-muted">No evidence files attached.</div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-md-6">
                <h6>Spot Report Memorandum (PDF)</h6>
                <div class="pdf-files">
                  <?php if ($report && !empty($report['pdf_files'])): ?>
                    <?php foreach ($report['pdf_files'] as $pf): ?>
                        <div class="file-item mb-2">
                          <?php $icon2 = file_icon_class($pf); $href2 = web_href($pf); $label2 = basename($pf); ?>
                          <a href="<?= htmlspecialchars($href2) ?>" target="_blank" title="<?= htmlspecialchars($label2) ?>"><i class="fa <?= htmlspecialchars($icon2) ?> fa-lg"></i></a>
                        </div>
                      <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-muted">No PDF attachments.</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Section -->
          <div class="report-section mb-4">
            <table class="table table-bordered" style="width: 200px;">
              <tr>
                <td class="text-center">
                  <h6>Status</h6>
                  <?php if ($report):
                    $st = ($report['status'] ?? '');
                    $stNorm = trim($st);
                    $badge = 'secondary';
                    if (strcasecmp($stNorm, 'Draft') === 0) $badge = 'warning';
                    elseif (strcasecmp($stNorm, 'Pending') === 0) $badge = 'warning';
                    elseif (strcasecmp($stNorm, 'Approved') === 0) $badge = 'success';
                    elseif (strcasecmp($stNorm, 'Rejected') === 0) $badge = 'danger';
                    elseif (strcasecmp($stNorm, 'Under Review') === 0 || strcasecmp($stNorm, 'Under_Review') === 0) $badge = 'info';
                  ?>
                    <span class="badge bg-<?= htmlspecialchars($badge) ?> fs-6"><?= htmlspecialchars($st) ?></span>
                  <?php else: ?>
                    <span class="badge bg-secondary fs-6">N/A</span>
                  <?php endif; ?>
                </td>
              </tr>
            </table>
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
  <script src="../../../../public/assets/js/admin/navigation.js?v=20260320-1"></script>
  <script src="../../../../public/assets/js/shared/responsive-report-tables.js?v=20260515-mobile2"></script>
  <script src="../../../../public/assets/js/enforcement_officer/view_spot_report.js"></script>
</body>
</html>


