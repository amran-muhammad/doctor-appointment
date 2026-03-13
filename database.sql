-- ============================================================
-- Doctor Appointment Booking System - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS doctor_appointment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE doctor_appointment;

-- ============================================================
-- USERS TABLE (Doctors & Patients share this table via role)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,           -- bcrypt hashed
    role ENUM('doctor','patient') NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    -- Doctor-specific fields (NULL for patients)
    specialty VARCHAR(100) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    experience_years TINYINT UNSIGNED DEFAULT NULL,
    consultation_fee DECIMAL(10,2) DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    -- Shared fields
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============================================================
-- DOCTOR AVAILABILITY TABLE
-- Stores recurring weekly availability per doctor
-- ============================================================
CREATE TABLE IF NOT EXISTS doctor_availability (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration_minutes TINYINT UNSIGNED NOT NULL DEFAULT 30,  -- each appointment slot length
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week)
) ENGINE=InnoDB;

-- ============================================================
-- APPOINTMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reason TEXT DEFAULT NULL,                  -- patient's reason for visit
    doctor_notes TEXT DEFAULT NULL,            -- doctor's notes/remarks
    email_sent TINYINT(1) NOT NULL DEFAULT 0, -- track if notification email was sent
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    -- Prevent double-booking same slot
    UNIQUE KEY unique_slot (doctor_id, appointment_date, appointment_time),
    INDEX idx_patient (patient_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- CSRF TOKENS TABLE (basic server-side CSRF tracking)
-- ============================================================
CREATE TABLE IF NOT EXISTS csrf_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE SEED DATA (optional - comment out in production)
-- ============================================================
-- Password for all sample users: Password@123
INSERT INTO users (full_name, email, password, role, phone, specialty, bio, experience_years, consultation_fee) VALUES
('Dr. Sarah Johnson', 'doctor@demo.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '+1-555-0101', 'Cardiology', 'Board-certified cardiologist with 15 years of experience in treating heart conditions.', 15, 150.00),
('John Patient', 'patient@demo.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '+1-555-0202', NULL, NULL, NULL, NULL);

INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration_minutes) VALUES
(1, 'Monday', '09:00:00', '17:00:00', 30),
(1, 'Wednesday', '09:00:00', '13:00:00', 30),
(1, 'Friday', '10:00:00', '16:00:00', 30);
