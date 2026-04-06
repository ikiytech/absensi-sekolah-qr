<?php
require_once __DIR__ . '/config/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        set_flash('danger', 'Username/email dan password wajib diisi.');
        redirect('login.php');
    }

    if (attempt_login($username, $password)) {
        set_flash('success', 'Selamat datang, login berhasil.');
        redirect('dashboard.php');
    }

    if (!get_flash()) {
        set_flash('danger', 'Username atau password salah.');
    }
    redirect('login.php');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Absensi QR Siswa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body{font-family:Inter,sans-serif;background:linear-gradient(135deg,#f3f0ff,#eef3ff);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .login-card{max-width:980px;width:100%;border:none;border-radius:32px;overflow:hidden;box-shadow:0 30px 80px rgba(73,55,181,.15)}
        .hero{background:linear-gradient(135deg,#5b34f4,#7a5cff);color:#fff;padding:56px}
        .form-side{padding:48px;background:#fff}
        .hero-badge{background:rgba(255,255,255,.16);display:inline-block;padding:8px 14px;border-radius:999px;font-weight:700;margin-bottom:18px}
        .dot{width:12px;height:12px;border-radius:50%;display:inline-block;background:#c7b9ff;margin-right:6px}
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="row g-0">
            <div class="col-lg-6 hero d-none d-lg-block">
                <div class="hero-badge">Sistem Absensi QR Sekolah</div>
                <h1 class="display-6 fw-bold">Kelola absensi siswa, QR code, laporan, dan operator dalam satu panel.</h1>
                <p class="mt-3 opacity-75">Cocok untuk absensi masuk/pulang, cetak kartu siswa, rekap laporan, dan live monitor kehadiran.</p>
                <div class="mt-5">
                    <div class="mb-2"><span class="dot"></span> QR per siswa dan cetak massal</div>
                    <div class="mb-2"><span class="dot"></span> Scanner QR real-time</div>
                    <div class="mb-2"><span class="dot"></span> Rekap harian hingga tahunan</div>
                    <div><span class="dot"></span> Registrasi operator dan approval admin</div>
                </div>
            </div>
            <div class="col-lg-6 form-side">
                <div class="mb-4">
                    <h2 class="fw-bold mb-1">Login Admin / Operator</h2>
                    <div class="text-muted">Masuk untuk mengelola sistem absensi sekolah.</div>
                </div>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']); ?>"><?= e($flash['message']); ?></div>
                <?php endif; ?>
                <form method="post" class="vstack gap-3">
                    <?= csrf_field(); ?>
                    <div>
                        <label class="form-label">Username atau Email</label>
                        <input type="text" name="username" class="form-control form-control-lg" required>
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control form-control-lg" required>
                    </div>
                    <button class="btn btn-primary btn-lg">Login</button>
                </form>
                <div class="text-muted mt-4">Belum punya akun operator? <a href="register.php">Daftar di sini</a></div>
                <div class="mt-3 small text-muted">Login default: <strong>admin</strong> / <strong>admin123</strong></div>
            </div>
        </div>
    </div>
</body>
</html>
