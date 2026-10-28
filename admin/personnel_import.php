<?php
// นำเข้าข้อมูลบุคลากรจากไฟล์ Excel (.xlsx) หรือ CSV: อัปโหลด → ตรวจสอบตัวอย่าง → ยืนยันนำเข้า
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../includes/spreadsheet.php';

// หัวคอลัมน์ที่รองรับ (ตัวพิมพ์เล็ก/ช่องว่างไม่มีผล) → ฟิลด์
const IMPORT_COLUMNS = [
    'prefix' => ['คำนำหน้า', 'คำนำหน้าชื่อ', 'prefix', 'title'],
    'fullname' => ['ชื่อ-สกุล', 'ชื่อ-นามสกุล', 'ชื่อสกุล', 'ชื่อ นามสกุล', 'fullname', 'full name'],
    'firstname' => ['ชื่อ', 'firstname', 'first name'],
    'lastname' => ['นามสกุล', 'สกุล', 'lastname', 'last name', 'surname'],
    'position' => ['ตำแหน่ง', 'position'],
    'academic_rank' => ['วิทยฐานะ', 'วิทยฐานะ/ระดับ', 'ระดับ', 'academic rank', 'rank'],
    'type' => ['ประเภท', 'ประเภทบุคลากร', 'type'],
    'department' => ['ฝ่าย/แผนกวิชา', 'ฝ่าย / แผนกวิชา', 'แผนกวิชา', 'แผนก', 'ฝ่าย', 'สังกัด', 'department'],
    'education' => ['วุฒิการศึกษา', 'วุฒิ', 'education'],
    'phone' => ['เบอร์โทร', 'โทรศัพท์', 'เบอร์โทรศัพท์', 'phone', 'tel'],
    'email' => ['อีเมล', 'อีเมล์', 'email', 'e-mail'],
    'sort_order' => ['ลำดับ', 'ลำดับการแสดง', 'sort', 'order'],
    'name_en' => ['ชื่อ-สกุล (en)', 'ชื่อภาษาอังกฤษ', 'name (en)', 'name_en', 'english name'],
    'position_en' => ['ตำแหน่ง (en)', 'ตำแหน่งภาษาอังกฤษ', 'position (en)', 'position_en'],
];
const TEMPLATE_HEADERS = ['คำนำหน้า', 'ชื่อ', 'นามสกุล', 'ตำแหน่ง', 'วิทยฐานะ', 'ประเภท', 'ฝ่าย/แผนกวิชา', 'วุฒิการศึกษา', 'เบอร์โทร', 'อีเมล', 'ลำดับ', 'ชื่อ-สกุล (EN)', 'ตำแหน่ง (EN)'];
const THAI_PREFIXES = ['ว่าที่ร้อยตรีหญิง', 'ว่าที่ร้อยตรี', 'ว่าที่ร้อยโท', 'ว่าที่ร้อยเอก', 'ว่าที่ ร.ต.', 'ว่าที่ร.ต.', 'นางสาว', 'น.ส.', 'นาง', 'นาย', 'ดร.', 'ผศ.', 'รศ.', 'ศ.', 'Mr.', 'Mrs.', 'Ms.', 'Miss', 'Dr.'];

// ---------- ดาวน์โหลดแบบฟอร์ม ----------
if (($_GET['template'] ?? '') === 'csv') {
    $depts = q('SELECT name FROM departments ORDER BY type, sort_order, id')->fetchAll(PDO::FETCH_COLUMN);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="personnel-template.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM ให้ Excel อ่านภาษาไทยถูก
    fputcsv($out, TEMPLATE_HEADERS, ',', '"', '');
    fputcsv($out, ['นาย', 'ตัวอย่าง', 'ลบแถวนี้', 'ครู', 'ครูชำนาญการ', 'ครูผู้สอน', $depts[4] ?? 'แผนกวิชาช่างยนต์', 'ค.อ.บ. วิศวกรรมเครื่องกล', '0812345678', 'example@pticec.ac.th', '1', 'Mr. Example Name', 'Teacher'], ',', '"', '');
    fputcsv($out, ['นางสาว', 'ตัวอย่าง', 'ลบแถวนี้', 'เจ้าหน้าที่งานทะเบียน', '', 'บุคลากรสนับสนุน', $depts[0] ?? 'ฝ่ายบริหารทรัพยากร', '', '', '', '1', '', ''], ',', '"', '');
    fclose($out);
    exit;
}

function norm_header(string $h): string
{
    return mb_strtolower(preg_replace('/\s+/u', ' ', trim(str_replace("\xEF\xBB\xBF", '', $h))));
}

function split_thai_name(string $full): array
{
    $full = trim(preg_replace('/\s+/u', ' ', $full));
    $prefix = '';
    foreach (THAI_PREFIXES as $p) {
        if (mb_stripos($full, $p) === 0) {
            $prefix = $p;
            $full = trim(mb_substr($full, mb_strlen($p)));
            break;
        }
    }
    $parts = explode(' ', $full, 2);
    return [$prefix, $parts[0] ?? '', $parts[1] ?? ''];
}

function map_type(string $v): ?string
{
    $v = mb_strtolower(trim($v));
    if ($v === '') {
        return null;
    }
    foreach (['executive' => ['ผู้บริหาร', 'บริหาร', 'executive'], 'staff' => ['บุคลากรสนับสนุน', 'สนับสนุน', 'เจ้าหน้าที่', 'staff'], 'teacher' => ['ครูผู้สอน', 'ครู', 'อาจารย์', 'teacher']] as $k => $words) {
        foreach ($words as $w) {
            if (str_contains($v, $w)) {
                return $k;
            }
        }
    }
    return null;
}

/** อ่านไฟล์ แล้วตรวจสอบทุกแถว คืน [แถวที่แปลงแล้ว, หัวคอลัมน์ที่ไม่รู้จัก] */
function parse_import(string $path, string $ext): array
{
    $rows = read_table_file($path, $ext);
    if (count($rows) < 2) {
        throw new RuntimeException('ไม่พบข้อมูลในไฟล์ (ต้องมีแถวหัวคอลัมน์และข้อมูลอย่างน้อย 1 แถว)');
    }
    if (count($rows) > 2001) {
        throw new RuntimeException('นำเข้าได้ครั้งละไม่เกิน 2,000 คน');
    }

    // จับคู่หัวคอลัมน์ (ชื่อที่ยาวกว่าก่อน เพื่อไม่ให้ "ชื่อ" ไปจับ "ชื่อ-สกุล")
    $header = array_map('norm_header', array_shift($rows));
    $map = [];
    $unknown = [];
    foreach ($header as $i => $h) {
        $found = null;
        foreach (IMPORT_COLUMNS as $field => $aliases) {
            if (in_array($h, array_map('norm_header', $aliases), true) && !in_array($field, $map, true)) {
                $found = $field;
                break;
            }
        }
        if ($found) {
            $map[$i] = $found;
        } elseif ($h !== '') {
            $unknown[] = $h;
        }
    }
    if (!in_array('firstname', $map, true) && !in_array('fullname', $map, true)) {
        throw new RuntimeException('ไม่พบคอลัมน์ "ชื่อ" หรือ "ชื่อ-สกุล" — กรุณาใช้แบบฟอร์มที่ดาวน์โหลดจากหน้านี้');
    }

    $depts = q('SELECT id, name, name_en FROM departments')->fetchAll();
    $findDept = function (string $name) use ($depts): ?int {
        $n = mb_strtolower(trim($name));
        foreach ($depts as $d) {
            $cands = [mb_strtolower($d['name']), mb_strtolower((string) $d['name_en']),
                mb_strtolower(preg_replace('/^(แผนกวิชา|ฝ่าย)\s*/u', '', $d['name']))];
            if (in_array($n, $cands, true) || in_array(preg_replace('/^(แผนกวิชา|แผนก|ฝ่าย)\s*/u', '', $n), $cands, true)) {
                return (int) $d['id'];
            }
        }
        return null;
    };
    $existing = [];
    foreach (q('SELECT id, firstname, lastname FROM personnel') as $p) {
        $existing[mb_strtolower($p['firstname'] . ' ' . $p['lastname'])] = (int) $p['id'];
    }

    $result = [];
    $seen = [];
    foreach ($rows as $n => $cells) {
        $v = [];
        foreach ($map as $i => $field) {
            $v[$field] = trim($cells[$i] ?? '');
        }
        $notes = [];
        $prefix = $v['prefix'] ?? '';
        $first = $v['firstname'] ?? '';
        $last = $v['lastname'] ?? '';
        if (($first === '' || $last === '') && !empty($v['fullname'])) {
            [$p2, $first, $last] = split_thai_name($v['fullname']);
            $prefix = $prefix ?: $p2;
        }
        $type = map_type($v['type'] ?? '');
        if (!$type) {
            $type = 'teacher';
            if (($v['type'] ?? '') !== '') {
                $notes[] = 'ไม่รู้จักประเภท "' . $v['type'] . '" ใช้ "ครูผู้สอน"';
            }
        }
        $deptId = null;
        if (($v['department'] ?? '') !== '') {
            $deptId = $findDept($v['department']);
            if (!$deptId) {
                $notes[] = 'ไม่พบฝ่าย/แผนก "' . $v['department'] . '" (เว้นว่าง)';
            }
        }
        $phone = preg_replace('/[^0-9+\-, ]/', '', $v['phone'] ?? '');
        if (preg_match('/^[1-9]\d{8}$/', $phone)) {
            $phone = '0' . $phone; // Excel ตัดเลข 0 หน้าเบอร์มือถือ
        }
        $email = $v['email'] ?? '';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $notes[] = 'อีเมลไม่ถูกต้อง (ไม่นำเข้าอีเมล)';
            $email = '';
        }

        $key = mb_strtolower($first . ' ' . $last);
        $status = 'new';
        if ($first === '' || $last === '') {
            $status = 'error';
            $notes[] = 'ไม่มีชื่อหรือนามสกุล';
        } elseif (isset($seen[$key])) {
            $status = 'error';
            $notes[] = 'ชื่อซ้ำกับแถวที่ ' . $seen[$key];
        } elseif (isset($existing[$key])) {
            $status = 'exists';
        }
        $seen[$key] = $n + 2;

        $result[] = [
            'line' => $n + 2, 'status' => $status, 'notes' => $notes, 'existing_id' => $existing[$key] ?? null,
            'data' => [
                'prefix' => mb_substr($prefix, 0, 50), 'firstname' => mb_substr($first, 0, 100), 'lastname' => mb_substr($last, 0, 100),
                'position' => mb_substr($v['position'] ?? '', 0, 150), 'academic_rank' => mb_substr($v['academic_rank'] ?? '', 0, 100),
                'type' => $type, 'department_id' => $deptId, 'education' => mb_substr($v['education'] ?? '', 0, 255),
                'phone' => mb_substr($phone, 0, 50), 'email' => mb_substr($email, 0, 150),
                'sort_order' => (int) ($v['sort_order'] ?? 0),
                'name_en' => mb_substr($v['name_en'] ?? '', 0, 200) ?: null, 'position_en' => mb_substr($v['position_en'] ?? '', 0, 150) ?: null,
            ],
        ];
    }
    return [$result, $unknown];
}

// ---------- ขั้นที่ 1: อัปโหลดและตรวจสอบ / ขั้นที่ 2: ยืนยันนำเข้า ----------
$preview = $_SESSION['personnel_import'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['cancel'])) {
        unset($_SESSION['personnel_import']);
        redirect('admin/personnel_import.php');
    }
    if (isset($_POST['confirm']) && $preview) {
        $update = ($_POST['mode'] ?? '') === 'update';
        $added = $updated = $skipped = 0;
        db()->beginTransaction();
        try {
            foreach ($preview['rows'] as $r) {
                $d = $r['data'];
                if ($r['status'] === 'new') {
                    $cols = array_keys($d);
                    q('INSERT INTO personnel (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($d));
                    $added++;
                } elseif ($r['status'] === 'exists' && $update) {
                    // อัปเดตเฉพาะช่องที่มีค่าในไฟล์ ไม่ลบข้อมูลเดิมที่ไฟล์เว้นว่าง
                    $set = array_filter($d, fn($val, $k) => !in_array($k, ['firstname', 'lastname'], true) && $val !== '' && $val !== null
                        && !($k === 'sort_order' && $val === 0), ARRAY_FILTER_USE_BOTH);
                    $set['type'] = $d['type'];
                    q('UPDATE personnel SET ' . implode(', ', array_map(fn($c) => "$c = ?", array_keys($set))) . ' WHERE id = ?', [...array_values($set), $r['existing_id']]);
                    $updated++;
                } else {
                    $skipped++;
                }
            }
            db()->commit();
        } catch (Throwable $ex) {
            db()->rollBack();
            flash('นำเข้าไม่สำเร็จ: ' . $ex->getMessage(), 'danger');
            redirect('admin/personnel_import.php');
        }
        unset($_SESSION['personnel_import']);
        flash("นำเข้าเรียบร้อย: เพิ่มใหม่ $added คน, อัปเดต $updated คน, ข้าม $skipped แถว");
        redirect('admin/personnel.php');
    }
    // อัปโหลดไฟล์ใหม่
    $f = $_FILES['file'] ?? null;
    try {
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('กรุณาเลือกไฟล์');
        }
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1048576) {
            throw new RuntimeException('อัปโหลดไม่สำเร็จ หรือไฟล์ใหญ่เกิน 5 MB');
        }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        [$rows, $unknown] = parse_import($f['tmp_name'], $ext);
        $_SESSION['personnel_import'] = ['file' => $f['name'], 'rows' => $rows, 'unknown' => $unknown];
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'danger');
    }
    redirect('admin/personnel_import.php');
}

$types = personnel_types();
$deptNames = q('SELECT id, name FROM departments')->fetchAll(PDO::FETCH_KEY_PAIR);
$counts = ['new' => 0, 'exists' => 0, 'error' => 0];
foreach ($preview['rows'] ?? [] as $r) {
    $counts[$r['status']]++;
}

admin_header('นำเข้าข้อมูลบุคลากร', 'personnel');
?>
<?php if (!$preview): ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card"><div class="card-header"><i class="bi bi-upload"></i> อัปโหลดไฟล์</div><div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="file" name="file" class="form-control form-control-lg mb-2" accept=".xlsx,.csv" required>
        <div class="form-text mb-3">รองรับไฟล์ Excel (.xlsx) และ CSV · ไม่เกิน 5 MB · ครั้งละไม่เกิน 2,000 คน · ระบบจะแสดงตัวอย่างให้ตรวจก่อนบันทึกจริง</div>
        <button class="btn btn-primary"><i class="bi bi-search"></i> ตรวจสอบข้อมูล</button>
        <a href="personnel.php" class="btn btn-light">กลับ</a>
      </form>
    </div></div>
  </div>
  <div class="col-lg-5">
    <div class="card"><div class="card-header"><i class="bi bi-file-earmark-spreadsheet"></i> แบบฟอร์มนำเข้า</div><div class="card-body">
      <a href="?template=csv" class="btn btn-success mb-3"><i class="bi bi-download"></i> ดาวน์โหลดแบบฟอร์ม (เปิดด้วย Excel ได้)</a>
      <ol class="small ps-3 mb-3">
        <li>เปิดแบบฟอร์มด้วย Excel แล้วลบแถวตัวอย่าง</li>
        <li>กรอกข้อมูล 1 คนต่อ 1 แถว — จำเป็นเฉพาะ <b>ชื่อ</b> และ <b>นามสกุล</b></li>
        <li>บันทึกเป็น .xlsx หรือ .csv แล้วอัปโหลด</li>
      </ol>
      <div class="small text-muted">
        <div><b>ประเภท:</b> ผู้บริหาร / ครูผู้สอน / บุคลากรสนับสนุน</div>
        <div><b>ฝ่าย/แผนกวิชา:</b> พิมพ์ให้ตรงกับชื่อในระบบ เช่น <?= e(implode(', ', array_slice(array_values($deptNames), 4, 3))) ?></div>
        <div><b>ใช้รายชื่อเดิมได้:</b> ถ้าไฟล์มีคอลัมน์ “ชื่อ-สกุล” ช่องเดียว ระบบจะแยกคำนำหน้า ชื่อ และนามสกุลให้อัตโนมัติ</div>
        <div><b>รูปภาพ:</b> เพิ่มทีละคนที่หน้าแก้ไขข้อมูลบุคลากร</div>
      </div>
    </div></div>
  </div>
</div>
<?php else: ?>
<div class="card mb-3"><div class="card-body d-flex flex-wrap gap-3 align-items-center">
  <div class="me-auto"><i class="bi bi-file-earmark-spreadsheet text-success"></i> <b><?= e($preview['file']) ?></b> — <?= count($preview['rows']) ?> แถว</div>
  <span class="badge text-bg-success fs-6">เพิ่มใหม่ <?= $counts['new'] ?></span>
  <span class="badge text-bg-info fs-6">มีอยู่แล้ว <?= $counts['exists'] ?></span>
  <span class="badge text-bg-danger fs-6">ข้าม (ผิดพลาด) <?= $counts['error'] ?></span>
</div></div>
<?php if ($preview['unknown']): ?>
  <div class="alert alert-warning small">ไม่รู้จักคอลัมน์ต่อไปนี้ จะไม่นำเข้า: <?= e(implode(', ', $preview['unknown'])) ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="table-responsive" style="max-height:60vh">
    <table class="table table-sm table-hover mb-0 small">
      <thead class="table-light sticky-top"><tr><th>แถว</th><th>สถานะ</th><th>ชื่อ-สกุล</th><th>ตำแหน่ง</th><th>ประเภท</th><th>ฝ่าย/แผนก</th><th>เบอร์โทร</th><th>อีเมล</th><th>หมายเหตุ</th></tr></thead>
      <tbody>
        <?php foreach ($preview['rows'] as $r): $d = $r['data']; ?>
          <tr class="<?= ['new' => '', 'exists' => 'table-info', 'error' => 'table-danger'][$r['status']] ?>">
            <td><?= $r['line'] ?></td>
            <td class="text-nowrap"><?= ['new' => '<span class="badge text-bg-success">ใหม่</span>', 'exists' => '<span class="badge text-bg-info">มีอยู่แล้ว</span>', 'error' => '<span class="badge text-bg-danger">ข้าม</span>'][$r['status']] ?></td>
            <td class="text-nowrap"><?= e(trim($d['prefix'] . $d['firstname'] . ' ' . $d['lastname'])) ?></td>
            <td><?= e($d['position']) ?><?= $d['academic_rank'] ? '<div class="text-muted">' . e($d['academic_rank']) . '</div>' : '' ?></td>
            <td class="text-nowrap"><?= $types[$d['type']] ?></td>
            <td><?= e($deptNames[$d['department_id']] ?? '-') ?></td>
            <td class="text-nowrap"><?= e($d['phone']) ?></td>
            <td><?= e($d['email']) ?></td>
            <td class="text-danger"><?= e(implode(' · ', $r['notes'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<form method="post" class="card"><div class="card-body d-flex flex-wrap gap-3 align-items-center">
  <?= csrf_field() ?>
  <?php if ($counts['exists']): ?>
    <div class="me-auto">
      <div class="small fw-medium mb-1">บุคลากรที่มีชื่ออยู่แล้ว <?= $counts['exists'] ?> คน:</div>
      <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="mode" id="m1" value="skip" checked><label class="form-check-label" for="m1">ข้าม (ไม่แก้ไขข้อมูลเดิม)</label></div>
      <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="mode" id="m2" value="update"><label class="form-check-label" for="m2">อัปเดตด้วยข้อมูลจากไฟล์ (เฉพาะช่องที่กรอก)</label></div>
    </div>
  <?php else: ?>
    <div class="me-auto small text-muted">ตรวจสอบตารางด้านบนให้เรียบร้อยก่อนกดนำเข้า</div>
  <?php endif; ?>
  <button class="btn btn-light" name="cancel" value="1" formnovalidate>ยกเลิก / เลือกไฟล์ใหม่</button>
  <button class="btn btn-primary" name="confirm" value="1" <?= $counts['new'] + $counts['exists'] ? '' : 'disabled' ?>><i class="bi bi-check2-circle"></i> ยืนยันนำเข้า</button>
</div></form>
<?php endif; ?>
<?php admin_footer(); ?>
