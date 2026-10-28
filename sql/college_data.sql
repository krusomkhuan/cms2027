-- ข้อมูลจริงของวิทยาลัยการอาชีพโพนทอง (รันซ้ำได้)

-- ---------- ตั้งค่าเว็บไซต์ ----------
INSERT INTO settings (skey, svalue) VALUES
('site_name', 'วิทยาลัยการอาชีพโพนทอง'),
('site_name_en', 'Phonthong Industrial and Community Education College'),
('slogan', 'สามัคคี มีวินัย ใส่ใจบริการ'),
('slogan_en', 'Unity, Discipline, Service-Minded'),
('address', 'เลขที่ 60 หมู่ที่ 11 ตำบลแวง อำเภอโพนทอง จังหวัดร้อยเอ็ด 45110'),
('address_en', '60 Moo 11, Waeng Subdistrict, Phon Thong District, Roi Et 45110, Thailand'),
('phone', '043-571144'),
('email', 'sarabun@pticec.ac.th'),
('facebook', 'https://www.facebook.com/pr.phonthong'),
('director_name', 'ว่าที่ร้อยโทสันติสุข โมฬา'),
('director_position', 'ผู้อำนวยการวิทยาลัยการอาชีพโพนทอง'),
('director_position_en', 'Director, Phonthong Industrial and Community Education College'),
('director_message', 'วิทยาลัยการอาชีพโพนทอง มุ่งเน้นการผลิต และพัฒนากำลังคน อาชีวศึกษาถึงระดับปริญญา ด้วยเทคโนโลยีที่ทันสมัย ได้มาตรฐานอาชีวศึกษา ตอบสนองต่อความต้องการของตลาดแรงงาน'),
('director_message_en', 'Phonthong Industrial and Community Education College is committed to producing and developing vocational manpower up to degree level through modern technology, meeting vocational education standards and responding to the needs of the labour market.'),
('theme_color', '#1e3a8a'),
('accent_color', '#d71920')
ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);

-- ---------- หน้าข้อมูลสถานศึกษา ----------
UPDATE pages SET title = 'ประวัติสถานศึกษา', title_en = 'History' WHERE slug = 'history';
UPDATE pages SET title = 'โครงสร้างการบริหาร', title_en = 'Organization Structure' WHERE slug = 'structure';
UPDATE pages SET title = 'ติดต่อเรา', title_en = 'Contact Us', content = '', content_en = NULL WHERE slug = 'contact';

UPDATE pages SET title = 'วิสัยทัศน์ พันธกิจ เป้าประสงค์', title_en = 'Vision, Mission and Goals', content =
'<h4>วิสัยทัศน์</h4>
<p>วิทยาลัยการอาชีพโพนทอง มุ่งเน้นการผลิต และพัฒนากำลังคน อาชีวศึกษาถึงระดับปริญญา ด้วยเทคโนโลยีที่ทันสมัย ได้มาตรฐานอาชีวศึกษา ตอบสนองต่อความต้องการของตลาดแรงงาน</p>
<h4>พันธกิจ</h4>
<ol>
<li>ผลิตและพัฒนาผู้เรียนให้ได้มาตรฐานสากล เป็นที่ยอมรับของตลาดแรงงาน</li>
<li>พัฒนาผู้เรียนให้มีจิตอาสา และบริการชุมชน</li>
<li>เสริมสร้างผู้เรียนให้มีคุณธรรม 12 ประการ</li>
<li>ส่งเสริมและพัฒนาผู้เรียนให้มีงานทำ</li>
<li>ส่งเสริมให้ใช้เทคโนโลยีทางการศึกษา เพื่อพัฒนาการเรียนการสอนให้มีประสิทธิภาพ รูปแบบการเรียนรู้ สู่การปฏิบัติและกิจกรรมการจัดการเรียนรู้ที่หลากหลาย</li>
<li>สร้างเครือข่ายความร่วมมือให้ทุกภาคส่วน มีส่วนร่วมในการจัดการอาชีวศึกษา</li>
<li>ส่งเสริมให้มีการวิจัย พัฒนา นวัตกรรม และสิ่งประดิษฐ์ เพื่อการพัฒนาชุมชนและสังคม</li>
<li>พัฒนาศักยภาพ ครู และบุคลากรทางการศึกษา ให้มีคุณภาพ สอดคล้องกับบริบทที่เปลี่ยนแปลง</li>
</ol>
<h4>เป้าประสงค์</h4>
<p>ผลิตช่างฝีมือ บุคลากรที่มีความสามารถด้านวิชาชีพ ให้สอดคล้องกับความต้องการของประชาชนในท้องถิ่นและตลาดแรงงาน สู่มาตรฐาน</p>',
content_en =
'<h4>Vision</h4>
<p>Phonthong Industrial and Community Education College is committed to producing and developing vocational manpower up to degree level through modern technology, meeting vocational education standards and responding to the needs of the labour market.</p>
<h4>Mission</h4>
<ol>
<li>Produce and develop learners to international standards that are accepted by the labour market.</li>
<li>Develop a volunteer spirit and community service in learners.</li>
<li>Instil the 12 core values in learners.</li>
<li>Promote and develop the employability of learners.</li>
<li>Promote the use of educational technology to make teaching and learning more effective, with practice-oriented and diverse learning activities.</li>
<li>Build cooperation networks so that all sectors take part in vocational education.</li>
<li>Promote research, development, innovation and inventions for community and social development.</li>
<li>Develop the capacity of teachers and educational personnel to be of high quality and responsive to a changing context.</li>
</ol>
<h4>Goal</h4>
<p>To produce skilled technicians and personnel with professional competence that meets the needs of the local community and the labour market, in line with recognised standards.</p>'
WHERE slug = 'vision';

UPDATE pages SET title = 'ปรัชญา อัตลักษณ์ เอกลักษณ์', title_en = 'Philosophy, Identity and Uniqueness', content =
'<h4>ปรัชญา</h4>
<p class="lead">สามัคคี มีวินัย ใส่ใจบริการ</p>
<h4>อัตลักษณ์</h4>
<p class="lead">คุณธรรมดี ฝีมือเด่น เน้นนวัตกรรมเทคโนโลยี</p>
<h4>เอกลักษณ์</h4>
<p class="lead">บริการวิชาชีพเด่น เน้นทักษะ สร้างผู้ประกอบการ</p>',
content_en =
'<h4>Philosophy</h4>
<p class="lead">Unity, Discipline, Service-Minded</p>
<h4>Identity</h4>
<p class="lead">Good Morals, Outstanding Skills, Focus on Innovation and Technology</p>
<h4>Uniqueness</h4>
<p class="lead">Excellence in Vocational Services, Skills-Focused, Building Entrepreneurs</p>'
WHERE slug = 'identity';

UPDATE pages SET title = 'สัญลักษณ์และสีประจำวิทยาลัย', title_en = 'Symbols and College Colours', content =
'<h4>สีประจำวิทยาลัย : น้ำเงิน - แดง</h4>
<table class="table table-bordered align-middle">
<tbody>
<tr><td style="width:90px;background:#1e3a8a">&nbsp;</td><td><strong>สีน้ำเงิน</strong> หมายถึง คุณธรรม</td></tr>
<tr><td style="background:#d71920">&nbsp;</td><td><strong>สีแดง</strong> หมายถึง ผู้เสียสละและผู้สร้างสรรค์</td></tr>
</tbody>
</table>',
content_en =
'<h4>College Colours: Blue and Red</h4>
<table class="table table-bordered align-middle">
<tbody>
<tr><td style="width:90px;background:#1e3a8a">&nbsp;</td><td><strong>Blue</strong> represents virtue.</td></tr>
<tr><td style="background:#d71920">&nbsp;</td><td><strong>Red</strong> represents self-sacrifice and creativity.</td></tr>
</tbody>
</table>'
WHERE slug = 'symbol';

UPDATE pages SET title = 'หลักสูตรที่เปิดสอน', title_en = 'Programmes Offered', content =
'<h4>ระดับประกาศนียบัตรวิชาชีพ (ปวช.)</h4>
<ul>
<li>สาขาวิชาช่างยนต์</li>
<li>สาขาวิชาช่างกลโรงงาน</li>
<li>สาขาวิชาช่างเชื่อมโลหะ</li>
<li>สาขาวิชาช่างไฟฟ้า</li>
<li>สาขาวิชาช่างอิเล็กทรอนิกส์</li>
<li>สาขาวิชาช่างก่อสร้าง</li>
<li>สาขาวิชาการบัญชี</li>
<li>สาขาวิชาการตลาด</li>
<li>สาขาวิชาเทคโนโลยีธุรกิจดิจิทัล</li>
<li>สาขาวิชาการโรงแรม</li>
</ul>
<h4>ระดับประกาศนียบัตรวิชาชีพชั้นสูง (ปวส.)</h4>
<ul>
<li>สาขาวิชาเทคนิคเครื่องกล (สาขางานยานยนต์)</li>
<li>สาขาวิชาเทคนิคการผลิต (ช่างกลโรงงาน)</li>
<li>สาขาวิชาช่างเชื่อมโลหะ</li>
<li>สาขาวิชาช่างไฟฟ้า</li>
<li>สาขาวิชาช่างอิเล็กทรอนิกส์</li>
<li>สาขาวิชาช่างโยธา</li>
<li>สาขาวิชาการบัญชี</li>
<li>สาขาวิชาการตลาด</li>
<li>สาขาวิชาเทคโนโลยีธุรกิจดิจิทัล</li>
<li>สาขาวิชาการโรงแรม</li>
</ul>',
content_en =
'<h4>Vocational Certificate (Voc. Cert.)</h4>
<ul>
<li>Auto Mechanics</li>
<li>Machine Shop</li>
<li>Welding and Metal Fabrication</li>
<li>Electrical Technology</li>
<li>Electronics</li>
<li>Construction</li>
<li>Accounting</li>
<li>Marketing</li>
<li>Digital Business Technology</li>
<li>Hotel</li>
</ul>
<h4>Higher Vocational Diploma (High Voc. Dip.)</h4>
<ul>
<li>Mechanical Technology (Automotive)</li>
<li>Production Technology (Machine Shop)</li>
<li>Welding and Metal Fabrication</li>
<li>Electrical Technology</li>
<li>Electronics</li>
<li>Civil Technology</li>
<li>Accounting</li>
<li>Marketing</li>
<li>Digital Business Technology</li>
<li>Hotel</li>
</ul>'
WHERE slug = 'programs';

-- ---------- หมวดข่าว / ลิงก์ (ชื่อภาษาอังกฤษ) ----------
UPDATE categories SET name_en = 'Public Relations' WHERE name = 'ข่าวประชาสัมพันธ์';
UPDATE categories SET name_en = 'Announcements' WHERE name = 'ข่าวประกาศ';
UPDATE categories SET name_en = 'Admissions & Jobs' WHERE name = 'ข่าวรับสมัคร';
UPDATE categories SET name_en = 'Procurement' WHERE name = 'ข่าวจัดซื้อจัดจ้าง';
UPDATE categories SET name_en = 'Achievements & Awards' WHERE name = 'ผลงานและรางวัล';
UPDATE categories SET color = '#1e3a8a' WHERE name = 'ข่าวประชาสัมพันธ์';
UPDATE categories SET color = '#d71920' WHERE name = 'ข่าวประกาศ';

UPDATE links SET title_en = 'Online Admission' WHERE title = 'ระบบสมัครเรียนออนไลน์';
UPDATE links SET title_en = 'College Management System (RMS)' WHERE title = 'ระบบบริหารสถานศึกษา (RMS)';
UPDATE links SET title_en = 'Course Registration' WHERE title = 'ระบบลงทะเบียนเรียน (ศธ.02)';
UPDATE links SET title_en = 'Check Grades' WHERE title = 'ตรวจผลการเรียน';
UPDATE links SET title_en = 'Homeroom Teacher System' WHERE title = 'ระบบครูที่ปรึกษา';
UPDATE links SET title_en = 'Online Library' WHERE title = 'ห้องสมุดออนไลน์';
UPDATE links SET title_en = 'e-Office (Saraban)' WHERE title = 'ระบบสารบรรณอิเล็กทรอนิกส์';
UPDATE links SET title_en = 'ITA Online' WHERE title = 'ITA Online';
UPDATE links SET title_en = 'Office of the Vocational Education Commission' WHERE title = 'สำนักงานคณะกรรมการการอาชีวศึกษา';
UPDATE links SET title_en = 'Institute of Vocational Education: Northeastern Region 3' WHERE title = 'สถาบันการอาชีวศึกษาภาคตะวันออกเฉียงเหนือ 3';
UPDATE links SET title_en = 'Vocational Education Data Center' WHERE title = 'ศูนย์ข้อมูลอาชีวศึกษา';
UPDATE links SET title_en = 'Ministry of Education' WHERE title = 'กระทรวงศึกษาธิการ';

-- ---------- ลบข้อมูลตัวอย่าง ----------
DELETE FROM news WHERE summary LIKE '(ข่าวตัวอย่าง)%';
DELETE FROM events WHERE title LIKE '(ตัวอย่าง)%';
DELETE FROM downloads WHERE title LIKE '%(ตัวอย่าง)%';
DELETE FROM student_stats;
DELETE FROM personnel;

-- ---------- ฝ่ายบริหาร ----------
UPDATE departments SET name_en = 'Resource Management Division' WHERE name = 'ฝ่ายบริหารทรัพยากร';
UPDATE departments SET name_en = 'Strategy and Planning Division' WHERE name = 'ฝ่ายยุทธศาสตร์และแผนงาน';
UPDATE departments SET name_en = 'Student Affairs Division' WHERE name = 'ฝ่ายพัฒนากิจการนักเรียน นักศึกษา';
UPDATE departments SET name_en = 'Academic Affairs Division' WHERE name = 'ฝ่ายวิชาการ';

-- ---------- แผนกวิชา (แทนที่ทั้งหมด) ----------
DELETE FROM departments WHERE type = 'academic';
INSERT INTO departments (name, name_en, type, description, description_en, sort_order) VALUES
('แผนกวิชาช่างยนต์', 'Auto Mechanics Department', 'academic', 'ปวช. สาขาวิชาช่างยนต์ / ปวส. สาขาวิชาเทคนิคเครื่องกล (สาขางานยานยนต์)', 'Voc. Cert. in Auto Mechanics / High Voc. Dip. in Mechanical Technology (Automotive)', 11),
('แผนกวิชาช่างกลโรงงาน', 'Machine Shop Department', 'academic', 'ปวช. สาขาวิชาช่างกลโรงงาน / ปวส. สาขาวิชาเทคนิคการผลิต (ช่างกลโรงงาน)', 'Voc. Cert. in Machine Shop / High Voc. Dip. in Production Technology', 12),
('แผนกวิชาช่างเชื่อมโลหะ', 'Welding and Metal Fabrication Department', 'academic', 'ปวช. และ ปวส. สาขาวิชาช่างเชื่อมโลหะ', 'Voc. Cert. and High Voc. Dip. in Welding and Metal Fabrication', 13),
('แผนกวิชาช่างไฟฟ้า', 'Electrical Technology Department', 'academic', 'ปวช. และ ปวส. สาขาวิชาช่างไฟฟ้า', 'Voc. Cert. and High Voc. Dip. in Electrical Technology', 14),
('แผนกวิชาช่างอิเล็กทรอนิกส์', 'Electronics Department', 'academic', 'ปวช. และ ปวส. สาขาวิชาช่างอิเล็กทรอนิกส์', 'Voc. Cert. and High Voc. Dip. in Electronics', 15),
('แผนกวิชาช่างก่อสร้าง', 'Construction and Civil Technology Department', 'academic', 'ปวช. สาขาวิชาช่างก่อสร้าง / ปวส. สาขาวิชาช่างโยธา', 'Voc. Cert. in Construction / High Voc. Dip. in Civil Technology', 16),
('แผนกวิชาการบัญชี', 'Accounting Department', 'academic', 'ปวช. และ ปวส. สาขาวิชาการบัญชี', 'Voc. Cert. and High Voc. Dip. in Accounting', 17),
('แผนกวิชาการตลาด', 'Marketing Department', 'academic', 'ปวช. และ ปวส. สาขาวิชาการตลาด', 'Voc. Cert. and High Voc. Dip. in Marketing', 18),
('แผนกวิชาเทคโนโลยีธุรกิจดิจิทัล', 'Digital Business Technology Department', 'academic', 'ปวช. และ ปวส. สาขาวิชาเทคโนโลยีธุรกิจดิจิทัล', 'Voc. Cert. and High Voc. Dip. in Digital Business Technology', 19),
('แผนกวิชาการโรงแรม', 'Hotel Department', 'academic', 'ปวช. และ ปวส. สาขาวิชาการโรงแรม', 'Voc. Cert. and High Voc. Dip. in Hotel', 20);

-- ---------- คณะผู้บริหาร ----------
INSERT INTO personnel (prefix, firstname, lastname, position, position_en, type, department_id, sort_order) VALUES
('ว่าที่ร้อยโท', 'สันติสุข', 'โมฬา', 'ผู้อำนวยการวิทยาลัยการอาชีพโพนทอง', 'Director', 'executive', NULL, 1),
('นางสาว', 'ศิริลักษณ์', 'แซ่อุ่น', 'รองผู้อำนวยการฝ่ายบริหารทรัพยากร', 'Deputy Director for Resource Management', 'executive',
  (SELECT id FROM departments WHERE name = 'ฝ่ายบริหารทรัพยากร' LIMIT 1), 2),
('นาย', 'ทวีเกียรติ', 'จันทะ', 'รองผู้อำนวยการฝ่ายวิชาการ และรองผู้อำนวยการฝ่ายกิจการนักเรียน นักศึกษา', 'Deputy Director for Academic Affairs and Student Affairs', 'executive',
  (SELECT id FROM departments WHERE name = 'ฝ่ายวิชาการ' LIMIT 1), 3),
('นาย', 'วิสิทธิ์ศักดิ์', 'แสนโสม', 'รองผู้อำนวยการฝ่ายยุทธศาสตร์และแผนงาน', 'Deputy Director for Strategy and Planning', 'executive',
  (SELECT id FROM departments WHERE name = 'ฝ่ายยุทธศาสตร์และแผนงาน' LIMIT 1), 4);

-- ---------- อาชีวศึกษาจังหวัดร้อยเอ็ด (ท้ายเว็บ) — เพิ่มเฉพาะที่ยังไม่มี, URL '#' = ยังไม่ระบุ ----------
INSERT INTO links (title, title_en, url, icon, link_group, sort_order)
SELECT v.title, v.title_en, '#', 'bi-building', 'province', v.sort_order FROM (
  SELECT 'วิทยาลัยเทคนิคร้อยเอ็ด' title, 'Roi Et Technical College' title_en, 1 sort_order UNION ALL
  SELECT 'วิทยาลัยอาชีวศึกษาร้อยเอ็ด', 'Roi Et Vocational College', 2 UNION ALL
  SELECT 'วิทยาลัยการอาชีพร้อยเอ็ด', 'Roi Et Industrial and Community Education College', 3 UNION ALL
  SELECT 'วิทยาลัยเกษตรและเทคโนโลยีร้อยเอ็ด', 'Roi Et College of Agriculture and Technology', 4 UNION ALL
  SELECT 'วิทยาลัยการอาชีพโพนทอง', 'Phonthong Industrial and Community Education College', 5 UNION ALL
  SELECT 'วิทยาลัยเทคนิคเกษตรวิสัย', 'Kaset Wisai Technical College', 6 UNION ALL
  SELECT 'วิทยาลัยเทคนิคสุวรรณภูมิ', 'Suwannaphum Technical College', 7 UNION ALL
  SELECT 'วิทยาลัยการอาชีพพนมไพร', 'Phanom Phrai Industrial and Community Education College', 8
) v WHERE NOT EXISTS (SELECT 1 FROM links l WHERE l.link_group = 'province' AND l.title = v.title);
-- ช่อง YouTube ของวิทยาลัย
INSERT INTO settings (skey, svalue) VALUES ('youtube_url', 'https://www.youtube.com/@pr.phonthong') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);

-- เว็บไซต์เดิมของวิทยาลัย (การ์ดข้างแผนที่หน้าแรก)
INSERT INTO settings (skey, svalue) VALUES ('old_site_url', 'https://www.pticec.ac.th/cms67/') ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);

-- ลิงก์ ITA Online ของวิทยาลัย
UPDATE links SET url = 'https://ita.pticec.ac.th/ati/' WHERE title = 'ITA Online';

-- เปลี่ยน "ตรวจผลการเรียน" เป็นระบบคลังภาพ Image Hub (ลิงก์จะแจ้งภายหลัง)
UPDATE links SET title = 'ระบบคลังภาพ Image Hub', title_en = 'Image Hub (Photo Archive)', icon = 'bi-images' WHERE title = 'ตรวจผลการเรียน';
