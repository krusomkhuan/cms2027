<?php
require_once __DIR__ . '/_layout.php';
require_admin();

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
<?php admin_footer(); ?>
