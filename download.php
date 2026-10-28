<?php
require_once __DIR__ . '/includes/init.php';

$d = q('SELECT * FROM downloads WHERE id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$d) {
    http_response_code(404);
    exit('ไม่พบไฟล์');
}
q('UPDATE downloads SET hits = hits + 1 WHERE id = ?', [$d['id']]);

if ($d['file']) {
    redirect(file_url($d['file']));
}
// อนุญาตเฉพาะลิงก์ http(s) เพื่อกัน javascript: URL
if ($d['url'] && preg_match('#^https?://#i', $d['url'])) {
    redirect($d['url']);
}
redirect('downloads.php');
