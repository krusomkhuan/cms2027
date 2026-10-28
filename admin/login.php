<?php
require_once __DIR__ . '/../includes/init.php';

if (current_user()) {
    redirect('admin/');
}

$error = '';
$_SESSION['login_fail'] = $_SESSION['login_fail'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ($_SESSION['login_fail'] >= 5 && time() - ($_SESSION['login_fail_at'] ?? 0) < 300) {
        $error = 'เข้าสู่ระบบผิดหลายครั้ง กรุณารอ 5 นาทีแล้วลองใหม่';
    } else {
        $u = q('SELECT * FROM users WHERE username = ? AND is_active = 1', [trim($_POST['username'] ?? '')])->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $u['id'];
            $_SESSION['login_fail'] = 0;
            q('UPDATE users SET last_login = NOW() WHERE id = ?', [$u['id']]);
            redirect('admin/');
        }
        $_SESSION['login_fail']++;
        $_SESSION['login_fail_at'] = time();
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
$theme = setting('theme_color', '#1e3a8a');
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เข้าสู่ระบบ | <?= e(setting('site_name')) ?></title>
  <?php if (setting('favicon', setting('logo'))): ?><link rel="icon" type="image/png" href="<?= e(file_url(setting('favicon', setting('logo')))) ?>"><?php endif; ?>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= asset('assets/css/admin.css') ?>" rel="stylesheet">
  <?php if (preg_match('/^#[0-9a-fA-F]{6}$/', $theme)): ?><style>:root{--primary: <?= $theme ?>;}</style><?php endif; ?>
</head>
<body>
<div class="login-page">
  <div class="card login-card">
    <div class="card-body p-4 p-sm-5">
      <div class="text-center mb-4">
        <i class="bi bi-shield-lock fs-1" style="color:var(--primary)"></i>
        <h1 class="h5 mt-2 mb-0">ระบบจัดการเว็บไซต์</h1>
        <div class="text-muted small"><?= e(setting('site_name')) ?></div>
      </div>
      <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="username">ชื่อผู้ใช้</label>
          <input class="form-control" id="username" name="username" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">รหัสผ่าน</label>
          <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary w-100 py-2">เข้าสู่ระบบ</button>
      </form>
      <?php if (setting('allow_register', '1') === '1'): ?>
        <div class="text-center mt-3 small">ครู / บุคลากรที่ยังไม่มีบัญชี <a href="<?= url('admin/register.php') ?>">สมัครสมาชิก</a></div>
      <?php endif; ?>
      <div class="text-center mt-3"><a href="<?= url() ?>" class="small text-muted"><i class="bi bi-arrow-left"></i> กลับหน้าเว็บไซต์</a></div>
    </div>
  </div>
</div>
</body>
</html>
