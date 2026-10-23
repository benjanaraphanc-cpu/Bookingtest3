<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) redirect(is_admin() ? 'admin/index.php' : 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $user = $st->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'username' => $user['username'],
                             'full_name' => $user['full_name'], 'role' => $user['role']];
        flash('success', 'เข้าสู่ระบบสำเร็จ');
        redirect($user['role'] === 'admin' ? 'admin/index.php' : 'index.php');
    }
    flash('danger', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
}
$pageTitle = 'เข้าสู่ระบบ';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-5">
  <div class="card shadow-sm"><div class="card-body p-4">
    <h4 class="mb-3">เข้าสู่ระบบ</h4>
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">ชื่อผู้ใช้</label><input name="username" class="form-control" required autofocus></div>
      <div class="mb-3"><label class="form-label">รหัสผ่าน</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-ku w-100">เข้าสู่ระบบ</button>
    </form>
    <div class="small text-muted mt-3">ยังไม่มีบัญชี? <a href="<?= e(url('register.php')) ?>">สมัครสมาชิก</a></div>
  </div></div>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
