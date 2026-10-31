<?php
require_once __DIR__ . '/_layout.php';

$typeLabels = ['division' => 'ฝ่าย / งาน', 'academic' => 'แผนกวิชา'];

// เพิ่มช่องเว็บไซต์ของแผนกให้ฐานข้อมูลเดิมอัตโนมัติ (ไม่ต้องนำเข้า SQL)
if (!q("SHOW COLUMNS FROM departments LIKE 'website'")->fetch()) {
    q('ALTER TABLE departments ADD COLUMN website VARCHAR(255) NULL AFTER description_en');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        q('DELETE FROM departments WHERE id = ?', [(int) $_POST['delete']]);
        flash('ลบเรียบร้อย (บุคลากรในฝ่าย/แผนกนี้จะเป็น "ไม่ระบุ" และข้อมูลผู้เรียนของแผนกนี้จะถูกลบ)');
    } else {
        $name = trim($_POST['name'] ?? '');
        $type = isset($typeLabels[$_POST['type'] ?? '']) ? $_POST['type'] : 'academic';
        $desc = trim($_POST['description'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $nameEn = trim($_POST['name_en'] ?? '') ?: null;
        $descEn = trim($_POST['description_en'] ?? '') ?: null;
        $website = trim($_POST['website'] ?? '');
        $website = preg_match('#^https?://#i', $website) ? mb_substr($website, 0, 255) : null;
        if ($name === '') {
            flash('กรุณากรอกชื่อ', 'danger');
        } elseif ($id = (int) ($_POST['id'] ?? 0)) {
            q('UPDATE departments SET name = ?, type = ?, description = ?, sort_order = ?, name_en = ?, description_en = ?, website = ? WHERE id = ?', [$name, $type, $desc, $sort, $nameEn, $descEn, $website, $id]);
            flash('บันทึกเรียบร้อย');
        } else {
            q('INSERT INTO departments (name, type, description, sort_order, name_en, description_en, website) VALUES (?, ?, ?, ?, ?, ?, ?)', [$name, $type, $desc, $sort, $nameEn, $descEn, $website]);
            flash('เพิ่มเรียบร้อย');
        }
    }
    redirect('admin/departments.php');
}

$rows = q('SELECT d.*, (SELECT COUNT(*) FROM personnel p WHERE p.department_id = d.id) AS total FROM departments d ORDER BY type, sort_order, id')->fetchAll();
$edit = ['id' => 0, 'name' => '', 'type' => 'academic', 'description' => '', 'sort_order' => 0];
foreach ($rows as $r) {
    if ((int) $r['id'] === (int) ($_GET['edit'] ?? 0)) {
        $edit = $r;
    }
}

admin_header('ฝ่าย / แผนกวิชา', 'departments');
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><?= $edit['id'] ? 'แก้ไข' : 'เพิ่มฝ่าย / แผนกวิชา' ?></div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
          <div class="mb-3"><label class="form-label req">ชื่อ</label><input class="form-control" name="name" value="<?= e($edit['name']) ?>" required></div>
          <div class="mb-3"><label class="form-label">ชื่อภาษาอังกฤษ <span class="badge text-bg-light">EN</span></label><input class="form-control" name="name_en" value="<?= e($edit['name_en'] ?? '') ?>"></div>
          <div class="row g-2 mb-3">
            <div class="col-7"><label class="form-label">ประเภท</label>
              <select class="form-select" name="type"><?php foreach ($typeLabels as $k => $l): ?><option value="<?= $k ?>" <?= $edit['type'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-5"><label class="form-label">ลำดับ</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $edit['sort_order'] ?>"></div>
          </div>
          <div class="mb-3"><label class="form-label">คำอธิบาย</label><textarea class="form-control" name="description" rows="3"><?= e($edit['description']) ?></textarea></div>
          <div class="mb-3"><label class="form-label">คำอธิบาย <span class="badge text-bg-light">EN</span></label><textarea class="form-control" name="description_en" rows="3"><?= e($edit['description_en'] ?? '') ?></textarea></div>
          <div class="mb-3"><label class="form-label">เว็บไซต์ของแผนก</label><input type="url" class="form-control" name="website" value="<?= e($edit['website'] ?? '') ?>" placeholder="https://...">
            <div class="form-text">แสดงในเมนู “เว็บไซต์แผนกวิชา” — ถ้าเว้นว่าง เมนูจะลิงก์ไปหน้าข้อมูลแผนกในเว็บนี้</div></div>
          <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
          <?php if ($edit['id']): ?><a href="departments.php" class="btn btn-light">ยกเลิก</a><?php endif; ?>
        </form>
        <p class="small text-muted mt-3 mb-0">“แผนกวิชา” จะใช้ในการบันทึกจำนวนผู้เรียน</p>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>ลำดับ</th><th>ชื่อ</th><th>ประเภท</th><th class="text-center">บุคลากร</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= (int) $r['sort_order'] ?></td>
                <td><?= e($r['name']) ?><div class="small text-muted"><?= e($r['name_en']) ?></div><?php if (!empty($r['website'])): ?><a class="small" href="<?= e($r['website']) ?>" target="_blank" rel="noopener"><i class="bi bi-globe"></i> <?= e($r['website']) ?></a><?php endif; ?></td>
                <td><span class="badge <?= $r['type'] === 'academic' ? 'text-bg-info' : 'text-bg-warning' ?>"><?= $typeLabels[$r['type']] ?></span></td>
                <td class="text-center"><?= number_format($r['total']) ?></td>
                <td class="text-end text-nowrap">
                  <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                  <?= delete_button('departments.php', $r['id'], 'ลบ "' . $r['name'] . '"? ข้อมูลผู้เรียนของแผนกนี้จะถูกลบด้วย') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
