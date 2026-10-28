<?php
require_once __DIR__ . '/_layout.php';

$id = (int) ($_GET['id'] ?? 0);
$n = $id ? q('SELECT * FROM news WHERE id = ?', [$id])->fetch() : null;
// ผู้สื่อข่าวแก้ไขได้เฉพาะข่าวของตัวเอง
if ($n && is_reporter() && (int) $n['author_id'] !== (int) $me['id']) {
    $n = null;
}
$autoPublish = setting('reporter_autopublish') === '1';
if ($id && !$n) {
    flash('ไม่พบข่าว', 'danger');
    redirect('admin/news.php');
}
$n = $n ?: ['category_id' => null, 'title' => '', 'summary' => '', 'content' => '', 'title_en' => '', 'summary_en' => '', 'content_en' => '', 'cover' => null, 'attachment' => null,
    'is_pinned' => 0, 'status' => is_reporter() && !$autoPublish ? 'pending' : 'published', 'published_at' => date('Y-m-d H:i:s')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // ลบรูปในแกลเลอรี
    if (isset($_POST['delete_image'])) {
        $img = q('SELECT * FROM news_images WHERE id = ? AND news_id = ?', [(int) $_POST['delete_image'], $id])->fetch();
        if ($img) {
            delete_file($img['path']);
            q('DELETE FROM news_images WHERE id = ?', [$img['id']]);
            flash('ลบรูปภาพเรียบร้อย');
        }
        redirect('admin/news_form.php?id=' . $id . '#gallery');
    }

    $data = [
        'category_id' => (int) ($_POST['category_id'] ?? 0) ?: null,
        'title' => trim($_POST['title'] ?? ''),
        'summary' => trim($_POST['summary'] ?? ''),
        'content' => $_POST['content'] ?? '',
        'title_en' => trim($_POST['title_en'] ?? '') ?: null,
        'summary_en' => trim($_POST['summary_en'] ?? '') ?: null,
        'content_en' => trim($_POST['content_en'] ?? '') ?: null,
        'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
        'status' => isset(NEWS_STATUS[$_POST['status'] ?? '']) ? $_POST['status'] : 'published',
        'published_at' => date('Y-m-d H:i:s', strtotime($_POST['published_at'] ?? '') ?: time()),
    ];
    if (is_reporter()) {
        // ผู้สื่อข่าวปักหมุดไม่ได้ และข่าวต้องผ่านการตรวจ (ทุกครั้งที่แก้ไข) เว้นแต่เปิดเผยแพร่อัตโนมัติ
        $data['is_pinned'] = (int) $n['is_pinned'];
        $data['status'] = $data['status'] === 'draft' ? 'draft' : ($autoPublish ? 'published' : 'pending');
    }
    $n = array_merge($n, $data);

    try {
        if ($data['title'] === '') {
            throw new RuntimeException('กรุณากรอกหัวข้อข่าว');
        }
        if ($cover = upload_file('cover', 'news', IMG_EXT, 5)) {
            delete_file($n['cover'] ?? null);
            $data['cover'] = $cover;
        } elseif (isset($_POST['remove_cover'])) {
            delete_file($n['cover']);
            $data['cover'] = null;
        }
        if ($att = upload_file('attachment', 'attachments', DOC_EXT, 20)) {
            delete_file($n['attachment'] ?? null);
            $data['attachment'] = $att;
        } elseif (isset($_POST['remove_attachment'])) {
            delete_file($n['attachment']);
            $data['attachment'] = null;
        }
        if ($data['summary'] === '') {
            $data['summary'] = excerpt($data['content'], 250);
        }
        if (!$data['summary_en'] && $data['content_en']) {
            $data['summary_en'] = excerpt($data['content_en'], 250);
        }

        $cols = array_keys($data);
        if ($id) {
            q('UPDATE news SET ' . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ' WHERE id = ?', [...array_values($data), $id]);
        } else {
            $data['author_id'] = $me['id'];
            $cols = array_keys($data);
            q('INSERT INTO news (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($data));
            $id = (int) db()->lastInsertId();
        }

        // รูปภาพกิจกรรม (หลายรูป)
        $order = (int) q('SELECT COALESCE(MAX(sort_order), 0) FROM news_images WHERE news_id = ?', [$id])->fetchColumn();
        foreach (array_keys($_FILES['gallery']['name'] ?? []) as $i) {
            if ($path = upload_file('gallery', 'news', IMG_EXT, 5, $i)) {
                q('INSERT INTO news_images (news_id, path, sort_order) VALUES (?, ?, ?)', [$id, $path, ++$order]);
            }
        }

        flash($data['status'] === 'pending' ? 'ส่งข่าวให้ผู้ดูแลตรวจแล้ว ข่าวจะแสดงบนเว็บหลังได้รับการอนุมัติ' : 'บันทึกข่าวเรียบร้อย');
        redirect('admin/news_form.php?id=' . $id);
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'danger');
    }
}

$cats = q('SELECT id, name FROM categories ORDER BY sort_order, id')->fetchAll();
$images = $id ? q('SELECT * FROM news_images WHERE news_id = ? ORDER BY sort_order, id', [$id])->fetchAll() : [];

admin_header($id ? 'แก้ไขข่าว' : 'เพิ่มข่าว', 'news');
?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#langTh">ภาษาไทย</button></li>
        <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#langEn">English <span class="small text-muted">(ไม่บังคับ)</span></button></li>
      </ul>
      <div class="tab-content">
      <div class="card tab-pane fade show active rounded-top-0" id="langTh">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label req" for="title">หัวข้อข่าว</label>
            <input class="form-control form-control-lg" id="title" name="title" value="<?= e($n['title']) ?>" required maxlength="255">
          </div>
          <div class="mb-3">
            <label class="form-label" for="summary">สรุปย่อ <span class="text-muted small">(ถ้าเว้นว่าง ระบบจะตัดจากเนื้อหา)</span></label>
            <textarea class="form-control" id="summary" name="summary" rows="2"><?= e($n['summary']) ?></textarea>
          </div>
          <label class="form-label">เนื้อหาข่าว</label>
          <textarea class="rich" name="content"><?= e($n['content']) ?></textarea>
        </div>
      </div>
      <div class="card tab-pane fade rounded-top-0" id="langEn">
        <div class="card-body">
          <p class="small text-muted">ถ้าเว้นว่าง หน้าเว็บภาษาอังกฤษจะแสดงเนื้อหาภาษาไทยแทน</p>
          <div class="mb-3"><label class="form-label" for="title_en">Title</label>
            <input class="form-control form-control-lg" id="title_en" name="title_en" value="<?= e($n['title_en']) ?>" maxlength="255"></div>
          <div class="mb-3"><label class="form-label" for="summary_en">Summary</label>
            <textarea class="form-control" id="summary_en" name="summary_en" rows="2"><?= e($n['summary_en']) ?></textarea></div>
          <label class="form-label">Content</label>
          <textarea class="rich" name="content_en"><?= e($n['content_en']) ?></textarea>
        </div>
      </div>
      </div>

      <div class="card mt-3" id="gallery">
        <div class="card-header">ภาพกิจกรรม (แกลเลอรี)</div>
        <div class="card-body">
          <?php if ($images): ?>
            <div class="row g-2 mb-3 gallery-admin">
              <?php foreach ($images as $img): ?>
                <div class="col-4 col-md-3 col-xl-2 position-relative">
                  <img src="<?= e(file_url($img['path'])) ?>" alt="">
                  <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 py-0 px-1" name="delete_image" value="<?= $img['id'] ?>" formnovalidate onclick="return confirm('ลบรูปนี้?')"><i class="bi bi-x"></i></button>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
          <div class="form-text">เลือกได้หลายรูปพร้อมกัน (JPG, PNG, WEBP ไม่เกิน 5 MB ต่อรูป)</div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header">การเผยแพร่</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="category_id">หมวดหมู่</label>
            <select class="form-select" id="category_id" name="category_id">
              <option value="">- ไม่ระบุ -</option>
              <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int) $n['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="published_at">วันเวลาที่ประกาศ</label>
            <input type="datetime-local" class="form-control" id="published_at" name="published_at" value="<?= e(date('Y-m-d\TH:i', strtotime($n['published_at']))) ?>">
            <div class="form-text">ตั้งเวลาล่วงหน้าได้ ข่าวจะแสดงเมื่อถึงเวลา</div>
          </div>
          <?php if (is_reporter()): ?>
            <div class="mb-3">
              <label class="form-label">สถานะ</label> <?= $id ? status_badge($n['status']) : '' ?>
              <div class="form-check"><input class="form-check-input" type="radio" name="status" id="st1" value="submit" <?= $n['status'] !== 'draft' ? 'checked' : '' ?>><label class="form-check-label" for="st1"><?= $autoPublish ? 'เผยแพร่' : 'ส่งให้ผู้ดูแลตรวจ' ?></label></div>
              <div class="form-check"><input class="form-check-input" type="radio" name="status" id="st2" value="draft" <?= $n['status'] === 'draft' ? 'checked' : '' ?>><label class="form-check-label" for="st2">เก็บเป็นฉบับร่าง</label></div>
              <?php if (!$autoPublish && $n['status'] === 'published'): ?><div class="form-text text-warning">ข่าวนี้เผยแพร่แล้ว หากแก้ไขจะต้องรอผู้ดูแลตรวจอีกครั้ง</div><?php endif; ?>
            </div>
            <button class="btn btn-primary w-100"><i class="bi bi-send"></i> <?= $autoPublish ? 'บันทึก' : 'บันทึก / ส่งตรวจ' ?></button>
          <?php else: ?>
            <div class="mb-3">
              <label class="form-label">สถานะ</label>
              <div>
                <?php foreach (NEWS_STATUS as $k => [$label]): ?>
                  <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="status" id="st_<?= $k ?>" value="<?= $k ?>" <?= $n['status'] === $k ? 'checked' : '' ?>><label class="form-check-label" for="st_<?= $k ?>"><?= $label ?></label></div>
                <?php endforeach; ?>
              </div>
              <?php if ($n['status'] === 'pending'): ?><div class="form-text text-warning">ข่าวนี้ส่งมาจากสมาชิก รอการตรวจ — เลือก “เผยแพร่” แล้วบันทึกเพื่ออนุมัติ</div><?php endif; ?>
            </div>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" name="is_pinned" id="is_pinned" <?= $n['is_pinned'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_pinned">ปักหมุดไว้ด้านบน</label>
            </div>
            <button class="btn btn-primary w-100"><i class="bi bi-save"></i> บันทึก</button>
          <?php endif; ?>
          <a href="news.php" class="btn btn-light w-100 mt-2">กลับ</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">ภาพหน้าปก</div>
        <div class="card-body">
          <img id="coverPreview" src="<?= e(file_url($n['cover'])) ?>" class="preview-img mb-2 <?= $n['cover'] ? '' : 'd-none' ?>" alt="">
          <input type="file" name="cover" class="form-control" accept="image/*" data-preview="#coverPreview">
          <?php if ($n['cover']): ?><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_cover" id="rc"><label class="form-check-label small" for="rc">ลบภาพหน้าปก</label></div><?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header">ไฟล์แนบ</div>
        <div class="card-body">
          <?php if ($n['attachment']): ?>
            <a href="<?= e(file_url($n['attachment'])) ?>" target="_blank" class="d-block mb-2 small"><i class="bi bi-paperclip"></i> <?= e(basename($n['attachment'])) ?></a>
          <?php endif; ?>
          <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.jpg,.jpeg,.png">
          <div class="form-text">PDF, Word, Excel, PowerPoint, ZIP ไม่เกิน 20 MB</div>
          <?php if ($n['attachment']): ?><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_attachment" id="ra"><label class="form-check-label small" for="ra">ลบไฟล์แนบ</label></div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</form>
<?php admin_footer(rich_editor_script()); ?>
