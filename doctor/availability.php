<?php
/**
 * doctor/availability.php
 * Manage weekly availability: days, start/end times, slot duration
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('doctor');

$pdo      = getDB();
$doctorId = currentUserId();
$days     = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// ── Handle Save ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    // Delete all existing availability for this doctor
    $pdo->prepare("DELETE FROM doctor_availability WHERE doctor_id = ?")->execute([$doctorId]);

    // Insert/update enabled days
    $stmt = $pdo->prepare("
        INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration_minutes, is_active)
        VALUES (:doctor_id, :day, :start, :end, :duration, 1)
    ");

    $saved = 0;
    foreach ($days as $day) {
        $dayKey = strtolower($day);
        // Check if this day is enabled
        if (!empty($_POST['day_enabled'][$dayKey])) {
            $start    = $_POST['start_time'][$dayKey] ?? '';
            $end      = $_POST['end_time'][$dayKey] ?? '';
            $duration = (int) ($_POST['slot_duration'][$dayKey] ?? 30);

            // Validate times
            if ($start && $end && strtotime($start) < strtotime($end)) {
                $stmt->execute([
                    ':doctor_id' => $doctorId,
                    ':day'       => $day,
                    ':start'     => $start,
                    ':end'       => $end,
                    ':duration'  => in_array($duration, [15,20,30,45,60]) ? $duration : 30,
                ]);
                $saved++;
            }
        }
    }

    setFlash('success', "Availability saved! {$saved} day(s) configured.");
    redirect(url('doctor/availability.php'));
}

// ── Load Current Availability ─────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ?");
$stmt->execute([$doctorId]);
$rows = $stmt->fetchAll();

// Index by day for easy lookup
$availability = [];
foreach ($rows as $row) {
    $availability[$row['day_of_week']] = $row;
}

$pageTitle = 'Manage Availability';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="section-title mb-0">Manage Availability</h2>
        <p class="section-subtitle">Set your weekly schedule and appointment slot durations</p>
    </div>
    <a href="<?= url('doctor/dashboard.php') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Dashboard
    </a>
</div>

<?php renderFlash(); ?>

<div class="card">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0"><i class="bi bi-calendar-week me-2 text-primary"></i>Weekly Schedule</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">
            <i class="bi bi-info-circle me-1"></i>
            Enable days you're available and set the working hours. Patients can only book within these slots.
        </p>

        <form method="POST" action="">
            <?= csrfField() ?>

            <div class="row g-4">
                <?php foreach ($days as $day):
                    $dayKey  = strtolower($day);
                    $enabled = isset($availability[$day]);
                    $avail   = $availability[$day] ?? null;
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="day-card <?= $enabled ? 'active-day' : '' ?>">
                        <!-- Day Toggle -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check form-switch mb-0">
                                    <input type="checkbox"
                                           class="form-check-input day-toggle"
                                           name="day_enabled[<?= $dayKey ?>]"
                                           id="day_<?= $dayKey ?>"
                                           value="1"
                                           <?= $enabled ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-700 fs-6" for="day_<?= $dayKey ?>">
                                        <?= $day ?>
                                    </label>
                                </div>
                            </div>
                            <?php if ($enabled): ?>
                                <span class="badge bg-success-subtle text-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Off</span>
                            <?php endif; ?>
                        </div>

                        <!-- Time Fields -->
                        <div class="row g-2" id="fields_<?= $dayKey ?>">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">Start Time</label>
                                <input type="time"
                                       name="start_time[<?= $dayKey ?>]"
                                       class="form-control form-control-sm"
                                       value="<?= $avail ? e(substr($avail['start_time'], 0, 5)) : '09:00' ?>"
                                       <?= !$enabled ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">End Time</label>
                                <input type="time"
                                       name="end_time[<?= $dayKey ?>]"
                                       class="form-control form-control-sm"
                                       value="<?= $avail ? e(substr($avail['end_time'], 0, 5)) : '17:00' ?>"
                                       <?= !$enabled ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted mb-1">Slot Duration</label>
                                <select name="slot_duration[<?= $dayKey ?>]"
                                        class="form-select form-select-sm"
                                        <?= !$enabled ? 'disabled' : '' ?>>
                                    <?php foreach ([15,20,30,45,60] as $mins): ?>
                                        <option value="<?= $mins ?>"
                                            <?= ($avail && $avail['slot_duration_minutes'] == $mins) ? 'selected' : ($mins == 30 && !$avail ? 'selected' : '') ?>>
                                            <?= $mins ?> minutes
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Save Button -->
            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-save me-2"></i>Save Availability
                </button>
                <span class="text-muted ms-3 small">Changes will apply to new bookings immediately.</span>
            </div>
        </form>
    </div>
</div>

<!-- Preview Card -->
<?php if (!empty($availability)): ?>
<div class="card mt-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0"><i class="bi bi-eye me-2 text-success"></i>Current Schedule Preview</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($availability as $day => $avail): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 bg-light rounded">
                    <strong class="text-primary"><?= e($day) ?></strong><br>
                    <span class="small text-muted">
                        <?= formatTime($avail['start_time']) ?> – <?= formatTime($avail['end_time']) ?>
                    </span><br>
                    <span class="badge bg-info-subtle text-info mt-1"><?= $avail['slot_duration_minutes'] ?>min slots</span>
                    <?php
                    $slots = generateTimeSlots($avail['start_time'], $avail['end_time'], $avail['slot_duration_minutes']);
                    echo '<br><small class="text-muted">' . count($slots) . ' slots/day</small>';
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
