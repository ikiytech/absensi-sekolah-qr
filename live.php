<?php
$pageTitle = 'Live List Absensi';
require_once __DIR__ . '/partials/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="stat-card">
            <div class="text-muted">Monitor Real-time</div>
            <div class="display-6 fw-bold mt-2" id="liveCount">0</div>
            <div class="text-muted mt-2">Jumlah data yang sedang ditampilkan.</div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="stat-card d-flex flex-column justify-content-center h-100">
            <div class="fw-semibold">Halaman ini cocok ditampilkan di monitor guru, ruang TU, atau layar lobi sekolah.</div>
            <div class="text-muted mt-2">Auto refresh 5 detik, menampilkan absensi terbaru seluruh siswa.</div>
        </div>
    </div>
</div>

<div class="panel-card table-card">
    <div class="card-header p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1">Live Attendance Feed</h5>
            <div class="text-muted small">Menampilkan 20 transaksi absensi terbaru.</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-soft" onclick="loadLive()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            <a href="scanner.php" class="btn btn-primary">Buka Scanner</a>
        </div>
    </div>
    <div class="card-body p-4">
        <div id="liveList" class="vstack gap-3">
            <div class="text-muted">Memuat data...</div>
        </div>
    </div>
</div>

<script>
async function loadLive() {
    try {
        const response = await fetch('api/live.php?limit=20');
        const data = await response.json();
        const container = document.getElementById('liveList');
        const rows = data.rows || [];
        document.getElementById('liveCount').textContent = rows.length;

        if (!rows.length) {
            container.innerHTML = '<div class="text-muted">Belum ada data absensi.</div>';
            return;
        }

        container.innerHTML = rows.map((row) => `
            <div class="live-item d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="fw-bold fs-5">${row.name}</div>
                    <div class="text-muted">NISN: ${row.nisn} | ${row.class}</div>
                    <div class="small text-muted mt-2">Tanggal: ${row.date} | Status: ${row.status}</div>
                </div>
                <div class="d-flex gap-4 align-items-center flex-wrap">
                    <div>
                        <div class="small text-muted">Masuk</div>
                        <div class="live-time">${row.check_in}</div>
                    </div>
                    <div>
                        <div class="small text-muted">Pulang</div>
                        <div class="live-time">${row.check_out}</div>
                    </div>
                    <div>
                        <div class="small text-muted">Keterangan</div>
                        <div class="fw-semibold">${row.notes}</div>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        document.getElementById('liveList').innerHTML = '<div class="text-danger">Gagal memuat live list.</div>';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    loadLive();
    setInterval(loadLive, 5000);
});
</script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
