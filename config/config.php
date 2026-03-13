<?php
/**
 * config/config.php
 * Global application configuration
 */

// ── Site Settings ────────────────────────────────────────────
define('SITE_NAME', 'MedBook');
define('SITE_URL', 'http://localhost/doctor-appointment'); // No trailing slash
define('SITE_EMAIL', 'noreply@medbook.com');

// ── Session Settings ─────────────────────────────────────────
define('SESSION_NAME', 'medbook_session');
define('SESSION_LIFETIME', 7200); // 2 hours in seconds

// ── Security ─────────────────────────────────────────────────
define('BCRYPT_COST', 12); // Cost factor for password_hash()

// ── SMTP Email Settings ──────────────────────────────────────
// Configure these with your SMTP provider credentials
define('SMTP_HOST', 'smtp.gmail.com');          // e.g., smtp.gmail.com, smtp.sendgrid.net
define('SMTP_PORT', 587);                        // 587 for TLS, 465 for SSL
define('SMTP_SECURE', 'tls');                    // 'tls' or 'ssl'
define('SMTP_AUTH', true);
define('SMTP_USER', 'amran.appifylab@gmail.com');     // Your SMTP username/email
// define('SMTP_PASS', 'qyhfgxiyffpudxgx');         // Your SMTP password or app password
define('SMTP_FROM_EMAIL', 'noreply@medbook.com');
define('SMTP_FROM_NAME', 'MedBook Appointments');

// ── File Uploads ─────────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/../assets/images/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/images/uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('America/New_York'); // Change to your timezone
