<?php
require_once __DIR__ . '/_layout.php';

$id = (int) ($_GET['id'] ?? 0);
$p = $id ? q('SELECT * FROM pages WHERE id = ?', [$id])->fetch() : null;
if ($id && !$p) {
    redirect('admin/pages.php');
}
$p = $p ?: ['slug' => '', 'title' => '', 'content' => '', 'title_en' => '', 'content_en' => '', 'sort_order' => 10, 'show_in_menu' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $p = array_merge($p, [
        'title' => trim($_POST['title'] ?? ''),
        'slug' => strtolower(trim($_POST['slug'] ?? '')),
        'content' => $_POST['content'] ?? '',
        'title_en' => trim($_POST['title_en'] ?? '') ?: null,
        'content_en' => trim($_POST['content_en'] ?? '') ?: null,
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'show_in_menu' => isset($_POST['show_in_menu']) ? 1 : 0,
    ]);
    if ($p['title'] === '') {
        flash('กรุณากรอกชื่อหน้า', 'danger');
    } elseif (!preg_match('/^[a-z0-9-]{2,100}$/', $p['slug'])) {
        flash('URL ต้องเป็นภาษาอังกฤษตัวเล็ก ตัวเลข หรือ - (2-100 ตัวอักษร)', 'danger');
    } elseif (q('SELECT id FROM pages WHERE slug = ? AND id <> ?', [$p['slug'], $id])->fetch()) {
        flash('URL นี้ถูกใช้แล้ว', 'danger');
    } else {
        $vals = [$p['slug'], $p['title'], $p['content'], $p['sort_order'], $p['show_in_menu'], $p['title_en'], $p['content_en']];
        if ($id) {
            q('UPDATE pages SET slug = ?, title = ?, content = ?, sort_order = ?, show_in_menu = ?, title_en = ?, content_en = ? WHERE id = ?', [...$vals, $id]);
        } else {
            q('INSERT INTO pages (slug, title, content, sort_order, show_in_menu, title_en, content_en) VALUES (?, ?, ?, ?, ?, ?, ?)', $vals);
            $id = (int) db()->lastInsertId();
        }
        flash('บันทึกเรียบร้อย');
        redirect('admin/page_form.php?id=' . $id);
    }
}

admin_header($id ? 'แก้ไขหน้า: ' . $p['title'] : 'เพิ่มหน้าข้อมูล', 'pages');
?>
<form method="post">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-9">
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#langTh">ภาษาไทย</button></li>
        <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#langEn">English <span class="small text-muted">(ไม่บังคับ)</span></button></li>
      </ul>
      <div class="tab-content">
        <div class="card tab-pane fade show active rounded-top-0" id="langTh"><div class="card-body">
          <div class="mb-3"><label class="form-label req">ชื่อหน้า</label><input class="form-control form-control-lg" name="title" value="<?= e($p['title']) ?>" required></div>
          <textarea class="rich" name="content"><?= e($p['content']) ?></textarea>
        </div></div>
        <div class="card tab-pane fade rounded-top-0" id="langEn"><div class="card-body">
          <p class="small text-muted">ถ้าเว้นว่าง หน้าเว็บภาษาอังกฤษจะแสดงเนื้อหาภาษาไทยแทน</p>
          <div class="mb-3"><label class="form-label">Title</label><input class="form-control form-control-lg" name="title_en" value="<?= e($p['title_en']) ?>"></div>
          <textarea class="rich" name="content_en"><?= e($p['content_en']) ?></textarea>
        </div></div>
      </div>
    </div>
    <div class="col-lg-3">
      <div class="card"><div class="card-body">
        <div class="mb-3"><label class="form-label req">URL (slug)</label>
          <input class="form-control" name="slug" value="<?= e($p['slug']) ?>" pattern="[a-z0-9\-]{2,100}" required <?= $p['slug'] === 'contact' ? 'readonly' : '' ?>>
          <div class="form-text">เช่น history, vision</div>
        </div>
        <div class="mb-3"><label class="form-label">ลำดับ</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $p['sort_order'] ?>"></div>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="show_in_menu" id="sm" <?= $p['show_in_menu'] ? 'checked' : '' ?>><label class="form-check-label" for="sm">แสดงในเมนู</label></div>
        <button class="btn btn-primary w-100"><i class="bi bi-save"></i> บันทึก</button>
        <a href="pages.php" class="btn btn-light w-100 mt-2">กลับ</a>
      </div></div>
    </div>
  </div>
</form>
<?php admin_footer(rich_editor_script()); ?>
