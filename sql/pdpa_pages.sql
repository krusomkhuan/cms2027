-- หน้านโยบาย PDPA และค่าตั้งต้น (รันซ้ำได้: ไม่ทับเนื้อหาที่แก้ไขแล้วในหลังบ้าน)
-- เนื้อหาเป็นร่างตั้งต้น ควรให้ผู้รับผิดชอบด้านกฎหมาย/เจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล (DPO) ของวิทยาลัยตรวจทานก่อนใช้จริง

INSERT IGNORE INTO settings (skey, svalue) VALUES
('pdpa_banner', '1'),
('pdpa_policy_version', '1.0'),
('dpo_name', 'เจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล วิทยาลัยการอาชีพโพนทอง'),
('dpo_email', 'sarabun@pticec.ac.th'),
('dpo_phone', '043-571144'),
('ga_id', ''),
('retention_messages_days', '365'),
('retention_consent_days', '730');

INSERT IGNORE INTO pages (slug, title, title_en, sort_order, show_in_menu, content, content_en) VALUES
('privacy', 'นโยบายคุ้มครองข้อมูลส่วนบุคคล', 'Privacy Policy', 90, 0,
'<p class="lead">วิทยาลัยการอาชีพโพนทอง สังกัดสำนักงานคณะกรรมการการอาชีวศึกษา (“วิทยาลัย”) ให้ความสำคัญกับการคุ้มครองข้อมูลส่วนบุคคล และดำเนินการตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) นโยบายนี้อธิบายว่าวิทยาลัยเก็บ ใช้ และเปิดเผยข้อมูลส่วนบุคคลผ่านเว็บไซต์นี้อย่างไร</p>
<h4>1. ผู้ควบคุมข้อมูลส่วนบุคคล</h4>
<p>วิทยาลัยการอาชีพโพนทอง เลขที่ 60 หมู่ที่ 11 ตำบลแวง อำเภอโพนทอง จังหวัดร้อยเอ็ด โทร 043-571144 อีเมล sarabun@pticec.ac.th</p>
<h4>2. ข้อมูลส่วนบุคคลที่เก็บรวบรวม</h4>
<ul>
<li><strong>แบบฟอร์มติดต่อสอบถามและแจ้งเรื่องร้องเรียน:</strong> ชื่อ-สกุล อีเมล เบอร์โทร เรื่องและรายละเอียดที่ท่านแจ้ง และหมายเลข IP</li>
<li><strong>คำร้องใช้สิทธิ์เจ้าของข้อมูล:</strong> ชื่อ-สกุล ช่องทางติดต่อ สถานะความเกี่ยวข้องกับวิทยาลัย และรายละเอียดคำร้อง</li>
<li><strong>บัญชีสมาชิก (ครู/บุคลากร):</strong> ชื่อ-สกุล ตำแหน่ง อีเมล เบอร์โทร ชื่อผู้ใช้ รหัสผ่าน (เก็บแบบเข้ารหัสทางเดียว) และประวัติการเข้าสู่ระบบ</li>
<li><strong>ข้อมูลบุคลากรที่เผยแพร่:</strong> ชื่อ-สกุล ตำแหน่ง สังกัด และรูปถ่าย ของผู้บริหาร ครู และบุคลากร</li>
<li><strong>ข้อมูลการใช้งานเว็บไซต์:</strong> คุกกี้ที่จำเป็นต่อการทำงานของเว็บไซต์ และคุกกี้อื่นตามที่ท่านเลือกยินยอม (ดู <a href="page.php?slug=cookie-policy">นโยบายคุกกี้</a>)</li>
</ul>
<p>ข้อมูลผู้เรียนบนเว็บไซต์แสดงเป็นจำนวนนับเท่านั้น ไม่เปิดเผยข้อมูลส่วนบุคคลของผู้เรียน</p>
<h4>3. วัตถุประสงค์และฐานทางกฎหมาย</h4>
<table class="table table-bordered">
<thead><tr><th>วัตถุประสงค์</th><th>ฐานทางกฎหมาย</th></tr></thead>
<tbody>
<tr><td>ตอบข้อซักถามและติดต่อกลับตามที่ท่านร้องขอ</td><td>ความยินยอม / การดำเนินการตามคำขอของเจ้าของข้อมูล</td></tr>
<tr><td>รับและตรวจสอบเรื่องร้องเรียนการทุจริตและประพฤติมิชอบ</td><td>การปฏิบัติตามกฎหมาย และภารกิจเพื่อประโยชน์สาธารณะ</td></tr>
<tr><td>เผยแพร่ข้อมูลผู้บริหารและบุคลากรตามหลักความโปร่งใสของหน่วยงานรัฐ</td><td>ภารกิจเพื่อประโยชน์สาธารณะ / การใช้อำนาจรัฐ</td></tr>
<tr><td>จัดการบัญชีสมาชิกสำหรับลงข่าวประชาสัมพันธ์</td><td>สัญญา / ประโยชน์โดยชอบด้วยกฎหมาย</td></tr>
<tr><td>รักษาความปลอดภัยของระบบและป้องกันการใช้งานในทางมิชอบ</td><td>ประโยชน์โดยชอบด้วยกฎหมาย</td></tr>
<tr><td>แสดงเนื้อหาจากภายนอก (Facebook, YouTube, Google Maps) และวิเคราะห์สถิติการใช้งาน</td><td>ความยินยอม</td></tr>
</tbody>
</table>
<h4>4. ระยะเวลาการเก็บรักษา</h4>
<ul>
<li>ข้อความติดต่อสอบถาม: ไม่เกิน 1 ปี นับจากวันที่ได้รับ</li>
<li>เรื่องร้องเรียนและคำร้องใช้สิทธิ์: ตามระยะเวลาที่ระเบียบราชการกำหนด หรือจนกว่าการดำเนินการจะแล้วเสร็จ</li>
<li>บันทึกการให้ความยินยอมคุกกี้: ไม่เกิน 2 ปี</li>
<li>บัญชีสมาชิก: ตลอดระยะเวลาที่ปฏิบัติงานกับวิทยาลัย หรือจนกว่าจะขอลบบัญชี</li>
</ul>
<h4>5. การเปิดเผยข้อมูล</h4>
<p>วิทยาลัยไม่ขายหรือให้เช่าข้อมูลส่วนบุคคล และจะเปิดเผยเฉพาะแก่ผู้ที่เกี่ยวข้องเท่าที่จำเป็น เช่น ผู้บังคับบัญชาตามสายงาน สำนักงานคณะกรรมการการอาชีวศึกษา หรือหน่วยงานรัฐที่มีอำนาจตามกฎหมาย</p>
<h4>6. สิทธิของเจ้าของข้อมูลส่วนบุคคล</h4>
<p>ท่านมีสิทธิ ดังนี้ ถอนความยินยอม · ขอเข้าถึงและขอรับสำเนา · ขอให้โอนย้ายข้อมูล · คัดค้านการประมวลผล · ขอให้ลบหรือทำให้ไม่สามารถระบุตัวตนได้ · ขอให้ระงับการใช้ · ขอให้แก้ไขให้ถูกต้อง · ร้องเรียนต่อสำนักงานคณะกรรมการคุ้มครองข้อมูลส่วนบุคคล (www.pdpc.or.th)</p>
<p>ยื่นคำร้องได้ที่ <a href="pdpa_request.php">แบบฟอร์มคำร้องใช้สิทธิ์เจ้าของข้อมูล</a> วิทยาลัยจะดำเนินการภายใน 30 วันนับจากวันที่ได้รับคำร้อง</p>
<h4>7. การรักษาความปลอดภัย</h4>
<p>วิทยาลัยใช้มาตรการรักษาความปลอดภัยที่เหมาะสม เช่น จำกัดสิทธิ์การเข้าถึงข้อมูลเฉพาะผู้ได้รับมอบหมาย เข้ารหัสรหัสผ่าน ป้องกันการโจมตีของแบบฟอร์ม และเก็บเรื่องร้องเรียนให้เข้าถึงได้เฉพาะผู้ดูแลระบบ</p>
<h4>8. การเปลี่ยนแปลงนโยบาย</h4>
<p>วิทยาลัยอาจปรับปรุงนโยบายนี้ตามความเหมาะสม และจะแจ้งบนเว็บไซต์นี้ หากมีการเปลี่ยนแปลงสาระสำคัญเกี่ยวกับคุกกี้ ระบบจะขอความยินยอมจากท่านใหม่</p>',
'<p class="lead">Phonthong Industrial and Community Education College, under the Office of the Vocational Education Commission (“the College”), is committed to protecting personal data in accordance with the Personal Data Protection Act B.E. 2562 (2019) (PDPA). This policy explains how the College collects, uses and discloses personal data through this website.</p>
<h4>1. Data Controller</h4>
<p>Phonthong Industrial and Community Education College, 60 Moo 11, Waeng Subdistrict, Phon Thong District, Roi Et 45110, Thailand. Tel. 043-571144, email sarabun@pticec.ac.th</p>
<h4>2. Personal Data We Collect</h4>
<ul>
<li><strong>Contact and complaint forms:</strong> name, email, phone, the subject and details you provide, and IP address.</li>
<li><strong>Data subject requests:</strong> name, contact details, your relationship with the College and the request details.</li>
<li><strong>Member accounts (teachers/staff):</strong> name, position, email, phone, username, password (stored as a one-way hash) and login history.</li>
<li><strong>Published staff information:</strong> names, positions, departments and photos of executives, teachers and staff.</li>
<li><strong>Website usage:</strong> cookies necessary for the website to function, and other cookies only if you consent (see the <a href="page.php?slug=cookie-policy">Cookie Policy</a>).</li>
</ul>
<p>Student information on this website is shown as aggregate counts only; no personal student data is published.</p>
<h4>3. Purposes and Legal Bases</h4>
<table class="table table-bordered">
<thead><tr><th>Purpose</th><th>Legal basis</th></tr></thead>
<tbody>
<tr><td>Answering enquiries and contacting you as requested</td><td>Consent / steps at the data subject’s request</td></tr>
<tr><td>Receiving and investigating corruption and misconduct complaints</td><td>Legal obligation and public task</td></tr>
<tr><td>Publishing information about executives and staff for public-sector transparency</td><td>Public task / exercise of official authority</td></tr>
<tr><td>Managing member accounts for posting news</td><td>Contract / legitimate interest</td></tr>
<tr><td>Keeping the system secure and preventing misuse</td><td>Legitimate interest</td></tr>
<tr><td>Displaying third-party content (Facebook, YouTube, Google Maps) and usage analytics</td><td>Consent</td></tr>
</tbody>
</table>
<h4>4. Retention</h4>
<ul>
<li>Contact messages: up to 1 year from receipt.</li>
<li>Complaints and data subject requests: as required by government regulations, or until handling is complete.</li>
<li>Cookie consent records: up to 2 years.</li>
<li>Member accounts: while working with the College, or until deletion is requested.</li>
</ul>
<h4>5. Disclosure</h4>
<p>The College does not sell or rent personal data, and discloses it only as necessary, for example to line supervisors, the Office of the Vocational Education Commission, or government authorities empowered by law.</p>
<h4>6. Your Rights</h4>
<p>You have the right to withdraw consent; access and obtain a copy; data portability; object to processing; erasure or anonymisation; restriction of use; rectification; and to lodge a complaint with the Personal Data Protection Committee (www.pdpc.or.th).</p>
<p>Submit a request using the <a href="pdpa_request.php">Data Subject Request form</a>. The College will respond within 30 days of receipt.</p>
<h4>7. Security</h4>
<p>The College applies appropriate security measures, such as limiting access to authorised staff, hashing passwords, protecting forms against abuse, and restricting complaints to system administrators only.</p>
<h4>8. Changes to This Policy</h4>
<p>The College may update this policy and will publish changes on this website. If cookie practices change materially, we will ask for your consent again.</p>'),

('cookie-policy', 'นโยบายคุกกี้', 'Cookie Policy', 91, 0,
'<p class="lead">คุกกี้คือไฟล์ข้อความขนาดเล็กที่เว็บไซต์บันทึกไว้ในอุปกรณ์ของท่าน เพื่อให้เว็บไซต์ทำงานได้และจดจำการตั้งค่าของท่าน</p>
<h4>ประเภทคุกกี้ที่เว็บไซต์นี้ใช้</h4>
<table class="table table-bordered">
<thead><tr><th>ประเภท</th><th>ตัวอย่าง / ผู้ให้บริการ</th><th>วัตถุประสงค์</th><th>ต้องขอความยินยอม</th></tr></thead>
<tbody>
<tr><td><strong>คุกกี้ที่จำเป็น</strong></td><td>cms2027 (session), pdpa_consent</td><td>รักษาการเข้าสู่ระบบ ป้องกันการปลอมแปลงแบบฟอร์ม จำภาษาที่เลือก นับผู้เข้าชมแบบไม่ระบุตัวตน และจำการตั้งค่าคุกกี้</td><td>ไม่ต้อง (จำเป็นต่อการทำงาน)</td></tr>
<tr><td><strong>คุกกี้วิเคราะห์การใช้งาน</strong></td><td>Google Analytics (ถ้าเปิดใช้)</td><td>วัดจำนวนผู้เข้าชมและหน้าที่ได้รับความนิยม เพื่อปรับปรุงเว็บไซต์</td><td>ต้อง</td></tr>
<tr><td><strong>คุกกี้สื่อภายนอก</strong></td><td>Facebook, YouTube, Google Maps</td><td>แสดงโพสต์ Facebook วิดีทัศน์ และแผนที่ ซึ่งผู้ให้บริการอาจบันทึกคุกกี้ของตนเอง</td><td>ต้อง</td></tr>
</tbody>
</table>
<p>เว็บไซต์ยังโหลดตัวอักษรและไฟล์ตกแต่งจาก Google Fonts และ jsDelivr ซึ่งไม่บันทึกคุกกี้ แต่ผู้ให้บริการจะได้รับหมายเลข IP ของท่านตามการทำงานปกติของอินเทอร์เน็ต</p>
<h4>การจัดการคุกกี้</h4>
<p>ท่านเปลี่ยนการตั้งค่าได้ทุกเมื่อที่ลิงก์ “ตั้งค่าคุกกี้” ท้ายเว็บไซต์ หรือลบคุกกี้ผ่านการตั้งค่าของเบราว์เซอร์ การปฏิเสธคุกกี้ที่ไม่จำเป็นจะไม่กระทบการใช้งานหลักของเว็บไซต์ แต่จะไม่แสดงเนื้อหาจากผู้ให้บริการภายนอกจนกว่าท่านจะอนุญาต</p>
<p>ดูรายละเอียดเพิ่มเติมได้ที่ <a href="page.php?slug=privacy">นโยบายคุ้มครองข้อมูลส่วนบุคคล</a></p>',
'<p class="lead">Cookies are small text files that a website stores on your device so that it works properly and remembers your preferences.</p>
<h4>Cookies Used on This Website</h4>
<table class="table table-bordered">
<thead><tr><th>Category</th><th>Examples / provider</th><th>Purpose</th><th>Consent required</th></tr></thead>
<tbody>
<tr><td><strong>Necessary</strong></td><td>cms2027 (session), pdpa_consent</td><td>Keeping you signed in, protecting forms against forgery, remembering your language, anonymous visit counting, and remembering your cookie choices</td><td>No (required for the site to work)</td></tr>
<tr><td><strong>Analytics</strong></td><td>Google Analytics (if enabled)</td><td>Measuring visits and popular pages to improve the website</td><td>Yes</td></tr>
<tr><td><strong>Third-party media</strong></td><td>Facebook, YouTube, Google Maps</td><td>Showing Facebook posts, videos and maps; these providers may set their own cookies</td><td>Yes</td></tr>
</tbody>
</table>
<p>The website also loads fonts and assets from Google Fonts and jsDelivr. These do not set cookies, but the providers receive your IP address as part of normal internet traffic.</p>
<h4>Managing Cookies</h4>
<p>You can change your choices at any time via “Cookie settings” in the website footer, or delete cookies in your browser settings. Declining optional cookies does not affect the main features of the website, but third-party content will not be shown until you allow it.</p>
<p>See also our <a href="page.php?slug=privacy">Privacy Policy</a>.</p>');
