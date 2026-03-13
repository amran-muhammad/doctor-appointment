<?php
/**
 * doctor/profile.php
 * Doctor profile management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('doctor');

$pdo      = getDB();
$doctorId = currentUserId();
$errors   = [];

// Load current profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

// ── Handle Update ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $fullName   = post('full_name');
    $phone      = post('phone');
    $specialty  = post('specialty');
    $experience = (int) post('experience_years');
    $fee        = (float) post('consultation_fee');
    $bio        = post('bio');

    if (strlen($fullName) < 2) $errors[] = 'Full name is required.';

    if (empty($errors)) {
        $pdo->prepare("
            UPDATE users 
            SET full_name=:name, phone=:phone, specialty=:specialty, 
                experience_years=:exp, consultation_fee=:fee, bio=:bio
            WHERE id=:id
        ")->execute([
            ':name'      => $fullName,
            ':phone'     => $phone ?: null,
            ':specialty' => $specialty,
            ':exp'       => $experience > 0 ? $experience : null,
            ':fee'       => $fee > 0 ? $fee : null,
            ':bio'       => $bio,
            ':id'        => $doctorId,
        ]);

        // Update session name
        $_SESSION['user_name'] = $fullName;
        setFlash('success', 'Profile updated successfully.');
        redirect(url('doctor/profile.php'));
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="section-title mb-0">My Profile</h2>
        <p class="section-subtitle">Update your professional information</p>
    </div>
    <a href="<?= url('doctor/dashboard.php') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Dashboard
    </a>
</div>

<?php renderFlash(); ?>

<div class="row g-4">
    <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="card text-center p-4">
            <div class="avatar-lg mx-auto mb-3"><?= strtoupper(substr($doctor['full_name'], 0, 1)) ?></div>
            <h5 class="fw-700">Dr. <?= e($doctor['full_name']) ?></h5>
            <p class="text-primary"><?= e($doctor['specialty'] ?? 'General Physician') ?></p>
            <hr>
            <div class="text-start small text-muted">
                <p class="mb-2"><i class="bi bi-envelope me-2"></i><?= e($doctor['email']) ?></p>
                <?php if ($doctor['phone']): ?>
                <p class="mb-2"><i class="bi bi-phone me-2"></i><?= e($doctor['phone']) ?></p>
                <?php endif; ?>
                <?php if ($doctor['experience_years']): ?>
                <p class="mb-2"><i class="bi bi-award me-2"></i><?= $doctor['experience_years'] ?> years experience</p>
                <?php endif; ?>
                <?php if ($doctor['consultation_fee']): ?>
                <p class="mb-0"><i class="bi bi-cash me-2"></i>$<?= number_format($doctor['consultation_fee'], 2) ?> per visit</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-pencil me-2 text-primary"></i>Edit Profile</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($doctor['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($doctor['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600">Specialty</label>
                            <select name="specialty" class="form-select">
                                <?php
                                $specialties = ['Cardiology','Dermatology','Endocrinology','Gastroenterology','General Physician','Gynecology','Neurology','Oncology','Ophthalmology','Orthopedics','Pediatrics','Psychiatry','Pulmonology','Radiology','Urology'];
                                foreach ($specialties as $s):
                                ?>
                                    <option value="<?= e($s) ?>" <?= $doctor['specialty'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-600">Experience (yrs)</label>
                            <input type="number" name="experience_years" class="form-control" min="0" max="60" value="<?= e($doctor['experience_years'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-600">Fee ($)</label>
                            <input type="number" name="consultation_fee" class="form-control" min="0" step="0.01" value="<?= e($doctor['consultation_fee'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-600">Bio</label>
                            <textarea name="bio" class="form-control" rows="4"><?= e($doctor['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
