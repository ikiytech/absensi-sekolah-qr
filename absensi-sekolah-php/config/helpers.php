<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function app_base_path(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_ends_with($scriptDir, '/api')) {
        $scriptDir = dirname($scriptDir);
    }
    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\') {
        return '';
    }
    return rtrim($scriptDir, '/');
}

function app_url(string $path = ''): string
{
    $base = app_base_path();
    return ($base ? $base . '/' : '') . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    if (!preg_match('/^https?:\/\//i', $url) && !str_starts_with($url, '/')) {
        $url = app_url($url);
    }
    header('Location: ' . $url);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(419);
        exit('CSRF token tidak valid.');
    }
}

function school_settings(): array
{
    static $settings = null;

    if (is_array($settings)) {
        return $settings;
    }

    try {
        $stmt = db()->query('SELECT * FROM school_settings WHERE id = 1 LIMIT 1');
        $settings = $stmt->fetch() ?: [];
    } catch (Throwable $e) {
        $settings = [];
    }

    return $settings;
}

function setting_value(string $key, string $default = ''): string
{
    $settings = school_settings();
    return isset($settings[$key]) && $settings[$key] !== null && $settings[$key] !== '' ? (string) $settings[$key] : $default;
}

function old(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? (string) $_POST[$key] : $default;
}

function selected($value, $expected): string
{
    return (string) $value === (string) $expected ? 'selected' : '';
}

function checked($value, $expected): string
{
    return (string) $value === (string) $expected ? 'checked' : '';
}

function format_datetime(?string $datetime, string $format = 'd/m/Y H:i'): string
{
    if (!$datetime) {
        return '-';
    }
    return date($format, strtotime($datetime));
}

function format_date(?string $date, string $format = 'd/m/Y'): string
{
    if (!$date) {
        return '-';
    }
    return date($format, strtotime($date));
}

function today_date(): string
{
    return date('Y-m-d');
}

function now_time(): string
{
    return date('H:i:s');
}

function now_datetime(): string
{
    return date('Y-m-d H:i:s');
}

function generate_qr_token(string $nisn): string
{
    return 'QR-' . preg_replace('/[^A-Za-z0-9]/', '', $nisn) . '-' . strtoupper(bin2hex(random_bytes(5)));
}

function upload_file(array $file, string $directory, string $prefix = 'file'): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload file gagal.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($extension, $allowed, true)) {
        throw new RuntimeException('Format file tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF.');
    }

    $targetDirectory = __DIR__ . '/../assets/uploads/' . trim($directory, '/');
    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0777, true) && !is_dir($targetDirectory)) {
        throw new RuntimeException('Gagal membuat folder upload.');
    }

    $fileName = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $targetDirectory . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Gagal menyimpan file upload.');
    }

    return 'assets/uploads/' . trim($directory, '/') . '/' . $fileName;
}

function delete_file_if_exists(?string $path): void
{
    if (!$path) {
        return;
    }

    $fullPath = __DIR__ . '/../' . ltrim($path, '/');
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function status_badge(string $status): string
{
    $map = [
        'hadir' => 'success',
        'terlambat' => 'warning',
        'pulang' => 'info',
        'izin' => 'secondary',
        'sakit' => 'secondary',
        'alpa' => 'danger',
        'pending' => 'secondary',
        'approved' => 'success',
        'rejected' => 'danger',
    ];

    $class = $map[strtolower($status)] ?? 'primary';
    return '<span class="badge text-bg-' . $class . '">' . e(ucfirst($status)) . '</span>';
}

function is_late_time(string $time): bool
{
    $lateAfter = setting_value('late_after', '07:15:00');
    return strtotime($time) > strtotime($lateAfter);
}

function attendance_mode(): string
{
    $mode = setting_value('scan_mode', 'auto');
    return in_array($mode, ['auto', 'masuk', 'pulang'], true) ? $mode : 'auto';
}

function get_count(string $table, string $where = '1=1', array $params = []): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function ensure_default_settings(): void
{
    $sql = 'INSERT INTO school_settings (id, school_name, school_address, school_phone, principal_name, principal_nip, attendance_start_time, late_after, attendance_end_time, scan_mode, document_city, footer_note)
            VALUES (1, :school_name, :school_address, :school_phone, :principal_name, :principal_nip, :attendance_start_time, :late_after, :attendance_end_time, :scan_mode, :document_city, :footer_note)
            ON DUPLICATE KEY UPDATE id = id';

    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':school_name' => 'SMK Contoh Indonesia',
        ':school_address' => 'Jl. Pendidikan No. 1',
        ':school_phone' => '08123456789',
        ':principal_name' => 'Nama Kepala Sekolah',
        ':principal_nip' => '-',
        ':attendance_start_time' => '07:00:00',
        ':late_after' => '07:15:00',
        ':attendance_end_time' => '15:00:00',
        ':scan_mode' => 'auto',
        ':document_city' => 'Kota Anda',
        ':footer_note' => 'Gunakan QR ini pada mesin scanner sekolah.',
    ]);
}

function get_classes(): array
{
    $stmt = db()->query('SELECT * FROM classes ORDER BY level, department, class_name');
    return $stmt->fetchAll();
}

function class_label(array $class): string
{
    return trim(($class['level'] ?? '') . ' ' . ($class['department'] ?? '') . ' ' . ($class['class_name'] ?? ''));
}

function parse_csv_or_xlsx(string $filePath, string $extension): array
{
    $extension = strtolower($extension);
    if ($extension === 'csv') {
        return parse_csv_rows($filePath);
    }
    if ($extension === 'xlsx') {
        return parse_xlsx_rows($filePath);
    }
    throw new RuntimeException('Format import harus CSV atau XLSX.');
}

function parse_csv_rows(string $filePath): array
{
    $rows = [];
    if (($handle = fopen($filePath, 'r')) === false) {
        throw new RuntimeException('File CSV tidak dapat dibuka.');
    }

    while (($data = fgetcsv($handle, 0, ',')) !== false) {
        if ($data === [null] || $data === false) {
            continue;
        }
        $rows[] = array_map(static fn ($item) => trim((string) $item), $data);
    }
    fclose($handle);
    return $rows;
}

function parse_xlsx_rows(string $filePath): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Ekstensi ZipArchive belum aktif di PHP.');
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new RuntimeException('File XLSX tidak dapat dibuka.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml);
        if ($xml !== false && isset($xml->si)) {
            foreach ($xml->si as $item) {
                $text = '';
                if (isset($item->t)) {
                    $text = (string) $item->t;
                } elseif (isset($item->r)) {
                    foreach ($item->r as $run) {
                        $text .= (string) $run->t;
                    }
                }
                $sharedStrings[] = $text;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        throw new RuntimeException('Worksheet pertama tidak ditemukan.');
    }

    $xml = simplexml_load_string($sheetXml);
    if ($xml === false) {
        $zip->close();
        throw new RuntimeException('Gagal membaca worksheet XLSX.');
    }

    $rows = [];
    if (isset($xml->sheetData->row)) {
        foreach ($xml->sheetData->row as $row) {
            $currentRow = [];
            $columnIndex = 0;
            foreach ($row->c as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                if ($ref !== '') {
                    $letters = preg_replace('/[^A-Z]/', '', $ref);
                    $columnIndex = letters_to_index($letters);
                }

                $value = '';
                $type = (string) ($cell['t'] ?? '');
                if ($type === 's') {
                    $sharedIndex = (int) $cell->v;
                    $value = $sharedStrings[$sharedIndex] ?? '';
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $value = (string) $cell->is->t;
                } elseif (isset($cell->v)) {
                    $value = (string) $cell->v;
                }

                $currentRow[$columnIndex] = trim($value);
                $columnIndex++;
            }

            if (!empty($currentRow)) {
                ksort($currentRow);
                $rows[] = array_values($currentRow);
            }
        }
    }

    $zip->close();
    return $rows;
}

function letters_to_index(string $letters): int
{
    $letters = strtoupper($letters);
    $index = 0;
    for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
        $index = $index * 26 + (ord($letters[$i]) - 64);
    }
    return max($index - 1, 0);
}

function get_or_create_class_by_import(string $className): int
{
    $className = trim($className);
    if ($className === '') {
        throw new RuntimeException('Nama kelas tidak boleh kosong pada file import.');
    }

    $stmt = db()->prepare('SELECT id FROM classes WHERE CONCAT(level, " ", department, " ", class_name) = :label OR class_name = :class_name LIMIT 1');
    $stmt->execute([
        ':label' => $className,
        ':class_name' => $className,
    ]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        return (int) $existing;
    }

    $parts = preg_split('/\s+/', $className);
    $level = $parts[0] ?? 'X';
    $department = $parts[1] ?? '-';
    $tail = array_slice($parts, 2);
    $simpleName = count($tail) > 0 ? implode(' ', $tail) : ($department !== '-' ? $department : $className);
    if ($simpleName === $department) {
        $simpleName = '1';
    }

    $insert = db()->prepare('INSERT INTO classes (level, department, class_name, homeroom_teacher, homeroom_phone, created_at) VALUES (:level, :department, :class_name, :teacher, :phone, :created_at)');
    $insert->execute([
        ':level' => $level,
        ':department' => $department,
        ':class_name' => $simpleName,
        ':teacher' => '-',
        ':phone' => '-',
        ':created_at' => now_datetime(),
    ]);

    return (int) db()->lastInsertId();
}

function app_logo(): string
{
    return setting_value('logo_path', '');
}

function current_page(): string
{
    return basename($_SERVER['PHP_SELF'] ?? '');
}

function attendance_action_label(string $action): string
{
    return match ($action) {
        'masuk' => 'Masuk',
        'pulang' => 'Pulang',
        default => ucfirst($action),
    };
}
