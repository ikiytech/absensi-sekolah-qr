<?php
require_once __DIR__ . '/config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if ($fullName === '' || $username === '' || $email === '' || $password === '') {
        set_flash('danger', 'Semua field wajib diisi.');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Format email tidak valid.');
        redirect('register.php');
    }

    if ($password !== $confirm) {
        set_flash('danger', 'Konfirmasi password tidak sama.');
        redirect('register.php');
    }

    $check = db()->prepare('SELECT COUNT(*) FROM users WHERE username = :username OR email = :email');
    $check->execute([
        ':username' => $username,
        ':email' => $email,
    ]);
    if ((int) $check->fetchColumn() > 0) {
        set_flash('warning', 'Username atau email sudah terdaftar.');
        redirect('register.php');
    }

    $stmt = db()->prepare('INSERT INTO users (full_name, username, email, password, role, status, created_at) VALUES (:full_name, :username, :email, :password, :role, :status, :created_at)');
    $stmt->execute([
        ':full_name' => $fullName,
        ':username' => $username,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => 'operator',
        ':status' => 'pending',
        ':created_at' => now_datetime(),
    ]);

    set_flash('success', 'Pendaftaran berhasil. Tunggu admin menyetujui akun Anda.');
    redirect('login.php');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Operator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body{font-family:Inter,sans-serif;background:#f6f7fb;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .card-box{max-width:620px;width:100%;border:none;border-radius:30px;box-shadow:0 24px 60px rgba(63,51,181,.12)}
    </style>
</head>
<body>
    <div class="card card-box p-4 p-md-5">
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Daftar Operator Sekolah</h2>
            <div class="text-muted">Akun operator akan aktif setelah disetujui admin.</div>
        </div>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
        <?php endif; ?>
        <form method="post" class="row g-3">
            <?= csrf_field(); ?>
            <div class="col-12">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="full_name" class="form-control form-control-lg" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control form-control-lg" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control form-control-lg" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control form-control-lg" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="form-control form-control-lg" required>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary btn-lg">Daftar</button>
                <a href="login.php" class="btn btn-outline-secondary btn-lg">Kembali ke Login</a>
            </div>
        </form>
    </div>
</body>
</html>
