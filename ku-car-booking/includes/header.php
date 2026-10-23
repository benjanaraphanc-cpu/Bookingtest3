<?php require_once __DIR__ . '/auth.php'; $u = current_user(); ?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'ระบบจองรถยนต์ มก.') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark ku-nav">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= e(url('index.php')) ?>">🚗 ระบบจองรถยนต์ มก.</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= e(url('index.php')) ?>">หน้าแรก</a></li>
        <?php if ($u && !is_admin()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('book.php')) ?>">จองรถ</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('my_bookings.php')) ?>">การจองของฉัน</a></li>
        <?php endif; ?>
        <?php if ($u && is_admin()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/index.php')) ?>">Admin Dashboard</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2 text-white">
        <?php if ($u): ?>
          <span><?= e($u['full_name']) ?> <span class="badge bg-light text-dark"><?= e($u['role']) ?></span></span>
          <a class="btn btn-sm btn-outline-light" href="<?= e(url('logout.php')) ?>">ออกจากระบบ</a>
        <?php else: ?>
          <a class="btn btn-sm btn-outline-light" href="<?= e(url('login.php')) ?>">เข้าสู่ระบบ</a>
          <a class="btn btn-sm btn-light" href="<?= e(url('register.php')) ?>">สมัครสมาชิก</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<main class="container my-4">
<?php foreach (flashes() as [$t, $m]): ?>
  <div class="alert alert-<?= e($t) ?> alert-dismissible fade show"><?= e($m) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endforeach; ?>
