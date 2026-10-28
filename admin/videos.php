<?php
require_once __DIR__ . '/_layout.php';

/** รับลิงก์ YouTube ได้ทุกรูปแบบ (watch?v=, youtu.be/, shorts/, embed/) หรือรหัสวิดีโอ */
function youtube_id(string $s): ?string
{
    $s = trim($s);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $s)) {
        return $s;
    }
    if (preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $s, $m)) {
        return $m[1];
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        q('DELETE FROM videos WHERE id = ?', [(int) $_POST['delete']]);
        flash('ลบวิดีโอเรียบร้อย');
    } elseif (isset($_POST['toggle'])) {
        q('UPDATE videos SET is_active = 1 - is_active WHERE id = ?', [(int) $_POST['toggle']]);
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $yt = youtube_id($_POST['youtube'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $titleEn = trim($_POST['title_en'] ?? '') ?: null;
        if ($title === '' || !$yt) {
            flash('กรุณากรอกชื่อวิดีโอ และลิงก์ YouTube ที่ถูกต้อง', 'danger');
        } elseif ($id) {
            q('UPDATE videos SET title = ?, title_en = ?, youtube_id = ?, sort_order = ? WHERE id = ?', [$title, $titleEn, $yt, $sort, $id]);
            flash('บันทึกเรียบร้อย');
        } else {
            q('INSERT INTO videos (title, title_en, youtube_id, sort_order) VALUES (?, ?, ?, ?)', [$title, $titleEn, $yt, $sort]);
            flash('เพิ่มวิดีโอเรียบร้อย');
        }
    }
    redirect('admin/videos.php');
}

$rows = q('SELECT * FROM videos ORDER BY sort_order, created_at DESC')->fetchAll();
$edit = ['id' => 0, 'title' => '', 'youtube_id' => '', 'sort_order' => 0];
foreach ($rows as $r) {
    if ((int) $r['id'] === (int) ($_GET['edit'] ?? 0)) {
        $edit = $r;
    }
}

admin_header('วิดีทัศน์ (YouTube)', 'videos');
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header"><?= $edit['id'] ? 'แก้ไขวิดีโอ' : 'เพิ่มวิดีโอ' ?></div><div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
        <div class="mb-3"><label class="form-label req">ลิงก์ YouTube</label>
          <input class="form-control" name="youtube" value="<?= $edit['youtube_id'] ? 'https://youtu.be/' . e($edit['youtube_id']) : '' ?>" placeholder="https://www.youtube.com/watch?v=..." required>
        </div>
        <div class="mb-3"><label class="form-label req">ชื่อวิดีโอ</label><input class="form-control" name="title" value="<?= e($edit['title']) ?>" required></div>
        <div class="mb-3"><label class="form-label">ชื่อวิดีโอ <span class="badge text-bg-light">EN</span></label><input class="form-control" name="title_en" value="<?= e($edit['title_en'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">ลำดับ</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $edit['sort_order'] ?>">
          <div class="form-text">ลำดับแรกจะแสดงเป็นวิดีโอใหญ่ในหน้าแรก</div></div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
        <?php if ($edit['id']): ?><a href="videos.php" class="btn btn-light">ยกเลิก</a><?php endif; ?>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="row g-3">
      <?php foreach ($rows as $r): ?>
        <div class="col-sm-6 col-xl-4">
          <div class="card h-100 <?= $r['is_active'] ? '' : 'opacity-50' ?>">
            <a href="https://youtu.be/<?= e($r['youtube_id']) ?>" target="_blank"><img src="https://i.ytimg.com/vi/<?= e($r['youtube_id']) ?>/hqdefault.jpg" class="card-img-top" style="aspect-ratio:16/9;object-fit:cover" alt=""></a>
            <div class="card-body py-2">
              <div class="small mb-2"><span class="badge text-bg-light">#<?= (int) $r['sort_order'] ?></span> <?= e($r['title']) ?></div>
              <div class="d-flex gap-1 justify-content-end">
                <form method="post" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm <?= $r['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>" name="toggle" value="<?= $r['id'] ?>" title="เปิด/ปิด"><i class="bi <?= $r['is_active'] ? 'bi-eye' : 'bi-eye-slash' ?>"></i></button></form>
                <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <?= delete_button('videos.php', $r['id'], 'ลบวิดีโอนี้?') ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$rows): ?><div class="col-12 text-center text-muted py-5">ยังไม่มีวิดีโอ</div><?php endif; ?>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
