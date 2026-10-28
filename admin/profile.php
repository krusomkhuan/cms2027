<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new = $_POST['new_password'] ?? '';
    if ($fullname === '') {
        flash('กรุณากรอกชื่อ-สกุล', 'danger');
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('รูปแบบอีเมลไม่ถูกต้อง', 'danger');
    } elseif ($email !== '' && q('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $me['id']])->fetch()) {
        flash('อีเมลนี้ถูกใช้โดยบัญชีอื่นแล้ว', 'danger');
    } elseif ($new !== '' && !password_verify($_POST['current_password'] ?? '', $me['password_hash'])) {
        flash('รหัสผ่านปัจจุบันไม่ถูกต้อง', 'danger');
    } elseif ($new !== '' && strlen($new) < 8) {
        flash('รหัสผ่านใหม่ต้องยาวอย่างน้อย 8 ตัวอักษร', 'danger');
    } elseif ($new !== '' && $new !== ($_POST['confirm_password'] ?? '')) {
        flash('ยืนยันรหัสผ่านใหม่ไม่ตรงกัน', 'danger');
    } else {
        q('UPDATE users SET fullname = ?, email = ?, phone = ?, position = ? WHERE id = ?', [$fullname, $email ?: null,
            mb_substr(trim($_POST['phone'] ?? ''), 0, 50) ?: null, mb_substr(trim($_POST['position'] ?? ''), 0, 150) ?: null, $me['id']]);
        if ($new !== '') {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
        }
        flash('บันทึกข้อมูลเรียบร้อย');
    }
    redirect('admin/profile.php');
}

admin_header('ข้อมูลส่วนตัว / เปลี่ยนรหัสผ่าน', 'profile');
?>
<div class="card" style="max-width:560px">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">ชื่อผู้ใช้</label><input class="form-control" value="<?= e($me['username']) ?>" disabled></div>
      <div class="mb-3"><label class="form-label req">ชื่อ-สกุล</label><input class="form-control" name="fullname" value="<?= e($me['fullname']) ?>" required></div>
      <div class="mb-3"><label class="form-label">ตำแหน่ง / ฝ่าย / แผนกวิชา</label><input class="form-control" name="position" value="<?= e($me['position'] ?? '') ?>" maxlength="150"></div>
      <div class="row g-2 mb-3">
        <div class="col-sm-7"><label class="form-label">อีเมล</label><input type="email" class="form-control" name="email" value="<?= e($me['email'] ?? '') ?>" maxlength="150"></div>
        <div class="col-sm-5"><label class="form-label">เบอร์โทร</label><input class="form-control" name="phone" value="<?= e($me['phone'] ?? '') ?>" maxlength="50"></div>
      </div>
      <div class="mb-3"><label class="form-label">สิทธิ์การใช้งาน</label><div><span class="badge text-bg-light border"><?= role_label($me['role']) ?></span></div></div>
      <hr>
      <p class="small text-muted">เว้นว่างหากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
      <div class="mb-3"><label class="form-label">รหัสผ่านปัจจุบัน</label><input type="password" class="form-control" name="current_password" autocomplete="current-password"></div>
      <div class="mb-3"><label class="form-label">รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)</label><input type="password" class="form-control" name="new_password" autocomplete="new-password"></div>
      <div class="mb-3"><label class="form-label">ยืนยันรหัสผ่านใหม่</label><input type="password" class="form-control" name="confirm_password" autocomplete="new-password"></div>
      <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
