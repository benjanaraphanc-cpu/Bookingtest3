<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();                       // กันผู้ใช้ทั่วไปยิงคำสั่งตรงๆ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/index.php');
csrf_check();

$id       = (int)($_POST['id'] ?? 0);
$decision = $_POST['decision'] ?? '';
$note     = trim($_POST['note'] ?? '') ?: null;
if (!in_array($decision, ['approve', 'reject'], true)) { flash('danger', 'คำสั่งไม่ถูกต้อง'); redirect('admin/index.php'); }

$pdo = db();
try {
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT * FROM bookings WHERE id = ? FOR UPDATE');
    $st->execute([$id]);
    $b = $st->fetch();
    if (!$b || $b['status'] !== 'pending') throw new RuntimeException('รายการนี้ไม่อยู่ในสถานะรออนุมัติ');

    if ($decision === 'approve') {
        // เช็คซ้ำอีกครั้งก่อนอนุมัติ: ห้ามทับกับรายการที่อนุมัติแล้ว
        $chk = $pdo->prepare("SELECT 1 FROM bookings WHERE car_id = ? AND id <> ? AND status = 'approved'
                              AND start_time < ? AND end_time > ? LIMIT 1");
        $chk->execute([$b['car_id'], $id, $b['end_time'], $b['start_time']]);
        if ($chk->fetch()) throw new RuntimeException('ไม่สามารถอนุมัติได้: ช่วงเวลานี้ทับกับการจองที่อนุมัติไปแล้ว');
    }
    $pdo->prepare('UPDATE bookings SET status = ?, admin_note = ?, decided_by = ?, decided_at = NOW() WHERE id = ?')
        ->execute([$decision === 'approve' ? 'approved' : 'rejected', $note, current_user()['id'], $id]);
    $pdo->commit();
    flash('success', $decision === 'approve' ? 'อนุมัติการจองแล้ว' : 'ไม่อนุมัติการจองแล้ว');
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('danger', $ex->getMessage());
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('danger', 'เกิดข้อผิดพลาดของระบบ');
}
redirect('admin/index.php');
