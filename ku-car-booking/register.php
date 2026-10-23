<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $err = null;
    if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $username)) $err = 'ชื่อผู้ใช้ต้องเป็น a-z 0-9 _ . ยาว 4-30 ตัว';
    elseif ($name === '')        $err = 'กรุณากรอกชื่อ-นามสกุล';
    elseif (strlen($pass) < 8)   $err = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'อีเมลไม่ถูกต้อง';
    else {
        $ex = db()->prepare('SELECT 1 FROM users WHERE username = ?');
        $ex->execute([$username]);
        if ($ex->fetch()) $err = 'ชื่อผู้ใช้นี้ถูกใช้แล้ว';
    }
    if ($err) { flash('danger', $err); }
    else {
        // สมัครเองได้เฉพาะ role = user เท่านั้น
        db()->prepare("INSERT INTO users (username,password_hash,full_name,email,phone,role) VALUES (?,?,?,?,?, 'user')")
            ->execute([$username, password_hash($pass, PASSWORD_DEFAULT), $name, $email ?: null, $phone ?: null]);
        flash('success', 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ');
        redirect('login.php');
    }
}
$pageTitle = 'สมัครสมาชิก';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-6">
  <div class="card shadow-sm"><div class="card-body p-4">
    <h4 class="mb-3">สมัครสมาชิก</h4>
    <form method="post">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">ชื่อผู้ใช้</label><input name="username" class="form-control" required value="<?= e($_POST['username'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">รหัสผ่าน (≥ 8 ตัว)</label><input type="password" name="password" class="form-control" required></div>
        <div class="col-12"><label class="form-label">ชื่อ-นามสกุล</label><input name="full_name" class="form-control" required value="<?= e($_POST['full_name'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">อีเมล</label><input name="email" type="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">เบอร์โทร</label><input name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>"></div>
      </div>
      <button class="btn btn-ku w-100 mt-3">สมัครสมาชิก</button>
    </form>
  </div></div>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
