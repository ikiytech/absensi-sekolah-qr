<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
$user = current_user();
$flash = get_flash();
$pageTitle = $pageTitle ?? 'Aplikasi Absensi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?> - <?= e(setting_value('school_name', 'Absensi QR')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar shadow-sm">
            <div>
                <div class="text-muted small">Panel Admin Sekolah</div>
                <h1 class="page-title mb-0"><?= e($pageTitle); ?></h1>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-md-block">
                    <div class="fw-semibold"><?= e($user['full_name'] ?? '-'); ?></div>
                    <small class="text-muted text-capitalize"><?= e($user['role'] ?? ''); ?></small>
                </div>
                <div class="user-avatar"><?= e(strtoupper(substr($user['full_name'] ?? 'A', 0, 1))); ?></div>
            </div>
        </header>

        <section class="content-area">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']); ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
