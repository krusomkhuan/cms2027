<?php
require_once __DIR__ . '/includes/front.php';

$activeMenu = 'personnel';
$types = personnel_types();
$type = isset($types[$_GET['type'] ?? '']) ? $_GET['type'] : '';
$deptId = (int) ($_GET['dept'] ?? 0);
$depts = q('SELECT * FROM departments ORDER BY type, sort_order, id')->fetchAll();
$dept = null;
foreach ($depts as $d) {
    if ((int) $d['id'] === $deptId) {
        $dept = $d;
    }
}

$where = 'p.is_active = 1';
$params = [];
if ($type) {
    $where .= ' AND p.type = ?';
    $params[] = $type;
}
if ($dept) {
    $where .= ' AND p.department_id = ?';
    $params[] = $dept['id'];
}
$people = q("SELECT p.*, d.name AS dept_name, d.name_en AS dept_name_en FROM personnel p LEFT JOIN departments d ON d.id = p.department_id
    WHERE $where ORDER BY FIELD(p.type, 'executive', 'teacher', 'staff'),
    CASE WHEN p.type = 'executive' THEN 0 ELSE COALESCE(d.sort_order, 999) END, p.sort_order, p.id", $params)->fetchAll();

// จัดกลุ่มตามประเภท (ถ้าไม่ได้กรองประเภท)
$groups = [];
foreach ($people as $p) {
    $groups[$p['type']][] = $p;
}

$counts = q('SELECT type, COUNT(*) FROM personnel WHERE is_active = 1 GROUP BY type')->fetchAll(PDO::FETCH_KEY_PAIR);

$title = $dept ? tr($dept, 'name') : ($type ? $types[$type] : t('ครูและบุคลากร'));
$pageTitle = $title;
require __DIR__ . '/includes/header.php';
echo page_banner($title, [t('บุคลากร') => url('personnel.php'), $title => null]);
?>
<section class="section">
  <div class="container">
    <div class="row g-3 mb-4">
      <?php foreach ($types as $k => $label): ?>
        <div class="col-4">
          <a href="<?= url('personnel.php?type=' . $k) ?>" class="card stat-card text-decoration-none h-100 <?= $type === $k ? 'border border-2 border-danger' : '' ?>">
            <div class="card-body text-center py-3"><div class="num"><?= number_format($counts[$k] ?? 0) ?></div><div class="text-muted small"><?= e($label) ?></div></div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <form class="row g-2 mb-4 align-items-end" method="get">
      <div class="col-sm-5 col-lg-4">
        <label class="form-label small text-muted mb-1"><?= t('ประเภท') ?></label>
        <select name="type" class="form-select" onchange="this.form.submit()">
          <option value=""><?= t('ทุกประเภท') ?></option>
          <?php foreach ($types as $k => $label): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-7 col-lg-5">
        <label class="form-label small text-muted mb-1"><?= t('ฝ่าย / แผนกวิชา') ?></label>
        <select name="dept" class="form-select" onchange="this.form.submit()">
          <option value=""><?= t('ทุกฝ่าย/แผนก') ?></option>
          <optgroup label="<?= t('ฝ่าย / งาน') ?>">
            <?php foreach ($depts as $d): if ($d['type'] !== 'division') continue; ?><option value="<?= $d['id'] ?>" <?= $deptId === (int) $d['id'] ? 'selected' : '' ?>><?= e(tr($d, 'name')) ?></option><?php endforeach; ?>
          </optgroup>
          <optgroup label="<?= t('แผนกวิชา') ?>">
            <?php foreach ($depts as $d): if ($d['type'] !== 'academic') continue; ?><option value="<?= $d['id'] ?>" <?= $deptId === (int) $d['id'] ? 'selected' : '' ?>><?= e(tr($d, 'name')) ?></option><?php endforeach; ?>
          </optgroup>
        </select>
      </div>
      <noscript><div class="col-auto"><button class="btn btn-primary"><?= t('แสดง') ?></button></div></noscript>
    </form>

    <?php if ($dept && $dept['description']): ?><p class="text-muted"><?= nl2br(e(tr($dept, 'description'))) ?></p><?php endif; ?>

    <?php foreach ($types as $k => $label): if (empty($groups[$k])) continue; ?>
      <h2 class="h4 section-title mb-4 mt-2"><?= e($label) ?></h2>
      <div class="row g-3 mb-5 justify-content-center">
        <?php foreach ($groups[$k] as $i => $p): ?>
          <?php if ($k === 'executive' && $i === 0 && !$dept): ?>
            <div class="col-12"><div class="person-lead"><?= person_card($p) ?></div></div>
          <?php else: ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2"><?= person_card($p) ?></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$people): ?><p class="text-center text-muted py-5"><?= t('ไม่พบข้อมูลบุคลากร') ?></p><?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
