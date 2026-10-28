<?php
// บันทึกการให้ความยินยอมคุกกี้ (เรียกจาก main.js) — เก็บ IP แบบ hash ไม่เก็บ IP จริง
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/pdpa.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('{"ok":false}');
}

// จำกัด 30 ครั้ง / ชั่วโมง ต่อ session
$recent = array_filter($_SESSION['consent_times'] ?? [], fn($ts) => $ts > time() - 3600);
$id = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['id'] ?? ''));
if (count($recent) >= 30 || strlen($id) < 8) {
    http_response_code(429);
    exit('{"ok":false}');
}
$recent[] = time();
$_SESSION['consent_times'] = array_values($recent);

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
q('INSERT INTO consent_logs (consent_id, analytics, media, policy_version, ip_hash, user_agent) VALUES (?, ?, ?, ?, ?, ?)', [
    substr($id, 0, 16),
    ($_POST['a'] ?? '') === '1' ? 1 : 0,
    ($_POST['m'] ?? '') === '1' ? 1 : 0,
    setting('pdpa_policy_version', '1.0'),
    $ip !== '' ? hash('sha256', pdpa_salt() . $ip) : null,
    mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
]);
echo '{"ok":true}';
