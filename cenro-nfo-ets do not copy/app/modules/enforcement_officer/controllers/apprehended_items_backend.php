<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$sidebarRole = 'Enforcement Officer';
// Load vehicles and seizure items for the Apprehended Items list
$items = [];
try {
  require_once dirname(__DIR__, 3) . '/config/db.php'; // loads $pdo

  // Helper: map item/vehicle status text to badge class used elsewhere
  if (!function_exists('map_status_to_class')) {
    function map_status_to_class($s) {
      $sRaw = strtolower(trim((string)$s));
      if ($sRaw === '') return 'bg-secondary';

      // Person statuses (case_detailsupdate mapping)
      if (strpos($sRaw, 'for custody') !== false || strpos($sRaw, 'for-custody') !== false || $sRaw === 'for custody' || $sRaw === 'for-custody') return 'bg-warning';
      if ($sRaw === 'in custody' || $sRaw === 'in-custody') return 'bg-info';
      if ($sRaw === 'detained') return 'bg-danger';
      if ($sRaw === 'bailed') return 'bg-cyan';
      if ($sRaw === 'released') return 'bg-success';
      if (strpos($sRaw, 'transferred') !== false) return 'bg-purple';
      if ($sRaw === 'convicted') return 'bg-dark';
      if ($sRaw === 'acquitted') return 'bg-teal';

      // Item/vehicle statuses
      if (strpos($sRaw, 'confiscat') !== false) return 'bg-warning';
      if (strpos($sRaw, 'seized') !== false) return 'bg-info';
      if (strpos($sRaw, 'under-custody') !== false || strpos($sRaw, 'under custody') !== false) return 'bg-primary';
      if (strpos($sRaw, 'for disposal') !== false || strpos($sRaw, 'for-disposal') !== false) return 'bg-orange';
      if (strpos($sRaw, 'disposed') !== false) return 'bg-success';
      if (strpos($sRaw, 'burn') !== false || strpos($sRaw, 'destroy') !== false) return 'bg-danger';
      if (strpos($sRaw, 'forfeited') !== false) return 'bg-purple';
      if (strpos($sRaw, 'donat') !== false || strpos($sRaw, 'donated') !== false) return 'bg-teal';
      if (strpos($sRaw, 'returned') !== false) return 'bg-cyan';
      if (strpos($sRaw, 'auction') !== false) return 'bg-indigo';

      // Fallbacks
      if (strpos($sRaw, 'custody') !== false) return 'bg-warning';
      if (strpos($sRaw, 'impound') !== false || strpos($sRaw, 'impounded') !== false) return 'bg-info';
      return 'bg-secondary';
    }
  }

  // Helper: build URL for stored file paths
  if (!function_exists('build_file_url_local')) {
    function build_file_url_local($path) {
      if (empty($path)) return '';
      if (preg_match('#^(https?:)?//#i', $path)) return $path;
      $p = '/' . ltrim($path, '/');
      $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
      $appPos = strpos($scriptName, '/app/');
      $basePath = $appPos !== false ? substr($scriptName, 0, $appPos) : '';
      $projectRoot = dirname(__DIR__, 4);
      // If file exists under project root or /public, prefer those web paths
      if (file_exists($projectRoot . $p)) {
        if (defined('BASE_URL') && BASE_URL && BASE_URL !== '/') return rtrim(BASE_URL, '/') . $p;
        return $basePath . $p;
      }
      if (file_exists($projectRoot . '/public' . $p)) {
        if (defined('BASE_URL') && BASE_URL && BASE_URL !== '/') return rtrim(BASE_URL, '/') . '/public' . $p;
        return $basePath . '/public' . $p;
      }
      // If file path already starts with /public or /uploads, and BASE_URL is defined, prepend it
      if (defined('BASE_URL') && BASE_URL && BASE_URL !== '/') return rtrim(BASE_URL, '/') . $p;
      // fallback to host absolute URL
      return $basePath . $p;
    }
  }

  // Vehicles (only approved cases - check parent spot_reports.status)
  $vstmt = $pdo->prepare("SELECT v.*, r.reference_no FROM spot_report_vehicles v JOIN spot_reports r ON r.id = v.report_id WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'approved' ORDER BY v.id DESC");
  $vstmt->execute();
  $vehicles = $vstmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($vehicles as $v) {
    $plate = $v['plate_no'] ?? $v['plate'] ?? '';
    $make = $v['make_model'] ?? $v['make'] ?? '';
    $color = $v['color'] ?? '';
    $descParts = array_filter([$make, $color, $plate]);
    $description = implode(', ', $descParts);

    $items[] = [
      'type' => 'vehicle',
      'type_label' => 'Vehicle',
      'reference_no' => $v['reference_no'] ?? '',
      'description' => $description,
      'quantity' => 1,
      'volume' => '-',
      'evidence' => '',
      'status_label' => $v['status'] ?? '',
      'status_class' => map_status_to_class($v['status'] ?? ''),
      'report_id' => $v['report_id'] ?? $v['reportId'] ?? null,
      'last_updated' => $v['updated_at'] ?? $v['created_at'] ?? ''
    ];
  }

  // Seizure / Items
  // Seizure / Items (only approved cases - check parent spot_reports.status)
  $istmt = $pdo->prepare("SELECT i.*, r.reference_no FROM spot_report_items i JOIN spot_reports r ON r.id = i.report_id WHERE LOWER(TRIM(COALESCE(r.status,''))) = 'approved' ORDER BY i.id DESC");
  $istmt->execute();
  $seizures = $istmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($seizures as $s) {
    $items[] = [
      'type' => 'item',
      'type_label' => $s['item_type'] ?? $s['type'] ?? 'Item',
      'reference_no' => $s['reference_no'] ?? '',
      'description' => $s['description'] ?? '',
      'quantity' => $s['quantity'] ?? '',
      'volume' => $s['volume'] ?? '',
      'volume_bdft' => $s['volume_bdft'] ?? null,
      'thickness_in' => $s['thickness_in'] ?? null,
      'width_in' => $s['width_in'] ?? null,
      'length_ft' => $s['length_ft'] ?? null,
      'thickness' => $s['thickness'] ?? null,
      'width' => $s['width'] ?? null,
      'length' => $s['length'] ?? null,
      'evidence' => '',
      'status_label' => $s['status'] ?? '',
      'status_class' => map_status_to_class($s['status'] ?? ''),
      'report_id' => $s['report_id'] ?? $s['reportId'] ?? null,
      'last_updated' => $s['updated_at'] ?? $s['created_at'] ?? ''
    ];
  }

  // Attach files/evidence: fetch files for involved reports
  $reportIds = array_values(array_filter(array_unique(array_map(function($it){ return $it['report_id'] ?? null; }, $items))));
  if (!empty($reportIds)) {
    $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
    $ff = $pdo->prepare("SELECT * FROM spot_report_files WHERE report_id IN ($placeholders) ORDER BY id ASC");
    $ff->execute($reportIds);
    $files = $ff->fetchAll(PDO::FETCH_ASSOC);
    $filesByReport = [];
    foreach ($files as $f) {
      $rid = $f['report_id'] ?? null;
      if (!isset($filesByReport[$rid])) $filesByReport[$rid] = [];
      $filesByReport[$rid][] = $f;
    }

    foreach ($items as &$it) {
      $rid = $it['report_id'] ?? null;
      $ehtml = '';
      if ($rid && !empty($filesByReport[$rid])) {
        // prefer files that reference the same kind (vehicle/item) in orig_name
        $chosen = null;
        foreach ($filesByReport[$rid] as $f) {
          $orig = strtolower($f['orig_name'] ?? $f['file_name'] ?? basename($f['file_path'] ?? $f['path'] ?? ''));
          if ($it['type'] === 'vehicle' && strpos($orig, 'vehicle') !== false) { $chosen = $f; break; }
          if ($it['type'] === 'item' && strpos($orig, 'item') !== false) { $chosen = $f; break; }
        }
        if (!$chosen) $chosen = $filesByReport[$rid][0];

        $fpath = $chosen['file_path'] ?? $chosen['path'] ?? $chosen['file_name'] ?? '';
        $url = build_file_url_local($fpath);
        $ext = strtolower(pathinfo($fpath, PATHINFO_EXTENSION));
        // Render an icon-only link (no thumbnail or filename) - clickable to open the file
        $iconClass = 'fa-file';
        $typeAttr = 'file';
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
          $iconClass = 'fa-image';
          $typeAttr = 'image';
        } elseif ($ext === 'pdf') {
          $iconClass = 'fa-file-pdf';
          $typeAttr = 'pdf';
        }
        $title = htmlspecialchars($chosen['orig_name'] ?? $chosen['file_name'] ?? basename($fpath));
        $ehtml = '<a href="' . htmlspecialchars($url) . '" target="_blank" title="' . $title . '" class="evidence-icon" data-type="' . $typeAttr . '"><i class="fa ' . $iconClass . '"></i></a>';
      }
      $it['evidence'] = $ehtml;
    }
    unset($it);
  }

} catch (Exception $e) {
  // If DB fails, leave $items empty so view shows the "No apprehended items found." message
  $items = [];
}
// Ensure items are shown in ascending reference number order (natural/date-like)
if (!empty($items) && is_array($items)) {
  usort($items, function($a, $b) {
    $ra = $a['reference_no'] ?? '';
    $rb = $b['reference_no'] ?? '';
    // Reverse order: newest/large reference first
    $c = strcmp($rb, $ra);
    if ($c !== 0) return $c;
    // Tie-breaker: keep consistent reverse order
    return strcmp($b['type'] ?? '', $a['type'] ?? '');
  });
}
?>
