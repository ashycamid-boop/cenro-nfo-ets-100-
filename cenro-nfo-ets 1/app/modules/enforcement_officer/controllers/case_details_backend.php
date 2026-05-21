<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$sidebarRole = 'Enforcement Officer';
// Load report data based on ?ref= or ?id=
$refParam = $_GET['ref'] ?? $_GET['id'] ?? null;
$report = null;
$apprehended_persons = [];
$vehicles = [];
$items = [];
$evidences = [];
if ($refParam) {
  try {
    require_once dirname(dirname(dirname(__DIR__))) . '/config/db.php'; // loads $pdo

    $stmt = $pdo->prepare("SELECT * FROM spot_reports WHERE reference_no = ? OR id = ? LIMIT 1");
    $stmt->execute([$refParam, $refParam]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($report) {
      $rid = $report['id'];
      // Apprehended persons (if table exists)
      try {
        $sp = $pdo->prepare("SELECT * FROM spot_report_persons WHERE report_id = ? ORDER BY id ASC");
        $sp->execute([$rid]);
        $apprehended_persons = $sp->fetchAll(PDO::FETCH_ASSOC);
      } catch (Exception $e) { $apprehended_persons = []; }

      // Vehicles
      try {
        $sv = $pdo->prepare("SELECT * FROM spot_report_vehicles WHERE report_id = ? ORDER BY id ASC");
        $sv->execute([$rid]);
        $vehicles = $sv->fetchAll(PDO::FETCH_ASSOC);
      } catch (Exception $e) { $vehicles = []; }

      // Items
      try {
        $si = $pdo->prepare("SELECT * FROM spot_report_items WHERE report_id = ? ORDER BY id ASC");
        $si->execute([$rid]);
        $items = $si->fetchAll(PDO::FETCH_ASSOC);
      } catch (Exception $e) { $items = []; }

      // Files / evidences
      try {
        $sf = $pdo->prepare("SELECT * FROM spot_report_files WHERE report_id = ? ORDER BY id ASC");
        $sf->execute([$rid]);
        $evidences = $sf->fetchAll(PDO::FETCH_ASSOC);
      } catch (Exception $e) { $evidences = []; }

      // Group evidences by row index when orig_name encodes owner (person#N:, vehicle#N:, item#N:)
      $person_files = [];
      $vehicle_files = [];
      $item_files = [];
      $other_evidences = [];
      foreach ($evidences as $f) {
        $orig = $f['orig_name'] ?? $f['file_name'] ?? basename($f['file_path'] ?? $f['path'] ?? '');
        $path = $f['file_path'] ?? $f['path'] ?? '';
        if (preg_match('/^(person|vehicle|item)#(\d+):(.+)$/', $orig, $m)) {
          $type = $m[1]; $idx = (int)$m[2]; $name = $m[3];
          $entry = ['path' => $path, 'orig' => $name];
          if ($type === 'person') {
            if (!isset($person_files[$idx])) $person_files[$idx] = [];
            $person_files[$idx][] = $entry;
          } elseif ($type === 'vehicle') {
            if (!isset($vehicle_files[$idx])) $vehicle_files[$idx] = [];
            $vehicle_files[$idx][] = $entry;
          } elseif ($type === 'item') {
            if (!isset($item_files[$idx])) $item_files[$idx] = [];
            $item_files[$idx][] = $entry;
          }
        } else {
          $other_evidences[] = ['path' => $path, 'orig' => $orig];
        }
      }
    }
  } catch (Exception $e) {
    $report = null;
  }
}
?>
 <?php
 // Helper to build file URLs that work whether BASE_URL is defined
 if (!function_exists('build_file_url')) {
   function build_file_url($href) {
     if (empty($href)) return '';
     // If already absolute URL, return as-is
     if (preg_match('#^(https?:)?//#i', $href)) return $href;
     $href = '/' . ltrim($href, '/');
     $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
     $appPos = strpos($scriptName, '/app/');
     $basePath = $appPos !== false ? substr($scriptName, 0, $appPos) : '';

     // Determine project root (4 levels up from this views folder)
     $projectRoot = dirname(__DIR__, 4);

     // If file is stored under public dir, ensure URL contains /public
     if (file_exists($projectRoot . $href)) {
       // file exists at projectRoot + href (rare)
     } elseif (file_exists($projectRoot . '/public' . $href)) {
       $href = '/public' . $href;
     }

     if (defined('BASE_URL') && BASE_URL && BASE_URL !== '/') {
       return rtrim(BASE_URL, '/') . $href;
     }

     return $basePath . $href;
   }
 }
?>
<?php
// Map status text to badge CSS class so server-rendered badges match client updates
if (!function_exists('map_status_to_class')) {
  function map_status_to_class($s) {
    $sRaw = strtolower(trim((string)$s));
    if ($sRaw === '') return 'bg-secondary';

    // Person statuses (match apprehended_items mapping)
    if (strpos($sRaw, 'for custody') !== false || strpos($sRaw, 'for-custody') !== false || $sRaw === 'for custody' || $sRaw === 'for-custody') return 'bg-warning';
    if ($sRaw === 'in custody' || $sRaw === 'in-custody') return 'bg-info';
    if ($sRaw === 'detained') return 'bg-danger';
    if ($sRaw === 'bailed') return 'bg-cyan';
    if ($sRaw === 'released') return 'bg-success';
    if (strpos($sRaw, 'transferred') !== false) return 'bg-purple';
    if ($sRaw === 'convicted' || $sRaw === 'convited') return 'bg-dark';
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
?>


