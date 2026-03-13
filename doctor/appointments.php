<?php
/**
 * doctor/appointments.php
 * View and manage all appointment requests (approve/reject)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mailer.php';

requireRole('doctor');

$pdo      = getDB();
$doctorId = currentUserId();

// ── Handle Status Change (Approve / Reject) ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    requireCsrf();

    $apptId      = (int) ($_POST['appointment_id'] ?? 0);
    $action      = in_array($_POST['action'], ['approve', 'reject']) ? $_POST['action'] : null;
    $doctorNotes = sanitize($_POST['doctor_notes'] ?? '');

    if ($apptId && $action) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        // Verify this appointment belongs to this doctor
        $checkStmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND doctor_id = ?");
        $checkStmt->execute([$apptId, $doctorId]);
        $appt = $checkStmt->fetch();

        if ($appt) {
            // Update status and notes
            $updateStmt = $pdo->prepare("
                UPDATE appointments 
                SET status = :status, doctor_notes = :notes, updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':status' => $newStatus,
                ':notes'  => $doctorNotes,
                ':id'     => $apptId,
            ]);

            // Fetch full appointment details for email
            $emailStmt = $pdo->prepare("
                SELECT a.*, 
                       p.full_name AS patient_name, p.email AS patient_email,
                       d.full_name AS doctor_name, d.specialty
                FROM appointments a
                JOIN users p ON p.id = a.patient_id
                JOIN users d ON d.id = a.doctor_id
                WHERE a.id = ?
            ");
            $emailStmt->execute([$apptId]);
            $fullAppt = $emailStmt->fetch();

            // Send email notification
            $emailSent = false;
            if ($fullAppt) {
                $fullAppt['doctor_notes'] = $doctorNotes;
                if ($newStatus === 'approved') {
                    $emailSent = sendAppointmentApprovalEmail($fullAppt);
                } else {
                    $emailSent = sendAppointmentRejectionEmail($fullAppt);
                }

                // Mark email as sent
                if ($emailSent) {
                    $pdo->prepare("UPDATE appointments SET email_sent = 1 WHERE id = ?")
                        ->execute([$apptId]);
                }
            }

            $msg = ucfirst($newStatus) . ' appointment';
            if ($emailSent) $msg .= ' — email notification sent to patient';
            setFlash('success', $msg . '.');
        } else {
            setFlash('danger', 'Appointment not found or access denied.');
        }
    }

    redirect(url('doctor/appointments.php'));
}

// ── Filters ───────────────────────────────────────────────────
$filterStatus = in_array(get('status'), ['pending','approved','rejected','']) ? get('status') : '';
$filterDate   = get('date');

// ── Fetch Appointments ─────────────────────────────────────────
$where  = ['a.doctor_id = :doctor_id'];
$params = [':doctor_id' => $doctorId];

if ($filterStatus) {
    $where[]            = 'a.status = :status';
    $params[':status']  = $filterStatus;
}
if ($filterDate && strtotime($filterDate)) {
    $where[]           = 'a.appointment_date = :date';
    $params[':date']   = $filterDate;
}

$sql = "
    SELECT a.*, 
           u.full_name AS patient_name, u.email AS patient_email, u.phone AS patient_phone
    FROM appointments a
    JOIN users u ON u.id = a.patient_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Detailed view for single appointment
$viewAppt = null;
if (get('id')) {
    $vs = $pdo->prepare("
        SELECT a.*, u.full_name AS patient_name, u.email AS patient_email, u.phone AS patient_phone
        FROM appointments a JOIN users u ON u.id = a.patient_id
        WHERE a.id = ? AND a.doctor_id = ?
    ");
    $vs->execute([(int)get('id'), $doctorId]);
    $viewAppt = $vs->fetch();
}

$pageTitle = 'Manage Appointments';
require_once __DIR__ . '/../includes/header.php';
?>

<script>const siteUrl = '<?= SITE_URL ?>';</script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="section-title mb-0">Appointments</h2>
        <p class="section-subtitle">Review and manage patient appointment requests</p>
    </div>
    <a href="<?= url('doctor/dashboard.php') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Dashboard
    </a>
</div>

<?php renderFlash(); ?>

<!-- Appointment Detail Modal (shown when ?id= is set) -->
<?php if ($viewAppt): ?>
<div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard2-pulse me-2 text-primary"></i>Appointment Details</h5>
                <a href="<?= url('doctor/appointments.php') ?>" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded mb-3">
                            <h6 class="fw-700 text-primary mb-3">Patient Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <?= e($viewAppt['patient_name']) ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?= e($viewAppt['patient_email']) ?></p>
                            <p class="mb-0"><strong>Phone:</strong> <?= e($viewAppt['patient_phone'] ?? 'N/A') ?></p>
                        </div>
                        <div class="p-3 bg-light rounded">
                            <h6 class="fw-700 text-primary mb-3">Appointment Info</h6>
                            <p class="mb-1"><strong>Date:</strong> <?= formatDate($viewAppt['appointment_date'], 'l, F j, Y') ?></p>
                            <p class="mb-1"><strong>Time:</strong> <?= formatTime($viewAppt['appointment_time']) ?></p>
                            <p class="mb-1"><strong>Status:</strong> <?= statusBadge($viewAppt['status']) ?></p>
                            <p class="mb-0"><strong>Booked:</strong> <?= formatDate($viewAppt['created_at'], 'M d, Y g:i A') ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php if ($viewAppt['reason']): ?>
                        <div class="p-3 bg-light rounded mb-3">
                            <h6 class="fw-700 text-primary mb-2">Patient's Reason</h6>
                            <p class="mb-0 text-muted"><?= nl2br(e($viewAppt['reason'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($viewAppt['status'] === 'pending'): ?>
                        <!-- Action Form -->
                        <div class="p-3 border rounded">
                            <h6 class="fw-700 mb-3">Take Action</h6>
                            <form method="POST" action="">
                                <?= csrfField() ?>
                                <input type="hidden" name="appointment_id" value="<?= $viewAppt['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Doctor's Notes (optional)</label>
                                    <textarea name="doctor_notes" class="form-control form-control-sm" rows="3"
                                              placeholder="Add a note for the patient..."><?= e($viewAppt['doctor_notes'] ?? '') ?></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="action" value="approve"
                                            class="btn btn-success flex-fill"
                                            onclick="return confirm('Approve this appointment?')">
                                        <i class="bi bi-check-circle me-1"></i>Approve
                                    </button>
                                    <button type="submit" name="action" value="reject"
                                            class="btn btn-danger flex-fill"
                                            onclick="return confirm('Reject this appointment?')">
                                        <i class="bi bi-x-circle me-1"></i>Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php elseif ($viewAppt['doctor_notes']): ?>
                        <div class="p-3 bg-light rounded">
                            <h6 class="fw-700 text-primary mb-2">Doctor's Notes</h6>
                            <p class="mb-0 text-muted"><?= nl2br(e($viewAppt['doctor_notes'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= url('doctor/appointments.php') ?>" class="btn btn-secondary">Close</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-auto">
                <label class="form-label small fw-600 mb-1">Filter by Status</label>
                <div class="btn-group btn-group-sm" role="group">
                    <?php foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label): ?>
                    <a href="?status=<?= $val ?>&date=<?= urlencode($filterDate) ?>"
                       class="btn <?= $filterStatus === $val ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <?= $label ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-600 mb-1">Filter by Date</label>
                <input type="date" name="date" class="form-control form-control-sm"
                       value="<?= e($filterDate) ?>"
                       onchange="this.form.submit()">
                <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
            </div>
            <?php if ($filterDate): ?>
            <div class="col-auto">
                <a href="?status=<?= urlencode($filterStatus) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Clear Date
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Appointments Table -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0"><i class="bi bi-list-check me-2 text-primary"></i>
            <?= count($appointments) ?> Appointment<?= count($appointments) !== 1 ? 's' : '' ?>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($appointments)): ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x text-muted"></i>
                <p class="mt-2 text-muted">No appointments found for the selected filters.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Email Sent</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $i => $appt): ?>
                    <tr class="<?= $appt['status'] === 'pending' ? 'table-warning' : '' ?>">
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-600"><?= e($appt['patient_name']) ?></div>
                            <small class="text-muted"><?= e($appt['patient_email']) ?></small>
                        </td>
                        <td><?= formatDate($appt['appointment_date']) ?></td>
                        <td><?= formatTime($appt['appointment_time']) ?></td>
                        <td><?= statusBadge($appt['status']) ?></td>
                        <td>
                            <?php if ($appt['email_sent']): ?>
                                <span class="text-success small"><i class="bi bi-check2-circle me-1"></i>Sent</span>
                            <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-dash-circle me-1"></i>Not sent</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?id=<?= $appt['id'] ?>&status=<?= urlencode($filterStatus) ?>"
                                   class="btn btn-sm btn-outline-primary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($appt['status'] === 'pending'): ?>
                                <form method="POST" class="d-inline" action="">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                    <button type="submit" name="action" value="approve"
                                            class="btn btn-sm btn-success" title="Approve"
                                            onclick="return confirm('Approve this appointment?')">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline" action="">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                    <button type="submit" name="action" value="reject"
                                            class="btn btn-sm btn-danger" title="Reject"
                                            onclick="return confirm('Reject this appointment?')">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
