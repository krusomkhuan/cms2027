<?php
require_once __DIR__ . '/_layout.php';

$groups = ['eservice' => 'บริการออนไลน์ (E-Service) หน้าแรก', 'external' => 'หน่วยงานที่เกี่ยวข้อง (ท้ายเว็บ)', 'province' => 'อาชีวศึกษาจังหวัดร้อยเอ็ด (ท้ายเว็บ — ใส่ # ถ้ายังไม่มีเว็บไซต์)'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        q('DELETE FROM links WHERE id = ?', [(int) $_POST['delete']]);
        flash('ลบลิงก์เรียบร้อย');
    } else {
        $title = trim($_POST['title'] ?? '');
        $link = trim($_POST['url'] ?? '');
        $icon = preg_match('/^bi-[a-z0-9-]+$/', $_POST['icon'] ?? '') ? $_POST['icon'] : 'bi-link-45deg';
        $group = isset($groups[$_POST['link_group'] ?? '']) ? $_POST['link_group'] : 'eservice';
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $titleEn = trim($_POST['title_en'] ?? '') ?: null;
        if ($title === '' || !preg_match('#^(https?://|\#$)#i', $link)) {
            flash('กรุณากรอกชื่อ และลิงก์ที่ขึ้นต้นด้วย http:// หรือ https://', 'danger');
        } elseif ($id = (int) ($_POST['id'] ?? 0)) {
            q('UPDATE links SET title = ?, title_en = ?, url = ?, icon = ?, link_group = ?, sort_order = ? WHERE id = ?', [$title, $titleEn, $link, $icon, $group, $sort, $id]);
            flash('บันทึกเรียบร้อย');
        } else {
            q('INSERT INTO links (title, title_en, url, icon, link_group, sort_order) VALUES (?, ?, ?, ?, ?, ?)', [$title, $titleEn, $link, $icon, $group, $sort]);
            flash('เพิ่มลิงก์เรียบร้อย');
        }
    }
    redirect('admin/links.php');
}

$rows = q('SELECT * FROM links ORDER BY link_group, sort_order, id')->fetchAll();
$edit = ['id' => 0, 'title' => '', 'url' => 'https://', 'icon' => 'bi-link-45deg', 'link_group' => 'eservice', 'sort_order' => 0];
foreach ($rows as $r) {
    if ((int) $r['id'] === (int) ($_GET['edit'] ?? 0)) {
        $edit = $r;
    }
}
$icons = ['bi-link-45deg', 'bi-person-plus', 'bi-pc-display', 'bi-journal-check', 'bi-mortarboard', 'bi-people', 'bi-book', 'bi-envelope-paper',
    'bi-shield-check', 'bi-calendar-check', 'bi-clipboard-data', 'bi-building', 'bi-bank', 'bi-database', 'bi-briefcase', 'bi-camera-video',
    'bi-facebook', 'bi-youtube', 'bi-globe', 'bi-chat-dots', 'bi-award', 'bi-bar-chart', 'bi-gear', 'bi-wifi'];

admin_header('ลิงก์ / E-Service', 'links');
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header"><?= $edit['id'] ? 'แก้ไขลิงก์' : 'เพิ่มลิงก์' ?></div><div class="card-body">
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
        <div class="mb-3"><label class="form-label req">ชื่อ</label><input class="form-control" name="title" value="<?= e($edit['title']) ?>" required></div>
        <div class="mb-3"><label class="form-label">ชื่อภาษาอังกฤษ <span class="badge text-bg-light">EN</span></label><input class="form-control" name="title_en" value="<?= e($edit['title_en'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label req">URL</label><input class="form-control" name="url" value="<?= e($edit['url']) ?>" required></div>
        <div class="mb-3"><label class="form-label">กลุ่ม</label>
          <select class="form-select" name="link_group"><?php foreach ($groups as $k => $l): ?><option value="<?= $k ?>" <?= $edit['link_group'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
        </div>
        <div class="mb-3"><label class="form-label">ไอคอน</label>
          <div class="d-flex flex-wrap gap-1">
            <?php foreach ($icons as $ic): ?>
              <input type="radio" class="btn-check" name="icon" id="<?= $ic ?>" value="<?= $ic ?>" <?= $edit['icon'] === $ic ? 'checked' : '' ?>>
              <label class="btn btn-sm btn-outline-secondary" for="<?= $ic ?>" title="<?= $ic ?>"><i class="bi <?= $ic ?>"></i></label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mb-3"><label class="form-label">ลำดับ</label><input type="number" class="form-control" name="sort_order" value="<?= (int) $edit['sort_order'] ?>"></div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
        <?php if ($edit['id']): ?><a href="links.php" class="btn btn-light">ยกเลิก</a><?php endif; ?>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <?php foreach ($groups as $gk => $gl): ?>
      <div class="card mb-3"><div class="card-header"><?= $gl ?></div><div class="table-responsive">
        <table class="table table-hover mb-0">
          <tbody>
            <?php foreach ($rows as $r): if ($r['link_group'] !== $gk) continue; ?>
              <tr>
                <td style="width:50px"><i class="bi <?= e($r['icon']) ?> fs-5"></i></td>
                <td><?= e($r['title']) ?><div class="small text-muted text-truncate" style="max-width:340px"><?= e($r['url']) ?></div></td>
                <td class="text-center">#<?= (int) $r['sort_order'] ?></td>
                <td class="text-end text-nowrap">
                  <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                  <?= delete_button('links.php', $r['id'], 'ลบลิงก์ "' . $r['title'] . '"?') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div></div>
    <?php endforeach; ?>
  </div>
</div>
<?php admin_footer(); ?>
