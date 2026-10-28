<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (isset($_POST['delete'])) {
            $d = q('SELECT file FROM downloads WHERE id = ?', [(int) $_POST['delete']])->fetch();
            if ($d) {
                delete_file($d['file']);
                q('DELETE FROM downloads WHERE id = ?', [(int) $_POST['delete']]);
                flash('ลบเอกสารเรียบร้อย');
            }
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $link = trim($_POST['url'] ?? '');
            $featured = isset($_POST['is_featured']) ? 1 : 0;
            $titleEn = trim($_POST['title_en'] ?? '') ?: null;
            if ($title === '') {
                throw new RuntimeException('กรุณากรอกชื่อเอกสาร');
            }
            if ($link !== '' && !preg_match('#^https?://#i', $link)) {
                throw new RuntimeException('ลิงก์ต้องขึ้นต้นด้วย http:// หรือ https://');
            }
            $file = upload_file('file', 'downloads', DOC_EXT, 20);
            $old = $id ? q('SELECT * FROM downloads WHERE id = ?', [$id])->fetch() : null;
            if (!$file && !$link && !($old['file'] ?? null)) {
                throw new RuntimeException('กรุณาเลือกไฟล์ หรือใส่ลิงก์อย่างใดอย่างหนึ่ง');
            }
            if ($old) {
                if ($file) {
                    delete_file($old['file']);
                }
                q('UPDATE downloads SET title = ?, title_en = ?, url = ?, is_featured = ?, file = ? WHERE id = ?', [$title, $titleEn, $link ?: null, $featured, $file ?: $old['file'], $id]);
            } else {
                q('INSERT INTO downloads (title, title_en, url, is_featured, file) VALUES (?, ?, ?, ?, ?)', [$title, $titleEn, $link ?: null, $featured, $file]);
            }
            flash('บันทึกเอกสารเรียบร้อย');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'danger');
    }
    redirect('admin/downloads.php');
}

$rows = q('SELECT * FROM downloads ORDER BY created_at DESC, id DESC')->fetchAll();
$edit = ['id' => 0, 'title' => '', 'url' => '', 'file' => null, 'is_featured' => 0];
foreach ($rows as $r) {
    if ((int) $r['id'] === (int) ($_GET['edit'] ?? 0)) {
        $edit = $r;
    }
}

admin_header('เอกสารดาวน์โหลด', 'downloads');
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header"><?= $edit['id'] ? 'แก้ไขเอกสาร' : 'เพิ่มเอกสาร' ?></div><div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
        <div class="mb-3"><label class="form-label req">ชื่อเอกสาร</label><input class="form-control" name="title" value="<?= e($edit['title']) ?>" required></div>
        <div class="mb-3"><label class="form-label">ชื่อเอกสาร <span class="badge text-bg-light">EN</span></label><input class="form-control" name="title_en" value="<?= e($edit['title_en'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label">ไฟล์</label>
          <?php if ($edit['file']): ?><div class="small mb-1"><a href="<?= e(file_url($edit['file'])) ?>" target="_blank"><i class="bi bi-paperclip"></i> <?= e(basename($edit['file'])) ?></a></div><?php endif; ?>
          <input type="file" class="form-control" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.jpg,.jpeg,.png">
          <div class="form-text">PDF, Office, ZIP ไม่เกิน 20 MB</div>
        </div>
        <div class="mb-3"><label class="form-label">หรือ ลิงก์ภายนอก</label><input class="form-control" name="url" value="<?= e($edit['url']) ?>" placeholder="https://drive.google.com/..."></div>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="is_featured" id="fe" <?= $edit['is_featured'] ? 'checked' : '' ?>><label class="form-check-label" for="fe">แสดงในแถบดาวน์โหลดหน้าแรก</label></div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
        <?php if ($edit['id']): ?><a href="downloads.php" class="btn btn-light">ยกเลิก</a><?php endif; ?>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card"><div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>ชื่อเอกสาร</th><th>ประเภท</th><th class="text-center">ดาวน์โหลด</th><th>วันที่</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= $r['is_featured'] ? '<i class="bi bi-star-fill text-warning" title="แสดงหน้าแรก"></i> ' : '' ?><?= e($r['title']) ?></td>
              <td class="small"><?= $r['file'] ? e(strtoupper(pathinfo($r['file'], PATHINFO_EXTENSION))) : 'ลิงก์' ?></td>
              <td class="text-center"><?= number_format($r['hits']) ?></td>
              <td class="small text-nowrap"><?= thai_date($r['created_at']) ?></td>
              <td class="text-end text-nowrap">
                <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <?= delete_button('downloads.php', $r['id'], 'ลบเอกสาร "' . $r['title'] . '"?') ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-5">ยังไม่มีเอกสาร</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div></div>
  </div>
</div>
<?php admin_footer(); ?>
