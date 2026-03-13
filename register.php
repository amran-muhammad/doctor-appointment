<?php
/**
 * register.php
 * User registration for both doctors and patients
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';

// Already logged in? Redirect
if (isLoggedIn()) redirect(url(currentUserRole() . '/dashboard.php'));

$errors  = [];
$success = '';
$role    = in_array(get('role'), ['doctor', 'patient']) ? get('role') : 'patient';

// ── Handle Form Submission ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $role       = in_array(post('role'), ['doctor', 'patient']) ? post('role') : 'patient';
    $fullName   = post('full_name');
    $email      = post('email');
    $phone      = post('phone');
    $password   = $_POST['password'] ?? ''; // raw for hashing
    $confirmPwd = $_POST['confirm_password'] ?? '';

    // Doctor-specific
    $specialty   = post('specialty');
    $experience  = (int) post('experience_years');
    $fee         = (float) post('consultation_fee');
    $bio         = post('bio');

    // ── Validation ────────────────────────────────────────────
    if (strlen($fullName) < 2)
        $errors[] = 'Full name must be at least 2 characters.';

    if (!isValidEmail($email))
        $errors[] = 'Please enter a valid email address.';

    if ($phone && !isValidPhone($phone))
        $errors[] = 'Please enter a valid phone number.';

    if (!isStrongPassword($password))
        $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, and a number.';

    if ($password !== $confirmPwd)
        $errors[] = 'Passwords do not match.';

    if ($role === 'doctor' && empty($specialty))
        $errors[] = 'Please enter your medical specialty.';

    // Check if email already exists
    if (empty($errors)) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email address is already registered.';
        }
    }

    // ── Insert User ───────────────────────────────────────────
    if (empty($errors)) {
        $hashedPwd = hashPassword($password);

        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, email, password, role, phone, specialty, bio, experience_years, consultation_fee)
            VALUES (:full_name, :email, :password, :role, :phone, :specialty, :bio, :experience_years, :consultation_fee)
        ");

        $stmt->execute([
            ':full_name'         => $fullName,
            ':email'             => $email,
            ':password'          => $hashedPwd,
            ':role'              => $role,
            ':phone'             => $phone ?: null,
            ':specialty'         => $role === 'doctor' ? $specialty : null,
            ':bio'               => $role === 'doctor' ? $bio : null,
            ':experience_years'  => $role === 'doctor' && $experience > 0 ? $experience : null,
            ':consultation_fee'  => $role === 'doctor' && $fee > 0 ? $fee : null,
        ]);

        setFlash('success', 'Registration successful! Please log in.');
        redirect(url('login.php'));
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card card">
        <!-- Header -->
        <div class="auth-header">
            <i class="bi bi-person-plus-fill fs-1 mb-2 d-block"></i>
            <h2>Create Account</h2>
            <p>Join MedBook — free, quick, and easy</p>
        </div>

        <div class="card-body p-4">
            <!-- Role Tabs -->
            <ul class="nav nav-pills nav-fill mb-4" role="tablist">
                <li class="nav-item">
                    <button class="nav-link <?= $role === 'patient' ? 'active' : '' ?>"
                            onclick="document.getElementById('role').value='patient';document.querySelectorAll('.doctor-only').forEach(e=>e.classList.add('d-none'));this.classList.add('active');document.querySelectorAll('.nav-link').forEach(b=>b!==this&&b.classList.remove('active'))">
                        <i class="bi bi-person me-1"></i>Register as Patient
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $role === 'doctor' ? 'active' : '' ?>"
                            onclick="document.getElementById('role').value='doctor';document.querySelectorAll('.doctor-only').forEach(e=>e.classList.remove('d-none'));this.classList.add('active');document.querySelectorAll('.nav-link').forEach(b=>b!==this&&b.classList.remove('active'))">
                        <i class="bi bi-hospital me-1"></i>Register as Doctor
                    </button>
                </li>
            </ul>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="role" id="role" value="<?= e($role) ?>">

                <!-- Common Fields -->
                <div class="mb-3">
                    <label class="form-label fw-600">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?= e(post('full_name')) ?>" placeholder="Your full name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= e(post('email')) ?>" placeholder="you@example.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-600">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?= e(post('phone')) ?>" placeholder="+1-555-0100">
                </div>

                <!-- Doctor-Only Fields -->
                <div class="doctor-only <?= $role !== 'doctor' ? 'd-none' : '' ?>">
                    <div class="mb-3">
                        <label class="form-label fw-600">Medical Specialty <span class="text-danger">*</span></label>
                        <select name="specialty" class="form-select">
                            <option value="">Select specialty...</option>
                            <?php
                            $specialties = ['Cardiology','Dermatology','Endocrinology','Gastroenterology','General Physician','Gynecology','Neurology','Oncology','Ophthalmology','Orthopedics','Pediatrics','Psychiatry','Pulmonology','Radiology','Urology'];
                            foreach ($specialties as $s):
                            ?>
                                <option value="<?= e($s) ?>" <?= post('specialty') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-600">Experience (years)</label>
                            <input type="number" name="experience_years" class="form-control" min="0" max="60"
                                   value="<?= e(post('experience_years')) ?>" placeholder="e.g. 10">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-600">Consultation Fee ($)</label>
                            <input type="number" name="consultation_fee" class="form-control" min="0" step="0.01"
                                   value="<?= e(post('consultation_fee')) ?>" placeholder="e.g. 100">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600">Professional Bio</label>
                        <textarea name="bio" class="form-control" rows="3"
                                  placeholder="Brief description of your background and expertise..."><?= e(post('bio')) ?></textarea>
                    </div>
                </div>

                <!-- Password Fields -->
                <div class="mb-3">
                    <label class="form-label fw-600">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="Min 8 chars, upper, lower, number" required>
                    <div class="progress mt-2" style="height:4px;">
                        <div id="password-strength" class="progress" style="height:4px;">
                            <div class="progress-bar" style="width:0%;transition:all 0.3s;"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-600">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" 
                           placeholder="Repeat your password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg fw-600">
                    <i class="bi bi-person-check me-2"></i>Create Account
                </button>

                <p class="text-center mt-3 mb-0 text-muted">
                    Already have an account? <a href="<?= url('login.php') ?>" class="text-primary fw-600">Login</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
// Init password strength bar reference for app.js
const progressContainer = document.getElementById('password-strength');
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
