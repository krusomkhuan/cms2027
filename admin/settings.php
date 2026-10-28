<?php
require_once __DIR__ . '/_layout.php';
require_admin();
require_once __DIR__ . '/../includes/facebook.php';

$textFields = [
    'site_name' => 'ชื่อสถานศึกษา (ไทย)',
    'site_name_en' => 'ชื่อสถานศึกษา (อังกฤษ)',
    'slogan' => 'คำขวัญ / สโลแกน',
    'address' => 'ที่อยู่',
    'phone' => 'เบอร์โทรศัพท์',
    'email' => 'อีเมล',
    'facebook' => 'ลิงก์ Facebook Page',
    'messenger_url' => 'ลิงก์แชท Messenger (เว้นว่าง = สร้างจากเพจ Facebook อัตโนมัติ เช่น https://m.me/pr.phonthong)',
    'line_url' => 'ลิงก์ LINE Official (เช่น https://lin.ee/...)',
    'youtube_url' => 'ลิงก์ช่อง YouTube',
    'admission_url' => 'ลิงก์ระบบสมัครเรียนออนไลน์ (แสดงปุ่ม “สมัครเรียนออนไลน์”)',
    'director_name' => 'ชื่อผู้อำนวยการ',
    'director_position' => 'ตำแหน่งผู้อำนวยการ',
    'director_phone' => 'เบอร์โทรผู้อำนวยการ',
    'map_embed' => 'ลิงก์ Google Maps (Embed)',
    'director_message' => 'สารจากผู้อำนวยการ (แสดงหน้าแรก)',
    'ticker_text' => 'ข้อความวิ่ง (บรรทัดละ 1 ข้อความ — เว้นว่างเพื่อใช้หัวข้อข่าวปักหมุดแทน)',
];
// ฉบับภาษาอังกฤษ — ถ้าเว้นว่าง หน้าเว็บภาษาอังกฤษจะใช้ภาษาไทยแทน
$enFields = [
    'slogan_en' => 'Slogan / Philosophy',
    'address_en' => 'Address',
    'director_name_en' => 'Director name',
    'director_position_en' => 'Director position',
    'director_message_en' => 'Message from the Director',
    'ticker_text_en' => 'Ticker text (one per line)',
];
$longFields = ['director_message', 'ticker_text', 'director_message_en', 'ticker_text_en'];
$imageFields = ['logo' => 'ตราสัญลักษณ์ (โลโก้)', 'director_photo' => 'รูปผู้อำนวยการ'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fb_save'])) {
    // โพสต์ล่าสุดจาก Facebook (Graph API) — token เว้นว่าง = ใช้ค่าเดิม
    csrf_check();
    save_setting('fb_page_id', preg_replace('/[^A-Za-z0-9.]/', '', $_POST['fb_page_id'] ?? ''));
    save_setting('fb_featured_posts', mb_substr(trim($_POST['fb_featured_posts'] ?? ''), 0, 3000));
    $featuredCount = count(fb_featured());
    if (isset($_POST['fb_clear'])) {
        foreach (['fb_page_token', 'fb_feed_cache', 'fb_feed_error', 'fb_feed_checked'] as $k) {
            save_setting($k, '');
        }
        flash('ลบ token และโพสต์ที่เก็บไว้แล้ว หน้าแรกจะแสดงข่าวล่าสุดของเว็บแทน');
    } else {
        $token = trim($_POST['fb_page_token'] ?? '');
        if ($token !== '') {
            save_setting('fb_page_token', $token);
        }
        if (fb_configured()) {
            [$count, $err] = fb_refresh_posts();
            $err ? flash('เชื่อมต่อ Facebook ไม่สำเร็จ: ' . $err, 'danger') : flash("เชื่อมต่อ Facebook สำเร็จ ดึงโพสต์ล่าสุดได้ $count โพสต์");
        } else {
            flash($featuredCount ? "บันทึกโพสต์แนะนำ $featuredCount โพสต์เรียบร้อย" : 'บันทึกแล้ว — ยังไม่มีโพสต์แนะนำหรือ token หน้าแรกจะแสดงข่าวล่าสุดของเว็บแทน', $featuredCount ? 'success' : 'warning');
        }
    }
    redirect('admin/settings.php#facebook');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        foreach (['facebook', 'messenger_url', 'line_url', 'youtube_url', 'admission_url'] as $k) {
            if (trim($_POST[$k] ?? '') !== '' && !preg_match('#^https?://#i', trim($_POST[$k]))) {
                throw new RuntimeException('ลิงก์ "' . $textFields[$k] . '" ต้องขึ้นต้นด้วย http:// หรือ https://');
            }
        }
        foreach ($textFields + $enFields as $k => $_) {
            $v = trim($_POST[$k] ?? '');
            if ($k === 'map_embed' && preg_match('/src="([^"]+)"/', $v, $m)) {
                $v = html_entity_decode($m[1]); // รองรับการวางโค้ด <iframe> ทั้งก้อน
            }
            save_setting($k, $v);
        }
        foreach (['theme_color', 'accent_color'] as $k) {
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $_POST[$k] ?? '')) {
                save_setting($k, $_POST[$k]);
            }
        }
        foreach ($imageFields as $k => $_) {
            if ($path = upload_file($k, 'site', IMG_EXT, 3)) {
                delete_file(setting($k));
                save_setting($k, $path);
                if ($k === 'logo') {
                    save_setting('favicon', ''); // ให้ไอคอนแท็บใช้โลโก้ใหม่แทน
                }
            } elseif (isset($_POST['remove_' . $k])) {
                delete_file(setting($k));
                save_setting($k, '');
            }
        }
        flash('บันทึกการตั้งค่าเรียบร้อย');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'danger');
    }
    redirect('admin/settings.php');
}

admin_header('ตั้งค่าเว็บไซต์', 'settings');
?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card"><div class="card-header">ข้อมูลทั่วไป</div><div class="card-body">
        <div class="row g-3">
          <?php foreach ($textFields as $k => $label): ?>
            <div class="<?= in_array($k, ['phone', 'email', 'director_name', 'director_position', 'facebook', 'messenger_url', 'line_url', 'youtube_url', 'director_phone'], true) ? 'col-md-6' : 'col-12' ?>">
              <label class="form-label" for="<?= $k ?>"><?= e($label) ?></label>
              <?php if (in_array($k, $longFields, true)): ?>
                <textarea class="form-control" id="<?= $k ?>" name="<?= $k ?>" rows="4"><?= e(setting($k)) ?></textarea>
              <?php else: ?>
                <input class="form-control" id="<?= $k ?>" name="<?= $k ?>" value="<?= e(setting($k)) ?>">
              <?php endif; ?>
              <?php if ($k === 'map_embed'): ?><div class="form-text">Google Maps → แชร์ → ฝังแผนที่ → คัดลอก HTML แล้ววางที่นี่ได้เลย</div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div></div>
      <div class="card mt-3"><div class="card-header">ภาษาอังกฤษ <span class="small text-muted">(สำหรับหน้าเว็บภาษาอังกฤษ — เว้นว่างจะแสดงภาษาไทยแทน)</span></div><div class="card-body">
        <div class="row g-3">
          <?php foreach ($enFields as $k => $label): ?>
            <div class="<?= in_array($k, $longFields, true) || $k === 'address_en' ? 'col-12' : 'col-md-6' ?>">
              <label class="form-label" for="<?= $k ?>"><?= e($label) ?></label>
              <?php if (in_array($k, $longFields, true)): ?>
                <textarea class="form-control" id="<?= $k ?>" name="<?= $k ?>" rows="4"><?= e(setting($k)) ?></textarea>
              <?php else: ?>
                <input class="form-control" id="<?= $k ?>" name="<?= $k ?>" value="<?= e(setting($k)) ?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div></div>
    </div>
    <div class="col-lg-4">
      <div class="card mb-3"><div class="card-header">รูปภาพ</div><div class="card-body">
        <?php foreach ($imageFields as $k => $label): ?>
          <div class="mb-4">
            <label class="form-label"><?= e($label) ?></label>
            <img id="prev_<?= $k ?>" src="<?= e(file_url(setting($k))) ?>" class="preview-img d-block mb-2 <?= setting($k) ? '' : 'd-none' ?>" style="max-height:120px" alt="">
            <input type="file" class="form-control form-control-sm" name="<?= $k ?>" accept="image/*" data-preview="#prev_<?= $k ?>">
            <?php if (setting($k)): ?><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_<?= $k ?>" id="rm_<?= $k ?>"><label class="form-check-label small" for="rm_<?= $k ?>">ลบรูป</label></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
        <div class="form-text mt-n2 mb-3">โลโก้แนะนำไฟล์ PNG พื้นหลังโปร่งใส</div>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label" for="theme_color">สีหลัก</label>
            <input type="color" class="form-control form-control-color w-100" id="theme_color" name="theme_color" value="<?= e(setting('theme_color', '#1e3a8a')) ?>">
          </div>
          <div class="col-6">
            <label class="form-label" for="accent_color">สีรอง</label>
            <input type="color" class="form-control form-control-color w-100" id="accent_color" name="accent_color" value="<?= e(setting('accent_color', '#d71920')) ?>">
          </div>
        </div>
        <div class="form-text">สีประจำวิทยาลัย: น้ำเงิน (คุณธรรม) และแดง (ผู้เสียสละและผู้สร้างสรรค์)</div>
      </div></div>
      <button class="btn btn-primary w-100"><i class="bi bi-save"></i> บันทึกการตั้งค่า</button>
    </div>
  </div>
</form>

<?php
$fbCache = json_decode(setting('fb_feed_cache', '[]'), true) ?: [];
$fbChecked = (int) setting('fb_feed_checked', '0');
?>
<form method="post" class="card mt-3" id="facebook">
  <div class="card-header"><i class="bi bi-facebook" style="color:#1877f2"></i> โพสต์ล่าสุดจาก Facebook (แสดงหน้าแรก)</div>
  <div class="card-body">
    <?= csrf_field() ?><input type="hidden" name="fb_save" value="1">
    <div class="row g-3">
      <div class="col-lg-6">
        <p class="small text-muted">ปลั๊กอินเพจของ Facebook ไม่แสดงไทม์ไลน์แล้ว เลือกได้ 2 วิธี (ใช้วิธีที่ 1 ก่อนถ้ามี): <b>1) โพสต์แนะนำ</b> วางลิงก์โพสต์ที่ต้องการ ไม่ต้องใช้ token · <b>2) โพสต์ล่าสุดอัตโนมัติ</b> ผ่าน Graph API (อัปเดตทุก 30 นาที) — ถ้าไม่ตั้งค่าทั้งสองแบบ หน้าแรกจะแสดงข่าวล่าสุดของเว็บแทน</p>
        <div class="mb-3"><label class="form-label">1) โพสต์แนะนำ — ลิงก์โพสต์ Facebook บรรทัดละ 1 ลิงก์ (สูงสุด 5)</label>
          <textarea class="form-control" name="fb_featured_posts" rows="4" placeholder="https://www.facebook.com/pr.phonthong/posts/...&#10;https://www.facebook.com/photo/?fbid=..."><?= e(setting('fb_featured_posts')) ?></textarea>
          <div class="form-text">คัดลอกลิงก์จากปุ่ม “แชร์ → คัดลอกลิงก์” หรือคลิกที่เวลาของโพสต์ · โพสต์ต้องตั้งค่าเป็นสาธารณะ · ใช้ได้ <?= count(fb_featured()) ?> ลิงก์</div></div>
        <div class="fw-medium small mb-2">2) โพสต์ล่าสุดอัตโนมัติ (Graph API)</div>
        <div class="mb-2"><label class="form-label">Page ID หรือชื่อเพจ</label>
          <input class="form-control" name="fb_page_id" value="<?= e(setting('fb_page_id', 'pr.phonthong')) ?>" placeholder="pr.phonthong"></div>
        <div class="mb-2"><label class="form-label">Page Access Token</label>
          <input type="password" class="form-control" name="fb_page_token" autocomplete="off" placeholder="<?= setting('fb_page_token') ? '•••••••• ตั้งค่าแล้ว (เว้นว่างเพื่อใช้ค่าเดิม)' : 'วาง token ที่ขึ้นต้นด้วย EAA...' ?>"></div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <button class="btn btn-primary"><i class="bi bi-save"></i> บันทึก<?= setting('fb_page_token') ? ' และดึงโพสต์ล่าสุด' : '' ?></button>
          <?php if (setting('fb_page_token')): ?><button class="btn btn-outline-danger" name="fb_clear" value="1" onclick="return confirm('ลบ token และโพสต์ที่เก็บไว้?')"><i class="bi bi-x-circle"></i> ยกเลิกการเชื่อมต่อ</button><?php endif; ?>
        </div>
        <div class="small mt-3">
          สถานะ:
          <?php if (!setting('fb_page_token')): ?><span class="badge text-bg-secondary">ยังไม่เชื่อมต่อ</span>
          <?php elseif (setting('fb_feed_error')): ?><span class="badge text-bg-danger">ผิดพลาด</span> <span class="text-danger"><?= e(setting('fb_feed_error')) ?></span>
          <?php else: ?><span class="badge text-bg-success">เชื่อมต่อแล้ว</span> โพสต์ที่เก็บไว้ <?= count($fbCache) ?> โพสต์<?= $fbChecked ? ' · อัปเดตล่าสุด ' . thai_date(date('Y-m-d H:i:s', $fbChecked), true) : '' ?><?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="p-3 bg-light rounded small">
          <div class="fw-semibold mb-2">วิธีขอ Page Access Token (ทำครั้งเดียว โดยผู้ดูแลเพจ)</div>
          <ol class="ps-3 mb-2">
            <li>เข้า <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">developers.facebook.com/apps</a> ด้วยบัญชีที่เป็นผู้ดูแลเพจ → <b>Create App</b> (เลือกประเภท Business หรือ Other)</li>
            <li>เปิด <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener">Graph API Explorer</a> → เลือกแอปที่สร้าง → <b>Get Page Access Token</b> → เลือกเพจ “งานประชาสัมพันธ์ วิทยาลัยการอาชีพโพนทอง” และให้สิทธิ์ <code>pages_read_engagement</code></li>
            <li>นำ token ไปที่ <a href="https://developers.facebook.com/tools/debug/accesstoken/" target="_blank" rel="noopener">Access Token Debugger</a> → <b>Extend Access Token</b> เพื่อให้ใช้ได้นาน แล้วคัดลอกมาวางที่ช่องด้านซ้าย</li>
          </ol>
          <div class="text-muted">token เก็บในฐานข้อมูลของเว็บเท่านั้น ไม่แสดงบนหน้าเว็บ และใช้อ่านโพสต์สาธารณะของเพจอย่างเดียว</div>
        </div>
      </div>
    </div>
  </div>
</form>
<?php admin_footer(); ?>
