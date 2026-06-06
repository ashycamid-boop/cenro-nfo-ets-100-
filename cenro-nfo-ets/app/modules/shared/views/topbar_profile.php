<?php
// Shared topbar profile include. Safe, minimal and resilient.
// Only start session if none exists and headers have not been sent yet.
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
  session_start();
}
require_once __DIR__ . '/../../../../app/config/db.php';

$topImg = '../../../../public/assets/images/default-avatar.png';
$topName = 'Guest';
$topRole = 'User';
$sessionUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$sessionUserEmail = $_SESSION['email'] ?? null;
$topFullName = '';
$signatureNotifications = [];
$signatureNotificationCount = 0;
try {
  $r = null;
  if (!empty($sessionUserId)) {
    $stmt = $pdo->prepare('
      SELECT u.id, u.full_name, u.profile_picture, u.role, p.first_name, p.last_name
      FROM users u
      LEFT JOIN user_name_parts p ON p.user_id = u.id
      WHERE u.id = ?
      LIMIT 1
    ');
    $stmt->execute([$sessionUserId]);
    $r = $stmt->fetch();
  } elseif (!empty($sessionUserEmail)) {
    $stmt = $pdo->prepare('
      SELECT u.id, u.full_name, u.profile_picture, u.role, p.first_name, p.last_name
      FROM users u
      LEFT JOIN user_name_parts p ON p.user_id = u.id
      WHERE u.email = ?
      LIMIT 1
    ');
    $stmt->execute([$sessionUserEmail]);
    $r = $stmt->fetch();
  }
  if (!empty($r)) {
    $topFullName = trim((string)($r['full_name'] ?? ''));
    // Prefer exact first_name + last_name from user_name_parts when available.
    $exactFirst = trim((string)($r['first_name'] ?? ''));
    $exactLast = trim((string)($r['last_name'] ?? ''));
    if ($exactFirst !== '' && $exactLast !== '') {
      $topName = $exactFirst . ' ' . $exactLast;
    } elseif (!empty($r['full_name'])) {
      $parts = preg_split('/\s+/', trim((string)$r['full_name'])) ?: [];
      if (count($parts) === 1) {
        $topName = $parts[0];
      } elseif (count($parts) >= 2) {
        $topName = $parts[0] . ' ' . $parts[count($parts) - 1];
      }
    }
    $topRole = !empty($r['role']) ? $r['role'] : $topRole;
    if (strcasecmp((string)$topRole, 'Admin') === 0) {
      $topRole = 'Admin';
    }
    if (!empty($r['profile_picture'])) {
      $stored = ltrim($r['profile_picture'], '/');
      $fsPath = __DIR__ . '/../../../../' . $stored;
      if (file_exists($fsPath)) {
        $topImg = '../../../../' . $stored;
      }
    }
  }
} catch (Exception $e) {
  // fallback to defaults silently
}

if (!function_exists('topbar_signature_request_url')) {
  function topbar_signature_request_url($status, $requestId)
  {
    $role = $_SESSION['user_role'] ?? '';
    $role = strtolower(str_replace(' ', '_', trim((string)$role)));
    $module = $role === 'admin' ? 'admin' : ($role === 'property_custodian' ? 'property_custodian' : 'admin');
    $statusLower = strtolower(trim((string)$status));
    $page = in_array($statusLower, ['ongoing', 'scheduled'], true) ? 'edit_requests_ongoing.php' : 'edit_requests.php';
    return app_url('app/modules/' . $module . '/views/' . $page . '?id=' . urlencode((string)$requestId));
  }
}

try {
  if (!empty($sessionUserId) && !empty($topFullName) && isset($pdo)) {
    $authStmt = $pdo->prepare("
      SELECT id, ticket_no, status, requester_name, request_type, 'Authorization' AS needed_for
      FROM service_requests
      WHERE auth1_name IS NOT NULL
        AND TRIM(auth1_name) <> ''
        AND LOWER(TRIM(auth1_name)) = LOWER(TRIM(?))
        AND (auth1_signature_path IS NULL OR TRIM(auth1_signature_path) = '')
        AND LOWER(COALESCE(status, '')) NOT IN ('completed', 'done', 'rejected')
      UNION ALL
      SELECT id, ticket_no, status, requester_name, request_type, 'Infrastructure Authorization' AS needed_for
      FROM service_requests
      WHERE auth2_name IS NOT NULL
        AND TRIM(auth2_name) <> ''
        AND LOWER(TRIM(auth2_name)) = LOWER(TRIM(?))
        AND (auth2_signature_path IS NULL OR TRIM(auth2_signature_path) = '')
        AND LOWER(COALESCE(status, '')) NOT IN ('completed', 'done', 'rejected')
      ORDER BY id DESC
      LIMIT 10
    ");
    $authStmt->execute([$topFullName, $topFullName]);
    foreach ($authStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $signatureNotifications[] = [
        'id' => $row['id'] ?? '',
        'ticket_no' => $row['ticket_no'] ?? '',
        'status' => $row['status'] ?? '',
        'requester_name' => $row['requester_name'] ?? '',
        'request_type' => $row['request_type'] ?? '',
        'needed_for' => $row['needed_for'] ?? 'Signature',
        'url' => topbar_signature_request_url($row['status'] ?? '', $row['id'] ?? ''),
      ];
    }

    $actionStmt = $pdo->prepare("
      SELECT sr.id, sr.ticket_no, sr.status, sr.requester_name, sr.request_type, 'Action Staff Signature' AS needed_for
      FROM service_request_actions sra
      INNER JOIN service_requests sr ON sr.id = sra.service_request_id
      WHERE sra.action_staff_id = ?
        AND (sra.action_signature_path IS NULL OR TRIM(sra.action_signature_path) = '')
        AND LOWER(COALESCE(sr.status, '')) NOT IN ('completed', 'done', 'rejected')
      ORDER BY COALESCE(sra.created_at, sr.updated_at, sr.created_at) DESC
      LIMIT 10
    ");
    $actionStmt->execute([$sessionUserId]);
    foreach ($actionStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $signatureNotifications[] = [
        'id' => $row['id'] ?? '',
        'ticket_no' => $row['ticket_no'] ?? '',
        'status' => $row['status'] ?? '',
        'requester_name' => $row['requester_name'] ?? '',
        'request_type' => $row['request_type'] ?? '',
        'needed_for' => $row['needed_for'] ?? 'Action Staff Signature',
        'url' => topbar_signature_request_url($row['status'] ?? '', $row['id'] ?? ''),
      ];
    }

    $seenSignatureNotifications = [];
    $signatureNotifications = array_values(array_filter($signatureNotifications, function ($item) use (&$seenSignatureNotifications) {
      $key = ($item['id'] ?? '') . '|' . ($item['needed_for'] ?? '');
      if (isset($seenSignatureNotifications[$key])) return false;
      $seenSignatureNotifications[$key] = true;
      return true;
    }));
    $signatureNotificationCount = count($signatureNotifications);
    $signatureNotifications = array_slice($signatureNotifications, 0, 8);
  }
} catch (Exception $e) {
  error_log('topbar signature notification error: ' . $e->getMessage());
  $signatureNotifications = [];
  $signatureNotificationCount = 0;
}

// If still showing Guest and a service request id is present in the URL,
// try to load the requester's name from the `service_requests` table so
// the topbar can reflect the request author when viewing a request.
try {
  if ((empty($r) || ($topName === 'Guest')) && !empty($_GET['id']) && isset($pdo)) {
    $reqId = $_GET['id'];
    if (ctype_digit((string)$reqId)) {
      $stmt = $pdo->prepare('SELECT requester_name FROM service_requests WHERE id = ? LIMIT 1');
    } else {
      $stmt = $pdo->prepare('SELECT requester_name FROM service_requests WHERE ticket_no = ? LIMIT 1');
    }
    $stmt->execute([$reqId]);
    $sr = $stmt->fetch();
    if (!empty($sr['requester_name'])) {
      $parts = preg_split('/\s+/', trim($sr['requester_name']));
      if (count($parts) === 1) {
        $topName = $parts[0];
      } else {
        $topName = $parts[0] . ' ' . $parts[count($parts) - 1];
      }
      $topRole = 'Requester';
    }
  }
} catch (Exception $e) {
  // silently ignore fallback errors
}
?>
<style>
  .topbar-notification-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    margin-right: 12px;
  }
  .topbar-notification-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border: 0;
    border-radius: 50%;
    background: #f4f7fb;
    color: #1f2d3d;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  .topbar-notification-btn:hover,
  .topbar-notification-wrap:focus-within .topbar-notification-btn {
    background: #eaf1ff;
  }
  .topbar-notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: #ffc107;
    color: #1f2d3d;
    font-size: 11px;
    font-weight: 700;
    line-height: 18px;
    text-align: center;
  }
  .topbar-notification-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: min(360px, calc(100vw - 32px));
    max-height: 420px;
    overflow: auto;
    display: none;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    z-index: 10000;
    border: 1px solid rgba(0,0,0,0.08);
  }
  .topbar-notification-wrap:focus-within .topbar-notification-dropdown,
  .topbar-notification-wrap:hover .topbar-notification-dropdown {
    display: block;
  }
  .topbar-notification-title {
    padding: 12px 14px;
    font-weight: 700;
    border-bottom: 1px solid #eef1f5;
    color: #1f2d3d;
  }
  .topbar-notification-empty {
    padding: 16px 14px;
    color: #6c757d;
    font-size: 13px;
  }
  .topbar-notification-item {
    display: block;
    padding: 12px 14px;
    color: #1f2d3d;
    text-decoration: none;
    border-bottom: 1px solid #eef1f5;
  }
  .topbar-notification-item:hover {
    background: #f7faff;
    color: #0d6efd;
  }
  .topbar-notification-item strong {
    display: block;
    font-size: 13px;
    margin-bottom: 3px;
  }
  .topbar-notification-item span {
    display: block;
    color: #6c757d;
    font-size: 12px;
  }
</style>
<div class="topbar-notification-wrap">
  <button type="button" class="topbar-notification-btn" aria-label="Signature notifications">
    <i class="fa fa-bell"></i>
    <?php if ($signatureNotificationCount > 0): ?>
      <span class="topbar-notification-badge"><?php echo htmlspecialchars((string)$signatureNotificationCount); ?></span>
    <?php endif; ?>
  </button>
  <div class="topbar-notification-dropdown" role="menu" aria-label="Signature notifications">
    <div class="topbar-notification-title">Notifications</div>
    <?php if (empty($signatureNotifications)): ?>
      <div class="topbar-notification-empty">No pending signatures.</div>
    <?php else: ?>
      <?php foreach ($signatureNotifications as $notif): ?>
        <?php
          $ticketLabel = $notif['ticket_no'] ?: ('Request #' . $notif['id']);
          $meta = trim(($notif['requester_name'] ?? '') . ' ' . ($notif['status'] ? '(' . $notif['status'] . ')' : ''));
        ?>
        <a class="topbar-notification-item" href="<?php echo htmlspecialchars($notif['url'], ENT_QUOTES, 'UTF-8'); ?>" role="menuitem">
          <strong><?php echo htmlspecialchars($notif['needed_for'] . ' needed - ' . $ticketLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
          <span><?php echo htmlspecialchars($meta !== '' ? $meta : 'Please review and sign this request.', ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<div class="topbar-profile-card" id="profileCard" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
  <img src="<?php echo htmlspecialchars($topImg); ?>" alt="Profile" class="topbar-profile-img" id="profileImg">
  <div class="topbar-profile-info">
    <span class="name"><?php echo htmlspecialchars($topName); ?></span>
    <span class="role"><?php echo htmlspecialchars($topRole); ?></span>
  </div>
  <div class="profile-dropdown" id="profileDropdown">
    <a href="profile.php"><i class="fa fa-user"></i> Profile</a>
    <a href="change_password.php"><i class="fa fa-lock"></i> Change Password</a>
    <a href="../../../../index.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

