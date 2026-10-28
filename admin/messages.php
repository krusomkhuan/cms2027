<?php
require_once __DIR__ . '/_layout.php';

// เรื่องร้องเรียนการทุจริตเป็นข้อมูลอ่อนไหว ให้เห็นเฉพาะผู้ดูแลระบบ
$types = ['contact' => 'ติดต่อสอบถาม'];
if ($me['role'] === 'admin') {
    $types['complaint'] = 'เรื่องร้องเรียน';
}
$type = isset($types[$_GET['type'] ?? '']) ? $_GET['type'] : 'contact';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $placeholders = implode(',', array_fill(0, count($types), '?'));
    if (isset($_POST['delete'])) {
        q("DELETE FROM messages WHERE id = ? AND type IN ($placeholders)", [(int) $_POST['delete'], ...array_keys($types)]);
        flash('ลบข้อความเรียบร้อย');
    } elseif (isset($_POST['unread'])) {
        q("UPDATE messages SET is_read = 0 WHERE id = ? AND type IN ($placeholders)", [(int) $_POST['unread'], ...array_keys($types)]);
    }
    redirect('admin/messages.php?type=' . $type);
}

$view = null;
if (!empty($_GET['id'])) {
    $view = q('SELECT * FROM messages WHERE id = ? AND type = ?', [(int) $_GET['id'], $type])->fetch() ?: null;
    if ($view && !$view['is_read']) {
        q('UPDATE messages SET is_read = 1 WHERE id = ?', [$view['id']]);
    }
}
$page = current_page();
$perPage = 25;
$total = (int) q('SELECT COUNT(*) FROM messages WHERE type = ?', [$type])->fetchColumn();
$rows = q("SELECT * FROM messages WHERE type = ? ORDER BY created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), [$type])->fetchAll();
$unread = q('SELECT type, COUNT(*) FROM messages WHERE is_read = 0 GROUP BY type')->fetchAll(PDO::FETCH_KEY_PAIR);

admin_header('ข้อความติดต่อ / เรื่องร้องเรียน', 'messages');
?>
<ul class="nav nav-tabs mb-3">
  <?php foreach ($types as $k => $label): ?>
    <li class="nav-item"><a class="nav-link <?= $type === $k ? 'active' : '' ?>" href="?type=<?= $k ?>"><?= $label ?> <?php if (!empty($unread[$k])): ?><span class="badge text-bg-danger"><?= $unread[$k] ?></span><?php endif; ?></a></li>
  <?php endforeach; ?>
</ul>
<div class="row g-3">
  <div class="<?= $view ? 'col-lg-5' : 'col-12' ?>">
    <div class="card">
      <div class="list-group list-group-flush">
        <?php foreach ($rows as $r): ?>
          <a href="?type=<?= $type ?>&id=<?= $r['id'] ?><?= $page > 1 ? '&page=' . $page : '' ?>" class="list-group-item list-group-item-action <?= $view && (int) $view['id'] === (int) $r['id'] ? 'active' : '' ?>">
            <div class="d-flex justify-content-between gap-2">
              <span class="<?= $r['is_read'] ? '' : 'fw-semibold' ?> text-truncate"><?= $r['is_read'] ? '' : '<i class="bi bi-circle-fill text-danger" style="font-size:.5rem;vertical-align:middle"></i> ' ?><?= e($r['subject']) ?></span>
              <small class="text-nowrap opacity-75"><?= thai_date($r['created_at']) ?></small>
            </div>
            <small class="opacity-75"><?= e($r['name']) ?></small>
          </a>
        <?php endforeach; ?>
        <?php if (!$rows): ?><div class="list-group-item text-muted text-center py-5">ยังไม่มีข้อความ</div><?php endif; ?>
      </div>
      <div class="card-footer bg-white"><?= pagination($total, $perPage, $page, ['type' => $type]) ?></div>
    </div>
  </div>
  <?php if ($view): ?>
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
          <span class="fw-medium"><?= e($view['subject']) ?></span>
          <div class="d-flex gap-1">
            <form method="post" action="?type=<?= $type ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary" name="unread" value="<?= $view['id'] ?>" title="ทำเครื่องหมายยังไม่อ่าน"><i class="bi bi-envelope"></i></button></form>
            <?= delete_button('messages.php?type=' . $type, $view['id'], 'ลบข้อความนี้?') ?>
          </div>
        </div>
        <div class="card-body">
          <dl class="row small mb-3">
            <dt class="col-3">ผู้ส่ง</dt><dd class="col-9"><?= e($view['name']) ?></dd>
            <dt class="col-3">อีเมล</dt><dd class="col-9"><?= $view['email'] ? '<a href="mailto:' . e($view['email']) . '">' . e($view['email']) . '</a>' : '-' ?></dd>
            <dt class="col-3">โทร</dt><dd class="col-9"><?= e($view['phone'] ?: '-') ?></dd>
            <dt class="col-3">วันที่</dt><dd class="col-9"><?= thai_date($view['created_at'], true) ?></dd>
          </dl>
          <div class="p-3 bg-light rounded" style="white-space:pre-wrap"><?= e($view['message']) ?></div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
