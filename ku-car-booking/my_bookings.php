<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$uid = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare("UPDATE bookings SET status='cancelled'
        WHERE id = ? AND user_id = ? AND status IN ('pending','approved') AND start_time > NOW()");
    $st->execute([(int)($_POST['id'] ?? 0), $uid]);
    flash($st->rowCount() ? 'success' : 'danger', $st->rowCount() ? 'ยกเลิกการจองแล้ว' : 'ไม่สามารถยกเลิกรายการนี้ได้');
    redirect('my_bookings.php');
}

$st = db()->prepare("SELECT b.*, c.plate_no, c.name AS car_name FROM bookings b
                     JOIN cars c ON c.id = b.car_id WHERE b.user_id = ? ORDER BY b.start_time DESC");
$st->execute([$uid]);
$rows = $st->fetchAll();
$pageTitle = 'การจองของฉัน';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-3">การจองของฉัน</h4>
<div class="table-responsive card shadow-sm"><table class="table align-middle mb-0">
  <thead class="table-light"><tr><th>รถ</th><th>ช่วงเวลา</th><th>ปลายทาง</th><th>สถานะ</th><th>หมายเหตุ Admin</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['plate_no']) ?><br><small class="text-muted"><?= e($r['car_name']) ?></small></td>
      <td><?= e(date('d/m/Y H:i', strtotime($r['start_time']))) ?><br>ถึง <?= e(date('d/m/Y H:i', strtotime($r['end_time']))) ?></td>
      <td><?= e($r['destination']) ?><br><small class="text-muted"><?= e($r['purpose']) ?></small></td>
      <td><span class="badge text-bg-<?= e(STATUS_CLR[$r['status']]) ?>"><?= e(STATUS_TH[$r['status']]) ?></span></td>
      <td><?= e($r['admin_note']) ?></td>
      <td><?php if (in_array($r['status'], ['pending','approved']) && strtotime($r['start_time']) > time()): ?>
        <form method="post" onsubmit="return confirm('ยืนยันการยกเลิก?')"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger">ยกเลิก</button></form>
      <?php endif; ?></td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มีรายการจอง</td></tr><?php endif; ?>
  </tbody></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
