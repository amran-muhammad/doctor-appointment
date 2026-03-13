<?php
/**
 * doctor/dashboard.php
 * Doctor's main dashboard with stats and recent appointments
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('doctor');

$pdo      = getDB();
$doctorId = currentUserId();

// ── Stats ─────────────────────────────────────────────────────
$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(status = 'pending')  as pending,
        SUM(status = 'approved') as approved,
        SUM(status = 'rejected') as rejected
    FROM appointments
    WHERE doctor_id = ?
");
$statsStmt->execute([$doctorId]);
$stats = $statsStmt->fetch();

// Today's appointments
$todayStmt = $pdo->prepare("
    SELECT COUNT(*) as today
    FROM appointments
    WHERE doctor_id = ? AND appointment_date = CURDATE() AND status = 'approved'
");
$todayStmt->execute([$doctorId]);
$today = $todayStmt->fetchColumn();

// Recent 10 appointments
$recentStmt = $pdo->prepare("
    SELECT a.*, u.full_name AS patient_name, u.email AS patient_email, u.phone AS patient_phone
    FROM appointments a
    JOIN users u ON u.id = a.patient_id
    WHERE a.doctor_id = ?
    ORDER BY a.created_at DESC
    LIMIT 10
");
$recentStmt->execute([$doctorId]);
$recent = $recentStmt->fetchAll();

// Doctor profile
$doctorStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$doctorStmt->execute([$doctorId]);
$doctor = $doctorStmt->fetch();

$pageTitle = 'Doctor Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<script>const siteUrl = '<?= SITE_URL ?>';</script>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="section-title mb-0">Welcome, Dr. <?= e($doctor['full_name']) ?> 👋</h2>
        <p class="section-subtitle"><?= e($doctor['specialty'] ?? 'General Physician') ?> &middot; <?= date('l, F j, Y') ?></p>
    </div>
    <a href="<?= url('doctor/appointments.php') ?>" class="btn btn-primary">
        <i class="bi bi-clipboard2-pulse me-2"></i>View All Appointments
    </a>
</div>

<?php renderFlash(); ?>

<!-- Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card bg-gradient-primary">
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Appointments</div>
            <i class="bi bi-calendar2-heart stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card bg-gradient-warning">
            <div class="stat-number"><?= $stats['pending'] ?></div>
            <div class="stat-label">Pending Review</div>
            <i class="bi bi-hourglass-split stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card bg-gradient-success">
            <div class="stat-number"><?= $stats['approved'] ?></div>
            <div class="stat-label">Approved</div>
            <i class="bi bi-check-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card bg-gradient-info">
            <div class="stat-number"><?= $today ?></div>
            <div class="stat-label">Today's Patients</div>
            <i class="bi bi-person-check stat-icon"></i>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Appointments Table -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Appointments</h5>
                <a href="<?= url('doctor/appointments.php') ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent)): ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x text-muted"></i>
                        <p class="mt-2 text-muted">No appointments yet.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $appt): ?>
                            <tr>
                                <td>
                                    <div class="fw-600"><?= e($appt['patient_name']) ?></div>
                                    <small class="text-muted"><?= e($appt['patient_email']) ?></small>
                                </td>
                                <td>
                                    <div><?= formatDate($appt['appointment_date']) ?></div>
                                    <small class="text-muted"><?= formatTime($appt['appointment_time']) ?></small>
                                </td>
                                <td><?= statusBadge($appt['status']) ?></td>
                                <td>
                                    <a href="<?= url('doctor/appointments.php?id=' . $appt['id']) ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Doctor Profile Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>My Profile</h5>
            </div>
            <div class="card-body text-center">
                <div class="avatar-lg mx-auto mb-3"><?= strtoupper(substr($doctor['full_name'], 0, 1)) ?></div>
                <h5 class="fw-700 mb-1">Dr. <?= e($doctor['full_name']) ?></h5>
                <p class="text-primary small mb-2"><?= e($doctor['specialty'] ?? '—') ?></p>
                <?php if ($doctor['experience_years']): ?>
                    <p class="text-muted small mb-3"><?= $doctor['experience_years'] ?> years experience</p>
                <?php endif; ?>
                <div class="d-grid gap-2">
                    <a href="<?= url('doctor/profile.php') ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil me-2"></i>Edit Profile
                    </a>
                    <a href="<?= url('doctor/availability.php') ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-calendar-week me-2"></i>Manage Availability
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-lightning me-2 text-warning"></i>Quick Stats</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Approval Rate</span>
                    <strong class="text-success">
                        <?php
                        $rate = $stats['total'] > 0
                            ? round(($stats['approved'] / $stats['total']) * 100)
                            : 0;
                        echo $rate . '%';
                        ?>
                    </strong>
                </div>
                <div class="progress mb-3" style="height:8px;">
                    <div class="progress-bar bg-success" style="width:<?= $rate ?>%"></div>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Rejected</span>
                    <strong class="text-danger"><?= $stats['rejected'] ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
