<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        q('DELETE FROM categories WHERE id = ?', [(int) $_POST['delete']]);
        flash('ลบหมวดหมู่เรียบร้อย (ข่าวในหมวดนี้จะกลายเป็น "ไม่ระบุหมวด")');
    } else {
        $name = trim($_POST['name'] ?? '');
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#1e3a8a';
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $nameEn = trim($_POST['name_en'] ?? '') ?: null;
        if ($name === '') {
            flash('กรุณากรอกชื่อหมวดหมู่', 'danger');
        } elseif ($id = (int) ($_POST['id'] ?? 0)) {
            q('UPDATE categories SET name = ?, name_en = ?, color = ?, sort_order = ? WHERE id = ?', [$name, $nameEn, $color, $sort, $id]);
            flash('บันทึกเรียบร้อย');
        } else {
            q('INSERT INTO categories (name, name_en, color, sort_order) VALUES (?, ?, ?, ?)', [$name, $nameEn, $color, $sort]);
            flash('เพิ่มหมวดหมู่เรียบร้อย');
        }
    }
    redirect('admin/categories.php');
}

$rows = q('SELECT c.*, (SELECT COUNT(*) FROM news n WHERE n.category_id = c.id) AS total FROM categories c ORDER BY sort_order, id')->fetchAll();
$edit = ['id' => 0, 'name' => '', 'color' => '#1e3a8a', 'sort_order' => count($rows) + 1];
foreach ($rows as $r) {
    if ((int) $r['id'] === (int) ($_GET['edit'] ?? 0)) {
        $edit = $r;
    }
}

admin_header('หมวดหมู่ข่าว', 'categories');
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><?= $edit['id'] ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่' ?></div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
          <div class="mb-3"><label class="form-label req">ชื่อหมวดหมู่</label><input class="form-control" name="name" value="<?= e($edit['name']) ?>" required></div>
          <div class="mb-3"><label class="form-label">ชื่อภาษาอังกฤษ <span class="badge text-bg-light">EN</span></label><input class="form-control" name="name_en" value="<?= e($edit['name_en'] ?? '') ?>"></div>
          <div class="row g-2 mb-3">
            <div class="col"><label class="form-label">สีป้าย</label><input type="color" class="form-control form-control-color w-100" name="color" value="<?= e($edit['color']) ?>"></div>
            <div class="col"><label class="form-label">ลำดับ</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $edit['sort_order'] ?>"></div>
          </div>
          <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
          <?php if ($edit['id']): ?><a href="categories.php" class="btn btn-light">ยกเลิก</a><?php endif; ?>
        </form>
        <p class="small text-muted mt-3 mb-0">หมวดที่อยู่ลำดับแรกจะแสดงเป็น “ข่าวประชาสัมพันธ์” แบบการ์ดภาพในหน้าแรก หมวดอื่นแสดงในกล่อง “ข่าวประกาศ”</p>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>ลำดับ</th><th>ชื่อหมวดหมู่</th><th class="text-center">จำนวนข่าว</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= (int) $r['sort_order'] ?></td>
                <td><span class="badge" style="background:<?= e($r['color']) ?>"><?= e($r['name']) ?></span> <span class="small text-muted"><?= e($r['name_en']) ?></span></td>
                <td class="text-center"><?= number_format($r['total']) ?></td>
                <td class="text-end text-nowrap">
                  <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                  <?= delete_button('categories.php', $r['id'], 'ลบหมวด "' . $r['name'] . '"?') ?>
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
