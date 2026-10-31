<?php
// ต่ออายุ session ของหลังบ้าน (เรียกจาก admin_footer ทุก 5 นาที)
require_once __DIR__ . '/../includes/init.php';

header('Cache-Control: no-store');
http_response_code(current_user() ? 204 : 401);
