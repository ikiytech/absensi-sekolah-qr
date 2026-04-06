<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $id = (int) $_GET['id'];
    $find = db()->prepare('SELECT photo_path FROM students WHERE id = :id LIMIT 1');
    $find->execute([':id' => $id]);
    $student = $find->fetch();
    if ($student) {
        $delete = db()->prepare('DELETE FROM students WHERE id = :id');
        $delete->execute([':id' => $id]);
        delete_file_if_exists($student['photo_path'] ?? null);
        set_flash('success', 'Data siswa berhasil dihapus.');
    }
    redirect('students.php');
}

if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'refresh') {
    $id = (int) $_GET['id'];
    $find = db()->prepare('SELECT nisn FROM students WHERE id = :id LIMIT 1');
    $find->execute([':id' => $id]);
    $student = $find->fetch();
    if ($student) {
        $update = db()->prepare('UPDATE students SET qr_token = :qr_token, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            ':qr_token' => generate_qr_token($student['nisn']),
            ':updated_at' => now_datetime(),
            ':id' => $id,
        ]);
        set_flash('success', 'QR token siswa berhasil diperbarui.');
    }
    redirect('students.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'save_student') {
        $id = (int) ($_POST['id'] ?? 0);
        $nisn = trim($_POST['nisn'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $classId = (int) ($_POST['class_id'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($nisn === '' || $fullName === '' || $classId <= 0) {
            set_flash('danger', 'NISN, nama siswa, dan kelas wajib diisi.');
            redirect('students.php' . ($id ? '?edit=' . $id : ''));
        }

        $checkSql = 'SELECT id FROM students WHERE nisn = :nisn' . ($id > 0 ? ' AND id != :id' : '');
        $check = db()->prepare($checkSql);
        $params = [':nisn' => $nisn];
        if ($id > 0) {
            $params[':id'] = $id;
        }
        $check->execute($params);
        if ($check->fetch()) {
            set_flash('warning', 'NISN sudah digunakan siswa lain.');
            redirect('students.php' . ($id ? '?edit=' . $id : ''));
        }

        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            $photoPath = upload_file($_FILES['photo'], 'students', 'student');
        }

        if ($id > 0) {
            $oldStmt = db()->prepare('SELECT photo_path, qr_token FROM students WHERE id = :id LIMIT 1');
            $oldStmt->execute([':id' => $id]);
            $old = $oldStmt->fetch();
            if (!$old) {
                set_flash('danger', 'Data siswa tidak ditemukan.');
                redirect('students.php');
            }

            $update = db()->prepare('UPDATE students SET nisn = :nisn, full_name = :full_name, class_id = :class_id, is_active = :is_active, photo_path = :photo_path, qr_token = :qr_token, updated_at = :updated_at WHERE id = :id');
            $update->execute([
                ':nisn' => $nisn,
                ':full_name' => $fullName,
                ':class_id' => $classId,
                ':is_active' => $isActive,
                ':photo_path' => $photoPath ?: ($old['photo_path'] ?? null),
                ':qr_token' => str_starts_with((string) $old['qr_token'], 'QR-' . $nisn . '-') ? $old['qr_token'] : generate_qr_token($nisn),
                ':updated_at' => now_datetime(),
                ':id' => $id,
            ]);
            if ($photoPath && !empty($old['photo_path'])) {
                delete_file_if_exists($old['photo_path']);
            }
            set_flash('success', 'Data siswa berhasil diperbarui.');
        } else {
            $insert = db()->prepare('INSERT INTO students (nisn, full_name, photo_path, class_id, qr_token, is_active, created_at) VALUES (:nisn, :full_name, :photo_path, :class_id, :qr_token, :is_active, :created_at)');
            $insert->execute([
                ':nisn' => $nisn,
                ':full_name' => $fullName,
                ':photo_path' => $photoPath,
                ':class_id' => $classId,
                ':qr_token' => generate_qr_token($nisn),
                ':is_active' => $isActive,
                ':created_at' => now_datetime(),
            ]);
            set_flash('success', 'Data siswa berhasil ditambahkan.');
        }

        redirect('students.php');
    }

    if ($formAction === 'import_students') {
        if (empty($_FILES['import_file']['name'])) {
            set_flash('warning', 'Silakan pilih file CSV atau XLSX.');
            redirect('students.php');
        }

        $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        $tmpPath = $_FILES['import_file']['tmp_name'];
        $rows = parse_csv_or_xlsx($tmpPath, $ext);

        if (count($rows) <= 1) {
            set_flash('warning', 'Data import kosong atau hanya berisi header.');
            redirect('students.php');
        }

        $imported = 0;
        $updated = 0;
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $nisn = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            $className = trim((string) ($row[2] ?? ''));

            if ($nisn === '' || $name === '' || $className === '') {
                continue;
            }

            $classId = get_or_create_class_by_import($className);
            $find = db()->prepare('SELECT id FROM students WHERE nisn = :nisn LIMIT 1');
            $find->execute([':nisn' => $nisn]);
            $existing = $find->fetchColumn();

            if ($existing) {
                $update = db()->prepare('UPDATE students SET full_name = :full_name, class_id = :class_id, is_active = 1, updated_at = :updated_at WHERE id = :id');
                $update->execute([
                    ':full_name' => $name,
                    ':class_id' => $classId,
                    ':updated_at' => now_datetime(),
                    ':id' => $existing,
                ]);
                $updated++;
            } else {
                $insert = db()->prepare('INSERT INTO students (nisn, full_name, class_id, qr_token, is_active, created_at) VALUES (:nisn, :full_name, :class_id, :qr_token, 1, :created_at)');
                $insert->execute([
                    ':nisn' => $nisn,
                    ':full_name' => $name,
                    ':class_id' => $classId,
                    ':qr_token' => generate_qr_token($nisn),
                    ':created_at' => now_datetime(),
                ]);
                $imported++;
            }
        }

        set_flash('success', 'Import selesai. Tambah baru: ' . $imported . ', diperbarui: ' . $updated . '.');
        redirect('students.php');
    }
}

$pageTitle = 'Master Data Siswa';
$search = trim($_GET['search'] ?? '');
$classFilter = (int) ($_GET['class_id'] ?? 0);
$classes = get_classes();

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(s.full_name LIKE :search OR s.nisn LIKE :search OR CONCAT(c.level, " ", c.department, " ", c.class_name) LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($classFilter > 0) {
    $where[] = 's.class_id = :class_id';
    $params[':class_id'] = $classFilter;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare('SELECT s.*, c.level, c.department, c.class_name
    FROM students s
    JOIN classes c ON c.id = s.class_id
    ' . $sqlWhere . '
    ORDER BY s.created_at DESC');
$stmt->execute($params);
$students = $stmt->fetchAll();

$editStudent = null;
if (!empty($_GET['edit'])) {
    $editStmt = db()->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
    $editStmt->execute([':id' => (int) $_GET['edit']]);
    $editStudent = $editStmt->fetch() ?: null;
}

$logStudent = null;
$logRows = [];
if (!empty($_GET['log'])) {
    $logStmt = db()->prepare('SELECT s.*, c.level, c.department, c.class_name FROM students s JOIN classes c ON c.id = s.class_id WHERE s.id = :id LIMIT 1');
    $logStmt->execute([':id' => (int) $_GET['log']]);
    $logStudent = $logStmt->fetch() ?: null;
    if ($logStudent) {
        $historyStmt = db()->prepare('SELECT * FROM attendance WHERE student_id = :student_id ORDER BY attendance_date DESC LIMIT 30');
        $historyStmt->execute([':student_id' => $logStudent['id']]);
        $logRows = $historyStmt->fetchAll();
    }
}

require_once __DIR__ . '/partials/header.php';
?>
<div class="panel-card mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold mb-1">Daftar Siswa Aktif</h5>
                <div class="text-muted">Kelola data murid, foto, pendaftaran, dan kartu QR siswa.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="operators.php" class="btn btn-soft"><i class="bi bi-person-check"></i> Belum Disetujui</a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal"><i class="bi bi-plus-lg"></i> Tambah Siswa</button>
                <button class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-file-earmark-excel"></i> Import Excel</button>
                <a href="print_qr.php<?= $classFilter > 0 ? '?class_id=' . $classFilter : ''; ?>" target="_blank" class="btn btn-dark"><i class="bi bi-printer"></i> Cetak QR Massal</a>
            </div>
        </div>

        <form method="get" class="filter-grid mb-3">
            <input type="text" class="form-control" name="search" placeholder="Cari nama atau NISN..." value="<?= e($search); ?>">
            <select name="class_id" class="form-select">
                <option value="0">Semua kelas</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= (int) $class['id']; ?>" <?= selected((string) $classFilter, (string) $class['id']); ?>><?= e(class_label($class)); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
            <a href="students.php" class="btn btn-outline-secondary">Reset</a>
        </form>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>NISN</th>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th>QR Token</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$students): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada data siswa.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($students as $index => $student): ?>
                        <?php $classLabel = trim($student['level'] . ' ' . $student['department'] . ' ' . $student['class_name']); ?>
                        <tr>
                            <td><?= $index + 1; ?></td>
                            <td>
                                <?php if ($student['photo_path']): ?>
                                    <img src="<?= e($student['photo_path']); ?>" class="student-photo" alt="<?= e($student['full_name']); ?>">
                                <?php else: ?>
                                    <div class="photo-placeholder"><?= e(strtoupper(substr($student['full_name'], 0, 1))); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold"><?= e($student['nisn']); ?></td>
                            <td><?= e($student['full_name']); ?></td>
                            <td><span class="badge rounded-pill text-bg-light"><?= e($classLabel); ?></span></td>
                            <td><small class="text-muted"><?= e($student['qr_token']); ?></small></td>
                            <td><?= $student['is_active'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>'; ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-success" data-qr-modal data-name="<?= e($student['full_name']); ?>" data-nisn="<?= e($student['nisn']); ?>" data-class="<?= e($classLabel); ?>" data-token="<?= e($student['qr_token']); ?>" data-photo="<?= e($student['photo_path'] ?? ''); ?>" data-footer="<?= e(setting_value('footer_note', 'Gunakan QR ini pada mesin scanner sekolah.')); ?>"><i class="bi bi-card-image"></i></button>
                                    <a href="students.php?edit=<?= (int) $student['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square"></i></a>
                                    <a href="students.php?action=refresh&id=<?= (int) $student['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Refresh QR token siswa ini?');"><i class="bi bi-arrow-repeat"></i></a>
                                    <a href="students.php?log=<?= (int) $student['id']; ?>" class="btn btn-sm btn-secondary"><i class="bi bi-clock-history"></i></a>
                                    <a href="students.php?action=delete&id=<?= (int) $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus siswa ini?');"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5">
            <form method="post" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="form_action" value="save_student">
                <input type="hidden" name="id" value="<?= (int) ($editStudent['id'] ?? 0); ?>">
                <div class="modal-header border-0 px-4 pt-4">
                    <div>
                        <h4 class="modal-title fw-bold"><?= $editStudent ? 'Edit Siswa' : 'Tambah Siswa'; ?></h4>
                        <div class="text-muted small">Lengkapi detail murid dan foto profil untuk kartu QR.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-2">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NISN</label>
                            <input type="text" name="nisn" class="form-control form-control-lg" required value="<?= e($editStudent['nisn'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kelas</label>
                            <select name="class_id" class="form-select form-select-lg" required>
                                <option value="">Pilih kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= (int) $class['id']; ?>" <?= selected((string) ($editStudent['class_id'] ?? ''), (string) $class['id']); ?>><?= e(class_label($class)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="full_name" class="form-control form-control-lg" required value="<?= e($editStudent['full_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Foto Siswa</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif">
                            <div class="small text-muted mt-1">Kosongkan jika tidak ingin mengganti foto.</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= checked((string) ($editStudent['is_active'] ?? '1'), '1'); ?>>
                                <label class="form-check-label" for="is_active">Siswa aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary"><?= $editStudent ? 'Update Siswa' : 'Simpan Siswa'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5">
            <form method="post" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="form_action" value="import_students">
                <div class="modal-header border-0 px-4 pt-4">
                    <div>
                        <h4 class="modal-title fw-bold">Import Data Excel</h4>
                        <div class="text-muted small">Format kolom: NISN, NAMA, KELAS. Bisa CSV atau XLSX.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-2">
                    <div class="alert alert-light border rounded-4">
                        <div class="fw-semibold mb-2">Template sederhana:</div>
                        <code>NISN,NAMA,KELAS</code><br>
                        <code>34032343,Andriyanto,X RPL 2</code>
                    </div>
                    <label class="form-label">Pilih File</label>
                    <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx" required>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary"><i class="bi bi-upload"></i> Upload Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5">
            <div class="modal-header border-0 px-4 pt-4">
                <div>
                    <h4 class="modal-title fw-bold">Kartu QR Siswa</h4>
                    <div class="text-muted small">Gunakan QR ini di mesin scanner.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-2">
                <div id="qrCardContent" class="qr-card-preview">
                    <img id="modalStudentPhoto" class="avatar-round d-none" alt="Foto Siswa">
                    <div id="modalPhotoPlaceholder" class="avatar-round d-flex align-items-center justify-content-center bg-light fs-3 fw-bold">S</div>
                    <h3 class="fw-bold mb-1" id="modalStudentName">Nama Siswa</h3>
                    <div class="text-muted mb-1">NISN: <span id="modalStudentNisn">-</span></div>
                    <div class="text-primary-soft fw-semibold mb-3" id="modalStudentClass">-</div>
                    <div class="qr-box"><div id="modalQrCode"></div></div>
                    <div class="small text-muted mt-3" id="modalFooterNote">Gunakan QR ini pada mesin scanner sekolah.</div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="printSingleCard"><i class="bi bi-printer"></i> Cetak Kartu</button>
            </div>
        </div>
    </div>
</div>

<?php if ($logStudent): ?>
<div class="modal fade" id="logModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5">
            <div class="modal-header border-0 px-4 pt-4">
                <div>
                    <h4 class="modal-title fw-bold">Log Absen: <?= e($logStudent['full_name']); ?></h4>
                    <div class="text-muted small">Menampilkan riwayat 30 hari terakhir.</div>
                </div>
                <a href="students.php" class="btn-close"></a>
            </div>
            <div class="modal-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$logRows): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat absensi.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($logRows as $row): ?>
                                <tr>
                                    <td><?= format_date($row['attendance_date'], 'd F Y'); ?></td>
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
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
<?php if ($editStudent): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('studentModal')).show();
});
</script>
<?php endif; ?>
<?php if ($logStudent): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('logModal')).show();
});
</script>
<?php endif; ?>
