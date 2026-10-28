<?php
// แบบฟอร์มคำร้องใช้สิทธิ์ของเจ้าของข้อมูลส่วนบุคคล (PDPA มาตรา 30–36)
require_once __DIR__ . '/includes/front.php';

$form = ['request_type' => '', 'relation' => '', 'name' => '', 'email' => '', 'phone' => '', 'detail' => ''];
$errors = [];
$done = $_SESSION['pdpa_done'] ?? null;
unset($_SESSION['pdpa_done']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($form as $k => $_) {
        $form[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $recent = array_filter($_SESSION['pdpa_times'] ?? [], fn($ts) => $ts > time() - 3600);
    if (($_POST['website'] ?? '') !== '' || count($recent) >= 3) {
        $errors[] = t('ส่งคำร้องบ่อยเกินไป กรุณาลองใหม่ภายหลัง');
    }
    if (!isset(PDPA_REQUEST_TYPES[$form['request_type']])) {
        $errors[] = t('กรุณาเลือกประเภทคำร้อง');
    }
    if (!isset(PDPA_RELATIONS[$form['relation']])) {
        $errors[] = t('กรุณาเลือกสถานะของท่าน');
    }
    if ($form['name'] === '' || mb_strlen($form['name']) > 150) {
        $errors[] = t('กรุณากรอกชื่อ-สกุล');
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('กรุณากรอกอีเมลให้ถูกต้อง เพื่อใช้แจ้งผลการดำเนินการ');
    }
    if (mb_strlen($form['detail']) < 10 || mb_strlen($form['detail']) > 5000) {
        $errors[] = t('กรุณากรอกรายละเอียด 10–5,000 ตัวอักษร');
    }
    if (empty($_POST['confirm'])) {
        $errors[] = t('กรุณายืนยันว่าเป็นเจ้าของข้อมูลหรือผู้มีอำนาจกระทำการแทน');
    }
    if (!$errors) {
        do {
            $ref = 'DSR' . ((int) date('Y') + 543) . '-' . random_int(10000, 99999);
        } while (q('SELECT id FROM pdpa_requests WHERE ref_no = ?', [$ref])->fetch());
        $due = date('Y-m-d', strtotime('+30 days'));
        q('INSERT INTO pdpa_requests (ref_no, request_type, relation, name, email, phone, detail, ip, due_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $ref, $form['request_type'], $form['relation'], $form['name'], $form['email'],
            mb_substr($form['phone'], 0, 50) ?: null, $form['detail'], $_SERVER['REMOTE_ADDR'] ?? null, $due,
        ]);
        $recent[] = time();
        $_SESSION['pdpa_times'] = array_values($recent);
        $_SESSION['pdpa_done'] = ['ref' => $ref, 'due' => $due];
        redirect('pdpa_request.php#form');
    }
}

$pageTitle = t('คำร้องใช้สิทธิ์เจ้าของข้อมูลส่วนบุคคล');
require __DIR__ . '/includes/header.php';
echo page_banner($pageTitle, [t('นโยบายคุ้มครองข้อมูลส่วนบุคคล') => url('page.php?slug=privacy'), t('ใช้สิทธิ์เจ้าของข้อมูล') => null]);
?>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body p-4">
          <h2 class="h5 section-title mb-3"><?= t('สิทธิของท่านตาม PDPA') ?></h2>
          <ul class="small ps-3 mb-0">
            <?php foreach (PDPA_REQUEST_TYPES as $k => $pair): if ($k === 'other') continue; ?><li class="mb-1"><?= e(pdpa_label($pair)) ?></li><?php endforeach; ?>
            <li><?= t('ร้องเรียนต่อสำนักงานคณะกรรมการคุ้มครองข้อมูลส่วนบุคคล') ?> (<a href="https://www.pdpc.or.th" target="_blank" rel="noopener">pdpc.or.th</a>)</li>
          </ul>
        </div></div>
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
          <h2 class="h6 fw-semibold mb-3"><i class="bi bi-person-badge"></i> <?= t('เจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล (DPO)') ?></h2>
          <div class="small">
            <div class="mb-1"><?= e(setting('dpo_name')) ?></div>
            <?php if (setting('dpo_email')): ?><div class="mb-1"><i class="bi bi-envelope"></i> <a href="mailto:<?= e(setting('dpo_email')) ?>"><?= e(setting('dpo_email')) ?></a></div><?php endif; ?>
            <?php if (setting('dpo_phone')): ?><div><i class="bi bi-telephone"></i> <?= e(setting('dpo_phone')) ?></div><?php endif; ?>
          </div>
          <hr>
          <p class="small text-muted mb-0"><?= t('วิทยาลัยจะพิจารณาและแจ้งผลภายใน 30 วันนับจากวันที่ได้รับคำร้อง อาจขอเอกสารยืนยันตัวตนเพิ่มเติมก่อนดำเนินการ') ?></p>
        </div></div>
      </div>
      <div class="col-lg-8" id="form">
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
          <?php if ($done): ?>
            <div class="alert alert-success">
              <h3 class="h5"><i class="bi bi-check-circle"></i> <?= t('ได้รับคำร้องของท่านแล้ว') ?></h3>
              <div><?= t('เลขที่คำร้อง') ?>: <b class="fs-5"><?= e($done['ref']) ?></b></div>
              <div><?= t('กำหนดแจ้งผลภายใน') ?>: <b><?= thai_date($done['due'], false, true) ?></b></div>
              <div class="small mt-2"><?= t('กรุณาเก็บเลขที่คำร้องไว้เพื่อใช้ติดตามเรื่องกับเจ้าหน้าที่') ?></div>
            </div>
          <?php endif; ?>
          <?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
          <form method="post" action="pdpa_request.php#form">
            <?= csrf_field() ?>
            <div class="d-none" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
            <div class="row g-3">
              <div class="col-md-7"><label class="form-label" for="request_type"><?= t('ประเภทคำร้อง') ?> *</label>
                <select class="form-select" id="request_type" name="request_type" required>
                  <option value="">— <?= t('เลือก') ?> —</option>
                  <?php foreach (PDPA_REQUEST_TYPES as $k => $pair): ?><option value="<?= $k ?>" <?= $form['request_type'] === $k ? 'selected' : '' ?>><?= e(pdpa_label($pair)) ?></option><?php endforeach; ?>
                </select></div>
              <div class="col-md-5"><label class="form-label" for="relation"><?= t('ท่านเป็น') ?> *</label>
                <select class="form-select" id="relation" name="relation" required>
                  <option value="">— <?= t('เลือก') ?> —</option>
                  <?php foreach (PDPA_RELATIONS as $k => $pair): ?><option value="<?= $k ?>" <?= $form['relation'] === $k ? 'selected' : '' ?>><?= e(pdpa_label($pair)) ?></option><?php endforeach; ?>
                </select></div>
              <div class="col-12"><label class="form-label" for="name"><?= t('ชื่อ-สกุล') ?> *</label><input class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" required maxlength="150"></div>
              <div class="col-md-7"><label class="form-label" for="email"><?= t('อีเมล') ?> *</label><input type="email" class="form-control" id="email" name="email" value="<?= e($form['email']) ?>" required maxlength="150"></div>
              <div class="col-md-5"><label class="form-label" for="phone"><?= t('เบอร์โทร') ?></label><input class="form-control" id="phone" name="phone" value="<?= e($form['phone']) ?>" maxlength="50"></div>
              <div class="col-12"><label class="form-label" for="detail"><?= t('รายละเอียดคำร้อง') ?> *</label>
                <textarea class="form-control" id="detail" name="detail" rows="5" required minlength="10" maxlength="5000" placeholder="<?= t('เช่น ข้อมูลที่ต้องการให้ดำเนินการ หน้าเว็บที่เกี่ยวข้อง และเหตุผล') ?>"><?= e($form['detail']) ?></textarea></div>
              <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="confirm" id="confirm" required>
                <label class="form-check-label small" for="confirm"><?= t('ข้าพเจ้าเป็นเจ้าของข้อมูลส่วนบุคคล หรือผู้มีอำนาจกระทำการแทน และยินยอมให้วิทยาลัยใช้ข้อมูลในแบบฟอร์มนี้เพื่อพิจารณาคำร้องเท่านั้น') ?></label></div></div>
              <div class="col-12"><button class="btn btn-primary px-4"><i class="bi bi-send"></i> <?= t('ส่งคำร้อง') ?></button></div>
            </div>
          </form>
        </div></div>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
