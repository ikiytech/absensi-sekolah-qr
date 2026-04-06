<?php
require_once __DIR__ . '/config/bootstrap.php';
require_admin();

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];
    if ($id > 0) {
        if ($action === 'approve') {
            $stmt = db()->prepare('UPDATE users SET status = :status, role = :role, updated_at = :updated_at WHERE id = :id AND role = :current_role');
            $stmt->execute([
                ':status' => 'approved',
                ':role' => 'operator',
                ':updated_at' => now_datetime(),
                ':id' => $id,
                ':current_role' => 'operator',
            ]);
            set_flash('success', 'Akun operator berhasil disetujui.');
        } elseif ($action === 'reject') {
            $stmt = db()->prepare('UPDATE users SET status = :status, updated_at = :updated_at WHERE id = :id AND role = :current_role');
            $stmt->execute([
                ':status' => 'rejected',
                ':updated_at' => now_datetime(),
                ':id' => $id,
                ':current_role' => 'operator',
            ]);
            set_flash('warning', 'Akun operator ditolak.');
        }
    }
    redirect('operators.php');
}

$pageTitle = 'Persetujuan Operator';
$operators = db()->query('SELECT * FROM users WHERE role = "operator" ORDER BY FIELD(status, "pending", "approved", "rejected"), created_at DESC')->fetchAll();
require_once __DIR__ . '/partials/header.php';
?>
<div class="panel-card table-card">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-1">Daftar Operator Sekolah</h5>
                <div class="text-muted">Admin dapat menyetujui atau menolak pendaftaran operator.</div>
            </div>
            <div class="badge text-bg-light p-2">Pending: <?= get_count('users', 'role = :role AND status = :status', [':role' => 'operator', ':status' => 'pending']); ?></div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Tanggal Daftar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$operators): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada operator terdaftar.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($operators as $index => $user): ?>
                        <tr>
                            <td><?= $index + 1; ?></td>
                            <td class="fw-semibold"><?= e($user['full_name']); ?></td>
                            <td><?= e($user['username']); ?></td>
                            <td><?= e($user['email']); ?></td>
                            <td><?= status_badge($user['status']); ?></td>
                            <td><?= format_datetime($user['created_at']); ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <?php if ($user['status'] !== 'approved'): ?>
                                        <a href="operators.php?action=approve&id=<?= (int) $user['id']; ?>" class="btn btn-sm btn-success">ACC</a>
                                    <?php endif; ?>
                                    <?php if ($user['status'] !== 'rejected'): ?>
                                        <a href="operators.php?action=reject&id=<?= (int) $user['id']; ?>" class="btn btn-sm btn-danger">Tolak</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
