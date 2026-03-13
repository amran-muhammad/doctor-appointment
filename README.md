# 🏥 MedBook — Doctor Appointment Booking System

A complete, production-ready appointment booking platform built with **Core PHP**, **MySQL**, **Bootstrap 5**, and **PHPMailer**.

---

## 📁 Folder Structure

```
doctor-appointment/
├── config/
│   ├── config.php          # Site & SMTP settings
│   └── database.php        # PDO database connection
├── includes/
│   ├── session.php         # Auth, CSRF, flash messages
│   ├── helpers.php         # Utility functions
│   ├── mailer.php          # PHPMailer email service
│   ├── header.php          # HTML header + navbar
│   └── footer.php          # HTML footer + scripts
├── doctor/
│   ├── dashboard.php       # Doctor dashboard
│   ├── availability.php    # Manage weekly schedule
│   ├── appointments.php    # View + approve/reject
│   └── profile.php         # Edit doctor profile
├── patient/
│   ├── dashboard.php       # Patient dashboard
│   ├── doctors.php         # Browse doctors
│   ├── book.php            # Book an appointment
│   ├── get_slots.php       # AJAX: get available slots
│   ├── appointments.php    # View appointment statuses
│   └── profile.php         # Edit patient profile
├── assets/
│   ├── css/style.css       # Main stylesheet
│   ├── js/app.js           # Client-side JavaScript
│   └── images/uploads/     # (auto-created)
├── vendor/
│   └── phpmailer/          # PHPMailer library (see setup)
├── index.php               # Landing page
├── login.php               # Login
├── register.php            # Registration
├── logout.php              # Logout
├── database.sql            # Database schema + seed data
└── README.md               # This file
```

---

## ⚡ Quick Setup (5 Steps)

### 1. Create the Database

```bash
mysql -u root -p < database.sql
```

Or open **phpMyAdmin** → Import → select `database.sql`.

### 2. Configure Database Connection

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'doctor_appointment');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');
```

### 3. Configure Site URL

Edit `config/config.php`:

```php
define('SITE_URL', 'http://localhost/doctor-appointment');
define('SITE_NAME', 'MedBook'); // Your brand name
```

### 4. Install PHPMailer

**Option A — Composer (Recommended):**

```bash
cd doctor-appointment
composer require phpmailer/phpmailer
```

**Option B — Manual Download:**
1. Download from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract and copy these 3 files to `vendor/phpmailer/`:
   - `src/PHPMailer.php` → `vendor/phpmailer/PHPMailer.php`
   - `src/SMTP.php`      → `vendor/phpmailer/SMTP.php`
   - `src/Exception.php` → `vendor/phpmailer/Exception.php`

### 5. Configure SMTP Email

Edit `config/config.php`:

```php
define('SMTP_HOST', 'smtp.gmail.com');   // Your SMTP server
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'your@gmail.com');   // Your email
define('SMTP_PASS', 'your_app_password'); // See note below
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'MedBook Appointments');
```

---

## 📧 SMTP Configuration Guide

### Using Gmail (Recommended for Development)

1. Enable 2-Factor Authentication on your Google account
2. Go to: **Google Account → Security → App Passwords**
3. Generate an app password for "Mail" + "Other device"
4. Use that 16-character password as `SMTP_PASS`

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'youremail@gmail.com');
define('SMTP_PASS', 'abcd efgh ijkl mnop');  // App password (no spaces)
```

### Using SendGrid

```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'apikey');
define('SMTP_PASS', 'SG.your_sendgrid_api_key');
```

### Using Mailgun

```php
define('SMTP_HOST', 'smtp.mailgun.org');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'postmaster@mg.yourdomain.com');
define('SMTP_PASS', 'your_mailgun_smtp_password');
```

### Using Amazon SES

```php
define('SMTP_HOST', 'email-smtp.us-east-1.amazonaws.com'); // Your region
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'your_ses_smtp_username');
define('SMTP_PASS', 'your_ses_smtp_password');
```

---

## 🧪 Demo Credentials

| Role    | Email              | Password |
|---------|--------------------|----------|
| Doctor  | doctor@demo.com    | password |
| Patient | patient@demo.com   | password |

---

## 🔒 Security Features

| Feature | Implementation |
|---------|---------------|
| Password hashing | `password_hash()` with bcrypt (cost 12) |
| SQL Injection | PDO prepared statements throughout |
| CSRF Protection | Token per session, validated on all POST |
| Session Fixation | `session_regenerate_id(true)` on login |
| XSS Prevention | `htmlspecialchars()` on all output |
| Cookie Security | httponly, samesite=Strict flags |
| Double Booking | MySQL UNIQUE constraint + app-level check |

---

## 🌟 Features Overview

### For Doctors
- ✅ Register & login with secure password hashing
- ✅ Manage weekly availability (days, hours, slot duration)
- ✅ View all appointment requests with patient details
- ✅ Approve or reject appointments with notes
- ✅ Auto-send email to patient on status change
- ✅ Dashboard with appointment stats

### For Patients
- ✅ Register & login
- ✅ Browse doctors by specialty
- ✅ Book appointments from doctor's available slots
- ✅ Real-time slot loading (AJAX, no page refresh)
- ✅ View appointment status (Pending/Approved/Rejected)
- ✅ Receive email notifications on approval/rejection
- ✅ Cannot book already-taken slots (double-booking protected)

---

## 🚀 PHP & Server Requirements

- PHP 8.0 or higher
- MySQL 5.7 / MariaDB 10.3 or higher
- PDO extension (`pdo_mysql`)
- `mod_rewrite` (optional, for cleaner URLs)
- Writable `assets/images/uploads/` directory

---

## 📞 Troubleshooting

**Email not sending?**
1. Check `error_log()` output — email errors are logged
2. Verify SMTP credentials in `config/config.php`
3. For Gmail: ensure App Password (not regular password) is used
4. Some servers block outgoing SMTP — use a transactional service

**Session issues?**
- Ensure PHP sessions are writable: `sys_get_temp_dir()`
- Check for output before `session_start()` calls

**Database connection failed?**
- Verify credentials in `config/database.php`
- Ensure database `doctor_appointment` exists
- Check MySQL is running

---

## 📄 License

MIT License — Free to use and modify for personal and commercial projects.
