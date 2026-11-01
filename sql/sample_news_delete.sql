-- ลบข่าวตัวอย่างทั้งหมด (sample_news.sql และ sample_news_2.sql) — เฉพาะข่าวที่มีหมายเหตุ "ข่าวตัวอย่างสำหรับทดสอบระบบ" และใช้ภาพปกใน uploads/sample
SET NAMES utf8mb4;
DELETE FROM news WHERE content LIKE '%ข่าวตัวอย่างสำหรับทดสอบระบบ%' AND cover LIKE 'uploads/sample/sample-news-%';
