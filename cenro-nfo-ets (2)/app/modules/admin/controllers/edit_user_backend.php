<?php
require_once dirname(__DIR__, 3) . '/config/app.php';
session_start();
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../helpers/profile_image_helper.php';
require_once __DIR__ . '/../../../helpers/contact_number_helper.php';
require_once __DIR__ . '/../../../helpers/email_helper.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Fetch user data if ID is provided
$user = null;
$message = '';
$error = '';
$firstName = '';
$middleName = '';
$lastName = '';
$suffix = '';
$position = '';

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

function parseFullNameComponents(string $fullName): array
{
    $first = '';
    $middle = '';
    $last = '';
    $suffix = '';

    $normalized = trim(preg_replace('/\s+/', ' ', $fullName));
    if ($normalized === '') {
        return [$first, $middle, $last, $suffix];
    }

    $parts = preg_split('/\s+/', $normalized) ?: [];
    if (empty($parts)) {
        return [$first, $middle, $last, $suffix];
    }

    $knownSuffixes = ['JR', 'SR', 'II', 'III', 'IV', 'V'];
    $lastToken = $parts[count($parts) - 1];
    $suffixCandidate = strtoupper(rtrim($lastToken, '.,'));

    if (in_array($suffixCandidate, $knownSuffixes, true)) {
        $suffix = rtrim($lastToken, ',');
        array_pop($parts);
    }

    $count = count($parts);
    if ($count === 1) {
        $first = $parts[0];
    } elseif ($count === 2) {
        $first = $parts[0];
        $last = $parts[1];
    } else {
        // Deterministic split for this form:
        // last token = last name, token before last = middle name, rest = first name.
        $last = $parts[$count - 1];
        $middle = $parts[$count - 2];
        $first = implode(' ', array_slice($parts, 0, $count - 2));
    }

    return [$first, $middle, $last, $suffix];
}

if (isset($_GET['id'])) {
    try {
        ensureUserNamePartsTable($pdo);
        $stmt = $pdo->prepare("
            SELECT
                u.id, u.email, u.full_name, u.role, u.status, u.contact_number, u.office_unit, u.profile_picture, u.position,
                p.first_name, p.middle_name, p.last_name, p.suffix AS name_suffix
            FROM users u
            LEFT JOIN user_name_parts p ON p.user_id = u.id
            WHERE u.id = :id
        ");
        $stmt->execute([':id' => $_GET['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (
                array_key_exists('first_name', $user) &&
                (
                    trim((string)($user['first_name'] ?? '')) !== '' ||
                    trim((string)($user['middle_name'] ?? '')) !== '' ||
                    trim((string)($user['last_name'] ?? '')) !== '' ||
                    trim((string)($user['name_suffix'] ?? '')) !== ''
                )
            ) {
                $firstName = (string)($user['first_name'] ?? '');
                $middleName = (string)($user['middle_name'] ?? '');
                $lastName = (string)($user['last_name'] ?? '');
                $suffix = (string)($user['name_suffix'] ?? '');
            } else {
                [$firstName, $middleName, $lastName, $suffix] = parseFullNameComponents((string) ($user['full_name'] ?? ''));
            }

            // Always load position from DB so it's visible/editable
            $position = $user['position'] ?? '';
        }
    } catch (Exception $e) {
        $error = "Failed to fetch user details";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    try {
        ensureUserNamePartsTable($pdo);
        // Fetch existing profile_picture using posted user_id to preserve it if no new file uploaded
        $profile_picture = null;
        if (!empty($_POST['user_id'])) {
            $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = :id");
            $stmt->execute([':id' => $_POST['user_id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            $profile_picture = $existing['profile_picture'] ?? null;
        }

        // Handle profile picture upload (prefer cropped image)
        if (!empty($_POST['cropped_profile_picture'])) {
            $profile_picture = saveProfileImageFromDataUrl($_POST['cropped_profile_picture'], (int) $_POST['user_id']);
        } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            $profile_picture = saveUploadedProfileImageFile($_FILES['profile_picture'], (int) $_POST['user_id']);
        }

        // Build full name from first, middle, last names
        $firstName = trim($_POST['firstName'] ?? '');
        $middleName = trim($_POST['middleName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $suffix = trim($_POST['suffix'] ?? '');
        $position = $_POST['position'] ?? null;
        $userId = (int) ($_POST['user_id'] ?? 0);
        $email = trim((string) ($_POST['email'] ?? ''));
        $contactNumber = trim((string) ($_POST['contactNumber'] ?? ''));

        $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName . ' ' . $suffix);
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName)); // Remove extra spaces

        if (userEmailExists($pdo, $email, $userId)) {
            throw new Exception('Email already exists.');
        }

        if (userContactNumberExists($pdo, $contactNumber, $userId)) {
            throw new Exception('Contact number already exists.');
        }

        // Handle password update (optional)
        $updatePassword = false;
        if (!empty($_POST['password']) && $_POST['password'] === $_POST['confirmPassword']) {
            $updatePassword = true;
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        } elseif (!empty($_POST['password']) && $_POST['password'] !== $_POST['confirmPassword']) {
            throw new Exception('Passwords do not match.');
        }

        if ($updatePassword) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = :full_name, email = :email, role = :role,
                    contact_number = :contact_number, office_unit = :office_unit,
                    profile_picture = :profile_picture, password_hash = :password_hash, position = :position
                WHERE id = :id
            ");

            $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':role' => $_POST['role'],
                ':contact_number' => $contactNumber,
                ':office_unit' => $_POST['officeUnit'],
                ':profile_picture' => $profile_picture,
                ':password_hash' => $hashedPassword,
                ':position' => $position,
                ':id' => $userId
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = :full_name, email = :email, role = :role,
                    contact_number = :contact_number, office_unit = :office_unit,
                    profile_picture = :profile_picture, position = :position
                WHERE id = :id
            ");

            $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':role' => $_POST['role'],
                ':contact_number' => $contactNumber,
                ':office_unit' => $_POST['officeUnit'],
                ':profile_picture' => $profile_picture,
                ':position' => $position,
                ':id' => $userId
            ]);
        }

        // Persist exact name parts so multi-word first names do not get re-parsed incorrectly.
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
            ':user_id' => $_POST['user_id'],
            ':first_name' => $firstName,
            ':middle_name' => $middleName,
            ':last_name' => $lastName,
            ':suffix' => $suffix,
        ]);

        $message = "User updated successfully!";
        // Refresh user data
        $stmt = $pdo->prepare("
            SELECT
                u.id, u.email, u.full_name, u.role, u.status, u.contact_number, u.office_unit, u.profile_picture, u.position,
                p.first_name, p.middle_name, p.last_name, p.suffix AS name_suffix
            FROM users u
            LEFT JOIN user_name_parts p ON p.user_id = u.id
            WHERE u.id = :id
        ");
        $stmt->execute([':id' => $_POST['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $firstName = (string)($user['first_name'] ?? '');
            $middleName = (string)($user['middle_name'] ?? '');
            $lastName = (string)($user['last_name'] ?? '');
            $suffix = (string)($user['name_suffix'] ?? '');
            if ($firstName === '' && $middleName === '' && $lastName === '' && $suffix === '') {
                [$firstName, $middleName, $lastName, $suffix] = parseFullNameComponents((string) ($user['full_name'] ?? ''));
            }
            $position = $user['position'] ?? '';
        }
    } catch (Exception $e) {
        $error = "Failed to update user: " . $e->getMessage();
    }
}
