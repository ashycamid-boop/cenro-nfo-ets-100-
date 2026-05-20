<?php require_once __DIR__ . '/../controllers/change_password_backend.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <?php require_once __DIR__ . '/../../../views/partials/favicon.php'; ?>
    <link rel="stylesheet" href="../../../../public/assets/css/modules/admin/change_password.css?v=20260517-compact">
</head>
<body>

<div class="cp-wrapper">
    <div class="cp-card">
        <div class="cp-body">
            <div style="display:flex;align-items:center;justify-content:flex-start;margin-bottom:8px">
                <button type="button" class="cp-back" onclick="goBack()">← Back</button>
            </div>
            <h4 class="cp-title">Change Password</h4>
            <div class="cp-sub">Enter your current password and choose a new secure password.</div>
            <?php if (!empty($_SESSION['cp_message'])): ?>
                <div class="cp-alert"><?php echo htmlspecialchars($_SESSION['cp_message']); unset($_SESSION['cp_message']); ?></div>
            <?php endif; ?>

            <form id="changePasswordForm" method="post" action="../controllers/change_password.php">
                <div class="cp-form-group">
                    <label for="current_password" id="lbl_current">Current password</label>
                    <div class="cp-input-wrapper">
                            <input type="password" id="current_password" name="current_password" class="cp-input" required>
                        <button type="button" class="cp-toggle" data-target="current_password" aria-label="Show current password"> 
                            <!-- eye-off (initial: hidden) -->
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6 0-10-7-10-7 .95-1.62 2.47-3.62 4.4-5.16" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 1l22 22" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>

                <div class="cp-form-group">
                    <label for="new_password" id="lbl_new">New password</label>
                    <div class="cp-input-wrapper">
                            <input type="password" id="new_password" name="new_password" class="cp-input" required minlength="8">
                        <button type="button" class="cp-toggle" data-target="new_password" aria-label="Show new password">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6 0-10-7-10-7 .95-1.62 2.47-3.62 4.4-5.16" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 1l22 22" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>

                <div class="cp-form-group">
                    <label for="confirm_password" id="lbl_confirm">Confirm password</label>
                    <div class="cp-input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" class="cp-input" required>
                        <button type="button" class="cp-toggle" data-target="confirm_password" aria-label="Show confirm password">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6 0-10-7-10-7 .95-1.62 2.47-3.62 4.4-5.16" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 1l22 22" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>

                <div id="cp_error" class="cp-error"></div>
                <button type="submit" class="cp-btn">Change password</button>
            </form>
        </div>
    </div>
</div>

<script src="../../../../public/assets/js/admin/change_password.js"></script>
</body>
</html>
