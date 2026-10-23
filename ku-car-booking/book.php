<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
if (is_admin()) { flash('warning', 'บัญชี Admin ทำหน้าที่อนุมัติเท่านั้น ไม่สามารถจองรถได้'); redirect('admin/index.php'); }

$inNet = is_ku_network();
$cars  = db()->query("SELECT * FROM cars WHERE status='available' ORDER BY id")->fetchAll();
$form  = ['car_id' => (int)($_GET['car'] ?? 0), 'start' => '', 'end' => '', 'destination' => '', 'purpose' => '', 'passengers' => 1];
if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    $form['start'] = $_GET['date'] . 'T08:00';
    $form['end']   = $_GET['date'] . 'T16:00';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = [
        'car_id' => (int)($_POST['car_id'] ?? 0), 'start' => $_POST['start'] ?? '', 'end' => $_POST['end'] ?? '',
        'destination' => trim($_POST['destination'] ?? ''), 'purpose' => trim($_POST['purpose'] ?? ''),
        'passengers' => (int)($_POST['passengers'] ?? 1),
    ];
    $err = null;
    $s = strtotime($form['start']); $en = strtotime($form['end']);

    // 1) บังคับ Wi-Fi มก. ฝั่งเซิร์ฟเวอร์ (ห้ามเชื่อแค่หน้าเว็บ)
    if (!$inNet)                                   $err = 'ไม่สามารถจองได้: ต้องเชื่อมต่อ Wi-Fi ของมหาวิทยาลัยเกษตรศาสตร์เท่านั้น';
    elseif (!$s || !$en)                           $err = 'กรุณาระบุวัน-เวลาให้ถูกต้อง';
    elseif ($s < time())                           $err = 'ไม่สามารถจองย้อนหลังได้';
    elseif ($en <= $s)                             $err = 'เวลาสิ้นสุดต้องหลังเวลาเริ่มต้น';
    elseif (($en - $s) > MAX_BOOKING_DAYS * 86400) $err = 'จองได้ครั้งละไม่เกิน ' . MAX_BOOKING_DAYS . ' วัน';
    elseif ($form['destination'] === '' || $form['purpose'] === '') $err = 'กรุณากรอกปลายทางและวัตถุประสงค์';
    elseif ($form['passengers'] < 1)               $err = 'จำนวนผู้โดยสารไม่ถูกต้อง';

    if (!$err) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            // ล็อกแถวรถคันนี้ เพื่อกันสองคนกดจองพร้อมกัน
            $car = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND status = 'available' FOR UPDATE");
            $car->execute([$form['car_id']]);
            $carRow = $car->fetch();
            if (!$carRow) {
                $err = 'ไม่พบรถคันนี้หรืออยู่ระหว่างซ่อมบำรุง';
            } elseif ($form['passengers'] > $carRow['seats']) {
                $err = 'จำนวนผู้โดยสารเกินจำนวนที่นั่ง (' . $carRow['seats'] . ' ที่นั่ง)';
            } else {
                $startSql = date('Y-m-d H:i:s', $s);
                $endSql   = date('Y-m-d H:i:s', $en);
                // 2) ตรวจจองซ้ำ: ช่วงเวลาทับกัน และสถานะ pending/approved
                $chk = $pdo->prepare("SELECT start_time, end_time, status FROM bookings
                    WHERE car_id = ? AND status IN ('pending','approved')
                      AND start_time < ? AND end_time > ? LIMIT 1");
                $chk->execute([$form['car_id'], $endSql, $startSql]);
                if ($c = $chk->fetch()) {
                    $err = 'รถคันนี้ถูกจองแล้วในช่วง ' . date('d/m/Y H:i', strtotime($c['start_time'])) . ' – '
                         . date('d/m/Y H:i', strtotime($c['end_time'])) . ' (' . STATUS_TH[$c['status']] . ') ไม่สามารถบันทึกการจองซ้ำได้';
                } else {
                    $pdo->prepare("INSERT INTO bookings (user_id,car_id,start_time,end_time,destination,purpose,passengers)
                                   VALUES (?,?,?,?,?,?,?)")
                        ->execute([current_user()['id'], $form['car_id'], $startSql, $endSql,
                                   $form['destination'], $form['purpose'], $form['passengers']]);
                }
            }
            $err ? $pdo->rollBack() : $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = 'เกิดข้อผิดพลาดของระบบ กรุณาลองใหม่';
        }
    }
    if ($err) flash('danger', $err);
    else { flash('success', 'ส่งคำขอจองเรียบร้อย รอผู้ดูแลระบบอนุมัติ'); redirect('my_bookings.php'); }
}

$pageTitle = 'จองรถ';
include __DIR__ . '/includes/header.php';
?>
<?php if (!$inNet): ?>
  <div class="alert alert-danger">🚫 คุณไม่ได้เชื่อมต่อ Wi-Fi ของมหาวิทยาลัยเกษตรศาสตร์ (IP: <?= e(client_ip()) ?>) จึงไม่สามารถจองรถได้</div>
<?php endif; ?>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card shadow-sm"><div class="card-body p-4">
  <h4 class="mb-3">แบบฟอร์มจองรถ</h4>
  <form method="post">
    <fieldset <?= $inNet ? '' : 'disabled' ?>>
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">เลือกรถ</label>
      <select name="car_id" class="form-select" required>
        <option value="">-- เลือกรถ --</option>
        <?php foreach ($cars as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $form['car_id'] === (int)$c['id'] ? 'selected' : '' ?>>
            <?= e($c['plate_no'] . ' - ' . $c['name'] . ' (' . $c['seats'] . ' ที่นั่ง)') ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="row g-3 mb-3">
      <div class="col-md-6"><label class="form-label">วัน-เวลาเริ่มใช้</label><input type="datetime-local" name="start" class="form-control" required value="<?= e($form['start']) ?>"></div>
      <div class="col-md-6"><label class="form-label">วัน-เวลาคืนรถ</label><input type="datetime-local" name="end" class="form-control" required value="<?= e($form['end']) ?>"></div>
    </div>
    <div class="mb-3"><label class="form-label">สถานที่ปลายทาง</label><input name="destination" class="form-control" required maxlength="200" value="<?= e($form['destination']) ?>"></div>
    <div class="mb-3"><label class="form-label">วัตถุประสงค์</label><input name="purpose" class="form-control" required maxlength="255" value="<?= e($form['purpose']) ?>"></div>
    <div class="mb-3"><label class="form-label">จำนวนผู้โดยสาร</label><input type="number" min="1" max="20" name="passengers" class="form-control" required value="<?= (int)$form['passengers'] ?>"></div>
    <button class="btn btn-ku w-100">ส่งคำขอจอง</button>
    </fieldset>
  </form>
</div></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
