<?php
require_once __DIR__ . '/includes/front.php';

$activeMenu = 'news';
$catId = (int) ($_GET['cat'] ?? 0);
$kw = trim($_GET['q'] ?? '');
$page = current_page();
$perPage = 12;

$where = "n.status = 'published' AND n.published_at <= NOW()";
$params = [];
if ($catId) {
    $where .= ' AND n.category_id = ?';
    $params[] = $catId;
}
if ($kw !== '') {
    $where .= ' AND (n.title LIKE ? OR n.summary LIKE ? OR n.title_en LIKE ?)';
    array_push($params, "%$kw%", "%$kw%", "%$kw%");
}

$total = (int) q("SELECT COUNT(*) FROM news n WHERE $where", $params)->fetchColumn();
$rows = q(NEWS_SELECT . " WHERE $where ORDER BY n.is_pinned DESC, n.published_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params)->fetchAll();
$cats = q('SELECT id, name, name_en FROM categories ORDER BY sort_order, id')->fetchAll();
$catName = '';
foreach ($cats as $c) {
    if ((int) $c['id'] === $catId) {
        $catName = tr($c, 'name');
    }
}

$pageTitle = $catName ?: t('ข่าวสารทั้งหมด');
require __DIR__ . '/includes/header.php';
echo page_banner($pageTitle, $catName ? [t('ข่าวสาร') => url('news.php'), $catName => null] : [t('ข่าวสาร') => null]);
?>
<section class="section">
  <div class="container">
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
      <div class="filter-pills d-flex flex-wrap gap-2">
        <a href="<?= url('news.php') ?>" class="btn btn-sm <?= $catId ? 'btn-outline-primary' : 'btn-primary' ?>"><?= t('ทั้งหมด') ?></a>
        <?php foreach ($cats as $c): ?>
          <a href="<?= url('news.php?cat=' . $c['id']) ?>" class="btn btn-sm <?= $catId === (int) $c['id'] ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e(tr($c, 'name')) ?></a>
        <?php endforeach; ?>
      </div>
      <form class="d-flex gap-2" method="get">
        <?php if ($catId): ?><input type="hidden" name="cat" value="<?= $catId ?>"><?php endif; ?>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="<?= t('ค้นหาข่าว...') ?>" value="<?= e($kw) ?>">
        <button class="btn btn-sm btn-primary" aria-label="<?= t('ค้นหา') ?>"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <?php if ($kw !== ''): ?><p class="text-muted"><?= t('ผลการค้นหา') ?> “<?= e($kw) ?>” <?= t('พบ') ?> <?= number_format($total) ?> <?= t('รายการ') ?></p><?php endif; ?>

    <div class="row g-4">
      <?php foreach ($rows as $n): ?>
        <div class="col-sm-6 col-lg-4 col-xl-3"><?= news_card($n) ?></div>
      <?php endforeach; ?>
      <?php if (!$rows): ?><p class="text-muted text-center py-5"><?= t('ไม่พบข่าว') ?></p><?php endif; ?>
    </div>
    <div class="mt-4"><?= pagination($total, $perPage, $page, array_filter(['cat' => $catId ?: null, 'q' => $kw ?: null])) ?></div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
