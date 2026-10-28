<?php
// ติดตั้งฐานข้อมูลครั้งแรก — หลังติดตั้งเสร็จระบบจะสร้างไฟล์ install.lock เพื่อปิดหน้านี้
require_once __DIR__ . '/config.php';

$lock = __DIR__ . '/install.lock';
$error = '';
$done = false;

if (is_file($lock)) {
    exit('ระบบติดตั้งเรียบร้อยแล้ว (ลบไฟล์ install.lock หากต้องการติดตั้งใหม่)');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $sample = !empty($_POST['sample']);

    if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) {
        $error = 'ชื่อผู้ใช้ต้องเป็น a-z, 0-9, _ หรือ . ยาว 3-50 ตัวอักษร';
    } elseif (strlen($password) < 8) {
        $error = 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร';
    } else {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . DB_NAME . '`');
            $pdo->exec(file_get_contents(__DIR__ . '/sql/schema.sql'));

            $ins = fn(string $sql, array $rows) => array_map(fn($r) => $pdo->prepare($sql)->execute($r), $rows);

            $ins('INSERT IGNORE INTO settings (skey, svalue) VALUES (?, ?)', [
                ['site_name', 'วิทยาลัยการอาชีพโพนทอง'],
                ['site_name_en', 'Phonthong Industrial and Community Education College'],
                ['slogan', 'ทักษะเด่น เน้นคุณธรรม นำสู่อาชีพ'],
                ['address', 'อำเภอโพนทอง จังหวัดร้อยเอ็ด 45110'],
                ['phone', '043-000-000'],
                ['email', 'info@pticec.ac.th'],
                ['facebook', ''],
                ['director_name', 'ชื่อ-สกุล ผู้อำนวยการ'],
                ['director_position', 'ผู้อำนวยการวิทยาลัยการอาชีพโพนทอง'],
                ['director_photo', ''],
                ['logo', ''],
                ['theme_color', '#1e3a8a'],
                ['map_embed', ''],
                ['visitor_count', '0'],
                ['director_phone', ''],
                ['director_message', 'วิทยาลัยการอาชีพโพนทองมุ่งพัฒนาผู้เรียนให้มีทักษะวิชาชีพตรงกับความต้องการของตลาดแรงงานและชุมชน ควบคู่กับคุณธรรม จริยธรรม เพื่อให้ผู้สำเร็จการศึกษามีงานทำและเป็นกำลังสำคัญในการพัฒนาท้องถิ่นและประเทศ'],
                ['ticker_text', ''],
                ['admission_url', ''],
                ['line_url', ''],
                ['youtube_url', ''],
            ]);

            $pdo->prepare('INSERT INTO users (username, password_hash, fullname, role) VALUES (?, ?, ?, ?)')
                ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullname ?: 'ผู้ดูแลระบบ', 'admin']);

            $ins('INSERT INTO categories (name, color, sort_order) VALUES (?, ?, ?)', [
                ['ข่าวประชาสัมพันธ์', '#1e3a8a', 1],
                ['ข่าวประกาศ', '#0d6efd', 2],
                ['ข่าวรับสมัคร', '#198754', 3],
                ['ข่าวจัดซื้อจัดจ้าง', '#6f42c1', 4],
                ['ผลงานและรางวัล', '#d4a017', 5],
            ]);

            $ins('INSERT INTO departments (name, type, sort_order) VALUES (?, ?, ?)', [
                ['ฝ่ายบริหารทรัพยากร', 'division', 1],
                ['ฝ่ายยุทธศาสตร์และแผนงาน', 'division', 2],
                ['ฝ่ายพัฒนากิจการนักเรียน นักศึกษา', 'division', 3],
                ['ฝ่ายวิชาการ', 'division', 4],
                ['แผนกวิชาช่างยนต์', 'academic', 11],
                ['แผนกวิชาช่างไฟฟ้ากำลัง', 'academic', 12],
                ['แผนกวิชาช่างอิเล็กทรอนิกส์', 'academic', 13],
                ['แผนกวิชาการบัญชี', 'academic', 14],
                ['แผนกวิชาเทคโนโลยีธุรกิจดิจิทัล', 'academic', 15],
                ['แผนกวิชาสามัญสัมพันธ์', 'academic', 16],
            ]);

            $ph = '<p>(กรุณาแก้ไขเนื้อหาส่วนนี้ที่ระบบหลังบ้าน เมนู “ข้อมูลสถานศึกษา”)</p>';
            $ins('INSERT INTO pages (slug, title, content, sort_order, show_in_menu) VALUES (?, ?, ?, ?, ?)', [
                ['history', 'ประวัติสถานศึกษา', '<h4>ประวัติวิทยาลัยการอาชีพโพนทอง</h4>' . $ph, 1, 1],
                ['vision', 'วิสัยทัศน์ พันธกิจ เป้าประสงค์', '<h4>วิสัยทัศน์</h4>' . $ph . '<h4>พันธกิจ</h4>' . $ph . '<h4>เป้าประสงค์</h4>' . $ph, 2, 1],
                ['identity', 'ปรัชญา อัตลักษณ์ เอกลักษณ์', '<h4>ปรัชญา</h4>' . $ph . '<h4>อัตลักษณ์</h4>' . $ph . '<h4>เอกลักษณ์</h4>' . $ph, 3, 1],
                ['symbol', 'สัญลักษณ์ประจำสถานศึกษา', '<p>ตราสัญลักษณ์ สีประจำสถานศึกษา ต้นไม้ประจำสถานศึกษา</p>' . $ph, 4, 1],
                ['structure', 'โครงสร้างการบริหาร', '<p>แผนภูมิโครงสร้างการบริหารสถานศึกษา</p>' . $ph, 5, 1],
                ['programs', 'หลักสูตรที่เปิดสอน', '<h4>ระดับประกาศนียบัตรวิชาชีพ (ปวช.)</h4>' . $ph . '<h4>ระดับประกาศนียบัตรวิชาชีพชั้นสูง (ปวส.)</h4>' . $ph, 6, 1],
                ['contact', 'ติดต่อเรา', '<p>ติดต่อสอบถามข้อมูลได้ในวันและเวลาราชการ</p>', 99, 0],
            ]);

            $ins('INSERT INTO links (title, url, icon, link_group, sort_order) VALUES (?, ?, ?, ?, ?)', [
                ['ระบบสมัครเรียนออนไลน์', '#', 'bi-person-plus', 'eservice', 1],
                ['ระบบบริหารสถานศึกษา (RMS)', '#', 'bi-pc-display', 'eservice', 2],
                ['ระบบลงทะเบียนเรียน (ศธ.02)', 'https://std2018.vec.go.th/', 'bi-journal-check', 'eservice', 3],
                ['ตรวจผลการเรียน', '#', 'bi-mortarboard', 'eservice', 4],
                ['ระบบครูที่ปรึกษา', '#', 'bi-people', 'eservice', 5],
                ['ห้องสมุดออนไลน์', '#', 'bi-book', 'eservice', 6],
                ['ระบบสารบรรณอิเล็กทรอนิกส์', '#', 'bi-envelope-paper', 'eservice', 7],
                ['ITA Online', '#', 'bi-shield-check', 'eservice', 8],
                ['สำนักงานคณะกรรมการการอาชีวศึกษา', 'https://www.vec.go.th/', 'bi-building', 'external', 1],
                ['สถาบันการอาชีวศึกษาภาคตะวันออกเฉียงเหนือ 3', '#', 'bi-building', 'external', 2],
                ['ศูนย์ข้อมูลอาชีวศึกษา', 'https://www.vec.go.th/', 'bi-database', 'external', 3],
                ['กระทรวงศึกษาธิการ', 'https://www.moe.go.th/', 'bi-bank', 'external', 4],
            ]);

            // ข้อมูลจริงของวิทยาลัย (ตั้งค่า หน้าข้อมูล แผนกวิชา ผู้บริหาร)
            $pdo->exec(file_get_contents(__DIR__ . '/sql/college_data.sql'));

            if ($sample) {
                $news = [
                    [1, 'วิทยาลัยการอาชีพโพนทอง จัดกิจกรรมปฐมนิเทศนักเรียนใหม่ ประจำปีการศึกษา 2569', 1],
                    [1, 'คณะครูและนักเรียนร่วมกิจกรรมจิตอาสาพัฒนาชุมชน อำเภอโพนทอง', 0],
                    [2, 'ประกาศกำหนดการลงทะเบียนเรียน ภาคเรียนที่ 2 ปีการศึกษา 2569', 0],
                    [3, 'รับสมัครนักเรียน นักศึกษา ระดับ ปวช. และ ปวส. ประจำปีการศึกษา 2570', 1],
                    [4, 'ประกาศผู้ชนะการเสนอราคา จัดซื้อครุภัณฑ์การศึกษา', 0],
                    [5, 'นักเรียนแผนกวิชาช่างยนต์ คว้ารางวัลการแข่งขันทักษะวิชาชีพระดับจังหวัด', 0],
                ];
                $st = $pdo->prepare('INSERT INTO news (category_id, title, summary, content, is_pinned, author_id, published_at) VALUES (?, ?, ?, ?, ?, 1, ?)');
                foreach ($news as $i => [$cat, $title, $pin]) {
                    $st->execute([$cat, $title, '(ข่าวตัวอย่าง) ' . $title,
                        '<p>(ข่าวตัวอย่าง) ' . $title . '</p><p>สามารถแก้ไขหรือลบข่าวนี้ได้ที่ระบบหลังบ้าน เมนู “ข่าวสาร”</p>',
                        $pin, date('Y-m-d H:i:s', strtotime("-$i day"))]);
                }
                $ins('INSERT INTO events (title, start_date, end_date, location, color) VALUES (?, ?, ?, ?, ?)', [
                    ['(ตัวอย่าง) ประชุมผู้ปกครองนักเรียน', date('Y-m-d', strtotime('+3 day')), null, 'หอประชุมวิทยาลัย', '#1e3a8a'],
                    ['(ตัวอย่าง) สอบกลางภาค', date('Y-m-d', strtotime('+10 day')), date('Y-m-d', strtotime('+14 day')), 'ทุกแผนกวิชา', '#0d6efd'],
                    ['(ตัวอย่าง) กิจกรรมจิตอาสาพัฒนาชุมชน', date('Y-m-d', strtotime('+18 day')), null, 'อำเภอโพนทอง', '#198754'],
                ]);
                $ins('INSERT INTO downloads (title, url, is_featured) VALUES (?, ?, ?)', [
                    ['ปฏิทินการศึกษา ภาคเรียนที่ 2 ปีการศึกษา 2569 (ตัวอย่าง)', '#', 1],
                    ['ตารางเรียน ภาคเรียนที่ 2 ปีการศึกษา 2569 (ตัวอย่าง)', '#', 1],
                    ['แบบฟอร์มคำร้องทั่วไป (ตัวอย่าง)', '#', 1],
                ]);
            }

            file_put_contents($lock, date('c'));
            $done = true;
        } catch (Throwable $ex) {
            $error = $ex->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ติดตั้งระบบ CMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
  <style>body{font-family:Kanit,sans-serif;background:#f4f1ee}</style>
</head>
<body>
<div class="container py-5" style="max-width:560px">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 mb-3">ติดตั้งระบบ CMS วิทยาลัยการอาชีพโพนทอง</h1>
      <?php if ($done): ?>
        <div class="alert alert-success">ติดตั้งเรียบร้อยแล้ว</div>
        <a href="<?= BASE_URL ?>/" class="btn btn-outline-secondary">ไปหน้าเว็บไซต์</a>
        <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn-danger">เข้าสู่ระบบหลังบ้าน</a>
      <?php else: ?>
        <p class="text-muted small">ฐานข้อมูล: <code><?= htmlspecialchars(DB_NAME) ?></code> @ <?= htmlspecialchars(DB_HOST) ?> (แก้ไขได้ที่ config.php)</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
          <div class="mb-3"><label class="form-label">ชื่อผู้ใช้ผู้ดูแลระบบ</label>
            <input name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>"></div>
          <div class="mb-3"><label class="form-label">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label>
            <input name="password" type="password" class="form-control" required minlength="8"></div>
          <div class="mb-3"><label class="form-label">ชื่อ-สกุล</label>
            <input name="fullname" class="form-control" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"></div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="sample" id="sample" checked>
            <label class="form-check-label" for="sample">เพิ่มข้อมูลตัวอย่าง (ข่าว กิจกรรม เอกสาร)</label></div>
          <button class="btn btn-danger w-100">ติดตั้ง</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
