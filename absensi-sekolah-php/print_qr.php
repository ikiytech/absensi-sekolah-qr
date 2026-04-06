<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();

$classId = (int) ($_GET['class_id'] ?? 0);
$classes = get_classes();
$sql = 'SELECT s.*, c.level, c.department, c.class_name FROM students s JOIN classes c ON c.id = s.class_id WHERE s.is_active = 1';
$params = [];
if ($classId > 0) {
    $sql .= ' AND s.class_id = :class_id';
    $params[':class_id'] = $classId;
}
$sql .= ' ORDER BY c.level, c.department, c.class_name, s.full_name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Massal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        body{font-family:Inter,Arial,sans-serif;background:#fff;padding:24px}
        .top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
        .school{font-weight:800;font-size:24px}
        .print-card .avatar-round.placeholder{display:flex;align-items:center;justify-content:center;background:#f1f3f9;color:#70798c;font-size:28px;font-weight:700}
    </style>
</head>
<body>
    <div class="top no-print">
        <div>
            <div class="school"><?= e(setting_value('school_name', 'Sekolah')); ?></div>
            <div class="text-muted">Cetak QR massal untuk seluruh siswa aktif.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="students.php" class="btn btn-soft">Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">Print Sekarang</button>
        </div>
    </div>

    <div class="print-grid">
        <?php if (!$students): ?>
            <div>Tidak ada data siswa untuk dicetak.</div>
        <?php endif; ?>
        <?php foreach ($students as $student): ?>
            <?php $classLabel = trim($student['level'] . ' ' . $student['department'] . ' ' . $student['class_name']); ?>
            <div class="print-card">
                <?php if (!empty($student['photo_path'])): ?>
                    <img src="<?= e($student['photo_path']); ?>" class="avatar-round" alt="<?= e($student['full_name']); ?>">
                <?php else: ?>
                    <div class="avatar-round placeholder"><?= e(strtoupper(substr($student['full_name'], 0, 1))); ?></div>
                <?php endif; ?>
                <div class="student-name"><?= e($student['full_name']); ?></div>
                <div class="meta">
                    NISN: <?= e($student['nisn']); ?><br>
                    Kelas: <?= e($classLabel); ?>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    <div class="qr-box"><div class="qr-item" data-token="<?= e($student['qr_token']); ?>"></div></div>
                </div>
                <div class="small text-muted mt-3"><?= e(setting_value('footer_note', 'Gunakan untuk absensi.')); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.qr-item').forEach(function (item) {
        new QRCode(item, {
            text: item.dataset.token,
            width: 170,
            height: 170,
            correctLevel: QRCode.CorrectLevel.H
        });
    });
});
</script>
</body>
</html>
