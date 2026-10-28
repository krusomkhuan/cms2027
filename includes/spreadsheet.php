<?php
// อ่านไฟล์ตาราง (.xlsx / .csv) เป็นอาร์เรย์ของแถว — ไม่ต้องใช้ส่วนขยาย zip ของ PHP

/** อ่านแถวทั้งหมดจากไฟล์ตามนามสกุล คืน [[cell, cell, ...], ...] */
function read_table_file(string $path, string $ext): array
{
    $rows = match (strtolower($ext)) {
        'xlsx' => read_xlsx_rows($path),
        'csv', 'txt' => read_csv_rows($path),
        default => throw new RuntimeException('รองรับเฉพาะไฟล์ .xlsx หรือ .csv'),
    };
    // ตัดช่องว่างหัวท้าย และทิ้งแถวที่ว่างทั้งแถว
    $out = [];
    foreach ($rows as $r) {
        $r = array_map(fn($v) => trim((string) $v), $r);
        if (implode('', $r) !== '') {
            $out[] = $r;
        }
    }
    return $out;
}

function read_csv_rows(string $path): array
{
    $data = file_get_contents($path);
    if (str_starts_with($data, "\xEF\xBB\xBF")) {
        $data = substr($data, 3);
    }
    // ไฟล์จาก Excel ภาษาไทยรุ่นเก่ามักเป็น TIS-620 / Windows-874
    if (!mb_check_encoding($data, 'UTF-8')) {
        $data = iconv('CP874', 'UTF-8//IGNORE', $data);
    }
    $firstLine = strtok($data, "\n");
    $delims = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
    arsort($delims);
    $delim = array_key_first($delims);

    $fh = fopen('php://temp', 'r+');
    fwrite($fh, $data);
    rewind($fh);
    $rows = [];
    while (($r = fgetcsv($fh, 0, $delim, '"', '')) !== false) {
        $rows[] = $r;
    }
    fclose($fh);
    return $rows;
}

/** อ่านไฟล์ใน zip ทั้งหมด คืน [ชื่อไฟล์ => เนื้อหา] (รองรับ store และ deflate) */
function zip_read_all(string $path): array
{
    $bin = file_get_contents($path);
    $eocd = strrpos($bin, "PK\x05\x06");
    if ($eocd === false) {
        throw new RuntimeException('ไฟล์ .xlsx ไม่ถูกต้องหรือเสียหาย');
    }
    $cd = unpack('vdisk/vcddisk/ventries/vtotal/Vsize/Voffset', substr($bin, $eocd + 4, 16));
    $pos = $cd['offset'];
    $files = [];
    for ($i = 0; $i < $cd['total']; $i++) {
        if (substr($bin, $pos, 4) !== "PK\x01\x02") {
            throw new RuntimeException('โครงสร้างไฟล์ .xlsx ไม่ถูกต้อง');
        }
        $h = unpack('vmethod/Vtime/Vcrc/Vcsize/Vusize/vnlen/velen/vclen/vdisk/viattr/Veattr/Voffset', substr($bin, $pos + 10, 36));
        $name = substr($bin, $pos + 46, $h['nlen']);
        $pos += 46 + $h['nlen'] + $h['elen'] + $h['clen'];

        $lh = unpack('vnlen/velen', substr($bin, $h['offset'] + 26, 4));
        $raw = substr($bin, $h['offset'] + 30 + $lh['nlen'] + $lh['elen'], $h['csize']);
        $files[$name] = match ($h['method']) {
            0 => $raw,
            8 => gzinflate($raw),
            default => null,
        };
    }
    return $files;
}

function read_xlsx_rows(string $path): array
{
    $zip = zip_read_all($path);
    $xml = fn(string $name) => isset($zip[$name]) && $zip[$name] !== null
        ? simplexml_load_string($zip[$name], 'SimpleXMLElement', LIBXML_NONET) : null;

    // หาแผ่นงานแรกจาก workbook.xml + rels
    $sheetPath = 'xl/worksheets/sheet1.xml';
    $wb = $xml('xl/workbook.xml');
    $rels = $xml('xl/_rels/workbook.xml.rels');
    if ($wb && $rels && isset($wb->sheets->sheet[0])) {
        $rid = (string) $wb->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        foreach ($rels->Relationship as $rel) {
            if ((string) $rel['Id'] === $rid) {
                $target = ltrim((string) $rel['Target'], '/');
                $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            }
        }
    }

    $shared = [];
    if ($ss = $xml('xl/sharedStrings.xml')) {
        foreach ($ss->si as $si) {
            // ข้อความธรรมดา <t> หรือข้อความหลายรูปแบบ <r><t>
            $shared[] = isset($si->t) ? (string) $si->t : implode('', array_map('strval', $si->xpath('.//*[local-name()="t"]')));
        }
    }

    $sheet = $xml($sheetPath);
    if (!$sheet) {
        throw new RuntimeException('ไม่พบแผ่นงานในไฟล์ .xlsx');
    }
    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $r = [];
        foreach ($row->c as $c) {
            preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
            $col = 0;
            foreach (str_split($m[1] ?? 'A') as $ch) {
                $col = $col * 26 + (ord($ch) - 64);
            }
            $t = (string) $c['t'];
            $v = match ($t) {
                's' => $shared[(int) $c->v] ?? '',
                'inlineStr' => implode('', array_map('strval', $c->xpath('.//*[local-name()="t"]'))),
                'b' => ((string) $c->v) === '1' ? 'TRUE' : 'FALSE',
                default => (string) $c->v,
            };
            // ตัวเลขเต็มที่ Excel เก็บเป็น 12.0 หรือเบอร์โทรที่เป็นตัวเลข
            if ($t === '' && is_numeric($v) && floor((float) $v) == (float) $v && abs((float) $v) < 1e15) {
                $v = (string) (int) $v;
            }
            $r[$col - 1] = $v;
        }
        if ($r) {
            $max = max(array_keys($r));
            $rows[] = array_replace(array_fill(0, $max + 1, ''), $r);
        }
    }
    return $rows;
}
