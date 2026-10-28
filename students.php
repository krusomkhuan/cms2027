<?php
require_once __DIR__ . '/includes/front.php';

$activeMenu = 'students';
$terms = q('SELECT DISTINCT academic_year, semester FROM student_stats ORDER BY academic_year DESC, semester DESC')->fetchAll();
$sel = $_GET['term'] ?? '';
$term = $terms[0] ?? null;
foreach ($terms as $t) {
    if ($sel === $t['academic_year'] . '-' . $t['semester']) {
        $term = $t;
    }
}

$depts = [];
$sum = ['male' => 0, 'female' => 0];
$levelSum = [];
if ($term) {
    $rows = q('SELECT s.*, d.name AS dept_name, d.name_en AS dept_name_en FROM student_stats s JOIN departments d ON d.id = s.department_id
        WHERE s.academic_year = ? AND s.semester = ? ORDER BY d.sort_order, d.id', [$term['academic_year'], $term['semester']])->fetchAll();
    foreach ($rows as $r) {
        $key = $r['level'] . $r['class_year'];
        $depts[$r['department_id']]['name'] = tr($r, 'dept_name');
        $depts[$r['department_id']]['cells'][$key] = ($depts[$r['department_id']]['cells'][$key] ?? 0) + $r['male'] + $r['female'];
        $depts[$r['department_id']]['male'] = ($depts[$r['department_id']]['male'] ?? 0) + $r['male'];
        $depts[$r['department_id']]['female'] = ($depts[$r['department_id']]['female'] ?? 0) + $r['female'];
        $sum['male'] += $r['male'];
        $sum['female'] += $r['female'];
        $levelSum[$r['level']] = ($levelSum[$r['level']] ?? 0) + $r['male'] + $r['female'];
    }
}
$total = $sum['male'] + $sum['female'];
$cols = [];
foreach (STUDENT_LEVELS as $level => $years) {
    for ($y = 1; $y <= $years; $y++) {
        $cols[$level . $y] = t($level) . ' ' . $y;
    }
}

$pageTitle = t('ข้อมูลผู้เรียน');
$yr = fn($y) => (int) $y - (lang() === 'en' ? 543 : 0);
require __DIR__ . '/includes/header.php';
echo page_banner($pageTitle, [t('ข้อมูลสถานศึกษา') => null, $pageTitle => null]);
?>
<section class="section">
  <div class="container">
    <?php if (!$term): ?>
      <p class="text-center text-muted py-5"><?= t('ยังไม่มีข้อมูลผู้เรียน') ?></p>
    <?php else: ?>
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 section-title"><?= t('จำนวนผู้เรียน ภาคเรียนที่') ?> <?= (int) $term['semester'] ?>/<?= $yr($term['academic_year']) ?></h2>
        <form method="get" class="d-flex gap-2 align-items-center">
          <label class="small text-muted text-nowrap" for="term"><?= t('ภาคเรียน') ?></label>
          <select name="term" id="term" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($terms as $t): $v = $t['academic_year'] . '-' . $t['semester']; ?>
              <option value="<?= e($v) ?>" <?= $t === $term ? 'selected' : '' ?>><?= (int) $t['semester'] ?>/<?= $yr($t['academic_year']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-6 col-lg"><div class="card stat-card h-100"><div class="card-body text-center"><div class="num"><?= number_format($total) ?></div><div class="text-muted"><?= t('ผู้เรียนทั้งหมด') ?></div></div></div></div>
        <?php foreach (STUDENT_LEVELS as $level => $_): ?>
          <div class="col-6 col-lg"><div class="card stat-card h-100"><div class="card-body text-center"><div class="num"><?= number_format($levelSum[$level] ?? 0) ?></div><div class="text-muted"><?= e(t($level)) ?></div></div></div></div>
        <?php endforeach; ?>
        <div class="col-6 col-lg"><div class="card stat-card h-100"><div class="card-body text-center"><div class="num"><?= number_format($sum['male']) ?></div><div class="text-muted"><i class="bi bi-gender-male"></i> <?= t('ชาย') ?></div></div></div></div>
        <div class="col-6 col-lg"><div class="card stat-card h-100"><div class="card-body text-center"><div class="num"><?= number_format($sum['female']) ?></div><div class="text-muted"><i class="bi bi-gender-female"></i> <?= t('หญิง') ?></div></div></div></div>
      </div>

      <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <h3 class="h6 mb-3"><?= t('จำนวนผู้เรียนแยกตามแผนกวิชา') ?></h3>
        <div style="height:<?= max(220, count($depts) * 42) ?>px"><canvas id="deptChart"></canvas></div>
      </div></div>

      <div class="table-responsive shadow-sm rounded">
        <table class="table table-bordered table-hover table-stats text-center align-middle mb-0 bg-white">
          <thead>
            <tr>
              <th class="text-start"><?= t('แผนกวิชา') ?></th>
              <?php foreach ($cols as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
              <th><?= t('ชาย') ?></th><th><?= t('หญิง') ?></th><th><?= t('รวม') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php $colSum = []; foreach ($depts as $d): ?>
              <tr>
                <td class="text-start"><?= e($d['name']) ?></td>
                <?php foreach ($cols as $k => $_): $v = $d['cells'][$k] ?? 0; $colSum[$k] = ($colSum[$k] ?? 0) + $v; ?><td><?= $v ? number_format($v) : '-' ?></td><?php endforeach; ?>
                <td><?= number_format($d['male']) ?></td><td><?= number_format($d['female']) ?></td>
                <td class="fw-semibold"><?= number_format($d['male'] + $d['female']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td class="text-start"><?= t('รวมทั้งหมด') ?></td>
              <?php foreach ($cols as $k => $_): ?><td><?= number_format($colSum[$k] ?? 0) ?></td><?php endforeach; ?>
              <td><?= number_format($sum['male']) ?></td><td><?= number_format($sum['female']) ?></td><td><?= number_format($total) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <p class="small text-muted mt-2"><?= t('หมายเหตุ: ข้อมูลจากงานทะเบียน แสดงเป็นจำนวนนับ ไม่เปิดเผยข้อมูลส่วนบุคคลของผู้เรียน') ?></p>

      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
      <script>
        (() => {
          const primary = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
          new Chart(document.getElementById('deptChart'), {
            type: 'bar',
            data: {
              labels: <?= json_encode(array_values(array_column($depts, 'name')), JSON_UNESCAPED_UNICODE) ?>,
              datasets: [
                { label: <?= json_encode(t('ชาย'), JSON_UNESCAPED_UNICODE) ?>, data: <?= json_encode(array_values(array_column($depts, 'male'))) ?>, backgroundColor: primary, borderRadius: 4 },
                { label: <?= json_encode(t('หญิง'), JSON_UNESCAPED_UNICODE) ?>, data: <?= json_encode(array_values(array_column($depts, 'female'))) ?>, backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--accent').trim(), borderRadius: 4 }
              ]
            },
            options: {
              indexAxis: 'y', maintainAspectRatio: false,
              scales: { x: { stacked: true, beginAtZero: true }, y: { stacked: true } },
              plugins: { legend: { position: 'bottom' } },
              font: { family: 'Kanit' }
            }
          });
        })();
      </script>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
