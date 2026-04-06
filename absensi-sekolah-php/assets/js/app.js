function createQr(target, text, size = 180) {
    if (!target) return;
    target.innerHTML = '';
    new QRCode(target, {
        text: text,
        width: size,
        height: size,
        correctLevel: QRCode.CorrectLevel.H
    });
}

function playTone(type = 'success') {
    try {
        const context = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = context.createOscillator();
        const gainNode = context.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(context.destination);
        oscillator.type = 'sine';
        oscillator.frequency.value = type === 'success' ? 880 : 240;
        gainNode.gain.setValueAtTime(0.18, context.currentTime);
        oscillator.start();
        oscillator.stop(context.currentTime + 0.18);
    } catch (error) {
        console.error(error);
    }
}

function printElementHtml(title, html) {
    const printWindow = window.open('', '_blank', 'width=900,height=700');
    if (!printWindow) return;
    printWindow.document.write(`
        <html>
            <head>
                <title>${title}</title>
                <link rel="stylesheet" href="assets/css/style.css">
                <style>body{padding:24px;font-family:Inter,Arial,sans-serif;}@media print{body{padding:0;}}</style>
            </head>
            <body>${html}<script>window.onload = function(){window.print();};</script></body>
        </html>
    `);
    printWindow.document.close();
}

document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-qr-modal]');
    if (!button) return;

    const modalEl = document.getElementById('qrModal');
    const qrTarget = document.getElementById('modalQrCode');
    const img = document.getElementById('modalStudentPhoto');
    const noPhoto = document.getElementById('modalPhotoPlaceholder');
    const nameEl = document.getElementById('modalStudentName');
    const nisnEl = document.getElementById('modalStudentNisn');
    const classEl = document.getElementById('modalStudentClass');
    const footerEl = document.getElementById('modalFooterNote');
    const token = button.dataset.token || '';
    const photo = button.dataset.photo || '';

    nameEl.textContent = button.dataset.name || '-';
    nisnEl.textContent = button.dataset.nisn || '-';
    classEl.textContent = button.dataset.class || '-';
    footerEl.textContent = button.dataset.footer || 'Gunakan QR ini untuk absensi.';

    if (photo) {
        img.src = photo;
        img.classList.remove('d-none');
        noPhoto.classList.add('d-none');
    } else {
        img.classList.add('d-none');
        noPhoto.classList.remove('d-none');
        noPhoto.textContent = (button.dataset.name || 'S').slice(0, 1).toUpperCase();
    }

    createQr(qrTarget, token, 170);
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    const printButton = document.getElementById('printSingleCard');
    if (printButton) {
        printButton.onclick = function () {
            const cardHtml = document.getElementById('qrCardContent').outerHTML;
            printElementHtml('Kartu QR Siswa', cardHtml);
        };
    }
});
