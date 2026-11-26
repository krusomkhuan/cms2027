<?php
// วิดีโอล่าสุดจากช่อง YouTube ของวิทยาลัย ผ่าน RSS สาธารณะของ YouTube (ไม่ต้องใช้ API key) เก็บแคช 30 นาที

const YT_CACHE_SECONDS = 1800;

function yt_http(string $url): ?string
{
    $ctx = stream_context_create(['http' => ['timeout' => 6, 'ignore_errors' => true,
        'header' => "User-Agent: Mozilla/5.0 (cms2027)\r\nAccept-Language: th,en\r\nCookie: CONSENT=YES+1\r\n"]]);
    $body = @file_get_contents($url, false, $ctx);
    return $body === false ? null : $body;
}

/** ช่อง R-Channel (สถานีโทรทัศน์เพื่อการศึกษาและพัฒนาอาชีพ) — แสดงใต้ปฏิทินหน้าแรก */
const RCHANNEL_DEFAULT_URL = 'https://www.youtube.com/@rchannelevent7917';

/**
 * ข้อมูลช่อง ['id', 'title', 'avatar', 'url'] — หา channel ID ครั้งเดียวแล้วจำไว้
 * $url = null ใช้ลิงก์ในการตั้งค่า (youtube_url), $p = คำนำหน้าคีย์ที่ใช้เก็บแคชของช่องนั้น
 */
function yt_channel(?string $url = null, string $p = 'yt_'): ?array
{
    $url ??= setting('youtube_url');
    if (!preg_match('#^https://(www\.|m\.)?youtube\.com/#i', $url)) {
        return null;
    }
    if (setting($p . 'channel_for') !== $url) {
        // ลิงก์ช่องเปลี่ยน → หา channel ID / ชื่อ / รูปโปรไฟล์ใหม่
        $id = preg_match('#/channel/(UC[\w-]{22})#', $url, $m) ? $m[1] : '';
        $html = yt_http($url) ?? '';
        if (!$id && (preg_match('/"externalId":"(UC[\w-]{22})"/', $html, $m) || preg_match('/"channelId":"(UC[\w-]{22})"/', $html, $m))) {
            $id = $m[1];
        }
        if (!$id) {
            return null; // ลองใหม่ครั้งถัดไป
        }
        preg_match('/<meta property="og:title" content="([^"]*)"/', $html, $t);
        preg_match('/<meta property="og:image" content="([^"]*)"/', $html, $img);
        save_setting($p . 'channel_id', $id);
        save_setting($p . 'channel_title', trim(html_entity_decode($t[1] ?? '', ENT_QUOTES, 'UTF-8')));
        save_setting($p . 'channel_avatar', html_entity_decode($img[1] ?? '', ENT_QUOTES, 'UTF-8'));
        save_setting($p . 'channel_for', $url);
        save_setting($p . 'feed_checked', '0');
    }
    return ['id' => setting($p . 'channel_id'), 'title' => setting($p . 'channel_title'), 'avatar' => setting($p . 'channel_avatar'), 'url' => $url];
}

/** วิดีโอล่าสุดของช่อง [['youtube_id','title','time'], ...] */
function yt_videos(int $limit = 4, ?string $url = null, string $p = 'yt_'): array
{
    $ch = yt_channel($url, $p);
    if (!$ch) {
        return [];
    }
    if (time() - (int) setting($p . 'feed_checked', '0') > YT_CACHE_SECONDS) {
        save_setting($p . 'feed_checked', (string) time()); // ไม่ลองซ้ำทุกคำขอถ้า YouTube ไม่ตอบ
        $xml = yt_http('https://www.youtube.com/feeds/videos.xml?channel_id=' . $ch['id']);
        $feed = $xml ? @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET) : false;
        if ($feed) {
            $videos = [];
            foreach ($feed->entry as $e) {
                $vid = (string) $e->children('http://www.youtube.com/xml/schemas/2015')->videoId;
                if (preg_match('/^[\w-]{11}$/', $vid)) {
                    $videos[] = ['youtube_id' => $vid, 'title' => trim((string) $e->title), 'time' => date('Y-m-d H:i:s', strtotime((string) $e->published))];
                }
            }
            save_setting($p . 'feed_cache', json_encode($videos, JSON_UNESCAPED_UNICODE));
        }
    }
    $videos = json_decode(setting($p . 'feed_cache', '[]'), true);
    return array_slice(is_array($videos) ? $videos : [], 0, $limit);
}
