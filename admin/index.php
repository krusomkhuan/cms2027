<?php
require_once __DIR__ . '/_layout.php';

// ผู้สื่อข่าวเห็นเฉพาะข่าวของตัวเอง
$own = is_reporter() ? ' AND n.author_id = ' . (int) $me['id'] : '';

if (is_reporter()) {
    $counts = q("SELECT status, COUNT(*) FROM news n WHERE 1$own GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $stats = [
        ['ข่าวของฉัน', array_sum($counts), 'bi-newspaper', 'news.php'],
        ['เผยแพร่แล้ว', $counts['published'] ?? 0, 'bi-check-circle', 'news.php?status=published'],
        ['รอตรวจ', $counts['pending'] ?? 0, 'bi-hourglass-split', 'news.php?status=pending'],
        ['ยอดอ่านรวม', (int) q("SELECT COALESCE(SUM(views), 0) FROM news n WHERE 1$own")->fetchColumn(), 'bi-eye', null],
    ];
} else {
    $stats = [
        ['ข่าวรอตรวจ', (int) q("SELECT COUNT(*) FROM news WHERE status = 'pending'")->fetchColumn(), 'bi-hourglass-split', 'news.php?status=pending'],
        ['ข่าวทั้งหมด', (int) q('SELECT COUNT(*) FROM news')->fetchColumn(), 'bi-newspaper', 'news.php'],
        ['ข้อความใหม่', (int) q("SELECT COUNT(*) FROM messages WHERE is_read = 0 AND type IN ('contact'" . ($me['role'] === 'admin' ? ",'complaint'" : '') . ')')->fetchColumn(), 'bi-envelope', 'messages.php'],
        ['ผู้เข้าชมวันนี้ / ทั้งหมด', (int) q('SELECT COALESCE(SUM(hits), 0) FROM visit_stats WHERE visit_date = CURDATE()')->fetchColumn(), 'bi-graph-up', null],
    ];
}
$latest = q("SELECT n.id, n.title, n.status, n.views, n.published_at, c.name AS cat_name, u.fullname AS author
    FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id
    WHERE 1$own ORDER BY n.status = 'pending' DESC, n.created_at DESC LIMIT 8")->fetchAll();
$popular = q("SELECT id, title, views FROM news n WHERE status = 'published'$own ORDER BY views DESC LIMIT 5")->fetchAll();

admin_header('แดชบอร์ด', 'dashboard');
?>
<?php if (is_reporter()): ?>
  <div class="alert alert-light border d-flex gap-3 align-items-center">
    <i class="bi bi-person-badge fs-3" style="color:var(--primary)"></i>
    <div>สวัสดี <b><?= e($me['fullname']) ?></b> — คุณมีสิทธิ์ <b>ผู้สื่อข่าว</b> เขียนข่าวและส่งให้ผู้ดูแลตรวจก่อนเผยแพร่<?= setting('reporter_autopublish') === '1' ? ' (ขณะนี้เปิดให้เผยแพร่ได้ทันที)' : '' ?></div>
  </div>
<?php endif; ?>
<div class="row g-3 mb-4">
  <?php foreach ($stats as $i => [$label, $n, $icon, $href]): ?>
    <div class="col-6 col-xl-3">
      <<?= $href ? 'a href="' . $href . '"' : 'div' ?> class="card text-decoration-none text-dark h-100 <?= $i === 0 && !is_reporter() && $n ? 'border border-warning' : '' ?>">
        <div class="stat-tile"><div class="ic"><i class="bi <?= $icon ?>"></i></div><div><div class="n"><?= number_format($n) ?><?= $href || is_reporter() ? '' : ' <small class="fs-6 text-muted">/ ' . number_format((int) setting('visitor_count', '0')) . '</small>' ?></div><div class="text-muted small"><?= $label ?></div></div></div>
      </<?= $href ? 'a' : 'div' ?>>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <?= is_reporter() ? 'ข่าวล่าสุดของฉัน' : 'ข่าวล่าสุด' ?> <a href="news_form.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> <?= is_reporter() ? 'เขียนข่าว' : 'เพิ่มข่าว' ?></a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>หัวข้อ</th><?= is_reporter() ? '' : '<th>ผู้เขียน</th>' ?><th>หมวด</th><th>วันที่</th><th>สถานะ</th></tr></thead>
          <tbody>
            <?php foreach ($latest as $n): ?>
              <tr class="<?= $n['status'] === 'pending' && !is_reporter() ? 'table-warning' : '' ?>">
                <td><a href="news_form.php?id=<?= $n['id'] ?>" class="text-decoration-none"><?= e(excerpt($n['title'], 70)) ?></a></td>
                <?php if (!is_reporter()): ?><td class="small text-nowrap"><?= e($n['author'] ?? '-') ?></td><?php endif; ?>
                <td class="small text-nowrap"><?= e($n['cat_name'] ?? '-') ?></td>
                <td class="small text-nowrap"><?= thai_date($n['published_at']) ?></td>
                <td><?= status_badge($n['status']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$latest): ?><tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีข่าว</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><?= is_reporter() ? 'ข่าวของฉันที่มีคนอ่านมากที่สุด' : 'ข่าวยอดนิยม' ?></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($popular as $p): ?>
          <li class="list-group-item d-flex justify-content-between gap-2"><span class="small"><?= e(excerpt($p['title'], 60)) ?></span><span class="badge text-bg-light"><?= number_format($p['views']) ?></span></li>
        <?php endforeach; ?>
        <?php if (!$popular): ?><li class="list-group-item small text-muted">-</li><?php endif; ?>
      </ul>
    </div>
    <div class="card">
      <div class="card-header">ทางลัด</div>
      <div class="card-body d-grid gap-2">
        <a href="news_form.php" class="btn btn-outline-secondary text-start"><i class="bi bi-plus-circle"></i> <?= is_reporter() ? 'เขียนข่าวใหม่' : 'เพิ่มข่าวใหม่' ?></a>
        <?php if (is_reporter()): ?>
          <a href="profile.php" class="btn btn-outline-secondary text-start"><i class="bi bi-person-gear"></i> แก้ไขข้อมูลส่วนตัว / รหัสผ่าน</a>
        <?php else: ?>
          <a href="news.php?status=pending" class="btn btn-outline-secondary text-start"><i class="bi bi-hourglass-split"></i> ตรวจข่าวจากสมาชิก</a>
          <a href="personnel_form.php" class="btn btn-outline-secondary text-start"><i class="bi bi-person-plus"></i> เพิ่มบุคลากร</a>
          <a href="students.php" class="btn btn-outline-secondary text-start"><i class="bi bi-mortarboard"></i> บันทึกจำนวนผู้เรียน</a>
          <a href="downloads.php" class="btn btn-outline-secondary text-start"><i class="bi bi-upload"></i> อัปโหลดเอกสาร</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
