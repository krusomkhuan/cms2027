<?php
/** @var string $pageTitle */
/** @var string $activeMenu */
$pageTitle = $pageTitle ?? '';
$activeMenu = $activeMenu ?? '';

// นับผู้เข้าชม 1 ครั้งต่อ session (ยอดรวม + รายวัน)
if (empty($_SESSION['visited'])) {
    $_SESSION['visited'] = 1;
    q("INSERT INTO settings (skey, svalue) VALUES ('visitor_count', '1') ON DUPLICATE KEY UPDATE svalue = CAST(svalue AS UNSIGNED) + 1");
    q('INSERT INTO visit_stats (visit_date, hits) VALUES (CURDATE(), 1) ON DUPLICATE KEY UPDATE hits = hits + 1');
}

$isEn = lang() === 'en';
$siteName = $isEn ? setting('site_name_en', setting('site_name')) : setting('site_name', 'วิทยาลัยการอาชีพโพนทอง');
$siteSub = $isEn ? setting('site_name') : setting('site_name_en');
$menuPages = q('SELECT slug, title, title_en FROM pages WHERE show_in_menu = 1 ORDER BY sort_order, id')->fetchAll();
$menuCats = q('SELECT id, name, name_en FROM categories ORDER BY sort_order, id')->fetchAll();
$menuDepts = q('SELECT id, name, name_en, type FROM departments ORDER BY type, sort_order, id')->fetchAll();
$colorVars = '';
foreach (['--primary' => ['theme_color', '#1e3a8a'], '--accent' => ['accent_color', '#d71920']] as $var => [$key, $default]) {
    $c = setting($key, $default);
    $colorVars .= $var . ':' . (preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : $default) . ';';
}

// ข้อความวิ่ง: จากการตั้งค่า (บรรทัดละ 1 ข้อความ) หรือหัวข้อข่าวปักหมุด
$ticker = [];
foreach (preg_split('/\R/', setting_l('ticker_text')) as $line) {
    if (trim($line) !== '') {
        $ticker[] = ['text' => trim($line), 'href' => null];
    }
}
if (!$ticker) {
    foreach (q("SELECT id, title, title_en FROM news WHERE is_pinned = 1 AND status = 'published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 5") as $t) {
        $ticker[] = ['text' => tr($t, 'title'), 'href' => url('news_view.php?id=' . $t['id'])];
    }
}
$socials = array_filter([
    'bi-facebook' => setting('facebook'),
    'bi-line' => setting('line_url'),
    'bi-youtube' => setting('youtube_url'),
], fn($u) => preg_match('#^https?://#i', $u));
$admissionUrl = preg_match('#^https?://#i', setting('admission_url')) ? setting('admission_url') : '';
?>
<!doctype html>
<html lang="<?= lang() ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($pageTitle ? $pageTitle . ' | ' : '') . $siteName) ?></title>
  <meta name="description" content="<?= e(setting('site_name') . ' ' . setting('site_name_en')) ?>">
  <?php if (setting('favicon', setting('logo'))): ?><link rel="icon" type="image/png" href="<?= e(file_url(setting('favicon', setting('logo')))) ?>"><?php endif; ?>
  <link rel="alternate" hreflang="th" href="<?= e(lang_url('th')) ?>">
  <link rel="alternate" hreflang="en" href="<?= e(lang_url('en')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
  <style>:root{<?= $colorVars ?>}</style>
  <script>try { const fs = localStorage.getItem('fontScale'); if (fs) document.documentElement.style.fontSize = fs; } catch (e) {}</script>
</head>
<body>

<?php if ($ticker): ?>
<div class="ticker">
  <div class="container d-flex align-items-center gap-2">
    <span class="ticker-label"><i class="bi bi-megaphone-fill"></i> <span class="d-none d-sm-inline"><?= t('ประกาศ') ?></span></span>
    <div class="ticker-track">
      <div class="ticker-move">
        <?php for ($rep = 0; $rep < 2; $rep++): ?>
          <?php foreach ($ticker as $tk): ?>
            <span class="ticker-item"><?php if ($tk['href']): ?><a href="<?= e($tk['href']) ?>"><?= e($tk['text']) ?></a><?php else: ?><?= e($tk['text']) ?><?php endif; ?></span>
          <?php endforeach; ?>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="topbar">
  <div class="container d-flex justify-content-between align-items-center gap-3">
    <div class="d-none d-lg-flex gap-3">
      <a href="<?= url('contact.php#map') ?>"><i class="bi bi-geo-alt"></i> <?= t('แผนที่และการเดินทาง') ?></a>
      <a href="<?= url('contact.php?type=complaint') ?>"><i class="bi bi-shield-exclamation"></i> <?= t('แจ้งเรื่องร้องเรียนการทุจริต') ?></a>
      <a href="<?= url('events.php') ?>"><i class="bi bi-calendar-event"></i> <?= t('ปฏิทินกิจกรรม') ?></a>
    </div>
    <div class="ms-auto d-flex align-items-center gap-3">
      <?php if (setting('phone')): ?><span class="d-none d-md-inline"><i class="bi bi-telephone"></i> <?= e(setting('phone')) ?></span><?php endif; ?>
      <?php if (setting('email')): ?><a class="d-none d-xl-inline" href="mailto:<?= e(setting('email')) ?>"><i class="bi bi-envelope"></i> <?= e(setting('email')) ?></a><?php endif; ?>
      <?php foreach ($socials as $icon => $href): ?><a href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= substr($icon, 3) ?>"><i class="bi <?= $icon ?>"></i></a><?php endforeach; ?>
      <span class="font-size-ctl" role="group" aria-label="<?= t('ขนาดตัวอักษร') ?>">
        <button type="button" data-fs="87.5%" title="<?= t('ตัวอักษรเล็ก') ?>">A-</button><button type="button" data-fs="" title="<?= t('ตัวอักษรปกติ') ?>">A</button><button type="button" data-fs="112.5%" title="<?= t('ตัวอักษรใหญ่') ?>">A+</button>
      </span>
      <span class="lang-switch" role="group" aria-label="Language">
        <a href="<?= e(lang_url('th')) ?>" class="<?= $isEn ? '' : 'active' ?>" hreflang="th" lang="th">TH</a><a href="<?= e(lang_url('en')) ?>" class="<?= $isEn ? 'active' : '' ?>" hreflang="en" lang="en">EN</a>
      </span>
      <a href="<?= url('admin/') ?>" title="<?= t('ระบบหลังบ้าน') ?>"><i class="bi bi-lock"></i></a>
    </div>
  </div>
</div>

<header class="site-header sticky-top">
  <nav class="navbar navbar-expand-xl">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url() ?>">
        <?php if (setting('logo')): ?>
          <img src="<?= file_url(setting('logo')) ?>" alt="" class="brand-logo">
        <?php else: ?>
          <span class="brand-logo brand-logo-text">PT</span>
        <?php endif; ?>
        <span class="brand-text">
          <span class="brand-th"><?= e($siteName) ?></span>
          <span class="brand-en"><?= e($siteSub) ?></span>
        </span>
      </a>
      <div class="d-flex align-items-center gap-1 order-xl-last">
        <button class="btn btn-search" type="button" data-bs-toggle="collapse" data-bs-target="#searchBar" aria-label="<?= t('ค้นหา') ?>"><i class="bi bi-search"></i></button>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="<?= t('เมนู') ?>">
          <i class="bi bi-list"></i>
        </button>
      </div>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link <?= $activeMenu === 'home' ? 'active' : '' ?>" href="<?= url() ?>"><?= t('หน้าแรก') ?></a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= $activeMenu === 'about' ? 'active' : '' ?>" href="#" data-bs-toggle="dropdown"><?= t('ข้อมูลสถานศึกษา') ?></a>
            <ul class="dropdown-menu">
              <?php foreach ($menuPages as $mp): ?>
                <li><a class="dropdown-item" href="<?= url('page.php?slug=' . urlencode($mp['slug'])) ?>"><?= e(tr($mp, 'title')) ?></a></li>
              <?php endforeach; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= url('students.php') ?>"><?= t('ข้อมูลผู้เรียน') ?></a></li>
              <li><a class="dropdown-item" href="<?= url('personnel.php') ?>"><?= t('ข้อมูลครูและบุคลากร') ?></a></li>
            </ul>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= $activeMenu === 'personnel' ? 'active' : '' ?>" href="#" data-bs-toggle="dropdown"><?= t('บุคลากร') ?></a>
            <ul class="dropdown-menu dropdown-menu-scroll">
              <li><a class="dropdown-item" href="<?= url('personnel.php?type=executive') ?>"><?= t('ผู้บริหารสถานศึกษา') ?></a></li>
              <li><a class="dropdown-item" href="<?= url('personnel.php') ?>"><?= t('บุคลากรทั้งหมด') ?></a></li>
              <li><hr class="dropdown-divider"></li>
              <li><h6 class="dropdown-header"><?= t('ฝ่าย / แผนกวิชา') ?></h6></li>
              <?php foreach ($menuDepts as $md): ?>
                <li><a class="dropdown-item" href="<?= url('personnel.php?dept=' . $md['id']) ?>"><?= e(tr($md, 'name')) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </li>
          <li class="nav-item"><a class="nav-link <?= $activeMenu === 'students' ? 'active' : '' ?>" href="<?= url('students.php') ?>"><?= t('ข้อมูลผู้เรียน') ?></a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($activeMenu, ['news', 'events', 'videos'], true) ? 'active' : '' ?>" href="#" data-bs-toggle="dropdown"><?= t('ข่าวสาร') ?></a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= url('news.php') ?>"><?= t('ข่าวทั้งหมด') ?></a></li>
              <?php foreach ($menuCats as $mc): ?>
                <li><a class="dropdown-item" href="<?= url('news.php?cat=' . $mc['id']) ?>"><?= e(tr($mc, 'name')) ?></a></li>
              <?php endforeach; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= url('events.php') ?>"><?= t('ปฏิทินกิจกรรม') ?></a></li>
              <li><a class="dropdown-item" href="<?= url('videos.php') ?>"><?= t('วิดีทัศน์') ?></a></li>
            </ul>
          </li>
          <li class="nav-item"><a class="nav-link <?= $activeMenu === 'downloads' ? 'active' : '' ?>" href="<?= url('downloads.php') ?>"><?= t('ดาวน์โหลด') ?></a></li>
          <li class="nav-item"><a class="nav-link <?= $activeMenu === 'contact' ? 'active' : '' ?>" href="<?= url('contact.php') ?>"><?= t('ติดต่อเรา') ?></a></li>
          <?php if ($admissionUrl): ?>
            <li class="nav-item ms-xl-2"><a class="btn btn-cta" href="<?= e($admissionUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-pencil-square"></i> <?= t('สมัครเรียนออนไลน์') ?></a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
  <div class="collapse" id="searchBar">
    <div class="container pb-3">
      <form action="<?= url('search.php') ?>" method="get" class="d-flex gap-2">
        <input type="search" name="q" class="form-control" placeholder="<?= t('ค้นหาข่าว บุคลากร ข้อมูลสถานศึกษา เอกสาร...') ?>" required>
        <button class="btn btn-primary text-nowrap"><i class="bi bi-search"></i> <?= t('ค้นหา') ?></button>
      </form>
    </div>
  </div>
</header>
<main>
