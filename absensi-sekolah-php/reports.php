<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();

$pageTitle = 'Rekap Laporan';
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

$totalRows = count($rows);
$totalLate = 0;
$totalReturned = 0;
foreach ($rows as $row) {
    if ($row['status'] === 'terlambat') {
        $totalLate++;
    }
    if (!empty($row['check_out'])) {
        $totalReturned++;
    }
}

require_once __DIR__ . '/partials/header.php';
?>
<div class="panel-card mb-4">
    <div class="card-body p-4">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Periode</label>
                <select name="period" id="periodSelect" class="form-select">
                    <option value="daily" <?= selected($period, 'daily'); ?>>Harian</option>
                    <option value="weekly" <?= selected($period, 'weekly'); ?>>Mingguan</option>
                    <option value="monthly" <?= selected($period, 'monthly'); ?>>Bulanan</option>
                    <option value="yearly" <?= selected($period, 'yearly'); ?>>Tahunan</option>
                </select>
            </div>
            <div class="col-md-2 period-field period-daily <?= $period === 'daily' ? '' : 'd-none'; ?>">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control" value="<?= e($dailyDate); ?>">
            </div>
            <div class="col-md-2 period-field period-weekly <?= $period === 'weekly' ? '' : 'd-none'; ?>">
                <label class="form-label">Minggu</label>
                <input type="week" name="week" class="form-control" value="<?= e($weekValue); ?>">
            </div>
            <div class="col-md-2 period-field period-monthly <?= $period === 'monthly' ? '' : 'd-none'; ?>">
                <label class="form-label">Bulan</label>
                <input type="month" name="month" class="form-control" value="<?= e($monthValue); ?>">
            </div>
            <div class="col-md-2 period-field period-yearly <?= $period === 'yearly' ? '' : 'd-none'; ?>">
                <label class="form-label">Tahun</label>
                <input type="number" name="year" class="form-control" value="<?= e((string) $yearValue); ?>" min="2020" max="2100">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kelas</label>
                <select name="class_id" class="form-select">
                    <option value="0">Semua kelas</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id']; ?>" <?= selected((string) $classId, (string) $class['id']); ?>><?= e(class_label($class)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i> Tampilkan</button>
                <a href="print_report.php?period=<?= e($period); ?>&date=<?= e($dailyDate); ?>&week=<?= e($weekValue); ?>&month=<?= e($monthValue); ?>&year=<?= e((string) $yearValue); ?>&class_id=<?= (int) $classId; ?>" target="_blank" class="btn btn-dark"><i class="bi bi-printer"></i> Print</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="text-muted">Total Data</div>
            <div class="display-6 fw-bold mt-2"><?= $totalRows; ?></div>
            <div class="text-muted mt-2">Periode <?= e($periodLabel); ?>: <?= format_date($startDate); ?> s.d. <?= format_date($endDate); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="text-muted">Terlambat</div>
            <div class="display-6 fw-bold mt-2"><?= $totalLate; ?></div>
            <div class="text-muted mt-2">Jumlah absen dengan status terlambat.</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="text-muted">Sudah Pulang</div>
            <div class="display-6 fw-bold mt-2"><?= $totalReturned; ?></div>
            <div class="text-muted mt-2">Data yang sudah memiliki jam pulang.</div>
        </div>
    </div>
</div>

<div class="panel-card table-card">
    <div class="card-header p-4">
        <h5 class="fw-bold mb-1">Laporan Detail Kehadiran <?= e($periodLabel); ?></h5>
        <div class="text-muted small">Periode: <?= format_date($startDate, 'd F Y'); ?> - <?= format_date($endDate, 'd F Y'); ?></div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
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
                    <tr><td colspan="10" class="text-center py-5 text-muted">Tidak ada data untuk filter ini.</td></tr>
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
                        <td><?= status_badge($row['status']); ?></td>
                        <td><?= e($row['notes'] ?: '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const select = document.getElementById('periodSelect');
    function toggleFields() {
        document.querySelectorAll('.period-field').forEach((item) => item.classList.add('d-none'));
        const target = document.querySelector('.period-' + select.value);
        if (target) {
            target.classList.remove('d-none');
        }
    }
    select.addEventListener('change', toggleFields);
    toggleFields();
})();
</script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
