<?php
$externalLinks = q("SELECT title, title_en, url FROM links WHERE link_group = 'external' ORDER BY sort_order, id")->fetchAll();
$visits = q("SELECT
    COALESCE(SUM(CASE WHEN visit_date = CURDATE() THEN hits END), 0) AS today,
    COALESCE(SUM(CASE WHEN YEARWEEK(visit_date, 1) = YEARWEEK(CURDATE(), 1) THEN hits END), 0) AS week,
    COALESCE(SUM(CASE WHEN visit_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN hits END), 0) AS month
    FROM visit_stats WHERE visit_date >= CURDATE() - INTERVAL 40 DAY")->fetch();
$lineUrl = preg_match('#^https?://#i', setting('line_url')) ? setting('line_url') : '';
$fbUrl = preg_match('#^https?://#i', setting('facebook')) ? setting('facebook') : '';
// ลิงก์แชท Messenger: ใช้ค่าที่ตั้งไว้ หรือสร้างจากชื่อเพจ Facebook (facebook.com/ชื่อเพจ → m.me/ชื่อเพจ)
$messengerUrl = preg_match('#^https?://#i', setting('messenger_url')) ? setting('messenger_url') : '';
if (!$messengerUrl && preg_match('#facebook\.com/([A-Za-z0-9.\-]+)/?$#i', $fbUrl, $fbm)) {
    $messengerUrl = 'https://m.me/' . $fbm[1];
}
$phone = setting('phone');
?>
</main>

<footer class="site-footer">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <?php if (setting('logo')): ?>
            <img src="<?= file_url(setting('logo')) ?>" alt="" class="brand-logo">
          <?php else: ?>
            <span class="brand-logo brand-logo-text">PT</span>
          <?php endif; ?>
          <div>
            <div class="fw-semibold fs-5"><?= e($siteName) ?></div>
            <div class="small opacity-75"><?= e($siteSub) ?></div>
          </div>
        </div>
        <p class="small opacity-75 mb-2"><?= t('สำนักงานคณะกรรมการการอาชีวศึกษา กระทรวงศึกษาธิการ') ?></p>
        <p class="mb-1"><i class="bi bi-geo-alt me-1"></i> <?= e(setting_l('address')) ?></p>
        <?php if (setting('phone')): ?><p class="mb-1"><i class="bi bi-telephone me-1"></i> <?= e(setting('phone')) ?></p><?php endif; ?>
        <?php if (setting('email')): ?><p class="mb-1"><i class="bi bi-envelope me-1"></i> <?= e(setting('email')) ?></p><?php endif; ?>
      </div>
      <div class="col-sm-6 col-lg-2">
        <h6 class="footer-title"><?= t('เมนูลัด') ?></h6>
        <ul class="list-unstyled footer-links">
          <li><a href="<?= url('news.php') ?>"><?= t('ข่าวสาร') ?></a></li>
          <li><a href="<?= url('events.php') ?>"><?= t('ปฏิทินกิจกรรม') ?></a></li>
          <li><a href="<?= url('personnel.php') ?>"><?= t('ครูและบุคลากร') ?></a></li>
          <li><a href="<?= url('students.php') ?>"><?= t('ข้อมูลผู้เรียน') ?></a></li>
          <li><a href="<?= url('downloads.php') ?>"><?= t('ดาวน์โหลด') ?></a></li>
          <li><a href="<?= url('contact.php') ?>"><?= t('ติดต่อเรา') ?></a></li>
          <li><a href="<?= url('contact.php?type=complaint') ?>"><?= t('แจ้งเรื่องร้องเรียน') ?></a></li>
          <?php if (setting('allow_register', '1') === '1'): ?><li><a href="<?= url('admin/register.php') ?>"><?= t('สมัครสมาชิก (ครู/บุคลากร)') ?></a></li><?php endif; ?>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h6 class="footer-title"><?= t('หน่วยงานที่เกี่ยวข้อง') ?></h6>
        <ul class="list-unstyled footer-links">
          <?php foreach ($externalLinks as $l): ?>
            <li><a href="<?= e($l['url']) ?>" target="_blank" rel="noopener"><?= e(tr($l, 'title')) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-lg-3">
        <h6 class="footer-title"><?= t('สถิติผู้เข้าชมเว็บไซต์') ?></h6>
        <ul class="list-unstyled visit-stats">
          <li><span><i class="bi bi-calendar-day"></i> <?= t('วันนี้') ?></span><b><?= number_format($visits['today']) ?></b></li>
          <li><span><i class="bi bi-calendar-week"></i> <?= t('สัปดาห์นี้') ?></span><b><?= number_format($visits['week']) ?></b></li>
          <li><span><i class="bi bi-calendar-month"></i> <?= t('เดือนนี้') ?></span><b><?= number_format($visits['month']) ?></b></li>
          <li><span><i class="bi bi-people"></i> <?= t('ทั้งหมด') ?></span><b><?= number_format((int) setting('visitor_count', '0')) ?></b></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container d-flex flex-wrap justify-content-between gap-2">
      <span>&copy; <?= lang() === 'en' ? date('Y') : date('Y') + 543 ?> <?= e($siteName) ?> <span class="opacity-50 small ms-1">v<?= APP_VERSION ?></span></span>
      <a href="<?= url('contact.php') ?>" class="text-white-50 text-decoration-none"><?= t('ติดต่อผู้ดูแลเว็บไซต์') ?></a>
    </div>
  </div>
</footer>

<?php if ($messengerUrl || $fbUrl || $lineUrl || $phone): ?>
<div class="contact-fab" id="contactFab">
  <div class="contact-panel" id="contactPanel" role="dialog" aria-label="<?= t('ติดต่อวิทยาลัย') ?>" hidden>
    <div class="contact-panel-head">
      <?php if (setting('logo')): ?><img src="<?= e(file_url(setting('logo'))) ?>" alt=""><?php endif; ?>
      <div><div class="fw-medium"><?= e($siteName) ?></div><div class="small opacity-75"><?= t('สอบถามข้อมูล ยินดีให้บริการ') ?></div></div>
    </div>
    <div class="contact-panel-body">
      <?php if ($messengerUrl): ?>
        <a href="<?= e($messengerUrl) ?>" target="_blank" rel="noopener" class="cp-item"><span class="cp-ic messenger"><i class="bi bi-messenger"></i></span><span><b><?= t('แชทผ่าน Messenger') ?></b><small><?= t('ตอบกลับในเวลาราชการ') ?></small></span></a>
      <?php endif; ?>
      <?php if ($fbUrl): ?>
        <a href="<?= e($fbUrl) ?>" target="_blank" rel="noopener" class="cp-item"><span class="cp-ic facebook"><i class="bi bi-facebook"></i></span><span><b><?= t('เพจ Facebook') ?></b><small><?= e(preg_replace('#^https?://(www\.)?#', '', $fbUrl)) ?></small></span></a>
      <?php endif; ?>
      <?php if ($lineUrl): ?>
        <a href="<?= e($lineUrl) ?>" target="_blank" rel="noopener" class="cp-item"><span class="cp-ic line"><i class="bi bi-line"></i></span><span><b>LINE</b><small><?= t('เพิ่มเพื่อนเพื่อรับข่าวสาร') ?></small></span></a>
      <?php endif; ?>
      <?php if ($phone): ?>
        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>" class="cp-item"><span class="cp-ic phone"><i class="bi bi-telephone-fill"></i></span><span><b><?= t('โทรหาวิทยาลัย') ?></b><small><?= e($phone) ?></small></span></a>
      <?php endif; ?>
      <a href="<?= url('contact.php#form') ?>" class="cp-item"><span class="cp-ic form"><i class="bi bi-envelope-fill"></i></span><span><b><?= t('ส่งข้อความถึงวิทยาลัย') ?></b><small><?= t('ผ่านแบบฟอร์มบนเว็บไซต์') ?></small></span></a>
    </div>
  </div>
  <button type="button" class="contact-fab-btn" id="contactFabBtn" aria-expanded="false" aria-controls="contactPanel" aria-label="<?= t('ติดต่อวิทยาลัย') ?>">
    <i class="bi bi-messenger ic-open"></i><i class="bi bi-x-lg ic-close"></i>
  </button>
</div>
<?php endif; ?>
<a href="#" class="back-top" aria-label="<?= t('กลับด้านบน') ?>"><i class="bi bi-arrow-up"></i></a>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
