<?php
session_start();

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$projectRoot = dirname(dirname(dirname(dirname(__DIR__))));
$appRoot = $projectRoot . '/app';

require_once $appRoot . '/config/db.php';
require_once $projectRoot . '/config/mail.php';
require_once $projectRoot . '/vendor/autoload.php';

const OTP_EXPIRY_SECONDS = 600;

$routeConfig = getPasswordFlowRoutes();
$changePasswordView = $routeConfig['change_password_view'];
$verifyOtpView = $routeConfig['verify_otp_view'];
$baseUrl = getProjectBaseUrl();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $changePasswordView);
    exit;
}

$userId = $_SESSION['uid'] ?? null;
$current = trim($_POST['current_password'] ?? '');
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$userId) {
    $_SESSION['cp_message'] = 'Your session expired. Please log in again.';
    header('Location: ' . $baseUrl . '/index.php');
    exit;
}

if ($new !== $confirm) {
    $_SESSION['cp_message'] = 'New password and confirm password do not match.';
    header('Location: ' . $changePasswordView);
    exit;
}

if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
    $_SESSION['cp_message'] = 'Password must be at least 8 characters long and contain a mix of letters and numbers.';
    header('Location: ' . $changePasswordView);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT email, password_hash, full_name FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current, $user['password_hash'])) {
        $_SESSION['cp_message'] = 'Current password incorrect.';
        header('Location: ' . $changePasswordView);
        exit;
    }

    if (!filter_var($user['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $_SESSION['cp_message'] = 'No valid email address is registered on your account.';
        header('Location: ' . $changePasswordView);
        exit;
    }

    $otp = generateOtpCode();
    $pendingPasswordChange = [
        'user_id' => (int) $userId,
        'email' => $user['email'],
        'name' => trim((string) ($user['full_name'] ?? 'Admin User')),
        'new_password_hash' => password_hash($new, PASSWORD_DEFAULT),
        'otp_hash' => hash('sha256', $otp),
        'otp_expires_at' => time() + OTP_EXPIRY_SECONDS,
        'otp_last_sent_at' => time(),
    ];

    sendPasswordChangeOtp($pendingPasswordChange['email'], $pendingPasswordChange['name'], $otp);

    $_SESSION['pending_password_change'] = $pendingPasswordChange;
    $_SESSION['cp_message'] = 'A verification code has been sent to your Gmail address.';

    header('Location: ' . $verifyOtpView);
    exit;
} catch (Exception $e) {
    error_log('Password change OTP email failed: ' . $e->getMessage());
    $_SESSION['cp_message'] = getMailErrorMessage($e);
    header('Location: ' . $changePasswordView);
    exit;
} catch (Throwable $e) {
    error_log('Password change request failed: ' . $e->getMessage());
    $_SESSION['cp_message'] = 'Unable to process your password change request right now.';
    header('Location: ' . $changePasswordView);
    exit;
}

function getPasswordFlowRoutes(): array
{
    $userRole = $_SESSION['user_role'] ?? '';
    $dbRole = $_SESSION['role'] ?? '';
    $baseUrl = getProjectBaseUrl();

    $changePasswordViews = [
        'admin' => $baseUrl . '/app/modules/admin/views/change_password.php',
        'enforcement_officer' => $baseUrl . '/app/modules/enforcement_officer/views/change_password.php',
        'enforcer' => $baseUrl . '/app/modules/enforcer/views/change_password.php',
        'office_staff' => $baseUrl . '/app/modules/office_staff/views/change_password.php',
        'property_custodian' => $baseUrl . '/app/modules/property_custodian/views/change_password.php',
    ];
    $verifyOtpViews = [
        'admin' => $baseUrl . '/app/modules/admin/views/verify_otp.php',
        'enforcement_officer' => $baseUrl . '/app/modules/enforcement_officer/views/verify_otp.php',
        'enforcer' => $baseUrl . '/app/modules/enforcer/views/verify_otp.php',
        'office_staff' => $baseUrl . '/app/modules/office_staff/views/verify_otp.php',
        'property_custodian' => $baseUrl . '/app/modules/property_custodian/views/verify_otp.php',
    ];

    if ($dbRole === 'Admin') {
        $changePasswordView = $changePasswordViews['admin'];
        $verifyOtpView = $verifyOtpViews['admin'];
    } else {
        $changePasswordView = $changePasswordViews[$userRole] ?? $changePasswordViews['admin'];
        $verifyOtpView = $verifyOtpViews[$userRole] ?? $verifyOtpViews['admin'];
    }

    return [
        'change_password_view' => $changePasswordView,
        'verify_otp_view' => $verifyOtpView,
    ];
}

function getProjectBaseUrl(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseUrl = preg_replace('#/app/modules/admin/controllers$#', '', $scriptDir);

    if ($baseUrl === null || $baseUrl === '/' || $baseUrl === '.') {
        return '';
    }

    return rtrim($baseUrl, '/');
}

function generateOtpCode(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendPasswordChangeOtp(string $recipientEmail, string $recipientName, string $otp): void
{
    $mailConfig = getMailConfig();

    $username = trim((string) ($mailConfig['username'] ?? ''));
    $password = preg_replace('/\s+/', '', trim((string) ($mailConfig['password'] ?? '')));

    if (
        $username === '' ||
        $password === '' ||
        $username === 'your-gmail@gmail.com' ||
        $password === 'your-16-digit-google-app-password'
    ) {
        throw new Exception('Mail username/password missing.');
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $mailConfig['host'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = $mailConfig['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) ($mailConfig['port'] ?? 587);
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = (int) ($mailConfig['timeout'] ?? 10);

    $fromEmail = $mailConfig['from_email'] ?? $mailConfig['username'];
    $fromName = $mailConfig['from_name'] ?? 'System Security';

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($recipientEmail, $recipientName);
    $mail->isHTML(true);
    $mail->Subject = 'Password Change Verification Code';
    $mail->Body = buildOtpHtmlBody($recipientName, $otp);
    $mail->AltBody = "Your password change verification code is {$otp}. This code will expire in 10 minutes.";
    $mail->send();
}

function getMailErrorMessage(Exception $e): string
{
    $message = $e->getMessage();

    if (stripos($message, 'Mail username/password missing') !== false) {
        return 'Gmail username or app password is missing in the mail configuration.';
    }

    if (
        stripos($message, 'Could not authenticate') !== false ||
        stripos($message, 'Username and Password not accepted') !== false
    ) {
        return 'Gmail authentication failed. Please check the Gmail address and Google App Password.';
    }

    if (
        stripos($message, 'SMTP connect() failed') !== false ||
        stripos($message, 'Could not connect to SMTP host') !== false
    ) {
        return 'Could not connect to Gmail SMTP. Please check your internet connection and SMTP settings.';
    }

    return 'Failed to send verification code. Please check the Gmail mail configuration.';
}

function buildOtpHtmlBody(string $recipientName, string $otp): string
{
    $safeName = htmlspecialchars($recipientName ?: 'User', ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<div style="font-family:Arial,sans-serif;color:#0f172a;line-height:1.5">
    <p>Hello {$safeName},</p>
    <p>We received a request to change your account password.</p>
    <p>Your verification code is:</p>
    <p style="font-size:28px;font-weight:700;letter-spacing:6px;margin:16px 0">{$safeOtp}</p>
    <p>This code will expire in 10 minutes.</p>
    <p>If you did not request this change, you can ignore this email.</p>
</div>
HTML;
}
