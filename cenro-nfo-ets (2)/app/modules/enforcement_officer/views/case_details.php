<?php require_once __DIR__ . '/../controllers/case_details_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Case Details - CENRO NASIPIT</title>
  <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
  <?php require_once __DIR__ . '/../../../views/partials/spot_report_badge.php'; ?>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Admin common styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/common.css?v=20260515-sidebar">
  <!-- Case Details specific styles -->
  <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/case_details.css?v=20260515-mobile2">
  <link rel="stylesheet" href="../../../../public/assets/css/modules/enforcement_officer/case_details.css?v=20260520-print-form-layout">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Fredoka+One:400&display=swap" rel="stylesheet">


</head>
<body class="admin-dashboard-page enforcement-officer-case-details-page" data-report-id="<?php echo (int)($report['id'] ?? 0); ?>" data-update-status-url="<?php echo htmlspecialchars(build_file_url('app/modules/enforcement_officer/actions/update_status.php'), ENT_QUOTES, 'UTF-8'); ?>">
<div class="layout">
    <button class="mobile-sidebar-backdrop" type="button" aria-label="Close sidebar"></button>
    <!-- Sidebar -->
    <nav class="sidebar" id="enforcementOfficerCaseDetailsSidebar" role="navigation" aria-label="Main sidebar">
      <div class="sidebar-logo">
        <img src="../../../../public/assets/images/denr-logo.png" alt="DENR Logo">
        <span>CENRO</span>
      </div>
      <div class="sidebar-role"><?php echo htmlspecialchars((string)($sidebarRole ?? 'Enforcement Officer'), ENT_QUOTES, 'UTF-8'); ?></div>
      <nav class="sidebar-nav" aria-label="Sidebar menu">
        <ul>
          <li><a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a></li>
          <li><a href="spot_reports.php"><i class="fa fa-file-text"></i> Spot Reports<?php echo render_spot_report_sidebar_badge(); ?></a></li>
          <li class="active"><a href="case_management.php"><i class="fa fa-briefcase"></i> Case Management</a></li>
          <li><a href="apprehended_items.php"><i class="fa fa-archive"></i> Apprehended Items</a></li>
          <li><a href="service_requests.php"><i class="fa fa-cog"></i> Service Requests</a></li>
          <li><a href="statistical_report.php"><i class="fa fa-chart-bar"></i> Statistical Report</a></li>
        </ul>
      </nav>
    </nav>
    <!-- Main -->
    <div class="main">
      <div class="topbar topbar-header">
        <div class="topbar-card">
          <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="enforcementOfficerCaseDetailsSidebar">
            <i class="fa fa-bars"></i>
          </button>
          <div class="topbar-title">Case Details</div>
          <?php include __DIR__ . '/../../shared/views/topbar_profile.php'; ?>

</div>
      </div>
      <div class="main-content">
        <!-- Action Buttons -->
        <div class="action-buttons mb-3 px-4">
          <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">Back</button>
          <button type="button" class="btn btn-primary me-2" onclick="printReport()" title="Print">Print</button>
        </div>
        <!-- Case Details Content -->
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
                <hr style="border-top: 4px solid #ff0000; margin: 5px 0 10px 0;">
                <h4 class="mt-3">Spot Report</h4>
                <?php if ($report): ?>
                  <div class="small text-muted mt-1">Reference: <?php echo htmlspecialchars($report['reference_no'] ?? '-'); ?></div>
                <?php endif; ?>
              </div>
              <img src="../../../../public/assets/images/bagong-pilipinas-logo.png" alt="Bagong Pilipinas Logo" class="logo-right">
            </div>
          </div>

          <!-- Main Details Table -->
          <div class="report-section mb-4">
            <table class="table table-bordered">
              <tr>
                <td class="field-label">Incident Date & Time:</td>
                <td><?php echo $report && !empty($report['incident_datetime']) ? htmlspecialchars(date('M d, Y g:i a', strtotime($report['incident_datetime']))) : '-'; ?></td>
                <td class="field-label">Memo Date:</td>
                <td><?php echo $report && !empty($report['memo_date']) ? htmlspecialchars(date('M d, Y g:i a', strtotime($report['memo_date']))) : '-'; ?></td>
                <td class="field-label">Reference No.:</td>
                <td><?php echo $report ? htmlspecialchars($report['reference_no']) : '-'; ?></td>
              </tr>
              <tr>
                <td class="field-label">Location:</td>
                <td colspan="5"><?php echo $report ? htmlspecialchars($report['location'] ?? '-') : '-'; ?></td>
              </tr>
              <tr>
                <td class="field-label">Summary:</td>
                <td colspan="5"><?php echo $report ? nl2br(htmlspecialchars($report['summary'] ?? '-')) : '-'; ?></td>
              </tr>
              <tr>
                <td class="field-label">Team Leader:</td>
                <td colspan="2"><?php echo $report ? htmlspecialchars($report['team_leader'] ?? '-') : '-'; ?></td>
                <td class="field-label">Custodian:</td>
                <td colspan="2"><?php echo $report ? htmlspecialchars($report['custodian'] ?? '-') : '-'; ?></td>
              </tr>
            </table>
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
                    <th class="evidence-col">Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($apprehended_persons)): ?>
                  <?php for ($pi = 0; $pi < count($apprehended_persons); $pi++): $p = $apprehended_persons[$pi]; ?>
                    <?php
                      $person_name = $p['full_name'] ?? $p['name'] ?? '-';
                      $person_age = $p['age'] ?? '-';
                      $person_gender = $p['gender'] ?? '-';
                      $person_address = $p['address'] ?? '-';
                      $person_contact = $p['contact_no'] ?? $p['contact'] ?? '-';
                      $person_role = $p['role'] ?? '-';
                      $person_status = isset($p['status']) ? $p['status'] : '';
                      $person_id = (int)($p['id'] ?? 0);
                      $pfiles = $person_files[$pi] ?? array();
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($person_name); ?></td>
                      <td><?php echo htmlspecialchars($person_age); ?></td>
                      <td><?php echo htmlspecialchars($person_gender); ?></td>
                      <td><?php echo htmlspecialchars($person_address); ?></td>
                      <td><?php echo htmlspecialchars($person_contact); ?></td>
                      <td><?php echo htmlspecialchars($person_role); ?></td>
                      <?php
                        $personBadge = map_status_to_class($person_status);
                        $personStatusNormalized = strtolower(trim((string)$person_status));
                        $isConvitedStyle = ($personStatusNormalized === 'convited' || $personStatusNormalized === 'convicted');
                        $personStatusDisplay = ($personStatusNormalized === 'convited') ? 'Convicted' : (string)$person_status;
                        $personStatusExtraClass = $isConvitedStyle ? ' status-convicted' : '';
                      ?>
                      <td>
                        <?php if (trim((string)$person_status) !== ''): ?>
                          <span class="badge <?php echo $personBadge . $personStatusExtraClass; ?>" id="person-status-<?php echo $person_id; ?>"><?php echo htmlspecialchars($personStatusDisplay); ?></span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td class="evidence-col">
                        <?php if (!empty($pfiles)): ?>
                          <?php foreach ($pfiles as $fentry): $href = $fentry['path'] ?? ''; $label = $fentry['orig'] ?? basename($href); $ext = strtolower(pathinfo($label, PATHINFO_EXTENSION)); $iconClass = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'fa-image text-primary' : ($ext === 'pdf' ? 'fa-file-pdf text-danger' : 'fa-file text-secondary'); ?>
                            <div style="display:inline-block; margin-right:8px;">
                              <a class="evidence-link" href="<?php echo htmlspecialchars(build_file_url($href)); ?>" target="_blank" title="<?php echo htmlspecialchars($label); ?>" style="color:inherit; text-decoration:none;">
                                <i class="fa <?php echo $iconClass; ?>" style="font-size:18px;"></i>
                              </a>
                            </div>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endfor; ?>
                  <?php else: ?>
                  <tr><td colspan="8" class="text-center">No apprehended persons recorded.</td></tr>
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
                  <th class="evidence-col">Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($vehicles)): ?>
                  <?php for ($vi = 0; $vi < count($vehicles); $vi++): $v = $vehicles[$vi]; ?>
                    <?php
                      $vehicle_plate = $v['plate_no'] ?? $v['plate'] ?? '-';
                      $vehicle_make = $v['make_model'] ?? $v['make'] ?? '-';
                      $vehicle_color = $v['color'] ?? '-';
                      $vehicle_owner = $v['registered_owner'] ?? $v['owner'] ?? '-';
                      $vehicle_contact = $v['contact_no'] ?? $v['contact'] ?? '-';
                      $vehicle_engine = $v['engine_chassis_no'] ?? $v['engine'] ?? '-';
                      $vehicle_status = $v['status'] ?? 'For Custody';
                      $vehicle_id = (int)($v['id'] ?? 0);
                      $vfiles = $vehicle_files[$vi] ?? array();
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($vehicle_plate); ?></td>
                      <td><?php echo htmlspecialchars($vehicle_make); ?></td>
                      <td><?php echo htmlspecialchars($vehicle_color); ?></td>
                      <td><?php echo htmlspecialchars($vehicle_owner); ?></td>
                      <td><?php echo htmlspecialchars($vehicle_contact); ?></td>
                      <td><?php echo htmlspecialchars($vehicle_engine); ?></td>
                      <td><?php echo htmlspecialchars($v['remarks'] ?? ''); ?></td>
                      <?php $vehicleBadge = map_status_to_class($vehicle_status); ?>
                      <td><span class="badge <?php echo $vehicleBadge; ?>" id="vehicle-status-<?php echo $vehicle_id; ?>"><?php echo htmlspecialchars($vehicle_status); ?></span></td>
                      <td class="evidence-col">
                        <?php if (!empty($vfiles)): ?>
                          <?php foreach ($vfiles as $fentry): $href = $fentry['path'] ?? ''; $label = $fentry['orig'] ?? basename($href); $ext = strtolower(pathinfo($label, PATHINFO_EXTENSION)); $iconClass = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'fa-image text-primary' : ($ext === 'pdf' ? 'fa-file-pdf text-danger' : 'fa-file text-secondary'); ?>
                            <div style="display:inline-block; margin-right:8px;">
                              <a class="evidence-link" href="<?php echo htmlspecialchars(build_file_url($href)); ?>" target="_blank" title="<?php echo htmlspecialchars($label); ?>" style="color:inherit; text-decoration:none;">
                                <i class="fa <?php echo $iconClass; ?>" style="font-size:18px;"></i>
                              </a>
                            </div>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endfor; ?>
                <?php else: ?>
                  <tr><td colspan="9" class="text-center">No vehicles recorded.</td></tr>
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
                  <th class="evidence-col">Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($items)): ?>
                  <?php for ($ii = 0; $ii < count($items); $ii++): $it = $items[$ii]; ?>
                    <?php
                      $item_no = $it['item_no'] ?? $it['id'] ?? '-';
                      $item_type = $it['item_type'] ?? $it['type'] ?? '-';
                      $item_desc = $it['description'] ?? '-';
                      $item_qty = $it['quantity'] ?? '-';
                      $item_vol = $it['volume'] ?? '-';
                      $item_val = isset($it['value']) ? number_format((float)$it['value'], 2) : '-';
                      $item_remarks = $it['remarks'] ?? '-';
                      $item_status = $it['status'] ?? 'For Custody';
                      $item_id = (int)($it['id'] ?? 0);
                      $ifiles = $item_files[$ii] ?? array();
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($item_no); ?></td>
                      <td><?php echo htmlspecialchars($item_type); ?></td>
                      <td><?php echo htmlspecialchars($item_desc); ?></td>
                      <td><?php echo htmlspecialchars($item_qty); ?></td>
                      <td>
                        <?php
                          $t = $it['thickness_in'] ?? $it['thickness'] ?? null;
                          $w = $it['width_in'] ?? $it['width'] ?? null;
                          $l = $it['length_ft'] ?? $it['length'] ?? null;
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
                            $t6 = $it['thickness_in'] ?? $it['thickness'] ?? null;
                            $w6 = $it['width_in'] ?? $it['width'] ?? null;
                            $l6 = $it['length_ft'] ?? $it['length'] ?? null;
                            $qtyRaw6 = $it['quantity'] ?? null;
                            $qty6 = null;
                            if ($qtyRaw6 !== null && $qtyRaw6 !== '') {
                              if (is_numeric($qtyRaw6)) $qty6 = (float)$qtyRaw6;
                              else {
                                $qnum6 = preg_replace('/[^0-9\.\-]/', '', (string)$qtyRaw6);
                                if ($qnum6 !== '') $qty6 = (float)$qnum6;
                              }
                            }
                            if ($t6 !== null && $w6 !== null && $l6 !== null && $qty6 !== null && is_numeric($t6) && is_numeric($w6) && is_numeric($l6)) {
                              $computed6 = ((float)$t6 * (float)$w6 * (float)$l6) / 12 * (float)$qty6;
                              echo htmlspecialchars(number_format($computed6, 3));
                            } elseif (!empty($item_vol)) {
                              echo htmlspecialchars($item_vol);
                            } else {
                              echo '<span class="text-muted">-</span>';
                            }
                          }
                        ?>
                      </td>
                      <td><?php echo is_string($item_val) ? htmlspecialchars($item_val) : htmlspecialchars(number_format((float)$item_val,2)); ?></td>
                      <td><?php echo htmlspecialchars($item_remarks); ?></td>
                      <?php $itemBadge = map_status_to_class($item_status); ?>
                      <td><span class="badge <?php echo $itemBadge; ?>" id="item-status-<?php echo $item_id; ?>"><?php echo htmlspecialchars($item_status); ?></span></td>
                      <td class="evidence-col">
                        <?php if (!empty($ifiles)): ?>
                          <?php foreach ($ifiles as $fentry): $href = $fentry['path'] ?? ''; $label = $fentry['orig'] ?? basename($href); $ext = strtolower(pathinfo($label, PATHINFO_EXTENSION)); $iconClass = in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'fa-image text-primary' : ($ext === 'pdf' ? 'fa-file-pdf text-danger' : 'fa-file text-secondary'); ?>
                            <div style="display:inline-block; margin-right:8px;">
                              <a class="evidence-link" href="<?php echo htmlspecialchars(build_file_url($href)); ?>" target="_blank" title="<?php echo htmlspecialchars($label); ?>" style="color:inherit; text-decoration:none;">
                                <i class="fa <?php echo $iconClass; ?>" style="font-size:18px;"></i>
                              </a>
                            </div>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endfor; ?>
                <?php else: ?>
                  <tr><td colspan="9" class="text-center">No seizure items recorded.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Evidence and Status Section -->
          <div class="report-section mb-4 no-print">
            <table class="table table-bordered">
              <tr>
                <td class="field-label" style="width: 150px;">Evidence(s)</td>
                <td style="width: 250px;">
                  <div class="evidence-files">
                    <?php if (!empty($other_evidences)): ?>
                      <div class="d-flex flex-wrap gap-2">
                      <?php foreach ($other_evidences as $f): ?>
                        <?php
                          $orig = $f['orig_name'] ?? $f['orig'] ?? $f['file_name'] ?? basename($f['file_path'] ?? $f['path'] ?? 'file');
                          $webpath = $f['file_path'] ?? $f['path'] ?? '';
                          $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                          // Skip PDF here so it only shows in the "Spot Report Memorandum (PDF)" column
                          if ($ext === 'pdf') continue;
                        ?>
                        <div class="file-item mb-2 text-center" style="width:36px;">
                          <?php if ($webpath && in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                            <a href="<?php echo htmlspecialchars(build_file_url($webpath)); ?>" target="_blank" title="<?php echo htmlspecialchars($orig); ?>" class="d-block text-center" style="text-decoration:none;">
                              <i class="fa fa-image text-primary" style="font-size:20px; display:block; margin:6px auto 2px;"></i>
                            </a>
                          <?php else: ?>
                            <?php
                              $iconClass = 'fa-file text-secondary';
                              if (in_array($ext, ['pdf'])) $iconClass = 'fa-file-pdf text-danger';
                              if (in_array($ext, ['doc','docx'])) $iconClass = 'fa-file-word text-primary';
                              if (in_array($ext, ['xls','xlsx','csv'])) $iconClass = 'fa-file-excel text-success';
                            ?>
                            <div style="display:flex; align-items:center; gap:8px; justify-content:center;">
                              <?php if ($webpath): ?>
                                <a href="<?php echo htmlspecialchars(build_file_url($webpath)); ?>" target="_blank" title="<?php echo htmlspecialchars($orig); ?>" style="text-decoration:none; color:inherit;">
                                  <i class="fa <?php echo $iconClass; ?>" style="font-size:18px;"></i>
                                </a>
                              <?php else: ?>
                                <i class="fa <?php echo $iconClass; ?> text-muted" style="font-size:18px;"></i>
                              <?php endif; ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <div class="text-muted">No evidence files uploaded.</div>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="field-label" style="width: 200px;">Spot Report Memorandum (PDF)</td>
                <td>
                  <div class="pdf-files">
                    <?php
                    $pdfShown = false;
                    foreach ($other_evidences as $f) {
                      $orig = $f['orig'] ?? $f['file_name'] ?? basename($f['path'] ?? $f['file_path'] ?? 'file');
                      $webpath = $f['path'] ?? $f['file_path'] ?? '';
                      $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                        if ($ext === 'pdf' || stripos($webpath, '.pdf') !== false) {
                        if ($webpath) {
                          echo '<div class="file-item mb-2"><a href="' . htmlspecialchars(build_file_url($webpath)) . '" target="_blank" title="' . htmlspecialchars($orig) . '"><i class="fa fa-file-pdf text-danger" style="font-size:18px;"></i></a></div>';
                        } else {
                          echo '<div class="file-item mb-2"><i class="fa fa-file-pdf text-danger" style="font-size:18px;"></i></div>';
                        }
                        $pdfShown = true;
                      }
                    }
                    if (!$pdfShown) echo '<div class="text-muted">No memorandum PDF attached.</div>';
                    ?>
                  </div>
                </td>
              </tr>
            </table>
          </div>

          <!-- Status Section -->
          <div class="report-section mb-4">
            <table class="table table-bordered" style="width: 280px;">
              <tr>
                <td class="text-center">
                  <div><strong>Case Status</strong></div>
                  <div class="mt-2 d-flex align-items-center justify-content-center gap-2">
                      <?php
                        $caseStatusDisplay = $report ? ($report['case_status'] ?? $report['status'] ?? 'Under Investigation') : 'Under Investigation';
                        $caseStatusKey = strtolower(trim($caseStatusDisplay));
                        // Normalize: remove extra whitespace and unify separators
                        $norm = preg_replace('/[^a-z0-9]+/', ' ', $caseStatusKey);
                        $caseBadgeClass = 'bg-secondary';

                        if (strpos($norm, 'under') !== false && strpos($norm, 'investig') !== false) {
                          $caseBadgeClass = 'bg-primary';
                        } elseif (strpos($norm, 'for filing') !== false || strpos($norm, 'for filing') !== false || strpos($norm, 'for filing') !== false) {
                          $caseBadgeClass = 'bg-warning';
                        } elseif (strpos($norm, 'ongoing') !== false || strpos($norm, 'trial') !== false) {
                          $caseBadgeClass = 'bg-info';
                        } elseif (strpos($norm, 'filed') !== false && strpos($norm, 'court') !== false) {
                          $caseBadgeClass = 'bg-secondary';
                        } elseif (strpos($norm, 'dismiss') !== false) {
                          $caseBadgeClass = 'bg-danger';
                        } elseif (strpos($norm, 'resolv') !== false || strpos($norm, 'resolved') !== false) {
                          $caseBadgeClass = 'bg-success';
                        } elseif (strpos($norm, 'archiv') !== false) {
                          $caseBadgeClass = 'bg-dark';
                        }
                      ?>
                      <span class="badge <?php echo $caseBadgeClass; ?>" id="case-status"><?php echo htmlspecialchars($caseStatusDisplay); ?></span>
                      <button class="btn btn-sm btn-outline-primary" onclick="editCaseStatus()" title="Edit Case Status">
                        <i class="fa fa-edit"></i>
                      </button>
                    </div>
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
  <!-- Case Details JavaScript -->
  <script src="../../../../public/assets/js/admin/case-details.js"></script>
  <!-- Case Details Status Management -->
  <script src="../../../../public/assets/js/enforcement_officer/case_details.js"></script>
</body>
</html>


