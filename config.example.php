<?php
// คัดลอกไฟล์นี้เป็น config.php แล้วแก้ค่าให้ตรงกับเครื่อง/เซิร์ฟเวอร์ (config.php ไม่ถูกเก็บใน git)
define('DB_HOST', 'localhost');
define('DB_NAME', 'cms2027');
define('DB_USER', 'root');
define('DB_PASS', '');

// path ของเว็บไซต์เทียบกับ document root (เช่น '' ถ้าติดตั้งที่ root ของโดเมน)
define('BASE_URL', '/cms2027');

define('ROOT_DIR', __DIR__);
define('UPLOAD_DIR', __DIR__ . '/uploads');

date_default_timezone_set('Asia/Bangkok');
