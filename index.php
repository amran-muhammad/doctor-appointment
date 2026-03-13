<?php
/**
 * index.php
 * Landing page - redirects logged-in users to their dashboards
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect logged-in users to their dashboard
if (isLoggedIn()) {
    if (isDoctor()) redirect(url('doctor/dashboard.php'));
    else            redirect(url('patient/dashboard.php'));
}

// Fetch top doctors for display on landing page
$pdo     = getDB();
$stmt    = $pdo->prepare("SELECT id, full_name, specialty, experience_years, consultation_fee, bio FROM users WHERE role='doctor' AND is_active=1 ORDER BY created_at DESC LIMIT 6");
$stmt->execute();
$doctors = $stmt->fetchAll();

$pageTitle = 'Find & Book Doctor Appointments';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-center mb-0">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="fw-800 mb-4">Your Health,<br>Our Priority</h1>
                <p class="lead mb-5">Book appointments with top doctors in minutes. Secure, convenient, and hassle-free healthcare scheduling.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="<?= url('register.php') ?>" class="btn btn-light btn-lg px-5 fw-600">
                        <i class="bi bi-person-plus me-2"></i>Get Started
                    </a>
                    <a href="<?= url('login.php') ?>" class="btn btn-outline-light btn-lg px-5">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Row -->
<div class="container mt-5">
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 text-center p-4">
                <div class="mb-3"><i class="bi bi-search-heart text-primary" style="font-size:3rem;"></i></div>
                <h5 class="fw-700">Find Doctors</h5>
                <p class="text-muted mb-0">Browse specialists by specialty and check their availability instantly.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center p-4">
                <div class="mb-3"><i class="bi bi-calendar2-check text-success" style="font-size:3rem;"></i></div>
                <h5 class="fw-700">Book Instantly</h5>
                <p class="text-muted mb-0">Select your preferred time slot and book in just a few clicks.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 text-center p-4">
                <div class="mb-3"><i class="bi bi-envelope-check text-warning" style="font-size:3rem;"></i></div>
                <h5 class="fw-700">Get Confirmed</h5>
                <p class="text-muted mb-0">Receive instant email confirmation once the doctor approves your booking.</p>
            </div>
        </div>
    </div>

    <!-- Our Doctors Section -->
    <?php if (!empty($doctors)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title">Our Doctors</h2>
                <p class="section-subtitle">Expert care from certified specialists</p>
            </div>
            <a href="<?= url('register.php?role=patient') ?>" class="btn btn-primary">
                Book Appointment <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            <?php foreach ($doctors as $doc): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card doctor-card h-100 p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-lg me-3"><?= strtoupper(substr($doc['full_name'], 0, 1)) ?></div>
                        <div>
                            <h5 class="mb-0 fw-700">Dr. <?= e($doc['full_name']) ?></h5>
                            <span class="text-primary small fw-600"><?= e($doc['specialty'] ?? 'General Physician') ?></span>
                        </div>
                    </div>
                    <?php if ($doc['bio']): ?>
                        <p class="text-muted small mb-3"><?= e(substr($doc['bio'], 0, 100)) ?>...</p>
                    <?php endif; ?>
                    <div class="d-flex gap-3 text-muted small">
                        <?php if ($doc['experience_years']): ?>
                        <span><i class="bi bi-award me-1 text-warning"></i><?= e($doc['experience_years']) ?> yrs exp</span>
                        <?php endif; ?>
                        <?php if ($doc['consultation_fee']): ?>
                        <span><i class="bi bi-cash me-1 text-success"></i>$<?= number_format($doc['consultation_fee'], 0) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA Section -->
    <div class="card bg-gradient-primary text-white text-center p-5 mb-4">
        <h3 class="fw-700 mb-3">Ready to take control of your health?</h3>
        <p class="mb-4 opacity-85">Join thousands of patients who trust MedBook for their healthcare appointments.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= url('register.php?role=patient') ?>" class="btn btn-light btn-lg fw-600">
                <i class="bi bi-person-heart me-2"></i>Register as Patient
            </a>
            <a href="<?= url('register.php?role=doctor') ?>" class="btn btn-outline-light btn-lg">
                <i class="bi bi-hospital me-2"></i>Register as Doctor
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
