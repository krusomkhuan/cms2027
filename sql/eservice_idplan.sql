-- เพิ่มปุ่มบริการออนไลน์ "ระบบไอดีแพลน IDPlan" ต่อท้ายรายการ E-Service (นำเข้าซ้ำได้)
-- ลิงก์ยังไม่ระบุ (#) — แก้ได้ที่หลังบ้าน เมนู ลิงก์ / E-Service
SET NAMES utf8mb4;
INSERT INTO links (title, title_en, url, icon, link_group, sort_order)
  SELECT 'ระบบไอดีแพลน IDPlan', 'IDPlan Online', '#', 'bi-person-lines-fill', 'eservice',
         (SELECT COALESCE(MAX(l.sort_order), 0) + 1 FROM links l WHERE l.link_group = 'eservice')
  FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM links WHERE title LIKE '%IDPlan%');
