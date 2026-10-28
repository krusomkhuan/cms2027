<?php
// PDPA: อ่านความยินยอมคุกกี้ และแสดงเนื้อหาจากภายนอกเฉพาะเมื่อได้รับความยินยอม

/** ความยินยอมของผู้เข้าชม ['analytics' => bool, 'media' => bool] หรือ null ถ้ายังไม่เลือก / นโยบายเปลี่ยนเวอร์ชัน */
function pdpa_consent(): ?array
{
    static $consent = false;
    if ($consent === false) {
        $consent = null;
        $raw = json_decode((string) ($_COOKIE['pdpa_consent'] ?? ''), true);
        if (is_array($raw) && (string) ($raw['v'] ?? '') === setting('pdpa_policy_version', '1.0')) {
            $consent = ['analytics' => !empty($raw['a']), 'media' => !empty($raw['m'])];
        }
    }
    return $consent;
}

function pdpa_enabled(): bool
{
    return setting('pdpa_banner', '1') === '1';
}

/** ผู้เข้าชมยินยอมคุกกี้ประเภทนี้หรือไม่ (ถ้าปิดระบบแจ้งคุกกี้ ถือว่ายินยอม) */
function has_consent(string $category): bool
{
    return !pdpa_enabled() || (pdpa_consent()[$category] ?? false);
}

/**
 * iframe จากผู้ให้บริการภายนอก (Facebook / YouTube / Google Maps)
 * ถ้ายังไม่ยินยอม "สื่อภายนอก" จะแสดงกล่องขออนุญาตแทน แล้ว main.js จะสร้าง iframe เมื่อผู้ใช้ยินยอม
 */
function media_embed(string $src, string $provider, string $title): string
{
    if (has_consent('media')) {
        return '<iframe src="' . e($src) . '" title="' . e($title) . '" loading="lazy" allow="encrypted-media; picture-in-picture"'
            . ' referrerpolicy="no-referrer-when-downgrade" style="border:0;width:100%;height:100%"></iframe>';
    }
    return '<div class="consent-embed" data-src="' . e($src) . '" data-title="' . e($title) . '">'
        . '<div class="consent-ph"><i class="bi bi-shield-lock"></i>'
        . '<div>' . e(sprintf(t('เนื้อหานี้แสดงจาก %s ซึ่งอาจบันทึกคุกกี้ในอุปกรณ์ของท่าน'), $provider)) . '</div>'
        . '<button type="button" class="btn btn-sm btn-primary" data-consent-media>' . t('อนุญาตสื่อภายนอกและแสดงเนื้อหา') . '</button>'
        . '<a href="' . url('page.php?slug=cookie-policy') . '" class="small">' . t('นโยบายคุกกี้') . '</a></div></div>';
}

/** รหัสลับสำหรับ hash หมายเลข IP ในบันทึกความยินยอม (สร้างครั้งแรกอัตโนมัติ) */
function pdpa_salt(): string
{
    $salt = setting('pdpa_salt');
    if ($salt === '') {
        $salt = bin2hex(random_bytes(16));
        save_setting('pdpa_salt', $salt);
    }
    return $salt;
}

const PDPA_REQUEST_TYPES = [
    'access' => ['ขอเข้าถึงและขอรับสำเนาข้อมูล', 'Access and obtain a copy'],
    'rectify' => ['ขอแก้ไขข้อมูลให้ถูกต้อง', 'Rectification'],
    'erase' => ['ขอลบหรือทำให้ไม่สามารถระบุตัวตนได้', 'Erasure / anonymisation'],
    'restrict' => ['ขอระงับการใช้ข้อมูล', 'Restriction of use'],
    'object' => ['คัดค้านการเก็บ ใช้ หรือเปิดเผยข้อมูล', 'Objection to processing'],
    'portability' => ['ขอให้โอนย้ายข้อมูล', 'Data portability'],
    'withdraw' => ['ถอนความยินยอม', 'Withdraw consent'],
    'other' => ['อื่น ๆ', 'Other'],
];
const PDPA_RELATIONS = [
    'student' => ['นักเรียน / นักศึกษา', 'Student'],
    'parent' => ['ผู้ปกครอง', 'Parent / guardian'],
    'staff' => ['ครู / บุคลากร', 'Teacher / staff'],
    'alumni' => ['ศิษย์เก่า', 'Alumni'],
    'public' => ['บุคคลทั่วไป', 'General public'],
];
const PDPA_STATUS = [
    'received' => ['รับเรื่องแล้ว', 'text-bg-secondary'],
    'in_progress' => ['กำลังดำเนินการ', 'text-bg-warning'],
    'completed' => ['ดำเนินการเสร็จสิ้น', 'text-bg-success'],
    'rejected' => ['ปฏิเสธคำร้อง', 'text-bg-danger'],
];

/** ข้อความตามภาษาจากรายการ [ไทย, อังกฤษ] */
function pdpa_label(array $pair): string
{
    return lang() === 'en' ? $pair[1] : $pair[0];
}
