<?php
session_start();

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'enforcement_officer') {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $appPos = strpos($scriptName, '/app/');
    $basePath = $appPos !== false ? substr($scriptName, 0, $appPos) : '';
    header('Location: ' . $basePath . '/index.php');
    exit;
}

$sidebarRole = 'Enforcement Officer';

// Load spot report JSON by reference (ref GET parameter)
$report = null;
if (!empty($_GET['ref'])) {
  $ref = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['ref']);
  $jsonPath = __DIR__ . '/../../../../storage/spot_reports/' . $ref . '.json';
  if (is_readable($jsonPath)) {
    $content = file_get_contents($jsonPath);
    $report = json_decode($content, true);
  }
}

// Debug helper: when ?debug=1 is present, output file path resolution info
if (!empty($_GET['debug']) && $report) {
  echo "<pre style='background:#f8f9fa;border:1px solid #ddd;padding:10px;'>File resolution debug:\n";
  $all = [];
  foreach (['evidence_files','pdf_files','person_files','vehicle_files','item_files'] as $k) {
    if (!empty($report[$k])) {
      if (is_array($report[$k])) {
        foreach ($report[$k] as $idx => $v) {
          if (is_array($v)) {
            // person/vehicle/item entries are arrays of arrays with 'path'
            foreach ($v as $vv) {
              $all[] = $vv['path'] ?? $vv;
            }
          } else {
            $all[] = $v;
          }
        }
      }
    }
  }
  $public = realpath(__DIR__ . '/../../../../public');
  foreach ($all as $ap) {
    $href = web_href($ap);
    $real = @realpath($ap);
    $cand = '';
    if ($public) {
      $p = str_replace('\\','/',$ap);
      if (stripos($p,'/public/')!==false) {
        $parts = explode('/public/',$p,2);
        $cand = $public . '/' . $parts[1];
      } elseif (stripos($p,'public/')===0) {
        $cand = $public . '/' . substr($p,strlen('public/'));
      } elseif (stripos($p,'uploads/')===0) {
        $cand = $public . '/' . $p;
      }
    }
    echo "path: ".htmlspecialchars($ap)."\n  web_href: ".htmlspecialchars($href)."\n  realpath: ".htmlspecialchars($real ?: '') ."\n  public_candidate: ".htmlspecialchars($cand ?: '') ."\n  exists(realpath): ".(is_file($real)?'yes':'no') ."\n  exists(candidate): ".(is_file($cand)?'yes':'no') ."\n\n";
  }
  echo "</pre>";
}

// If JSON not found, try loading from database (static view)
if (!$report && !empty($ref)) {
  try {
    require_once dirname(dirname(dirname(__DIR__))) . '/config/db.php'; // loads $pdo
    $s = $pdo->prepare('SELECT * FROM spot_reports WHERE reference_no = ? LIMIT 1');
    $s->execute([$ref]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    if ($r) {
      $report = [];
      $report['reference_no'] = $r['reference_no'];
      $report['incident_datetime'] = $r['incident_datetime'];
      $report['memo_date'] = $r['memo_date'];
      $report['location'] = $r['location'];
      $report['summary'] = $r['summary'];
      $report['team_leader'] = $r['team_leader'];
      $report['custodian'] = $r['custodian'];
      $report['status'] = $r['status'];

      // persons (include status)
      $pstmt = $pdo->prepare('SELECT name, age, gender, address, contact, role, status FROM spot_report_persons WHERE report_id = ?');
      $pstmt->execute([$r['id']]);
      $report['persons'] = $pstmt->fetchAll(PDO::FETCH_ASSOC);

      // vehicles (include status and remarks)
      $vstmt = $pdo->prepare('SELECT plate, make, color, owner, contact, engine, status, remarks FROM spot_report_vehicles WHERE report_id = ?');
      $vstmt->execute([$r['id']]);
      $report['vehicles'] = $vstmt->fetchAll(PDO::FETCH_ASSOC);

      // items (include status and new numeric dimensions)
      $itstmt = $pdo->prepare('SELECT item_no, type, description, quantity, volume, value, remarks, status, thickness_in, width_in, length_ft, volume_bdft FROM spot_report_items WHERE report_id = ?');
      $itstmt->execute([$r['id']]);
      $items = $itstmt->fetchAll(PDO::FETCH_ASSOC);
      $report['items'] = array_map(function($it){
        return [
          'no' => $it['item_no'],
          'type' => $it['type'],
          'description' => $it['description'],
          'quantity' => $it['quantity'],
          'volume' => $it['volume'],
          'value' => $it['value'],
          'remarks' => $it['remarks'],
          'status' => isset($it['status']) ? $it['status'] : '',
          'thickness' => isset($it['thickness_in']) ? $it['thickness_in'] : null,
          'width' => isset($it['width_in']) ? $it['width_in'] : null,
          'length' => isset($it['length_ft']) ? $it['length_ft'] : null,
          'volume_bdft' => isset($it['volume_bdft']) ? $it['volume_bdft'] : null
        ];
      }, $items);

      // files: group by orig_name tokens (person#N:, vehicle#N:, item#N:) when present
      $fst = $pdo->prepare('SELECT file_type, file_path, orig_name FROM spot_report_files WHERE report_id = ?');
      $fst->execute([$r['id']]);
      $files = $fst->fetchAll(PDO::FETCH_ASSOC);
      $report['evidence_files'] = [];
      $report['pdf_files'] = [];
      $report['person_files'] = [];
      $report['vehicle_files'] = [];
      $report['item_files'] = [];
      foreach ($files as $f) {
        $path = $f['file_path'];
        $orig = $f['orig_name'] ?? '';
        if ($f['file_type'] === 'pdf') {
          $report['pdf_files'][] = $path;
          continue;
        }
        // expect orig_name like: person#0:photo.jpg or vehicle#1:photo.png or item#2:doc.jpg
        if (preg_match('/^(person|vehicle|item)#(\d+):(.+)$/', $orig, $m)) {
          $type = $m[1];
          $idx = intval($m[2]);
          $origfn = $m[3];
          $entry = ['path' => $path, 'orig' => $origfn];
          if ($type === 'person') {
            if (!isset($report['person_files'][$idx])) $report['person_files'][$idx] = [];
            $report['person_files'][$idx][] = $entry;
          } elseif ($type === 'vehicle') {
            if (!isset($report['vehicle_files'][$idx])) $report['vehicle_files'][$idx] = [];
            $report['vehicle_files'][$idx][] = $entry;
          } elseif ($type === 'item') {
            if (!isset($report['item_files'][$idx])) $report['item_files'][$idx] = [];
            $report['item_files'][$idx][] = $entry;
          }
        } else {
          // not mapped to a specific row
          $report['evidence_files'][] = $path;
        }
      }
    }
  } catch (Exception $e) {
    // ignore DB errors, $report stays null
  }
}

// Helper: format datetime for display
function fmt_dt($dt) {
  if (empty($dt)) return '';
  $ts = strtotime($dt);
  if (!$ts) return htmlspecialchars($dt);
  return date('M j, Y g:i a', $ts);
}

// Helper: human readable filesize
function hr_filesize_from_webpath($webpath) {
  if (!$webpath) return '';
  // Try to find the physical file and return its filesize.
  $fs = null;
  $p = str_replace('\\', '/', $webpath);
  // 1) If path is an absolute filesystem path
  $real = @realpath($webpath);
  if ($real && is_file($real)) {
    $fs = filesize($real);
  }
  // 2) Try mapping via document root + web_href
  if ($fs === null) {
    $docroot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    if ($docroot) {
      $href = web_href($webpath);
      if ($href && strpos($href, '/') === 0) {
        $candidate = $docroot . $href;
        if (is_file($candidate)) $fs = filesize($candidate);
      }
    }
  }
  // 3) Try resolving under project public folder
  if ($fs === null) {
    $public = realpath(__DIR__ . '/../../../../public');
    if ($public) {
      // if webpath contains '/public/' or starts with 'public/' or 'uploads/'
      if (stripos($p, '/public/') !== false) {
        $parts = explode('/public/', $p, 2);
        $candidate = $public . '/' . $parts[1];
        if (is_file($candidate)) $fs = filesize($candidate);
      }
      if ($fs === null && stripos($p, 'public/') === 0) {
        $candidate = $public . '/' . substr($p, strlen('public/'));
        if (is_file($candidate)) $fs = filesize($candidate);
      }
      if ($fs === null && stripos($p, 'uploads/') === 0) {
        $candidate = $public . '/' . $p;
        if (is_file($candidate)) $fs = filesize($candidate);
      }
    }
  }
  if ($fs === null) return '';
  $units = ['B','KB','MB','GB'];
  $i = 0;
  while ($fs >= 1024 && $i < 3) { $fs /= 1024; $i++; }
  return round($fs, 2) . ' ' . $units[$i];
}

function file_icon_class($path) {
  $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) return 'fa-image text-primary';
  if (in_array($ext, ['mp4','mov','webm','avi','mkv'])) return 'fa-video text-success';
  if ($ext === 'pdf') return 'fa-file-pdf text-danger';
  return 'fa-file text-secondary';
}

// Helper: produce a web-accessible href from stored file_path
function web_href($path) {
  if (!$path) return '';
  if (preg_match('#^https?://#i', $path)) return $path;

  $p = '/' . ltrim(str_replace('\\', '/', $path), '/');
  $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
  $appPos = strpos($scriptName, '/app/');
  $basePath = $appPos !== false ? substr($scriptName, 0, $appPos) : '';

  if ($basePath !== '' && strpos($p, $basePath . '/') === 0) {
    return $p;
  }

  if (stripos($p, '/uploads/') === 0) {
    return $basePath . '/public' . $p;
  }

  if (stripos($p, '/public/uploads/') === 0) {
    return $basePath . $p;
  }

  return $basePath . $p;
}
?>



