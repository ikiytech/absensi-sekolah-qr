<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $id = (int) $_GET['id'];
    $check = get_count('students', 'class_id = :class_id', [':class_id' => $id]);
    if ($check > 0) {
        set_flash('warning', 'Kelas tidak bisa dihapus karena masih dipakai siswa.');
    } else {
        $delete = db()->prepare('DELETE FROM classes WHERE id = :id');
        $delete->execute([':id' => $id]);
        set_flash('success', 'Kelas berhasil dihapus.');
    }
    redirect('classes.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $level = trim($_POST['level'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $className = trim($_POST['class_name'] ?? '');
    $teacher = trim($_POST['homeroom_teacher'] ?? '');
    $phone = trim($_POST['homeroom_phone'] ?? '');

    if ($level === '' || $department === '' || $className === '') {
        set_flash('danger', 'Level, jurusan, dan nama kelas wajib diisi.');
        redirect('classes.php' . ($id ? '?edit=' . $id : ''));
    }

    if ($id > 0) {
        $stmt = db()->prepare('UPDATE classes SET level = :level, department = :department, class_name = :class_name, homeroom_teacher = :teacher, homeroom_phone = :phone, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':level' => $level,
            ':department' => $department,
            ':class_name' => $className,
            ':teacher' => $teacher,
            ':phone' => $phone,
            ':updated_at' => now_datetime(),
            ':id' => $id,
        ]);
        set_flash('success', 'Kelas berhasil diperbarui.');
    } else {
        $stmt = db()->prepare('INSERT INTO classes (level, department, class_name, homeroom_teacher, homeroom_phone, created_at) VALUES (:level, :department, :class_name, :teacher, :phone, :created_at)');
        $stmt->execute([
            ':level' => $level,
            ':department' => $department,
            ':class_name' => $className,
            ':teacher' => $teacher,
            ':phone' => $phone,
            ':created_at' => now_datetime(),
        ]);
        set_flash('success', 'Kelas baru berhasil ditambahkan.');
    }

    redirect('classes.php');
}

$pageTitle = 'Master Data Kelas';
$classes = db()->query('SELECT c.*, COUNT(s.id) AS total_students
    FROM classes c
    LEFT JOIN students s ON s.class_id = c.id
    GROUP BY c.id
    ORDER BY c.level, c.department, c.class_name')->fetchAll();

$editClass = null;
if (!empty($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM classes WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $editClass = $stmt->fetch() ?: null;
}

require_once __DIR__ . '/partials/header.php';
?>
<div class="panel-card table-card">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold mb-1">Data Kelas Sekolah</h5>
                <div class="text-muted">Kelola level, jurusan, nama kelas lengkap, dan wali kelas.</div>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#classModal"><i class="bi bi-plus-lg"></i> Tambah Kelas</button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th>Nama Lengkap</th>
                        <th>Wali Kelas</th>
                        <th>No. WA</th>
                        <th>Total Siswa</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$classes): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada data kelas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($classes as $index => $class): ?>
                        <tr>
                            <td><?= $index + 1; ?></td>
                            <td><span class="badge text-bg-light"><?= e($class['level']); ?></span></td>
                            <td><?= e($class['department']); ?></td>
                            <td class="fw-semibold"><?= e(class_label($class)); ?></td>
                            <td><?= e($class['homeroom_teacher']); ?></td>
                            <td><?= e($class['homeroom_phone']); ?></td>
                            <td><?= (int) $class['total_students']; ?> siswa</td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="classes.php?edit=<?= (int) $class['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square"></i></a>
                                    <a href="classes.php?action=delete&id=<?= (int) $class['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kelas ini?');"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="classModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5">
            <form method="post">
                <?= csrf_field(); ?>
                <input type="hidden" name="id" value="<?= (int) ($editClass['id'] ?? 0); ?>">
                <div class="modal-header border-0 px-4 pt-4">
                    <div>
                        <h4 class="modal-title fw-bold"><?= $editClass ? 'Edit Kelas' : 'Tambah Kelas'; ?></h4>
                        <div class="text-muted small">Simpan detail kelas dan wali kelas untuk laporan.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-2">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Kelas</label>
                            <select name="level" class="form-select" required>
                                <option value="">Pilih</option>
                                <?php foreach (['X', 'XI', 'XII'] as $level): ?>
                                    <option value="<?= e($level); ?>" <?= selected((string) ($editClass['level'] ?? ''), $level); ?>><?= e($level); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Jurusan / Program Studi</label>
                            <input type="text" name="department" class="form-control" placeholder="Contoh: RPL, TKJ" required value="<?= e($editClass['department'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nama Kelas Lengkap</label>
                            <input type="text" name="class_name" class="form-control" placeholder="Contoh: 2 atau A" required value="<?= e($editClass['class_name'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nama Wali Kelas</label>
                            <input type="text" name="homeroom_teacher" class="form-control" placeholder="Nama lengkap wali kelas" value="<?= e($editClass['homeroom_teacher'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">No. WA Wali Kelas</label>
                            <input type="text" name="homeroom_phone" class="form-control" placeholder="Contoh: 08123456789" value="<?= e($editClass['homeroom_phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
<?php if ($editClass): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('classModal')).show();
});
</script>
<?php endif; ?>
