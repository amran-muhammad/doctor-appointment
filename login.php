<?php
/**
 * login.php
 * User authentication - handles both doctors and patients
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';

// Already logged in?
if (isLoggedIn()) redirect(url(currentUserRole() . '/dashboard.php'));

$errors = [];

// ── Handle Login ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $email    = post('email');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Please enter your email and password.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password'])) {
            loginUser($user);
            setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');

            // Redirect to appropriate dashboard
            if ($user['role'] === 'doctor') {
                redirect(url('doctor/dashboard.php'));
            } else {
                redirect(url('patient/dashboard.php'));
            }
        } else {
            // Generic error to prevent user enumeration
            $errors[] = 'Invalid email or password. Please try again.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card card">
        <div class="auth-header">
            <i class="bi bi-person-circle fs-1 mb-2 d-block"></i>
            <h2>Welcome Back</h2>
            <p>Sign in to your MedBook account</p>
        </div>

        <div class="card-body p-4">
            <?php renderFlash(); ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $err): ?>
                        <div><i class="bi bi-exclamation-circle me-2"></i><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label fw-600">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" 
                               value="<?= e(post('email')) ?>" placeholder="you@example.com" 
                               required autofocus autocomplete="email">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-600">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Your password" required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg fw-600 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="alert alert-info small mt-3">
                <strong><i class="bi bi-info-circle me-1"></i>Demo Credentials:</strong><br>
                <strong>Doctor:</strong> doctor@demo.com / password<br>
                <strong>Patient:</strong> patient@demo.com / password
            </div>

            <p class="text-center mt-3 mb-0 text-muted">
                Don't have an account? <a href="<?= url('register.php') ?>" class="text-primary fw-600">Register here</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
