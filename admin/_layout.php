<?php
require_once __DIR__ . '/../includes/init.php';

$me = require_login();

// ผู้สื่อข่าว (สมาชิกที่สมัครเอง) เข้าได้เฉพาะหน้าข่าวของตัวเองและข้อมูลส่วนตัว
const REPORTER_PAGES = ['index.php', 'news.php', 'news_form.php', 'profile.php', 'upload_image.php'];
if ($me['role'] === 'reporter' && !in_array(basename($_SERVER['SCRIPT_NAME']), REPORTER_PAGES, true)) {
    http_response_code(403);
    exit('สิทธิ์ผู้สื่อข่าวเข้าถึงได้เฉพาะเมนูข่าวสาร');
}

function is_reporter(): bool
{
    global $me;
    return $me['role'] === 'reporter';
}

function role_label(string $role): string
{
    return ['admin' => 'ผู้ดูแลระบบ', 'editor' => 'ผู้แก้ไขเนื้อหา', 'reporter' => 'ผู้สื่อข่าว (สมาชิก)'][$role] ?? $role;
}

const NEWS_STATUS = [
    'published' => ['เผยแพร่', 'text-bg-success'],
    'pending' => ['รอตรวจ', 'text-bg-warning'],
    'draft' => ['ฉบับร่าง', 'text-bg-secondary'],
];

function status_badge(string $status): string
{
    [$label, $class] = NEWS_STATUS[$status] ?? [$status, 'text-bg-light'];
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

function admin_header(string $title, string $active = ''): void
{
    global $me;
    if (is_reporter()) {
        $menu = [
            'dashboard' => ['index.php', 'bi-speedometer2', 'แดชบอร์ด'],
            'news' => ['news.php', 'bi-newspaper', 'ข่าวของฉัน'],
            'profile' => ['profile.php', 'bi-person-circle', 'ข้อมูลส่วนตัว'],
        ];
    } else {
        $menu = [
            'dashboard' => ['index.php', 'bi-speedometer2', 'แดชบอร์ด'],
            'news' => ['news.php', 'bi-newspaper', 'ข่าวสาร'],
            'categories' => ['categories.php', 'bi-tags', 'หมวดหมู่ข่าว'],
            'events' => ['events.php', 'bi-calendar-event', 'ปฏิทินกิจกรรม'],
            'videos' => ['videos.php', 'bi-youtube', 'วิดีทัศน์'],
            'messages' => ['messages.php', 'bi-envelope', 'ข้อความ / ร้องเรียน'],
            'personnel' => ['personnel.php', 'bi-person-badge', 'บุคลากร'],
            'departments' => ['departments.php', 'bi-diagram-3', 'ฝ่าย / แผนกวิชา'],
            'students' => ['students.php', 'bi-mortarboard', 'ข้อมูลผู้เรียน'],
            'pages' => ['pages.php', 'bi-file-earmark-richtext', 'ข้อมูลสถานศึกษา'],
            'slides' => ['slides.php', 'bi-images', 'สไลด์หน้าแรก'],
            'downloads' => ['downloads.php', 'bi-cloud-arrow-down', 'เอกสารดาวน์โหลด'],
            'links' => ['links.php', 'bi-link-45deg', 'ลิงก์ / E-Service'],
        ];
        if ($me['role'] === 'admin') {
            $menu['settings'] = ['settings.php', 'bi-gear', 'ตั้งค่าเว็บไซต์'];
            $menu['users'] = ['users.php', 'bi-people', 'ผู้ใช้งาน / สมาชิก'];
        }
    }
    $pendingNews = is_reporter() ? 0 : (int) q("SELECT COUNT(*) FROM news WHERE status = 'pending'")->fetchColumn();
    $theme = setting('theme_color', '#1e3a8a');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $theme)) {
        $theme = '#1e3a8a';
    }
    $unreadTypes = $me['role'] === 'admin' ? "'contact','complaint'" : "'contact'";
    $unread = (int) q("SELECT COUNT(*) FROM messages WHERE is_read = 0 AND type IN ($unreadTypes)")->fetchColumn();
    ?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> | ระบบจัดการเว็บไซต์</title>
  <?php if (setting('favicon', setting('logo'))): ?><link rel="icon" type="image/png" href="<?= e(file_url(setting('favicon', setting('logo')))) ?>"><?php endif; ?>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= asset('assets/css/admin.css') ?>" rel="stylesheet">
  <style>:root{--primary: <?= e($theme) ?>;}</style>
</head>
<body>
<div class="admin-wrap">
  <aside class="admin-side offcanvas-lg offcanvas-start" id="adminSide" tabindex="-1">
    <div class="side-brand">
      <i class="bi bi-grid-1x2-fill"></i>
      <div><div class="fw-medium">CMS</div><div class="small opacity-75"><?= e(setting('site_name')) ?></div></div>
      <button type="button" class="btn-close btn-close-white ms-auto d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#adminSide"></button>
    </div>
    <nav class="side-nav">
      <?php foreach ($menu as $key => [$href, $icon, $label]): ?>
        <a href="<?= url('admin/' . $href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><i class="bi <?= $icon ?>"></i> <?= e($label) ?><?php if ($key === 'messages' && $unread): ?> <span class="badge text-bg-warning ms-auto"><?= $unread ?></span><?php endif; ?><?php if ($key === 'news' && $pendingNews): ?> <span class="badge text-bg-danger ms-auto" title="ข่าวรอตรวจ"><?= $pendingNews ?></span><?php endif; ?></a>
      <?php endforeach; ?>
      <hr>
      <a href="<?= url() ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> ดูหน้าเว็บไซต์</a>
      <div class="small text-white-50 px-3 mt-2">CMS v<?= APP_VERSION ?></div>
    </nav>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <button class="btn btn-link text-dark d-lg-none p-0 fs-3" data-bs-toggle="offcanvas" data-bs-target="#adminSide" aria-label="เมนู"><i class="bi bi-list"></i></button>
      <h1 class="h5 m-0"><?= e($title) ?></h1>
      <div class="dropdown ms-auto">
        <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> <span class="d-none d-sm-inline"><?= e($me['fullname']) ?></span></button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><span class="dropdown-item-text small text-muted"><?= role_label($me['role']) ?></span></li>
          <li><a class="dropdown-item" href="<?= url('admin/profile.php') ?>"><i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน</a></li>
          <li>
            <form method="post" action="<?= url('admin/logout.php') ?>"><?= csrf_field() ?>
              <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</button>
            </form>
          </li>
        </ul>
      </div>
    </header>
    <div class="admin-content">
      <?= flashes() ?>
<?php
}

function admin_footer(string $extra = ''): void
{
    ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // ยืนยันก่อนลบ
  document.addEventListener('submit', (e) => {
    const msg = e.target.dataset.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
  });
  // แสดงตัวอย่างรูปก่อนอัปโหลด
  document.querySelectorAll('input[type=file][data-preview]').forEach((inp) => {
    inp.addEventListener('change', () => {
      const img = document.querySelector(inp.dataset.preview);
      if (img && inp.files[0]) { img.src = URL.createObjectURL(inp.files[0]); img.classList.remove('d-none'); }
    });
  });
</script>
<?= $extra ?>
</body>
</html>
<?php
}

/** ปุ่มลบแบบ POST พร้อม CSRF */
function delete_button(string $action, int $id, string $confirm = 'ยืนยันการลบรายการนี้?'): string
{
    return '<form method="post" action="' . e($action) . '" class="d-inline" data-confirm="' . e($confirm) . '">'
        . csrf_field() . '<input type="hidden" name="delete" value="' . $id . '">'
        . '<button class="btn btn-sm btn-outline-danger" title="ลบ"><i class="bi bi-trash"></i></button></form>';
}

/** โหลด TinyMCE สำหรับ textarea.rich */
function rich_editor_script(): string
{
    // ค่าที่ใส่ใน JS: url อัปโหลด, CSRF, CSS หน้าเว็บ (ให้หน้าตาในตัวแก้ไขเหมือนหน้าเว็บจริง)
    $vars = [
        '{UPLOAD_URL}' => url('admin/upload_image.php'),
        '{CSRF}' => csrf_token(),
        '{SITE_CSS}' => url('assets/css/style.css'),
        '{PRIMARY}' => setting('theme_color', '#1e3a8a'),
        '{ACCENT}' => setting('accent_color', '#d71920'),
    ];
    return strtr(<<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
tinymce.init({
  selector: "textarea.rich",
  language: "th_TH",
  language_url: "https://cdn.jsdelivr.net/npm/tinymce-i18n@26.9.28/langs6/th_TH.js",
  height: 560, min_height: 360, branding: false, promotion: false,
  menubar: "edit insert view format table tools",
  plugins: "advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table wordcount emoticons nonbreaking accordion quickbars autosave",
  toolbar: [
    "undo redo | blocks fontfamily fontsize lineheight | bold italic underline strikethrough subscript superscript | forecolor backcolor removeformat",
    "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | blockquote hr | link image media table accordion | infobox twocol | charmap emoticons insertdatetime | searchreplace visualblocks fullscreen preview code"
  ],
  toolbar_sticky: true, toolbar_sticky_offset: 60,
  quickbars_selection_toolbar: "bold italic underline forecolor | quicklink h3 h4 blockquote",
  quickbars_insert_toolbar: "quickimage quicktable hr",
  contextmenu: "link image table",
  block_formats: "ย่อหน้า=p; หัวข้อใหญ่ (H2)=h2; หัวข้อ (H3)=h3; หัวข้อย่อย (H4)=h4; หัวข้อเล็ก (H5)=h5; ข้อความจัดรูปแบบไว้ (Pre)=pre",
  font_family_formats: "Sarabun=Sarabun,sans-serif; Kanit=Kanit,sans-serif; TH Sarabun New=TH Sarabun New,Sarabun,sans-serif; Tahoma=tahoma,sans-serif; Arial=arial,helvetica,sans-serif; Times New Roman=times new roman,times,serif",
  font_size_formats: "12px 14px 16px 18px 20px 24px 28px 32px 40px",
  line_height_formats: "1 1.2 1.5 1.8 2 2.5",
  color_map: [
    "{PRIMARY}", "น้ำเงินวิทยาลัย", "{ACCENT}", "แดงวิทยาลัย",
    "000000", "ดำ", "444444", "เทาเข้ม", "888888", "เทา", "FFFFFF", "ขาว",
    "198754", "เขียว", "0D6EFD", "ฟ้า", "FD7E14", "ส้ม", "D4A017", "ทอง",
    "FFF3CD", "ไฮไลต์เหลือง", "D1E7DD", "ไฮไลต์เขียว", "CFE2FF", "ไฮไลต์ฟ้า", "F8D7DA", "ไฮไลต์แดง"
  ],
  insertdatetime_formats: ["%d/%m/%Y", "%H:%M น.", "%d/%m/%Y %H:%M น."],
  image_advtab: true, image_caption: true, image_title: true,
  automatic_uploads: true, paste_data_images: true,
  table_default_attributes: { class: "table table-bordered" },
  table_class_list: [{ title: "ตารางมีเส้น", value: "table table-bordered" }, { title: "ตารางลายสลับ", value: "table table-striped" }, { title: "ไม่มีเส้น", value: "table" }],
  style_formats_merge: true,
  style_formats: [
    { title: "ข้อความเด่น (Lead)", selector: "p", classes: "lead" },
    { title: "ข้อความสีน้ำเงิน", inline: "span", styles: { color: "{PRIMARY}" } },
    { title: "ข้อความสีแดง", inline: "span", styles: { color: "{ACCENT}" } },
    { title: "ปุ่มลิงก์", selector: "a", classes: "btn btn-primary" },
    { title: "รูปมุมมน + เงา", selector: "img", classes: "rounded shadow-sm" }
  ],
  autosave_ask_before_unload: true, autosave_interval: "20s", autosave_retention: "60m",
  content_css: ["https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css", "https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Sarabun:wght@400;600&display=swap"],
  content_style: ":root{--primary:{PRIMARY};--accent:{ACCENT}} body{font-family:Sarabun,Kanit,sans-serif;font-size:16px;line-height:1.85;padding:1rem} h2,h3,h4,h5{font-family:Kanit,sans-serif;color:{PRIMARY}} img{max-width:100%;height:auto} blockquote{border-left:4px solid {ACCENT};padding-left:1rem;color:#555}",
  convert_urls: false,
  setup: (editor) => {
    // แทรกกล่องข้อความแบบต่าง ๆ
    editor.ui.registry.addMenuButton("infobox", {
      icon: "info", tooltip: "แทรกกล่องข้อความ",
      fetch: (cb) => cb([
        ["กล่องข้อมูล (ฟ้า)", "info", "ข้อมูลเพิ่มเติม"],
        ["กล่องสำคัญ (เหลือง)", "warning", "สิ่งที่ควรทราบ"],
        ["กล่องสำเร็จ (เขียว)", "success", "ประกาศผล"],
        ["กล่องแจ้งเตือน (แดง)", "danger", "ข้อควรระวัง"]
      ].map(([text, type, title]) => ({
        type: "menuitem", text,
        onAction: () => editor.insertContent(`<div class="alert alert-${type}"><strong>${title}</strong><p class="mb-0">พิมพ์ข้อความที่นี่</p></div><p></p>`)
      })))
    });
    // แทรกเลย์เอาต์ 2 คอลัมน์ (ข้อความ + รูป)
    editor.ui.registry.addButton("twocol", {
      icon: "table-insert-column-after", tooltip: "แทรกเลย์เอาต์ 2 คอลัมน์",
      onAction: () => editor.insertContent('<div class="row g-3"><div class="col-md-6"><p>คอลัมน์ซ้าย</p></div><div class="col-md-6"><p>คอลัมน์ขวา</p></div></div><p></p>')
    });
  },
  images_upload_handler: (blob) => {
    const fd = new FormData();
    fd.append("file", blob.blob(), blob.filename());
    fd.append("csrf", "{CSRF}");
    return fetch("{UPLOAD_URL}", { method: "POST", body: fd })
      .then((r) => r.json())
      .then((j) => j.location || Promise.reject(j.error || "อัปโหลดไม่สำเร็จ"));
  }
});
</script>
HTML, array_map('e', $vars));
}
