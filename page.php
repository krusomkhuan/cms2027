<?php
require_once __DIR__ . '/includes/front.php';

$slug = (string) ($_GET['slug'] ?? '');
if ($slug === 'contact') {
    redirect('contact.php');
}
$p = q('SELECT * FROM pages WHERE slug = ?', [$slug])->fetch();
if (!$p) {
    http_response_code(404);
    $p = ['title' => t('ไม่พบหน้าที่ต้องการ'), 'content' => '<p>' . t('ไม่พบหน้าที่ต้องการ') . '</p>', 'slug' => '', 'updated_at' => null];
}
$title = tr($p, 'title');
$thaiOnly = lang() === 'en' && trim(strip_tags((string) ($p['content_en'] ?? ''))) === '';

$activeMenu = 'about';
$pageTitle = $title;
$sidePages = q('SELECT slug, title, title_en FROM pages WHERE show_in_menu = 1 ORDER BY sort_order, id')->fetchAll();

require __DIR__ . '/includes/header.php';
echo page_banner($title, [t('ข้อมูลสถานศึกษา') => null, $title => null]);
?>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
          <?php if ($thaiOnly && $p['slug'] !== ''): ?><div class="alert alert-light border small"><i class="bi bi-translate"></i> This page is available in Thai only.</div><?php endif; ?>
          <div class="article" lang="<?= $thaiOnly ? 'th' : lang() ?>"><?= $thaiOnly ? $p['content'] : tr($p, 'content') ?></div>
          <?php if (!empty($p['updated_at'])): ?><p class="text-muted small mt-4 mb-0"><?= t('ปรับปรุงล่าสุด') ?> <?= thai_date($p['updated_at'], true) ?></p><?php endif; ?>
        </div></div>
      </div>
      <aside class="col-lg-4">
        <div class="list-group shadow-sm">
          <div class="list-group-item text-white" style="background:var(--primary)"><?= t('ข้อมูลสถานศึกษา') ?></div>
          <?php foreach ($sidePages as $sp): ?>
            <a href="<?= url('page.php?slug=' . urlencode($sp['slug'])) ?>" class="list-group-item list-group-item-action <?= $sp['slug'] === $slug ? 'fw-semibold text-danger' : '' ?>"><?= e(tr($sp, 'title')) ?></a>
          <?php endforeach; ?>
          <a href="<?= url('personnel.php') ?>" class="list-group-item list-group-item-action"><?= t('ข้อมูลครูและบุคลากร') ?></a>
          <a href="<?= url('students.php') ?>" class="list-group-item list-group-item-action"><?= t('ข้อมูลผู้เรียน') ?></a>
        </div>
      </aside>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
