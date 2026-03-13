<?php
/**
 * includes/mailer.php
 * Email notification service using PHPMailer
 * 
 * NOTE: Install PHPMailer via Composer:
 *   composer require phpmailer/phpmailer
 * Or download manually from https://github.com/PHPMailer/PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Autoload PHPMailer (adjust path if not using Composer)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Manual include fallback
    require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
    require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
}

/**
 * Send an HTML email using PHPMailer + SMTP
 *
 * @param string $toEmail  Recipient email
 * @param string $toName   Recipient name
 * @param string $subject  Email subject
 * @param string $htmlBody HTML email body
 * @param string $textBody Plain-text fallback
 * @return bool True on success, false on failure
 */
function sendEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $textBody = ''
): bool {
    $mail = new PHPMailer(true); // true = enable exceptions

    try {
        // ── Server Settings ────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Optional: disable SSL verification in development only
        // $mail->SMTPOptions = ['ssl' => ['verify_peer' => false]];

        // ── Recipients ─────────────────────────────────────────
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        // ── Content ────────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody); // Plain text fallback

        $mail->send();
        error_log("Email sent successfully to: {$toEmail}");
        return true;

    } catch (Exception $e) {
        error_log("Email failed to {$toEmail}: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send appointment approval notification email to patient
 *
 * @param array $appointment  Appointment row with joined doctor/patient data
 * @return bool
 */
function sendAppointmentApprovalEmail(array $appointment): bool {
    $subject = '✅ Appointment Confirmed - ' . SITE_NAME;

    $doctorName   = htmlspecialchars($appointment['doctor_name']);
    $patientName  = htmlspecialchars($appointment['patient_name']);
    $specialty    = htmlspecialchars($appointment['specialty'] ?? 'General Physician');
    $apptDate     = formatDate($appointment['appointment_date'], 'l, F j, Y');
    $apptTime     = formatTime($appointment['appointment_time']);
    $siteName     = SITE_NAME;

    $htmlBody = <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Appointment Confirmed</title>
    </head>
    <body style="margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;background:#f4f6fb;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:40px 0;">
            <tr><td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#1a73e8,#0d47a1);padding:36px 40px;text-align:center;">
                            <h1 style="color:#fff;margin:0;font-size:28px;font-weight:700;">🏥 {$siteName}</h1>
                            <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;">Appointment Confirmation</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            <!-- Success Icon -->
                            <div style="text-align:center;margin-bottom:28px;">
                                <div style="background:#e8f5e9;border-radius:50%;width:80px;height:80px;display:inline-flex;align-items:center;justify-content:center;margin:0 auto;">
                                    <span style="font-size:42px;">✅</span>
                                </div>
                                <h2 style="color:#2e7d32;margin:16px 0 4px;font-size:22px;">Appointment Confirmed!</h2>
                                <p style="color:#666;margin:0;font-size:15px;">Your appointment has been approved by the doctor.</p>
                            </div>

                            <!-- Greeting -->
                            <p style="color:#333;font-size:16px;margin:0 0 24px;">Dear <strong>{$patientName}</strong>,</p>
                            <p style="color:#555;font-size:15px;line-height:1.6;margin:0 0 28px;">
                                Great news! Your appointment request has been <strong style="color:#2e7d32;">approved</strong>. 
                                Please find your appointment details below.
                            </p>

                            <!-- Appointment Card -->
                            <div style="background:#f8f9ff;border:2px solid #e3e8ff;border-radius:10px;padding:24px;margin-bottom:28px;">
                                <h3 style="color:#1a73e8;margin:0 0 16px;font-size:16px;text-transform:uppercase;letter-spacing:0.5px;">📋 Appointment Details</h3>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding:8px 0;color:#888;font-size:14px;width:40%;">👨‍⚕️ Doctor</td>
                                        <td style="padding:8px 0;color:#333;font-size:15px;font-weight:600;">Dr. {$doctorName}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;color:#888;font-size:14px;">🩺 Specialty</td>
                                        <td style="padding:8px 0;color:#333;font-size:15px;">{$specialty}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;color:#888;font-size:14px;">📅 Date</td>
                                        <td style="padding:8px 0;color:#333;font-size:15px;font-weight:600;">{$apptDate}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;color:#888;font-size:14px;">⏰ Time</td>
                                        <td style="padding:8px 0;color:#333;font-size:15px;font-weight:600;">{$apptTime}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 0;color:#888;font-size:14px;">📌 Status</td>
                                        <td style="padding:8px 0;">
                                            <span style="background:#e8f5e9;color:#2e7d32;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;">CONFIRMED</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Reminder -->
                            <div style="background:#fff8e1;border-left:4px solid #ffc107;padding:16px;border-radius:4px;margin-bottom:28px;">
                                <p style="color:#f57c00;margin:0;font-size:14px;font-weight:600;">⚠️ Reminder</p>
                                <p style="color:#795548;margin:6px 0 0;font-size:14px;line-height:1.5;">
                                    Please arrive 10-15 minutes early. Bring any relevant medical records or documents.
                                </p>
                            </div>

                            <!-- CTA Button -->
                            <div style="text-align:center;margin-bottom:28px;">
                                <a href="{SITE_URL}/patient/appointments.php" 
                                   style="background:#1a73e8;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-size:15px;font-weight:600;display:inline-block;">
                                    View My Appointments
                                </a>
                            </div>

                            <p style="color:#555;font-size:15px;margin:0;">
                                If you have any questions, please contact us at 
                                <a href="mailto:{SITE_EMAIL}" style="color:#1a73e8;">{SITE_EMAIL}</a>.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8f9fa;padding:24px 40px;text-align:center;border-top:1px solid #e9ecef;">
                            <p style="color:#999;font-size:13px;margin:0;">
                                This is an automated notification from {$siteName}.<br>
                                Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td></tr>
        </table>
    </body>
    </html>
    HTML;

    // Replace placeholders
    $htmlBody = str_replace('{SITE_URL}', SITE_URL, $htmlBody);
    $htmlBody = str_replace('{SITE_EMAIL}', SITE_EMAIL, $htmlBody);

    $textBody = "Dear {$patientName},\n\n"
        . "Your appointment has been APPROVED!\n\n"
        . "Doctor: Dr. {$doctorName} ({$specialty})\n"
        . "Date: {$apptDate}\n"
        . "Time: {$apptTime}\n\n"
        . "Please arrive 10-15 minutes early.\n\n"
        . "- " . SITE_NAME;

    return sendEmail(
        $appointment['patient_email'],
        $appointment['patient_name'],
        $subject,
        $htmlBody,
        $textBody
    );
}

/**
 * Send appointment rejection notification to patient
 */
function sendAppointmentRejectionEmail(array $appointment): bool {
    $subject = '❌ Appointment Update - ' . SITE_NAME;

    $doctorName  = htmlspecialchars($appointment['doctor_name']);
    $patientName = htmlspecialchars($appointment['patient_name']);
    $apptDate    = formatDate($appointment['appointment_date'], 'l, F j, Y');
    $apptTime    = formatTime($appointment['appointment_time']);
    $siteName    = SITE_NAME;
    $notes       = htmlspecialchars($appointment['doctor_notes'] ?? 'No reason provided.');

    $htmlBody = <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>Appointment Update</title></head>
    <body style="margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;background:#f4f6fb;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:40px 0;">
            <tr><td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#1a73e8,#0d47a1);padding:36px 40px;text-align:center;">
                            <h1 style="color:#fff;margin:0;font-size:28px;">🏥 {$siteName}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px;">
                            <p style="color:#333;font-size:16px;">Dear <strong>{$patientName}</strong>,</p>
                            <p style="color:#555;font-size:15px;line-height:1.6;">
                                We regret to inform you that your appointment request with <strong>Dr. {$doctorName}</strong> 
                                on <strong>{$apptDate}</strong> at <strong>{$apptTime}</strong> has been <strong style="color:#c62828;">declined</strong>.
                            </p>
                            <div style="background:#fce4ec;border-left:4px solid #e53935;padding:16px;border-radius:4px;margin:20px 0;">
                                <p style="color:#b71c1c;margin:0;font-size:14px;font-weight:600;">Doctor's Note:</p>
                                <p style="color:#555;margin:6px 0 0;font-size:14px;">{$notes}</p>
                            </div>
                            <p style="color:#555;font-size:15px;">You can book another available slot at your convenience.</p>
                            <div style="text-align:center;margin:28px 0;">
                                <a href="{SITE_URL}/patient/doctors.php" 
                                   style="background:#1a73e8;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-size:15px;font-weight:600;display:inline-block;">
                                    Book Another Appointment
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8f9fa;padding:20px 40px;text-align:center;border-top:1px solid #e9ecef;">
                            <p style="color:#999;font-size:13px;margin:0;">Automated notification from {$siteName}.</p>
                        </td>
                    </tr>
                </table>
            </td></tr>
        </table>
    </body>
    </html>
    HTML;

    $htmlBody = str_replace('{SITE_URL}', SITE_URL, $htmlBody);

    return sendEmail(
        $appointment['patient_email'],
        $appointment['patient_name'],
        $subject,
        $htmlBody
    );
}
