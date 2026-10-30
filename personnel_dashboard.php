<?php
require_once __DIR__ . '/includes/front.php';

$activeMenu = 'personnel';
$types = personnel_types();
$people = q('SELECT p.prefix, p.type, p.academic_rank, p.education, p.department_id, d.name AS dept_name, d.name_en AS dept_name_en, d.type AS dept_type
    FROM personnel p LEFT JOIN departments d ON d.id = p.department_id WHERE p.is_active = 1')->fetchAll();
$total = count($people);

/** เพศจากคำนำหน้า: m / f / '' (ไม่ระบุ เช่น ว่าที่ร้อยตรี ดร.) */
function prefix_gender(string $prefix): string
{
    $p = mb_strtolower(trim($prefix));
    if ($p === '') {
        return '';
    }
    if (preg_match('/หญิง|นาง|น\.ส\.|mrs|ms|miss/u', $p)) {
        return 'f';
    }
    if (preg_match('/^นาย|^mr\.?$/u', $p)) {
        return 'm';
    }
    return '';
}

/** ระดับวุฒิการศึกษาจากข้อความวุฒิ */
function education_level(string $edu): string
{
    $e = trim($edu);
    if ($e === '') {
        return 'none';
    }
    if (preg_match('/ปวส|ปวช|อนุปริญญา|ม\.\s?[36]\b|มัธยม/u', $e)) {
        return 'below';
    }
    if (preg_match('/ปริญญาเอก|ปร\.ด|[ก-ฮ]+\.ด\.|Ph\.?\s?D|D\.Ed|Ed\.D|Dr\./iu', $e)) {
        return 'phd';
    }
    if (preg_match('/ปริญญาโท|[ก-ฮ]+\.ม\.|M\.(Sc|A|Ed|Eng|B\.A)|MBA|Master/iu', $e)) {
        return 'master';
    }
    return 'bachelor';
}

$byType = array_fill_keys(array_keys($types), 0);
$gender = ['m' => 0, 'f' => 0, '' => 0];
$ranks = [];
$edu = ['phd' => 0, 'master' => 0, 'bachelor' => 0, 'below' => 0, 'none' => 0];
$byDept = []; // [dept_id => [name, dept_type, executive, teacher, staff]]
foreach ($people as $p) {
    $byType[$p['type']]++;
    $gender[prefix_gender((string) $p['prefix'])]++;
    $edu[education_level((string) $p['education'])]++;
    if ($p['type'] !== 'staff') {
        $r = trim((string) $p['academic_rank']) ?: t('ยังไม่มีวิทยฐานะ / ไม่ระบุ');
        $ranks[$r] = ($ranks[$r] ?? 0) + 1;
    }
    if ($p['department_id'] && $p['type'] !== 'executive') {
        $id = (int) $p['department_id'];
        $byDept[$id] = $byDept[$id] ?? ['name' => tr($p, 'dept_name'), 'dept_type' => $p['dept_type'], 'teacher' => 0, 'staff' => 0];
        $byDept[$id][$p['type']]++;
    }
}
arsort($ranks);
$eduLabels = ['phd' => t('ปริญญาเอก'), 'master' => t('ปริญญาโท'), 'bachelor' => t('ปริญญาตรี'), 'below' => t('ต่ำกว่าปริญญาตรี'), 'none' => t('ไม่ระบุ')];
$pct = function ($n) use ($total) {
    return $total ? round($n * 100 / $total, 1) : 0;
};

// ครูต่อผู้เรียนรายแผนกวิชา (ภาคเรียนล่าสุด)
$term = latest_student_term();
$students = [];
$studentTotal = 0;
if ($term) {
    foreach (q('SELECT department_id, SUM(male + female) AS n FROM student_stats WHERE academic_year = ? AND semester = ? GROUP BY department_id', [$term['academic_year'], $term['semester']]) as $r) {
        $students[(int) $r['department_id']] = (int) $r['n'];
        $studentTotal += (int) $r['n'];
    }
}
$academic = q("SELECT id, name, name_en FROM departments WHERE type = 'academic' ORDER BY sort_order, id")->fetchAll();
$teacherCount = $byType['teacher'] + $byType['executive'];
$yr = function ($y) {
    return (int) $y - (lang() === 'en' ? 543 : 0);
};

// กราฟแผนก: เรียงตามจำนวนบุคลากร
uasort($byDept, function ($a, $b) {
    return ($b['teacher'] + $b['staff']) <=> ($a['teacher'] + $a['staff']);
});

$pageTitle = t('Dashboard บุคลากร');
require __DIR__ . '/includes/header.php';
echo page_banner($pageTitle, [t('บุคลากร') => url('personnel.php'), $pageTitle => null]);
?>
<section class="section dash">
  <div class="container">
    <?php if (!$total): ?>
      <p class="text-center text-muted py-5"><?= t('ไม่พบข้อมูลบุคลากร') ?></p>
    <?php else: ?>
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
          <div class="small text-uppercase fw-medium" style="color:var(--accent)"><i class="bi bi-speedometer2"></i> Personnel Dashboard</div>
          <h2 class="h4 section-title mb-0"><?= t('ข้อมูลครูและบุคลากร') ?></h2>
        </div>
        <a href="<?= url('personnel.php') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-lines-fill"></i> <?= t('ดูรายชื่อบุคลากร') ?></a>
      </div>

      <!-- ตัวเลขสำคัญ -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
          <div class="dash-hero h-100">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="lbl"><?= t('ครูและบุคลากรทั้งหมด') ?></div>
                <div class="big"><?= number_format($total) ?></div>
                <div class="lbl"><?= t('คน') ?></div>
              </div>
              <i class="bi bi-person-badge-fill dash-hero-icon"></i>
            </div>
            <?php if ($studentTotal && $teacherCount): ?>
              <div class="dash-delta mt-3"><i class="bi bi-people"></i> <?= t('ผู้เรียนต่อครู') ?> <?= round($studentTotal / $teacherCount) ?>:1</div>
            <?php endif; ?>
            <div class="dash-split mt-3">
              <div style="width:<?= $pct($gender['m']) ?>%" class="m"></div><div style="width:<?= $pct($gender['f']) ?>%" class="f"></div>
            </div>
            <div class="d-flex justify-content-between small mt-1 opacity-75 flex-wrap gap-1">
              <span><i class="bi bi-gender-male"></i> <?= t('ชาย') ?> <?= $gender['m'] ?></span>
              <span><i class="bi bi-gender-female"></i> <?= t('หญิง') ?> <?= $gender['f'] ?></span>
              <?php if ($gender['']): ?><span><?= t('ไม่ระบุ') ?> <?= $gender[''] ?></span><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-8">
          <div class="row g-3 h-100">
            <?php foreach (['executive' => 'bi-award', 'teacher' => 'bi-easel', 'staff' => 'bi-briefcase'] as $k => $icon): ?>
              <div class="col-6">
                <a class="dash-kpi h-100" href="<?= url('personnel.php?type=' . $k) ?>">
                  <div class="ic"><i class="bi <?= $icon ?>"></i></div>
                  <div><div class="num"><?= number_format($byType[$k]) ?></div><div class="lbl"><?= e($types[$k]) ?> · <?= $pct($byType[$k]) ?>%</div></div>
                </a>
              </div>
            <?php endforeach; ?>
            <div class="col-6">
              <div class="dash-kpi h-100">
                <div class="ic"><i class="bi bi-mortarboard"></i></div>
                <div><div class="num"><?= number_format($edu['phd'] + $edu['master']) ?></div><div class="lbl"><?= t('วุฒิปริญญาโทขึ้นไป') ?> · <?= $pct($edu['phd'] + $edu['master']) ?>%</div></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- กราฟ -->
      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="dash-card h-100">
            <h3><i class="bi bi-bar-chart-line"></i> <?= t('บุคลากรแยกตามฝ่าย / แผนกวิชา') ?></h3>
            <?php if ($byDept): ?>
              <div style="height:<?= max(240, count($byDept) * 36) ?>px"><canvas id="deptChart"></canvas></div>
            <?php else: ?>
              <div class="dash-empty"><i class="bi bi-diagram-3"></i><?= t('ยังไม่ได้ระบุฝ่าย/แผนกของบุคลากร') ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="row g-3 h-100">
            <div class="col-sm-6 col-lg-12">
              <div class="dash-card h-100">
                <h3><i class="bi bi-pie-chart"></i> <?= t('สัดส่วนประเภทบุคลากร') ?></h3>
                <div class="dash-donut"><canvas id="typeChart"></canvas></div>
              </div>
            </div>
            <div class="col-sm-6 col-lg-12">
              <div class="dash-card h-100">
                <h3><i class="bi bi-mortarboard"></i> <?= t('วุฒิการศึกษา') ?></h3>
                <div class="dash-donut"><canvas id="eduChart"></canvas></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-6">
          <div class="dash-card h-100">
            <h3><i class="bi bi-award"></i> <?= t('วิทยฐานะของครูและผู้บริหาร') ?></h3>
            <?php $maxRank = $ranks ? max($ranks) : 1; foreach ($ranks as $label => $n): ?>
              <div class="dash-rank">
                <div class="flex-grow-1 min-w-0">
                  <div class="d-flex justify-content-between gap-2"><span class="text-truncate"><?= e($label) ?></span><b class="text-nowrap"><?= $n ?> <small class="text-muted fw-normal"><?= t('คน') ?></small></b></div>
                  <div class="bar"><div class="m" style="width:<?= round($n * 100 / $maxRank, 1) ?>%"></div></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (!$ranks): ?><div class="dash-empty"><?= t('ไม่พบข้อมูลบุคลากร') ?></div><?php endif; ?>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="dash-card h-100 p-0 overflow-hidden">
            <h3 class="p-3 m-0"><i class="bi bi-people"></i> <?= t('ครูและผู้เรียนรายแผนกวิชา') ?>
              <?php if ($term): ?><small class="text-muted fw-normal">(<?= t('ภาคเรียน') ?> <?= (int) $term['semester'] ?>/<?= $yr($term['academic_year']) ?>)</small><?php endif; ?></h3>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light"><tr><th><?= t('แผนกวิชา') ?></th><th class="text-center"><?= t('ครู') ?></th><th class="text-center"><?= t('ผู้เรียน') ?></th><th class="text-center"><?= t('ผู้เรียนต่อครู') ?></th></tr></thead>
                <tbody>
                  <?php foreach ($academic as $d): $tc = $byDept[$d['id']]['teacher'] ?? 0; $sc = $students[$d['id']] ?? 0; ?>
                    <tr>
                      <td><a href="<?= url('personnel.php?dept=' . $d['id']) ?>" class="text-decoration-none"><?= e(tr($d, 'name')) ?></a></td>
                      <td class="text-center"><?= $tc ?: '-' ?></td>
                      <td class="text-center"><?= $sc ? number_format($sc) : '-' ?></td>
                      <td class="text-center"><?= $tc && $sc ? '<span class="badge rounded-pill text-bg-light border">' . round($sc / $tc) . ':1</span>' : '-' ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <p class="small text-muted"><?= t('หมายเหตุ: เพศคำนวณจากคำนำหน้าชื่อ และระดับวุฒิคำนวณจากข้อความวุฒิการศึกษาที่บันทึกไว้') ?></p>

      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
      <script>
        (() => {
          const css = getComputedStyle(document.documentElement);
          const blue = css.getPropertyValue('--primary').trim() || '#1e3a8a';
          const red = css.getPropertyValue('--accent').trim() || '#d71920';
          const mix = '#7b3a6e', sky = '#5b8def', grey = '#cfd4dc';
          Chart.defaults.font.family = 'Kanit, sans-serif';
          Chart.defaults.color = '#555';
          const legend = { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } };
          const donut = (id, labels, data, colors) => new Chart(document.getElementById(id), {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
            options: { cutout: '68%', maintainAspectRatio: false, plugins: { legend } }
          });

          donut('typeChart', <?= json_encode(array_values($types), JSON_UNESCAPED_UNICODE) ?>, <?= json_encode(array_values($byType)) ?>, [red, blue, sky]);
          <?php $eduShow = array_filter($edu); ?>
          donut('eduChart', <?= json_encode(array_values(array_intersect_key($eduLabels, $eduShow)), JSON_UNESCAPED_UNICODE) ?>, <?= json_encode(array_values($eduShow)) ?>,
            <?= json_encode(array_values(array_intersect_key(['phd' => '#d71920', 'master' => '#7b3a6e', 'bachelor' => '#1e3a8a', 'below' => '#5b8def', 'none' => '#cfd4dc'], $eduShow))) ?>);

          const dept = document.getElementById('deptChart');
          if (dept) {
            new Chart(dept, {
              type: 'bar',
              data: {
                labels: <?= json_encode(array_values(array_column($byDept, 'name')), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [
                  { label: <?= json_encode($types['teacher'], JSON_UNESCAPED_UNICODE) ?>, data: <?= json_encode(array_values(array_column($byDept, 'teacher'))) ?>, backgroundColor: blue, borderRadius: 4 },
                  { label: <?= json_encode($types['staff'], JSON_UNESCAPED_UNICODE) ?>, data: <?= json_encode(array_values(array_column($byDept, 'staff'))) ?>, backgroundColor: sky, borderRadius: 4 }
                ]
              },
              options: { indexAxis: 'y', maintainAspectRatio: false, scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }, y: { stacked: true, grid: { display: false } } }, plugins: { legend } }
            });
          }
        })();
      </script>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
