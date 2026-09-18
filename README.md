# SI MENU — Intranet Portal

**TH:** หน้าแรกที่พนักงานทุกคนเปิดตอนเริ่มงาน — ระบบภายใน ประกาศ เอกสาร ปฏิทิน อยู่ในหน้าเดียว
**EN:** The page every employee opens at the start of the day — internal systems, announcements, documents, and calendars in one place.

`PHP 8` · `Tailwind CSS 4` · `MS SQL Server (ODBC)` · `Vanilla JavaScript`

---

## 🇹🇭 ภาษาไทย

### ทำไมต้องมีหน้านี้

บริษัทมีระบบภายในเยอะมาก — e-Leave, Business Plus, IT Service, ระบบจอง, ระบบ OT และอีกหลายตัว แต่ละตัวคนละ URL พนักงานใหม่ไม่รู้ว่ามีอะไรบ้าง ส่วนประกาศบริษัทก็ติดบอร์ดบ้าง ส่งไลน์บ้าง หาย้อนหลังไม่ได้

SI MENU เป็นประตูทางเข้าเดียว รวมทุกอย่างไว้ และทำให้ "ของทางการ" ค้นเจอได้จริง

### ทำอะไรได้บ้าง

- **รวมแอปภายใน** — จัดหมวดหมู่ ค้นหา และเข้าถึงทุกระบบจากหน้าเดียว
- **ประกาศบริษัท** — เผยแพร่ ดูย้อนหลัง และดาวน์โหลดเป็นไฟล์
- **ทะเบียนเอกสาร** — จัดการเอกสารทางการ มีการลงทะเบียน ซ่อนแถวที่ไม่ต้องแสดง และออก PDF รายแถว
- **ส่งออก Excel แบบใส่รหัสผ่าน** — สำหรับเอกสารที่ไม่ควรเปิดต่อกันได้อิสระ
- **หลายภาษา** — ไทย / อังกฤษ / พม่า พร้อมธงเลือกภาษา และจำค่าที่เลือกไว้ตอนกรอกฟอร์ม
- **ปฏิทิน แผนที่ ข้อมูลพนักงาน** — ของที่ต้องใช้บ่อยแต่ไม่มีที่อยู่ถาวร

### หลักที่ยึด

หนึ่ง section สื่อสารหนึ่งเรื่อง ข้อมูลทางการมาก่อนการตกแต่ง ใช้สีน้ำเงิน-เขียวขององค์กรบนพื้นเรียบ เว้นที่ว่างเยอะ และเคลื่อนไหวเท่าที่จำเป็น — ตั้งใจให้ตรงข้ามกับพอร์ทัลองค์กรรุ่นเก่าที่สีเยอะและอ่านยาก

### ติดตั้ง

```bash
npm install
cp conn.example.php conn.php   # แล้วใส่ค่าฐานข้อมูลของคุณ
npm run build                  # คอมไพล์ Tailwind
# วางไว้ใต้ document root ของ Apache (XAMPP)
```

> ⚠️ ไฟล์ `conn.php` ถูก gitignore ไว้เพราะมีรหัสผ่านฐานข้อมูล — ห้าม commit

---

## 🇬🇧 English

### Why this page exists

The company runs a lot of internal systems — e-Leave, Business Plus, IT Service, booking, overtime, and more — each at its own URL. New employees had no idea what existed. Announcements went up on a board, or into a chat group, and were unfindable a week later.

SI MENU is the single front door, and it makes official information actually retrievable.

### What it does

- **Application gateway** — every internal system, categorized and searchable, from one page
- **Company announcements** — publish, browse history, download as files
- **Document register** — official documents with registration, row-level hiding, and per-row PDF export
- **Password-protected Excel export** for documents that shouldn't circulate freely
- **Three languages** — Thai, English, Burmese, with a flag switcher that survives form entry
- **Calendars, maps, and employee info** — the frequently-needed things that never had a home

### Design stance

One section, one purpose. Official information outranks decoration. Corporate blue and green on neutral surfaces, generous whitespace, restrained motion — a deliberate reaction against the crowded, multicolored legacy portal it replaced.

### Setup

```bash
npm install
cp conn.example.php conn.php   # then fill in your own database values
npm run build                  # compile Tailwind
# serve from an Apache document root (XAMPP)
```

> ⚠️ `conn.php` is gitignored because it holds database credentials — never commit it.

### Note

Code only. Uploaded documents, announcement data, and the gallery are excluded.
