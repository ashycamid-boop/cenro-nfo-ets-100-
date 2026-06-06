function goBack() {
    var fallback = 'dashboard.php';
    try {
        var referrer = document.referrer || '';
        var current = window.location.href;
        var isPasswordFlow = /\/controllers\/(change_password|verify_otp)\.php|\/views\/(change_password|verify_otp)\.php/.test(referrer);
        if (referrer && referrer !== current && !isPasswordFlow) {
            window.location = referrer;
            return;
        }
    } catch (e) {}
    window.location = fallback;
}

// Toggle visibility for password fields
document.querySelectorAll('.cp-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var targetId = this.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (!input) return;
        var isPwd = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPwd ? 'text' : 'password');
        // swap icon: simple eye / eye-off
        this.innerHTML = isPwd
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6 0-10-7-10-7 .95-1.62 2.47-3.62 4.4-5.16" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 1l22 22" stroke="#111827" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    });
});

// Real-time validation for new/confirm password
var newInput = document.getElementById('new_password');
var confInput = document.getElementById('confirm_password');
var errBox = document.getElementById('cp_error');
var lblNew = document.getElementById('lbl_new');
var lblConf = document.getElementById('lbl_confirm');

function validateMatch() {
    var a = newInput.value || '';
    var b = confInput.value || '';
    errBox.style.display = 'none';
    lblNew.classList.remove('label-error');
    lblConf.classList.remove('label-error');
    newInput.classList.remove('input-error');
    confInput.classList.remove('input-error');
    if (b.length === 0) return;
    if (a !== b) {
        errBox.textContent = 'New password and confirm password do not match.';
        errBox.style.display = 'block';
        lblConf.classList.add('label-error');
        confInput.classList.add('input-error');
        return false;
    }
    if (a.length > 0 && a.length < 8) {
        errBox.textContent = 'Password must be at least 8 characters long and contain a mix of letters and numbers.';
        errBox.style.display = 'block';
        lblNew.classList.add('label-error');
        newInput.classList.add('input-error');
        return false;
    }

    // Composition: require at least one letter and one number
    var hasLetter = /[A-Za-z]/.test(a);
    var hasNumber = /[0-9]/.test(a);
    if (a.length > 0 && !(hasLetter && hasNumber)) {
        errBox.textContent = 'Password must be at least 8 characters long and contain a mix of letters and numbers.';
        errBox.style.display = 'block';
        lblNew.classList.add('label-error');
        newInput.classList.add('input-error');
        return false;
    }
    return true;
}


newInput.addEventListener('input', validateMatch);
confInput.addEventListener('input', validateMatch);

document.getElementById('changePasswordForm').addEventListener('submit', function (e) {
    if (!validateMatch()) e.preventDefault();
});
