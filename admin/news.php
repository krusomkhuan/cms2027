<?php
require_once __DIR__ . '/_layout.php';

$query = array_intersect_key($_GET, array_flip(['cat', 'q', 'status', 'page']));
// ผู้สื่อข่าวจัดการได้เฉพาะข่าวของตัวเอง
$own = is_reporter() ? ' AND author_id = ' . (int) $me['id'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        $id = (int) $_POST['delete'];
        $n = q("SELECT cover, attachment FROM news WHERE id = ?$own", [$id])->fetch();
        if ($n) {
            foreach (q('SELECT path FROM news_images WHERE news_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN) as $p) {
                delete_file($p);
            }
            delete_file($n['cover']);
            delete_file($n['attachment']);
            q('DELETE FROM news WHERE id = ?', [$id]);
            flash('ลบข่าวเรียบร้อย');
        }
    } elseif (isset($_POST['approve']) && !is_reporter()) {
        q("UPDATE news SET status = 'published', published_at = GREATEST(published_at, NOW()) WHERE id = ? AND status = 'pending'", [(int) $_POST['approve']]);
        flash('อนุมัติและเผยแพร่ข่าวเรียบร้อย');
    }
    redirect('admin/news.php?' . http_build_query($query));
}

$catId = (int) ($_GET['cat'] ?? 0);
$kw = trim($_GET['q'] ?? '');
$status = isset(NEWS_STATUS[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$page = current_page();
$perPage = 20;
$where = '1' . str_replace('author_id', 'n.author_id', $own);
$params = [];
if ($catId) {
    $where .= ' AND n.category_id = ?';
    $params[] = $catId;
}
if ($kw !== '') {
    $where .= ' AND n.title LIKE ?';
    $params[] = "%$kw%";
}
if ($status) {
    $where .= ' AND n.status = ?';
    $params[] = $status;
}
$total = (int) q("SELECT COUNT(*) FROM news n WHERE $where", $params)->fetchColumn();
$rows = q("SELECT n.*, c.name AS cat_name, c.color AS cat_color, u.fullname AS author, u.role AS author_role
    FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id
    WHERE $where ORDER BY n.status = 'pending' DESC, n.published_at DESC, n.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params)->fetchAll();
$cats = q('SELECT id, name FROM categories ORDER BY sort_order, id')->fetchAll();
$filters = array_filter(['cat' => $catId ?: null, 'q' => $kw ?: null, 'status' => $status ?: null]);

admin_header(is_reporter() ? 'ข่าวของฉัน' : 'จัดการข่าวสาร', 'news');
?>
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center">
    <form class="d-flex flex-wrap gap-2 me-auto" method="get">
      <select name="cat" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
        <option value="">ทุกหมวด</option>
        <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $catId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
      <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
        <option value="">ทุกสถานะ</option>
        <?php foreach (NEWS_STATUS as $k => [$label]): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" style="width:200px" placeholder="ค้นหาหัวข้อ..." value="<?= e($kw) ?>">
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
    <a href="news_form.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> <?= is_reporter() ? 'เขียนข่าว' : 'เพิ่มข่าว' ?></a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th style="width:80px">ภาพ</th><th>หัวข้อ</th><th>หมวด</th><th>วันที่ประกาศ</th><th class="text-center">อ่าน</th><th>สถานะ</th><th style="width:140px"></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $n): ?>
          <tr class="<?= $n['status'] === 'pending' && !is_reporter() ? 'table-warning' : '' ?>">
            <td><?php if ($n['cover']): ?><img src="<?= e(file_url($n['cover'])) ?>" class="thumb-sm" alt=""><?php else: ?><div class="thumb-sm d-flex align-items-center justify-content-center text-muted"><i class="bi bi-image"></i></div><?php endif; ?></td>
            <td>
              <?= $n['is_pinned'] ? '<i class="bi bi-pin-angle-fill text-warning" title="ปักหมุด"></i>' : '' ?>
              <a href="news_form.php?id=<?= $n['id'] ?>" class="text-decoration-none text-dark"><?= e($n['title']) ?></a>
              <div class="small text-muted"><?= e($n['author'] ?? '') ?><?= $n['author_role'] === 'reporter' ? ' <span class="badge text-bg-light">สมาชิก</span>' : '' ?></div>
            </td>
            <td class="text-nowrap"><?php if ($n['cat_name']): ?><span class="badge" style="background:<?= e($n['cat_color']) ?>"><?= e($n['cat_name']) ?></span><?php endif; ?></td>
            <td class="small text-nowrap"><?= thai_date($n['published_at'], true) ?></td>
            <td class="text-center"><?= number_format($n['views']) ?></td>
            <td><?= status_badge($n['status']) ?></td>
            <td class="text-nowrap text-end">
              <?php if ($n['status'] === 'pending' && !is_reporter()): ?>
                <form method="post" class="d-inline" action="news.php?<?= e(http_build_query($query)) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success" name="approve" value="<?= $n['id'] ?>" title="อนุมัติเผยแพร่"><i class="bi bi-check-lg"></i></button></form>
              <?php elseif ($n['status'] === 'published'): ?>
                <a href="<?= url('news_view.php?id=' . $n['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="ดู"><i class="bi bi-eye"></i></a>
              <?php endif; ?>
              <a href="news_form.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary" title="<?= $n['status'] === 'pending' && !is_reporter() ? 'ตรวจ / แก้ไข' : 'แก้ไข' ?>"><i class="bi bi-pencil"></i></a>
              <?= delete_button('news.php?' . http_build_query($query), $n['id'], 'ยืนยันการลบข่าว "' . excerpt($n['title'], 50) . '"?') ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-5"><?= is_reporter() ? 'ยังไม่มีข่าว — กด “เขียนข่าว” เพื่อเริ่ม' : 'ไม่พบข่าว' ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center">
    <span class="small text-muted">ทั้งหมด <?= number_format($total) ?> รายการ</span>
    <?= pagination($total, $perPage, $page, $filters) ?>
  </div>
</div>
<?php admin_footer(); ?>
