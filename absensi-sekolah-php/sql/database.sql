CREATE DATABASE IF NOT EXISTS absensi_sekolah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi_sekolah;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator') NOT NULL DEFAULT 'operator',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(50) NOT NULL,
    department VARCHAR(100) NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    homeroom_teacher VARCHAR(150) NOT NULL,
    homeroom_phone VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nisn VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    photo_path VARCHAR(255) NULL,
    class_id INT UNSIGNED NOT NULL,
    qr_token VARCHAR(120) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    check_in TIME NULL,
    check_out TIME NULL,
    status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpa') NOT NULL DEFAULT 'hadir',
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uniq_attendance_student_date (student_id, attendance_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS school_settings (
    id TINYINT UNSIGNED PRIMARY KEY,
    school_name VARCHAR(150) NOT NULL,
    school_address TEXT NULL,
    school_phone VARCHAR(100) NULL,
    principal_name VARCHAR(150) NULL,
    principal_nip VARCHAR(100) NULL,
    attendance_start_time TIME NOT NULL DEFAULT '07:00:00',
    late_after TIME NOT NULL DEFAULT '07:15:00',
    attendance_end_time TIME NOT NULL DEFAULT '15:00:00',
    scan_mode ENUM('auto', 'masuk', 'pulang') NOT NULL DEFAULT 'auto',
    document_city VARCHAR(100) NULL,
    footer_note VARCHAR(255) NULL,
    logo_path VARCHAR(255) NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sp_letters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    letter_no VARCHAR(100) NOT NULL,
    sp_level ENUM('SP1', 'SP2', 'SP3') NOT NULL DEFAULT 'SP1',
    letter_date DATE NOT NULL,
    reason TEXT NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_sp_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sp_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (full_name, username, email, password, role, status, created_at)
SELECT 'Administrator', 'admin', 'admin@sekolah.local', '$2y$12$F91lVuWA5C.aR9woc87gbuPwmIJYwrfMl5jXUIIIDYyngnWDieKLG', 'admin', 'approved', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin'
);

INSERT INTO school_settings (id, school_name, school_address, school_phone, principal_name, principal_nip, attendance_start_time, late_after, attendance_end_time, scan_mode, document_city, footer_note)
SELECT 1, 'SMK Contoh Indonesia', 'Jl. Pendidikan No. 1', '08123456789', 'Nama Kepala Sekolah', '-', '07:00:00', '07:15:00', '15:00:00', 'auto', 'Kota Anda', 'Gunakan QR ini pada mesin scanner sekolah.'
WHERE NOT EXISTS (
    SELECT 1 FROM school_settings WHERE id = 1
);
