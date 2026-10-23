-- ระบบจองรถยนต์ มหาวิทยาลัยเกษตรศาสตร์
CREATE DATABASE IF NOT EXISTS ku_car_booking
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ku_car_booking;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS cars;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(120) NULL,
  phone         VARCHAR(20)  NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE cars (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plate_no    VARCHAR(20)  NOT NULL UNIQUE,
  name        VARCHAR(100) NOT NULL,
  type        VARCHAR(40)  NOT NULL,
  seats       TINYINT UNSIGNED NOT NULL,
  image       VARCHAR(150) NULL,          -- ชื่อไฟล์ใน assets/img/cars/
  description VARCHAR(255) NULL,
  status      ENUM('available','maintenance') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  car_id      INT UNSIGNED NOT NULL,
  start_time  DATETIME NOT NULL,
  end_time    DATETIME NOT NULL,
  destination VARCHAR(200) NOT NULL,
  purpose     VARCHAR(255) NOT NULL,
  passengers  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  status      ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  admin_note  VARCHAR(255) NULL,
  decided_by  INT UNSIGNED NULL,
  decided_at  DATETIME NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_b_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_b_car  FOREIGN KEY (car_id)  REFERENCES cars(id),
  INDEX idx_car_time (car_id, start_time, end_time),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- บัญชีทดสอบ: admin / Admin@1234  และ  user1 / User@1234
INSERT INTO users (username, password_hash, full_name, email, phone, role) VALUES
('admin', '$2b$10$hwnEG9LMQHDrVnSe8hbse.8IqLRy2nNgVuLqFLU4TJaNIWDXTzYju', 'ผู้ดูแลระบบ', 'admin@ku.ac.th', '0800000000', 'admin'),
('user1', '$2b$10$eT4bVmfqa4LqgxBGqsb66e7crogNt1nSRIGfkwECG2KfsRqp1pyfW', 'สมชาย ใจดี', 'somchai@ku.th', '0811111111', 'user');

-- รถตัวอย่าง (วางรูปจริงที่ assets/img/cars/ ตามชื่อไฟล์ด้านล่าง)
INSERT INTO cars (plate_no, name, type, seats, image, description) VALUES
('กก 1234', 'Toyota Fortuner', 'SUV', 7, 'toyota-fortuner.jpg', 'SUV 7 ที่นั่ง เหมาะกับการเดินทางต่างจังหวัด'),
('ขข 5678', 'Toyota Commuter', 'รถตู้', 12, 'toyota-commuter.jpg', 'รถตู้ 12 ที่นั่ง สำหรับคณะเดินทาง'),
('คค 9012', 'Honda City', 'เก๋ง', 5, 'honda-city.jpg', 'รถเก๋งประหยัดน้ำมัน สำหรับติดต่องานในเมือง'),
('งง 3456', 'Isuzu D-Max', 'กระบะ', 4, 'isuzu-dmax.jpg', 'กระบะ สำหรับบรรทุกอุปกรณ์/ลงพื้นที่');
