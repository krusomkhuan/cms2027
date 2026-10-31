<?php
// รับรูปที่แทรกในตัวแก้ไขเนื้อหา (TinyMCE)
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

function upload_error(int $code, string $msg): void
{
    http_response_code($code);
    exit(json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    upload_error(405, 'ไม่รองรับคำขอนี้');
}
// ไฟล์ใหญ่เกิน post_max_size ของเซิร์ฟเวอร์ PHP จะทิ้งข้อมูลทั้งหมด (รวม csrf) จึงต้องตรวจก่อน
if (!$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    upload_error(413, 'รูปใหญ่เกินกว่าที่เซิร์ฟเวอร์รับได้ (สูงสุด ' . ini_get('post_max_size') . ') — กรุณาย่อรูปก่อนอัปโหลด');
}
if (!current_user()) {
    upload_error(403, 'หมดเวลาการเข้าสู่ระบบ — กรุณาเข้าสู่ระบบใหม่ในแท็บอื่น แล้วลองอัปโหลดอีกครั้ง');
}
if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
    upload_error(403, 'รหัสยืนยันหน้าเว็บหมดอายุ — กรุณาบันทึกร่าง แล้วรีเฟรชหน้านี้');
}
try {
    $path = upload_file('file', 'content', IMG_EXT, 5);
    echo json_encode(['location' => $path ? url($path) : null, 'error' => $path ? null : 'ไม่พบไฟล์'], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $ex) {
    upload_error(400, $ex->getMessage());
}
