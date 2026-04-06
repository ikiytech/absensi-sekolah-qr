# Aplikasi Absensi QR Siswa - PHP Native + MySQL

## Fitur utama

- Login admin dan operator
- Registrasi operator mandiri, admin tinggal ACC / tolak
- Dashboard ringkas
- Master data kelas
- Master data siswa + upload foto
- Auto generate QR token per siswa
- Lihat kartu QR siswa dan cetak per siswa
- Cetak QR massal semua siswa / per kelas
- Scanner QR untuk masuk / pulang
- Input manual NISN atau token QR
- Notifikasi suara saat scan berhasil / gagal
- Log absensi 30 hari per siswa
- Live list absensi real-time
- Rekap laporan harian, mingguan, bulanan, tahunan
- Print laporan absensi lengkap
- Pengaturan identitas sekolah / kepala sekolah / jam masuk / mode scan
- Pembuatan dan cetak surat SP
- Import data siswa dari CSV atau XLSX sederhana

## Struktur file penting

- `sql/database.sql` : database dan tabel
- `config/database.php` : konfigurasi koneksi database
- `login.php` : login admin/operator
- `students.php` : data siswa, QR, import
- `classes.php` : data kelas
- `scanner.php` : scanner QR
- `reports.php` : rekap laporan
- `live.php` : live list absensi
- `settings.php` : pengaturan sekolah
- `sp_letters.php` : surat SP

## Cara install

1. Salin folder project ke `htdocs` XAMPP atau web server Anda.
2. Buat database MySQL dengan nama `absensi_sekolah`.
3. Import file `sql/database.sql` ke phpMyAdmin.
4. Buka file `config/database.php` lalu sesuaikan:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
5. Pastikan folder berikut bisa ditulis:
   - `assets/uploads/students`
   - `assets/uploads/settings`
6. Jalankan project dari browser.

## Login default

- Username: `admin`
- Password: `admin123`

## Format import siswa

File CSV atau XLSX sederhana dengan header:

```text
NISN,NAMA,KELAS
34032343,Andriyanto,X RPL 2
12345003,Citra Kirana,X RPL 2
```

## Catatan penting

- Kamera scanner web umumnya paling stabil di `localhost` atau domain `https`.
- Print laporan dan kartu masih memakai print browser, belum generator PDF server-side.
- Parser XLSX dibuat ringan untuk worksheet pertama, cocok untuk template sederhana.
