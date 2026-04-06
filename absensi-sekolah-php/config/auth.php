<?php

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user !== null) {
        return $user;
    }

    $stmt = db()->prepare('SELECT id, full_name, username, email, role, status FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('danger', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    $user = current_user();
    if (($user['role'] ?? '') !== 'admin') {
        set_flash('danger', 'Akses hanya untuk admin.');
        redirect('dashboard.php');
    }
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1');
    $stmt->execute([
        ':username' => $username,
        ':email' => $username,
    ]);

    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user['password'])) {
        return false;
    }

    if (($user['status'] ?? 'pending') !== 'approved') {
        set_flash('warning', 'Akun Anda belum disetujui admin.');
        return false;
    }

    $_SESSION['user_id'] = $user['id'];
    return true;
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}
