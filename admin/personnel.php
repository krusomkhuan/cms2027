<?php
require_once __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_check();
    $p = q('SELECT photo FROM personnel WHERE id = ?', [(int) $_POST['delete']])->fetch();
    if ($p) {
        delete_file($p['photo']);
        q('DELETE FROM personnel WHERE id = ?', [(int) $_POST['delete']]);
        flash('ลบข้อมูลบุคลากรเรียบร้อย');
    }
    redirect('admin/personnel.php?' . http_build_query(array_intersect_key($_GET, array_flip(['type', 'dept', 'q']))));
}

$types = personnel_types();
$type = isset($types[$_GET['type'] ?? '']) ? $_GET['type'] : '';
$deptId = (int) ($_GET['dept'] ?? 0);
$kw = trim($_GET['q'] ?? '');
$where = '1';
$params = [];
if ($type) {
    $where .= ' AND p.type = ?';
    $params[] = $type;
}
if ($deptId) {
    $where .= ' AND p.department_id = ?';
    $params[] = $deptId;
}
if ($kw !== '') {
    $where .= ' AND (p.firstname LIKE ? OR p.lastname LIKE ? OR p.position LIKE ?)';
    array_push($params, "%$kw%", "%$kw%", "%$kw%");
}
$rows = q("SELECT p.*, d.name AS dept_name FROM personnel p LEFT JOIN departments d ON d.id = p.department_id
    WHERE $where ORDER BY FIELD(p.type, 'executive', 'teacher', 'staff'),
    CASE WHEN p.type = 'executive' THEN 0 ELSE COALESCE(d.sort_order, 999) END, p.sort_order, p.id", $params)->fetchAll();
$depts = q('SELECT id, name FROM departments ORDER BY type, sort_order, id')->fetchAll();
$query = array_filter(['type' => $type ?: null, 'dept' => $deptId ?: null, 'q' => $kw ?: null]);

admin_header('ข้อมูลบุคลากร', 'personnel');
?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center">
    <form class="d-flex flex-wrap gap-2 me-auto" method="get">
      <select name="type" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
        <option value="">ทุกประเภท</option>
        <?php foreach ($types as $k => $l): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select>
      <select name="dept" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
        <option value="">ทุกฝ่าย/แผนก</option>
        <?php foreach ($depts as $d): ?><option value="<?= $d['id'] ?>" <?= $deptId === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" style="width:180px" placeholder="ค้นหาชื่อ/ตำแหน่ง" value="<?= e($kw) ?>">
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
    <a href="personnel_import.php" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-arrow-up"></i> นำเข้าจาก Excel</a>
    <a href="personnel_form.php" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> เพิ่มบุคลากร</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>รูป</th><th>ชื่อ-สกุล</th><th>ตำแหน่ง</th><th>ฝ่าย/แผนก</th><th>ประเภท</th><th class="text-center">ลำดับ</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $p): ?>
          <tr class="<?= $p['is_active'] ? '' : 'opacity-50' ?>">
            <td><?php if ($p['photo']): ?><img src="<?= e(file_url($p['photo'])) ?>" class="thumb-person" alt=""><?php else: ?><div class="thumb-person d-flex align-items-center justify-content-center text-muted"><i class="bi bi-person"></i></div><?php endif; ?></td>
            <td><?= e(personnel_name($p)) ?><?= $p['is_active'] ? '' : ' <span class="badge text-bg-secondary">ซ่อน</span>' ?><div class="small text-muted"><?= e($p['academic_rank']) ?></div></td>
            <td class="small"><?= e($p['position']) ?></td>
            <td class="small"><?= e($p['dept_name'] ?? '-') ?></td>
            <td class="small text-nowrap"><?= $types[$p['type']] ?></td>
            <td class="text-center"><?= (int) $p['sort_order'] ?></td>
            <td class="text-end text-nowrap">
              <a href="personnel_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?= delete_button('personnel.php?' . http_build_query($query), $p['id'], 'ลบข้อมูล ' . personnel_name($p) . '?') ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-5">ไม่พบข้อมูล</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white small text-muted">ทั้งหมด <?= count($rows) ?> คน · ลำดับน้อยแสดงก่อน (ผู้บริหารลำดับแรกจะแสดงเด่นที่สุด)</div>
</div>
<?php admin_footer(); ?>
