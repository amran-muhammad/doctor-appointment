<?php
/**
 * patient/doctors.php
 * Browse all doctors, view profiles, and initiate booking
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('patient');

$pdo = getDB();

// Specialty filter
$filterSpecialty = sanitize($_GET['specialty'] ?? '');

// Build query
$sql    = "SELECT * FROM users WHERE role='doctor' AND is_active=1";
$params = [];

if ($filterSpecialty) {
    $sql     .= " AND specialty = ?";
    $params[] = $filterSpecialty;
}
$sql .= " ORDER BY full_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// Get all specialties for filter dropdown
$specStmt  = $pdo->query("SELECT DISTINCT specialty FROM users WHERE role='doctor' AND is_active=1 AND specialty IS NOT NULL ORDER BY specialty");
$specialties = $specStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Find a Doctor';
require_once __DIR__ . '/../includes/header.php';
?>

<script>const siteUrl = '<?= SITE_URL ?>';</script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="section-title mb-0">Find a Doctor</h2>
        <p class="section-subtitle">Browse our specialists and book your appointment</p>
    </div>
    <a href="<?= url('patient/dashboard.php') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Dashboard
    </a>
</div>

<?php renderFlash(); ?>

<!-- Specialty Filter -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="fw-600 text-muted me-2">Specialty:</span>
            <a href="<?= url('patient/doctors.php') ?>"
               class="btn btn-sm <?= !$filterSpecialty ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
            <?php foreach ($specialties as $spec): ?>
            <a href="?specialty=<?= urlencode($spec) ?>"
               class="btn btn-sm <?= $filterSpecialty === $spec ? 'btn-primary' : 'btn-outline-primary' ?>">
               <?= e($spec) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Doctor Cards -->
<?php if (empty($doctors)): ?>
    <div class="empty-state">
        <i class="bi bi-search text-muted"></i>
        <p class="mt-2 text-muted">No doctors found for the selected specialty.</p>
    </div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($doctors as $doc): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card doctor-card h-100">
            <div class="card-body p-4">
                <!-- Doctor Header -->
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="avatar-lg flex-shrink-0"><?= strtoupper(substr($doc['full_name'], 0, 1)) ?></div>
                    <div>
                        <h5 class="fw-700 mb-1">Dr. <?= e($doc['full_name']) ?></h5>
                        <span class="badge bg-primary-subtle text-primary"><?= e($doc['specialty'] ?? 'General Physician') ?></span>
                    </div>
                </div>

                <!-- Meta Info -->
                <div class="d-flex gap-3 text-muted small mb-3">
                    <?php if ($doc['experience_years']): ?>
                    <span><i class="bi bi-award-fill text-warning me-1"></i><?= $doc['experience_years'] ?> yrs</span>
                    <?php endif; ?>
                    <?php if ($doc['consultation_fee']): ?>
                    <span><i class="bi bi-cash-stack text-success me-1"></i>$<?= number_format($doc['consultation_fee'], 0) ?>/visit</span>
                    <?php endif; ?>
                </div>

                <!-- Bio -->
                <?php if ($doc['bio']): ?>
                <p class="text-muted small mb-3"><?= e(strlen($doc['bio']) > 120 ? substr($doc['bio'], 0, 120) . '...' : $doc['bio']) ?></p>
                <?php endif; ?>

                <!-- Contact -->
                <?php if ($doc['phone']): ?>
                <p class="small text-muted mb-3">
                    <i class="bi bi-telephone me-1"></i><?= e($doc['phone']) ?>
                </p>
                <?php endif; ?>

                <!-- Book Button -->
                <a href="<?= url('patient/book.php?doctor_id=' . $doc['id']) ?>"
                   class="btn btn-primary w-100">
                    <i class="bi bi-calendar-plus me-2"></i>Book Appointment
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
