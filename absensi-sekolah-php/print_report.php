<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();

$classes = get_classes();
$period = $_GET['period'] ?? 'daily';
$classId = (int) ($_GET['class_id'] ?? 0);
$today = new DateTimeImmutable(today_date());
$dailyDate = $_GET['date'] ?? $today->format('Y-m-d');
$weekValue = $_GET['week'] ?? $today->format('o-\WW');
$monthValue = $_GET['month'] ?? $today->format('Y-m');
$yearValue = (int) ($_GET['year'] ?? $today->format('Y'));

$startDate = $today->format('Y-m-d');
$endDate = $today->format('Y-m-d');
$periodLabel = 'Harian';
if ($period === 'daily') {
    $startDate = $dailyDate;
    $endDate = $dailyDate;
    $periodLabel = 'Harian';
} elseif ($period === 'weekly') {
    [$yearPart, $weekPart] = explode('-W', $weekValue . '-W');
    $yearPart = (int) $yearPart;
    $weekPart = (int) $weekPart;
    $monday = (new DateTimeImmutable())->setISODate($yearPart ?: (int) date('o'), $weekPart ?: (int) date('W'));
    $sunday = $monday->modify('+6 days');
    $startDate = $monday->format('Y-m-d');
    $endDate = $sunday->format('Y-m-d');
    $periodLabel = 'Mingguan';
} elseif ($period === 'monthly') {
    $startDate = date('Y-m-01', strtotime($monthValue . '-01'));
    $endDate = date('Y-m-t', strtotime($monthValue . '-01'));
    $periodLabel = 'Bulanan';
} elseif ($period === 'yearly') {
    $startDate = $yearValue . '-01-01';
    $endDate = $yearValue . '-12-31';
    $periodLabel = 'Tahunan';
}

$sql = 'SELECT a.*, s.full_name, s.nisn, c.level, c.department, c.class_name, c.homeroom_teacher
        FROM attendance a
        JOIN students s ON s.id = a.student_id
        JOIN classes c ON c.id = s.class_id
        WHERE a.attendance_date BETWEEN :start_date AND :end_date';
$params = [
    ':start_date' => $startDate,
    ':end_date' => $endDate,
];
if ($classId > 0) {
    $sql .= ' AND c.id = :class_id';
    $params[':class_id'] = $classId;
}
$sql .= ' ORDER BY a.attendance_date DESC, s.full_name ASC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Laporan Absensi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,Arial,sans-serif;background:#fff;padding:24px;color:#111}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #111;padding:8px;font-size:12px}
        th{background:#f4f4f4}
    </style>
</head>
<body>
    <div class="report-letterhead">
        <?php if (app_logo()): ?>
            <img src="<?= e(app_logo()); ?>" class="report-logo" alt="Logo Sekolah">
        <?php endif; ?>
        <div>
            <div style="font-size:26px;font-weight:800;"><?= e(setting_value('school_name', 'Nama Sekolah')); ?></div>
            <div><?= e(setting_value('school_address', 'Alamat sekolah')); ?></div>
            <div>Telp: <?= e(setting_value('school_phone', '-')); ?></div>
        </div>
    </div>

    <h3 style="text-align:center;margin:12px 0 4px;">LAPORAN DETAIL RIWAYAT KEHADIRAN <?= strtoupper(e($periodLabel)); ?></h3>
    <div style="text-align:center;margin-bottom:14px;">Periode: <?= format_date($startDate, 'd F Y'); ?> s.d. <?= format_date($endDate, 'd F Y'); ?></div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Wali Kelas</th>
                <th>Masuk</th>
                <th>Pulang</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="10" style="text-align:center;padding:24px;">Tidak ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td><?= $index + 1; ?></td>
                    <td><?= format_date($row['attendance_date'], 'd F Y'); ?></td>
                    <td><?= e($row['nisn']); ?></td>
                    <td><?= e($row['full_name']); ?></td>
                    <td><?= e(trim($row['level'] . ' ' . $row['department'] . ' ' . $row['class_name'])); ?></td>
                    <td><?= e($row['homeroom_teacher']); ?></td>
                    <td><?= e($row['check_in'] ?: '-'); ?></td>
                    <td><?= e($row['check_out'] ?: '-'); ?></td>
                    <td><?= e(ucfirst($row['status'])); ?></td>
                    <td><?= e($row['notes'] ?: '-'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="signature-grid">
        <div class="signature-box">
            Mengetahui,<br>
            Kepala Sekolah
            <div style="height:80px;"></div>
            <strong><?= e(setting_value('principal_name', 'Nama Kepala Sekolah')); ?></strong><br>
            NIP. <?= e(setting_value('principal_nip', '-')); ?>
        </div>
        <div class="signature-box" style="text-align:right;">
            <?= e(setting_value('document_city', 'Kota Anda')); ?>, <?= format_date(today_date(), 'd F Y'); ?><br>
            Admin Presensi
            <div style="height:80px;"></div>
            <strong><?= e($user['full_name'] ?? 'Administrator'); ?></strong>
        </div>
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>
