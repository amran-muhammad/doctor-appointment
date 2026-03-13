<?php
/**
 * includes/session.php
 * Secure session management and authentication helpers
 */

require_once __DIR__ . '/../config/config.php';

// Configure session security settings BEFORE starting session
ini_set('session.cookie_httponly', 1);       // Prevent JS access to cookie
ini_set('session.cookie_samesite', 'Strict'); // CSRF mitigation
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ── CSRF Token Functions ──────────────────────────────────────

/**
 * Generate a CSRF token and store in session
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate submitted CSRF token against session token
 */
function validateCsrfToken(string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden CSRF input field
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF from POST and die on failure
 */
function requireCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die('CSRF validation failed. Please go back and try again.');
    }
    // Regenerate token after use for extra security
    unset($_SESSION['csrf_token']);
}

// ── Authentication Functions ──────────────────────────────────

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user's ID
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's role
 */
function currentUserRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if current user is a doctor
 */
function isDoctor(): bool {
    return currentUserRole() === 'doctor';
}

/**
 * Check if current user is a patient
 */
function isPatient(): bool {
    return currentUserRole() === 'patient';
}

/**
 * Require user to be logged in; redirect to login if not
 */
function requireLogin(string $redirectTo = '/index.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . $redirectTo);
        exit;
    }
}

/**
 * Require user to have a specific role
 */
function requireRole(string $role): void {
    requireLogin();
    if (currentUserRole() !== $role) {
        header('Location: ' . SITE_URL . '/index.php?error=unauthorized');
        exit;
    }
}

/**
 * Log in a user by setting session variables
 */
function loginUser(array $user): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
}

/**
 * Destroy session and log out user
 */
function logoutUser(): void {
    $_SESSION = [];

    // Destroy the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

// ── Flash Message Functions ───────────────────────────────────

/**
 * Set a one-time flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message
 */
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message HTML if one exists
 */
function renderFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $msg  = htmlspecialchars($flash['message']);
        echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
                {$msg}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
              </div>";
    }
}
