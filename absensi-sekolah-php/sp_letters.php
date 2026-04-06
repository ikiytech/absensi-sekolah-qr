<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $stmt = db()->prepare('DELETE FROM sp_letters WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['id']]);
    set_flash('success', 'Surat SP berhasil dihapus.');
    redirect('sp_letters.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $letterNo = trim($_POST['letter_no'] ?? '');
    $spLevel = trim($_POST['sp_level'] ?? 'SP1');
    $letterDate = trim($_POST['letter_date'] ?? today_date());
    $reason = trim($_POST['reason'] ?? '');

    if ($studentId <= 0 || $letterNo === '' || $reason === '') {
        set_flash('danger', 'Siswa, nomor surat, dan alasan wajib diisi.');
        redirect('sp_letters.php');
    }

    $stmt = db()->prepare('INSERT INTO sp_letters (student_id, letter_no, sp_level, letter_date, reason, created_by, created_at) VALUES (:student_id, :letter_no, :sp_level, :letter_date, :reason, :created_by, :created_at)');
    $stmt->execute([
        ':student_id' => $studentId,
        ':letter_no' => $letterNo,
        ':sp_level' => $spLevel,
        ':letter_date' => $letterDate,
        ':reason' => $reason,
        ':created_by' => current_user()['id'] ?? null,
        ':created_at' => now_datetime(),
    ]);

    set_flash('success', 'Surat SP berhasil dibuat.');
    redirect('sp_letters.php');
}

$pageTitle = 'Surat SP';
$students = db()->query('SELECT s.id, s.full_name, s.nisn, c.level, c.department, c.class_name
    FROM students s
    JOIN classes c ON c.id = s.class_id
    ORDER BY s.full_name ASC')->fetchAll();
$letters = db()->query('SELECT sp.*, s.full_name, s.nisn, c.level, c.department, c.class_name
    FROM sp_letters sp
    JOIN students s ON s.id = sp.student_id
    JOIN classes c ON c.id = s.class_id
    ORDER BY sp.letter_date DESC, sp.id DESC')->fetchAll();
require_once __DIR__ . '/partials/header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="panel-card h-100">
            <div class="card-header p-4">
                <h5 class="fw-bold mb-1">Buat Surat Peringatan</h5>
                <div class="text-muted small">Gunakan untuk SP1, SP2, atau SP3.</div>
            </div>
            <div class="card-body p-4">
                <form method="post" class="row g-3">
                    <?= csrf_field(); ?>
                    <div class="col-12">
                        <label class="form-label">Pilih Siswa</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Pilih siswa</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= (int) $student['id']; ?>"><?= e($student['full_name'] . ' - ' . $student['nisn'] . ' - ' . class_label($student)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor Surat</label>
                        <input type="text" name="letter_no" class="form-control" placeholder="421/SP/SMK/2026" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Level SP</label>
                        <select name="sp_level" class="form-select">
                            <option value="SP1">SP1</option>
                            <option value="SP2">SP2</option>
                            <option value="SP3">SP3</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tanggal Surat</label>
                        <input type="date" name="letter_date" class="form-control" value="<?= e(today_date()); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alasan / Isi Teguran</label>
                        <textarea name="reason" class="form-control" rows="6" placeholder="Tuliskan alasan pembuatan surat SP..." required></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary btn-lg">Simpan Surat SP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel-card table-card">
            <div class="card-header p-4">
                <h5 class="fw-bold mb-1">Daftar Surat SP</h5>
                <div class="text-muted small">Riwayat surat yang sudah dibuat.</div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Surat</th>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Level</th>
                            <th>Tanggal</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$letters): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada surat SP.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($letters as $index => $letter): ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td><?= e($letter['letter_no']); ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($letter['full_name']); ?></div>
                                    <small class="text-muted">NISN: <?= e($letter['nisn']); ?></small>
                                </td>
                                <td><?= e(trim($letter['level'] . ' ' . $letter['department'] . ' ' . $letter['class_name'])); ?></td>
                                <td><span class="badge text-bg-warning"><?= e($letter['sp_level']); ?></span></td>
                                <td><?= format_date($letter['letter_date'], 'd F Y'); ?></td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="print_sp.php?id=<?= (int) $letter['id']; ?>" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-printer"></i></a>
                                        <a href="sp_letters.php?action=delete&id=<?= (int) $letter['id']; ?>" onclick="return confirm('Hapus surat ini?');" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
