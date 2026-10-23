<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'หน้าแรก - ปฏิทินการจองรถ';
$cars  = db()->query("SELECT * FROM cars ORDER BY id")->fetchAll();
$inNet = is_ku_network();
include __DIR__ . '/includes/header.php';
?>
<?php if ($inNet): ?>
  <div class="alert alert-success py-2">✅ คุณอยู่ในเครือข่ายมหาวิทยาลัยเกษตรศาสตร์ (IP: <?= e(client_ip()) ?>) สามารถกดจองรถได้</div>
<?php else: ?>
  <div class="alert alert-danger py-2">🚫 ตรวจพบว่าคุณไม่ได้เชื่อมต่อ Wi-Fi ของมหาวิทยาลัยเกษตรศาสตร์ (IP: <?= e(client_ip()) ?>)
    — ดูปฏิทินได้ แต่ <strong>ไม่สามารถจองรถได้</strong> จนกว่าจะเชื่อมต่อ Wi-Fi มก.</div>
<?php endif; ?>

<h4 class="mb-3">รถที่ให้บริการ</h4>
<div class="row g-3 mb-4">
<?php foreach ($cars as $c): ?>
  <div class="col-sm-6 col-lg-3">
    <div class="card car-card h-100">
      <img src="<?= e(car_img($c['image'])) ?>" class="card-img-top" alt="<?= e($c['name']) ?>">
      <div class="card-body d-flex flex-column">
        <h6 class="card-title mb-1"><?= e($c['name']) ?></h6>
        <div class="small text-muted mb-1">ทะเบียน <?= e($c['plate_no']) ?> · <?= e($c['type']) ?> · <?= (int)$c['seats'] ?> ที่นั่ง</div>
        <p class="small flex-grow-1"><?= e($c['description']) ?></p>
        <?php if ($c['status'] !== 'available'): ?>
          <button class="btn btn-secondary btn-sm" disabled>อยู่ระหว่างซ่อมบำรุง</button>
        <?php elseif (!$inNet): ?>
          <button class="btn btn-secondary btn-sm" disabled>ต้องใช้ Wi-Fi มก. เพื่อจอง</button>
        <?php elseif (is_admin()): ?>
          <button class="btn btn-outline-secondary btn-sm" disabled>Admin ไม่สามารถจองได้</button>
        <?php else: ?>
          <a class="btn btn-ku btn-sm" href="<?= e(url('book.php?car=' . (int)$c['id'])) ?>">จองรถคันนี้</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<div class="card shadow-sm"><div class="card-body">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0">ปฏิทินการจอง (คิวรถ)</h4>
    <div class="d-flex align-items-center gap-3">
      <span class="small"><span class="badge" style="background:#198754">&nbsp;</span> อนุมัติแล้ว
            <span class="badge ms-2" style="background:#f0ad00">&nbsp;</span> รออนุมัติ</span>
      <select id="carFilter" class="form-select form-select-sm" style="width:auto">
        <option value="0">ทุกคัน</option>
        <?php foreach ($cars as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['plate_no'] . ' ' . $c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
  </div>
  <div id="calendar"></div>
</div></div>

<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>
<script>
const eventsUrl = <?= json_encode(url('api/events.php')) ?>;
const bookUrl   = <?= json_encode(url('book.php')) ?>;
const canBook   = <?= json_encode($inNet && !is_admin()) ?>;
const carFilter = document.getElementById('carFilter');

const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
  locale: 'th',
  initialView: 'dayGridMonth',
  height: 'auto',
  headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
  dayMaxEvents: 3,
  events: (info, ok, fail) => {
    const q = new URLSearchParams({ start: info.startStr, end: info.endStr, car_id: carFilter.value });
    fetch(eventsUrl + '?' + q).then(r => r.json()).then(ok).catch(fail);
  },
  dateClick: (info) => {
    if (!canBook) { alert('ต้องเชื่อมต่อ Wi-Fi มก. และเข้าสู่ระบบในฐานะผู้ใช้ จึงจะจองได้'); return; }
    const car = carFilter.value > 0 ? '&car=' + carFilter.value : '';
    location.href = bookUrl + '?date=' + info.dateStr.substring(0, 10) + car;
  },
  eventClick: (info) => {
    const p = info.event.extendedProps;
    const fmt = d => d ? d.toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' }) : '';
    alert(info.event.title + '\nสถานะ: ' + p.status + '\n' + fmt(info.event.start) + ' – ' + fmt(info.event.end) + (p.who ? '\n' + p.who : ''));
  }
});
calendar.render();
carFilter.addEventListener('change', () => calendar.refetchEvents());
</script>
<?php $extraScripts = ob_get_clean(); include __DIR__ . '/includes/footer.php'; ?>
