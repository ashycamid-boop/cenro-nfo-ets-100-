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

