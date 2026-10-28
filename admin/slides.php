<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (isset($_POST['delete'])) {
            $s = q('SELECT image FROM slides WHERE id = ?', [(int) $_POST['delete']])->fetch();
            if ($s) {
                delete_file($s['image']);
                q('DELETE FROM slides WHERE id = ?', [(int) $_POST['delete']]);
                flash('ลบสไลด์เรียบร้อย');
            }
        } elseif (isset($_POST['toggle'])) {
            q('UPDATE slides SET is_active = 1 - is_active WHERE id = ?', [(int) $_POST['toggle']]);
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $link = trim($_POST['link'] ?? '');
            if ($link !== '' && !preg_match('#^(https?://|/)#i', $link)) {
                throw new RuntimeException('ลิงก์ต้องขึ้นต้นด้วย http://, https:// หรือ /');
            }
            $vals = [trim($_POST['title'] ?? ''), trim($_POST['title_en'] ?? '') ?: null, $link, (int) ($_POST['sort_order'] ?? 0)];
            $img = upload_file('image', 'slides', IMG_EXT, 5);
            if ($id) {
                q('UPDATE slides SET title = ?, title_en = ?, link = ?, sort_order = ? WHERE id = ?', [...$vals, $id]);
                if ($img) {
                    delete_file(q('SELECT image FROM slides WHERE id = ?', [$id])->fetchColumn() ?: null);
                    q('UPDATE slides SET image = ? WHERE id = ?', [$img, $id]);
                }
            } else {
                if (!$img) {
                    throw new RuntimeException('กรุณาเลือกรูปภาพ');
                }
                q('INSERT INTO slides (title, title_en, link, sort_order, image) VALUES (?, ?, ?, ?, ?)', [...$vals, $img]);
            }
            flash('บันทึกสไลด์เรียบร้อย');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'danger');
    }
    redirect('admin/slides.php');
}

$rows = q('SELECT * FROM slides ORDER BY sort_order, id')->fetchAll();
$edit = ['id' => 0, 'title' => '', 'link' => '', 'sort_order' => count($rows) + 1, 'image' => null];
foreach ($rows as $r) {
    if ((int) $r['id'] === (int) ($_GET['edit'] ?? 0)) {
        $edit = $r;
    }
}

admin_header('สไลด์หน้าแรก (แบนเนอร์)', 'slides');
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header"><?= $edit['id'] ? 'แก้ไขสไลด์' : 'เพิ่มสไลด์' ?></div><div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
        <img id="slidePrev" src="<?= e(file_url($edit['image'])) ?>" class="preview-img mb-2 <?= $edit['image'] ? '' : 'd-none' ?>" alt="">
        <div class="mb-3"><label class="form-label <?= $edit['id'] ? '' : 'req' ?>">รูปภาพ</label><input type="file" class="form-control" name="image" accept="image/*" data-preview="#slidePrev" <?= $edit['id'] ? '' : 'required' ?>>
          <div class="form-text">แนะนำขนาด 1920×700 px ไม่เกิน 5 MB</div></div>
        <div class="mb-3"><label class="form-label">คำบรรยาย</label><input class="form-control" name="title" value="<?= e($edit['title']) ?>"></div>
        <div class="mb-3"><label class="form-label">คำบรรยาย <span class="badge text-bg-light">EN</span></label><input class="form-control" name="title_en" value="<?= e($edit['title_en'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">ลิงก์เมื่อคลิก</label><input class="form-control" name="link" value="<?= e($edit['link']) ?>" placeholder="https://..."></div>
        <div class="mb-3"><label class="form-label">ลำดับ</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $edit['sort_order'] ?>"></div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
        <?php if ($edit['id']): ?><a href="slides.php" class="btn btn-light">ยกเลิก</a><?php endif; ?>
      </form>
      <p class="small text-muted mt-3 mb-0">หากไม่มีสไลด์ที่เปิดใช้งาน หน้าแรกจะแสดงแบนเนอร์ชื่อวิทยาลัยพร้อมผู้อำนวยการแทน</p>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="row g-3">
      <?php foreach ($rows as $r): ?>
        <div class="col-md-6">
          <div class="card h-100 <?= $r['is_active'] ? '' : 'opacity-50' ?>">
            <img src="<?= e(file_url($r['image'])) ?>" class="card-img-top" style="aspect-ratio:16/6;object-fit:cover" alt="">
            <div class="card-body py-2 d-flex align-items-center gap-2">
              <span class="badge text-bg-light">#<?= (int) $r['sort_order'] ?></span>
              <span class="small flex-grow-1 text-truncate"><?= e($r['title'] ?: '-') ?></span>
              <form method="post" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm <?= $r['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>" name="toggle" value="<?= $r['id'] ?>" title="เปิด/ปิด"><i class="bi <?= $r['is_active'] ? 'bi-eye' : 'bi-eye-slash' ?>"></i></button></form>
              <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?= delete_button('slides.php', $r['id'], 'ลบสไลด์นี้?') ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$rows): ?><div class="col-12 text-center text-muted py-5">ยังไม่มีสไลด์</div><?php endif; ?>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
