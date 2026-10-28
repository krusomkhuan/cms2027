<?php
// รับรูปที่แทรกในตัวแก้ไขเนื้อหา (TinyMCE)
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');
if (!current_user() || $_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit(json_encode(['error' => 'ไม่มีสิทธิ์']));
}
try {
    $path = upload_file('file', 'content', IMG_EXT, 5);
    echo json_encode(['location' => $path ? url($path) : null, 'error' => $path ? null : 'ไม่พบไฟล์'], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $ex) {
    http_response_code(400);
    echo json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE);
}
