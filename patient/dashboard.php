<?php
/**
 * patient/dashboard.php
 * Patient main dashboard
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('patient');

$pdo       = getDB();
$patientId = currentUserId();

// ── Stats ─────────────────────────────────────────────────────
$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(status = 'pending')  as pending,
        SUM(status = 'approved') as approved,
        SUM(status = 'rejected') as rejected
    FROM appointments WHERE patient_id = ?
");
$statsStmt->execute([$patientId]);
$stats = $statsStmt->fetch();

// Upcoming approved appointment
$upcomingStmt = $pdo->prepare("
    SELECT a.*, u.full_name AS doctor_name, u.specialty
    FROM appointments a JOIN users u ON u.id = a.doctor_id
    WHERE a.patient_id = ? AND a.status = 'approved' AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 1
");
$upcomingStmt->execute([$patientId]);
$upcoming = $upcomingStmt->fetch();

// Recent appointments
$recentStmt = $pdo->prepare("
    SELECT a.*, u.full_name AS doctor_name, u.specialty
    FROM appointments a JOIN users u ON u.id = a.doctor_id
    WHERE a.patient_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
");
$recentStmt->execute([$patientId]);
$recent = $recentStmt->fetchAll();

// Patient info
$patientStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$patientStmt->execute([$patientId]);
$patient = $patientStmt->fetch();

$pageTitle = 'Patient Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<script>const siteUrl = '<?= SITE_URL ?>';</script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="section-title mb-0">Welcome, <?= e($patient['full_name']) ?> 👋</h2>
        <p class="section-subtitle">Manage your appointments and health records</p>
    </div>
    <a href="<?= url('patient/doctors.php') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Book Appointment
    </a>
</div>

<?php renderFlash(); ?>

<!-- Upcoming Appointment Alert -->
<?php if ($upcoming): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-calendar-check-fill fs-3 text-success"></i>
        <div>
            <strong>Upcoming Appointment!</strong><br>
            <span>Dr. <?= e($upcoming['doctor_name']) ?> (<?= e($upcoming['specialty']) ?>) &mdash; 
                  <?= formatDate($upcoming['appointment_date'], 'l, F j') ?> at <?= formatTime($upcoming['appointment_time']) ?>
            </span>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card bg-gradient-primary">
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Bookings</div>
            <i class="bi bi-calendar2 stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card bg-gradient-warning">
            <div class="stat-number"><?= $stats['pending'] ?></div>
            <div class="stat-label">Pending</div>
            <i class="bi bi-hourglass stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card bg-gradient-success">
            <div class="stat-number"><?= $stats['approved'] ?></div>
            <div class="stat-label">Confirmed</div>
            <i class="bi bi-check-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card bg-gradient-danger">
            <div class="stat-number"><?= $stats['rejected'] ?></div>
            <div class="stat-label">Declined</div>
            <i class="bi bi-x-circle stat-icon"></i>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Appointments -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Appointments</h5>
                <a href="<?= url('patient/appointments.php') ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent)): ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x text-muted"></i>
                        <p class="mt-2 text-muted">No appointments yet. <a href="<?= url('patient/doctors.php') ?>">Book one now!</a></p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $appt): ?>
                            <tr>
                                <td>
                                    <div class="fw-600">Dr. <?= e($appt['doctor_name']) ?></div>
                                    <small class="text-muted"><?= e($appt['specialty']) ?></small>
                                </td>
                                <td>
                                    <div><?= formatDate($appt['appointment_date']) ?></div>
                                    <small class="text-muted"><?= formatTime($appt['appointment_time']) ?></small>
                                </td>
                                <td><?= statusBadge($appt['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-lightning me-2 text-warning"></i>Quick Actions</h5>
            </div>
            <div class="card-body d-grid gap-3">
                <a href="<?= url('patient/doctors.php') ?>" class="btn btn-primary">
                    <i class="bi bi-search-heart me-2"></i>Find & Book Doctor
                </a>
                <a href="<?= url('patient/appointments.php') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-list-check me-2"></i>All Appointments
                </a>
                <a href="<?= url('patient/profile.php') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-person-gear me-2"></i>Edit Profile
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2 text-info"></i>Tips</h5>
            </div>
            <div class="card-body small text-muted">
                <p class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Arrive 10-15 minutes early.</p>
                <p class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Bring your medical records.</p>
                <p class="mb-2"><i class="bi bi-check2 text-success me-2"></i>You'll receive email confirmation.</p>
                <p class="mb-0"><i class="bi bi-check2 text-success me-2"></i>Check status in "My Appointments".</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
