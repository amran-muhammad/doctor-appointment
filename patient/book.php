<?php
/**
 * patient/book.php
 * Book an appointment with a specific doctor
 * - Shows doctor's availability by day
 * - Generates available time slots
 * - Prevents double-booking
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('patient');

$pdo       = getDB();
$patientId = currentUserId();
$doctorId  = (int) get('doctor_id');

// Validate doctor exists
if (!$doctorId) {
    setFlash('danger', 'Please select a doctor first.');
    redirect(url('patient/doctors.php'));
}

$doctorStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'doctor' AND is_active = 1");
$doctorStmt->execute([$doctorId]);
$doctor = $doctorStmt->fetch();

if (!$doctor) {
    setFlash('danger', 'Doctor not found.');
    redirect(url('patient/doctors.php'));
}

// Get doctor's available days
$availStmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? AND is_active = 1 ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
$availStmt->execute([$doctorId]);
$availability = $availStmt->fetchAll();

// Map available days for quick lookup
$availableDays = [];
foreach ($availability as $a) {
    $availableDays[$a['day_of_week']] = $a;
}

$errors = [];

// ── Handle Booking Submission ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $appointmentDate = post('appointment_date');
    $appointmentTime = post('appointment_time');
    $reason          = post('reason');

    // Validation
    if (!$appointmentDate) $errors[] = 'Please select a date.';
    if (!$appointmentTime) $errors[] = 'Please select a time slot.';
    if ($appointmentDate && !isFutureDate($appointmentDate)) $errors[] = 'Please select a future date.';

    if (empty($errors)) {
        // Check the selected day is in doctor's availability
        $dayOfWeek = getDayOfWeek($appointmentDate);
        if (!isset($availableDays[$dayOfWeek])) {
            $errors[] = 'The doctor is not available on ' . $dayOfWeek . 's.';
        } else {
            // Validate the time slot is within the doctor's hours
            $avail     = $availableDays[$dayOfWeek];
            $timeSlots = generateTimeSlots($avail['start_time'], $avail['end_time'], $avail['slot_duration_minutes']);

            if (!in_array($appointmentTime, $timeSlots)) {
                $errors[] = 'Invalid time slot selected.';
            } else {
                // Check for double-booking (unique constraint also handles this in DB)
                $checkStmt = $pdo->prepare("
                    SELECT id FROM appointments 
                    WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'rejected'
                ");
                $checkStmt->execute([$doctorId, $appointmentDate, $appointmentTime]);
                if ($checkStmt->fetch()) {
                    $errors[] = 'This time slot is already booked. Please choose another.';
                }

                // Check patient doesn't have pending/approved appointment at same date+time
                $dupStmt = $pdo->prepare("
                    SELECT id FROM appointments 
                    WHERE patient_id = ? AND appointment_date = ? AND status IN ('pending','approved')
                ");
                $dupStmt->execute([$patientId, $appointmentDate]);
                if ($dupStmt->fetch()) {
                    $errors[] = 'You already have an appointment on this date.';
                }
            }
        }
    }

    // ── Insert Appointment ────────────────────────────────────
    if (empty($errors)) {
        try {
            $pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
                VALUES (:patient, :doctor, :date, :time, :reason, 'pending')
            ")->execute([
                ':patient' => $patientId,
                ':doctor'  => $doctorId,
                ':date'    => $appointmentDate,
                ':time'    => $appointmentTime,
                ':reason'  => $reason ?: null,
            ]);

            setFlash('success', 'Appointment request sent! You will be notified once the doctor reviews it.');
            redirect(url('patient/appointments.php'));

        } catch (PDOException $e) {
            // Handle unique constraint violation (double-booking race condition)
            if ($e->getCode() == 23000) {
                $errors[] = 'This slot was just booked by someone else. Please choose another.';
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Book Appointment';
require_once __DIR__ . '/../includes/header.php';

// Calculate min date (tomorrow) and prepare available days for JS
$minDate     = date('Y-m-d', strtotime('+1 day'));
$maxDate     = date('Y-m-d', strtotime('+60 days'));
$dayNames    = array_keys($availableDays);
?>

<script>const siteUrl = '<?= SITE_URL ?>';</script>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('patient/doctors.php') ?>">Find Doctors</a></li>
        <li class="breadcrumb-item active">Book Appointment</li>
    </ol>
</nav>

<div class="row g-4">
    <!-- Doctor Info Sidebar -->
    <div class="col-lg-4">
        <div class="card p-4 mb-4">
            <div class="text-center mb-3">
                <div class="avatar-lg mx-auto mb-3"><?= strtoupper(substr($doctor['full_name'], 0, 1)) ?></div>
                <h5 class="fw-700">Dr. <?= e($doctor['full_name']) ?></h5>
                <span class="badge bg-primary-subtle text-primary"><?= e($doctor['specialty'] ?? 'General Physician') ?></span>
            </div>

            <?php if ($doctor['bio']): ?>
            <p class="text-muted small mb-3"><?= e($doctor['bio']) ?></p>
            <?php endif; ?>

            <div class="small text-muted">
                <?php if ($doctor['experience_years']): ?>
                <p class="mb-1"><i class="bi bi-award text-warning me-2"></i><?= $doctor['experience_years'] ?> years experience</p>
                <?php endif; ?>
                <?php if ($doctor['consultation_fee']): ?>
                <p class="mb-0"><i class="bi bi-cash text-success me-2"></i>Consultation fee: <strong>$<?= number_format($doctor['consultation_fee'], 2) ?></strong></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Days Card -->
        <div class="card p-4">
            <h6 class="fw-700 mb-3"><i class="bi bi-calendar2-week text-primary me-2"></i>Available Days</h6>
            <?php if (empty($availability)): ?>
                <p class="text-muted small">No availability set yet. Please check back later.</p>
            <?php else: ?>
                <?php foreach ($availability as $avail): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-600 small"><?= e($avail['day_of_week']) ?></span>
                    <span class="text-muted small">
                        <?= formatTime($avail['start_time']) ?> – <?= formatTime($avail['end_time']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-calendar-plus me-2 text-primary"></i>Select Date & Time</h5>
            </div>
            <div class="card-body">

                <?php if (empty($availability)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This doctor has not set any available time slots yet. Please try again later.
                    </div>
                <?php else: ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?>
                            <div><i class="bi bi-exclamation-circle me-2"></i><?= e($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="bookingForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="doctor_id" id="doctor_id" value="<?= $doctorId ?>">
                    <input type="hidden" name="appointment_time" id="appointment_time" value="">

                    <!-- Step 1: Date -->
                    <div class="mb-4">
                        <label class="form-label fw-700 fs-6">
                            <span class="badge bg-primary me-2">1</span>Choose a Date
                        </label>
                        <input type="date"
                               name="appointment_date"
                               id="appointment_date"
                               class="form-control"
                               min="<?= $minDate ?>"
                               max="<?= $maxDate ?>"
                               value="<?= e(post('appointment_date')) ?>"
                               required>
                        <div class="form-text text-muted mt-1">
                            Available on: <?= implode(', ', $dayNames) ?>
                        </div>
                    </div>

                    <!-- Step 2: Time Slots (dynamically loaded) -->
                    <div class="mb-4">
                        <label class="form-label fw-700 fs-6">
                            <span class="badge bg-primary me-2">2</span>Choose a Time Slot
                        </label>
                        <div id="slots-wrapper">
                            <div class="p-4 bg-light rounded text-center text-muted">
                                <i class="bi bi-calendar-event fs-2 d-block mb-2"></i>
                                Please select a date first to see available slots.
                            </div>
                        </div>
                        <div id="selected-time-display" class="mt-2 text-success fw-600 small"></div>
                    </div>

                    <!-- Step 3: Reason -->
                    <div class="mb-4">
                        <label class="form-label fw-700 fs-6">
                            <span class="badge bg-secondary me-2">3</span>Reason for Visit <span class="text-muted fw-400">(optional)</span>
                        </label>
                        <textarea name="reason" class="form-control" rows="3"
                                  placeholder="Briefly describe your symptoms or reason for the appointment..."><?= e(post('reason')) ?></textarea>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn" disabled>
                            <i class="bi bi-send me-2"></i>Request Appointment
                        </button>
                        <a href="<?= url('patient/doctors.php') ?>" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput   = document.getElementById('appointment_date');
    const slotsWrapper = document.getElementById('slots-wrapper');
    const timeInput   = document.getElementById('appointment_time');
    const submitBtn   = document.getElementById('submitBtn');
    const display     = document.getElementById('selected-time-display');
    const doctorId    = document.getElementById('doctor_id').value;

    // ── Load slots when date changes ──────────────────────────
    dateInput.addEventListener('change', function () {
        const date = this.value;
        if (!date) return;

        // Reset selection
        timeInput.value     = '';
        submitBtn.disabled  = true;
        display.textContent = '';

        // Show loading spinner
        slotsWrapper.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2 small">Loading available slots...</p>
            </div>`;

        fetch(`${siteUrl}/patient/get_slots.php?doctor_id=${encodeURIComponent(doctorId)}&date=${encodeURIComponent(date)}`)
            .then(function(r) {
                if (!r.ok) throw new Error('Network error');
                return r.json();
            })
            .then(function(data) {
                if (data.error) {
                    slotsWrapper.innerHTML = `
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>${data.error}
                        </div>`;
                    return;
                }
                if (!data.slots || data.slots.length === 0) {
                    slotsWrapper.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                            No available slots for this day.
                        </div>`;
                    return;
                }
                renderSlots(data.slots);
            })
            .catch(function(err) {
                slotsWrapper.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-wifi-off me-2"></i>Failed to load slots. Please try again.
                    </div>`;
                console.error('Slot fetch error:', err);
            });
    });

    // ── Render slot buttons ───────────────────────────────────
    function renderSlots(slots) {
        const available = slots.filter(s => !s.booked).length;

        let html = `<div class="mb-2 small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        ${available} of ${slots.length} slots available — click one to select
                    </div>
                    <div class="slot-grid">`;

        slots.forEach(function(slot) {
            if (slot.booked) {
                html += `<button type="button" class="slot-btn slot-booked" disabled title="Already booked">
                            ${slot.label}
                         </button>`;
            } else {
                html += `<button type="button" class="slot-btn" data-time="${slot.time}" data-label="${slot.label}">
                            ${slot.label}
                         </button>`;
            }
        });

        html += `</div>`;
        slotsWrapper.innerHTML = html;

        // ── Attach click handlers to available slots ──────────
        slotsWrapper.querySelectorAll('.slot-btn:not(.slot-booked)').forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Deselect all
                slotsWrapper.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));

                // Select this one
                this.classList.add('selected');

                // Set hidden input value
                timeInput.value = this.dataset.time;

                // Update display and enable submit
                display.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i>Selected: <strong>${this.dataset.label}</strong>`;
                submitBtn.disabled = false;
            });
        });
    }

    // ── Guard: prevent submit without a slot selected ─────────
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        if (!timeInput.value) {
            e.preventDefault();
            display.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Please select a time slot before submitting.</span>`;
            slotsWrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
