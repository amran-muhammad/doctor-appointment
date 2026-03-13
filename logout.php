<?php
/**
 * logout.php
 * Destroys user session and redirects to login
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';

logoutUser();
setFlash('success', 'You have been logged out successfully.');
redirect(url('login.php'));
