<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        q('DELETE FROM events WHERE id = ?', [(int) $_POST['delete']]);
        flash('ลบกิจกรรมเรียบร้อย');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $start = DateTime::createFromFormat('Y-m-d', $_POST['start_date'] ?? '');
        $end = DateTime::createFromFormat('Y-m-d', $_POST['end_date'] ?? '');
        $vals = [
            trim($_POST['title'] ?? ''),
            $start ? $start->format('Y-m-d') : null,
            $end && $start && $end > $start ? $end->format('Y-m-d') : null,
            trim($_POST['time_text'] ?? ''),
            trim($_POST['location'] ?? ''),
            trim($_POST['detail'] ?? ''),
            preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#1e3a8a',
            trim($_POST['title_en'] ?? '') ?: null,
            trim($_POST['location_en'] ?? '') ?: null,
            trim($_POST['detail_en'] ?? '') ?: null,
        ];
        if ($vals[0] === '' || !$vals[1]) {
            flash('กรุณากรอกชื่อกิจกรรมและวันที่เริ่ม', 'danger');
            redirect('admin/events.php' . ($id ? '?edit=' . $id : ''));
        }
        if ($id) {
            q('UPDATE events SET title = ?, start_date = ?, end_date = ?, time_text = ?, location = ?, detail = ?, color = ?, title_en = ?, location_en = ?, detail_en = ? WHERE id = ?', [...$vals, $id]);
        } else {
            q('INSERT INTO events (title, start_date, end_date, time_text, location, detail, color, title_en, location_en, detail_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $vals);
        }
        flash('บันทึกกิจกรรมเรียบร้อย');
    }
    redirect('admin/events.php');
}

$showPast = !empty($_GET['past']);
$rows = q('SELECT * FROM events WHERE ' . ($showPast ? 'COALESCE(end_date, start_date) < CURDATE() ORDER BY start_date DESC LIMIT 100' : 'COALESCE(end_date, start_date) >= CURDATE() ORDER BY start_date'))->fetchAll();
$edit = ['id' => 0, 'title' => '', 'start_date' => date('Y-m-d'), 'end_date' => '', 'time_text' => '', 'location' => '', 'detail' => '', 'color' => '#1e3a8a', 'title_en' => '', 'location_en' => '', 'detail_en' => ''];
if (!empty($_GET['edit'])) {
    $edit = q('SELECT * FROM events WHERE id = ?', [(int) $_GET['edit']])->fetch() ?: $edit;
}
$colors = ['#1e3a8a' => 'น้ำเงิน', '#d71920' => 'แดง', '#0d6efd' => 'ฟ้า', '#198754' => 'เขียว', '#d4a017' => 'ทอง', '#6f42c1' => 'ม่วง', '#fd7e14' => 'ส้ม', '#6c757d' => 'เทา'];

admin_header('ปฏิทินกิจกรรม', 'events');
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header"><?= $edit['id'] ? 'แก้ไขกิจกรรม' : 'เพิ่มกิจกรรม' ?></div><div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
        <div class="mb-3"><label class="form-label req">ชื่อกิจกรรม</label><input class="form-control" name="title" value="<?= e($edit['title']) ?>" required></div>
        <div class="row g-2 mb-3">
          <div class="col-6"><label class="form-label req">วันที่เริ่ม</label><input type="date" class="form-control" name="start_date" value="<?= e($edit['start_date']) ?>" required></div>
          <div class="col-6"><label class="form-label">ถึงวันที่</label><input type="date" class="form-control" name="end_date" value="<?= e($edit['end_date']) ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label">เวลา</label><input class="form-control" name="time_text" value="<?= e($edit['time_text']) ?>" placeholder="เช่น 08:30–12:00 น."></div>
        <div class="mb-3"><label class="form-label">สถานที่</label><input class="form-control" name="location" value="<?= e($edit['location']) ?>"></div>
        <div class="mb-3"><label class="form-label">รายละเอียด</label><textarea class="form-control" name="detail" rows="3"><?= e($edit['detail']) ?></textarea></div>
        <div class="mb-3"><label class="form-label">สี</label>
          <div class="d-flex flex-wrap gap-1">
            <?php foreach ($colors as $c => $label): ?>
              <input type="radio" class="btn-check" name="color" id="c<?= substr($c, 1) ?>" value="<?= $c ?>" <?= strcasecmp($edit['color'], $c) === 0 ? 'checked' : '' ?>>
              <label class="btn btn-sm btn-outline-secondary" for="c<?= substr($c, 1) ?>"><span class="d-inline-block rounded-circle align-middle" style="width:12px;height:12px;background:<?= $c ?>"></span> <?= $label ?></label>
            <?php endforeach; ?>
          </div>
        </div>
        <details class="mb-3"><summary class="small text-primary">ภาษาอังกฤษ (ไม่บังคับ)</summary>
          <div class="pt-2">
            <div class="mb-2"><label class="form-label small">Title</label><input class="form-control form-control-sm" name="title_en" value="<?= e($edit['title_en']) ?>"></div>
            <div class="mb-2"><label class="form-label small">Location</label><input class="form-control form-control-sm" name="location_en" value="<?= e($edit['location_en']) ?>"></div>
            <div class="mb-2"><label class="form-label small">Details</label><textarea class="form-control form-control-sm" name="detail_en" rows="2"><?= e($edit['detail_en']) ?></textarea></div>
          </div>
        </details>
        <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
        <?php if ($edit['id']): ?><a href="events.php" class="btn btn-light">ยกเลิก</a><?php endif; ?>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="btn-group btn-group-sm">
          <a href="events.php" class="btn <?= $showPast ? 'btn-outline-secondary' : 'btn-secondary' ?>">กำลังจะมาถึง</a>
          <a href="events.php?past=1" class="btn <?= $showPast ? 'btn-secondary' : 'btn-outline-secondary' ?>">ผ่านไปแล้ว</a>
        </div>
        <a href="<?= url('events.php') ?>" target="_blank" class="small">ดูปฏิทินหน้าเว็บ <i class="bi bi-box-arrow-up-right"></i></a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>วันที่</th><th>กิจกรรม</th><th>สถานที่</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td class="small text-nowrap"><?= thai_date($r['start_date']) ?><?= $r['end_date'] ? '<br><span class="text-muted">ถึง ' . thai_date($r['end_date']) . '</span>' : '' ?></td>
                <td><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:<?= e($r['color']) ?>"></span><?= e($r['title']) ?><?= $r['time_text'] ? '<div class="small text-muted">' . e($r['time_text']) . '</div>' : '' ?></td>
                <td class="small"><?= e($r['location']) ?></td>
                <td class="text-end text-nowrap">
                  <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                  <?= delete_button('events.php', $r['id'], 'ลบกิจกรรม "' . $r['title'] . '"?') ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted py-5">ไม่มีกิจกรรม</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
