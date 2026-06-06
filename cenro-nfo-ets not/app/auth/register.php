<?php
// Server-side user registration handler
// Receives POST from add_user form and inserts into users table

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/profile_image_helper.php';
require_once __DIR__ . '/../helpers/contact_number_helper.php';
require_once __DIR__ . '/../helpers/email_helper.php';

function ensureUserNamePartsTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_name_parts (
            user_id INT PRIMARY KEY,
            first_name VARCHAR(255) NOT NULL DEFAULT '',
            middle_name VARCHAR(255) NOT NULL DEFAULT '',
            last_name VARCHAR(255) NOT NULL DEFAULT '',
            suffix VARCHAR(50) NOT NULL DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_name_parts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/admin/views/add_user.php');
    exit;
}

// Collect and sanitize
$firstName = trim($_POST['firstName'] ?? '');
$middleName = trim($_POST['middleName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$suffix = trim($_POST['suffix'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$contact = trim($_POST['contactNumber'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirmPassword'] ?? '';
$officeUnit = trim($_POST['officeUnit'] ?? '');
$role = trim($_POST['role'] ?? '');
$position = trim($_POST['position'] ?? '');

if (empty($_POST['cropped_profile_picture']) && (!isset($_FILES['profile_picture']) || ($_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
    header('Location: ../modules/admin/views/add_user.php?err=' . urlencode('Profile picture is required.'));
    exit;
}

// Handle profile picture upload - prefer cropped image if present
$profile_picture_path = null;
try {
    if (!empty($_POST['cropped_profile_picture'])) {
        $profile_picture_path = saveProfileImageFromDataUrl($_POST['cropped_profile_picture']);
    } elseif (isset($_FILES['profile_picture'])) {
        $profile_picture_path = saveUploadedProfileImageFile($_FILES['profile_picture']);
    }
} catch (Exception $e) {
    header('Location: ../modules/admin/views/add_user.php?err=' . urlencode($e->getMessage()));
    exit;
}
//duplicate email and contact number checks
// Basic validation
if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $password !== $confirm || $role === '') {
    header('Location: ../modules/admin/views/add_user.php?err=Invalid+input');
    exit;
}

if (userEmailExists($pdo, $email)) {
    header('Location: ../modules/admin/views/add_user.php?err=Email+already+exists');
    exit;
}

if (userContactNumberExists($pdo, $contact)) {
    header('Location: ../modules/admin/views/add_user.php?err=Contact+number+already+exists');
    exit;
}

// Compose full name
$fullName = $firstName;
if ($middleName !== '') $fullName .= ' ' . $middleName;
$fullName .= ' ' . $lastName;
if ($suffix !== '') $fullName .= ', ' . $suffix;

// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user with all fields (including position)
$insert = $pdo->prepare('INSERT INTO users (email, password_hash, full_name, contact_number, office_unit, profile_picture, role, position, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
try {
    ensureUserNamePartsTable($pdo);
    $insert->execute([$email, $hash, $fullName, $contact, $officeUnit, $profile_picture_path, $role, $position]);
    $userId = (int) $pdo->lastInsertId();
    if ($userId > 0) {
        $namePartsStmt = $pdo->prepare("
            INSERT INTO user_name_parts (user_id, first_name, middle_name, last_name, suffix)
            VALUES (:user_id, :first_name, :middle_name, :last_name, :suffix)
            ON DUPLICATE KEY UPDATE
                first_name = VALUES(first_name),
                middle_name = VALUES(middle_name),
                last_name = VALUES(last_name),
                suffix = VALUES(suffix)
        ");
        $namePartsStmt->execute([
            ':user_id' => $userId,
            ':first_name' => $firstName,
            ':middle_name' => $middleName,
            ':last_name' => $lastName,
            ':suffix' => $suffix,
        ]);
    }
} catch (Exception $e) {
    header('Location: ../modules/admin/views/add_user.php?err=Unable+to+create+user');
    exit;
}

// Optionally insert contact info into audit_logs or user profile tables; omitted for brevity

header('Location: ../modules/admin/views/user_management.php?success=1');
exit;
