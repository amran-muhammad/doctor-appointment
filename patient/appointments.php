<?php
/**
 * patient/appointments.php
 * View all appointments with their status
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('patient');

$pdo       = getDB();
$patientId = currentUserId();

// Status filter
$filterStatus = in_array(get('status'), ['pending','approved','rejected','']) ? get('status') : '';

$where  = ['a.patient_id = :patient_id'];
$params = [':patient_id' => $patientId];

if ($filterStatus) {
    $where[]            = 'a.status = :status';
    $params[':status']  = $filterStatus;
}

$stmt = $pdo->prepare("
    SELECT a.*, 
           d.full_name AS doctor_name, d.specialty, d.phone AS doctor_phone, d.email AS doctor_email
    FROM appointments a
    JOIN users d ON d.id = a.doctor_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'My Appointments';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="section-title mb-0">My Appointments</h2>
        <p class="section-subtitle">Track all your appointment requests and statuses</p>
    </div>
    <a href="<?= url('patient/doctors.php') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Book New
    </a>
</div>

<?php renderFlash(); ?>

<!-- Status Filter Tabs -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach (['' => 'All', 'pending' => '⏳ Pending', 'approved' => '✅ Approved', 'rejected' => '❌ Rejected'] as $val => $label): ?>
            <a href="?status=<?= $val ?>"
               class="btn btn-sm <?= $filterStatus === $val ? 'btn-primary' : 'btn-outline-primary' ?>">
               <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Appointments -->
<?php if (empty($appointments)): ?>
    <div class="card">
        <div class="empty-state py-5">
            <i class="bi bi-calendar-x text-muted fs-1 d-block mb-3"></i>
            <h5 class="text-muted">No appointments found</h5>
            <p class="text-muted">
                <?= $filterStatus ? "No {$filterStatus} appointments." : "You haven't booked any appointments yet." ?>
            </p>
            <a href="<?= url('patient/doctors.php') ?>" class="btn btn-primary mt-2">
                <i class="bi bi-search-heart me-2"></i>Find a Doctor
            </a>
        </div>
    </div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($appointments as $appt): ?>
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <!-- Header with Status Badge -->
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-700 mb-1">Dr. <?= e($appt['doctor_name']) ?></h5>
                        <span class="text-muted small"><?= e($appt['specialty'] ?? 'General Physician') ?></span>
                    </div>
                    <?= statusBadge($appt['status']) ?>
                </div>

                <!-- Appointment Details -->
                <div class="bg-light rounded p-3 mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-calendar3 text-primary me-2"></i>
                        <span class="fw-600"><?= formatDate($appt['appointment_date'], 'l, F j, Y') ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clock text-primary me-2"></i>
                        <span><?= formatTime($appt['appointment_time']) ?></span>
                    </div>
                </div>

                <!-- Reason -->
                <?php if ($appt['reason']): ?>
                <div class="mb-3">
                    <p class="text-muted small mb-1"><strong>Reason:</strong></p>
                    <p class="text-muted small mb-0"><?= e($appt['reason']) ?></p>
                </div>
                <?php endif; ?>

                <!-- Doctor's Notes (shown after review) -->
                <?php if ($appt['doctor_notes'] && $appt['status'] !== 'pending'): ?>
                <div class="alert alert-<?= $appt['status'] === 'approved' ? 'success' : 'danger' ?> small py-2 mb-3">
                    <strong><i class="bi bi-chat-left-text me-1"></i>Doctor's Note:</strong><br>
                    <?= e($appt['doctor_notes']) ?>
                </div>
                <?php endif; ?>

                <!-- Status specific messages -->
                <?php if ($appt['status'] === 'approved' && $appt['email_sent']): ?>
                <div class="text-success small mb-2">
                    <i class="bi bi-envelope-check me-1"></i>Confirmation email sent
                </div>
                <?php elseif ($appt['status'] === 'pending'): ?>
                <div class="text-warning small mb-2">
                    <i class="bi bi-hourglass-split me-1"></i>Awaiting doctor's review
                </div>
                <?php endif; ?>

                <!-- Booked on -->
                <p class="text-muted small mb-0">
                    <i class="bi bi-calendar-plus me-1"></i>
                    Booked on <?= formatDate($appt['created_at'], 'M j, Y') ?>
                </p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
