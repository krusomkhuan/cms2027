<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/pdpa.php';
require_once __DIR__ . '/facebook.php';

if (!is_file(ROOT_DIR . '/install.lock')) {
    redirect('install.php');
}

// ภาษาหน้าเว็บ: ?lang=th|en แล้วจำไว้ใน session
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'th';
$GLOBALS['LANG'] = $_SESSION['lang'] = in_array($lang, ['th', 'en'], true) ? $lang : 'th';

/** URL หน้าปัจจุบันในอีกภาษา */
function lang_url(string $l): string
{
    return strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query(array_merge($_GET, ['lang' => $l]));
}

const NEWS_SELECT = 'SELECT n.*, c.name AS cat_name, c.name_en AS cat_name_en, c.color AS cat_color FROM news n LEFT JOIN categories c ON c.id = n.category_id';

function news_card(array $n): string
{
    $link = url('news_view.php?id=' . $n['id']);
    $thumb = $n['cover']
        ? '<img src="' . e(file_url($n['cover'])) . '" alt="" loading="lazy">'
        : '<div class="ph"><i class="bi bi-newspaper"></i></div>';
    $badge = $n['cat_name'] ? '<span class="cat-badge" style="background:' . e($n['cat_color']) . '">' . e(tr($n, 'cat_name')) . '</span>' : '';
    $pin = $n['is_pinned'] ? '<i class="bi bi-pin-angle-fill pin" title="' . t('ข่าวปักหมุด') . '"></i> ' : '';
    return '<div class="card news-card">'
        . '<a class="thumb" href="' . $link . '">' . $thumb . '</a>'
        . '<div class="card-body">'
        . '<div class="d-flex justify-content-between align-items-center mb-2">' . $badge
        . '<span class="news-meta"><i class="bi bi-eye"></i> ' . number_format($n['views']) . '</span></div>'
        . '<h3 class="card-title">' . $pin . '<a href="' . $link . '">' . e(tr($n, 'title')) . '</a></h3>'
        . '<div class="news-meta"><i class="bi bi-calendar3"></i> ' . thai_date($n['published_at']) . '</div>'
        . '</div></div>';
}

function person_card(array $p, string $extraClass = ''): string
{
    $photo = $p['photo']
        ? '<img src="' . e(file_url($p['photo'])) . '" alt="' . e(personnel_name($p)) . '" loading="lazy">'
        : '<div class="ph"><i class="bi bi-person-fill"></i></div>';
    $meta = '';
    if (!empty($p['academic_rank'])) {
        $meta .= '<div class="meta">' . e(t($p['academic_rank'])) . '</div>';
    }
    if (!empty($p['dept_name'])) {
        $meta .= '<div class="meta">' . e(tr($p, 'dept_name')) . '</div>';
    }
    if (!empty($p['phone'])) {
        $meta .= '<div class="meta"><i class="bi bi-telephone"></i> ' . e($p['phone']) . '</div>';
    }
    return '<div class="card person-card ' . $extraClass . '"><div class="photo">' . $photo . '</div>'
        . '<div class="card-body p-3"><div class="name">' . e(personnel_name($p)) . '</div>'
        . '<div class="pos">' . e(tr($p, 'position')) . '</div>' . $meta . '</div></div>';
}

function page_banner(string $title, array $crumbs = []): string
{
    $html = '<section class="page-banner"><div class="container"><h1>' . e($title) . '</h1>'
        . '<nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="' . url() . '">' . t('หน้าแรก') . '</a></li>';
    foreach ($crumbs as $label => $href) {
        $html .= $href ? '<li class="breadcrumb-item"><a href="' . e($href) . '">' . e($label) . '</a></li>'
            : '<li class="breadcrumb-item active">' . e($label) . '</li>';
    }
    return $html . '</ol></nav></div></section>';
}

function video_card(array $v, bool $large = false): string
{
    $id = e($v['youtube_id']);
    return '<a href="#" class="video-card' . ($large ? ' large' : '') . '" data-bs-toggle="modal" data-bs-target="#videoModal" data-yt="' . $id . '" data-title="' . e(tr($v, 'title')) . '">'
        . '<span class="vthumb"><img src="https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg" alt="" loading="lazy"><i class="bi bi-play-circle-fill"></i></span>'
        . '<span class="vtitle">' . e(tr($v, 'title')) . '</span></a>';
}

/** หน้าต่างเล่นวิดีโอ YouTube (ใช้คู่กับ video_card) — โหลดวิดีโอเมื่อยินยอมคุกกี้สื่อภายนอกแล้วเท่านั้น */
function video_modal(): string
{
    return '<div class="modal fade" id="videoModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content bg-dark border-0">
  <div class="modal-header border-0 py-2"><h5 class="modal-title text-white fs-6"></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="ratio ratio-16x9 video-holder"></div>
</div></div></div>
<script>
(() => {
  const m = document.getElementById("videoModal"), holder = m.querySelector(".video-holder");
  m.addEventListener("show.bs.modal", (e) => {
    const a = e.relatedTarget;
    m.querySelector(".modal-title").textContent = a.dataset.title;
    window.pdpa.embed(holder, "https://www.youtube-nocookie.com/embed/" + encodeURIComponent(a.dataset.yt) + "?autoplay=1&rel=0", "YouTube", a.dataset.title);
  });
  m.addEventListener("hidden.bs.modal", () => { holder.innerHTML = ""; });
})();
</script>';
}

/**
 * ตำแหน่งวิทยาลัยบน Google Maps: ['embed' => URL ฝังแผนที่, 'open' => เปิดใน Google Maps, 'directions' => นำทาง]
 * ใช้โค้ดฝังจากการตั้งค่า (map_embed) ถ้ามี ไม่เช่นนั้นค้นหาจากชื่อวิทยาลัย (ไม่ต้องใช้ API key)
 */
function college_map(): array
{
    $place = setting('map_query', setting('site_name', 'วิทยาลัยการอาชีพโพนทอง'));
    $custom = setting('map_embed');
    $embed = preg_match('#^https://(www\.google\.com/maps/embed\?|(www\.)?google\.com/maps\?[^"\'<>\s]*output=embed|maps\.google\.com/maps\?[^"\'<>\s]*output=embed)[^"\'<>\s]*$#', $custom)
        ? $custom
        : 'https://www.google.com/maps?q=' . urlencode($place) . '&hl=' . (lang() === 'en' ? 'en' : 'th') . '&z=14&output=embed';
    return [
        'embed' => $embed,
        'open' => 'https://www.google.com/maps/search/?api=1&query=' . urlencode($place),
        'directions' => 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($place),
    ];
}

/** สรุปจำนวนผู้เรียนของภาคเรียนล่าสุด */
function latest_student_term(): ?array
{
    return q('SELECT academic_year, semester FROM student_stats ORDER BY academic_year DESC, semester DESC LIMIT 1')->fetch() ?: null;
}
