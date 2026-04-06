<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$code = trim((string) ($payload['code'] ?? ''));
$code = preg_replace('/\s+/', '', $code);
$inputMode = trim((string) ($payload['mode'] ?? 'auto'));
$mode = in_array($inputMode, ['auto', 'masuk', 'pulang'], true) ? $inputMode : 'auto';

if ($code === '') {
    echo json_encode(['success' => false, 'message' => 'QR TOKEN wajib diisi.']);
    exit;
}

try {
    $stmt = db()->prepare('
        SELECT s.*, c.level, c.department, c.class_name
        FROM students s
        JOIN classes c ON c.id = s.class_id
        WHERE s.qr_token = :token
          AND s.is_active = 1
        LIMIT 1
    ');
    $stmt->execute([
        ':token' => $code,
    ]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode([
            'success' => false,
            'message' => 'QR TOKEN tidak ditemukan: ' . $code
        ]);
        exit;
    }

    $attendanceStmt = db()->prepare('SELECT * FROM attendance WHERE student_id = :student_id AND attendance_date = :attendance_date LIMIT 1');
    $attendanceStmt->execute([
        ':student_id' => $student['id'],
        ':attendance_date' => today_date(),
    ]);
    $attendance = $attendanceStmt->fetch();

    $now = now_time();
    $status = is_late_time($now) ? 'terlambat' : 'hadir';
    $action = '';
    $message = '';
    $notes = null;

    if ($mode === 'auto') {
        if (!$attendance) {
            $action = 'masuk';
            $insert = db()->prepare('INSERT INTO attendance (student_id, attendance_date, check_in, status, notes, created_at, updated_at) VALUES (:student_id, :attendance_date, :check_in, :status, :notes, :created_at, :updated_at)');
            $insert->execute([
                ':student_id' => $student['id'],
                ':attendance_date' => today_date(),
                ':check_in' => $now,
                ':status' => $status,
                ':notes' => null,
                ':created_at' => now_datetime(),
                ':updated_at' => now_datetime(),
            ]);
            $message = 'Absensi masuk berhasil dicatat.';
        } elseif (!$attendance['check_out']) {
            $action = 'pulang';
            $update = db()->prepare('UPDATE attendance SET check_out = :check_out, updated_at = :updated_at WHERE id = :id');
            $update->execute([
                ':check_out' => $now,
                ':updated_at' => now_datetime(),
                ':id' => $attendance['id'],
            ]);
            $message = 'Absensi pulang berhasil dicatat.';
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Siswa ini sudah scan masuk dan pulang hari ini.',
                'student' => [
                    'name' => $student['full_name'],
                    'nisn' => $student['nisn'],
                ],
            ]);
            exit;
        }
    }

    if ($mode === 'masuk') {
        if ($attendance && $attendance['check_in']) {
            echo json_encode(['success' => false, 'message' => 'Absen masuk siswa ini sudah tercatat hari ini.']);
            exit;
        }

        if ($attendance) {
            $update = db()->prepare('UPDATE attendance SET check_in = :check_in, status = :status, updated_at = :updated_at WHERE id = :id');
            $update->execute([
                ':check_in' => $now,
                ':status' => $status,
                ':updated_at' => now_datetime(),
                ':id' => $attendance['id'],
            ]);
        } else {
            $insert = db()->prepare('INSERT INTO attendance (student_id, attendance_date, check_in, status, notes, created_at, updated_at) VALUES (:student_id, :attendance_date, :check_in, :status, :notes, :created_at, :updated_at)');
            $insert->execute([
                ':student_id' => $student['id'],
                ':attendance_date' => today_date(),
                ':check_in' => $now,
                ':status' => $status,
                ':notes' => null,
                ':created_at' => now_datetime(),
                ':updated_at' => now_datetime(),
            ]);
        }
        $action = 'masuk';
        $message = 'Absensi masuk berhasil dicatat.';
    }

    if ($mode === 'pulang') {
        if ($attendance && $attendance['check_out']) {
            echo json_encode(['success' => false, 'message' => 'Absen pulang siswa ini sudah tercatat hari ini.']);
            exit;
        }

        if ($attendance) {
            $update = db()->prepare('UPDATE attendance SET check_out = :check_out, updated_at = :updated_at WHERE id = :id');
            $update->execute([
                ':check_out' => $now,
                ':updated_at' => now_datetime(),
                ':id' => $attendance['id'],
            ]);
        } else {
            $notes = 'Scan pulang tanpa scan masuk.';
            $insert = db()->prepare('INSERT INTO attendance (student_id, attendance_date, check_out, status, notes, created_at, updated_at) VALUES (:student_id, :attendance_date, :check_out, :status, :notes, :created_at, :updated_at)');
            $insert->execute([
                ':student_id' => $student['id'],
                ':attendance_date' => today_date(),
                ':check_out' => $now,
                ':status' => 'hadir',
                ':notes' => $notes,
                ':created_at' => now_datetime(),
                ':updated_at' => now_datetime(),
            ]);
        }
        $action = 'pulang';
        $message = 'Absensi pulang berhasil dicatat.';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'action' => $action,
        'time' => $now,
        'status' => $action === 'masuk' ? $status : ($attendance['status'] ?? $status),
        'notes' => $notes,
        'student' => [
            'id' => (int) $student['id'],
            'name' => $student['full_name'],
            'nisn' => $student['nisn'],
            'photo' => $student['photo_path'],
            'class' => trim($student['level'] . ' ' . $student['department'] . ' ' . $student['class_name']),
        ],
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server: ' . $exception->getMessage(),
    ]);
}