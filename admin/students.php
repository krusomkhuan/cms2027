<?php
require_once __DIR__ . '/_layout.php';

$thisYear = (int) date('Y') + 543;
$year = (int) ($_GET['year'] ?? 0);
$sem = (int) ($_GET['sem'] ?? 0);
if (!$year) {
    $latest = q('SELECT academic_year, semester FROM student_stats ORDER BY academic_year DESC, semester DESC LIMIT 1')->fetch();
    $year = $latest ? (int) $latest['academic_year'] : $thisYear;
    $sem = $latest ? (int) $latest['semester'] : 1;
}
$sem = in_array($sem, [1, 2, 3], true) ? $sem : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete_term'])) {
        q('DELETE FROM student_stats WHERE academic_year = ? AND semester = ?', [$year, $sem]);
        flash("ลบข้อมูลภาคเรียน $sem/$year เรียบร้อย");
        redirect('admin/students.php');
    }
    $up = db()->prepare('INSERT INTO student_stats (academic_year, semester, level, department_id, class_year, male, female) VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE male = VALUES(male), female = VALUES(female)');
    $del = db()->prepare('DELETE FROM student_stats WHERE academic_year = ? AND semester = ? AND level = ? AND department_id = ? AND class_year = ?');
    db()->beginTransaction();
    foreach ($_POST['s'] ?? [] as $deptId => $levels) {
        foreach ($levels as $level => $years) {
            if (!isset(STUDENT_LEVELS[$level])) {
                continue;
            }
            foreach ($years as $cy => $mf) {
                $cy = (int) $cy;
                if ($cy < 1 || $cy > STUDENT_LEVELS[$level]) {
                    continue;
                }
                $m = max(0, (int) ($mf['m'] ?? 0));
                $f = max(0, (int) ($mf['f'] ?? 0));
                if ($m + $f > 0) {
                    $up->execute([$year, $sem, $level, (int) $deptId, $cy, $m, $f]);
                } else {
                    $del->execute([$year, $sem, $level, (int) $deptId, $cy]);
                }
            }
        }
    }
    db()->commit();
    flash("บันทึกข้อมูลผู้เรียน ภาคเรียนที่ $sem/$year เรียบร้อย");
    redirect("admin/students.php?year=$year&sem=$sem");
}

$depts = q("SELECT id, name FROM departments WHERE type = 'academic' ORDER BY sort_order, id")->fetchAll();
$data = [];
foreach (q('SELECT * FROM student_stats WHERE academic_year = ? AND semester = ?', [$year, $sem]) as $r) {
    $data[$r['department_id']][$r['level']][$r['class_year']] = $r;
}
$terms = q('SELECT academic_year, semester, SUM(male + female) AS total FROM student_stats GROUP BY academic_year, semester ORDER BY academic_year DESC, semester DESC')->fetchAll();

admin_header('ข้อมูลผู้เรียน', 'students');
?>
<div class="row g-3">
  <div class="col-xl-9">
    <div class="card">
      <div class="card-header">
        <form class="d-flex flex-wrap gap-2 align-items-center" method="get">
          <span>ปีการศึกษา</span>
          <input type="number" name="year" class="form-control form-control-sm" style="width:100px" value="<?= $year ?>" min="2500" max="2700">
          <span>ภาคเรียน</span>
          <select name="sem" class="form-select form-select-sm" style="width:auto">
            <?php foreach ([1, 2, 3] as $s): ?><option value="<?= $s ?>" <?= $sem === $s ? 'selected' : '' ?>><?= $s === 3 ? '3 (ฤดูร้อน)' : $s ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-outline-secondary">เปิด</button>
        </form>
      </div>
      <form method="post">
        <?= csrf_field() ?>
        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0 text-center align-middle">
            <thead class="table-light">
              <tr>
                <th rowspan="2" class="text-start align-middle">แผนกวิชา</th>
                <?php foreach (STUDENT_LEVELS as $level => $n): for ($y = 1; $y <= $n; $y++): ?><th colspan="2"><?= e($level) ?> <?= $y ?></th><?php endfor; endforeach; ?>
              </tr>
              <tr>
                <?php foreach (STUDENT_LEVELS as $level => $n): for ($y = 1; $y <= $n; $y++): ?><th class="small">ช</th><th class="small">ญ</th><?php endfor; endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($depts as $d): ?>
                <tr>
                  <td class="text-start text-nowrap small"><?= e($d['name']) ?></td>
                  <?php foreach (STUDENT_LEVELS as $level => $n): for ($y = 1; $y <= $n; $y++): $c = $data[$d['id']][$level][$y] ?? null; ?>
                    <td><input type="number" min="0" class="form-control form-control-sm input-stat" name="s[<?= $d['id'] ?>][<?= e($level) ?>][<?= $y ?>][m]" value="<?= $c ? (int) $c['male'] : '' ?>" placeholder="0"></td>
                    <td><input type="number" min="0" class="form-control form-control-sm input-stat" name="s[<?= $d['id'] ?>][<?= e($level) ?>][<?= $y ?>][f]" value="<?= $c ? (int) $c['female'] : '' ?>" placeholder="0"></td>
                  <?php endfor; endforeach; ?>
                </tr>
              <?php endforeach; ?>
              <?php if (!$depts): ?><tr><td colspan="20" class="text-muted py-4">ยังไม่มีแผนกวิชา — เพิ่มได้ที่เมนู “ฝ่าย / แผนกวิชา”</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer bg-white d-flex flex-wrap gap-2 justify-content-between">
          <span class="small text-muted align-self-center">ช = ชาย, ญ = หญิง · ช่องว่างหรือ 0 = ไม่มีผู้เรียน</span>
          <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึกภาคเรียน <?= $sem ?>/<?= $year ?></button>
        </div>
      </form>
    </div>
  </div>
  <div class="col-xl-3">
    <div class="card">
      <div class="card-header">ภาคเรียนที่มีข้อมูล</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($terms as $t): $active = (int) $t['academic_year'] === $year && (int) $t['semester'] === $sem; ?>
          <a href="?year=<?= (int) $t['academic_year'] ?>&sem=<?= (int) $t['semester'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between <?= $active ? 'active' : '' ?>">
            <span><?= (int) $t['semester'] ?>/<?= (int) $t['academic_year'] ?></span><span><?= number_format($t['total']) ?> คน</span>
          </a>
        <?php endforeach; ?>
        <?php if (!$terms): ?><li class="list-group-item text-muted small">ยังไม่มีข้อมูล</li><?php endif; ?>
      </ul>
      <?php if ($data): ?>
        <div class="card-body">
          <form method="post" data-confirm="ลบข้อมูลผู้เรียนทั้งหมดของภาคเรียน <?= $sem ?>/<?= $year ?>?">
            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger w-100" name="delete_term" value="1"><i class="bi bi-trash"></i> ลบข้อมูลภาคเรียนนี้</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
    <p class="small text-muted mt-2">หน้าเว็บไซต์จะแสดงภาคเรียนล่าสุดเป็นค่าเริ่มต้น และผู้ชมเลือกดูภาคเรียนก่อนหน้าได้</p>
  </div>
</div>
<?php admin_footer(); ?>
