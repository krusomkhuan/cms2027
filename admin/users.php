<?php
require_once __DIR__ . '/_layout.php';
require_admin();

$roles = ['admin' => role_label('admin'), 'editor' => role_label('editor'), 'reporter' => role_label('reporter')];
$roleBadge = ['admin' => 'text-bg-danger', 'editor' => 'text-bg-primary', 'reporter' => 'text-bg-info'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['save_register'])) {
        save_setting('allow_register', isset($_POST['allow_register']) ? '1' : '0');
        save_setting('register_code', mb_substr(trim($_POST['register_code'] ?? ''), 0, 50));
        save_setting('reporter_autopublish', isset($_POST['reporter_autopublish']) ? '1' : '0');
        flash('บันทึกการตั้งค่าการสมัครสมาชิกเรียบร้อย');
    } elseif (isset($_POST['delete'])) {
        if ((int) $_POST['delete'] === (int) $me['id']) {
            flash('ไม่สามารถลบบัญชีของตัวเองได้', 'danger');
        } else {
            q('DELETE FROM users WHERE id = ?', [(int) $_POST['delete']]);
            flash('ลบผู้ใช้เรียบร้อย (ข่าวที่เขียนไว้ยังอยู่ แต่จะไม่แสดงชื่อผู้เขียน)');
        }
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $role = isset($roles[$_POST['role'] ?? '']) ? $_POST['role'] : 'editor';
        $active = isset($_POST['is_active']) ? 1 : 0;
        $pass = $_POST['password'] ?? '';
        if ($id === (int) $me['id']) {
            $role = 'admin';
            $active = 1;
        }
        if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username) || $fullname === '') {
            flash('ชื่อผู้ใช้ต้องเป็น a-z, 0-9, _ หรือ . (3-50 ตัว) และต้องกรอกชื่อ-สกุล', 'danger');
        } elseif (q('SELECT id FROM users WHERE username = ? AND id <> ?', [$username, $id])->fetch()) {
            flash('ชื่อผู้ใช้นี้มีอยู่แล้ว', 'danger');
        } elseif ((!$id || $pass !== '') && strlen($pass) < 8) {
            flash('รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร', 'danger');
        } else {
            if ($id) {
                q('UPDATE users SET username = ?, fullname = ?, role = ?, is_active = ? WHERE id = ?', [$username, $fullname, $role, $active, $id]);
                if ($pass !== '') {
                    q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
                }
            } else {
                q('INSERT INTO users (username, fullname, role, is_active, password_hash) VALUES (?, ?, ?, ?, ?)', [$username, $fullname, $role, $active, password_hash($pass, PASSWORD_DEFAULT)]);
            }
            flash('บันทึกผู้ใช้เรียบร้อย');
        }
    }
    redirect('admin/users.php');
}

$roleFilter = isset($roles[$_GET['role'] ?? '']) ? $_GET['role'] : '';
$rows = q('SELECT u.*, (SELECT COUNT(*) FROM news n WHERE n.author_id = u.id) AS news_count FROM users u'
    . ($roleFilter ? ' WHERE u.role = ?' : '')
    . " ORDER BY FIELD(u.role, 'admin', 'editor', 'reporter'), u.created_at DESC", $roleFilter ? [$roleFilter] : [])->fetchAll();
$roleCounts = q('SELECT role, COUNT(*) FROM users GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);
$edit = ['id' => 0, 'username' => '', 'fullname' => '', 'role' => 'editor', 'is_active' => 1];
if (!empty($_GET['edit'])) {
    $edit = q('SELECT * FROM users WHERE id = ?', [(int) $_GET['edit']])->fetch() ?: $edit;
}

admin_header('ผู้ใช้งาน / สมาชิก', 'users');
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header"><?= $edit['id'] ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้' ?></div><div class="card-body">
      <form method="post" autocomplete="off">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
        <div class="mb-3"><label class="form-label req">ชื่อผู้ใช้</label><input class="form-control" name="username" value="<?= e($edit['username']) ?>" required></div>
        <div class="mb-3"><label class="form-label req">ชื่อ-สกุล</label><input class="form-control" name="fullname" value="<?= e($edit['fullname']) ?>" required></div>
        <div class="mb-3"><label class="form-label <?= $edit['id'] ? '' : 'req' ?>">รหัสผ่าน</label><input type="password" class="form-control" name="password" autocomplete="new-password" <?= $edit['id'] ? 'placeholder="เว้นว่างหากไม่เปลี่ยน"' : 'required' ?> minlength="8"></div>
        <div class="mb-3"><label class="form-label">สิทธิ์</label>
          <select class="form-select" name="role" <?= (int) $edit['id'] === (int) $me['id'] ? 'disabled' : '' ?>><?php foreach ($roles as $k => $l): ?><option value="<?= $k ?>" <?= $edit['role'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
          <div class="form-text">ผู้แก้ไขเนื้อหา: จัดการข่าวและข้อมูลทั้งหมด ยกเว้นตั้งค่าเว็บและผู้ใช้<br>ผู้สื่อข่าว: เขียนข่าวของตัวเองได้อย่างเดียว ข่าวต้องผ่านการตรวจ</div>
        </div>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="is_active" id="act" <?= $edit['is_active'] ? 'checked' : '' ?> <?= (int) $edit['id'] === (int) $me['id'] ? 'disabled' : '' ?>><label class="form-check-label" for="act">เปิดใช้งาน</label></div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
        <?php if ($edit['id']): ?><a href="users.php" class="btn btn-light">ยกเลิก</a><?php endif; ?>
      </form>
      <?php if ($edit['id']): ?>
        <hr><div class="small text-muted">
          <?php if (!empty($edit['position'])): ?><div><i class="bi bi-briefcase"></i> <?= e($edit['position']) ?></div><?php endif; ?>
          <?php if (!empty($edit['email'])): ?><div><i class="bi bi-envelope"></i> <a href="mailto:<?= e($edit['email']) ?>"><?= e($edit['email']) ?></a></div><?php endif; ?>
          <?php if (!empty($edit['phone'])): ?><div><i class="bi bi-telephone"></i> <?= e($edit['phone']) ?></div><?php endif; ?>
          <div><i class="bi bi-calendar-plus"></i> สร้างบัญชีเมื่อ <?= thai_date($edit['created_at'], true) ?></div>
        </div>
      <?php endif; ?>
    </div></div>

    <div class="card mt-3"><div class="card-header"><i class="bi bi-person-plus"></i> การสมัครสมาชิก (ครู / บุคลากร)</div><div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="save_register" value="1">
        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="allow_register" id="ar" <?= setting('allow_register', '1') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="ar">เปิดรับสมัครสมาชิก</label></div>
        <div class="mb-2"><label class="form-label small mb-1" for="rc">รหัสลงทะเบียน <span class="text-muted">(เว้นว่าง = ไม่ต้องใช้รหัส)</span></label>
          <input class="form-control form-control-sm" id="rc" name="register_code" value="<?= e(setting('register_code')) ?>" maxlength="50" autocomplete="off">
          <div class="form-text">แจกรหัสนี้ให้เฉพาะครู/บุคลากร เพื่อกันคนภายนอกสมัคร</div></div>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="reporter_autopublish" id="ap" <?= setting('reporter_autopublish') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="ap">ข่าวจากสมาชิกเผยแพร่ได้ทันที (ไม่ต้องรอตรวจ)</label></div>
        <button class="btn btn-sm btn-primary"><i class="bi bi-save"></i> บันทึก</button>
        <a href="<?= url('admin/register.php') ?>" target="_blank" class="btn btn-sm btn-light">ดูหน้าสมัคร <i class="bi bi-box-arrow-up-right"></i></a>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <ul class="nav nav-pills mb-3 gap-1">
      <li class="nav-item"><a class="nav-link py-1 <?= $roleFilter ? '' : 'active' ?>" href="users.php">ทั้งหมด (<?= array_sum($roleCounts) ?>)</a></li>
      <?php foreach ($roles as $k => $l): ?><li class="nav-item"><a class="nav-link py-1 <?= $roleFilter === $k ? 'active' : '' ?>" href="?role=<?= $k ?>"><?= $l ?> (<?= $roleCounts[$k] ?? 0 ?>)</a></li><?php endforeach; ?>
    </ul>
    <div class="card"><div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>ชื่อผู้ใช้</th><th>ชื่อ-สกุล</th><th>สิทธิ์</th><th class="text-center">ข่าว</th><th>เข้าระบบล่าสุด</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="<?= $r['is_active'] ? '' : 'opacity-50' ?>">
              <td><?= e($r['username']) ?></td>
              <td><?= e($r['fullname']) ?><?php if ($r['position'] || $r['email']): ?><div class="small text-muted"><?= e($r['position'] ?: $r['email']) ?></div><?php endif; ?></td>
              <td><span class="badge <?= $roleBadge[$r['role']] ?? 'text-bg-secondary' ?>"><?= $roles[$r['role']] ?></span></td>
              <td class="text-center"><?= number_format($r['news_count']) ?></td>
              <td class="small"><?= $r['last_login'] ? thai_date($r['last_login'], true) : '-' ?></td>
              <td class="text-end text-nowrap">
                <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <?= (int) $r['id'] === (int) $me['id'] ? '' : delete_button('users.php', $r['id'], 'ลบผู้ใช้ ' . $r['username'] . '?') ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">ไม่มีผู้ใช้ในกลุ่มนี้</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div></div>
  </div>
</div>
<?php admin_footer(); ?>
