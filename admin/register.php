<?php
// สมัครสมาชิกสำหรับครู/บุคลากร — ได้สิทธิ์ "ผู้สื่อข่าว" ใช้งานได้ทันที (ข่าวต้องผ่านการตรวจ เว้นแต่เปิดเผยแพร่อัตโนมัติ)
require_once __DIR__ . '/../includes/init.php';

if (current_user()) {
    redirect('admin/');
}
$open = setting('allow_register', '1') === '1';
$needCode = setting('register_code') !== '';
$form = ['fullname' => '', 'position' => '', 'email' => '', 'phone' => '', 'username' => ''];
$errors = [];

if ($open && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($form as $k => $_) {
        $form[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $pass = (string) ($_POST['password'] ?? '');
    // honeypot + จำกัด 3 ครั้ง / ชั่วโมง ต่อ session
    $recent = array_filter($_SESSION['register_times'] ?? [], fn($ts) => $ts > time() - 3600);
    if (($_POST['website'] ?? '') !== '' || count($recent) >= 3) {
        $errors[] = 'สมัครสมาชิกไม่สำเร็จ กรุณาลองใหม่ภายหลัง';
    }
    if ($needCode && !hash_equals(setting('register_code'), trim((string) ($_POST['code'] ?? '')))) {
        $errors[] = 'รหัสลงทะเบียนไม่ถูกต้อง (สอบถามได้ที่งานประชาสัมพันธ์)';
    }
    if ($form['fullname'] === '' || mb_strlen($form['fullname']) > 150) {
        $errors[] = 'กรุณากรอกชื่อ-สกุล';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'กรุณากรอกอีเมลให้ถูกต้อง';
    } elseif (q('SELECT id FROM users WHERE email = ?', [$form['email']])->fetch()) {
        $errors[] = 'อีเมลนี้สมัครสมาชิกแล้ว';
    }
    if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $form['username'])) {
        $errors[] = 'ชื่อผู้ใช้ต้องเป็น a-z, 0-9, _ หรือ . ยาว 3-50 ตัวอักษร';
    } elseif (q('SELECT id FROM users WHERE username = ?', [$form['username']])->fetch()) {
        $errors[] = 'ชื่อผู้ใช้นี้มีผู้ใช้แล้ว';
    }
    if (strlen($pass) < 8) {
        $errors[] = 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร';
    } elseif ($pass !== ($_POST['password2'] ?? '')) {
        $errors[] = 'ยืนยันรหัสผ่านไม่ตรงกัน';
    }
    if (empty($_POST['consent'])) {
        $errors[] = 'กรุณายอมรับเงื่อนไขการใช้งาน';
    }

    if (!$errors) {
        q("INSERT INTO users (username, password_hash, fullname, email, phone, position, role) VALUES (?, ?, ?, ?, ?, ?, 'reporter')",
            [$form['username'], password_hash($pass, PASSWORD_DEFAULT), $form['fullname'], $form['email'],
                mb_substr($form['phone'], 0, 50) ?: null, mb_substr($form['position'], 0, 150) ?: null]);
        $recent[] = time();
        $_SESSION['register_times'] = array_values($recent);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) db()->lastInsertId();
        q('UPDATE users SET last_login = NOW() WHERE id = ?', [$_SESSION['uid']]);
        flash('สมัครสมาชิกเรียบร้อย ยินดีต้อนรับ ' . $form['fullname'] . ' — เริ่มเขียนข่าวได้ที่เมนู “ข่าวของฉัน”');
        redirect('admin/');
    }
}
$theme = setting('theme_color', '#1e3a8a');
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>สมัครสมาชิก | <?= e(setting('site_name')) ?></title>
  <?php if (setting('favicon', setting('logo'))): ?><link rel="icon" type="image/png" href="<?= e(file_url(setting('favicon', setting('logo')))) ?>"><?php endif; ?>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= asset('assets/css/admin.css') ?>" rel="stylesheet">
  <?php if (preg_match('/^#[0-9a-fA-F]{6}$/', $theme)): ?><style>:root{--primary: <?= $theme ?>;}</style><?php endif; ?>
</head>
<body>
<div class="login-page">
  <div class="card login-card" style="max-width:560px">
    <div class="card-body p-4 p-sm-5">
      <div class="text-center mb-4">
        <?php if (setting('logo')): ?><img src="<?= e(file_url(setting('logo'))) ?>" alt="" style="width:72px;height:72px;object-fit:contain"><?php endif; ?>
        <h1 class="h5 mt-2 mb-0">สมัครสมาชิก (ครู / บุคลากร)</h1>
        <div class="text-muted small"><?= e(setting('site_name')) ?></div>
      </div>

      <?php if (!$open): ?>
        <div class="alert alert-secondary">ขณะนี้ปิดรับสมัครสมาชิก กรุณาติดต่อผู้ดูแลระบบ</div>
      <?php else: ?>
        <div class="alert alert-info small py-2">
          <i class="bi bi-info-circle"></i> สมาชิกจะได้สิทธิ์ <b>ผู้สื่อข่าว</b> เขียนและส่งข่าวประชาสัมพันธ์ได้ทันที
          <?= setting('reporter_autopublish') === '1' ? '' : 'โดยข่าวจะเผยแพร่หลังผู้ดูแลตรวจสอบแล้ว' ?>
        </div>
        <?php if ($errors): ?><div class="alert alert-danger py-2 small"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
        <form method="post" autocomplete="off">
          <?= csrf_field() ?>
          <div class="d-none" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
          <div class="row g-3">
            <div class="col-12"><label class="form-label req" for="fullname">ชื่อ-สกุล</label><input class="form-control" id="fullname" name="fullname" value="<?= e($form['fullname']) ?>" required maxlength="150"></div>
            <div class="col-12"><label class="form-label" for="position">ตำแหน่ง / ฝ่าย / แผนกวิชา</label><input class="form-control" id="position" name="position" value="<?= e($form['position']) ?>" maxlength="150" placeholder="เช่น ครูแผนกวิชาช่างยนต์"></div>
            <div class="col-sm-7"><label class="form-label req" for="email">อีเมล</label><input type="email" class="form-control" id="email" name="email" value="<?= e($form['email']) ?>" required maxlength="150"></div>
            <div class="col-sm-5"><label class="form-label" for="phone">เบอร์โทร</label><input class="form-control" id="phone" name="phone" value="<?= e($form['phone']) ?>" maxlength="50"></div>
            <div class="col-12"><label class="form-label req" for="username">ชื่อผู้ใช้ (ใช้เข้าสู่ระบบ)</label><input class="form-control" id="username" name="username" value="<?= e($form['username']) ?>" required pattern="[a-zA-Z0-9_.]{3,50}" autocomplete="username">
              <div class="form-text">ภาษาอังกฤษ ตัวเลข _ หรือ . ยาว 3-50 ตัว</div></div>
            <div class="col-sm-6"><label class="form-label req" for="password">รหัสผ่าน</label><input type="password" class="form-control" id="password" name="password" required minlength="8" autocomplete="new-password"></div>
            <div class="col-sm-6"><label class="form-label req" for="password2">ยืนยันรหัสผ่าน</label><input type="password" class="form-control" id="password2" name="password2" required minlength="8" autocomplete="new-password"></div>
            <?php if ($needCode): ?>
              <div class="col-12"><label class="form-label req" for="code">รหัสลงทะเบียน</label><input class="form-control" id="code" name="code" required autocomplete="off">
                <div class="form-text">ขอรหัสได้ที่งานประชาสัมพันธ์ของวิทยาลัย</div></div>
            <?php endif; ?>
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="consent" id="consent" required>
              <label class="form-check-label small" for="consent">ข้าพเจ้าเป็นครูหรือบุคลากรของวิทยาลัย และจะลงเฉพาะข่าวที่ถูกต้อง เหมาะสม ไม่ละเมิดลิขสิทธิ์หรือข้อมูลส่วนบุคคลของผู้อื่น และรับทราบ<a href="<?= url('page.php?slug=privacy') ?>" target="_blank">นโยบายคุ้มครองข้อมูลส่วนบุคคล</a></label></div></div>
            <div class="col-12"><button class="btn btn-primary w-100 py-2"><i class="bi bi-person-plus"></i> สมัครสมาชิก</button></div>
          </div>
        </form>
      <?php endif; ?>
      <div class="text-center mt-4 small">
        มีบัญชีแล้ว? <a href="<?= url('admin/login.php') ?>">เข้าสู่ระบบ</a> · <a href="<?= url() ?>" class="text-muted">กลับหน้าเว็บไซต์</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
