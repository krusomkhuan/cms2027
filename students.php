<?php
require_once __DIR__ . '/includes/front.php';

$activeMenu = 'students';
$terms = q('SELECT academic_year, semester, SUM(male + female) AS total FROM student_stats GROUP BY academic_year, semester ORDER BY academic_year DESC, semester DESC')->fetchAll();
$sel = $_GET['term'] ?? '';
$termIdx = 0;
foreach ($terms as $i => $t) {
    if ($sel === $t['academic_year'] . '-' . $t['semester']) {
        $termIdx = $i;
    }
}
$term = $terms[$termIdx] ?? null;
$prevTerm = $terms[$termIdx + 1] ?? null;

$depts = [];
$sum = ['male' => 0, 'female' => 0];
$levelSum = [];
$yearSum = []; // [ปวช.1 => [m, f]]
if ($term) {
    $rows = q('SELECT s.*, d.name AS dept_name, d.name_en AS dept_name_en FROM student_stats s JOIN departments d ON d.id = s.department_id
        WHERE s.academic_year = ? AND s.semester = ? ORDER BY d.sort_order, d.id', [$term['academic_year'], $term['semester']])->fetchAll();
    foreach ($rows as $r) {
        $key = $r['level'] . $r['class_year'];
        $id = $r['department_id'];
        $depts[$id]['name'] = tr($r, 'dept_name');
        $depts[$id]['cells'][$key] = ($depts[$id]['cells'][$key] ?? 0) + $r['male'] + $r['female'];
        $depts[$id]['male'] = ($depts[$id]['male'] ?? 0) + $r['male'];
        $depts[$id]['female'] = ($depts[$id]['female'] ?? 0) + $r['female'];
        $sum['male'] += $r['male'];
        $sum['female'] += $r['female'];
        $levelSum[$r['level']] = ($levelSum[$r['level']] ?? 0) + $r['male'] + $r['female'];
        $yearSum[$key]['m'] = ($yearSum[$key]['m'] ?? 0) + $r['male'];
        $yearSum[$key]['f'] = ($yearSum[$key]['f'] ?? 0) + $r['female'];
    }
}
$total = $sum['male'] + $sum['female'];
$cols = [];
foreach (STUDENT_LEVELS as $level => $years) {
    for ($y = 1; $y <= $years; $y++) {
        $cols[$level . $y] = t($level) . ' ' . $y;
    }
}

$yr = function ($y) {
    return (int) $y - (lang() === 'en' ? 543 : 0);
};
$pct = function ($n) use ($total) {
    return $total ? round($n * 100 / $total, 1) : 0;
};
$delta = $prevTerm ? $total - (int) $prevTerm['total'] : null;
$teachers = (int) q("SELECT COUNT(*) FROM personnel WHERE is_active = 1 AND type IN ('teacher', 'executive')")->fetchColumn();
$personnelCount = (int) q('SELECT COUNT(*) FROM personnel WHERE is_active = 1')->fetchColumn();

// แผนกเรียงตามจำนวนผู้เรียน
$ranking = $depts;
uasort($ranking, function ($a, $b) {
    return ($b['male'] + $b['female']) <=> ($a['male'] + $a['female']);
});
$maxDept = $ranking ? max(1, reset($ranking)['male'] + reset($ranking)['female']) : 1;

// แนวโน้ม 8 ภาคเรียนล่าสุด (เก่า → ใหม่)
$trend = array_reverse(array_slice($terms, 0, 8));

$pageTitle = t('ข้อมูลผู้เรียน');
require __DIR__ . '/includes/header.php';
echo page_banner($pageTitle, [t('ข้อมูลสถานศึกษา') => null, $pageTitle => null]);
?>
<section class="section dash">
  <div class="container">
    <?php if (!$term): ?>
      <p class="text-center text-muted py-5"><?= t('ยังไม่มีข้อมูลผู้เรียน') ?></p>
    <?php else: ?>
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
          <div class="small text-uppercase fw-medium" style="color:var(--accent)"><i class="bi bi-speedometer2"></i> Student Dashboard</div>
          <h2 class="h4 section-title mb-0"><?= t('จำนวนผู้เรียน ภาคเรียนที่') ?> <?= (int) $term['semester'] ?>/<?= $yr($term['academic_year']) ?></h2>
        </div>
        <form method="get" class="d-flex gap-2 align-items-center">
          <label class="small text-muted text-nowrap" for="term"><?= t('ภาคเรียน') ?></label>
          <select name="term" id="term" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($terms as $i => $t): $v = $t['academic_year'] . '-' . $t['semester']; ?>
              <option value="<?= e($v) ?>" <?= $i === $termIdx ? 'selected' : '' ?>><?= (int) $t['semester'] ?>/<?= $yr($t['academic_year']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <!-- ตัวเลขสำคัญ -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
          <div class="dash-hero h-100">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="lbl"><?= t('ผู้เรียนทั้งหมด') ?></div>
                <div class="big" data-count="<?= $total ?>"><?= number_format($total) ?></div>
                <div class="lbl"><?= t('คน') ?></div>
              </div>
              <i class="bi bi-people-fill dash-hero-icon"></i>
            </div>
            <?php if ($delta !== null): ?>
              <div class="dash-delta mt-3">
                <i class="bi bi-<?= $delta > 0 ? 'arrow-up-right' : ($delta < 0 ? 'arrow-down-right' : 'dash') ?>"></i>
                <?= ($delta > 0 ? '+' : '') . number_format($delta) ?> <?= t('เทียบภาคเรียนก่อน') ?> (<?= (int) $prevTerm['semester'] ?>/<?= $yr($prevTerm['academic_year']) ?>)
              </div>
            <?php endif; ?>
            <div class="dash-split mt-3">
              <div style="width:<?= $pct($sum['male']) ?>%" class="m"></div><div style="width:<?= $pct($sum['female']) ?>%" class="f"></div>
            </div>
            <div class="d-flex justify-content-between small mt-1 opacity-75">
              <span><i class="bi bi-gender-male"></i> <?= t('ชาย') ?> <?= number_format($sum['male']) ?> (<?= $pct($sum['male']) ?>%)</span>
              <span><i class="bi bi-gender-female"></i> <?= t('หญิง') ?> <?= number_format($sum['female']) ?> (<?= $pct($sum['female']) ?>%)</span>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-8">
          <div class="row g-3 h-100">
            <?php foreach (STUDENT_LEVELS as $level => $_): $n = $levelSum[$level] ?? 0; ?>
              <div class="col-6">
                <div class="dash-kpi h-100">
                  <div class="ic"><i class="bi bi-mortarboard"></i></div>
                  <div><div class="num"><?= number_format($n) ?></div><div class="lbl"><?= t('ระดับ') ?> <?= e(t($level)) ?> · <?= $pct($n) ?>%</div></div>
                </div>
              </div>
            <?php endforeach; ?>
            <div class="col-6">
              <div class="dash-kpi h-100">
                <div class="ic"><i class="bi bi-diagram-3"></i></div>
                <div><div class="num"><?= count($depts) ?></div><div class="lbl"><?= t('แผนกวิชาที่มีผู้เรียน') ?></div></div>
              </div>
            </div>
            <div class="col-6">
              <a class="dash-kpi h-100" href="<?= url('personnel.php') ?>">
                <div class="ic"><i class="bi bi-person-badge"></i></div>
                <div>
                  <div class="num"><?= number_format($personnelCount) ?></div>
                  <div class="lbl"><?= t('ครูและบุคลากร') ?><?php if ($teachers): ?> · <?= t('ผู้เรียนต่อครู') ?> <?= round($total / $teachers) ?>:1<?php endif; ?></div>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- กราฟ -->
      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="dash-card h-100">
            <h3><i class="bi bi-bar-chart-line"></i> <?= t('จำนวนผู้เรียนแยกตามแผนกวิชา') ?></h3>
            <div style="height:<?= max(240, count($depts) * 40) ?>px"><canvas id="deptChart"></canvas></div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="row g-3 h-100">
            <div class="col-sm-6 col-lg-12">
              <div class="dash-card h-100">
                <h3><i class="bi bi-gender-ambiguous"></i> <?= t('สัดส่วนเพศ') ?></h3>
                <div class="dash-donut"><canvas id="genderChart"></canvas></div>
              </div>
            </div>
            <div class="col-sm-6 col-lg-12">
              <div class="dash-card h-100">
                <h3><i class="bi bi-pie-chart"></i> <?= t('สัดส่วนระดับชั้น') ?></h3>
                <div class="dash-donut"><canvas id="levelChart"></canvas></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-6">
          <div class="dash-card h-100">
            <h3><i class="bi bi-layers"></i> <?= t('จำนวนผู้เรียนแยกตามชั้นปี') ?></h3>
            <div style="height:260px"><canvas id="yearChart"></canvas></div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="dash-card h-100">
            <h3><i class="bi bi-graph-up-arrow"></i> <?= t('แนวโน้มจำนวนผู้เรียน') ?></h3>
            <?php if (count($trend) > 1): ?>
              <div style="height:260px"><canvas id="trendChart"></canvas></div>
            <?php else: ?>
              <div class="dash-empty"><i class="bi bi-hourglass-split"></i><?= t('จะแสดงเมื่อมีข้อมูลอย่างน้อย 2 ภาคเรียน') ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- อันดับแผนกวิชา -->
      <div class="dash-card mb-4">
        <h3><i class="bi bi-trophy"></i> <?= t('แผนกวิชาเรียงตามจำนวนผู้เรียน') ?></h3>
        <div class="row gx-4">
          <?php $rank = 0; foreach ($ranking as $d): $n = $d['male'] + $d['female']; $rank++; ?>
            <div class="col-md-6">
              <div class="dash-rank">
                <span class="no"><?= $rank ?></span>
                <div class="flex-grow-1 min-w-0">
                  <div class="d-flex justify-content-between gap-2"><span class="text-truncate"><?= e($d['name']) ?></span><b class="text-nowrap"><?= number_format($n) ?> <small class="text-muted fw-normal"><?= t('คน') ?></small></b></div>
                  <div class="bar"><div class="m" style="width:<?= round($d['male'] * 100 / $maxDept, 1) ?>%"></div><div class="f" style="width:<?= round($d['female'] * 100 / $maxDept, 1) ?>%"></div></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ตารางละเอียด -->
      <div class="dash-card p-0 overflow-hidden">
        <h3 class="p-3 m-0"><i class="bi bi-table"></i> <?= t('ตารางข้อมูลละเอียด') ?></h3>
        <div class="table-responsive">
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
      </div>
      <p class="small text-muted mt-2"><?= t('หมายเหตุ: ข้อมูลจากงานทะเบียน แสดงเป็นจำนวนนับ ไม่เปิดเผยข้อมูลส่วนบุคคลของผู้เรียน') ?></p>

      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
      <script>
        (() => {
          const css = getComputedStyle(document.documentElement);
          const blue = css.getPropertyValue('--primary').trim() || '#1e3a8a';
          const red = css.getPropertyValue('--accent').trim() || '#d71920';
          const mix = '#7b3a6e', sky = '#5b8def', rose = '#f08a8f';
          Chart.defaults.font.family = 'Kanit, sans-serif';
          Chart.defaults.color = '#555';
          const M = <?= json_encode(t('ชาย'), JSON_UNESCAPED_UNICODE) ?>, F = <?= json_encode(t('หญิง'), JSON_UNESCAPED_UNICODE) ?>;
          const legend = { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } };
          const donut = (id, labels, data, colors) => new Chart(document.getElementById(id), {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
            options: { cutout: '68%', maintainAspectRatio: false, plugins: { legend } }
          });

          new Chart(document.getElementById('deptChart'), {
            type: 'bar',
            data: {
              labels: <?= json_encode(array_values(array_column($depts, 'name')), JSON_UNESCAPED_UNICODE) ?>,
              datasets: [
                { label: M, data: <?= json_encode(array_values(array_column($depts, 'male'))) ?>, backgroundColor: blue, borderRadius: 4 },
                { label: F, data: <?= json_encode(array_values(array_column($depts, 'female'))) ?>, backgroundColor: red, borderRadius: 4 }
              ]
            },
            options: { indexAxis: 'y', maintainAspectRatio: false, scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }, y: { stacked: true, grid: { display: false } } }, plugins: { legend } }
          });

          donut('genderChart', [M, F], [<?= $sum['male'] ?>, <?= $sum['female'] ?>], [blue, red]);
          donut('levelChart', <?= json_encode(array_map('t', array_keys(STUDENT_LEVELS)), JSON_UNESCAPED_UNICODE) ?>,
            <?= json_encode(array_map(function ($l) use ($levelSum) { return $levelSum[$l] ?? 0; }, array_keys(STUDENT_LEVELS))) ?>, [blue, mix, red]);

          new Chart(document.getElementById('yearChart'), {
            type: 'bar',
            data: {
              labels: <?= json_encode(array_values($cols), JSON_UNESCAPED_UNICODE) ?>,
              datasets: [
                { label: M, data: <?= json_encode(array_map(function ($k) use ($yearSum) { return $yearSum[$k]['m'] ?? 0; }, array_keys($cols))) ?>, backgroundColor: sky, borderRadius: 6 },
                { label: F, data: <?= json_encode(array_map(function ($k) use ($yearSum) { return $yearSum[$k]['f'] ?? 0; }, array_keys($cols))) ?>, backgroundColor: rose, borderRadius: 6 }
              ]
            },
            options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }, plugins: { legend } }
          });

          const trend = document.getElementById('trendChart');
          if (trend) {
            const ctx = trend.getContext('2d');
            const fill = ctx.createLinearGradient(0, 0, 0, 260);
            fill.addColorStop(0, 'rgba(30,58,138,.35)');
            fill.addColorStop(1, 'rgba(215,25,32,.02)');
            new Chart(trend, {
              type: 'line',
              data: {
                labels: <?= json_encode(array_map(function ($t) use ($yr) { return $t['semester'] . '/' . $yr($t['academic_year']); }, $trend)) ?>,
                datasets: [{ label: <?= json_encode(t('ผู้เรียนทั้งหมด'), JSON_UNESCAPED_UNICODE) ?>, data: <?= json_encode(array_map('intval', array_column($trend, 'total'))) ?>,
                  borderColor: blue, backgroundColor: fill, fill: true, tension: .35, pointBackgroundColor: red, pointRadius: 5 }]
              },
              options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
            });
          }
        })();
      </script>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
