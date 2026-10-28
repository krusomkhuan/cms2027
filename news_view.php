<?php
require_once __DIR__ . '/includes/front.php';

$id = (int) ($_GET['id'] ?? 0);
$n = q("SELECT n.*, c.name AS cat_name, c.name_en AS cat_name_en, c.color AS cat_color, u.fullname AS author
    FROM news n
    LEFT JOIN categories c ON c.id = n.category_id
    LEFT JOIN users u ON u.id = n.author_id
    WHERE n.id = ? AND n.status = 'published' AND n.published_at <= NOW()", [$id])->fetch();

if (!$n) {
    http_response_code(404);
    $pageTitle = t('ไม่พบข่าว');
    require __DIR__ . '/includes/header.php';
    echo page_banner(t('ไม่พบข่าวที่ต้องการ'), [t('ข่าวสาร') => url('news.php'), t('ไม่พบข่าว') => null]);
    echo '<div class="container section text-center"><a class="btn btn-primary" href="' . url('news.php') . '">' . t('กลับไปหน้าข่าวสาร') . '</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// นับยอดอ่าน 1 ครั้งต่อ session ต่อข่าว
if (empty($_SESSION['read'][$id])) {
    $_SESSION['read'][$id] = 1;
    q('UPDATE news SET views = views + 1 WHERE id = ?', [$id]);
    $n['views']++;
}

$images = q('SELECT path FROM news_images WHERE news_id = ? ORDER BY sort_order, id', [$id])->fetchAll(PDO::FETCH_COLUMN);
$related = q(NEWS_SELECT . " WHERE n.status = 'published' AND n.published_at <= NOW() AND n.id <> ? AND n.category_id <=> ?
    ORDER BY n.published_at DESC LIMIT 4", [$id, $n['category_id']])->fetchAll();

$activeMenu = 'news';
$pageTitle = tr($n, 'title');
$catLabel = $n['cat_name'] ? tr($n, 'cat_name') : t('ข่าวสาร');
$thaiOnly = lang() === 'en' && trim(strip_tags((string) $n['content_en'])) === '';
require __DIR__ . '/includes/header.php';
echo page_banner($catLabel, [t('ข่าวสาร') => url('news.php'), $catLabel => $n['category_id'] ? url('news.php?cat=' . $n['category_id']) : null]);
?>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <article class="col-lg-8">
        <h1 class="h3 fw-semibold lh-base"><?= e(tr($n, 'title')) ?></h1>
        <div class="news-meta d-flex flex-wrap gap-3 mb-3 pb-3 border-bottom">
          <span><i class="bi bi-calendar3"></i> <?= thai_date($n['published_at'], true, true) ?></span>
          <?php if ($n['author']): ?><span><i class="bi bi-person"></i> <?= t('ผู้ประกาศ') ?>: <?= e($n['author']) ?></span><?php endif; ?>
          <span><i class="bi bi-eye"></i> <?= t('อ่าน') ?> <?= number_format($n['views']) ?> <?= t('ครั้ง') ?></span>
        </div>
        <?php if ($n['cover']): ?>
          <img src="<?= e(file_url($n['cover'])) ?>" alt="" class="img-fluid rounded mb-4 w-100">
        <?php endif; ?>
        <?php if ($thaiOnly): ?><div class="alert alert-light border small"><i class="bi bi-translate"></i> This article is available in Thai only.</div><?php endif; ?>
        <div class="article" lang="<?= $thaiOnly ? 'th' : lang() ?>"><?= $thaiOnly ? $n['content'] : tr($n, 'content') /* เนื้อหา HTML จากผู้ดูแลระบบ */ ?></div>

        <?php if ($n['attachment']): ?>
          <a href="<?= e(file_url($n['attachment'])) ?>" target="_blank" class="btn btn-outline-primary mt-3">
            <i class="bi bi-paperclip"></i> <?= t('ไฟล์แนบ') ?> (<?= e(strtoupper(pathinfo($n['attachment'], PATHINFO_EXTENSION))) ?>)
          </a>
        <?php endif; ?>

        <?php if ($images): ?>
          <h2 class="h5 mt-5 mb-3 section-title"><?= t('ภาพกิจกรรม') ?></h2>
          <div class="row g-2 gallery">
            <?php foreach ($images as $i => $img): ?>
              <div class="col-6 col-md-4">
                <a href="#" data-bs-toggle="modal" data-bs-target="#galleryModal" data-index="<?= $i ?>"><img src="<?= e(file_url($img)) ?>" alt="" loading="lazy"></a>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="modal fade" id="galleryModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
              <div class="modal-content bg-dark border-0">
                <div class="modal-body p-0">
                  <div id="galleryCarousel" class="carousel slide">
                    <div class="carousel-inner">
                      <?php foreach ($images as $i => $img): ?>
                        <div class="carousel-item <?= $i ? '' : 'active' ?>"><img src="<?= e(file_url($img)) ?>" class="d-block w-100" style="max-height:85vh;object-fit:contain" alt=""></div>
                      <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                  </div>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
              </div>
            </div>
          </div>
          <script>
            document.getElementById('galleryModal').addEventListener('show.bs.modal', (e) => {
              bootstrap.Carousel.getOrCreateInstance('#galleryCarousel').to(+e.relatedTarget.dataset.index);
            });
          </script>
        <?php endif; ?>

        <div class="mt-4 pt-3 border-top d-flex gap-2 flex-wrap">
          <span class="text-muted"><?= t('แชร์') ?>:</span>
          <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"><i class="bi bi-facebook"></i> Facebook</a>
          <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://social-plugins.line.me/lineit/share?url=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"><i class="bi bi-line"></i> LINE</a>
        </div>
      </article>

      <aside class="col-lg-4">
        <h2 class="h5 section-title mb-3"><?= t('ข่าวที่เกี่ยวข้อง') ?></h2>
        <div class="d-grid gap-3">
          <?php foreach ($related as $r): ?><?= news_card($r) ?><?php endforeach; ?>
          <?php if (!$related): ?><p class="text-muted">-</p><?php endif; ?>
        </div>
      </aside>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
