<?php
/**
 * patient/profile.php
 * Patient profile management and password change
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('patient');

$pdo       = getDB();
$patientId = currentUserId();
$errors    = [];

// Load current profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

// ── Handle Updates ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $action = post('action');

    if ($action === 'profile') {
        $fullName = post('full_name');
        $phone    = post('phone');

        if (strlen($fullName) < 2) $errors[] = 'Full name is required.';

        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")->execute([
                $fullName, $phone ?: null, $patientId
            ]);
            $_SESSION['user_name'] = $fullName;
            setFlash('success', 'Profile updated successfully.');
            redirect(url('patient/profile.php'));
        }

    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!verifyPassword($current, $patient['password']))
            $errors[] = 'Current password is incorrect.';
        if (!isStrongPassword($new))
            $errors[] = 'New password must be at least 8 characters with uppercase, lowercase, and a number.';
        if ($new !== $confirm)
            $errors[] = 'New passwords do not match.';

        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([
                hashPassword($new), $patientId
            ]);
            setFlash('success', 'Password changed successfully.');
            redirect(url('patient/profile.php'));
        }
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="section-title mb-0">My Profile</h2>
        <p class="section-subtitle">Manage your personal information and security settings</p>
    </div>
    <a href="<?= url('patient/dashboard.php') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Dashboard
    </a>
</div>

<?php renderFlash(); ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Summary -->
    <div class="col-lg-4">
        <div class="card text-center p-4">
            <div class="avatar-lg mx-auto mb-3"><?= strtoupper(substr($patient['full_name'], 0, 1)) ?></div>
            <h5 class="fw-700"><?= e($patient['full_name']) ?></h5>
            <p class="text-muted small"><?= e($patient['email']) ?></p>
            <span class="badge bg-primary-subtle text-primary">Patient</span>
        </div>
    </div>

    <!-- Edit Forms -->
    <div class="col-lg-8">
        <!-- Personal Info -->
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-person me-2 text-primary"></i>Personal Information</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($patient['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($patient['phone'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-600">Email</label>
                            <input type="email" class="form-control bg-light" value="<?= e($patient['email']) ?>" disabled>
                            <div class="form-text">Email address cannot be changed.</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-lock me-2 text-warning"></i>Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-600">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600">New Password</label>
                            <input type="password" name="new_password" id="password" class="form-control" placeholder="Min 8 chars" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning px-4">
                                <i class="bi bi-shield-lock me-2"></i>Change Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
