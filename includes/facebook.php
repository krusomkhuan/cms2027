<?php
// ดึงโพสต์ล่าสุดจากเพจ Facebook ผ่าน Graph API (ต้องตั้งค่า Page Access Token ที่หลังบ้าน) เก็บแคช 30 นาที

const FB_CACHE_SECONDS = 1800;

function fb_configured(): bool
{
    return setting('fb_page_token') !== '';
}

/** เรียก Graph API คืน [ข้อมูล JSON, ข้อความ error หรือ null] */
function fb_graph(string $path, array $params): array
{
    $params['access_token'] = setting('fb_page_token');
    $ctx = stream_context_create(['http' => ['timeout' => 6, 'ignore_errors' => true, 'header' => "User-Agent: cms2027\r\n"]]);
    $body = @file_get_contents('https://graph.facebook.com/' . ltrim($path, '/') . '?' . http_build_query($params), false, $ctx);
    if ($body === false) {
        return [null, 'เชื่อมต่อ Facebook ไม่ได้ (ตรวจสอบอินเทอร์เน็ตของเซิร์ฟเวอร์)'];
    }
    $json = json_decode($body, true);
    if (!is_array($json) || isset($json['error'])) {
        return [null, $json['error']['message'] ?? 'Facebook ตอบกลับไม่ถูกต้อง'];
    }
    return [$json, null];
}

/** ดึงโพสต์ใหม่จาก Facebook แล้วบันทึกแคช คืน [จำนวนโพสต์, error หรือ null] */
function fb_refresh_posts(): array
{
    $page = setting('fb_page_id', 'me');
    [$json, $err] = fb_graph($page . '/posts', ['fields' => 'message,full_picture,permalink_url,created_time', 'limit' => 6]);
    if ($err) {
        save_setting('fb_feed_error', date('Y-m-d H:i') . ' ' . $err);
        save_setting('fb_feed_checked', (string) time()); // ไม่ลองซ้ำทุกครั้งที่มีคนเปิดหน้าเว็บ
        return [0, $err];
    }
    $posts = [];
    foreach ($json['data'] ?? [] as $p) {
        if (empty($p['permalink_url'])) {
            continue;
        }
        $posts[] = [
            'message' => mb_substr((string) ($p['message'] ?? ''), 0, 500),
            'image' => (string) ($p['full_picture'] ?? ''),
            'url' => (string) $p['permalink_url'],
            'time' => date('Y-m-d H:i:s', strtotime((string) ($p['created_time'] ?? 'now'))),
        ];
    }
    save_setting('fb_feed_cache', json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    save_setting('fb_feed_checked', (string) time());
    save_setting('fb_feed_error', '');
    return [count($posts), null];
}

/** ลิงก์โพสต์แนะนำที่ผู้ดูแลเลือกเอง (บรรทัดละ 1 ลิงก์ สูงสุด 5) — แสดงด้วยปลั๊กอินโพสต์ของ Facebook ไม่ต้องใช้ token */
function fb_featured(): array
{
    $urls = [];
    foreach (preg_split('/\R/', setting('fb_featured_posts')) as $line) {
        $line = trim($line);
        if (preg_match('#^https://(www\.|m\.|web\.)?facebook\.com/[^\s"\'<>]+$#i', $line)) {
            $urls[] = preg_replace('#^https://(m\.|web\.)#i', 'https://www.', $line);
        }
    }
    return array_slice(array_values(array_unique($urls)), 0, 5);
}

/** URL ปลั๊กอินแสดงโพสต์เดียวของ Facebook */
function fb_post_embed_url(string $postUrl): string
{
    return 'https://www.facebook.com/plugins/post.php?href=' . urlencode($postUrl) . '&width=500&show_text=true&locale=' . (lang() === 'en' ? 'en_US' : 'th_TH');
}

/** โพสต์ล่าสุด (จากแคช ดึงใหม่เมื่อเกิน 30 นาที) — คืน [] ถ้ายังไม่ได้ตั้งค่า */
function fb_posts(int $limit = 4): array
{
    if (!fb_configured()) {
        return [];
    }
    if (time() - (int) setting('fb_feed_checked', '0') > FB_CACHE_SECONDS) {
        fb_refresh_posts();
    }
    $posts = json_decode(setting('fb_feed_cache', '[]'), true);
    return array_slice(is_array($posts) ? $posts : [], 0, $limit);
}
