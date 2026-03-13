<?php
/**
 * includes/helpers.php
 * Utility functions used throughout the application
 */

/**
 * Sanitize a string for safe output (XSS prevention)
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize input from user (trim + strip tags)
 */
function sanitize(string $input): string {
    return strip_tags(trim($input));
}

/**
 * Validate email address
 */
function isValidEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (basic)
 */
function isValidPhone(string $phone): bool {
    return preg_match('/^[\+]?[\d\s\-\(\)]{7,20}$/', $phone) === 1;
}

/**
 * Hash a password securely
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

/**
 * Verify a password against hash
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Validate password strength:
 * Min 8 chars, at least one uppercase, one lowercase, one digit
 */
function isStrongPassword(string $password): bool {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/**
 * Format a date for display
 */
function formatDate(string $date, string $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

/**
 * Format a time for display (e.g., "09:00" → "9:00 AM")
 */
function formatTime(string $time): string {
    return date('g:i A', strtotime($time));
}

/**
 * Generate time slots between start and end times with given duration
 * Returns array of time strings like ['09:00', '09:30', ...]
 */
function generateTimeSlots(string $startTime, string $endTime, int $durationMinutes): array {
    $slots  = [];
    $start  = strtotime($startTime);
    $end    = strtotime($endTime);
    $step   = $durationMinutes * 60; // convert to seconds

    for ($t = $start; $t < $end; $t += $step) {
        $slots[] = date('H:i', $t);
    }

    return $slots;
}

/**
 * Get status badge HTML for appointment status
 */
function statusBadge(string $status): string {
    $badges = [
        'pending'  => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
    ];
    $color = $badges[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst(e($status)) . '</span>';
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Return a URL relative to SITE_URL
 */
function url(string $path): string {
    return SITE_URL . '/' . ltrim($path, '/');
}

/**
 * Get the day of week for a given date (e.g., "Monday")
 */
function getDayOfWeek(string $date): string {
    return date('l', strtotime($date));
}

/**
 * Safely get POST value
 */
function post(string $key, string $default = ''): string {
    return sanitize($_POST[$key] ?? $default);
}

/**
 * Safely get GET value
 */
function get(string $key, string $default = ''): string {
    return sanitize($_GET[$key] ?? $default);
}

/**
 * Validate that a date string is valid and in the future
 */
function isFutureDate(string $date): bool {
    $timestamp = strtotime($date);
    return $timestamp !== false && $timestamp >= strtotime('today');
}
