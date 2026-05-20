<?php
require_once __DIR__ . '/../controllers/change_password_backend.php';

$pendingPasswordChange = $_SESSION['pending_password_change'] ?? null;
if (!$pendingPasswordChange) {
    header('Location: change_password.php');
    exit;
}

$maskedEmail = maskEmailAddress((string) ($pendingPasswordChange['email'] ?? ''));

function maskEmailAddress(string $email): string
{
    if (!str_contains($email, '@')) {
        return $email;
    }

    [$local, $domain] = explode('@', $email, 2);
    $visibleLocal = substr($local, 0, min(2, strlen($local)));
    $maskedLocal = $visibleLocal . str_repeat('*', max(1, strlen($local) - strlen($visibleLocal)));

    return $maskedLocal . '@' . $domain;
}
?>
<link rel="stylesheet" href="../../../../public/assets/css/modules/admin/change_password.css">

<div class="cp-wrapper">
    <div class="cp-card">
        <div class="cp-body">
            <div style="display:flex;align-items:center;justify-content:flex-start;margin-bottom:8px">
                <button type="button" class="cp-back" onclick="window.location.href='change_password.php'">Back</button>
            </div>
            <h4 class="cp-title">Email Verification</h4>
            <div class="cp-sub">Enter the 6-digit code sent to <?php echo htmlspecialchars($maskedEmail); ?> before we update your password.</div>

            <?php if (!empty($_SESSION['cp_message'])): ?>
                <div class="cp-alert"><?php echo htmlspecialchars($_SESSION['cp_message']); unset($_SESSION['cp_message']); ?></div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['otp_message'])): ?>
                <div class="cp-alert cp-alert-error"><?php echo htmlspecialchars($_SESSION['otp_message']); unset($_SESSION['otp_message']); ?></div>
            <?php endif; ?>

            <form method="post" action="../controllers/verify_otp.php" class="cp-otp-form">
                <input type="hidden" name="action" value="verify">
                <div class="cp-form-group">
                    <label for="otp_code">Verification code</label>
                    <input
                        type="text"
                        id="otp_code"
                        name="otp_code"
                        class="cp-input cp-otp-input"
                        inputmode="numeric"
                        pattern="\d{6}"
                        maxlength="6"
                        placeholder="Enter 6-digit code"
                        required
                    >
                </div>
                <button type="submit" class="cp-btn">Verify and update password</button>
            </form>

            <form method="post" action="../controllers/verify_otp.php" class="cp-secondary-form">
                <input type="hidden" name="action" value="resend">
                <button type="submit" class="cp-btn cp-btn-secondary">Resend code</button>
            </form>

            <form method="post" action="../controllers/verify_otp.php" class="cp-secondary-form">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="cp-btn cp-btn-cancel">Cancel request</button>
            </form>
        </div>
    </div>
</div>

<script src="../../../../public/assets/js/admin/verify_otp.js"></script>
