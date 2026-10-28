<?php
require_once __DIR__ . '/_layout.php';

$types = personnel_types();
$id = (int) ($_GET['id'] ?? 0);
$p = $id ? q('SELECT * FROM personnel WHERE id = ?', [$id])->fetch() : null;
if ($id && !$p) {
    flash('ไม่พบข้อมูล', 'danger');
    redirect('admin/personnel.php');
}
$p = $p ?: ['prefix' => '', 'firstname' => '', 'lastname' => '', 'position' => '', 'position_en' => '', 'name_en' => '', 'academic_rank' => '', 'type' => 'teacher',
    'department_id' => null, 'education' => '', 'phone' => '', 'email' => '', 'photo' => null, 'sort_order' => 0, 'is_active' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'prefix' => trim($_POST['prefix'] ?? ''),
        'firstname' => trim($_POST['firstname'] ?? ''),
        'lastname' => trim($_POST['lastname'] ?? ''),
        'position' => trim($_POST['position'] ?? ''),
        'position_en' => trim($_POST['position_en'] ?? '') ?: null,
        'name_en' => trim($_POST['name_en'] ?? '') ?: null,
        'academic_rank' => trim($_POST['academic_rank'] ?? ''),
        'type' => isset($types[$_POST['type'] ?? '']) ? $_POST['type'] : 'teacher',
        'department_id' => (int) ($_POST['department_id'] ?? 0) ?: null,
        'education' => trim($_POST['education'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    $p = array_merge($p, $data);
    try {
        if ($data['firstname'] === '' || $data['lastname'] === '') {
            throw new RuntimeException('กรุณากรอกชื่อและนามสกุล');
        }
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('รูปแบบอีเมลไม่ถูกต้อง');
        }
        if ($photo = upload_file('photo', 'personnel', IMG_EXT, 3)) {
            delete_file($p['photo']);
            $data['photo'] = $photo;
        } elseif (isset($_POST['remove_photo'])) {
            delete_file($p['photo']);
            $data['photo'] = null;
        }
        $cols = array_keys($data);
        if ($id) {
            q('UPDATE personnel SET ' . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ' WHERE id = ?', [...array_values($data), $id]);
        } else {
            q('INSERT INTO personnel (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($data));
        }
        flash('บันทึกข้อมูลบุคลากรเรียบร้อย');
        redirect(isset($_POST['add_more']) ? 'admin/personnel_form.php' : 'admin/personnel.php');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'danger');
    }
}

$depts = q('SELECT id, name, type FROM departments ORDER BY type, sort_order, id')->fetchAll();

admin_header($id ? 'แก้ไขข้อมูลบุคลากร' : 'เพิ่มบุคลากร', 'personnel');
?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-3">
      <div class="card"><div class="card-body text-center">
        <img id="photoPreview" src="<?= e(file_url($p['photo'])) ?>" class="preview-img mb-2 <?= $p['photo'] ? '' : 'd-none' ?>" alt="">
        <?php if (!$p['photo']): ?><div class="text-muted mb-2" id="photoPh"><i class="bi bi-person-square" style="font-size:5rem"></i></div><?php endif; ?>
        <input type="file" name="photo" class="form-control form-control-sm" accept="image/*" data-preview="#photoPreview" onchange="document.getElementById('photoPh')?.remove()">
        <div class="form-text">ภาพแนวตั้ง 3:4 ไม่เกิน 3 MB</div>
        <?php if ($p['photo']): ?><div class="form-check mt-2 text-start"><input class="form-check-input" type="checkbox" name="remove_photo" id="rp"><label class="form-check-label small" for="rp">ลบรูป</label></div><?php endif; ?>
      </div></div>
    </div>
    <div class="col-lg-9">
      <div class="card"><div class="card-body">
        <div class="row g-3">
          <div class="col-sm-3"><label class="form-label">คำนำหน้า</label>
            <input class="form-control" name="prefix" list="prefixes" value="<?= e($p['prefix']) ?>">
            <datalist id="prefixes"><option value="นาย"><option value="นาง"><option value="นางสาว"><option value="ดร."><option value="ว่าที่ร้อยตรี"></datalist>
          </div>
          <div class="col-sm-4"><label class="form-label req">ชื่อ</label><input class="form-control" name="firstname" value="<?= e($p['firstname']) ?>" required></div>
          <div class="col-sm-5"><label class="form-label req">นามสกุล</label><input class="form-control" name="lastname" value="<?= e($p['lastname']) ?>" required></div>
          <div class="col-sm-6"><label class="form-label">ตำแหน่ง</label><input class="form-control" name="position" value="<?= e($p['position']) ?>" placeholder="เช่น ครู, หัวหน้าแผนกวิชา..."></div>
          <div class="col-sm-6"><label class="form-label">วิทยฐานะ / ระดับ</label>
            <input class="form-control" name="academic_rank" list="ranks" value="<?= e($p['academic_rank']) ?>">
            <datalist id="ranks"><option value="ครูชำนาญการ"><option value="ครูชำนาญการพิเศษ"><option value="ครูเชี่ยวชาญ"><option value="ครูผู้ช่วย"><option value="พนักงานราชการ"><option value="ครูอัตราจ้าง"></datalist>
          </div>
          <div class="col-sm-6"><label class="form-label">ประเภทบุคลากร</label>
            <select class="form-select" name="type"><?php foreach ($types as $k => $l): ?><option value="<?= $k ?>" <?= $p['type'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
          </div>
          <div class="col-sm-6"><label class="form-label">ฝ่าย / แผนกวิชา</label>
            <select class="form-select" name="department_id">
              <option value="">- ไม่ระบุ -</option>
              <?php foreach ($depts as $d): ?><option value="<?= $d['id'] ?>" <?= (int) $p['department_id'] === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6"><label class="form-label">ชื่อ-สกุล <span class="badge text-bg-light">EN</span></label><input class="form-control" name="name_en" value="<?= e($p['name_en']) ?>" placeholder="เช่น Mr. Somchai Jaidee"></div>
          <div class="col-sm-6"><label class="form-label">ตำแหน่ง <span class="badge text-bg-light">EN</span></label><input class="form-control" name="position_en" value="<?= e($p['position_en']) ?>" placeholder="เช่น Deputy Director for Academic Affairs"></div>
          <div class="col-12"><label class="form-label">วุฒิการศึกษา</label><input class="form-control" name="education" value="<?= e($p['education']) ?>"></div>
          <div class="col-sm-6"><label class="form-label">เบอร์โทร</label><input class="form-control" name="phone" value="<?= e($p['phone']) ?>"></div>
          <div class="col-sm-6"><label class="form-label">อีเมล</label><input type="email" class="form-control" name="email" value="<?= e($p['email']) ?>"></div>
          <div class="col-sm-3"><label class="form-label">ลำดับการแสดง</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $p['sort_order'] ?>"></div>
          <div class="col-sm-9 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="ia" <?= $p['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="ia">แสดงบนเว็บไซต์</label></div></div>
        </div>
        <hr>
        <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
        <?php if (!$id): ?><button class="btn btn-outline-primary" name="add_more" value="1"><i class="bi bi-plus"></i> บันทึกและเพิ่มคนถัดไป</button><?php endif; ?>
        <a href="personnel.php" class="btn btn-light">กลับ</a>
      </div></div>
    </div>
  </div>
</form>
<?php admin_footer(); ?>
