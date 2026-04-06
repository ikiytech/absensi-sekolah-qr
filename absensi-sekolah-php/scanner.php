<?php
$pageTitle = 'Scanner QR';
require_once __DIR__ . '/partials/header.php';

$todayCount = get_count('attendance', 'attendance_date = :date', [':date' => today_date()]);
$todayIn = get_count('attendance', 'attendance_date = :date AND check_in IS NOT NULL', [':date' => today_date()]);
$todayOut = get_count('attendance', 'attendance_date = :date AND check_out IS NOT NULL', [':date' => today_date()]);
?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="text-muted">Absensi Hari Ini</div>
            <div class="display-6 fw-bold mt-2"><?= $todayCount; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="text-muted">Sudah Masuk</div>
            <div class="display-6 fw-bold mt-2"><?= $todayIn; ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="text-muted">Sudah Pulang</div>
            <div class="display-6 fw-bold mt-2"><?= $todayOut; ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="panel-card h-100">
            <div class="card-header p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">Scanner Absensi</h5>
                    <div class="text-muted small">Arahkan kamera ke QR kartu siswa.</div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <label class="small text-muted">Mode</label>
                    <select class="form-select" id="scanMode" style="width: 150px;">
                        <option value="auto">Auto</option>
                        <option value="masuk">Masuk</option>
                        <option value="pulang">Pulang</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="scanner-shell mb-4">
                    <div id="reader"></div>
                </div>
                <div class="alert alert-light border rounded-4 mb-4">
                    <div class="fw-semibold mb-1">Catatan penggunaan kamera</div>
                    <div class="small text-muted">Akses kamera browser umumnya berjalan baik di localhost atau hosting HTTPS.</div>
                </div>
                <form id="manualScanForm" class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Input Manual NISN / Token QR</label>
                        <input type="text" class="form-control form-control-lg" id="manualCode" placeholder="Masukkan NISN atau QR token...">
                    </div>
                    <div class="col-md-4 d-grid align-items-end">
                        <button class="btn btn-primary btn-lg mt-4"><i class="bi bi-send-check"></i> Proses Manual</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="panel-card h-100">
            <div class="card-header p-4">
                <h5 class="fw-bold mb-1">Hasil Scan Terakhir</h5>
                <div class="text-muted small">Notifikasi masuk atau pulang akan tampil di sini.</div>
            </div>
            <div class="card-body p-4">
                <div id="resultCard" class="qr-card-preview">
                    <div class="avatar-round d-flex align-items-center justify-content-center bg-light fs-2 fw-bold">?</div>
                    <h4 class="fw-bold">Belum ada scan</h4>
                    <div class="text-muted">Scan QR siswa untuk memulai absensi.</div>
                </div>
                <div class="mt-4 alert alert-light border rounded-4 mb-0">
                    <div class="small text-muted">Jam masuk standar: <strong><?= e(setting_value('attendance_start_time', '07:00:00')); ?></strong></div>
                    <div class="small text-muted">Batas terlambat: <strong><?= e(setting_value('late_after', '07:15:00')); ?></strong></div>
                    <div class="small text-muted">Jam pulang standar: <strong><?= e(setting_value('attendance_end_time', '15:00:00')); ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel-card mt-4 table-card">
    <div class="card-header p-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-1">Live Absensi Hari Ini</h5>
            <div class="text-muted small">Auto refresh setiap 5 detik.</div>
        </div>
        <a href="live.php" class="btn btn-soft">Halaman Live List</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Tanggal</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody id="liveTableBody">
                <tr><td colspan="7" class="text-center py-5 text-muted">Memuat data...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let lastScan = '';
let lastScanAt = 0;

function updateResultCard(response, success = true) {
    const card = document.getElementById('resultCard');
    if (!success) {
        card.innerHTML = `
            <div class="avatar-round d-flex align-items-center justify-content-center bg-danger-subtle text-danger fs-2 fw-bold">!</div>
            <h4 class="fw-bold mt-3">Scan gagal</h4>
            <div class="text-muted">${response.message || 'Terjadi kesalahan.'}</div>
        `;
        playTone('error');
        return;
    }

    const student = response.student || {};
    const badgeClass = response.action === 'masuk' ? 'success' : 'info';
    const avatar = student.photo
        ? `<img src="${student.photo}" class="avatar-round" alt="${student.name}">`
        : `<div class="avatar-round d-flex align-items-center justify-content-center bg-light fs-2 fw-bold">${(student.name || 'S').slice(0,1).toUpperCase()}</div>`;

    card.innerHTML = `
        ${avatar}
        <h4 class="fw-bold mt-3 mb-1">${student.name || '-'}</h4>
        <div class="text-muted mb-1">NISN: ${student.nisn || '-'}</div>
        <div class="text-primary-soft fw-semibold mb-3">${student.class || '-'}</div>
        <div class="badge text-bg-${badgeClass} mb-3">${response.action === 'masuk' ? 'Masuk' : 'Pulang'} - ${response.time}</div>
        <div class="small text-muted">${response.message || ''}</div>
    `;
    playTone('success');
}

async function submitScan(code) {
    try {
        const response = await fetch('api/scan.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                code: code,
                mode: document.getElementById('scanMode').value
            })
        });
        const data = await response.json();
        updateResultCard(data, Boolean(data.success));
        refreshLiveTable();
    } catch (error) {
        updateResultCard({message: 'Gagal terhubung ke server scanner.'}, false);
    }
}

function onScanSuccess(decodedText) {
    alert("ISI QR = " + decodedText);

    const now = Date.now();
    if (decodedText === lastScan && now - lastScanAt < 2500) {
        return;
    }
    lastScan = decodedText;
    lastScanAt = now;
    submitScan(decodedText);
}
async function refreshLiveTable() {
    try {
        const response = await fetch('api/live.php?today=1&limit=15');
        const data = await response.json();
        const tbody = document.getElementById('liveTableBody');
        if (!data.rows || !data.rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Belum ada data absensi hari ini.</td></tr>';
            return;
        }
        tbody.innerHTML = data.rows.map((row) => `
            <tr>
                <td>
                    <div class="fw-semibold">${row.name}</div>
                    <small class="text-muted">NISN: ${row.nisn}</small>
                </td>
                <td>${row.class}</td>
                <td>${row.date}</td>
                <td>${row.check_in}</td>
                <td>${row.check_out}</td>
                <td><span class="badge text-bg-${row.status.toLowerCase() === 'terlambat' ? 'warning' : 'success'}">${row.status}</span></td>
                <td>${row.notes}</td>
            </tr>
        `).join('');
    } catch (error) {
        console.error(error);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const scanner = new Html5QrcodeScanner('reader', {
        fps: 10,
        qrbox: {width: 260, height: 260},
        rememberLastUsedCamera: true,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
    }, false);

    scanner.render(onScanSuccess, function () {});
    refreshLiveTable();
    setInterval(refreshLiveTable, 5000);

    document.getElementById('manualScanForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const code = document.getElementById('manualCode').value.trim();
        if (!code) return;
        submitScan(code);
        document.getElementById('manualCode').value = '';
    });
});
</script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
