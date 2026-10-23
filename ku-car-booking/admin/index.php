<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$filter = $_GET['status'] ?? 'pending';
$valid  = array_keys(STATUS_TH);
$sql = "SELECT b.*, c.plate_no, c.name AS car_name, u.full_name, u.phone
        FROM bookings b JOIN cars c ON c.id=b.car_id JOIN users u ON u.id=b.user_id";
$params = [];
if (in_array($filter, $valid, true)) { $sql .= ' WHERE b.status = ?'; $params[] = $filter; } else { $filter = 'all'; }
$sql .= ' ORDER BY b.start_time DESC';
$st = db()->prepare($sql); $st->execute($params); $rows = $st->fetchAll();

$counts = db()->query("SELECT status, COUNT(*) n FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3">Admin Dashboard – รายการจองทั้งหมด</h4>
<ul class="nav nav-pills mb-3">
  <li class="nav-item"><a class="nav-link <?= $filter==='all'?'active':'' ?>" href="?status=all">ทั้งหมด</a></li>
  <?php foreach (STATUS_TH as $k => $t): ?>
    <li class="nav-item"><a class="nav-link <?= $filter===$k?'active':'' ?>" href="?status=<?= e($k) ?>"><?= e($t) ?> <span class="badge bg-light text-dark"><?= (int)($counts[$k] ?? 0) ?></span></a></li>
  <?php endforeach; ?>
</ul>
<div class="table-responsive card shadow-sm"><table class="table align-middle mb-0">
  <thead class="table-light"><tr><th>#</th><th>ผู้จอง</th><th>รถ</th><th>ช่วงเวลา</th><th>ปลายทาง / วัตถุประสงค์</th><th>สถานะ</th><th style="min-width:260px">พิจารณา</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= e($r['full_name']) ?><br><small class="text-muted"><?= e($r['phone']) ?></small></td>
      <td><?= e($r['plate_no']) ?><br><small class="text-muted"><?= e($r['car_name']) ?></small></td>
      <td><?= e(date('d/m/Y H:i', strtotime($r['start_time']))) ?><br>ถึง <?= e(date('d/m/Y H:i', strtotime($r['end_time']))) ?></td>
      <td><?= e($r['destination']) ?><br><small class="text-muted"><?= e($r['purpose']) ?> (<?= (int)$r['passengers'] ?> คน)</small></td>
      <td><span class="badge text-bg-<?= e(STATUS_CLR[$r['status']]) ?>"><?= e(STATUS_TH[$r['status']]) ?></span>
          <?php if ($r['admin_note']): ?><br><small class="text-muted"><?= e($r['admin_note']) ?></small><?php endif; ?></td>
      <td>
        <?php if ($r['status'] === 'pending'): ?>
        <form method="post" action="<?= e(url('admin/action.php')) ?>" class="d-flex flex-column gap-1">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input name="note" class="form-control form-control-sm" placeholder="หมายเหตุ (ไม่บังคับ)">
          <div class="d-flex gap-1">
            <button name="decision" value="approve" class="btn btn-sm btn-success flex-fill">อนุมัติ</button>
            <button name="decision" value="reject" class="btn btn-sm btn-danger flex-fill">ไม่อนุมัติ</button>
          </div>
        </form>
        <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">ไม่มีรายการ</td></tr><?php endif; ?>
  </tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
