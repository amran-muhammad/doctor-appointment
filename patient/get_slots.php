<?php
/**
 * patient/get_slots.php
 * AJAX endpoint: Returns available time slots for a given doctor + date
 * Excludes already-booked slots
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

// Must be logged in as patient
requireRole('patient');

header('Content-Type: application/json');

$doctorId = (int) ($_GET['doctor_id'] ?? 0);
$date     = sanitize($_GET['date'] ?? '');

// Validate inputs
if (!$doctorId || !$date || !strtotime($date)) {
    echo json_encode(['error' => 'Invalid request parameters.']);
    exit;
}

// Must be today or future
if (strtotime($date) < strtotime('today')) {
    echo json_encode(['error' => 'Please select a future date.']);
    exit;
}

$pdo = getDB();

// Get day of week for the requested date
$dayOfWeek = date('l', strtotime($date)); // e.g., "Monday"

// Check doctor has availability for that day
$availStmt = $pdo->prepare("
    SELECT * FROM doctor_availability 
    WHERE doctor_id = ? AND day_of_week = ? AND is_active = 1
");
$availStmt->execute([$doctorId, $dayOfWeek]);
$availability = $availStmt->fetch();

if (!$availability) {
    echo json_encode([
        'error' => "The doctor is not available on {$dayOfWeek}s. Please choose a different date.",
        'slots' => []
    ]);
    exit;
}

// Generate all possible time slots
$allSlots = generateTimeSlots(
    $availability['start_time'],
    $availability['end_time'],
    $availability['slot_duration_minutes']
);

// Get already-booked slots for this doctor on this date
$bookedStmt = $pdo->prepare("
    SELECT appointment_time 
    FROM appointments 
    WHERE doctor_id = ? AND appointment_date = ? AND status IN ('pending', 'approved')
");
$bookedStmt->execute([$doctorId, $date]);
$bookedTimes = $bookedStmt->fetchAll(PDO::FETCH_COLUMN);

// Build response
$slotsResponse = [];
foreach ($allSlots as $slot) {
    $isBooked = in_array($slot, $bookedTimes);
    $slotsResponse[] = [
        'time'   => $slot,
        'label'  => date('g:i A', strtotime($slot)),
        'booked' => $isBooked,
    ];
}

echo json_encode([
    'date'      => $date,
    'day'       => $dayOfWeek,
    'available' => count(array_filter($slotsResponse, fn($s) => !$s['booked'])),
    'total'     => count($slotsResponse),
    'slots'     => $slotsResponse,
]);
