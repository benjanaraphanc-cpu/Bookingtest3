<?php
// ===== ตั้งค่าระบบ =====
date_default_timezone_set('Asia/Bangkok');

// ฐานข้อมูล (XAMPP ค่าเริ่มต้น: root / รหัสผ่านว่าง)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'ku_car_booking');
define('DB_USER', 'root');
define('DB_PASS', '');

// โฟลเดอร์ของเว็บ: รันด้วย `php -S` ที่ root โปรเจกต์ ใช้ '' ,
// ถ้าวางใน XAMPP htdocs/ku-car-booking ให้ใช้ '/ku-car-booking'
define('BASE_URL', '');

// ===== จำกัดเครือข่าย Wi-Fi มหาวิทยาลัย =====
define('NETWORK_CHECK_ENABLED', true);   // false = ปิดการตรวจสอบ (ใช้ตอนพัฒนาเท่านั้น)
// ช่วง IP ของ มก. (ตรวจสอบกับ สำนักบริการคอมพิวเตอร์ มก. ให้ตรงกับ Wi-Fi จริงอีกครั้ง)
define('KU_ALLOWED_CIDRS', [
    '158.108.0.0/16',
    // '10.0.0.0/8',          // ถ้า Wi-Fi ภายในจ่าย IP วง private และ Server อยู่ใน LAN
    // '192.168.1.0/24',      // ตัวอย่าง: วง Wi-Fi ที่ใช้ทดสอบ
]);
// อนุญาตเครื่องตัวเอง (localhost) ระหว่างพัฒนา -> ตอนใช้งานจริงให้เป็น false
define('DEV_ALLOW_LOCALHOST', true);

// กำหนดการจอง
define('MAX_BOOKING_DAYS', 7);
