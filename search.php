<?php
require_once __DIR__ . '/includes/front.php';

$kw = trim(mb_substr($_GET['q'] ?? '', 0, 100));
$like = '%' . addcslashes($kw, '%_\\') . '%';
$results = ['news' => [], 'pages' => [], 'personnel' => [], 'downloads' => [], 'events' => []];

if (mb_strlen($kw) >= 2) {
    $results['news'] = q(NEWS_SELECT . " WHERE n.status = 'published' AND n.published_at <= NOW()
        AND (n.title LIKE ? OR n.summary LIKE ? OR n.content LIKE ? OR n.title_en LIKE ? OR n.content_en LIKE ?)
        ORDER BY n.published_at DESC LIMIT 12", [$like, $like, $like, $like, $like])->fetchAll();
    $results['pages'] = q('SELECT * FROM pages WHERE title LIKE ? OR content LIKE ? OR title_en LIKE ? OR content_en LIKE ? LIMIT 10', [$like, $like, $like, $like])->fetchAll();
    $results['personnel'] = q("SELECT p.*, d.name AS dept_name, d.name_en AS dept_name_en FROM personnel p LEFT JOIN departments d ON d.id = p.department_id
        WHERE p.is_active = 1 AND (CONCAT(p.firstname, ' ', p.lastname) LIKE ? OR p.name_en LIKE ? OR p.position LIKE ? OR p.position_en LIKE ? OR d.name LIKE ? OR d.name_en LIKE ?)
        LIMIT 12", [$like, $like, $like, $like, $like, $like])->fetchAll();
    $results['downloads'] = q('SELECT * FROM downloads WHERE title LIKE ? OR title_en LIKE ? ORDER BY created_at DESC LIMIT 10', [$like, $like])->fetchAll();
    $results['events'] = q('SELECT * FROM events WHERE title LIKE ? OR title_en LIKE ? OR location LIKE ? ORDER BY start_date DESC LIMIT 10', [$like, $like, $like])->fetchAll();
}
$count = array_sum(array_map('count', $results));

$pageTitle = t('ค้นหา') . ($kw !== '' ? ': ' . $kw : '');
require __DIR__ . '/includes/header.php';
echo page_banner(t('ผลการค้นหา'), [t('ค้นหา') => null]);
?>
<section class="section">
  <div class="container">
    <form class="d-flex gap-2 mb-4" method="get" style="max-width:640px">
      <input type="search" name="q" class="form-control form-control-lg" value="<?= e($kw) ?>" placeholder="<?= t('พิมพ์คำค้นหา...') ?>" required minlength="2">
      <button class="btn btn-primary px-4" aria-label="<?= t('ค้นหา') ?>"><i class="bi bi-search"></i></button>
    </form>

    <?php if (mb_strlen($kw) < 2): ?>
      <p class="text-muted"><?= t('กรุณาพิมพ์คำค้นหาอย่างน้อย 2 ตัวอักษร') ?></p>
    <?php elseif (!$count): ?>
      <p class="text-muted"><?= t('ไม่พบผลลัพธ์สำหรับ') ?> “<?= e($kw) ?>”</p>
    <?php else: ?>
      <p class="text-muted"><?= t('พบ') ?> <?= number_format($count) ?> <?= t('รายการสำหรับ') ?> “<?= e($kw) ?>”</p>

      <?php if ($results['news']): ?>
        <h2 class="h5 section-title my-3"><?= t('ข่าวสาร') ?> (<?= count($results['news']) ?>)</h2>
        <div class="row g-3 mb-4"><?php foreach ($results['news'] as $n): ?><div class="col-sm-6 col-lg-3"><?= news_card($n) ?></div><?php endforeach; ?></div>
        <a href="<?= url('news.php?q=' . urlencode($kw)) ?>" class="small"><?= t('ค้นหาในข่าวทั้งหมด') ?> →</a>
      <?php endif; ?>

      <?php if ($results['pages']): ?>
        <h2 class="h5 section-title mt-5 mb-3"><?= t('ข้อมูลสถานศึกษา') ?></h2>
        <div class="list-group shadow-sm">
          <?php foreach ($results['pages'] as $p): ?>
            <a href="<?= url($p['slug'] === 'contact' ? 'contact.php' : 'page.php?slug=' . urlencode($p['slug'])) ?>" class="list-group-item list-group-item-action">
              <div class="fw-medium"><?= e(tr($p, 'title')) ?></div><div class="small text-muted"><?= e(excerpt(tr($p, 'content'), 160)) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($results['personnel']): ?>
        <h2 class="h5 section-title mt-5 mb-3"><?= t('บุคลากร') ?></h2>
        <div class="row g-3"><?php foreach ($results['personnel'] as $p): ?><div class="col-6 col-md-3 col-xl-2"><?= person_card($p) ?></div><?php endforeach; ?></div>
      <?php endif; ?>

      <?php if ($results['downloads']): ?>
        <h2 class="h5 section-title mt-5 mb-3"><?= t('เอกสารดาวน์โหลด') ?></h2>
        <div class="list-group shadow-sm">
          <?php foreach ($results['downloads'] as $d): ?>
            <a href="<?= url('download.php?id=' . $d['id']) ?>" target="_blank" class="list-group-item list-group-item-action"><i class="bi bi-download me-2"></i><?= e(tr($d, 'title')) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($results['events']): ?>
        <h2 class="h5 section-title mt-5 mb-3"><?= t('กิจกรรม') ?></h2>
        <div class="list-group shadow-sm">
          <?php foreach ($results['events'] as $ev): ?>
            <a href="<?= url('events.php?m=' . substr($ev['start_date'], 0, 7) . '#ev' . $ev['id']) ?>" class="list-group-item list-group-item-action">
              <i class="bi bi-calendar-event me-2"></i><?= e(tr($ev, 'title')) ?> <span class="small text-muted">· <?= thai_date($ev['start_date']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
