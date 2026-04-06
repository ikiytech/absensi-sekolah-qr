<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT sp.*, s.full_name, s.nisn, c.level, c.department, c.class_name
    FROM sp_letters sp
    JOIN students s ON s.id = sp.student_id
    JOIN classes c ON c.id = s.class_id
    WHERE sp.id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$letter = $stmt->fetch();
if (!$letter) {
    exit('Surat SP tidak ditemukan.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Surat SP</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body{font-family:Inter,Arial,sans-serif;background:#fff;padding:32px;line-height:1.7;color:#111}
        .doc{max-width:900px;margin:0 auto}
        .center{text-align:center}
    </style>
</head>
<body>
<div class="doc">
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

    <div class="center" style="margin-top:10px;">
        <div style="font-size:22px;font-weight:800;">SURAT PERINGATAN <?= e($letter['sp_level']); ?></div>
        <div>Nomor: <?= e($letter['letter_no']); ?></div>
    </div>

    <p>Pada hari ini, <?= format_date($letter['letter_date'], 'd F Y'); ?>, pihak sekolah memberikan surat peringatan kepada siswa berikut:</p>

    <table style="width:100%;border-collapse:collapse;margin:14px 0;">
        <tr><td style="width:180px;padding:6px 0;">Nama Siswa</td><td>: <?= e($letter['full_name']); ?></td></tr>
        <tr><td style="padding:6px 0;">NISN</td><td>: <?= e($letter['nisn']); ?></td></tr>
        <tr><td style="padding:6px 0;">Kelas</td><td>: <?= e(trim($letter['level'] . ' ' . $letter['department'] . ' ' . $letter['class_name'])); ?></td></tr>
        <tr><td style="padding:6px 0;">Jenis Surat</td><td>: <?= e($letter['sp_level']); ?></td></tr>
    </table>

    <p>Adapun alasan penerbitan surat peringatan ini adalah sebagai berikut:</p>
    <div style="border:1px solid #111;padding:16px;border-radius:12px;min-height:140px;">
        <?= nl2br(e($letter['reason'])); ?>
    </div>

    <p style="margin-top:20px;">Demikian surat ini dibuat untuk menjadi perhatian dan tindak lanjut bersama antara siswa, orang tua/wali, dan pihak sekolah.</p>

    <div class="signature-grid">
        <div class="signature-box">
            Mengetahui,<br>
            Kepala Sekolah
            <div style="height:80px;"></div>
            <strong><?= e(setting_value('principal_name', 'Nama Kepala Sekolah')); ?></strong><br>
            NIP. <?= e(setting_value('principal_nip', '-')); ?>
        </div>
        <div class="signature-box" style="text-align:right;">
            <?= e(setting_value('document_city', 'Kota Anda')); ?>, <?= format_date($letter['letter_date'], 'd F Y'); ?><br>
            Bagian Kesiswaan / Admin
            <div style="height:80px;"></div>
            <strong><?= e(current_user()['full_name'] ?? 'Administrator'); ?></strong>
        </div>
    </div>
</div>
<script>window.onload=function(){window.print();};</script>
</body>
</html>
