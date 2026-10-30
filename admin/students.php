<?php
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../includes/spreadsheet.php';

/** คอลัมน์ในแบบฟอร์มนำเข้า: [ระดับ, ชั้นปี, m|f, หัวคอลัมน์] เช่น ['ปวช.', 1, 'm', 'ปวช.1 ชาย'] */
function stat_columns(): array
{
    $cols = [];
    foreach (STUDENT_LEVELS as $level => $n) {
        for ($y = 1; $y <= $n; $y++) {
            $cols[] = [$level, $y, 'm', "$level$y ชาย"];
            $cols[] = [$level, $y, 'f', "$level$y หญิง"];
        }
    }
    return $cols;
}

/** "ปวช.1", "ปวช 1", "ปวส." → ['ปวช.', 1] (ชั้นปี 0 = ไม่ระบุ) */
function parse_level(string $s): ?array
{
    if (!preg_match('/(ปวช|ปวส)\.?\s*(\d)?/u', $s, $m)) {
        return null;
    }
    return [$m[1] . '.', isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0];
}

/** ชาย/หญิง จากข้อความที่ตัดระดับชั้นออกแล้ว (ตรวจหญิงก่อน เพราะ female มีคำว่า male) */
function parse_gender(string $s): ?string
{
    if (preg_match('/หญิง|ญ|female|^f$/iu', $s)) {
        return 'f';
    }
    if (preg_match('/ชาย|ช|male|^m$/iu', $s)) {
        return 'm';
    }
    return null;
}

/**
 * อ่านไฟล์จำนวนผู้เรียน รองรับ 2 รูปแบบ
 * - แบบตาราง: แผนกวิชา | ปวช.1 ชาย | ปวช.1 หญิง | ... (แบบฟอร์มจากหน้านี้)
 * - แบบรายการ: แผนกวิชา | ระดับ | ชั้นปี | ชาย | หญิง (แถวซ้ำ เช่น แยกห้อง จะรวมกัน)
 * คืน ['data' => [dept][level][ชั้นปี][m|f], 'notes' => [...], 'rows' => จำนวนแถวที่ใช้ได้]
 */
function parse_student_import(string $path, string $ext, array $depts): array
{
    $rows = read_table_file($path, $ext);
    if (count($rows) < 2) {
        throw new RuntimeException('ไม่พบข้อมูลในไฟล์ (ต้องมีแถวหัวคอลัมน์และข้อมูลอย่างน้อย 1 แถว)');
    }
    $header = array_shift($rows);
    $deptCol = $levelCol = $yearCol = $mCol = $fCol = null;
    $wide = [];
    foreach ($header as $i => $h) {
        $h = mb_strtolower(preg_replace('/\s+/u', '', str_replace("\xEF\xBB\xBF", '', $h)));
        if ($deptCol === null && preg_match('/^(แผนกวิชา|แผนก|สาขาวิชา|สาขา|department)/u', $h)) {
            $deptCol = $i;
        } elseif (in_array($h, ['ระดับ', 'ระดับชั้น', 'ระดับการศึกษา', 'level'], true)) {
            $levelCol = $i;
        } elseif (in_array($h, ['ชั้นปี', 'ชั้น', 'ปี', 'year'], true)) {
            $yearCol = $i;
        } elseif (in_array($h, ['ชาย', 'ช', 'male', 'm'], true)) {
            $mCol = $i;
        } elseif (in_array($h, ['หญิง', 'ญ', 'female', 'f'], true)) {
            $fCol = $i;
        } elseif (($lv = parse_level($h)) && $lv[1] >= 1 && $lv[1] <= (STUDENT_LEVELS[$lv[0]] ?? 0)) {
            // คอลัมน์ "รวม" ไม่มีเพศ จึงถูกข้าม
            $g = parse_gender(preg_replace('/(ปวช|ปวส)\.?\d/u', '', $h));
            if ($g) {
                $wide[$i] = [$lv[0], $lv[1], $g];
            }
        }
    }
    if ($deptCol === null) {
        throw new RuntimeException('ไม่พบคอลัมน์ "แผนกวิชา" — กรุณาใช้แบบฟอร์มที่ดาวน์โหลดจากหน้านี้');
    }
    if (!$wide && ($levelCol === null || ($mCol === null && $fCol === null))) {
        throw new RuntimeException('ไม่พบคอลัมน์จำนวนผู้เรียน (เช่น "ปวช.1 ชาย" หรือ "ระดับ / ชั้นปี / ชาย / หญิง") — กรุณาใช้แบบฟอร์มที่ดาวน์โหลดจากหน้านี้');
    }

    $strip = function (string $s): string {
        return mb_strtolower(preg_replace('/^(แผนกวิชา|แผนก|สาขาวิชา|สาขา)\s*/u', '', preg_replace('/\s+/u', ' ', trim($s))));
    };
    $lookup = [];
    foreach ($depts as $d) {
        $lookup[$strip($d['name'])] = (int) $d['id'];
        if (!empty($d['name_en'])) {
            $lookup[$strip($d['name_en'])] = (int) $d['id'];
        }
    }
    $num = function ($v): int {
        return (int) preg_replace('/[^\d]/', '', (string) $v);
    };

    $data = [];
    $notes = [];
    $used = 0;
    foreach ($rows as $n => $cells) {
        $name = trim($cells[$deptCol] ?? '');
        if ($name === '' || preg_match('/^(รวม|total)/iu', $name)) {
            continue;
        }
        $id = $lookup[$strip($name)] ?? null;
        if (!$id) {
            $notes[] = 'แถวที่ ' . ($n + 2) . ': ไม่พบแผนกวิชา "' . $name . '" (ข้าม)';
            continue;
        }
        $data[$id] = $data[$id] ?? [];
        if ($wide) {
            foreach ($wide as $i => [$level, $y, $g]) {
                $data[$id][$level][$y][$g] = ($data[$id][$level][$y][$g] ?? 0) + $num($cells[$i] ?? '');
            }
        } else {
            $lv = parse_level((string) ($cells[$levelCol] ?? ''));
            $y = $yearCol !== null ? $num($cells[$yearCol] ?? '') : ($lv[1] ?? 0);
            if (!$lv || $y < 1 || $y > STUDENT_LEVELS[$lv[0]]) {
                $notes[] = 'แถวที่ ' . ($n + 2) . ': ระดับหรือชั้นปีไม่ถูกต้อง (ข้าม)';
                continue;
            }
            foreach (['m' => $mCol, 'f' => $fCol] as $g => $col) {
                if ($col !== null) {
                    $data[$id][$lv[0]][$y][$g] = ($data[$id][$lv[0]][$y][$g] ?? 0) + $num($cells[$col] ?? '');
                }
            }
        }
        $used++;
    }
    if (!$used) {
        throw new RuntimeException('ไม่พบแถวข้อมูลที่ตรงกับแผนกวิชาในระบบ' . ($notes ? ' — ' . $notes[0] : ''));
    }
    return ['data' => $data, 'notes' => $notes, 'rows' => $used];
}

$thisYear = (int) date('Y') + 543;
$year = (int) ($_GET['year'] ?? 0);
$sem = (int) ($_GET['sem'] ?? 0);
if (!$year) {
    $latest = q('SELECT academic_year, semester FROM student_stats ORDER BY academic_year DESC, semester DESC LIMIT 1')->fetch();
    $year = $latest ? (int) $latest['academic_year'] : $thisYear;
    $sem = $latest ? (int) $latest['semester'] : 1;
}
$sem = in_array($sem, [1, 2, 3], true) ? $sem : 1;

$depts = q("SELECT id, name, name_en FROM departments WHERE type = 'academic' ORDER BY sort_order, id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['cancel_import'])) {
        unset($_SESSION['student_import']);
        redirect("admin/students.php?year=$year&sem=$sem");
    }
    if (isset($_POST['import'])) {
        // นำเข้าไฟล์ → เติมตัวเลขลงตารางให้ตรวจก่อน (ยังไม่บันทึกจนกว่าจะกดบันทึก)
        $f = $_FILES['file'] ?? null;
        try {
            if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException('กรุณาเลือกไฟล์');
            }
            if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1048576) {
                throw new RuntimeException('อัปโหลดไม่สำเร็จ หรือไฟล์ใหญ่เกิน 5 MB');
            }
            $parsed = parse_student_import($f['tmp_name'], pathinfo($f['name'], PATHINFO_EXTENSION), $depts);
            $_SESSION['student_import'] = ['year' => $year, 'sem' => $sem, 'file' => $f['name']] + $parsed;
        } catch (RuntimeException $ex) {
            flash($ex->getMessage(), 'danger');
        }
        redirect("admin/students.php?year=$year&sem=$sem");
    }
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
    unset($_SESSION['student_import']);
    flash("บันทึกข้อมูลผู้เรียน ภาคเรียนที่ $sem/$year เรียบร้อย");
    redirect("admin/students.php?year=$year&sem=$sem");
}

$data = [];
foreach (q('SELECT * FROM student_stats WHERE academic_year = ? AND semester = ?', [$year, $sem]) as $r) {
    $data[$r['department_id']][$r['level']][$r['class_year']] = $r;
}

// ---------- ดาวน์โหลดแบบฟอร์ม (เติมตัวเลขของภาคเรียนที่เปิดอยู่ ใช้สำรองข้อมูลได้ด้วย) ----------
if (($_GET['template'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"students-$year-$sem.csv\"");
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM ให้ Excel อ่านภาษาไทยถูก
    fputcsv($out, array_merge(['แผนกวิชา'], array_column(stat_columns(), 3)), ',', '"', '');
    foreach ($depts as $d) {
        $line = [$d['name']];
        foreach (stat_columns() as [$level, $y, $g]) {
            $c = $data[$d['id']][$level][$y] ?? null;
            $line[] = $c ? (int) $c[$g === 'm' ? 'male' : 'female'] : 0;
        }
        fputcsv($out, $line, ',', '"', '');
    }
    fclose($out);
    exit;
}

// ข้อมูลจากไฟล์ที่นำเข้า (รอตรวจและบันทึก) — แผนกที่ไม่มีในไฟล์ใช้ตัวเลขเดิม
$import = $_SESSION['student_import'] ?? null;
if ($import && ((int) $import['year'] !== $year || (int) $import['sem'] !== $sem)) {
    $import = null;
}
$changed = 0;
$cell = function (int $deptId, string $level, int $y, string $g) use ($data, $import, &$changed): array {
    $c = $data[$deptId][$level][$y] ?? null;
    $old = $c ? (int) $c[$g === 'm' ? 'male' : 'female'] : 0;
    if ($import && isset($import['data'][$deptId])) {
        $new = (int) ($import['data'][$deptId][$level][$y][$g] ?? 0);
        if ($new !== $old) {
            $changed++;
        }
        return [$new ?: '', $new !== $old, $old];
    }
    return [$c ? $old : '', false, $old];
};
if ($import) {
    foreach ($depts as $d) {
        foreach (stat_columns() as [$level, $y, $g]) {
            $cell((int) $d['id'], $level, $y, $g);
        }
    }
}
$changedCount = $changed;
$terms = q('SELECT academic_year, semester, SUM(male + female) AS total FROM student_stats GROUP BY academic_year, semester ORDER BY academic_year DESC, semester DESC')->fetchAll();

admin_header('ข้อมูลผู้เรียน', 'students');
?>
<?php if ($import): ?>
  <div class="alert alert-warning d-flex flex-wrap gap-2 align-items-center">
    <div class="me-auto">
      <i class="bi bi-file-earmark-spreadsheet"></i> นำตัวเลขจาก <b><?= e($import['file']) ?></b> (<?= (int) $import['rows'] ?> แถว) ลงตารางแล้ว
      — เปลี่ยนแปลง <b><?= $changedCount ?></b> ช่อง (ไฮไลต์สีเหลือง ชี้เมาส์เพื่อดูค่าเดิม)
      <div class="small">ตรวจสอบแล้วกด <b>บันทึกภาคเรียน <?= $sem ?>/<?= $year ?></b> ด้านล่าง · ยังไม่บันทึกจนกว่าจะกดปุ่ม</div>
      <?php foreach ($import['notes'] as $note): ?><div class="small text-danger"><?= e($note) ?></div><?php endforeach; ?>
    </div>
    <form method="post"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary" name="cancel_import" value="1">ยกเลิกการนำเข้า</button></form>
  </div>
<?php endif; ?>
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
                  <?php foreach (STUDENT_LEVELS as $level => $n): for ($y = 1; $y <= $n; $y++): foreach (['m', 'f'] as $g): [$val, $diff, $old] = $cell((int) $d['id'], $level, $y, $g); ?>
                    <td><input type="number" min="0" class="form-control form-control-sm input-stat<?= $diff ? ' is-imported' : '' ?>" name="s[<?= $d['id'] ?>][<?= e($level) ?>][<?= $y ?>][<?= $g ?>]" value="<?= $val ?>" placeholder="0"<?= $diff ? ' title="เดิม ' . $old . '"' : '' ?>></td>
                  <?php endforeach; endfor; endforeach; ?>
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
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-file-earmark-arrow-up"></i> นำเข้าจาก Excel / CSV</div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data" action="?year=<?= $year ?>&sem=<?= $sem ?>">
          <?= csrf_field() ?>
          <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.csv" required>
          <button class="btn btn-sm btn-primary w-100" name="import" value="1"><i class="bi bi-upload"></i> นำเข้าภาคเรียน <?= $sem ?>/<?= $year ?></button>
        </form>
        <a href="?year=<?= $year ?>&sem=<?= $sem ?>&template=csv" class="btn btn-sm btn-outline-success w-100 mt-2"><i class="bi bi-download"></i> ดาวน์โหลดแบบฟอร์ม</a>
        <div class="small text-muted mt-2">
          แบบฟอร์มมีรายชื่อแผนกและตัวเลขปัจจุบันให้แล้ว แก้ใน Excel แล้วอัปโหลดกลับ
          · รองรับไฟล์แบบรายการ <code>แผนกวิชา, ระดับ, ชั้นปี, ชาย, หญิง</code> (แยกห้องได้ ระบบรวมให้)
        </div>
      </div>
    </div>
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
