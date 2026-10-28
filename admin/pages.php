<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_check();
    q("DELETE FROM pages WHERE id = ? AND slug <> 'contact'", [(int) $_POST['delete']]);
    flash('ลบหน้าเรียบร้อย');
    redirect('admin/pages.php');
}

$rows = q('SELECT * FROM pages ORDER BY sort_order, id')->fetchAll();

admin_header('ข้อมูลพื้นฐานสถานศึกษา', 'pages');
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="small text-muted">หน้าข้อมูล เช่น ประวัติ วิสัยทัศน์ โครงสร้างการบริหาร (แสดงในเมนู “ข้อมูลสถานศึกษา”)</span>
    <a href="page_form.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มหน้า</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>ลำดับ</th><th>ชื่อหน้า</th><th>URL</th><th>แสดงในเมนู</th><th>แก้ไขล่าสุด</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int) $r['sort_order'] ?></td>
            <td><?= e($r['title']) ?></td>
            <td class="small"><code>page.php?slug=<?= e($r['slug']) ?></code></td>
            <td><?= $r['show_in_menu'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-dash-circle text-muted"></i>' ?></td>
            <td class="small"><?= $r['updated_at'] ? thai_date($r['updated_at'], true) : '-' ?></td>
            <td class="text-end text-nowrap">
              <a href="<?= url('page.php?slug=' . urlencode($r['slug'])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
              <a href="page_form.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?= $r['slug'] === 'contact' ? '' : delete_button('pages.php', $r['id'], 'ลบหน้า "' . $r['title'] . '"?') ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_footer(); ?>
