/**
 * assets/js/app.js
 * MedBook - Client-side JavaScript
 */

'use strict';

// ── DOM Ready ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Initialize tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // Note: Slot loading for book.php is handled inline in that page's own script block.

    // ── Confirm dialogs for destructive actions ───────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // ── Availability: Day toggle ──────────────────────────────
    document.querySelectorAll('.day-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const card = this.closest('.day-card');
            if (card) card.classList.toggle('active-day', this.checked);
            const inputs = card ? card.querySelectorAll('input:not(.day-toggle), select') : [];
            inputs.forEach(inp => inp.disabled = !this.checked);
        });
    });

    // ── Password strength indicator ───────────────────────────
    const passInput = document.getElementById('password');
    const strengthBar = document.getElementById('password-strength');

    if (passInput && strengthBar) {
        passInput.addEventListener('input', function () {
            const strength = checkPasswordStrength(this.value);
            const bar = strengthBar.querySelector('.progress-bar');
            if (bar) {
                const levels = { weak: [25, 'bg-danger'], fair: [50, 'bg-warning'], good: [75, 'bg-info'], strong: [100, 'bg-success'] };
                const [width, cls] = levels[strength] || [0, ''];
                bar.style.width = width + '%';
                bar.className = 'progress-bar ' + cls;
            }
        });

        function checkPasswordStrength(pwd) {
            let score = 0;
            if (pwd.length >= 8)         score++;
            if (/[A-Z]/.test(pwd))       score++;
            if (/[a-z]/.test(pwd))       score++;
            if (/[0-9]/.test(pwd))       score++;
            if (/[^A-Za-z0-9]/.test(pwd))score++;
            if (score <= 1) return 'weak';
            if (score === 2) return 'fair';
            if (score === 3) return 'good';
            return 'strong';
        }
    }

    // ── Appointment modal: pre-fill doctor info ───────────────
    document.querySelectorAll('[data-bs-target="#bookingModal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const modal = document.getElementById('bookingModal');
            if (!modal) return;
            ['doctor_id', 'doctor_name'].forEach(key => {
                const el = modal.querySelector(`[name="${key}"], #modal_${key}`);
                if (el && this.dataset[key]) el.value = this.dataset[key];
            });
            const nameEl = modal.querySelector('#modal_doctor_display');
            if (nameEl && this.dataset.doctorName) nameEl.textContent = 'Dr. ' + this.dataset.doctorName;
        });
    });

});

// ── Global site URL for AJAX calls ───────────────────────────
// Set via PHP: <script>const siteUrl = '<?= SITE_URL ?>';</script>
