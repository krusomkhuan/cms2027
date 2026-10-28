<?php
require_once __DIR__ . '/includes/front.php';

$types = ['contact' => t('ติดต่อสอบถาม'), 'complaint' => t('แจ้งเรื่องร้องเรียนการทุจริต / ประพฤติมิชอบ')];
$type = ($_GET['type'] ?? $_POST['type'] ?? '') === 'complaint' ? 'complaint' : 'contact';
$form = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
$errors = [];
$sent = !empty($_SESSION['msg_sent']);
unset($_SESSION['msg_sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($form as $k => $_) {
        $form[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    // honeypot กันบอท + จำกัด 3 ข้อความ / 10 นาที ต่อ session
    $recent = array_filter($_SESSION['msg_times'] ?? [], fn($ts) => $ts > time() - 600);
    if (($_POST['website'] ?? '') !== '') {
        $errors[] = t('ส่งข้อความไม่สำเร็จ');
    } elseif (count($recent) >= 3) {
        $errors[] = t('ส่งข้อความบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่');
    }
    if ($form['name'] === '' || mb_strlen($form['name']) > 150) {
        $errors[] = t('กรุณากรอกชื่อ-สกุล');
    }
    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('รูปแบบอีเมลไม่ถูกต้อง');
    }
    if ($form['email'] === '' && $form['phone'] === '') {
        $errors[] = t('กรุณากรอกอีเมลหรือเบอร์โทรอย่างน้อย 1 ช่อง เพื่อให้ติดต่อกลับได้');
    }
    if ($form['subject'] === '' || mb_strlen($form['subject']) > 255) {
        $errors[] = t('กรุณากรอกเรื่อง');
    }
    if (mb_strlen($form['message']) < 10 || mb_strlen($form['message']) > 5000) {
        $errors[] = t('กรุณากรอกรายละเอียด 10–5,000 ตัวอักษร');
    }
    if (empty($_POST['consent'])) {
        $errors[] = t('กรุณายินยอมให้วิทยาลัยใช้ข้อมูลเพื่อติดต่อกลับ');
    }
    if (!$errors) {
        q('INSERT INTO messages (type, name, email, phone, subject, message, ip) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$type, $form['name'], $form['email'] ?: null, mb_substr($form['phone'], 0, 50) ?: null, $form['subject'], $form['message'], $_SERVER['REMOTE_ADDR'] ?? null]);
        $recent[] = time();
        $_SESSION['msg_times'] = array_values($recent);
        $_SESSION['msg_sent'] = 1;
        redirect('contact.php' . ($type === 'complaint' ? '?type=complaint' : '') . '#form');
    }
}

$page = q("SELECT * FROM pages WHERE slug = 'contact'")->fetch();
$pageContent = $page ? tr($page, 'content') : '';
$mapSrc = college_map()['embed'];

$activeMenu = 'contact';
$pageTitle = $type === 'complaint' ? t('แจ้งเรื่องร้องเรียน') : t('ติดต่อเรา');
require __DIR__ . '/includes/header.php';
echo page_banner($pageTitle, [$pageTitle => null]);
?>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-4">
          <h2 class="h5 section-title mb-3"><?= t('ข้อมูลการติดต่อ') ?></h2>
          <?php if (trim(strip_tags($pageContent)) !== ''): ?><div class="article mb-3"><?= $pageContent ?></div><?php endif; ?>
          <div class="d-flex gap-3 mb-3"><i class="bi bi-building fs-4 text-danger"></i><div><div class="fw-medium"><?= e($siteName) ?></div><div class="small text-muted"><?= t('สำนักงานคณะกรรมการการอาชีวศึกษา') ?></div></div></div>
          <div class="d-flex gap-3 mb-3"><i class="bi bi-geo-alt fs-4 text-danger"></i><div><div class="fw-medium"><?= t('ที่อยู่') ?></div><?= e(setting_l('address')) ?></div></div>
          <div class="d-flex gap-3 mb-3"><i class="bi bi-telephone fs-4 text-danger"></i><div><div class="fw-medium"><?= t('โทรศัพท์') ?></div><?= e(setting('phone', '-')) ?></div></div>
          <div class="d-flex gap-3 mb-3"><i class="bi bi-envelope fs-4 text-danger"></i><div><div class="fw-medium"><?= t('อีเมล') ?></div><?= e(setting('email', '-')) ?></div></div>
          <?php if (setting('facebook')): ?><div class="d-flex gap-3 mb-3"><i class="bi bi-facebook fs-4 text-danger"></i><div><div class="fw-medium">Facebook</div><a href="<?= e(setting('facebook')) ?>" target="_blank" rel="noopener" class="text-break"><?= e(preg_replace('#^https?://(www\.)?#', '', setting('facebook'))) ?></a></div></div><?php endif; ?>
          <p class="small text-muted mb-0"><?= t('เวลาทำการ: วันจันทร์–ศุกร์ 08:30–16:30 น. (เว้นวันหยุดราชการ)') ?></p>
        </div></div>
      </div>
      <div class="col-lg-7" id="form">
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
          <ul class="nav nav-pills news-tabs mb-3">
            <?php foreach ($types as $k => $label): ?>
              <li class="nav-item"><a class="nav-link <?= $type === $k ? 'active' : '' ?>" href="contact.php<?= $k === 'complaint' ? '?type=complaint' : '' ?>#form"><?= e($label) ?></a></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($type === 'complaint'): ?>
            <div class="alert alert-warning small"><?= t('ช่องทางรับเรื่องร้องเรียนการทุจริตและประพฤติมิชอบ ข้อมูลผู้ร้องเรียนจะถูกเก็บเป็นความลับ และเปิดดูได้เฉพาะผู้ดูแลระบบเท่านั้น') ?></div>
          <?php endif; ?>
          <?php if ($sent): ?><div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= t('ส่งข้อความเรียบร้อยแล้ว ขอบคุณครับ/ค่ะ เจ้าหน้าที่จะติดต่อกลับโดยเร็ว') ?></div><?php endif; ?>
          <?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
          <form method="post" action="contact.php<?= $type === 'complaint' ? '?type=complaint' : '' ?>#form">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="<?= $type ?>">
            <div class="d-none" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label"><?= t('ชื่อ-สกุล') ?> *</label><input class="form-control" name="name" value="<?= e($form['name']) ?>" required maxlength="150"></div>
              <div class="col-md-6"><label class="form-label"><?= t('เบอร์โทร') ?></label><input class="form-control" name="phone" value="<?= e($form['phone']) ?>" maxlength="50"></div>
              <div class="col-12"><label class="form-label"><?= t('อีเมล') ?></label><input type="email" class="form-control" name="email" value="<?= e($form['email']) ?>" maxlength="150"></div>
              <div class="col-12"><label class="form-label"><?= t('เรื่อง') ?> *</label><input class="form-control" name="subject" value="<?= e($form['subject']) ?>" required maxlength="255"></div>
              <div class="col-12"><label class="form-label"><?= t('รายละเอียด') ?> *</label><textarea class="form-control" name="message" rows="5" required minlength="10" maxlength="5000"><?= e($form['message']) ?></textarea></div>
              <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="consent" id="consent" required><label class="form-check-label small" for="consent"><?= t('ข้าพเจ้ายินยอมให้วิทยาลัยเก็บข้อมูลข้างต้นเพื่อใช้ติดต่อกลับเท่านั้น') ?> (<a href="<?= url('page.php?slug=privacy') ?>" target="_blank"><?= t('นโยบายคุ้มครองข้อมูลส่วนบุคคล') ?></a>)</label></div></div>
              <div class="col-12"><button class="btn btn-primary px-4"><i class="bi bi-send"></i> <?= t('ส่งข้อความ') ?></button></div>
            </div>
          </form>
        </div></div>
      </div>
    </div>
    <?php if ($mapSrc): ?>
      <div id="map" class="ratio ratio-21x9 mt-4 rounded overflow-hidden shadow-sm"><?= media_embed($mapSrc, 'Google Maps', t('แผนที่')) ?></div>
    <?php else: ?>
      <div id="map"></div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
