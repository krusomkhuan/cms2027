-- โครงสร้างฐานข้อมูล CMS วิทยาลัยการอาชีพโพนทอง

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  fullname VARCHAR(150) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(64) PRIMARY KEY,
  svalue TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#1e3a8a',
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  title VARCHAR(255) NOT NULL,
  summary TEXT NULL,
  content MEDIUMTEXT NULL,
  cover VARCHAR(255) NULL,
  attachment VARCHAR(255) NULL,
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('published','draft') NOT NULL DEFAULT 'published',
  views INT NOT NULL DEFAULT 0,
  author_id INT NULL,
  published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_news_pub (status, published_at),
  CONSTRAINT fk_news_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_news_user FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  news_id INT NOT NULL,
  path VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_img_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ฝ่าย/งาน (division) และแผนกวิชา (academic)
CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  type ENUM('division','academic') NOT NULL DEFAULT 'academic',
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personnel (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prefix VARCHAR(50) NULL,
  firstname VARCHAR(100) NOT NULL,
  lastname VARCHAR(100) NOT NULL,
  position VARCHAR(150) NULL,
  academic_rank VARCHAR(100) NULL,
  type ENUM('executive','teacher','staff') NOT NULL DEFAULT 'teacher',
  department_id INT NULL,
  education VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(150) NULL,
  photo VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_person_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- จำนวนนักเรียน แยกปีการศึกษา/ภาคเรียน/ระดับ/แผนก/ชั้นปี
CREATE TABLE IF NOT EXISTS student_stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  academic_year SMALLINT NOT NULL,
  semester TINYINT NOT NULL DEFAULT 1,
  level VARCHAR(20) NOT NULL,
  department_id INT NOT NULL,
  class_year TINYINT NOT NULL,
  male INT NOT NULL DEFAULT 0,
  female INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_stat (academic_year, semester, level, department_id, class_year),
  CONSTRAINT fk_stat_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  content MEDIUMTEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  show_in_menu TINYINT(1) NOT NULL DEFAULT 1,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NULL,
  image VARCHAR(255) NOT NULL,
  link VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS downloads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  file VARCHAR(255) NULL,
  url VARCHAR(255) NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  hits INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  youtube_id VARCHAR(20) NOT NULL,
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ปฏิทินกิจกรรม
CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  time_text VARCHAR(100) NULL,
  location VARCHAR(255) NULL,
  detail TEXT NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#1e3a8a',
  INDEX idx_event_date (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ข้อความติดต่อสอบถาม / แจ้งเรื่องร้องเรียน
CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('contact','complaint') NOT NULL DEFAULT 'contact',
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(50) NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  ip VARCHAR(45) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- สถิติผู้เข้าชมรายวัน
CREATE TABLE IF NOT EXISTS visit_stats (
  visit_date DATE PRIMARY KEY,
  hits INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ลิงก์บริการออนไลน์ (eservice) และหน่วยงานที่เกี่ยวข้อง (external)
CREATE TABLE IF NOT EXISTS links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  url VARCHAR(255) NOT NULL,
  icon VARCHAR(50) NOT NULL DEFAULT 'bi-link-45deg',
  link_group ENUM('eservice','external') NOT NULL DEFAULT 'eservice',
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ภาษาอังกฤษ (รองรับเว็บ 2 ภาษา) — ถ้าเว้นว่างจะแสดงภาษาไทยแทน ----------
ALTER TABLE categories ADD COLUMN IF NOT EXISTS name_en VARCHAR(150) NULL AFTER name;
ALTER TABLE news ADD COLUMN IF NOT EXISTS title_en VARCHAR(255) NULL AFTER title,
  ADD COLUMN IF NOT EXISTS summary_en TEXT NULL AFTER summary,
  ADD COLUMN IF NOT EXISTS content_en MEDIUMTEXT NULL AFTER content;
ALTER TABLE departments ADD COLUMN IF NOT EXISTS name_en VARCHAR(150) NULL AFTER name,
  ADD COLUMN IF NOT EXISTS description_en TEXT NULL AFTER description;
ALTER TABLE personnel ADD COLUMN IF NOT EXISTS name_en VARCHAR(200) NULL AFTER lastname,
  ADD COLUMN IF NOT EXISTS position_en VARCHAR(150) NULL AFTER position;
ALTER TABLE pages ADD COLUMN IF NOT EXISTS title_en VARCHAR(255) NULL AFTER title,
  ADD COLUMN IF NOT EXISTS content_en MEDIUMTEXT NULL AFTER content;
ALTER TABLE slides ADD COLUMN IF NOT EXISTS title_en VARCHAR(255) NULL AFTER title;
ALTER TABLE downloads ADD COLUMN IF NOT EXISTS title_en VARCHAR(255) NULL AFTER title;
ALTER TABLE videos ADD COLUMN IF NOT EXISTS title_en VARCHAR(255) NULL AFTER title;
ALTER TABLE events ADD COLUMN IF NOT EXISTS title_en VARCHAR(255) NULL AFTER title,
  ADD COLUMN IF NOT EXISTS location_en VARCHAR(255) NULL AFTER location,
  ADD COLUMN IF NOT EXISTS detail_en TEXT NULL AFTER detail;
ALTER TABLE links ADD COLUMN IF NOT EXISTS title_en VARCHAR(150) NULL AFTER title;
-- ---------- สมาชิก (ครู/บุคลากร สมัครเองเป็น "ผู้สื่อข่าว") ----------
ALTER TABLE users MODIFY role ENUM('admin','editor','reporter') NOT NULL DEFAULT 'editor';
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER fullname,
  ADD COLUMN IF NOT EXISTS phone VARCHAR(50) NULL AFTER email,
  ADD COLUMN IF NOT EXISTS position VARCHAR(150) NULL AFTER phone;
ALTER TABLE news MODIFY status ENUM('published','pending','draft') NOT NULL DEFAULT 'published';
-- ---------- PDPA ----------
-- บันทึกการให้ความยินยอมคุกกี้ (หลักฐานตาม พ.ร.บ.คุ้มครองข้อมูลส่วนบุคคล) — เก็บ IP แบบ hash
CREATE TABLE IF NOT EXISTS consent_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  consent_id CHAR(16) NOT NULL,
  analytics TINYINT(1) NOT NULL DEFAULT 0,
  media TINYINT(1) NOT NULL DEFAULT 0,
  policy_version VARCHAR(10) NOT NULL,
  ip_hash CHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_consent_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- คำร้องใช้สิทธิ์ของเจ้าของข้อมูลส่วนบุคคล
CREATE TABLE IF NOT EXISTS pdpa_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ref_no VARCHAR(20) NOT NULL UNIQUE,
  request_type VARCHAR(30) NOT NULL,
  relation VARCHAR(30) NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(50) NULL,
  detail TEXT NOT NULL,
  status ENUM('received','in_progress','completed','rejected') NOT NULL DEFAULT 'received',
  admin_note TEXT NULL,
  ip VARCHAR(45) NULL,
  due_at DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---------- กลุ่มลิงก์ "อาชีวศึกษาจังหวัดร้อยเอ็ด" (ท้ายเว็บ) ----------
ALTER TABLE links MODIFY link_group ENUM('eservice','external','province') NOT NULL DEFAULT 'eservice';