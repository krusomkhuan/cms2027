<?php
require_once __DIR__ . '/includes/front.php';

$activeMenu = 'events';
$m = preg_match('/^\d{4}-\d{2}$/', $_GET['m'] ?? '') ? $_GET['m'] : date('Y-m');
$first = new DateTimeImmutable($m . '-01');
$last = $first->modify('last day of this month');
$monthsLong = lang() === 'en' ? ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] : ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

$events = q('SELECT * FROM events WHERE start_date <= ? AND COALESCE(end_date, start_date) >= ? ORDER BY start_date, id',
    [$last->format('Y-m-d'), $first->format('Y-m-d')])->fetchAll();

// กระจายกิจกรรมลงแต่ละวัน
$byDay = [];
foreach ($events as $ev) {
    $d = max($ev['start_date'], $first->format('Y-m-d'));
    $end = min($ev['end_date'] ?: $ev['start_date'], $last->format('Y-m-d'));
    for ($cur = new DateTime($d); $cur->format('Y-m-d') <= $end; $cur->modify('+1 day')) {
        $byDay[$cur->format('Y-m-d')][] = $ev;
    }
}

// ปฏิทินเริ่มวันอาทิตย์
$start = $first->modify('-' . $first->format('w') . ' days');
$end = $last->modify('+' . (6 - (int) $last->format('w')) . ' days');
$today = date('Y-m-d');
$title = t('ปฏิทินกิจกรรม') . ' ' . $monthsLong[(int) $first->format('n')] . ' ' . ((int) $first->format('Y') + (lang() === 'en' ? 0 : 543));

$pageTitle = t('ปฏิทินกิจกรรม');
require __DIR__ . '/includes/header.php';
echo page_banner($pageTitle, [t('ข่าวสาร') => url('news.php'), $pageTitle => null]);
?>
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
      <a class="btn btn-outline-primary btn-sm" href="?m=<?= $first->modify('-1 month')->format('Y-m') ?>"><i class="bi bi-chevron-left"></i> <span class="d-none d-sm-inline"><?= t('เดือนก่อน') ?></span></a>
      <h2 class="h5 m-0 text-center"><?= e($title) ?></h2>
      <a class="btn btn-outline-primary btn-sm" href="?m=<?= $first->modify('+1 month')->format('Y-m') ?>"><span class="d-none d-sm-inline"><?= t('เดือนถัดไป') ?></span> <i class="bi bi-chevron-right"></i></a>
    </div>
    <?php if ($m !== date('Y-m')): ?><div class="text-center mb-3"><a href="events.php" class="small"><?= t('กลับไปเดือนปัจจุบัน') ?></a></div><?php endif; ?>

    <div class="table-responsive shadow-sm rounded mb-5">
      <table class="table table-bordered cal mb-0">
        <thead><tr><?php foreach (lang() === 'en' ? ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] : ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'] as $dn): ?><th><?= $dn ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php for ($d = $start; $d <= $end; $d = $d->modify('+1 day')): $key = $d->format('Y-m-d'); ?>
            <?php if ($d->format('w') === '0'): ?><tr><?php endif; ?>
            <td class="<?= $d->format('Y-m') !== $m ? 'other' : '' ?> <?= $key === $today ? 'today' : '' ?>">
              <span class="dnum"><?= $d->format('j') ?></span>
              <?php foreach ($byDay[$key] ?? [] as $ev): ?>
                <a class="ev" href="#ev<?= $ev['id'] ?>" style="background:<?= e($ev['color']) ?>" title="<?= e(tr($ev, 'title')) ?>"><?= e(tr($ev, 'title')) ?></a>
              <?php endforeach; ?>
            </td>
            <?php if ($d->format('w') === '6'): ?></tr><?php endif; ?>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>

    <h3 class="h5 section-title mb-3"><?= t('รายการกิจกรรมเดือนนี้') ?></h3>
    <div class="list-group shadow-sm">
      <?php foreach ($events as $ev): ?>
        <div class="list-group-item py-3" id="ev<?= $ev['id'] ?>" style="border-left:5px solid <?= e($ev['color']) ?>">
          <div class="fw-medium"><?= e(tr($ev, 'title')) ?></div>
          <div class="news-meta d-flex flex-wrap gap-3 mt-1">
            <span><i class="bi bi-calendar3"></i> <?= thai_date($ev['start_date'], false, true) ?><?= $ev['end_date'] && $ev['end_date'] !== $ev['start_date'] ? ' – ' . thai_date($ev['end_date'], false, true) : '' ?></span>
            <?php if ($ev['time_text']): ?><span><i class="bi bi-clock"></i> <?= e($ev['time_text']) ?></span><?php endif; ?>
            <?php if ($ev['location']): ?><span><i class="bi bi-geo-alt"></i> <?= e(tr($ev, 'location')) ?></span><?php endif; ?>
          </div>
          <?php if ($ev['detail']): ?><div class="mt-2 small"><?= nl2br(e(tr($ev, 'detail'))) ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (!$events): ?><div class="list-group-item text-muted text-center py-4"><?= t('ไม่มีกิจกรรมในเดือนนี้') ?></div><?php endif; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
