<?php
// ส่งข้อมูลการจองให้ปฏิทิน (FullCalendar) ในรูปแบบ JSON
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

$start = strtotime($_GET['start'] ?? '') ?: strtotime('-1 month');
$end   = strtotime($_GET['end'] ?? '')   ?: strtotime('+2 month');
$carId = (int)($_GET['car_id'] ?? 0);

$sql = "SELECT b.id, b.start_time, b.end_time, b.status, c.plate_no, c.name AS car_name,
               u.full_name, b.destination
        FROM bookings b
        JOIN cars c ON c.id = b.car_id
        JOIN users u ON u.id = b.user_id
        WHERE b.status IN ('pending','approved')
          AND b.start_time < :e AND b.end_time > :s";
$params = [':s' => date('Y-m-d H:i:s', $start), ':e' => date('Y-m-d H:i:s', $end)];
if ($carId) { $sql .= ' AND b.car_id = :c'; $params[':c'] = $carId; }
$sql .= ' ORDER BY b.start_time';

$st = db()->prepare($sql);
$st->execute($params);

$events = [];
foreach ($st as $r) {
    $approved = $r['status'] === 'approved';
    $events[] = [
        'id'    => $r['id'],
        'title' => $r['plate_no'] . ' ' . $r['car_name'] . ($approved ? '' : ' (รออนุมัติ)'),
        'start' => str_replace(' ', 'T', $r['start_time']),
        'end'   => str_replace(' ', 'T', $r['end_time']),
        'color' => $approved ? '#198754' : '#f0ad00',
        'textColor' => $approved ? '#fff' : '#212529',
        'extendedProps' => [
            'status' => STATUS_TH[$r['status']],
            // แสดงชื่อผู้จองเฉพาะ Admin เพื่อความเป็นส่วนตัว
            'who'    => is_admin() ? $r['full_name'] . ' → ' . $r['destination'] : null,
        ],
    ];
}
echo json_encode($events, JSON_UNESCAPED_UNICODE);
