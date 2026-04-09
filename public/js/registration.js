// Password visibility toggle
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eye = document.getElementById(fieldId + '-eye');
    if (field.type === 'password') {
        field.type = 'text';
        eye.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        eye.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Password strength indicator
document.addEventListener('DOMContentLoaded', function () {
    const pwField = document.getElementById('password');
    const strengthEl = document.getElementById('passwordStrength');
    const confirmField = document.getElementById('password_confirmation');
    const confirmError = document.getElementById('confirmError');

    if (pwField && strengthEl) {
        pwField.addEventListener('input', function () {
            const val = pwField.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            if (val.length === 0) { strengthEl.textContent = ''; return; }
            if (score <= 2) { strengthEl.textContent = 'Weak password'; strengthEl.className = 'password-strength pw-weak'; }
            else if (score === 3 || score === 4) { strengthEl.textContent = 'Medium password'; strengthEl.className = 'password-strength pw-medium'; }
            else { strengthEl.textContent = 'Strong password'; strengthEl.className = 'password-strength pw-strong'; }
        });
    }

    if (confirmField && confirmError) {
        confirmField.addEventListener('input', function () {
            if (pwField.value !== confirmField.value) {
                confirmError.textContent = 'Passwords do not match.';
            } else {
                confirmError.textContent = '';
            }
        });
    }
});
