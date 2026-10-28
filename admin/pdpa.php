<?php
// PDPA: คำร้องใช้สิทธิ์เจ้าของข้อมูล / บันทึกความยินยอมคุกกี้ / ตั้งค่าและระยะเวลาเก็บข้อมูล (เฉพาะผู้ดูแลระบบ)
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../includes/pdpa.php';
require_admin();

$tab = in_array($_GET['tab'] ?? '', ['requests', 'consents', 'settings'], true) ? $_GET['tab'] : 'requests';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['update_request'])) {
        $status = isset(PDPA_STATUS[$_POST['status'] ?? '']) ? $_POST['status'] : 'received';
        q('UPDATE pdpa_requests SET status = ?, admin_note = ? WHERE id = ?', [$status, trim($_POST['admin_note'] ?? '') ?: null, (int) $_POST['update_request']]);
        flash('บันทึกสถานะคำร้องเรียบร้อย');
        redirect('admin/pdpa.php?id=' . (int) $_POST['update_request']);
    }
    if (isset($_POST['delete_request'])) {
        q('DELETE FROM pdpa_requests WHERE id = ?', [(int) $_POST['delete_request']]);
        flash('ลบคำร้องเรียบร้อย');
        redirect('admin/pdpa.php');
    }
    if (isset($_POST['save_settings'])) {
        foreach (['dpo_name' => 150, 'dpo_email' => 150, 'dpo_phone' => 50] as $k => $len) {
            save_setting($k, mb_substr(trim($_POST[$k] ?? ''), 0, $len));
        }
        save_setting('pdpa_banner', isset($_POST['pdpa_banner']) ? '1' : '0');
        $ga = strtoupper(trim($_POST['ga_id'] ?? ''));
        save_setting('ga_id', preg_match('/^G-[A-Z0-9]{4,20}$/', $ga) ? $ga : '');
        foreach (['retention_messages_days', 'retention_consent_days'] as $k) {
            save_setting($k, (string) max(30, min(3650, (int) ($_POST[$k] ?? 365))));
        }
        if (isset($_POST['bump_version'])) {
            // เพิ่มเลขเวอร์ชันนโยบาย → ผู้เข้าชมทุกคนต้องเลือกความยินยอมใหม่
            [$major, $minor] = array_map('intval', explode('.', setting('pdpa_policy_version', '1.0')) + [0, 0]);
            save_setting('pdpa_policy_version', $major . '.' . ($minor + 1));
        }
        flash('บันทึกการตั้งค่า PDPA เรียบร้อย' . ($ga !== '' && setting('ga_id') === '' ? ' (รหัส Google Analytics ไม่ถูกต้อง จึงไม่ได้บันทึก)' : ''));
        redirect('admin/pdpa.php?tab=settings');
    }
    if (isset($_POST['purge'])) {
        $msgDays = (int) setting('retention_messages_days', '365');
        $conDays = (int) setting('retention_consent_days', '730');
        // ลบเฉพาะข้อความติดต่อสอบถาม — เรื่องร้องเรียนต้องเก็บตามระเบียบราชการ
        $m = q("DELETE FROM messages WHERE type = 'contact' AND created_at < NOW() - INTERVAL ? DAY", [$msgDays])->rowCount();
        $c = q('DELETE FROM consent_logs WHERE created_at < NOW() - INTERVAL ? DAY', [$conDays])->rowCount();
        flash("ลบข้อมูลที่เกินระยะเวลาเก็บรักษาแล้ว: ข้อความติดต่อ $m รายการ, บันทึกความยินยอม $c รายการ");
        redirect('admin/pdpa.php?tab=settings');
    }
}

$openCount = (int) q("SELECT COUNT(*) FROM pdpa_requests WHERE status IN ('received','in_progress')")->fetchColumn();
$overdueCount = (int) q("SELECT COUNT(*) FROM pdpa_requests WHERE status IN ('received','in_progress') AND due_at < CURDATE()")->fetchColumn();

admin_header('PDPA / คุ้มครองข้อมูลส่วนบุคคล', 'pdpa');
?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link <?= $tab === 'requests' ? 'active' : '' ?>" href="?tab=requests">คำร้องใช้สิทธิ์ <?php if ($openCount): ?><span class="badge text-bg-danger"><?= $openCount ?></span><?php endif; ?></a></li>
  <li class="nav-item"><a class="nav-link <?= $tab === 'consents' ? 'active' : '' ?>" href="?tab=consents">บันทึกความยินยอมคุกกี้</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab === 'settings' ? 'active' : '' ?>" href="?tab=settings">ตั้งค่า / ระยะเวลาเก็บข้อมูล</a></li>
</ul>

<?php if ($tab === 'requests'):
    $view = !empty($_GET['id']) ? q('SELECT * FROM pdpa_requests WHERE id = ?', [(int) $_GET['id']])->fetch() : null;
    $status = isset(PDPA_STATUS[$_GET['status'] ?? '']) ? $_GET['status'] : '';
    $rows = q('SELECT * FROM pdpa_requests' . ($status ? ' WHERE status = ?' : '') . " ORDER BY status IN ('received','in_progress') DESC, due_at, id DESC LIMIT 200", $status ? [$status] : [])->fetchAll();
?>
  <?php if ($overdueCount): ?><div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle"></i> มีคำร้องเกินกำหนด 30 วัน <?= $overdueCount ?> รายการ — กฎหมายกำหนดให้ดำเนินการโดยไม่ชักช้า ภายใน 30 วัน</div><?php endif; ?>
  <div class="row g-3">
    <div class="<?= $view ? 'col-lg-5' : 'col-12' ?>">
      <div class="card">
        <div class="card-header d-flex flex-wrap gap-1">
          <a href="?tab=requests" class="btn btn-sm <?= $status ? 'btn-light' : 'btn-secondary' ?>">ทั้งหมด</a>
          <?php foreach (PDPA_STATUS as $k => [$label]): ?><a href="?tab=requests&status=<?= $k ?>" class="btn btn-sm <?= $status === $k ? 'btn-secondary' : 'btn-light' ?>"><?= $label ?></a><?php endforeach; ?>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($rows as $r): $overdue = in_array($r['status'], ['received', 'in_progress'], true) && $r['due_at'] < date('Y-m-d'); ?>
            <a href="?tab=requests&id=<?= $r['id'] ?>" class="list-group-item list-group-item-action <?= $view && (int) $view['id'] === (int) $r['id'] ? 'active' : '' ?>">
              <div class="d-flex justify-content-between gap-2">
                <span class="fw-medium"><?= e($r['ref_no']) ?> · <?= e(PDPA_REQUEST_TYPES[$r['request_type']][0] ?? $r['request_type']) ?></span>
                <span class="badge <?= PDPA_STATUS[$r['status']][1] ?>"><?= PDPA_STATUS[$r['status']][0] ?></span>
              </div>
              <small class="opacity-75"><?= e($r['name']) ?> · ยื่น <?= thai_date($r['created_at']) ?> · <span class="<?= $overdue ? 'text-danger fw-semibold' : '' ?>">ครบกำหนด <?= thai_date($r['due_at']) ?><?= $overdue ? ' (เกินกำหนด)' : '' ?></span></small>
            </a>
          <?php endforeach; ?>
          <?php if (!$rows): ?><div class="list-group-item text-muted text-center py-5">ยังไม่มีคำร้อง</div><?php endif; ?>
        </div>
      </div>
    </div>
    <?php if ($view): ?>
      <div class="col-lg-7">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-medium">คำร้อง <?= e($view['ref_no']) ?></span>
            <form method="post" data-confirm="ลบคำร้องนี้? (ควรเก็บไว้เป็นหลักฐานตามระยะเวลาที่ระเบียบกำหนด)"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" name="delete_request" value="<?= $view['id'] ?>"><i class="bi bi-trash"></i></button></form>
          </div>
          <div class="card-body">
            <dl class="row small mb-3">
              <dt class="col-4">ประเภทคำร้อง</dt><dd class="col-8"><?= e(PDPA_REQUEST_TYPES[$view['request_type']][0] ?? $view['request_type']) ?></dd>
              <dt class="col-4">ผู้ยื่น</dt><dd class="col-8"><?= e($view['name']) ?> (<?= e(PDPA_RELATIONS[$view['relation']][0] ?? '-') ?>)</dd>
              <dt class="col-4">อีเมล</dt><dd class="col-8"><a href="mailto:<?= e($view['email']) ?>?subject=<?= rawurlencode('คำร้อง ' . $view['ref_no']) ?>"><?= e($view['email']) ?></a></dd>
              <dt class="col-4">โทร</dt><dd class="col-8"><?= e($view['phone'] ?: '-') ?></dd>
              <dt class="col-4">วันที่ยื่น</dt><dd class="col-8"><?= thai_date($view['created_at'], true) ?></dd>
              <dt class="col-4">ครบกำหนด</dt><dd class="col-8"><?= thai_date($view['due_at'], false, true) ?></dd>
            </dl>
            <div class="p-3 bg-light rounded mb-3" style="white-space:pre-wrap"><?= e($view['detail']) ?></div>
            <div class="alert alert-light border small mb-3"><i class="bi bi-info-circle"></i> ตรวจสอบตัวตนของผู้ยื่นก่อนดำเนินการ (เช่น ขอสำเนาบัตรผ่านทางอีเมลราชการ) และแจ้งผลให้ผู้ยื่นทราบทางอีเมลภายในกำหนด</div>
            <form method="post">
              <?= csrf_field() ?><input type="hidden" name="update_request" value="<?= $view['id'] ?>">
              <div class="mb-2"><label class="form-label">สถานะ</label>
                <select name="status" class="form-select"><?php foreach (PDPA_STATUS as $k => [$label]): ?><option value="<?= $k ?>" <?= $view['status'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
              <div class="mb-3"><label class="form-label">บันทึกการดำเนินการ (ภายใน)</label><textarea name="admin_note" class="form-control" rows="4" placeholder="เช่น ยืนยันตัวตนแล้ว / ลบข้อมูลในระบบแล้วเมื่อ..."><?= e($view['admin_note']) ?></textarea></div>
              <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'consents'):
    $stats = q('SELECT COUNT(*) total, COALESCE(SUM(analytics), 0) a, COALESCE(SUM(media), 0) m, COALESCE(SUM(analytics = 0 AND media = 0), 0) n
        FROM consent_logs WHERE created_at >= NOW() - INTERVAL 30 DAY')->fetch();
    $pct = fn($n) => $stats['total'] ? round($n * 100 / $stats['total']) . '%' : '-';
    $logs = q('SELECT * FROM consent_logs ORDER BY id DESC LIMIT 100')->fetchAll();
?>
  <div class="row g-3 mb-3">
    <?php foreach ([['บันทึก 30 วันล่าสุด', number_format($stats['total']), 'bi-journal-check'], ['ยอมรับสื่อภายนอก', $pct($stats['m']), 'bi-play-btn'], ['ยอมรับวิเคราะห์การใช้งาน', $pct($stats['a']), 'bi-bar-chart'], ['เลือกเฉพาะที่จำเป็น', $pct($stats['n']), 'bi-shield-check']] as [$label, $val, $icon]): ?>
      <div class="col-6 col-xl-3"><div class="card h-100"><div class="stat-tile"><div class="ic"><i class="bi <?= $icon ?>"></i></div><div><div class="n"><?= $val ?></div><div class="text-muted small"><?= $label ?></div></div></div></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <div class="card-header small text-muted">100 รายการล่าสุด · เวอร์ชันนโยบายปัจจุบัน <?= e(setting('pdpa_policy_version', '1.0')) ?> · หมายเลข IP เก็บแบบเข้ารหัสทางเดียว (hash) ไม่สามารถย้อนกลับได้</div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0 small">
      <thead><tr><th>วันเวลา</th><th>รหัสความยินยอม</th><th class="text-center">วิเคราะห์</th><th class="text-center">สื่อภายนอก</th><th>เวอร์ชัน</th><th>อุปกรณ์</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td class="text-nowrap"><?= thai_date($l['created_at'], true) ?></td>
            <td><code><?= e($l['consent_id']) ?></code></td>
            <td class="text-center"><?= $l['analytics'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?></td>
            <td class="text-center"><?= $l['media'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?></td>
            <td><?= e($l['policy_version']) ?></td>
            <td class="text-truncate" style="max-width:320px"><?= e($l['user_agent']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มีบันทึก</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>

<?php else: ?>
  <div class="row g-3">
    <div class="col-lg-7">
      <form method="post" class="card"><div class="card-header">ตั้งค่า PDPA</div><div class="card-body">
        <?= csrf_field() ?><input type="hidden" name="save_settings" value="1">
        <h6 class="fw-semibold">เจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล (DPO)</h6>
        <div class="row g-2 mb-3">
          <div class="col-12"><label class="form-label small">ชื่อ / ตำแหน่ง</label><input class="form-control" name="dpo_name" value="<?= e(setting('dpo_name')) ?>"></div>
          <div class="col-md-7"><label class="form-label small">อีเมล</label><input type="email" class="form-control" name="dpo_email" value="<?= e(setting('dpo_email')) ?>"></div>
          <div class="col-md-5"><label class="form-label small">เบอร์โทร</label><input class="form-control" name="dpo_phone" value="<?= e(setting('dpo_phone')) ?>"></div>
        </div>
        <h6 class="fw-semibold">คุกกี้</h6>
        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="pdpa_banner" id="pb" <?= pdpa_enabled() ? 'checked' : '' ?>><label class="form-check-label" for="pb">แสดงแถบขอความยินยอมคุกกี้ และบล็อกสื่อภายนอกจนกว่าจะได้รับความยินยอม</label></div>
        <div class="mb-2"><label class="form-label small">Google Analytics 4 (Measurement ID) <span class="text-muted">— เว้นว่างหากไม่ใช้</span></label>
          <input class="form-control" name="ga_id" value="<?= e(setting('ga_id')) ?>" placeholder="G-XXXXXXXXXX">
          <div class="form-text">โหลดเฉพาะผู้เข้าชมที่ยินยอม “คุกกี้วิเคราะห์การใช้งาน” และปิดการเก็บ IP เต็ม</div></div>
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="bump_version" id="bv"><label class="form-check-label" for="bv">ขอความยินยอมใหม่จากผู้เข้าชมทุกคน (เวอร์ชันนโยบายปัจจุบัน <?= e(setting('pdpa_policy_version', '1.0')) ?> → เพิ่มเลข)</label>
          <div class="form-text">ใช้เมื่อแก้ไขนโยบายคุกกี้ในสาระสำคัญ เช่น เพิ่มผู้ให้บริการใหม่</div></div>
        <h6 class="fw-semibold">ระยะเวลาเก็บรักษาข้อมูล</h6>
        <div class="row g-2 mb-3">
          <div class="col-md-6"><label class="form-label small">ข้อความติดต่อสอบถาม (วัน)</label><input type="number" min="30" max="3650" class="form-control" name="retention_messages_days" value="<?= (int) setting('retention_messages_days', '365') ?>"></div>
          <div class="col-md-6"><label class="form-label small">บันทึกความยินยอมคุกกี้ (วัน)</label><input type="number" min="30" max="3650" class="form-control" name="retention_consent_days" value="<?= (int) setting('retention_consent_days', '730') ?>"></div>
        </div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก</button>
      </div></form>
    </div>
    <div class="col-lg-5">
      <div class="card mb-3"><div class="card-header">ลบข้อมูลที่เกินระยะเวลาเก็บรักษา</div><div class="card-body">
        <?php
        $oldMsg = (int) q("SELECT COUNT(*) FROM messages WHERE type = 'contact' AND created_at < NOW() - INTERVAL ? DAY", [(int) setting('retention_messages_days', '365')])->fetchColumn();
        $oldCon = (int) q('SELECT COUNT(*) FROM consent_logs WHERE created_at < NOW() - INTERVAL ? DAY', [(int) setting('retention_consent_days', '730')])->fetchColumn();
        ?>
        <ul class="small mb-3">
          <li>ข้อความติดต่อสอบถามเกิน <?= (int) setting('retention_messages_days', '365') ?> วัน: <b><?= number_format($oldMsg) ?></b> รายการ</li>
          <li>บันทึกความยินยอมเกิน <?= (int) setting('retention_consent_days', '730') ?> วัน: <b><?= number_format($oldCon) ?></b> รายการ</li>
        </ul>
        <form method="post" data-confirm="ลบข้อมูลที่เกินระยะเวลาเก็บรักษา? ไม่สามารถกู้คืนได้"><?= csrf_field() ?>
          <button class="btn btn-outline-danger" name="purge" value="1" <?= $oldMsg + $oldCon ? '' : 'disabled' ?>><i class="bi bi-trash"></i> ลบข้อมูลที่เกินกำหนด</button></form>
        <p class="small text-muted mt-3 mb-0">เรื่องร้องเรียนการทุจริตและคำร้องใช้สิทธิ์ จะไม่ถูกลบอัตโนมัติ เพราะต้องเก็บตามระเบียบราชการ</p>
      </div></div>
      <div class="card"><div class="card-header">หน้าที่เกี่ยวข้อง</div><div class="list-group list-group-flush">
        <a class="list-group-item list-group-item-action" href="<?= url('page.php?slug=privacy') ?>" target="_blank"><i class="bi bi-file-earmark-lock"></i> นโยบายคุ้มครองข้อมูลส่วนบุคคล</a>
        <a class="list-group-item list-group-item-action" href="<?= url('page.php?slug=cookie-policy') ?>" target="_blank"><i class="bi bi-cookie"></i> นโยบายคุกกี้</a>
        <a class="list-group-item list-group-item-action" href="<?= url('pdpa_request.php') ?>" target="_blank"><i class="bi bi-person-check"></i> แบบฟอร์มคำร้องใช้สิทธิ์</a>
        <a class="list-group-item list-group-item-action" href="pages.php"><i class="bi bi-pencil-square"></i> แก้ไขเนื้อหานโยบาย (เมนู ข้อมูลสถานศึกษา)</a>
      </div></div>
    </div>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
