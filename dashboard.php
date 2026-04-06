<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/partials/header.php';

$totalStudents = get_count('students');
$totalClasses = get_count('classes');
$totalAttendanceToday = get_count('attendance', 'attendance_date = :date', [':date' => today_date()]);
$totalLateToday = get_count('attendance', 'attendance_date = :date AND status = :status', [':date' => today_date(), ':status' => 'terlambat']);
$totalPendingOperators = get_count('users', 'status = :status AND role = :role', [':status' => 'pending', ':role' => 'operator']);

$recentAttendanceStmt = db()->prepare('SELECT a.*, s.full_name, s.nisn, s.photo_path, c.level, c.department, c.class_name
    FROM attendance a
    JOIN students s ON s.id = a.student_id
    JOIN classes c ON c.id = s.class_id
    ORDER BY a.updated_at DESC, a.created_at DESC
    LIMIT 8');
$recentAttendanceStmt->execute();
$recentAttendance = $recentAttendanceStmt->fetchAll();

$classSummary = db()->query('SELECT c.id, c.level, c.department, c.class_name, COUNT(s.id) AS total_students
    FROM classes c
    LEFT JOIN students s ON s.class_id = c.id
    GROUP BY c.id, c.level, c.department, c.class_name
    ORDER BY total_students DESC, c.level, c.department
    LIMIT 6')->fetchAll();
?>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted">Total Siswa</div>
                    <div class="display-6 fw-bold mt-2"><?= $totalStudents; ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-people"></i></div>
            </div>
            <div class="text-muted mt-3">Data master siswa aktif di sistem.</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted">Data Kelas</div>
                    <div class="display-6 fw-bold mt-2"><?= $totalClasses; ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-diagram-3"></i></div>
            </div>
            <div class="text-muted mt-3">Kelas, jurusan, dan wali kelas.</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted">Absen Hari Ini</div>
                    <div class="display-6 fw-bold mt-2"><?= $totalAttendanceToday; ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            </div>
            <div class="text-muted mt-3">Jumlah siswa yang sudah scan hari ini.</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted">Terlambat Hari Ini</div>
                    <div class="display-6 fw-bold mt-2"><?= $totalLateToday; ?></div>
                </div>
                <div class="stat-icon"><i class="bi bi-alarm"></i></div>
            </div>
            <div class="text-muted mt-3">Operator pending: <?= $totalPendingOperators; ?> akun.</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel-card table-card">
            <div class="card-header p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1">Riwayat Absensi Terbaru</h5>
                    <div class="text-muted small">Update scan masuk dan pulang siswa terbaru.</div>
                </div>
                <a href="live.php" class="btn btn-soft">Lihat Live List</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Tanggal</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$recentAttendance): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data absensi.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentAttendance as $row): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($row['photo_path'])): ?>
                                            <img src="<?= e($row['photo_path']); ?>" class="student-photo" alt="<?= e($row['full_name']); ?>">
                                        <?php else: ?>
                                            <div class="photo-placeholder"><?= e(strtoupper(substr($row['full_name'], 0, 1))); ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-semibold"><?= e($row['full_name']); ?></div>
                                            <small class="text-muted">NISN: <?= e($row['nisn']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e(trim($row['level'] . ' ' . $row['department'] . ' ' . $row['class_name'])); ?></td>
                                <td><?= format_date($row['attendance_date']); ?></td>
                                <td><?= e($row['check_in'] ?: '-'); ?></td>
                                <td><?= e($row['check_out'] ?: '-'); ?></td>
                                <td><?= status_badge($row['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel-card h-100">
            <div class="card-header p-4">
                <h5 class="fw-bold mb-1">Ringkasan Kelas</h5>
                <div class="text-muted small">Jumlah siswa per kelas.</div>
            </div>
            <div class="card-body p-4">
                <?php if (!$classSummary): ?>
                    <div class="text-muted">Belum ada data kelas.</div>
                <?php endif; ?>
                <div class="vstack gap-3">
                    <?php foreach ($classSummary as $class): ?>
                        <div class="live-item">
                            <div class="fw-semibold"><?= e(trim($class['level'] . ' ' . $class['department'] . ' ' . $class['class_name'])); ?></div>
                            <div class="text-muted small mt-1"><?= (int) $class['total_students']; ?> siswa</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 d-grid gap-2">
                    <a href="students.php" class="btn btn-primary">Kelola Data Siswa</a>
                    <a href="scanner.php" class="btn btn-soft">Buka Scanner</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
