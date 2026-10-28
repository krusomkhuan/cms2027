<?php
require_once __DIR__ . '/includes/front.php';

$activeMenu = 'home';

$slides = q('SELECT * FROM slides WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
$featuredDownloads = q('SELECT * FROM downloads WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 4')->fetchAll();

// แท็บข่าว: รวมข่าว + แยกตามหมวด
$published = "n.status = 'published' AND n.published_at <= NOW()";
$newsTabs = [['id' => 'all', 'name' => t('รวมข่าว'), 'more' => t('ดูข่าวทั้งหมด'), 'href' => url('news.php'),
    'items' => q(NEWS_SELECT . " WHERE $published ORDER BY n.is_pinned DESC, n.published_at DESC LIMIT 6")->fetchAll()]];
foreach (q('SELECT id, name, name_en FROM categories ORDER BY sort_order, id') as $c) {
    $items = q(NEWS_SELECT . " WHERE $published AND n.category_id = ? ORDER BY n.is_pinned DESC, n.published_at DESC LIMIT 6", [$c['id']])->fetchAll();
    if ($items) {
        $newsTabs[] = ['id' => 'c' . $c['id'], 'name' => tr($c, 'name'), 'more' => t('ดูทั้งหมด') . ' ' . tr($c, 'name'),
            'href' => url('news.php?cat=' . $c['id']), 'items' => $items];
    }
}

$events = q('SELECT * FROM events WHERE COALESCE(end_date, start_date) >= CURDATE() ORDER BY start_date, id LIMIT 5')->fetchAll();
$videos = q('SELECT * FROM videos WHERE is_active = 1 ORDER BY sort_order, created_at DESC LIMIT 4')->fetchAll();
$services = q("SELECT * FROM links WHERE link_group = 'eservice' ORDER BY sort_order, id")->fetchAll();

$term = latest_student_term();
$studentTotals = ['all' => 0];
if ($term) {
    foreach (q('SELECT level, SUM(male + female) AS total FROM student_stats WHERE academic_year = ? AND semester = ? GROUP BY level', [$term['academic_year'], $term['semester']]) as $r) {
        $studentTotals[$r['level']] = (int) $r['total'];
        $studentTotals['all'] += (int) $r['total'];
    }
}
$personnelCount = (int) q('SELECT COUNT(*) FROM personnel WHERE is_active = 1')->fetchColumn();
$executives = q("SELECT * FROM personnel WHERE is_active = 1 AND type = 'executive' ORDER BY sort_order, id LIMIT 5")->fetchAll();

$fbUrl = preg_match('#^https://(www\.|m\.)?facebook\.com/#i', setting('facebook')) ? setting('facebook') : '';
$isEn = lang() === 'en';
$monthsShort = $isEn
    ? ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    : ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
$yearShort = fn(int $ts) => substr((string) ((int) date('Y', $ts) + ($isEn ? 0 : 543)), 2);

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <?php if ($slides): ?>
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
      <?php if (count($slides) > 1): ?>
        <div class="carousel-indicators">
          <?php foreach ($slides as $i => $s): ?>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i ? '' : 'active' ?>" aria-label="<?= t('สไลด์') ?> <?= $i + 1 ?>"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="carousel-inner">
        <?php foreach ($slides as $i => $s): ?>
          <div class="carousel-item <?= $i ? '' : 'active' ?>" data-bs-interval="6000">
            <?php if ($s['link']): ?><a href="<?= e($s['link']) ?>"><?php endif; ?>
            <img src="<?= e(file_url($s['image'])) ?>" alt="<?= e(tr($s, 'title')) ?>">
            <?php if ($s['link']): ?></a><?php endif; ?>
            <?php if ($s['title']): ?><div class="carousel-caption d-none d-md-block"><h5 class="m-0"><?= e(tr($s, 'title')) ?></h5></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (count($slides) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="hero-default">
      <div class="container py-5">
        <div class="text-uppercase small opacity-75 mb-2"><?= e($siteSub) ?></div>
        <h1><?= e($siteName) ?></h1>
        <p class="slogan mb-4"><?= e(setting_l('slogan')) ?></p>
        <a href="<?= url('news.php') ?>" class="btn btn-light btn-lg me-2"><i class="bi bi-newspaper"></i> <?= t('ข่าวสาร') ?></a>
        <a href="<?= url('page.php?slug=history') ?>" class="btn btn-outline-light btn-lg"><?= t('เกี่ยวกับเรา') ?></a>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php if ($featuredDownloads): ?>
<section class="dl-strip">
  <div class="container">
    <div class="row g-0">
      <?php foreach ($featuredDownloads as $d): ?>
        <div class="col-md-6 col-lg-3">
          <a class="dl-item" href="<?= url('download.php?id=' . $d['id']) ?>" target="_blank">
            <i class="bi bi-cloud-arrow-down"></i>
            <span><span class="small opacity-75 d-block"><?= t('ดาวน์โหลด') ?></span><span class="t"><?= e(tr($d, 'title')) ?></span></span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section director-section">
  <div class="container">
    <div class="row g-4 align-items-center">
      <div class="col-md-4 col-lg-3 text-center">
        <div class="director-photo">
          <?php if (setting('director_photo')): ?>
            <img src="<?= e(file_url(setting('director_photo'))) ?>" alt="<?= e(setting_l('director_name')) ?>">
          <?php else: ?>
            <div class="ph"><i class="bi bi-person-fill"></i></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-md-8 col-lg-9">
        <div class="small text-uppercase fw-medium" style="color:var(--accent)"><?= t('สารจากผู้อำนวยการ') ?></div>
        <h2 class="h3 fw-semibold mb-3" style="color:var(--primary)"><?= e(setting_l('slogan')) ?></h2>
        <blockquote class="director-quote"><?= nl2br(e(setting_l('director_message'))) ?></blockquote>
        <div class="fw-semibold fs-5"><?= e(setting_l('director_name')) ?></div>
        <div class="text-muted"><?= e(setting_l('director_position')) ?></div>
        <?php if (setting('director_phone')): ?><div class="text-muted small mt-1"><i class="bi bi-telephone"></i> <?= e(setting('director_phone')) ?></div><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="section-head">
          <div><h2 class="section-title"><?= t('ข่าวสาร') ?></h2><p class="section-sub"><?= t('ข่าวประชาสัมพันธ์ กิจกรรม และประกาศของวิทยาลัย') ?></p></div>
        </div>
        <ul class="nav nav-pills news-tabs mb-3" role="tablist">
          <?php foreach ($newsTabs as $i => $tab): ?>
            <li class="nav-item" role="presentation">
              <button class="nav-link <?= $i ? '' : 'active' ?>" data-bs-toggle="pill" data-bs-target="#tab-<?= $tab['id'] ?>" type="button" role="tab"><?= e($tab['name']) ?></button>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="tab-content">
          <?php foreach ($newsTabs as $i => $tab): ?>
            <div class="tab-pane fade <?= $i ? '' : 'show active' ?>" id="tab-<?= $tab['id'] ?>" role="tabpanel">
              <div class="row g-3">
                <?php foreach ($tab['items'] as $n): ?>
                  <div class="col-sm-6 col-xl-4"><?= news_card($n) ?></div>
                <?php endforeach; ?>
                <?php if (!$tab['items']): ?><p class="text-muted"><?= t('ยังไม่มีข่าว') ?></p><?php endif; ?>
              </div>
              <?php if ($tab['items']): ?><div class="text-center mt-4"><a href="<?= e($tab['href']) ?>" class="btn btn-outline-primary"><?= e($tab['more']) ?> <i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="section-head">
          <div><h2 class="section-title"><?= t('ปฏิทินกิจกรรม') ?></h2><p class="section-sub"><?= t('กิจกรรมที่กำลังจะมาถึง') ?></p></div>
        </div>
        <div class="card border-0 shadow-sm"><div class="card-body announce-list">
          <?php foreach ($events as $ev): $ts = strtotime($ev['start_date']); ?>
            <div class="item">
              <div class="date-box" style="border-color:<?= e($ev['color']) ?>"><div class="d" style="color:<?= e($ev['color']) ?>"><?= date('j', $ts) ?></div><div class="m" style="background:<?= e($ev['color']) ?>"><?= $monthsShort[(int) date('n', $ts)] ?> <?= $yearShort($ts) ?></div></div>
              <div>
                <a class="t" href="<?= url('events.php?m=' . date('Y-m', $ts) . '#ev' . $ev['id']) ?>"><?= e(tr($ev, 'title')) ?></a>
                <div class="news-meta mt-1">
                  <?php if ($ev['end_date'] && $ev['end_date'] !== $ev['start_date']): ?><i class="bi bi-calendar-range"></i> <?= t('ถึง') ?> <?= thai_date($ev['end_date']) ?><br><?php endif; ?>
                  <?php if ($ev['location']): ?><i class="bi bi-geo-alt"></i> <?= e(tr($ev, 'location')) ?><?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$events): ?><p class="text-muted m-0"><?= t('ยังไม่มีกิจกรรมที่กำลังจะมาถึง') ?></p><?php endif; ?>
          <a href="<?= url('events.php') ?>" class="btn btn-primary w-100 mt-2"><i class="bi bi-calendar3"></i> <?= t('ดูปฏิทินทั้งหมด') ?></a>
        </div></div>
      </div>
    </div>
  </div>
</section>

<section class="stats-band">
  <div class="container py-4">
    <div class="row">
      <div class="col-6 col-md-3 stat-box"><div class="num" data-count="<?= $studentTotals['all'] ?>">0</div><div class="lbl"><?= t('ผู้เรียนทั้งหมด (คน)') ?></div></div>
      <div class="col-6 col-md-3 stat-box"><div class="num" data-count="<?= $studentTotals['ปวช.'] ?? 0 ?>">0</div><div class="lbl"><?= t('ระดับ ปวช. (คน)') ?></div></div>
      <div class="col-6 col-md-3 stat-box"><div class="num" data-count="<?= $studentTotals['ปวส.'] ?? 0 ?>">0</div><div class="lbl"><?= t('ระดับ ปวส. (คน)') ?></div></div>
      <div class="col-6 col-md-3 stat-box"><div class="num" data-count="<?= $personnelCount ?>">0</div><div class="lbl"><?= t('ครูและบุคลากร (คน)') ?></div></div>
    </div>
    <?php if ($term): ?>
      <p class="text-center small opacity-75 mb-0 mt-2"><?= t('ข้อมูลผู้เรียน ภาคเรียนที่') ?> <?= (int) $term['semester'] ?>/<?= (int) $term['academic_year'] - ($isEn ? 543 : 0) ?> · <a class="text-white" href="<?= url('students.php') ?>"><?= t('ดูรายละเอียด') ?></a></p>
    <?php endif; ?>
  </div>
</section>

<?php if ($services): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-head"><div><h2 class="section-title"><?= t('บริการออนไลน์') ?></h2><p class="section-sub"><?= t('E-Service สำหรับนักเรียน ครู และบุคคลทั่วไป') ?></p></div></div>
    <div class="row g-3">
      <?php foreach ($services as $s): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a class="service-item" href="<?= e($s['url']) ?>" target="_blank" rel="noopener"><i class="bi <?= e($s['icon']) ?>"></i><span><?= e(tr($s, 'title')) ?></span></a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($executives): ?>
<section class="section">
  <div class="container">
    <div class="section-head">
      <div><h2 class="section-title"><?= t('คณะผู้บริหาร') ?></h2></div>
      <a href="<?= url('personnel.php?type=executive') ?>" class="btn btn-sm btn-outline-primary"><?= t('ดูทั้งหมด') ?> <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3 justify-content-center">
      <?php foreach ($executives as $p): ?>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2"><?= person_card($p) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($videos || $fbUrl): ?>
<section class="section section-alt">
  <div class="container">
    <div class="row g-4">
      <?php if ($videos): ?>
        <div class="<?= $fbUrl ? 'col-lg-8' : 'col-12' ?>">
          <div class="section-head">
            <div><h2 class="section-title"><?= t('วิดีทัศน์') ?></h2><p class="section-sub"><?= t('วิดีทัศน์เผยแพร่ข้อมูลวิทยาลัย') ?></p></div>
            <a href="<?= url('videos.php') ?>" class="btn btn-sm btn-outline-primary"><?= t('ดูทั้งหมด') ?> <i class="bi bi-arrow-right"></i></a>
          </div>
          <div class="row g-3">
            <?php foreach ($videos as $i => $v): ?>
              <div class="<?= $i === 0 ? 'col-12' : 'col-4' ?>"><?= video_card($v, $i === 0) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($fbUrl): ?>
        <div class="<?= $videos ? 'col-lg-4' : 'col-12' ?>">
          <div class="section-head"><div><h2 class="section-title">Facebook</h2><p class="section-sub"><?= t('ติดตามข่าวสารล่าสุด') ?></p></div></div>
          <div class="fb-embed">
            <?= media_embed('https://www.facebook.com/plugins/page.php?href=' . urlencode($fbUrl) . '&tabs=timeline&width=500&height=560&small_header=true&adapt_container_width=true&hide_cover=false&locale=' . ($isEn ? 'en_US' : 'th_TH'), 'Facebook', 'Facebook Page') ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?= $videos ? video_modal() : '' ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
