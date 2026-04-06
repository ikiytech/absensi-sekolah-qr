<?php
require_once __DIR__ . '/config/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $settings = school_settings();
    $logoPath = $settings['logo_path'] ?? null;
    if (!empty($_FILES['logo']['name'])) {
        $newLogo = upload_file($_FILES['logo'], 'settings', 'logo');
        if ($newLogo) {
            if ($logoPath) {
                delete_file_if_exists($logoPath);
            }
            $logoPath = $newLogo;
        }
    }

    $stmt = db()->prepare('UPDATE school_settings SET school_name = :school_name, school_address = :school_address, school_phone = :school_phone, principal_name = :principal_name, principal_nip = :principal_nip, attendance_start_time = :attendance_start_time, late_after = :late_after, attendance_end_time = :attendance_end_time, scan_mode = :scan_mode, document_city = :document_city, footer_note = :footer_note, logo_path = :logo_path, updated_at = :updated_at WHERE id = 1');
    $stmt->execute([
        ':school_name' => trim($_POST['school_name'] ?? ''),
        ':school_address' => trim($_POST['school_address'] ?? ''),
        ':school_phone' => trim($_POST['school_phone'] ?? ''),
        ':principal_name' => trim($_POST['principal_name'] ?? ''),
        ':principal_nip' => trim($_POST['principal_nip'] ?? ''),
        ':attendance_start_time' => trim($_POST['attendance_start_time'] ?? '07:00:00'),
        ':late_after' => trim($_POST['late_after'] ?? '07:15:00'),
        ':attendance_end_time' => trim($_POST['attendance_end_time'] ?? '15:00:00'),
        ':scan_mode' => trim($_POST['scan_mode'] ?? 'auto'),
        ':document_city' => trim($_POST['document_city'] ?? ''),
        ':footer_note' => trim($_POST['footer_note'] ?? ''),
        ':logo_path' => $logoPath,
        ':updated_at' => now_datetime(),
    ]);

    set_flash('success', 'Pengaturan sekolah berhasil diperbarui.');
    redirect('settings.php');
}

$pageTitle = 'Pengaturan Sekolah';
$settings = school_settings();
require_once __DIR__ . '/partials/header.php';
?>
<div class="panel-card">
    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-lg-8">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field(); ?>
                    <div class="col-12">
                        <label class="form-label">Nama Sekolah</label>
                        <input type="text" name="school_name" class="form-control form-control-lg" value="<?= e($settings['school_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Alamat Sekolah</label>
                        <textarea name="school_address" class="form-control" rows="3"><?= e($settings['school_address'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="school_phone" class="form-control" value="<?= e($settings['school_phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Kepala Sekolah</label>
                        <input type="text" name="principal_name" class="form-control" value="<?= e($settings['principal_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">NIP Kepala Sekolah</label>
                        <input type="text" name="principal_nip" class="form-control" value="<?= e($settings['principal_nip'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jam Masuk</label>
                        <input type="time" name="attendance_start_time" class="form-control" value="<?= e($settings['attendance_start_time'] ?? '07:00:00'); ?>" step="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Batas Terlambat</label>
                        <input type="time" name="late_after" class="form-control" value="<?= e($settings['late_after'] ?? '07:15:00'); ?>" step="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jam Pulang</label>
                        <input type="time" name="attendance_end_time" class="form-control" value="<?= e($settings['attendance_end_time'] ?? '15:00:00'); ?>" step="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mode Scan Default</label>
                        <select name="scan_mode" class="form-select">
                            <option value="auto" <?= selected((string) ($settings['scan_mode'] ?? 'auto'), 'auto'); ?>>Auto</option>
                            <option value="masuk" <?= selected((string) ($settings['scan_mode'] ?? 'auto'), 'masuk'); ?>>Masuk</option>
                            <option value="pulang" <?= selected((string) ($settings['scan_mode'] ?? 'auto'), 'pulang'); ?>>Pulang</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kota Dokumen</label>
                        <input type="text" name="document_city" class="form-control" value="<?= e($settings['document_city'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Footer Kartu QR</label>
                        <input type="text" name="footer_note" class="form-control" value="<?= e($settings['footer_note'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Logo Sekolah</label>
                        <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary btn-lg">Simpan Pengaturan</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-4">
                <div class="qr-card-preview sticky-top" style="top:24px;">
                    <?php if (!empty($settings['logo_path'])): ?>
                        <img src="<?= e($settings['logo_path']); ?>" alt="Logo" style="width:90px;height:90px;object-fit:contain;margin-bottom:18px;">
                    <?php else: ?>
                        <div class="avatar-round d-flex align-items-center justify-content-center bg-light fs-2 fw-bold">L</div>
                    <?php endif; ?>
                    <h4 class="fw-bold mb-1"><?= e($settings['school_name'] ?? 'Nama Sekolah'); ?></h4>
                    <div class="text-muted"><?= e($settings['school_address'] ?? 'Alamat sekolah'); ?></div>
                    <div class="text-muted mt-2">Kepala Sekolah: <?= e($settings['principal_name'] ?? '-'); ?></div>
                    <div class="small text-muted mt-3">Preview ini akan dipakai pada halaman print laporan dan kartu.</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
