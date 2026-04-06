<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$limit = min(max((int) ($_GET['limit'] ?? 12), 1), 50);
$todayOnly = isset($_GET['today']) ? (int) $_GET['today'] === 1 : false;

$sql = 'SELECT a.*, s.full_name, s.nisn, s.photo_path, c.level, c.department, c.class_name
        FROM attendance a
        JOIN students s ON s.id = a.student_id
        JOIN classes c ON c.id = s.class_id';
$params = [];
if ($todayOnly) {
    $sql .= ' WHERE a.attendance_date = :attendance_date';
    $params[':attendance_date'] = today_date();
}
$sql .= ' ORDER BY a.updated_at DESC, a.created_at DESC LIMIT ' . $limit;

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$data = array_map(static function (array $row): array {
    return [
        'name' => $row['full_name'],
        'nisn' => $row['nisn'],
        'photo' => $row['photo_path'],
        'class' => trim($row['level'] . ' ' . $row['department'] . ' ' . $row['class_name']),
        'date' => format_date($row['attendance_date']),
        'check_in' => $row['check_in'] ?: '-',
        'check_out' => $row['check_out'] ?: '-',
        'status' => ucfirst($row['status']),
        'notes' => $row['notes'] ?: '-',
    ];
}, $rows);

echo json_encode([
    'success' => true,
    'rows' => $data,
]);
