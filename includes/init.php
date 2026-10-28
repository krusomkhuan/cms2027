<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('cms2027');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// เวอร์ชันของระบบ (Semantic Versioning: MAJOR.MINOR.PATCH) — ดูรายละเอียดใน CHANGELOG.md
const APP_VERSION = '1.4.0';

const IMG_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const DOC_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png'];
const STUDENT_LEVELS = ['ปวช.' => 3, 'ปวส.' => 2];

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** URL ไฟล์ CSS/JS พร้อมเลขเวอร์ชัน เพื่อให้เบราว์เซอร์โหลดไฟล์ใหม่เมื่อไฟล์เปลี่ยน */
function asset(string $path): string
{
    $file = ROOT_DIR . '/' . ltrim($path, '/');
    return url($path) . '?v=' . APP_VERSION . (is_file($file) ? '.' . filemtime($file) : '');
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') || str_starts_with($path, '/') ? $path : url($path)));
    exit;
}

/** ค่าตั้งค่าทั้งหมด โหลดครั้งเดียวต่อการเรียกหน้า (คืนแบบอ้างอิงเพื่อให้ save_setting อัปเดตได้) */
function &settings_cache(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = q('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $cache = &settings_cache();
    return isset($cache[$key]) && $cache[$key] !== '' ? $cache[$key] : $default;
}

function save_setting(string $key, string $value): void
{
    q('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [$key, $value]);
    $cache = &settings_cache();
    $cache[$key] = $value;
}

/* ---------- ความปลอดภัย ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('CSRF token ไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง');
    }
}

function flash(string $msg, string $type = 'success'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flashes(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        $html .= '<div class="alert alert-' . e($type) . ' alert-dismissible fade show">' . e($msg)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/* ---------- ภาษา (หน้าบ้านกำหนดผ่าน set_lang ใน front.php, หลังบ้านเป็นภาษาไทยเสมอ) ---------- */

function lang(): string
{
    return $GLOBALS['LANG'] ?? 'th';
}

/** แปลข้อความหน้าเว็บ: ใช้ข้อความภาษาไทยเป็น key */
function t(string $th): string
{
    static $dict = null;
    if (lang() !== 'en') {
        return $th;
    }
    $dict ??= require __DIR__ . '/lang_en.php';
    return $dict[$th] ?? $th;
}

/** เลือกฟิลด์ตามภาษา เช่น tr($news, 'title') → title_en (ถ้ามี) หรือ title */
function tr(array $row, string $field): string
{
    if (lang() === 'en' && !empty($row[$field . '_en'])) {
        return (string) $row[$field . '_en'];
    }
    return (string) ($row[$field] ?? '');
}

/** ค่าตั้งค่าตามภาษา เช่น setting_l('address') → address_en (ถ้ามี) */
function setting_l(string $key, string $default = ''): string
{
    return lang() === 'en' && setting($key . '_en') !== '' ? setting($key . '_en') : setting($key, $default);
}

/* ---------- วันที่ภาษาไทย ---------- */

function thai_date(?string $dt, bool $withTime = false, bool $full = false): string
{
    if (!$dt) {
        return '';
    }
    if (lang() === 'en') {
        $ts = strtotime($dt);
        return date($full ? 'j F Y' : 'j M Y', $ts) . ($withTime ? ' ' . date('H:i', $ts) : '');
    }
    $short = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $long = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $ts = strtotime($dt);
    $s = date('j', $ts) . ' ' . ($full ? $long : $short)[(int) date('n', $ts)] . ' ' . ((int) date('Y', $ts) + 543);
    return $withTime ? $s . ' ' . date('H:i', $ts) . ' น.' : $s;
}

function excerpt(?string $html, int $len = 150): string
{
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8')));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
}

/* ---------- อัปโหลดไฟล์ ---------- */

/**
 * รับไฟล์จาก $_FILES[$field] (หรือไฟล์ลำดับที่ $index สำหรับ input แบบ multiple)
 * คืนค่า path แบบ relative เช่น uploads/news/2026/10/abc.jpg หรือ null ถ้าไม่ได้เลือกไฟล์
 */
function upload_file(string $field, string $subdir, array $allowedExt, int $maxMB = 10, ?int $index = null): ?string
{
    $f = $_FILES[$field] ?? null;
    if (!$f) {
        return null;
    }
    if ($index !== null) {
        $f = array_map(fn($v) => $v[$index], $f);
    }
    if ($f['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('อัปโหลดไฟล์ "' . $f['name'] . '" ไม่สำเร็จ (รหัส ' . $f['error'] . ')');
    }
    if ($f['size'] > $maxMB * 1048576) {
        throw new RuntimeException('ไฟล์ "' . $f['name'] . '" มีขนาดเกิน ' . $maxMB . ' MB');
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('ไม่อนุญาตไฟล์นามสกุล .' . $ext . ' (อนุญาต: ' . implode(', ', $allowedExt) . ')');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $office = ['application/zip', 'application/octet-stream', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2'];
    $mimes = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
        'gif' => ['image/gif'], 'webp' => ['image/webp'], 'pdf' => ['application/pdf'],
        'doc' => array_merge(['application/msword'], $office),
        'xls' => array_merge(['application/vnd.ms-excel'], $office),
        'ppt' => array_merge(['application/vnd.ms-powerpoint'], $office),
        'docx' => array_merge(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'], $office),
        'xlsx' => array_merge(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], $office),
        'pptx' => array_merge(['application/vnd.openxmlformats-officedocument.presentationml.presentation'], $office),
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];
    if (!in_array($mime, $mimes[$ext] ?? [], true)) {
        throw new RuntimeException('เนื้อหาไฟล์ "' . $f['name'] . '" ไม่ตรงกับนามสกุล');
    }
    $rel = 'uploads/' . $subdir . '/' . date('Y/m');
    $dir = ROOT_DIR . '/' . $rel;
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('สร้างโฟลเดอร์อัปโหลดไม่ได้');
    }
    $name = date('dHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('บันทึกไฟล์ไม่สำเร็จ');
    }
    return $rel . '/' . $name;
}

function delete_file(?string $rel): void
{
    if ($rel && str_starts_with($rel, 'uploads/') && !str_contains($rel, '..')) {
        $path = ROOT_DIR . '/' . $rel;
        if (is_file($path)) {
            unlink($path);
        }
    }
}

function file_url(?string $rel, string $fallback = ''): string
{
    return $rel ? url($rel) : $fallback;
}

/* ---------- แบ่งหน้า ---------- */

function pagination(int $total, int $perPage, int $page, array $query = []): string
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) {
        return '';
    }
    $link = function (int $p) use ($query) {
        return '?' . http_build_query(array_merge($query, ['page' => $p]));
    };
    $html = '<nav><ul class="pagination justify-content-center flex-wrap">';
    $html .= '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . e($link(max(1, $page - 1))) . '">&laquo;</a></li>';
    for ($p = max(1, $page - 3); $p <= min($pages, $page + 3); $p++) {
        $html .= '<li class="page-item' . ($p === $page ? ' active' : '') . '"><a class="page-link" href="' . e($link($p)) . '">' . $p . '</a></li>';
    }
    $html .= '<li class="page-item' . ($page >= $pages ? ' disabled' : '') . '"><a class="page-link" href="' . e($link(min($pages, $page + 1))) . '">&raquo;</a></li>';
    return $html . '</ul></nav>';
}

function current_page(): int
{
    return max(1, (int) ($_GET['page'] ?? 1));
}

/* ---------- ผู้ใช้หลังบ้าน ---------- */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = q('SELECT * FROM users WHERE id = ? AND is_active = 1', [$_SESSION['uid']])->fetch() ?: null;
        }
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('admin/login.php');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        exit('เฉพาะผู้ดูแลระบบเท่านั้น');
    }
    return $u;
}

function personnel_name(array $p): string
{
    if (lang() === 'en' && !empty($p['name_en'])) {
        return $p['name_en'];
    }
    return trim(($p['prefix'] ?? '') . $p['firstname'] . ' ' . $p['lastname']);
}

function personnel_types(): array
{
    return ['executive' => t('ผู้บริหาร'), 'teacher' => t('ครูผู้สอน'), 'staff' => t('บุคลากรสนับสนุน')];
}
