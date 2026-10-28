<?php
require_once __DIR__ . '/includes/front.php';

$activeMenu = 'videos';
$page = current_page();
$perPage = 12;
$total = (int) q('SELECT COUNT(*) FROM videos WHERE is_active = 1')->fetchColumn();
$videos = q("SELECT * FROM videos WHERE is_active = 1 ORDER BY sort_order, created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage))->fetchAll();

$pageTitle = t('วิดีทัศน์');
require __DIR__ . '/includes/header.php';
echo page_banner($pageTitle, [t('ข่าวสาร') => url('news.php'), $pageTitle => null]);
?>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <?php foreach ($videos as $v): ?>
        <div class="col-sm-6 col-lg-4"><?= video_card($v) ?></div>
      <?php endforeach; ?>
      <?php if (!$videos): ?><p class="text-center text-muted py-5"><?= t('ยังไม่มีวิดีทัศน์') ?></p><?php endif; ?>
    </div>
    <div class="mt-4"><?= pagination($total, $perPage, $page) ?></div>
  </div>
</section>
<?= video_modal() ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
