-- ปรับรายชื่อแผนกวิชาให้ตรงกับเมนู "เว็บไซต์แผนกวิชา" (12 แผนก ตามลำดับที่วิทยาลัยกำหนด)
-- จับคู่ด้วยชื่อ นำเข้าซ้ำได้ ไม่กระทบข้อมูลบุคลากรและจำนวนผู้เรียนเดิม
SET NAMES utf8mb4;

-- เปลี่ยนชื่อ แผนกวิชาช่างไฟฟ้า → แผนกวิชาช่างไฟฟ้ากำลัง
UPDATE departments SET name = 'แผนกวิชาช่างไฟฟ้ากำลัง', name_en = 'Electrical Power Department'
  WHERE name = 'แผนกวิชาช่างไฟฟ้า' AND type = 'academic';

-- เพิ่มแผนกวิชาที่ยังไม่มี
INSERT INTO departments (name, name_en, type, description, sort_order)
  SELECT 'แผนกวิชาเทคนิคพื้นฐาน', 'Basic Technical Department', 'academic', 'สอนรายวิชาพื้นฐานช่างอุตสาหกรรม', 11 FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM departments WHERE name = 'แผนกวิชาเทคนิคพื้นฐาน');
INSERT INTO departments (name, name_en, type, description, sort_order)
  SELECT 'แผนกวิชาสามัญสัมพันธ์', 'General Education Department', 'academic', 'สอนรายวิชาหมวดสมรรถนะแกนกลาง (ภาษาไทย ภาษาต่างประเทศ วิทยาศาสตร์ คณิตศาสตร์ สังคมศึกษา สุขศึกษาและพลศึกษา)', 14 FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM departments WHERE name = 'แผนกวิชาสามัญสัมพันธ์');

-- เรียงลำดับตามเมนู
UPDATE departments SET sort_order = CASE name
    WHEN 'แผนกวิชาเทคนิคพื้นฐาน' THEN 11
    WHEN 'แผนกวิชาช่างก่อสร้าง' THEN 12
    WHEN 'แผนกวิชาช่างยนต์' THEN 13
    WHEN 'แผนกวิชาสามัญสัมพันธ์' THEN 14
    WHEN 'แผนกวิชาการโรงแรม' THEN 15
    WHEN 'แผนกวิชาช่างกลโรงงาน' THEN 16
    WHEN 'แผนกวิชาช่างเชื่อมโลหะ' THEN 17
    WHEN 'แผนกวิชาช่างไฟฟ้ากำลัง' THEN 18
    WHEN 'แผนกวิชาช่างอิเล็กทรอนิกส์' THEN 19
    WHEN 'แผนกวิชาการบัญชี' THEN 20
    WHEN 'แผนกวิชาการตลาด' THEN 21
    WHEN 'แผนกวิชาเทคโนโลยีธุรกิจดิจิทัล' THEN 22
    ELSE sort_order END
  WHERE type = 'academic';
