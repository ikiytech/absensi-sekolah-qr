<?php $page = current_page(); ?>
<aside class="sidebar shadow-sm">
    <div class="brand d-flex align-items-center gap-2">
        <div class="brand-icon"><i class="bi bi-qr-code-scan"></i></div>
        <div>
            <div class="brand-title">Absensi</div>
            <small class="text-muted">QR Siswa</small>
        </div>
    </div>

    <nav class="nav flex-column mt-4 gap-1">
        <a class="nav-link <?= in_array($page, ['dashboard.php', 'index.php'], true) ? 'active' : ''; ?>" href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a class="nav-link <?= $page === 'scanner.php' ? 'active' : ''; ?>" href="scanner.php"><i class="bi bi-qr-code-scan"></i> Scanner QR</a>
        <a class="nav-link <?= $page === 'students.php' ? 'active' : ''; ?>" href="students.php"><i class="bi bi-people-fill"></i> Data Siswa</a>
        <a class="nav-link <?= $page === 'classes.php' ? 'active' : ''; ?>" href="classes.php"><i class="bi bi-diagram-3-fill"></i> Data Kelas</a>
        <a class="nav-link <?= $page === 'reports.php' ? 'active' : ''; ?>" href="reports.php"><i class="bi bi-bar-chart-fill"></i> Rekap Laporan</a>
        <a class="nav-link <?= $page === 'live.php' ? 'active' : ''; ?>" href="live.php"><i class="bi bi-broadcast"></i> Live List</a>
        <a class="nav-link <?= $page === 'sp_letters.php' ? 'active' : ''; ?>" href="sp_letters.php"><i class="bi bi-file-earmark-text-fill"></i> Surat SP</a>
        <a class="nav-link <?= $page === 'operators.php' ? 'active' : ''; ?>" href="operators.php"><i class="bi bi-person-check-fill"></i> Operator</a>
        <a class="nav-link <?= $page === 'settings.php' ? 'active' : ''; ?>" href="settings.php"><i class="bi bi-gear-fill"></i> Pengaturan</a>
        <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
</aside>
