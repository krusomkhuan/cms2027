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

/** ข้อมูลช่อง ['id', 'title', 'avatar', 'url'] จากลิงก์ในการตั้งค่า (youtube_url) — หา channel ID ครั้งเดียวแล้วจำไว้ */
function yt_channel(): ?array
{
    $url = setting('youtube_url');
    if (!preg_match('#^https://(www\.|m\.)?youtube\.com/#i', $url)) {
        return null;
    }
    if (setting('yt_channel_for') !== $url) {
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
        save_setting('yt_channel_id', $id);
        save_setting('yt_channel_title', trim(html_entity_decode($t[1] ?? '', ENT_QUOTES, 'UTF-8')));
        save_setting('yt_channel_avatar', html_entity_decode($img[1] ?? '', ENT_QUOTES, 'UTF-8'));
        save_setting('yt_channel_for', $url);
        save_setting('yt_feed_checked', '0');
    }
    return ['id' => setting('yt_channel_id'), 'title' => setting('yt_channel_title'), 'avatar' => setting('yt_channel_avatar'), 'url' => $url];
}

/** วิดีโอล่าสุดของช่อง [['youtube_id','title','time'], ...] */
function yt_videos(int $limit = 4): array
{
    $ch = yt_channel();
    if (!$ch) {
        return [];
    }
    if (time() - (int) setting('yt_feed_checked', '0') > YT_CACHE_SECONDS) {
        save_setting('yt_feed_checked', (string) time()); // ไม่ลองซ้ำทุกคำขอถ้า YouTube ไม่ตอบ
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
            save_setting('yt_feed_cache', json_encode($videos, JSON_UNESCAPED_UNICODE));
        }
    }
    $videos = json_decode(setting('yt_feed_cache', '[]'), true);
    return array_slice(is_array($videos) ? $videos : [], 0, $limit);
}
