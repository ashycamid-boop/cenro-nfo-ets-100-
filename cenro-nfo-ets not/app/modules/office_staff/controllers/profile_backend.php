<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../helpers/profile_image_helper.php';

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'office_staff') {
    header('Location: ' . app_url('index.php'));
    exit;
}

// --- REPLACED START: robust session / user selection logic ---
// Accept the session keys used by the app (login.php sets `uid`, `email`, `full_name`)
$sessionUserId = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$sessionUserEmail = $_SESSION['email'] ?? null;
$sessionFullName = $_SESSION['full_name'] ?? null;

// Load logged-in user's full row early (if available). If DB fetch fails, fall back to session values.
$loggedInUser = null;
if (!empty($sessionUserId)) {
  try {
    $stmt = $pdo->prepare('SELECT id, email, full_name, contact_number, office_unit, position, profile_picture, role, status, created_at, updated_at, last_login FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$sessionUserId]);
    $loggedInUser = $stmt->fetch();
  } catch (Exception $e) {
    $loggedInUser = false;
  }

  // If DB query returned nothing, use minimal info from session so topbar/profile can still show name/email
  if (!$loggedInUser || empty($loggedInUser['id'])) {
    $loggedInUser = [
      'id' => (int)$sessionUserId,
      'email' => $sessionUserEmail ?? '',
      'full_name' => $sessionFullName ?? 'User',
      'contact_number' => '',
      'office_unit' => '',
      'position' => '',
      'profile_picture' => $_SESSION['profile_picture'] ?? '',
      'role' => $_SESSION['role'] ?? '',
      'status' => $_SESSION['status'] ?? 0,
    ];
  }
}

// Determine which user to show: ?id= or logged-in user
$viewUserId = null;
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
  $viewUserId = (int) $_GET['id'];
} elseif (!empty($loggedInUser['id'])) {
  $viewUserId = (int) $loggedInUser['id'];
}

$user = null;
$notFound = false;
if ($viewUserId) {
  try {
    // include updated_at and last_login so profile displays all DB fields
    $stmt = $pdo->prepare('SELECT id, email, full_name, contact_number, office_unit, position, profile_picture, role, status, created_at, updated_at, last_login FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$viewUserId]);
    $user = $stmt->fetch();
  } catch (Exception $e) {
    $user = false;
  }
}

// If requested user not found but we have a logged-in user, show logged-in user's profile instead
if (!$user && !empty($loggedInUser['id'])) {
  $user = $loggedInUser;
  $notFound = false;
  $viewUserId = (int)$loggedInUser['id'];
}

// If still no user, show placeholder
if (!$user) {
  $notFound = true;
  $user = [
    'id' => '',
    'email' => '',
    'full_name' => 'User not found',
    'contact_number' => '',
    'office_unit' => '',
    'position' => '',
    'profile_picture' => '',
    'role' => '',
    'status' => 0,
    'created_at' => null,
  ];
}

// Determine ownership
$isOwnProfile = false;
if (!empty($loggedInUser['id']) && $viewUserId && ((int)$loggedInUser['id'] === (int)$viewUserId)) {
  $isOwnProfile = true;
}
// --- REPLACED END ---

$profileMessage = '';
$profileError = '';

// Handle profile update when the owner submits the form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $isOwnProfile) {
  try {
    // Preserve existing profile_picture unless a new one uploaded
    $profile_picture = null;
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = :id");
    $stmt->execute([':id' => $loggedInUser['id']]);
    $existing = $stmt->fetch();
    $profile_picture = $existing['profile_picture'] ?? null;

    // Handle profile picture upload (optional) - prefer cropped image
    if (!empty($_POST['cropped_profile_picture'])) {
      $profile_picture = saveProfileImageFromDataUrl($_POST['cropped_profile_picture'], (int) $loggedInUser['id']);
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
      $uploadedPath = saveUploadedProfileImageFile($_FILES['profile_picture'], (int) $loggedInUser['id']);
      if ($uploadedPath !== null) {
        $profile_picture = $uploadedPath;
      }
    }
    // If this submission is picture-only, update only the profile_picture column
    if (!empty($_POST['profile_only']) && $_POST['profile_only'] == '1') {
      $stmt = $pdo->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :id");
      $stmt->execute([':profile_picture' => $profile_picture, ':id' => $loggedInUser['id']]);
      $profileMessage = 'Profile picture updated successfully.';
    } else {
      // Build full name
      $firstName = trim($_POST['firstName'] ?? '');
      $middleName = trim($_POST['middleName'] ?? '');
      $lastName = trim($_POST['lastName'] ?? '');
      $suffix = trim($_POST['suffix'] ?? '');
      $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName . ' ' . $suffix);
      $fullName = preg_replace('/\s+/', ' ', $fullName);

      // Password update (optional)
      $updatePassword = false;
      if (!empty($_POST['password'])) {
        if ($_POST['password'] !== ($_POST['confirmPassword'] ?? '')) {
          throw new Exception('Passwords do not match.');
        }
        $updatePassword = true;
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
      }

      if ($updatePassword) {
        $stmt = $pdo->prepare("\n        UPDATE users SET full_name = :full_name, email = :email, contact_number = :contact_number,\n        office_unit = :office_unit, profile_picture = :profile_picture, password = :password WHERE id = :id\n      ");
        $stmt->execute([
          ':full_name' => $fullName,
          ':email' => $_POST['email'],
          ':contact_number' => $_POST['contactNumber'] ?? '',
          ':office_unit' => $_POST['officeUnit'] ?? '',
          ':profile_picture' => $profile_picture,
          ':password' => $hashedPassword,
          ':id' => $loggedInUser['id']
        ]);
      } else {
        $stmt = $pdo->prepare("\n        UPDATE users SET full_name = :full_name, email = :email, contact_number = :contact_number,\n        office_unit = :office_unit, profile_picture = :profile_picture WHERE id = :id\n      ");
        $stmt->execute([
          ':full_name' => $fullName,
          ':email' => $_POST['email'],
          ':contact_number' => $_POST['contactNumber'] ?? '',
          ':office_unit' => $_POST['officeUnit'] ?? '',
          ':profile_picture' => $profile_picture,
          ':id' => $loggedInUser['id']
        ]);
      }

      $profileMessage = 'Profile updated successfully.';
    }
    // refresh $user and $loggedInUser display data
    $stmt = $pdo->prepare('SELECT id, email, full_name, contact_number, office_unit, position, profile_picture, role, status, created_at, updated_at, last_login FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$loggedInUser['id']]);
    $user = $stmt->fetch();
    // refresh loggedInUser minimal info
    $stmt = $pdo->prepare('SELECT id, full_name, profile_picture, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$loggedInUser['id']]);
    $loggedInUser = $stmt->fetch();
  } catch (Exception $e) {
    $profileError = 'Failed to update profile: ' . $e->getMessage();
  }
}

