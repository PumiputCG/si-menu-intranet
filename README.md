# SI MENU

## SI MENU คืออะไร / About

พอร์ทัลกลางขององค์กรที่รวมซอฟต์แวร์ภายในทุกตัวไว้ในหน้าเดียว และเป็นช่องทางหลักในการประกาศข่าวสารถึงพนักงาน พนักงานไม่ต้องจำลิงก์ของแต่ละระบบอีกต่อไป

The organization's central portal. It gathers every internal application on one page and serves as the main channel for company announcements, so staff no longer need to remember a separate link for each system.

## ทำอะไรได้บ้าง / Features

- รวมลิงก์ระบบภายในทั้งหมด จัดหมวดหมู่และค้นหาได้
- ประกาศข่าวสาร ดูย้อนหลัง และดาวน์โหลดไฟล์แนบ
- ทะเบียนเอกสารทางการ ส่งออกเป็น PDF รายแถว และเป็น Excel แบบใส่รหัสผ่าน
- ใช้งานได้ 3 ภาษา ไทย อังกฤษ และพม่า
- ติดตั้งเป็นแอปบนมือถือได้ (PWA)

* Every internal system linked in one place, grouped and searchable
* Company announcements with history and downloadable attachments
* An official document register with per-row PDF and password-protected Excel export
* Available in Thai, English and Burmese
* Installable on phones as a web app (PWA)

## Tech Stack

**Backend:** PHP 8

**Frontend:** HTML5, JavaScript, Tailwind CSS, Alpine.js, SweetAlert2, Font Awesome

**Database:** Microsoft SQL Server (ODBC)

## ติดตั้ง / Installation

ต้องมี Apache กับ PHP 8 ที่เปิด extension ODBC, ODBC Driver for SQL Server และ Node.js (ใช้ XAMPP ได้) ให้ clone ไว้ในโฟลเดอร์ `htdocs`

Requires Apache and PHP 8 with the ODBC extension enabled, an ODBC Driver for SQL Server, and Node.js. XAMPP works. Clone it into your `htdocs` folder.

```bash
git clone https://github.com/PumiputCG/si-menu-intranet.git
cd si-menu-intranet
npm install
npm run build
cp conn.example.php conn.php
```

แก้ค่าเชื่อมต่อฐานข้อมูลใน `conn.php` แล้วเปิด http://localhost/si-menu-intranet

Fill in the database settings in `conn.php`, then open http://localhost/si-menu-intranet
