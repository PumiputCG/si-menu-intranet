<?php
/* ══════════════════════════════════════════════════════════════
   zoom_lock.php — คุมขนาดการแสดงผลให้เท่ากันทุกเครื่อง

   ใส่ไว้ใน <head> ของทุกหน้า:
     <?php require_once dirname(__FILE__) . '/zoom_lock.php'; ?>

   ── ปัญหาที่ไฟล์นี้แก้ ──
   เดิมล็อคเป็นเปอร์เซ็นต์ตายตัว (เช่น 80%) ซึ่งคุมได้แค่ "จำนวน CSS px"
   แต่ 1 CSS px จะใหญ่แค่ไหนบนจอจริง ขึ้นกับ Windows Display Scaling
   ของแต่ละเครื่อง จอ 1920x1080 เท่ากันแต่ตั้ง scaling ต่างกัน
   จะเห็นตัวอักษรต่างกันได้ถึง 1.5 เท่า

     scaling 100% → innerWidth 1920 → ตัวอักษรเล็ก
     scaling 150% → innerWidth 1280 → ตัวอักษรใหญ่

   ── วิธีแก้ ──
   คิดซูมจากความกว้างจอแทน:  zoom = innerWidth ÷ DESIGN_W
   ทำให้พื้นที่ layout ได้ DESIGN_W CSS px เท่ากันทุกเครื่องเสมอ
   ขนาดตัวอักษรบนจอจริงจึงเท่ากันด้วย เพราะ scaling ถูกหักล้างไปในตัว
   (innerWidth × scaling = ความกว้างจอจริง ซึ่งเท่ากัน)

   ผลพลอยได้: ผู้ใช้กดซูมเบราว์เซอร์เองก็ไม่มีผล เพราะ innerWidth
   เปลี่ยนตาม แล้วสูตรคำนวณชดเชยให้อัตโนมัติ ไม่ต้องวัด outerWidth
   ══════════════════════════════════════════════════════════════ */
?>
<style>
  /* จอเล็ก / จอสัมผัส — ไม่คุมขนาด ปล่อยให้ responsive ทำงาน */
  :root { --screen-h: 100vh; }
</style>
<script>
  (function () {
    'use strict';

    /* ── ปุ่มปรับหลัก ──
       DESIGN_W = ความกว้าง layout ที่ต้องการให้ทุกเครื่องได้เท่ากัน (CSS px)
       เลขน้อย = ทุกอย่างใหญ่ขึ้น เห็นเนื้อหาน้อยลง
       เลขมาก  = ทุกอย่างเล็กลง เห็นเนื้อหามากขึ้น */
    var DESIGN_W = 1900;

    /* เพดานล่าง-บน กันจอแคบมากแล้วตัวเล็กจนอ่านไม่ออก
       และจอกว้างมากแล้วตัวใหญ่เทอะทะ */
    var MIN_ZOOM = 0.70;
    var MAX_ZOOM = 1.20;

    /* ต่ำกว่านี้ถือว่าเป็นมือถือ/แท็บเล็ต ปล่อย responsive ล้วน */
    var MIN_WIDTH = 1024;

    var root = document.documentElement;
    var applied = 0;

    function shouldScale() {
      var w = window.innerWidth || root.clientWidth;
      var fine = !window.matchMedia || window.matchMedia('(pointer: fine)').matches;
      return w >= MIN_WIDTH && fine;
    }

    /* --screen-h = ความสูงจอจริง แปลงกลับเป็นหน่วยก่อนถูกซูม
       ความยาว L ใน CSS จะถูกวาดออกมาเป็น L × zoom
       อยากให้วาดได้ innerHeight พอดี จึงต้องใส่ innerHeight ÷ zoom */
    function syncScreenHeight() {
      if (!applied) {
        root.style.removeProperty('--screen-h');
        return;
      }

      var h = window.innerHeight || root.clientHeight;
      if (h > 0) {
        root.style.setProperty('--screen-h', (h / applied) + 'px');
      }
    }

    function apply() {
      if (!shouldScale()) {
        if (applied !== 0) {
          applied = 0;
          root.style.zoom = '';
          syncScreenHeight();
        }
        return;
      }

      var w = window.innerWidth || root.clientWidth;
      var next = w / DESIGN_W;

      if (next < MIN_ZOOM) { next = MIN_ZOOM; }
      if (next > MAX_ZOOM) { next = MAX_ZOOM; }

      /* ปัดทศนิยม 3 ตำแหน่ง กันการเขียนค่าซ้ำถี่ ๆ จนหน้ากระตุก */
      next = Math.round(next * 1000) / 1000;

      if (next !== applied) {
        applied = next;
        root.style.zoom = next;
      }

      syncScreenHeight();
    }

    apply();

    window.addEventListener('resize', apply);
    window.addEventListener('orientationchange', apply);

    /* ── ปิดทางซูมที่ผู้ใช้กดเอง ──
       จริง ๆ สูตรชดเชยให้อยู่แล้ว กดแล้วภาพไม่เปลี่ยน
       แต่บล็อกไว้กันผู้ใช้กดรัว ๆ แล้วสงสัยว่าทำไมไม่มีอะไรเกิดขึ้น */
    window.addEventListener('wheel', function (e) {
      if (!shouldScale()) { return; }
      if (e.ctrlKey || e.metaKey) { e.preventDefault(); }
    }, { passive: false });

    window.addEventListener('keydown', function (e) {
      if (!shouldScale()) { return; }
      if (!e.ctrlKey && !e.metaKey) { return; }

      var k = e.key;
      if (k === '+' || k === '-' || k === '=' || k === '_' || k === '0') {
        e.preventDefault();
      }
    });

    /* หมายเหตุ: ไม่ทับ meta viewport
       การใส่ user-scalable=no ปิดการซูมนิ้วบนมือถือ คนสายตาไม่ดีจะใช้ไม่ได้ */
  })();
</script>
