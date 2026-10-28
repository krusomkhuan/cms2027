<?php
require_once __DIR__ . '/includes/front.php';

$activeMenu = 'downloads';
$kw = trim($_GET['q'] ?? '');
$page = current_page();
$perPage = 20;
$where = '1';
$params = [];
if ($kw !== '') {
    $where = '(title LIKE ? OR title_en LIKE ?)';
    array_push($params, "%$kw%", "%$kw%");
}
$total = (int) q("SELECT COUNT(*) FROM downloads WHERE $where", $params)->fetchColumn();
$rows = q("SELECT * FROM downloads WHERE $where ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params)->fetchAll();

$pageTitle = t('เอกสารดาวน์โหลด');
require __DIR__ . '/includes/header.php';
echo page_banner($pageTitle, [t('ดาวน์โหลด') => null]);
?>
<section class="section">
  <div class="container" style="max-width:960px">
    <form class="d-flex gap-2 mb-4" method="get">
      <input type="search" name="q" class="form-control" placeholder="<?= t('ค้นหาเอกสาร...') ?>" value="<?= e($kw) ?>">
      <button class="btn btn-primary text-nowrap"><i class="bi bi-search"></i> <?= t('ค้นหา') ?></button>
    </form>
    <div class="list-group shadow-sm">
      <?php foreach ($rows as $d): $ext = $d['file'] ? strtoupper(pathinfo($d['file'], PATHINFO_EXTENSION)) : 'LINK'; ?>
        <a href="<?= url('download.php?id=' . $d['id']) ?>" target="_blank" class="list-group-item list-group-item-action d-flex gap-3 align-items-center py-3">
          <i class="bi <?= $ext === 'PDF' ? 'bi-file-earmark-pdf text-danger' : ($ext === 'LINK' ? 'bi-link-45deg text-primary' : 'bi-file-earmark-text text-secondary') ?> fs-3"></i>
          <div class="flex-grow-1">
            <div><?= e(tr($d, 'title')) ?></div>
            <div class="news-meta"><?= thai_date($d['created_at']) ?> · <?= e($ext) ?> · <?= t('ดาวน์โหลด') ?> <?= number_format($d['hits']) ?> <?= t('ครั้ง') ?></div>
          </div>
          <i class="bi bi-download"></i>
        </a>
      <?php endforeach; ?>
      <?php if (!$rows): ?><div class="list-group-item text-muted text-center py-4"><?= t('ไม่พบเอกสาร') ?></div><?php endif; ?>
    </div>
    <div class="mt-4"><?= pagination($total, $perPage, $page, array_filter(['q' => $kw ?: null])) ?></div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
