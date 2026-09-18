<?php
require_once dirname(__FILE__) . '/no_cache.php';   /* ส่ง header กันแคช ต้องอยู่ก่อน output ใด ๆ */
require_once dirname(__FILE__) . '/announcement_helpers.php';
require_once dirname(__FILE__) . '/announcement_register_helpers.php';
announcement_start_session();

$message = '';
$errors = array();
$postedAction = '';
$instantDrop = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instant_drop']) && $_POST['instant_drop'] === '1'
  && isset($_POST['action']) && in_array($_POST['action'], array('drop_cover', 'drop_file'));

/* ประกาศที่ต้องเปิดโมดัลแก้ไขค้างไว้หลังรีโหลด (เช่น เพิ่งลบภาพ/ไฟล์ข้างใน) */
$reopenId = '';
$reopenStep = 1;

if (isset($_SESSION['announcement_flash'])) {
  $message = $_SESSION['announcement_flash'];
  unset($_SESSION['announcement_flash']);
}

if (isset($_SESSION['announcement_reopen'])) {
  $reopen = $_SESSION['announcement_reopen'];
  $reopenId = isset($reopen['id']) ? $reopen['id'] : '';
  $reopenStep = isset($reopen['step']) ? (int) $reopen['step'] : 1;
  unset($_SESSION['announcement_reopen']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? $_POST['action'] : '';
  $postedAction = $action;

  if ($action === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (announcement_login($username, $password)) {
      header('Location: announcements_admin.php');
      exit;
    }

    $errors[] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
  } elseif ($action === 'logout') {
    announcement_logout();
    header('Location: announcements_admin.php');
    exit;
  } elseif (!announcement_check_csrf(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
    $errors[] = 'Session หมดอายุ กรุณาลองใหม่';
  } elseif (announcement_is_admin() && $action === 'create') {
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $body = isset($_POST['body']) ? $_POST['body'] : '';
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    $files = isset($_FILES['attachments']) ? $_FILES['attachments'] : null;
    $coverFile = isset($_FILES['cover_image']) ? $_FILES['cover_image'] : null;
    $pinned = isset($_POST['pinned']) && $_POST['pinned'] === '1';
    $translations = array(
      'en' => array(
        'title' => isset($_POST['title_en']) ? $_POST['title_en'] : '',
        'body' => isset($_POST['body_en']) ? $_POST['body_en'] : ''
      ),
      'my' => array(
        'title' => isset($_POST['title_my']) ? $_POST['title_my'] : '',
        'body' => isset($_POST['body_my']) ? $_POST['body_my'] : ''
      )
    );

    if (announcement_create($title, $body, $files, $coverFile, $translations, $errors, $pinned, $category)) {
      $_SESSION['announcement_flash'] = 'บันทึกประกาศเรียบร้อยแล้ว';
      header('Location: announcements_admin.php#allPosts');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'update') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $body = isset($_POST['body']) ? $_POST['body'] : '';
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    $files = isset($_FILES['attachments']) ? $_FILES['attachments'] : null;
    $coverFile = isset($_FILES['cover_image']) ? $_FILES['cover_image'] : null;
    $removeAttachmentIds = isset($_POST['remove_attachments']) ? $_POST['remove_attachments'] : array();
    $removeCover = isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1';
    $pinned = isset($_POST['pinned']) && $_POST['pinned'] === '1';
    $translations = array(
      'en' => array(
        'title' => isset($_POST['title_en']) ? $_POST['title_en'] : '',
        'body' => isset($_POST['body_en']) ? $_POST['body_en'] : ''
      ),
      'my' => array(
        'title' => isset($_POST['title_my']) ? $_POST['title_my'] : '',
        'body' => isset($_POST['body_my']) ? $_POST['body_my'] : ''
      )
    );

    if (announcement_update($id, $title, $body, $files, $coverFile, $translations, $removeAttachmentIds, $removeCover, $errors, $pinned, $category)) {
      $_SESSION['announcement_flash'] = 'อัปเดตประกาศเรียบร้อยแล้ว';
      header('Location: announcements_admin.php#allPosts');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'create_category') {
    $catTh = isset($_POST['cat_name_th']) ? $_POST['cat_name_th'] : '';
    $catEn = isset($_POST['cat_name_en']) ? $_POST['cat_name_en'] : '';
    $catMy = isset($_POST['cat_name_my']) ? $_POST['cat_name_my'] : '';

    if (announcement_category_create($catTh, $catEn, $catMy, $errors)) {
      $_SESSION['announcement_flash'] = 'เพิ่มหัวข้อแท็บเรียบร้อยแล้ว';
      header('Location: announcements_admin.php');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'delete_category') {
    $catId = isset($_POST['cat_id']) ? $_POST['cat_id'] : '';

    if (announcement_category_delete($catId, $errors)) {
      $_SESSION['announcement_flash'] = 'ลบหัวข้อแท็บเรียบร้อยแล้ว';
      header('Location: announcements_admin.php');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'pin') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $pinned = isset($_POST['pinned']) && $_POST['pinned'] === '1';
    if (announcement_set_pinned($id, $pinned, $errors)) {
      $_SESSION['announcement_flash'] = $pinned ? 'ปักหมุดประกาศแล้ว' : 'ถอดหมุดประกาศแล้ว';
      header('Location: announcements_admin.php');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'delete') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    /* ประกาศทะเบียนลบไม่ได้ — ปุ่มถูกถอดออกจากหน้าแล้ว
       ด่านนี้กันกรณียิง POST ตรงเข้ามา */
    if ($id === ANNOUNCEMENT_REGISTER_POST_ID) {
      $errors[] = 'ประกาศทะเบียนเป็นประกาศระบบ ไม่สามารถลบได้';
    } elseif (announcement_delete($id)) {
      $_SESSION['announcement_flash'] = 'ลบประกาศเรียบร้อยแล้ว';
      header('Location: announcements_admin.php');
      exit;
    } else {
      $errors[] = 'ลบประกาศไม่สำเร็จ';
    }

  /* ── ลบภาพปก / ไฟล์แนบ ทีละชิ้นทันที ── */
  } elseif (announcement_is_admin() && ($action === 'drop_cover' || $action === 'drop_file')) {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $items = announcement_read_all();
    $index = -1;
    $count = count($items);

    for ($i = 0; $i < $count; $i++) {
      if (isset($items[$i]['id']) && $items[$i]['id'] === $id) {
        $index = $i;
        break;
      }
    }

    if ($index < 0) {
      $errors[] = 'ไม่พบประกาศ';
    } else {
      $oldDropItem = $items[$index];
      $removed = array();
      if ($action === 'drop_cover') {
        $slot = (isset($_POST['slot']) && $_POST['slot'] === '2') ? 2 : 1;
        $items[$index][$slot === 2 ? 'cover_image_2' : 'cover_image'] = null;
        $flashMsg = $slot === 2 ? 'ลบภาพปก 2 แล้ว' : 'ลบภาพปก 1 แล้ว';
      } else {
        $dropId = isset($_POST['file_id']) ? $_POST['file_id'] : '';
        $current = announcement_get_attachments($items[$index]);
        $removed = array();
        $remaining = announcement_remaining_attachments($current, array($dropId), $removed);
        $items[$index]['attachments'] = array_values($remaining);
        $items[$index]['attachment'] = null;
        $flashMsg = 'ลบไฟล์แนบแล้ว';
      }

      $items[$index]['updated_at'] = date('Y-m-d H:i:s');

      if (announcement_write_all($items)) {
        /* Save references first: a failed JSON write must not delete the original file. */
        if ($action === 'drop_cover') {
          announcement_delete_cover_image($oldDropItem, $slot);
        } else {
          announcement_delete_uploaded_files($removed);
        }
        if ($instantDrop) {
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(array('ok' => true));
          exit;
        }
        $_SESSION['announcement_flash'] = $flashMsg;
        /* กลับมาแล้วเปิดโมดัลแก้ไขของประกาศเดิมค้างไว้ ให้ทำงานต่อได้เลย */
        $_SESSION['announcement_reopen'] = array(
          'id' => $id,
          'step' => ($action === 'drop_cover' ? 2 : 1)
        );
        header('Location: announcements_admin.php#allPosts');
        exit;
      }

      $errors[] = 'ลบไม่สำเร็จ';
    }

  /* ── ภาพสไลด์หน้าแรก ── */
  } elseif (announcement_is_admin() && $action === 'hero_add') {
    $slideFile = isset($_FILES['hero_image']) ? $_FILES['hero_image'] : null;

    if (hero_create($slideFile, $errors)) {
      $_SESSION['announcement_flash'] = 'เพิ่มภาพสไลด์เรียบร้อยแล้ว';
      header('Location: announcements_admin.php#heroSlides');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'hero_delete') {
    $slideId = isset($_POST['slide_id']) ? $_POST['slide_id'] : '';

    if (hero_delete($slideId, $errors)) {
      $_SESSION['announcement_flash'] = 'ลบภาพสไลด์เรียบร้อยแล้ว';
      header('Location: announcements_admin.php#heroSlides');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'hero_move') {
    $slideId = isset($_POST['slide_id']) ? $_POST['slide_id'] : '';
    $dir = (isset($_POST['dir']) && $_POST['dir'] === 'down') ? 1 : -1;

    if (hero_move($slideId, $dir, $errors)) {
      header('Location: announcements_admin.php#heroSlides');
      exit;
    }

  /* ── ระบบงาน (Applications) ── */
  } elseif (announcement_is_admin() && ($action === 'app_add' || $action === 'app_update')) {
    $appData = array(
      'cat' => isset($_POST['app_cat']) ? $_POST['app_cat'] : '',
      'url' => isset($_POST['app_url']) ? $_POST['app_url'] : '',
      'name_th' => isset($_POST['app_name_th']) ? $_POST['app_name_th'] : '',
      'desc_th' => isset($_POST['app_desc_th']) ? $_POST['app_desc_th'] : '',
      'name_en' => isset($_POST['app_name_en']) ? $_POST['app_name_en'] : '',
      'desc_en' => isset($_POST['app_desc_en']) ? $_POST['app_desc_en'] : '',
      'name_my' => isset($_POST['app_name_my']) ? $_POST['app_name_my'] : '',
      'desc_my' => isset($_POST['app_desc_my']) ? $_POST['app_desc_my'] : ''
    );
    $iconFile = isset($_FILES['app_icon']) ? $_FILES['app_icon'] : null;

    if ($action === 'app_add') {
      $ok = app_create($appData, $iconFile, $errors);
      $flash = 'เพิ่มระบบงานเรียบร้อยแล้ว';
    } else {
      $ok = app_update(isset($_POST['app_id']) ? $_POST['app_id'] : '', $appData, $iconFile, $errors);
      $flash = 'อัปเดตระบบงานเรียบร้อยแล้ว';
    }

    if ($ok) {
      $_SESSION['announcement_flash'] = $flash;
      header('Location: announcements_admin.php#appList');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'app_delete') {
    $appId = isset($_POST['app_id']) ? $_POST['app_id'] : '';

    if (app_delete($appId, $errors)) {
      $_SESSION['announcement_flash'] = 'ลบระบบงานเรียบร้อยแล้ว';
      header('Location: announcements_admin.php#appList');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'app_cat_add') {
    $acTh = isset($_POST['app_cat_th']) ? $_POST['app_cat_th'] : '';
    $acEn = isset($_POST['app_cat_en']) ? $_POST['app_cat_en'] : '';
    $acMy = isset($_POST['app_cat_my']) ? $_POST['app_cat_my'] : '';

    if (app_category_create($acTh, $acEn, $acMy, $errors)) {
      $_SESSION['announcement_flash'] = 'เพิ่มหมวดเรียบร้อยแล้ว';
      header('Location: announcements_admin.php#appList');
      exit;
    }
  } elseif (announcement_is_admin() && $action === 'app_cat_delete') {
    $acId = isset($_POST['app_cat_id']) ? $_POST['app_cat_id'] : '';

    if (app_category_delete($acId, $errors)) {
      $_SESSION['announcement_flash'] = 'ลบหมวดเรียบร้อยแล้ว';
      header('Location: announcements_admin.php#appList');
      exit;
    }

  /* ── ทะเบียนประกาศ (อ่านจากไฟล์ Excel ที่แอดมินอัปโหลด) ── */
  } elseif (announcement_is_admin() && $action === 'register_upload') {
    $registerFile = isset($_FILES['register_file']) ? $_FILES['register_file'] : null;
    $registerError = '';
    $registerReason = '';

    /* ลองด้วยรหัสที่เคยใช้ได้ก่อน — ไฟล์ล็อกที่รหัสไม่เปลี่ยนจะผ่านเลยไม่ต้องถาม */
    $savedPassword = announcement_register_password_get();

    if (announcement_register_store_workbook($registerFile, $registerError, $savedPassword, $registerReason)) {
      $_SESSION['announcement_flash'] = 'อัปโหลดไฟล์ทะเบียนเรียบร้อยแล้ว';
      header('Location: announcements_admin.php#registerTable');
      exit;
    }

    /* ไฟล์ล็อกอยู่และรหัสที่เก็บไว้ใช้ไม่ได้ → พักไฟล์ไว้แล้วไปถามรหัส
       จะได้ไม่ต้องให้แอดมินเลือกไฟล์ใหม่ */
    if ($registerReason === 'password' && isset($registerFile['tmp_name']) && is_uploaded_file($registerFile['tmp_name'])) {
      $stashError = '';
      $binary = @file_get_contents($registerFile['tmp_name']);
      $token = $binary === false ? false : announcement_register_pending_stash($binary, $stashError);

      if ($token !== false) {
        $_SESSION['announcement_register_pending'] = array(
          'token' => $token,
          'name'  => basename($registerFile['name']),
          'tried' => $savedPassword !== ''
        );
        header('Location: announcements_admin.php#registerTable');
        exit;
      }

      $errors[] = $stashError !== '' ? $stashError : $registerError;
    } else {
      $errors[] = $registerError;
    }
  } elseif (announcement_is_admin() && $action === 'register_unlock') {
    $pending = isset($_SESSION['announcement_register_pending']) ? $_SESSION['announcement_register_pending'] : null;
    $pendingPath = $pending ? announcement_register_pending_path($pending['token']) : '';
    $typed = isset($_POST['register_password']) ? (string) $_POST['register_password'] : '';

    if ($pendingPath === '') {
      unset($_SESSION['announcement_register_pending']);
      $errors[] = 'ไฟล์ที่พักไว้หมดอายุแล้ว กรุณาอัปโหลดไฟล์ใหม่อีกครั้ง';
    } else {
      $unlockError = '';
      $unlockReason = '';
      $binary = @file_get_contents($pendingPath);

      if ($binary !== false && announcement_register_accept_workbook($binary, $typed, $unlockError, $unlockReason)) {
        announcement_register_pending_drop($pending['token']);
        unset($_SESSION['announcement_register_pending']);

        /* จำรหัสไว้ให้ครั้งหน้าอัปโหลดได้เลย — ติ๊กออกได้ถ้าไม่อยากให้เก็บ */
        if (isset($_POST['remember_password']) && $_POST['remember_password'] === '1') {
          announcement_register_password_set($typed);
        } else {
          announcement_register_password_set('');
        }

        $_SESSION['announcement_flash'] = 'ปลดล็อกและอัปโหลดไฟล์ทะเบียนเรียบร้อยแล้ว';
        header('Location: announcements_admin.php#registerTable');
        exit;
      }

      /* รหัสผิด → เปิดโมดัลค้างไว้ให้พิมพ์ใหม่ ไฟล์ยังพักอยู่ที่เดิม */
      if ($unlockReason === 'password') {
        $_SESSION['announcement_register_pending']['tried'] = true;
      } else {
        announcement_register_pending_drop($pending['token']);
        unset($_SESSION['announcement_register_pending']);
      }

      $errors[] = $unlockError !== '' ? $unlockError : 'ปลดล็อกไฟล์ไม่สำเร็จ';
    }
  } elseif (announcement_is_admin() && $action === 'register_cancel_unlock') {
    if (isset($_SESSION['announcement_register_pending'])) {
      announcement_register_pending_drop($_SESSION['announcement_register_pending']['token']);
      unset($_SESSION['announcement_register_pending']);
    }

    header('Location: announcements_admin.php#registerTable');
    exit;
  } elseif (announcement_is_admin() && $action === 'register_save') {
    $saveError = '';
    $currentRegister = announcement_register_read(true);

    if (announcement_register_save_table($currentRegister, $_POST, $saveError)) {
      $_SESSION['announcement_flash'] = 'บันทึกทะเบียนเรียบร้อยแล้ว';
      header('Location: announcements_admin.php#registerTable');
      exit;
    }

    $errors[] = $saveError;
  } elseif (announcement_is_admin() && $action === 'register_doc_upload') {
    $docError = '';
    $docFile = isset($_FILES['register_doc']) ? $_FILES['register_doc'] : null;

    if (announcement_register_store_document(isset($_POST['row_key']) ? $_POST['row_key'] : '', $docFile, $docError)) {
      $_SESSION['announcement_flash'] = 'แนบไฟล์เอกสารเรียบร้อยแล้ว';
      header('Location: announcements_admin.php#registerTable');
      exit;
    }

    $errors[] = $docError;
  } elseif (announcement_is_admin() && $action === 'register_doc_delete') {
    $docError = '';

    if (announcement_register_delete_document(isset($_POST['row_key']) ? $_POST['row_key'] : '', $docError)) {
      $_SESSION['announcement_flash'] = 'ลบไฟล์ที่แนบเรียบร้อยแล้ว';
      header('Location: announcements_admin.php#registerTable');
      exit;
    }

    $errors[] = $docError;
  } elseif (announcement_is_admin() && $action === 'register_reset_row') {
    if (announcement_register_reset_row(isset($_POST['row_key']) ? $_POST['row_key'] : '')) {
      $_SESSION['announcement_flash'] = 'คืนค่าจากไฟล์ Excel เรียบร้อยแล้ว';
      header('Location: announcements_admin.php#registerTable');
      exit;
    }

    $errors[] = 'คืนค่าไม่สำเร็จ';
  }
}

if ($instantDrop) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array('ok' => false));
  exit;
}

$isAdmin = announcement_is_admin();
$announcements = announcement_read_all();
$csrfToken = announcement_csrf_token();
$galleryImages = announcement_gallery_images();
$bgImage = count($galleryImages) > 0 ? $galleryImages[0] : '';
$showCreateForm = $isAdmin && $postedAction === 'create' && count($errors) > 0;
$categories = announcement_category_read_all();
$categoryCounts = announcement_category_counts($announcements);
$heroSlides = hero_read_all();
$appList = app_read_all();
$appCats = app_category_list();

/* ไฟล์ Excel ที่อัปโหลดมาแล้วแต่ยังไขรหัสไม่ได้ — ค้างรอให้แอดมินกรอกรหัสผ่าน */
$registerPending = null;
if ($isAdmin && isset($_SESSION['announcement_register_pending'])) {
  $pendingCheck = $_SESSION['announcement_register_pending'];

  if (announcement_register_pending_path($pendingCheck['token']) !== '') {
    $registerPending = $pendingCheck;
  } else {
    unset($_SESSION['announcement_register_pending']);
  }
}

$registerSavedPassword = $isAdmin ? announcement_register_password_get() : '';

/* ทะเบียนประกาศ — คอนโซลต้องเห็นแถวที่สั่งซ่อนด้วย จึงส่ง true */
$adminRegister = $isAdmin ? announcement_register_read(true) : null;
$adminRegisterRows = ($adminRegister && isset($adminRegister['rows'])) ? $adminRegister['rows'] : array();

$mailIt = 'Pumiput.it@supavut.com';
$loginFailed = !$isAdmin && $postedAction === 'login' && count($errors) > 0;

$uncategorizedCount = 0;
$pinnedCount = 0;
foreach ($announcements as $statItem) {
  if (announcement_category_of($statItem) === '') {
    $uncategorizedCount++;
  }
  if (announcement_is_pinned($statItem)) {
    $pinnedCount++;
  }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>ผู้ดูแลประกาศ | SUPAVUT GROUP</title>
  <link rel="icon" type="image/png" href="./img/3si.png">
  <link rel="apple-touch-icon" href="./img/pwa/icon-192.png">
  <link rel="manifest" href="./site.webmanifest">
  <meta name="theme-color" content="#064ba6">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* ══════════════════════════════════════════════════════════
       1. DESIGN TOKENS — ชุดเดียวกับหน้าแรก (index.php)
       ══════════════════════════════════════════════════════════ */
    :root {
      /* แบรนด์ */
      --navy: #064ba6;
      --navy-deep: #052a57;
      --navy-tile: #064ba6;
      --navy-ink: #052a57;
      --navy-soft: #eaf1fb;
      --green: #168642;
      --green-soft: #e6f4ec;

      /* พื้นผิว */
      --bg: #f4f6f9;
      --surface: #ffffff;
      --surface-2: #f8fafc;
      --surface-3: #eef2f7;

      /* ตัวอักษร */
      --ink: #111827;
      --ink-2: #33415c;
      --muted: #5b6778;
      --faint: #8b95a5;
      --on-navy: #ffffff;
      --on-navy-dim: rgba(255, 255, 255, 0.76);

      /* เส้น */
      --line: #e3e8ef;
      --line-2: #cfd8e3;

      /* สถานะ */
      --danger: #c0392b;
      --danger-soft: #fdecea;
      --amber: #b45309;
      --amber-soft: #fef3e2;

      /* ป้ายวันที่บนภาพปก (ชุดสีเดิมจากเวอร์ชัน 1) */
      --news-wine: #8d2b3a;
      --news-gold: #d9c374;

      /* รัศมี */
      /* ลดความมนของกรอบลง */
      --r-sm: 4px;
      --r-md: 6px;
      --r-lg: 8px;
      --r-xl: 10px;
      --r-full: 999px;

      /* เงา */
      --sh-1: 0 1px 2px rgba(17, 24, 39, 0.05);
      --sh-2: 0 2px 8px rgba(17, 24, 39, 0.06), 0 1px 2px rgba(17, 24, 39, 0.04);
      --sh-3: 0 10px 30px rgba(17, 24, 39, 0.10), 0 2px 6px rgba(17, 24, 39, 0.05);
      --sh-4: 0 24px 60px rgba(6, 40, 90, 0.18);

      /* จังหวะ */
      --shell: 1320px;
      --gut: clamp(16px, 3vw, 32px);
      --head-h: 64px;
      --screen-h: 100vh;
      --side-w: 210px;

      /* ชั้นความลึก */
      --z-sticky: 100;
      --z-dropdown: 200;

      /* จังหวะการเคลื่อนไหว */
      --ease: cubic-bezier(0.22, 1, 0.36, 1);
      --fast: 140ms;
      --base: 200ms;
    }

    /* ══════════════════════════════════════════════════════════
       2. RESET / BASE
       ══════════════════════════════════════════════════════════ */
    * { box-sizing: border-box; }

    /* กฎของเบราว์เซอร์สำหรับ [hidden] แพ้ selector ที่เป็น class
       จึงต้องบังคับไว้ ไม่งั้นกล่องที่ตั้ง display ในคลาสจะไม่ยอมซ่อน */
    [hidden] { display: none !important; }

    html {
      scroll-behavior: smooth;
      -webkit-text-size-adjust: 100%;
    }

    html, body { height: 100%; }

    body {
      margin: 0;
      height: var(--screen-h);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      background: var(--bg);
      color: var(--ink);
      font-family: "Inter", "Noto Sans Thai", "Noto Sans Myanmar", system-ui, -apple-system, "Segoe UI", sans-serif;
      font-size: 17px;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3, h4, p, figure { margin: 0; }
    img { max-width: 100%; display: block; }
    a { color: inherit; text-decoration: none; -webkit-tap-highlight-color: transparent; }

    button, input, select, textarea {
      font: inherit;
      color: inherit;
    }

    button {
      border: 0;
      background: none;
      cursor: pointer;
      -webkit-tap-highlight-color: transparent;
    }

    :focus-visible {
      outline: 2px solid var(--navy);
      outline-offset: 2px;
      border-radius: 4px;
    }

    .shell {
      width: 100%;
      max-width: var(--shell);
      margin-inline: auto;
      padding-inline: var(--gut);
    }

    /* แถบเมนูกับท้ายหน้ากางเต็มจอ — โลโก้ชิดซ้าย เครื่องมือชิดขวา */
    .site-header .shell,
    .site-footer .shell { max-width: none; padding-inline: clamp(12px, 1.6vw, 22px); }

    .sr-only {
      position: absolute;
      width: 1px; height: 1px;
      padding: 0; margin: -1px;
      overflow: hidden;
      clip: rect(0 0 0 0);
      white-space: nowrap;
    }

    /* ══════════════════════════════════════════════════════════
       3. HEADER — เหมือนหน้าแรกทุกประการ
       ══════════════════════════════════════════════════════════ */
    .site-header {
      flex: 0 0 auto;
      z-index: var(--z-sticky);
      background: var(--surface);
      border-bottom: 1px solid var(--line);
    }

    .header-row {
      display: flex;
      align-items: center;
      gap: clamp(10px, 1.6vw, 26px);
      min-height: var(--head-h);
      padding-inline: clamp(12px, 1.6vw, 22px);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 11px;
      flex: 0 0 auto;
    }

    .brand-mark {
      width: 40px;
      height: 40px;
      object-fit: contain;
    }

    .brand-name {
      display: grid;
      line-height: 1;
    }

    .brand-name b {
      color: var(--navy);
      font-size: 1.0625rem;
      font-weight: 700;
      letter-spacing: 0.05em;
    }

    .brand-name span {
      color: var(--green);
      font-size: 0.625rem;
      font-weight: 700;
      letter-spacing: 0.3em;
      margin-top: 3px;
    }

    .header-tools {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-left: auto;
      flex: 0 0 auto;
    }

    .icon-btn {
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      border-radius: var(--r-sm);
      color: var(--muted);
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .icon-btn:hover { background: var(--surface-3); color: var(--ink); }
    .icon-btn svg { width: 18px; height: 18px; }
    .icon-btn img { width: 20px; height: 20px; object-fit: contain; }

    /* เมนูป๊อปอัป (ข้อมูลองค์กร / ภาษา) */
    .pop { position: relative; flex: 0 0 auto; }

    .pop-panel {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      z-index: var(--z-dropdown);
      min-width: 210px;
      padding: 6px;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
      box-shadow: var(--sh-3);
      display: none;
    }

    .pop-panel.on { display: grid; gap: 2px; }

    .pop-label {
      padding: 6px 10px 4px;
      color: var(--faint);
      font-size: 0.6875rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .pop-item {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 9px 10px;
      border-radius: var(--r-sm);
      color: var(--ink);
      font-size: 0.875rem;
      text-align: left;
      transition: background var(--fast) var(--ease);
    }

    .pop-item:hover { background: var(--surface-3); }
    .pop-item img { width: 19px; height: 14px; object-fit: cover; border-radius: 2px; flex: 0 0 auto; }
    .pop-item svg { width: 17px; height: 17px; flex: 0 0 auto; color: var(--muted); }
    .pop-item span { flex: 1 1 auto; }

    .pop-item[aria-current="true"] {
      background: var(--navy-soft);
      color: var(--navy-ink);
      font-weight: 600;
    }

    .pop-item.danger { color: var(--danger); }
    .pop-item.danger svg { color: var(--danger); }
    .pop-item.danger:hover { background: var(--danger-soft); }

    .pop-divider {
      height: 1px;
      margin: 5px 6px;
      background: var(--line);
    }

    .lang-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      height: 28px;
      padding: 0 7px;
      border-radius: var(--r-full);
      color: var(--ink);
      transition: background var(--fast) var(--ease);
    }

    .lang-btn:hover, .lang-btn[aria-expanded="true"] { background: var(--surface-2); }
    .lang-btn img { width: 20px; height: 15px; object-fit: cover; border-radius: 3px; }
    .lang-btn svg { width: 12px; height: 12px; color: var(--muted); }

    /* ══════════════════════════════════════════════════════════
       4. หน้ากระดาษ — เป็นพื้นที่สกรอลล์เดียว header/footer ปักหมุดเสมอ
       ══════════════════════════════════════════════════════════ */
    .page {
      flex: 1 1 auto;
      min-height: 0;
      overflow: hidden;
    }

    /* หน้าล็อกอินยังต้องสกรอลล์ได้บนจอเตี้ย */
    /* หน้าล็อกอิน — ภาพสไลด์เต็มพื้นหลัง กล่องล็อกอินลอยกลางจอ */
    .page-auth {
      position: relative;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }

    /* ภาพเต็มพื้นหลัง ไม่มีแผ่นสีทับ เพราะภาพนี้เข้มพออยู่แล้ว */
    .auth-bg {
      position: absolute;
      inset: 0;
      overflow: hidden;
      background: var(--navy-deep);
    }

    .auth-bg img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
    }

    /* กล่องล็อกอินกลางจอพอดี — ไม่ตั้ง min-height เอง
       ปล่อยให้ flex ของ .page-auth แจกความสูงที่เหลือให้ แล้วจัดกึ่งกลาง
       (ตั้ง min-height ทับจะบวกซ้ำกับความสูงที่ flex ให้มา กล่องเลยเลื่อนลงล่าง) */
    .page-auth .shell {
      position: relative;
      z-index: 1;
      flex: 1 1 auto;
      min-height: 0;
      display: grid;
      place-items: center;
      padding-block: 24px;
    }

    /* แถบแจ้งเตือน */
    .alerts {
      display: grid;
      gap: 8px;
      margin-bottom: 16px;
    }

    .alert {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 14px;
      border: 1px solid var(--line);
      border-left-width: 3px;
      border-radius: var(--r-sm);
      background: var(--surface);
      font-size: 0.875rem;
      box-shadow: var(--sh-1);
    }

    .alert svg { width: 17px; height: 17px; flex: 0 0 auto; }
    .alert.ok { border-left-color: var(--green); color: var(--ink-2); }
    .alert.ok svg { color: var(--green); }
    .alert.bad { border-left-color: var(--danger); background: var(--danger-soft); color: #7f2018; }
    .alert.bad svg { color: var(--danger); }

    /* ══════════════════════════════════════════════════════════
       5. หน้าเข้าสู่ระบบ
       ══════════════════════════════════════════════════════════ */
    .auth {
      position: relative;
      width: min(100%, 460px);
      margin-inline: auto;
      padding: 54px 42px 34px;
      border-radius: var(--r-lg);
      background: var(--surface);
      box-shadow: 0 30px 70px rgba(5, 26, 54, 0.42);
      text-align: center;
    }

    .auth-mark {
      width: 52px;
      height: 52px;
      margin: 0 auto 14px;
      object-fit: contain;
    }

    .auth h1 {
      margin-bottom: 26px;
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: -0.01em;
    }

    .auth .field { margin-bottom: 16px; text-align: left; }
    .auth .field > label { font-size: 0.875rem; }

    .auth input[type="text"],
    .auth input[type="password"] {
      height: 46px;
      padding: 0 14px;
      font-size: 0.9375rem;
    }

    .auth button[type="submit"] {
      height: 46px;
      margin-top: 4px;
      font-size: 0.9375rem;
    }

    /* ช่องรหัสผ่าน + ปุ่มแสดง/ซ่อน */
    .pw { position: relative; }
    .pw input { padding-right: 38px; }

    .pw-eye {
      position: absolute;
      top: 50%;
      right: 5px;
      width: 28px;
      height: 28px;
      display: grid;
      place-items: center;
      border-radius: 6px;
      color: var(--muted);
      transform: translateY(-50%);
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .pw-eye:hover { background: var(--surface-3); color: var(--ink-2); }
    .pw-eye svg { width: 17px; height: 17px; }
    .pw-eye .eye-off { display: none; }
    .pw-eye[aria-pressed="true"] .eye-on { display: none; }
    .pw-eye[aria-pressed="true"] .eye-off { display: block; }

    /* สถานะผิดพลาด */
    .auth-error {
      display: flex;
      align-items: flex-start;
      gap: 9px;
      margin-bottom: 14px;
      padding: 9px 11px;
      text-align: left;
      border: 1px solid rgba(192, 57, 43, 0.26);
      border-radius: var(--r-sm);
      background: var(--danger-soft);
      color: #7f2018;
      font-size: 0.8125rem;
      line-height: 1.5;
    }

    .auth-error svg {
      width: 16px;
      height: 16px;
      flex: 0 0 auto;
      margin-top: 2px;
      color: var(--danger);
    }

    input[aria-invalid="true"] { border-color: rgba(192, 57, 43, 0.55); }

    input[aria-invalid="true"]:focus {
      border-color: var(--danger);
      box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.12);
    }

    /* ปุ่มขณะกำลังส่ง */
    .auth .btn-primary { height: 38px; margin-top: 4px; }
    .btn[aria-busy="true"] { pointer-events: none; opacity: 0.72; }

    .spin {
      width: 15px;
      height: 15px;
      flex: 0 0 auto;
      border: 2px solid rgba(255, 255, 255, 0.34);
      border-top-color: #ffffff;
      border-radius: 50%;
      animation: spin 620ms linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* ลิงก์กลับหน้าแรก — มุมซ้ายบนของการ์ด */
    .auth-back {
      position: absolute;
      top: 10px;
      left: 10px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 6px;
      color: var(--muted);
      font-size: 0.75rem;
      transition: color var(--fast) var(--ease);
    }

    .auth-back:hover { color: var(--navy-ink); }
    .auth-back svg { width: 15px; height: 15px; }

    @media (max-width: 460px) {
      .auth {
        padding: 30px 22px 26px;
        border: 0;
        border-radius: 0;
        background: transparent;
      }
    }

    /* ══════════════════════════════════════════════════════════
       6. คอนโซล — sidebar ชิดขอบจอ + พื้นที่ทำงานสกรอลล์ในตัว
       ══════════════════════════════════════════════════════════ */
    /* เมนูซ้ายอยู่นิ่ง — สกรอลล์เฉพาะพื้นที่เนื้อหา */
    .app {
      height: 100%;
      display: flex;
      align-items: stretch;
      overflow: hidden;
    }

    .side {
      flex: 0 0 var(--side-w);
      display: flex;
      flex-direction: column;
      gap: 2px;
      padding: 12px 10px;
      border-right: 1px solid var(--line);
      background: var(--surface-2);
      overflow-y: auto;
    }

    .side-item {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 10px 12px;
      border-radius: var(--r-sm);
      color: var(--ink-2);
      font-size: 0.9375rem;
      text-align: left;
      white-space: nowrap;
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .side-item svg { width: 18px; height: 18px; flex: 0 0 auto; color: var(--faint); }
    .side-item > span { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; }

    .side-item b {
      flex: 0 0 auto;
      min-width: 20px;
      padding: 1px 6px;
      border-radius: var(--r-full);
      background: var(--surface-3);
      color: var(--muted);
      font-size: 0.6875rem;
      font-weight: 600;
      text-align: center;
    }

    .side-item:hover { background: var(--surface-3); }

    .side-item.is-on {
      background: var(--navy-soft);
      color: var(--navy-ink);
      font-weight: 600;
    }

    .side-item.is-on svg { color: var(--navy-ink); }
    .side-item.is-on b { background: rgba(7, 58, 117, 0.14); color: var(--navy-ink); }

    .side-div { height: 1px; margin: 6px 6px; background: var(--line); }

    .work {
      flex: 1 1 auto;
      min-width: 0;
      overflow-y: auto;
    }

    /* ระยะล่างเผื่อไว้เยอะ — ไม่งั้นปุ่มแบ่งหน้าใบสุดท้ายไปติดใต้แถบท้าย
       จนเลื่อนลงไปกดไม่ถึง */
    .work-inner {
      max-width: var(--shell);
      margin-inline: auto;
      padding: clamp(10px, 1.4vw, 16px) clamp(12px, 2vw, 22px) clamp(70px, 8vw, 110px);
    }

    /* แต่ละเมนูซ้าย = หน้าแยกกัน แสดงทีละหน้า */
    .view { display: none; }
    .view.is-on { display: block; }

    .work-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 16px;
    }

    .work-head h1 {
      font-size: 1.25rem;
      font-weight: 700;
      letter-spacing: -0.01em;
    }

    /* ── การ์ดตัวเลขสรุป ── */
    .metrics {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 8px;
      margin-bottom: 12px;
    }

    .metric {
      padding: 9px 12px;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
      box-shadow: var(--sh-1);
    }

    .metric b {
      display: block;
      color: var(--navy-ink);
      font-size: 1.25rem;
      font-weight: 700;
      line-height: 1.2;
    }

    .metric span {
      color: var(--muted);
      font-size: 0.75rem;
    }

    /* ── แถบค้นหา + ตัวกรองแท็บ ── */
    .bar {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }

    .search {
      position: relative;
      flex: 0 1 260px;
    }

    .search svg {
      position: absolute;
      top: 50%;
      left: 12px;
      width: 16px;
      height: 16px;
      color: var(--faint);
      transform: translateY(-50%);
      pointer-events: none;
    }

    .search input[type="search"] {
      width: 100%;
      height: 32px;
      padding: 0 11px 0 32px;
      border: 1px solid var(--line);
      border-radius: var(--r-full);
      background: var(--surface);
      font-size: 0.8125rem;
    }

    .search input:focus {
      outline: 0;
      border-color: var(--navy);
      box-shadow: 0 0 0 3px rgba(7, 58, 117, 0.18);
    }

    .segs {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .seg {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      height: 27px;
      padding: 0 10px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface);
      color: var(--muted);
      font-size: 0.75rem;
      transition: border-color var(--fast) var(--ease), background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .seg b { color: var(--faint); font-size: 0.75rem; font-weight: 600; }
    .seg:hover { border-color: var(--line-2); color: var(--ink); }

    .seg[aria-selected="true"] {
      border-color: var(--navy);
      background: var(--navy);
      color: var(--on-navy);
      font-weight: 600;
    }

    .seg[aria-selected="true"] b { color: var(--on-navy-dim); }

    /* แถวแท็บที่สร้างไว้ — ไม่มีกรอบ/พื้นหลัง */
    .segs.is-boxed { margin-bottom: 12px; }

    .seg-wrap { display: inline-flex; align-items: center; gap: 3px; }
    .seg-wrap form { margin: 0; }

    .seg-del {
      width: 20px;
      height: 20px;
      display: inline-grid;
      place-items: center;
      border-radius: var(--r-sm);
      background: var(--surface-3);
      color: var(--danger);
      font-size: 0.8125rem;
      line-height: 1;
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .seg-del:hover { background: var(--danger); color: #ffffff; }

    /* ══════════════════════════════════════════════════════════
       7. การ์ด / ฟอร์ม
       ══════════════════════════════════════════════════════════ */
    label {
      display: block;
      margin-bottom: 6px;
      color: var(--ink-2);
      font-size: 0.8125rem;
      font-weight: 600;
    }

    input[type="text"],
    input[type="search"],
    input[type="password"],
    input[type="file"],
    textarea,
    select {
      width: 100%;
      padding: 7px 10px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface);
      font-size: 0.8125rem;
      transition: border-color var(--fast) var(--ease), box-shadow var(--fast) var(--ease);
    }

    input:focus, textarea:focus, select:focus {
      outline: 0;
      border-color: var(--navy);
      box-shadow: 0 0 0 3px rgba(7, 58, 117, 0.18);
    }

    input[type="file"] { padding: 5px 9px; color: var(--muted); font-size: 0.75rem; }

    textarea {
      min-height: 74px;
      resize: vertical;
      line-height: 1.55;
    }

    /* จำกัดความกว้างฟอร์ม ไม่ให้ช่องกรอกยาวเกินไปบนจอกว้าง */
    .create-form,
    .row-edit form,
    .cat-form { max-width: 620px; }

    input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--navy);
    }

    .field { margin-bottom: 11px; }

    .hint {
      margin-top: 5px;
      color: var(--muted);
      font-size: 0.75rem;
    }


    .grid-2 {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    /* แท็บภาษาในฟอร์ม */
    .lang-tabs {
      display: inline-flex;
      gap: 2px;
      margin-bottom: 14px;
      padding: 3px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface-2);
    }

    .lang-tab {
      min-width: 48px;
      height: 28px;
      border-radius: 6px;
      color: var(--muted);
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .lang-tab[aria-selected="true"] {
      background: var(--navy);
      color: var(--on-navy);
      box-shadow: var(--sh-1);
    }

    .lang-panel { display: none; }
    .lang-panel.is-on { display: block; }

    .check {
      display: flex;
      align-items: center;
      gap: 9px;
      margin-bottom: 14px;
      padding: 10px 12px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface-2);
      color: var(--ink-2);
      font-size: 0.8125rem;
      font-weight: 500;
      cursor: pointer;
    }

    .check span { flex: 1 1 auto; }

    /* ปุ่ม */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      height: 32px;
      padding: 0 13px;
      border: 1px solid transparent;
      border-radius: var(--r-sm);
      font-size: 0.8125rem;
      font-weight: 600;
      white-space: nowrap;
      transition: background var(--fast) var(--ease), border-color var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .btn svg { width: 16px; height: 16px; }

    .btn-primary { background: var(--navy); color: var(--on-navy); }
    .btn-primary:hover { background: var(--navy-deep); }

    .btn-ghost {
      border-color: var(--line);
      background: var(--surface);
      color: var(--ink-2);
    }

    .btn-ghost:hover { border-color: var(--line-2); background: var(--surface-3); }

    .btn-danger {
      border-color: var(--line);
      background: var(--surface);
      color: var(--danger);
    }

    .btn-danger:hover { border-color: var(--danger); background: var(--danger-soft); }

    .btn-sm { height: 27px; padding: 0 10px; font-size: 0.75rem; }
    .btn.is-icon { width: 30px; padding: 0; flex: 0 0 auto; }
    .btn-block { width: 100%; }

    /* ══════════════════════════════════════════════════════════
       8. รายการประกาศ
       ══════════════════════════════════════════════════════════ */
    .rows { display: grid; gap: 6px; }

    .row {
      position: relative;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
      box-shadow: var(--sh-1);
      transition: border-color var(--fast) var(--ease), box-shadow var(--fast) var(--ease);
    }

    .row:hover { border-color: var(--navy); box-shadow: var(--sh-2); }
    .row.is-pinned { border-left: 3px solid var(--navy); }
    .row.is-paged-out, .row.is-filtered { display: none; }

    .row-main {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 10px 12px;
    }

    /* ปักหมุด — ติดมุมขวาบนของการ์ด */
    .row-pin {
      position: absolute;
      top: 0;
      right: 2px;
      z-index: 2;
    }

    /* คอลัมน์ลำดับ */
    .row-lead {
      flex: 0 0 auto;
      display: grid;
      justify-items: center;
      width: 22px;
      padding-top: 1px;
    }

    .row-thumb {
      position: relative;
      width: 152px;
      height: 114px;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      overflow: hidden;
      border: 1px solid var(--line);
      background: var(--surface-3);
      color: var(--faint);
    }

    .row-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .row-thumb svg { width: 30px; height: 30px; }

    /* ป้ายวันที่แขวนมุมซ้ายบนของภาพปก */
    .row-date {
      position: absolute;
      top: 0;
      left: 0;
      z-index: 1;
      display: grid;
      place-items: center;
      align-content: center;
      width: 34px;
      min-height: 34px;
      padding: 3px 2px;
      background: var(--navy);
      color: #ffffff;
    }

    .row-date strong {
      color: #ffffff;
      font-size: 0.8125rem;
      font-weight: 800;
      line-height: 1;
    }

    .row-date span {
      font-size: 0.5rem;
      font-weight: 700;
      line-height: 1.2;
    }

    .row-body {
      flex: 1 1 auto;
      min-width: 0;
      display: flex;
      flex-direction: column;
      min-height: 114px;
    }

    /* เว้นที่ให้ปุ่มปักหมุดมุมขวาบน — เฉพาะบรรทัดบน */
    .row-meta, .row-body h3, .row-body p { padding-right: 48px; }

    /* ลำดับที่ของประกาศ */
    .row-no {
      color: var(--faint);
      font-size: 0.75rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
    }

    .row-meta {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 2px;
      color: var(--faint);
      font-size: 0.6875rem;
      letter-spacing: 0.01em;
    }

    .row-body h3 {
      margin: 0 0 4px;
      display: -webkit-box;
      overflow: hidden;
      color: var(--ink);
      font-size: 0.9375rem;
      font-weight: 600;
      line-height: 1.4;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    .row-body p {
      display: -webkit-box;
      overflow: hidden;
      color: var(--muted);
      font-size: 0.8125rem;
      line-height: 1.5;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    /* แถวล่างการ์ด — ปุ่มชิดซ้าย ป้ายกำกับชิดขวา */
    .row-foot {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: auto;
      padding-top: 8px;
    }

    /* ป้ายท้ายการ์ด — ข้อความเปล่า ไม่มีกรอบ */
    .row-flags {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      align-items: center;
      gap: 10px;
      margin-left: auto;
      color: var(--faint);
      font-size: 0.6875rem;
      line-height: 1.4;
    }

    .row-flags:empty { display: none; }
    .row-flags [data-cat-tag] { color: var(--navy-ink); font-weight: 600; }
    .row-flags .flag-new { color: var(--green); font-weight: 600; }

    .tags {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      margin-top: 8px;
    }

    .tag {
      display: inline-flex;
      align-items: center;
      height: 21px;
      padding: 0 8px;
      border-radius: var(--r-full);
      background: var(--surface-3);
      color: var(--muted);
      font-size: 0.6875rem;
      font-weight: 500;
    }

    .tag.tag-cat { background: var(--navy-soft); color: var(--navy-ink); font-weight: 600; }
    .tag.tag-none { background: var(--surface-3); color: var(--faint); }
    .tag.tag-pin { background: var(--amber-soft); color: var(--amber); font-weight: 600; }
    .tag.tag-new { background: var(--green-soft); color: var(--green); font-weight: 600; }

    /* แก้ไข / ลบ — ชิดซ้ายของแถวล่าง */
    .row-tools {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .row-tools form { margin: 0; }

    .pin-form { margin: 0; }

    .pin-btn {
      width: 42px;
      height: 42px;
      display: grid;
      place-items: center;
      border: 0;
      background: transparent;
      color: var(--line-2);
      transition: color var(--fast) var(--ease);
    }

    .pin-btn svg { width: 42px; height: 42px; }

    /* ยังไม่ปักหมุด = ไอคอนโปร่ง · ปักหมุดแล้ว = ทึบ */
    .pin-btn .pin-fill { fill: transparent; }
    .pin-btn:hover { color: var(--navy); }
    .pin-btn.is-on { color: var(--navy); }
    .pin-btn.is-on .pin-fill { fill: currentColor; }

    .row-acts {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 0 12px 10px 132px;
    }

    .row-acts form { margin: 0; }

    .row-edit {
      display: none;
      padding: 18px 16px;
      border-top: 1px solid var(--line);
      background: var(--surface-2);
      border-radius: 0 0 var(--r-md) var(--r-md);
    }

    .row-edit.is-on { display: block; }

    .row-edit h4 {
      margin-bottom: 12px;
      font-size: 0.8125rem;
      font-weight: 700;
    }

    .row-edit input[type="text"],
    .row-edit textarea,
    .row-edit select,
    .row-edit input[type="file"] { background: var(--surface); }

    /* ไฟล์แนบเดิม / ภาพปกเดิม */
    .assets { display: grid; gap: 6px; }

    .asset {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 0;
      padding: 8px 10px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface);
      font-weight: 400;
      cursor: pointer;
    }

    .asset span {
      flex: 1 1 auto;
      min-width: 0;
      overflow: hidden;
      color: var(--ink-2);
      font-size: 0.8125rem;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .asset img { width: 26px; height: 26px; flex: 0 0 auto; object-fit: contain; }
    .asset .cover-thumb { width: 74px; height: 46px; object-fit: cover; margin: 0; }

    .drop-form { display: none; }

    /* ปุ่มถังขยะ — กดแล้วลบทันที */
    /* ปุ่มถังขยะ — สีแดง ไม่มีกรอบ */
    .asset-del {
      flex: 0 0 auto;
      width: 26px;
      height: 26px;
      display: grid;
      place-items: center;
      border: 0;
      border-radius: 6px;
      background: transparent;
      color: var(--danger);
      transition: background var(--fast) var(--ease);
    }

    .asset-del svg { width: 16px; height: 16px; }
    .asset-del:hover { background: var(--danger-soft); }

    /* ไฟล์ที่เพิ่งเลือก (ยังไม่บันทึก) */
    .asset.is-new {
      border-style: dashed;
      border-color: var(--line-2);
      background: var(--surface);
    }

    /* รูปย่อของไฟล์แนบที่เป็นภาพ — กดดูได้ */
    .asset-thumb {
      width: 40px;
      height: 40px;
      flex: 0 0 auto;
      overflow: hidden;
      padding: 0;
      border: 1px solid var(--line);
      border-radius: 6px;
      background: var(--surface-3);
      transition: border-color var(--fast) var(--ease);
    }

    .asset-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .asset-thumb:hover { border-color: var(--navy); }

    /* ══════════════════════════════════════════════════════════
       ตัวอย่างไฟล์ที่เพิ่งเลือก (ทุกช่องอัปโหลดภาพ)
       ══════════════════════════════════════════════════════════ */
    .pick-row {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .pick-row input[type="file"] { flex: 1 1 auto; min-width: 0; }

    /* ── ฟอร์มแบบ 2 ขั้น ── */
    .wz-step { display: none; }
    .wz-step.is-on { display: block; }

    /* รูปภาพ / ไฟล์แนบ คนละบรรทัด */
    .media-stack { display: grid; grid-template-columns: minmax(0, 1fr); gap: 14px; }

    /* รูปภาพที่เลือก — ไทล์เล็ก มีกากบาทมุมขวาบน */
    .tile-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 8px;
    }

    .tile {
      position: relative;
      width: 76px;
      height: 76px;
      overflow: hidden;
      border-radius: var(--r-sm);
      background: var(--surface-3);
    }

    .tile img { width: 100%; height: 100%; object-fit: cover; }

    /* ตัวอย่างไอคอน — การ์ดเล็กกว่าปกติ และไม่ครอปโลโก้ */
    .is-icon-pick .tile { width: 54px; height: 54px; border: 1px solid var(--line); background: var(--surface); }
    .is-icon-pick .tile img { object-fit: contain; padding: 5px; }
    .is-icon-pick .tile-x { width: 17px; height: 17px; top: 2px; right: 2px; font-size: 0.6875rem; }

    /* ไทล์เอกสาร — ไอคอนกลางกล่อง มีชื่อไฟล์ใต้ไทล์ */
    .tile-item { width: 76px; }

    .tile.is-doc {
      display: grid;
      place-items: center;
      border: 1px solid var(--line);
      background: var(--surface-2);
    }

    .tile.is-doc img { width: 44px; height: 44px; object-fit: contain; }

    .tile-cap {
      display: block;
      margin-top: 4px;
      overflow: hidden;
      color: var(--muted);
      font-size: 0.625rem;
      line-height: 1.3;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .tile-open {
      position: absolute;
      inset: 0;
      padding: 0;
      border: 0;
      background: none;
      cursor: zoom-in;
    }

    .tile-x {
      position: absolute;
      top: 3px;
      right: 3px;
      z-index: 2;
      width: 20px;
      height: 20px;
      display: grid;
      place-items: center;
      border: 0;
      border-radius: 50%;
      background: rgba(9, 12, 18, 0.66);
      color: #ffffff;
      font-size: 0.8125rem;
      line-height: 1;
      transition: background var(--fast) var(--ease);
    }

    .tile-x:hover { background: var(--danger); }

    /* รูปภาพ/ไฟล์แนบ — ไม่ต้องมีกรอบ */
    .media-stack .asset {
      border: 0;
      padding: 4px 0;
      background: none;
    }

    /* บันทึกเต็มความกว้าง · ย้อนกลับอยู่ใต้ ไม่มีกรอบ */
    .wz-acts { display: grid; gap: 8px; }

    .wz-back {
      justify-self: center;
      padding: 4px 10px;
      border: 0;
      background: none;
      color: var(--muted);
      font-size: 0.8125rem;
      transition: color var(--fast) var(--ease);
    }

    .wz-back:hover { color: var(--navy-ink); text-decoration: underline; }

    /* หัวข้อย่อยในฟอร์ม */
    .sec-label {
      margin: 18px 0 10px;
      padding-top: 14px;
      border-top: 1px solid var(--line);
      color: var(--faint);
      font-size: 0.6875rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    /* ป้ายบอกสัดส่วน/ปลายทาง ต่อท้าย label */
    label .tag-ratio {
      margin-left: 6px;
      padding: 1px 6px;
      border-radius: 3px;
      background: var(--surface-3);
      color: var(--muted);
      font-size: 0.625rem;
      font-weight: 600;
      letter-spacing: 0;
    }

    /* ภาพปก 1 และ 2 คนละบรรทัด */
    .cover-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr);
      gap: 14px;
      align-items: start;
    }

    /* กันแถวขยับตอนแนบภาพ — จองที่ว่างไว้ล่วงหน้า */
    .cover-row .field [data-cover-preview]:not([hidden]) {
      min-height: 96px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 8px;
      border: 1px dashed var(--line-2);
      border-radius: var(--r-sm);
      background: var(--surface-2);
    }

    /* มีภาพแล้ว — ตัดกรอบประ ให้ภาพเต็มกล่อง */
    .cover-row .field [data-cover-preview].has-img:not([hidden]) {
      min-height: 0;
      padding: 0;
      border: 0;
      background: none;
      display: block;
    }

    .cover-row .field [data-cover-preview]:empty::after {
      content: "";
      width: 26px;
      height: 26px;
      background: currentColor;
      color: var(--line-2);
      -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.7'%3E%3Crect x='3.5' y='5' width='17' height='14' rx='1.5'/%3E%3Cpath d='m4 16 4.5-4.5L13 16l3-3 4 4'/%3E%3C/svg%3E") center/contain no-repeat;
      mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.7'%3E%3Crect x='3.5' y='5' width='17' height='14' rx='1.5'/%3E%3Cpath d='m4 16 4.5-4.5L13 16l3-3 4 4'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    @media (max-width: 720px) {
      .cover-row { grid-template-columns: minmax(0, 1fr); }
    }

    /* การ์ดภาพปก — ไม่มีกรอบ มีกากบาทมุมขวาบน */
    .cover-slots { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }

    .cover-card {
      position: relative;
      width: 100%;
      max-width: 240px;
    }

    .cover-shot {
      display: block;
      width: 100%;
      padding: 0;
      border: 0;
      border-radius: var(--r-sm);
      overflow: hidden;
      background: var(--surface-3);
      cursor: zoom-in;
    }

    .cover-shot img { width: 100%; display: block; }

    /* ── ทำเครื่องหมาย "จะลบ" — ลบจริงตอนกดอัปเดต กดซ้ำเพื่อเลิกทำ ── */
    .is-dropping { position: relative; }
    .is-dropping .cover-shot img,
    .is-dropping img,
    .is-dropping .asset-tag { opacity: 0.28; filter: grayscale(1); }
    /* ชื่อไฟล์อยู่นอก .tile จึงต้องเลือกแบบพี่น้อง */
    .tile.is-dropping ~ .tile-cap { color: var(--danger); text-decoration: line-through; }

    .cover-card.is-dropping,
    .tile.is-dropping { outline: 2px solid var(--danger); outline-offset: -2px; }

    /* ป้าย "จะลบ" ทับกลางภาพ ให้เห็นชัดว่ายังไม่ได้ลบจริง */
    .is-dropping::after {
      content: attr(data-drop-note);
      position: absolute;
      inset: auto 0 0 0;
      z-index: 3;
      padding: 2px 4px;
      background: var(--danger);
      color: #ffffff;
      font-size: 0.5625rem;
      font-weight: 600;
      text-align: center;
      pointer-events: none;
    }

    .cover-card.is-dropping .cover-x,
    .tile.is-dropping .tile-x { background: var(--danger); color: #ffffff; }

    .cover-x {
      position: absolute;
      top: 4px;
      right: 4px;
      z-index: 2;
      width: 22px;
      height: 22px;
      display: grid;
      place-items: center;
      border: 0;
      border-radius: 50%;
      background: rgba(9, 12, 18, 0.62);
      color: #ffffff;
      font-size: 0.875rem;
      line-height: 1;
      transition: background var(--fast) var(--ease);
    }

    .cover-x:hover { background: var(--danger); }

    .cover-name {
      margin-top: 4px;
      color: var(--muted);
      font-size: 0.6875rem;
    }

    /* ══════════════════════════════════════════════════════════
       โมดัลยืนยันการบันทึก
       ══════════════════════════════════════════════════════════ */
    .confirm {
      position: fixed;
      inset: 0;
      z-index: 700;
      display: grid;
      place-items: center;
      padding: 16px;
      background: rgba(9, 12, 18, 0.55);
    }

    .confirm[hidden] { display: none; }

    .confirm-card {
      width: min(100%, 300px);
      padding: 22px 20px 18px;
      border-radius: var(--r-lg);
      background: var(--surface);
      box-shadow: var(--sh-4);
      text-align: center;
    }

    /* เครื่องหมายถูก — วาดเส้นทีละนิด */
    .confirm-mark { display: block; width: 54px; height: 54px; margin: 0 auto 12px; }
    .confirm-mark svg { width: 100%; height: 100%; }

    .cm-ring {
      stroke: var(--green);
      stroke-width: 2.5;
      stroke-dasharray: 145;
      stroke-dashoffset: 145;
      animation: cmRing 420ms var(--ease) forwards;
    }

    .cm-tick {
      stroke: var(--green);
      stroke-width: 4;
      stroke-linecap: round;
      stroke-linejoin: round;
      stroke-dasharray: 40;
      stroke-dashoffset: 40;
      animation: cmTick 300ms var(--ease) 320ms forwards;
    }

    @keyframes cmRing { to { stroke-dashoffset: 0; } }
    @keyframes cmTick { to { stroke-dashoffset: 0; } }

    .confirm-text {
      margin-bottom: 18px;
      font-size: 0.9375rem;
      font-weight: 600;
    }

    /* ยืนยันซ้าย ยกเลิกขวา */
    .confirm-acts {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }

    .confirm-acts.is-single { grid-template-columns: 1fr; }

    /* ผลลัพธ์ล้มเหลว — กากบาทสีแดง */
    .confirm-mark.is-bad .cm-ring,
    .confirm-mark.is-bad .cm-tick { stroke: var(--danger); }
    .confirm-mark.is-bad .cm-tick { stroke-dasharray: 46; stroke-dashoffset: 46; }

    /* ══════════════════════════════════════════════════════════
       โมดัลดูภาพขยาย
       ══════════════════════════════════════════════════════════ */
    .zoombox {
      position: fixed;
      inset: 0;
      z-index: 600;
      display: grid;
      place-items: center;
      padding: 10px;
      background: rgba(9, 12, 18, 0.94);
    }

    .zoombox[hidden] { display: none; }

    .zb-stage {
      margin: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      max-width: 100%;
      max-height: 100%;
    }

    .zb-stage img {
      max-width: 96vw;
      max-height: calc(var(--screen-h) - 90px);
      object-fit: contain;
      border-radius: var(--r-sm);
      transform-origin: center center;
      transition: transform 120ms var(--ease);
      cursor: zoom-in;
    }

    .zb-stage img.is-zoomed { cursor: grab; }
    .zb-stage img.is-dragging { cursor: grabbing; transition: none; }

    .zb-stage figcaption {
      color: rgba(255, 255, 255, 0.82);
      font-size: 0.8125rem;
      text-align: center;
    }

    .zb-close {
      position: absolute;
      top: clamp(10px, 2vw, 20px);
      right: clamp(10px, 2vw, 20px);
      width: 36px;
      height: 36px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.14);
      color: #fff;
    }

    .zb-close:hover { background: rgba(255, 255, 255, 0.28); }
    .zb-close svg { width: 17px; height: 17px; }

    /* ปุ่มดาวน์โหลดในโมดัลดูภาพ — โผล่เฉพาะภาพที่อนุญาตให้ดาวน์โหลด */
    .zb-dl {
      position: absolute;
      top: clamp(10px, 2vw, 20px);
      right: calc(clamp(10px, 2vw, 20px) + 44px);
      height: 36px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 0 14px;
      border-radius: var(--r-full);
      background: rgba(255, 255, 255, 0.14);
      color: #ffffff;
      font-size: 0.75rem;
      font-weight: 600;
      text-decoration: none;
    }

    .zb-dl:hover { background: rgba(255, 255, 255, 0.28); color: #ffffff; }
    .zb-dl[hidden] { display: none; }
    .zb-dl svg { width: 16px; height: 16px; flex: 0 0 auto; }

    .zb-hint {
      position: absolute;
      bottom: clamp(10px, 2vw, 18px);
      left: 50%;
      transform: translateX(-50%);
      padding: 4px 12px;
      border-radius: var(--r-full);
      background: rgba(255, 255, 255, 0.14);
      color: rgba(255, 255, 255, 0.82);
      font-size: 0.75rem;
      pointer-events: none;
    }

    /* ══════════════════════════════════════════════════════════
       โมดัลครอปภาพปก
       ══════════════════════════════════════════════════════════ */
    .cropbox {
      position: fixed;
      inset: 0;
      z-index: 620;
      display: grid;
      place-items: center;
      padding: clamp(10px, 2vw, 28px);
      background: rgba(9, 12, 18, 0.9);
    }

    .cropbox[hidden] { display: none; }

    .cb-panel {
      width: min(100%, 760px);
      max-height: 100%;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      border-radius: var(--r-lg);
      background: var(--surface);
    }

    .cb-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 12px 14px;
      border-bottom: 1px solid var(--line);
    }

    .cb-head h2 { font-size: 0.875rem; font-weight: 700; }

    .cb-x {
      width: 28px;
      height: 28px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      color: var(--muted);
    }

    .cb-x:hover { background: var(--surface-3); color: var(--ink); }
    .cb-x svg { width: 16px; height: 16px; }

    .cb-stage {
      flex: 1 1 auto;
      min-height: 0;
      display: grid;
      place-items: center;
      padding: 14px;
      background: #1b2330;
    }

    /* กรอบล็อกสัดส่วน — ภาพเลื่อน/ซูมอยู่ข้างใน */
    .cb-frame {
      position: relative;
      width: min(100%, 560px);
      overflow: hidden;
      border-radius: var(--r-sm);
      background: #0d131c;
      cursor: grab;
      user-select: none;
      touch-action: none;
    }

    .cb-frame.is-dragging { cursor: grabbing; }

    /* กรอบไอคอน — พื้นลายตารางให้เห็นส่วนโปร่งใสของโลโก้ และแคบลงเพราะเป็นจัตุรัส */
    .cb-frame.is-icon {
      width: min(100%, 320px);
      background-color: #ffffff;
      background-image:
        linear-gradient(45deg, #dde3ec 25%, transparent 25%, transparent 75%, #dde3ec 75%),
        linear-gradient(45deg, #dde3ec 25%, transparent 25%, transparent 75%, #dde3ec 75%);
      background-size: 16px 16px;
      background-position: 0 0, 8px 8px;
    }

    /* ไอคอนไม่ต้องใช้เส้นแบ่งสามส่วน เหลือแค่ขอบกรอบ */
    .cb-frame.is-icon .cb-grid {
      background-image: none;
      box-shadow: inset 0 0 0 2px rgba(6, 75, 166, 0.55);
    }

    .cb-frame img {
      position: absolute;
      top: 50%;
      left: 50%;
      max-width: none;
      transform-origin: center center;
      pointer-events: none;
    }

    /* เส้นตารางช่วยจัดองค์ประกอบ */
    .cb-grid {
      position: absolute;
      inset: 0;
      pointer-events: none;
      background-image:
        linear-gradient(to right, rgba(255, 255, 255, 0.28) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.28) 1px, transparent 1px);
      background-size: 33.333% 33.333%;
      box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.7);
    }

    .cb-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
      padding: 11px 14px;
      border-top: 1px solid var(--line);
    }

    .cb-zoom {
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1 1 200px;
      color: var(--muted);
    }

    .cb-zoom svg { width: 15px; height: 15px; flex: 0 0 auto; }
    .cb-zoom input[type="range"] { flex: 1 1 auto; min-width: 0; accent-color: var(--navy); }
    .cb-actions { display: flex; gap: 6px; }

    .asset-tag {
      width: 26px;
      height: 26px;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      border-radius: 5px;
      background: var(--navy-soft);
      color: var(--navy-ink);
      font-size: 0.5625rem;
      font-weight: 700;
    }

    .cover-thumb {
      width: 148px;
      height: 92px;
      margin-bottom: 8px;
      object-fit: cover;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
    }

    /* ว่างเปล่า */
    .blank {
      padding: 40px 24px;
      border: 1px dashed var(--line-2);
      border-radius: var(--r-md);
      background: var(--surface);
      color: var(--muted);
      font-size: 0.875rem;
      text-align: center;
    }

    /* ══════════════════════════════════════════════════════════
       ทะเบียนประกาศ — ตารางแบบสเปรดชีต
       ══════════════════════════════════════════════════════════ */
    /* ป้ายแทนปุ่มลบของประกาศระบบ */
    .row-locked {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      height: 30px;
      padding: 0 11px;
      border: 1px solid var(--line-2);
      border-radius: var(--r-sm);
      background: var(--surface-2);
      color: var(--faint);
      font-size: 0.8125rem;
      font-weight: 600;
    }

    .row-locked svg { width: 14px; height: 14px; }

    .work-note { margin: 4px 0 0; color: var(--muted); font-size: 0.8125rem; }
    .reg-empty { margin-top: 4px; }

    .reg-bar {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 12px;
    }

    .reg-bulk { display: flex; gap: 6px; }

    .reg-find {
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1 1 200px;
      min-width: 0;
      height: 36px;
      padding: 0 12px;
      border: 1px solid var(--line-2);
      border-radius: var(--r-sm);
      background: var(--surface);
    }

    .reg-find svg { width: 16px; height: 16px; flex: 0 0 auto; color: var(--muted); }

    .reg-find input {
      flex: 1 1 auto;
      min-width: 0;
      border: 0;
      background: none;
      font-size: 0.875rem;
      outline: none;
    }

    /* ตารางกว้างกว่าจอ — เลื่อนในกรอบตัวเอง หัวตารางค้างไว้ */
    .reg-wrap {
      max-height: 62vh;
      overflow: auto;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
    }

    /* min-width กันคอลัมน์ถูกบีบจนตัวเลขวันที่ขาด — แคบกว่านี้ให้เลื่อนแนวนอนแทน */
    .reg-table {
      width: 100%;
      min-width: 1234px;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 0.8125rem;
      white-space: nowrap;
    }

    .reg-table th {
      position: sticky;
      top: 0;
      z-index: 2;
      padding: 10px 12px;
      border-bottom: 1px solid var(--line-2);
      border-right: 1px solid var(--line);
      background: var(--surface-2);
      color: var(--muted);
      font-size: 0.75rem;
      font-weight: 700;
      text-align: left;
    }

    .reg-table td {
      padding: 9px 12px;
      border-bottom: 1px solid var(--line);
      border-right: 1px solid var(--line);
      vertical-align: middle;
    }

    .reg-table th:last-child,
    .reg-table td:last-child { border-right: 0; }
    .reg-table tbody tr:last-child td { border-bottom: 0; }
    .reg-table tbody tr:hover td { background: var(--navy-soft); }

    /* แถวที่สั่งซ่อน — จางลงให้เห็นชัดว่าไม่ขึ้นหน้าเว็บ */
    .reg-table tr.is-off td { background: var(--surface-2); color: var(--faint); }
    .reg-table tr.is-off:hover td { background: var(--surface-3); }
    .reg-table tr[data-reg-row].is-filtered { display: none; }

    .reg-table td { padding: 4px 6px; }
    .reg-table th { padding: 9px 10px; }

    .reg-c-show { width: 52px; text-align: center; }
    .reg-c-seq { width: 62px; }
    .reg-c-no { width: 98px; }
    .reg-c-sub { width: 252px; white-space: normal; }
    .reg-c-to, .reg-c-by { width: 122px; }
    .reg-c-dep { width: 100px; }
    .reg-c-date { width: 110px; }
    .reg-c-link { width: 316px; }

    /* ── ช่องติ๊ก แสดง/ซ่อน ──
       ใช้ checkbox จริงแต่งด้วย appearance:none · ห้ามซ่อนด้วย position:absolute
       เพราะโฟกัสจะวิ่งไปที่ตัวที่ถูกดันออกนอกจอ แล้วเบราว์เซอร์เลื่อนหน้าตามทันที
       (อาการเดิม: ติ๊กแถวท้าย ๆ แล้วตารางกระโดด) */
    .reg-check {
      appearance: none;
      -webkit-appearance: none;
      width: 18px;
      height: 18px;
      margin: 0;
      border: 1.5px solid var(--line-2);
      border-radius: 4px;
      background: var(--surface);
      cursor: pointer;
      vertical-align: middle;
      transition: background 120ms, border-color 120ms;
    }

    .reg-check:checked {
      border-color: var(--navy);
      background: var(--navy) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none'%3E%3Cpath d='m5 12.5 4.5 4.5L19 7.5' stroke='%23fff' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center / 12px 12px no-repeat;
    }

    .reg-check:focus-visible { outline: 2px solid var(--navy); outline-offset: 2px; }

    /* ── ช่องแก้ข้อมูลในตาราง — ดูเหมือนเซลล์ธรรมดาจนกว่าจะโฟกัส ── */
    .reg-in {
      width: 100%;
      min-width: 0;
      padding: 5px 7px;
      border: 1px solid transparent;
      border-radius: var(--r-sm);
      background: transparent;
      color: inherit;
      font: inherit;
      font-size: 0.8125rem;
    }

    .reg-in:hover { border-color: var(--line); background: var(--surface); }

    .reg-in:focus {
      border-color: var(--navy);
      background: var(--surface);
      outline: none;
      box-shadow: 0 0 0 2px rgba(6, 75, 166, 0.12);
    }

    .reg-in-mid { text-align: center; }

    .reg-in-area {
      resize: vertical;
      min-height: 34px;
      line-height: 1.45;
      white-space: normal;
    }

    .reg-c-sub .reg-in { font-weight: 600; }

    /* ช่องลิงก์ — อ่านอย่างเดียว โทนเทา บอกให้รู้ว่าแก้ที่ไฟล์ Excel เท่านั้น */
    .reg-link-cell { display: flex; align-items: center; gap: 3px; }

    .reg-link-in {
      font-size: 0.75rem;
      color: var(--muted);
      background: var(--surface-2);
      border-color: var(--line);
      cursor: default;
    }

    .reg-link-in:hover, .reg-link-in:focus {
      border-color: var(--line);
      background: var(--surface-2);
      box-shadow: none;
    }

    .reg-link-in::placeholder { color: var(--faint); font-style: italic; }

    .reg-doc-ico { flex: 0 0 auto; display: grid; place-items: center; color: var(--faint); }
    .reg-doc-ico svg { width: 15px; height: 15px; }

    .reg-open {
      flex: 0 0 auto;
      width: 28px;
      height: 28px;
      display: grid;
      place-items: center;
      border: 1px solid var(--line-2);
      border-radius: var(--r-sm);
      background: var(--surface);
      color: var(--navy);
      cursor: pointer;
    }

    .reg-open svg { width: 14px; height: 14px; }
    .reg-open:hover:not(:disabled) { border-color: var(--navy); background: var(--navy-soft); }
    .reg-open:disabled { color: var(--line-2); cursor: default; }

    /* ปุ่มคลิปหนีบ — เขียวเมื่อแถวนี้มีไฟล์ที่แอดมินแนบไว้แล้ว */
    .reg-doc-btn { color: var(--faint); }
    .reg-doc-btn:hover { border-color: var(--navy); background: var(--navy-soft); color: var(--navy); }

    .reg-link-cell.has-doc .reg-doc-btn,
    .reg-link-cell.has-doc .reg-doc-ico { color: var(--green); }
    .reg-link-cell.has-doc .reg-doc-btn { border-color: var(--green); background: var(--green-soft); }
    .reg-link-cell.has-doc .reg-link-in { color: var(--ink-2); }

    /* โมดัลแนบเอกสารรายแถว */
    .reg-doc-subject {
      margin-bottom: 12px;
      padding: 8px 10px;
      border-radius: var(--r-sm);
      background: var(--surface-2);
      color: var(--ink-2);
      font-size: 0.75rem;
    }

    .reg-doc-now {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 14px;
      padding: 8px 10px;
      border: 1px solid var(--green);
      border-radius: var(--r-sm);
      background: var(--green-soft);
    }

    /* โมดัลใส่รหัสผ่านไฟล์ Excel */
    .reg-pass-file {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 14px;
      padding: 9px 11px;
      border: 1px solid var(--amber);
      border-radius: var(--r-sm);
      background: var(--amber-soft);
      color: var(--amber);
      font-size: 0.75rem;
      font-weight: 600;
    }

    .reg-pass-file svg { width: 16px; height: 16px; flex: 0 0 auto; }

    .reg-pass-file span {
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .reg-pass-cancel { margin-top: 8px; }

    .reg-doc-now[hidden] { display: none; }
    .reg-doc-now form { margin: 0; }

    .reg-doc-now-name {
      min-width: 0;
      overflow: hidden;
      color: var(--ink);
      font-size: 0.75rem;
      font-weight: 600;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* แบ่งหน้า */
    .pager {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-wrap: wrap;
      gap: 5px;
      margin-top: 14px;
    }

    .pager:empty { display: none; }

    .pager button {
      width: 28px;
      height: 28px;
      display: grid;
      place-items: center;
      border: 1px solid var(--line);
      border-radius: 50%;
      background: var(--surface);
      color: var(--muted);
      font-size: 0.75rem;
      font-weight: 600;
      transition: background var(--fast) var(--ease), border-color var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .pager button:hover:not(:disabled) { border-color: var(--line-2); color: var(--ink); }

    .pager .pg-num[aria-current="true"] {
      border-color: var(--navy);
      background: var(--navy);
      color: var(--on-navy);
    }

    .pager .pg-nav svg { width: 14px; height: 14px; }

    .pager button:disabled {
      opacity: 0.4;
      cursor: default;
    }

    /* ══════════════════════════════════════════════════════════
       9. ขั้นตอน 1 / 2 / 3 + ตารางแท็บ
       ══════════════════════════════════════════════════════════ */
    .step {
      padding: 14px 16px;
      border: 1px solid var(--line);
      border-radius: var(--r-md);
      background: var(--surface);
      box-shadow: var(--sh-1);
    }

    .step + .step { margin-top: 10px; }

    .step-head {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
    }

    .step-no {
      flex: 0 0 auto;
      width: 19px;
      height: 19px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: var(--navy);
      color: var(--on-navy);
      font-size: 0.6875rem;
      font-weight: 700;
    }

    .step-head-text { flex: 1 1 auto; min-width: 0; }
    .step-head-text h2 { font-size: 0.8125rem; font-weight: 700; color: var(--navy); }
    .step-head-text p { margin-top: 1px; color: var(--muted); font-size: 0.75rem; }
    .step-head .btn { flex: 0 0 auto; }

    /* ══════════════════════════════════════════════════════════
       โมดัลฟอร์ม — เพิ่มแท็บ / เพิ่มประกาศ / แก้ไขประกาศ
       ══════════════════════════════════════════════════════════ */
    .fmodal {
      position: fixed;
      inset: 0;
      z-index: 560;
      display: grid;
      place-items: center;
      padding: clamp(10px, 2vw, 26px);
      background: rgba(9, 12, 18, 0.6);
    }

    .fmodal[hidden] { display: none; }

    .fmodal-card {
      width: min(100%, 460px);
      max-height: 100%;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      border-radius: var(--r-lg);
      background: var(--surface);
      box-shadow: var(--sh-4);
    }

    .fmodal-card.is-wide { width: min(100%, 660px); }

    .fmodal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex: 0 0 auto;
      padding: 13px 16px;
      border-bottom: 1px solid var(--line);
    }

    .fmodal-head h2 { font-size: 0.9375rem; font-weight: 700; }

    .fmodal-x {
      width: 28px;
      height: 28px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      color: var(--muted);
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .fmodal-x:hover { background: var(--surface-3); color: var(--ink); }
    .fmodal-x svg { width: 16px; height: 16px; }

    /* เนื้อในเลื่อนได้ ถ้าฟอร์มยาวเกินจอ */
    .fmodal-body {
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
      padding: 16px;
    }

    /* ในโมดัลไม่ต้องจำกัดความกว้างฟอร์มอีก */
    .fmodal-body .create-form,
    .fmodal-body .cat-form,
    .fmodal-body form { max-width: none; }

    .fmodal-body .cat-form-row { flex-direction: column; align-items: stretch; }

    /* ── ภาพสไลด์หน้าแรก ── */
    .slide-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
      gap: 10px;
    }

    .slide {
      position: relative;
      margin: 0;
      overflow: hidden;
      border: 1px solid var(--line);
      background: var(--surface-2);
    }

    .slide img {
      width: 100%;
      aspect-ratio: 16 / 9;
      height: auto;
      object-fit: cover;
      display: block;
    }

    /* ลำดับที่แสดง — ป้ายมุมซ้ายบน */
    .slide-no {
      position: absolute;
      top: 0;
      left: 0;
      z-index: 1;
      min-width: 26px;
      height: 26px;
      display: grid;
      place-items: center;
      padding: 0 6px;
      background: var(--navy);
      color: var(--on-navy);
      font-size: 0.75rem;
      font-weight: 700;
    }

    /* ลบ — กากบาทมุมขวาบน ชุดเดียวกับไทล์ภาพที่แนบ */
    .slide-x-form { position: absolute; top: 5px; right: 5px; z-index: 2; margin: 0; }

    .slide-x {
      width: 24px;
      height: 24px;
      display: grid;
      place-items: center;
      border: 0;
      border-radius: 50%;
      background: rgba(9, 12, 18, 0.66);
      color: #ffffff;
      font-size: 0.9375rem;
      line-height: 1;
      transition: background var(--fast) var(--ease);
    }

    .slide-x:hover { background: var(--danger); }

    /* ปุ่มสลับลำดับ — ลอยมุมขวาล่าง */
    .slide-acts {
      position: absolute;
      right: 5px;
      bottom: 5px;
      z-index: 2;
      display: flex;
      gap: 3px;
      opacity: 0;
      transition: opacity var(--fast) var(--ease);
    }

    .slide:hover .slide-acts,
    .slide:focus-within .slide-acts { opacity: 1; }

    .slide-acts form { margin: 0; }

    .slide-btn {
      width: 26px;
      height: 26px;
      display: grid;
      place-items: center;
      background: rgba(9, 12, 18, 0.66);
      color: #ffffff;
      transition: background var(--fast) var(--ease);
    }

    .slide-btn svg { width: 15px; height: 15px; }
    .slide-btn:hover:not(:disabled) { background: var(--navy); color: var(--on-navy); }
    .slide-btn:disabled { opacity: 0.25; cursor: default; }

    /* ── ไอคอนระบบงาน ── */
    .app-icon {
      width: 44px;
      height: 44px;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      overflow: hidden;
      padding: 5px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface);
      color: var(--faint);
    }

    .app-icon img { width: 100%; height: 100%; object-fit: contain; }
    .app-icon svg { width: 20px; height: 20px; }

    /* ไอคอนที่กดดูภาพใหญ่ได้ (แล้วค่อยดาวน์โหลดจากในโมดัล) */
    .app-icon-btn { cursor: zoom-in; transition: border-color var(--fast) var(--ease); }
    .app-icon-btn:hover { border-color: var(--navy); }

    /* แถบ "ไอคอนปัจจุบัน" ในโมดัลแก้ไขระบบงาน */
    .app-icon-now {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface-2);
    }

    .app-icon-now .app-icon { width: 52px; height: 52px; }

    .app-icon-now-text {
      min-width: 0;
      display: grid;
      gap: 2px;
      font-size: 0.6875rem;
      color: var(--muted);
    }

    .app-icon-now-text b {
      overflow: hidden;
      color: var(--ink);
      font-size: 0.75rem;
      font-weight: 600;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* ── ตัวอย่างไอคอนที่เพิ่งเลือก ──
       บอกให้เห็นว่าหน้าเว็บย่อภาพลงกรอบจัตุรัส 1:1 แบบ "พอดีทั้งภาพ" (contain)
       พื้นลายตารางคือส่วนที่จะกลายเป็นที่ว่างถ้าภาพไม่เป็นจัตุรัส */
    .icon-pick {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px;
      border: 1px solid var(--line);
      border-radius: var(--r-sm);
      background: var(--surface-2);
    }

    .icon-pick-shot {
      width: 64px;
      height: 64px;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      overflow: hidden;
      padding: 0;
      border: 1px dashed var(--line-2);
      border-radius: var(--r-sm);
      background-color: var(--surface);
      background-image:
        linear-gradient(45deg, var(--surface-3) 25%, transparent 25%, transparent 75%, var(--surface-3) 75%),
        linear-gradient(45deg, var(--surface-3) 25%, transparent 25%, transparent 75%, var(--surface-3) 75%);
      background-size: 12px 12px;
      background-position: 0 0, 6px 6px;
      cursor: zoom-in;
      transition: border-color var(--fast) var(--ease);
    }

    .icon-pick-shot:hover { border-color: var(--navy); }
    .icon-pick-shot img { width: 100%; height: 100%; object-fit: contain; }

    .icon-pick-text {
      min-width: 0;
      flex: 1 1 auto;
      display: grid;
      gap: 2px;
      font-size: 0.6875rem;
      color: var(--muted);
    }

    .icon-pick-text b {
      overflow: hidden;
      color: var(--ink);
      font-size: 0.75rem;
      font-weight: 600;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .icon-pick-x {
      width: 24px;
      height: 24px;
      flex: 0 0 auto;
      display: grid;
      place-items: center;
      border-radius: 50%;
      color: var(--muted);
      font-size: 1rem;
      line-height: 1;
      transition: background var(--fast) var(--ease), color var(--fast) var(--ease);
    }

    .icon-pick-x:hover { background: var(--danger-soft); color: var(--danger); }

    .app-url {
      display: block;
      margin-top: 3px;
      overflow: hidden;
      color: var(--navy-ink);
      font-size: 0.6875rem;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .app-url:hover { text-decoration: underline; }

    /* ป้ายหมวดแขวนมุมขวาบนของการ์ดแอป */
    .app-cat-tag {
      position: absolute;
      top: 6px;
      right: 6px;
      z-index: 2;
      padding: 1px 8px;
      border-radius: var(--r-full);
      background: var(--navy-soft);
      color: var(--navy-ink);
      font-size: 0.625rem;
      font-weight: 600;
    }

    /* ตารางแอป 4 คอลัมน์ */
    .app-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 8px;
      align-items: start;
    }

    /* การ์ดแอปในตาราง — เรียงแนวตั้งให้พอดีคอลัมน์แคบ */
    .app-grid .row-main {
      flex-direction: column;
      align-items: stretch;
      gap: 8px;
    }

    .app-grid .row-body { padding-right: 0; }
    .app-grid .row-acts { padding: 0 10px 10px; }

    /* ฟอร์มแก้ไขระบบงานอยู่ในโมดัล — ใช้ความกว้างของโมดัล ไม่ใช่ความกว้างคอลัมน์ */
    .app-grid .fmodal form { max-width: none; }

    @media (max-width: 1180px) {
      .app-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 880px) {
      .app-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 560px) {
      .app-grid { grid-template-columns: minmax(0, 1fr); }
    }

    .cat-form { margin: 0; }

    .cat-form-row {
      display: flex;
      align-items: flex-end;
      gap: 10px;
    }

    .cat-form-row .lang-panel.is-on { flex: 1 1 auto; min-width: 0; }
    .cat-form .field { margin-bottom: 0; }

    /* ══════════════════════════════════════════════════════════
       10. FOOTER — เหมือนหน้าแรก
       ══════════════════════════════════════════════════════════ */
    .site-footer {
      flex: 0 0 auto;
      border-top: 1px solid var(--line);
      background: var(--surface);
      padding-block: 10px;
    }

    .footer-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
      color: var(--faint);
      font-size: 0.75rem;
    }

    .footer-row a { color: var(--muted); }
    .footer-row a:hover { color: var(--navy-ink); }

    /* ══════════════════════════════════════════════════════════
       11. RESPONSIVE
       ══════════════════════════════════════════════════════════ */
    @media (max-width: 1024px) {
      .app { flex-direction: column; }

      .side {
        flex: 0 0 auto;
        flex-direction: row;
        overflow-x: auto;
        overflow-y: hidden;
        border-right: 0;
        border-bottom: 1px solid var(--line);
      }

      .side-div { display: none; }
      .side-item { white-space: nowrap; }
    }

    @media (max-width: 880px) {
      .grid-2 { grid-template-columns: minmax(0, 1fr); }
    }

    @media (max-width: 640px) {
      .metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
      .metric { padding: 10px 12px; }
      .metric b { font-size: 1.25rem; }
      .brand-name b { font-size: 0.9375rem; }
      .search { flex: 1 1 100%; }
      .work-head { align-items: stretch; flex-direction: column; }
      .work-head .btn { width: 100%; }
      .row-main { padding: 10px; gap: 9px; }
      .row-thumb { width: 84px; height: 60px; }
      .row-date { width: 28px; min-height: 28px; }
      .row-date strong { font-size: 0.6875rem; }
      .row-acts { padding: 0 10px 10px; }
    }

    /* ══════════════════════════════════════════════════════════
       12. ลดการเคลื่อนไหว
       ══════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
      html { scroll-behavior: auto; }

      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }

      /* ตัวหมุนไม่มีความหมายถ้าไม่หมุน — ใช้ข้อความบอกสถานะแทน */
      .spin { display: none; }
    }
  </style>
</head>

<body>
  <!-- ══════════════════ HEADER ══════════════════ -->
  <header class="site-header">
    <div class="shell header-row">
      <a class="brand" href="index.php" aria-label="SUPAVUT GROUP">
        <img class="brand-mark" src="img/3si.png" alt="">
        <span class="brand-name">
          <b>SUPAVUT</b>
          <span>GROUP</span>
        </span>
      </a>

      <div class="header-tools">
        <div class="pop">
          <button class="icon-btn" id="menuBtn" type="button" aria-label="เมนู" aria-expanded="false" aria-haspopup="true">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          </button>
          <div class="pop-panel" id="menuPanel" role="menu">
            <a class="pop-item" href="index.php" role="menuitem">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 11 9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
              <span data-i18n="menuHome">หน้าแรก</span>
            </a>
            <?php if ($isAdmin) { ?>
              <div class="pop-divider"></div>
              <form method="post" style="margin:0">
                <input type="hidden" name="action" value="logout">
                <button class="pop-item danger" type="submit" role="menuitem">
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 5V4a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1v-1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M14 12H4m0 0 3-3m-3 3 3 3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <span data-i18n="menuLogout">ออกจากระบบ</span>
                </button>
              </form>
            <?php } ?>
          </div>
        </div>

        <div class="pop">
          <button class="lang-btn" id="langBtn" type="button" aria-label="เปลี่ยนภาษา" aria-expanded="false" aria-haspopup="true">
            <img id="langFlag" src="img/flags/th.png" alt="">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <div class="pop-panel" id="langPanel" role="menu">
            <button class="pop-item" type="button" role="menuitem" data-lang="th"><img src="img/flags/th.png" alt=""><span>ไทย</span></button>
            <button class="pop-item" type="button" role="menuitem" data-lang="en"><img src="img/flags/en.png" alt=""><span>English</span></button>
            <button class="pop-item" type="button" role="menuitem" data-lang="my"><img src="img/flags/my.png" alt=""><span>မြန်မာ</span></button>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- ══════════════════ PAGE ══════════════════ -->
  <main class="page<?php echo $isAdmin ? '' : ' page-auth'; ?>">
    <?php if (!$isAdmin) { ?>
      <?php /* ภาพพื้นหลังประจำหน้าล็อกอิน — เปลี่ยนไฟล์ได้ที่ img/login-bg.png */ ?>
      <div class="auth-bg" aria-hidden="true">
        <img src="img/login-bg.png" alt="">
      </div>

      <div class="shell">
        <!-- ══════════════════ LOGIN ══════════════════ -->
        <section class="auth">
          <a class="auth-back" href="index.php">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span data-i18n="menuHome">หน้าแรก</span>
          </a>

          <img class="auth-mark" src="img/3si.png" alt="">
          <h1 data-i18n="loginTitle">เข้าสู่ระบบ</h1>

          <?php if (count($errors) > 0) { ?>
            <div class="auth-error" id="loginError" role="alert">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7.5v5m0 3.2v.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              <span><?php echo announcement_h($errors[0]); ?></span>
            </div>
          <?php } ?>

          <form method="post" id="loginForm">
            <input type="hidden" name="action" value="login">

            <div class="field">
              <label for="username" data-i18n="userLabel">ชื่อผู้ใช้</label>
              <input id="username" name="username" type="text" autocomplete="username" required autofocus
                     <?php if ($loginFailed) { ?>aria-invalid="true" aria-describedby="loginError"<?php } ?>>
            </div>

            <div class="field">
              <label for="password" data-i18n="passLabel">รหัสผ่าน</label>
              <div class="pw">
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       <?php if ($loginFailed) { ?>aria-invalid="true" aria-describedby="loginError"<?php } ?>>
                <button class="pw-eye" id="pwEye" type="button" aria-pressed="false" aria-controls="password" aria-label="แสดงรหัสผ่าน" title="แสดงรหัสผ่าน">
                  <svg class="eye-on" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.9" stroke="currentColor" stroke-width="1.7"/></svg>
                  <svg class="eye-off" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9.9 5.8A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3 3.8M6.4 7.7A16.8 16.8 0 0 0 2.5 12S6 18.5 12 18.5c1.4 0 2.6-.3 3.7-.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="m4 4 16 16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                </button>
              </div>
            </div>

            <button class="btn btn-primary btn-block" id="loginBtn" type="submit">
              <span class="spin" hidden aria-hidden="true"></span>
              <span data-i18n="loginBtn">เข้าสู่ระบบ</span>
            </button>
          </form>
        </section>

      </div>
    <?php } else { ?>
      <!-- ══════════════════ CONSOLE — sidebar ชิดขอบจอ, header/footer ปักหมุด ══════════════════ -->
      <div class="app">
        <!-- ── เมนูซ้าย ── -->
        <aside class="side" aria-label="เมนูผู้ดูแล">
          <button class="side-item is-on" type="button" data-goto="posts" aria-current="page">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 11h16M4 16h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span data-i18n="sidePosts">ประกาศ</span>
          </button>

          <button class="side-item" type="button" data-goto="hero">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="1.5" stroke="currentColor" stroke-width="1.8"/><path d="m4 16 4.5-4.5L13 16l3-3 4 4" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            <span data-i18n="sideHero">ตั้งภาพหน้าปก</span>
          </button>

          <button class="side-item" type="button" data-goto="apps">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="4" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="13" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/></svg>
            <span data-i18n="sideApps">จัดการแอปพลิเคชัน</span>
          </button>

          <button class="side-item" type="button" data-goto="register">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4.5h11l3 3V19a.5.5 0 0 1-.5.5h-13A.5.5 0 0 1 5 19V4.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8.5 10h7M8.5 13.5h7M8.5 17h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            <span data-i18n="sideRegister">ทะเบียนประกาศ</span>
          </button>
        </aside>

        <div class="work">
          <div class="work-inner">

            <section class="view is-on" data-view="posts">
              <div class="work-head">
                <h1 data-i18n="sidePosts">ประกาศ</h1>
              </div>

              <!-- ══ ขั้นที่ 1: ตัวอย่างประกาศ ══ -->
              <article id="allPosts" class="step" style="scroll-margin-top: calc(var(--head-h) + 12px);">
                <div class="step-head">
                  <span class="step-no">1</span>
                  <div class="step-head-text">
                    <h2 data-i18n="step3Title">ตัวอย่างประกาศ</h2>
                  </div>
                  <button id="btnTab" class="btn btn-ghost btn-sm" type="button">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span data-i18n="step1Title">เพิ่มแท็บ</span>
                  </button>
                  <button id="btnCreate" class="btn btn-primary btn-sm" type="button">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span data-i18n="btnAdd">เพิ่มประกาศ</span>
                  </button>
                </div>

                <!-- แถวบน: ค้นหา + ตัวกรองหลัก -->
                <div class="bar">
                  <div class="search">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.9"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                    <input id="search" type="search" autocomplete="off" placeholder="ค้นหา" data-i18n-placeholder="searchPh" aria-label="ค้นหาประกาศ">
                  </div>

                  <div class="segs" role="tablist" aria-label="กรองหลัก">
                    <button class="seg" type="button" role="tab" aria-selected="true" data-pin="__all">
                      <span data-i18n="segAll">ทั้งหมด</span><b><?php echo count($announcements); ?></b>
                    </button>
                    <button class="seg" type="button" role="tab" aria-selected="false" data-pin="__pinned">
                      <span data-i18n="segPinned">ปักหมุด</span><b><?php echo $pinnedCount; ?></b>
                    </button>
                    <button class="seg" type="button" role="tab" aria-selected="false" data-pin="__plain">
                      <span data-i18n="segPlain">ทั่วไป</span><b><?php echo count($announcements) - $pinnedCount; ?></b>
                    </button>
                  </div>
                </div>

                <!-- แถวล่าง: แท็บที่สร้างไว้ -->
                <div class="segs is-boxed" role="tablist" aria-label="กรองตามแท็บ">
                    <?php foreach ($categories as $cat) { ?>
                      <?php $catCount = isset($categoryCounts[$cat['id']]) ? $categoryCounts[$cat['id']] : 0; ?>
                      <span class="seg-wrap">
                        <button class="seg" type="button" role="tab" aria-selected="false" data-cat="<?php echo announcement_h($cat['id']); ?>"
                                data-name-th="<?php echo announcement_h(announcement_category_name($cat, 'th')); ?>"
                                data-name-en="<?php echo announcement_h(announcement_category_name($cat, 'en')); ?>"
                                data-name-my="<?php echo announcement_h(announcement_category_name($cat, 'my')); ?>">
                          <span><?php echo announcement_h(announcement_category_name($cat, 'th')); ?></span><b><?php echo $catCount; ?></b>
                        </button>
                        <form method="post" onsubmit="return confirm(tr('askDeleteCat'));">
                          <input type="hidden" name="action" value="delete_category">
                          <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                          <input type="hidden" name="cat_id" value="<?php echo announcement_h($cat['id']); ?>">
                          <button type="submit" class="seg-del" aria-label="ลบแท็บ <?php echo announcement_h(announcement_category_name($cat, 'th')); ?>" title="ลบ">×</button>
                        </form>
                      </span>
                    <?php } ?>
                    <?php if ($uncategorizedCount > 0) { ?>
                      <button class="seg" type="button" role="tab" aria-selected="false" data-cat="__none">
                        <span data-i18n="segNone">ไม่มีแท็บ</span><b><?php echo $uncategorizedCount; ?></b>
                      </button>
                    <?php } ?>
                </div>

                <?php if (count($announcements) === 0) { ?>
                  <div class="blank" data-i18n="blankNone">ยังไม่มีประกาศ</div>
                <?php } ?>

                <div class="rows">
                <?php foreach ($announcements as $rowNo => $announcement) { ?>
                  <?php
                    $announcementId = isset($announcement['id']) ? $announcement['id'] : '';
                    $safeId = preg_replace('/[^A-Za-z0-9_]/', '_', $announcementId);
                    $attachments = announcement_get_attachments($announcement);
                    $isNew = announcement_is_new($announcement);
                    $coverUrl = announcement_cover_url($announcement);
                    $hasCover = $coverUrl !== '';
                    $cover2Url = announcement_cover_slot_url($announcement, 2);
                    $titleTh = announcement_localized_title($announcement, 'th');
                    $titleEn = announcement_localized_title($announcement, 'en');
                    $titleMy = announcement_localized_title($announcement, 'my');
                    $bodyTh = announcement_excerpt(announcement_localized_body($announcement, 'th'));
                    $bodyEn = announcement_excerpt(announcement_localized_body($announcement, 'en'));
                    $bodyMy = announcement_excerpt(announcement_localized_body($announcement, 'my'));
                    $isPinned = announcement_is_pinned($announcement);
                    $rowCat = announcement_category_of($announcement);
                    $rowCatObj = $rowCat !== '' ? announcement_category_find($rowCat) : null;
                  ?>
                  <article class="row<?php echo $isPinned ? ' is-pinned' : ''; ?>" data-row
                           data-no="<?php echo $rowNo + 1; ?>"
                           data-pinned="<?php echo $isPinned ? '1' : '0'; ?>"
                           data-cat="<?php echo announcement_h($rowCat); ?>"
                           data-title-th="<?php echo announcement_h($titleTh); ?>"
                           data-title-en="<?php echo announcement_h($titleEn); ?>"
                           data-title-my="<?php echo announcement_h($titleMy); ?>"
                           data-body-th="<?php echo announcement_h($bodyTh); ?>"
                           data-body-en="<?php echo announcement_h($bodyEn); ?>"
                           data-body-my="<?php echo announcement_h($bodyMy); ?>">
                    <?php /* ปักหมุด — ติดมุมขวาบนของการ์ด */ ?>
                    <div class="row-pin">
                        <form class="pin-form" method="post">
                          <input type="hidden" name="action" value="pin">
                          <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                          <input type="hidden" name="id" value="<?php echo announcement_h($announcementId); ?>">
                          <input type="hidden" name="pinned" value="<?php echo $isPinned ? '0' : '1'; ?>">
                          <button class="pin-btn<?php echo $isPinned ? ' is-on' : ''; ?>" type="submit" aria-label="<?php echo $isPinned ? 'ถอดหมุด' : 'ปักหมุด'; ?>" title="<?php echo $isPinned ? 'ถอดหมุด' : 'ปักหมุด'; ?>">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10a1 1 0 0 1 1 1v15l-6-4-6 4V5a1 1 0 0 1 1-1Z" fill="currentColor" class="pin-fill"/><path d="M7 4h10a1 1 0 0 1 1 1v15l-6-4-6 4V5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                          </button>
                        </form>
                    </div>

                    <div class="row-main">
                      <div class="row-lead">
                        <span class="row-no"><?php echo $rowNo + 1; ?></span>
                      </div>

                      <div class="row-thumb">
                        <?php if ($hasCover) { ?>
                          <img src="<?php echo announcement_h($coverUrl); ?>" alt="" loading="lazy">
                        <?php } else { ?>
                          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="1.5" stroke="currentColor" stroke-width="1.7"/><path d="m4 16 4.5-4.5L13 16l3-3 4 4" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                        <?php } ?>
                        <div class="row-date" aria-label="<?php echo announcement_h(announcement_format_date($announcement['created_at'])); ?>">
                          <strong><?php echo announcement_h(announcement_date_day($announcement['created_at'])); ?></strong>
                          <span><?php echo announcement_h(announcement_date_month_short_th($announcement['created_at'])); ?></span>
                        </div>
                      </div>

                      <div class="row-body">
                        <div class="row-meta">
                          <span><?php echo announcement_h(announcement_format_date($announcement['created_at'])); ?></span>
                        </div>
                        <h3 data-title><?php echo announcement_h($titleTh); ?></h3>
                        <p data-body><?php echo announcement_h($bodyTh); ?></p>

                        <?php /* แถวล่างการ์ด: ปุ่มซ้าย · ป้ายกำกับขวา */ ?>
                        <div class="row-foot">
                          <div class="row-tools">
                            <button class="btn btn-ghost btn-sm" type="button" data-edit aria-expanded="false" aria-controls="edit_<?php echo announcement_h($safeId); ?>" data-i18n="btnEdit">แก้ไข</button>

                            <?php /* ประกาศทะเบียนเป็นประกาศระบบ — ไม่มีปุ่มลบ
                                     ฝั่ง POST ก็ปฏิเสธ action=delete ของ id นี้ด้วย */ ?>
                            <?php if ($announcementId === ANNOUNCEMENT_REGISTER_POST_ID) { ?>
                              <span class="row-locked" title="ประกาศระบบ ลบไม่ได้">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10.5V8a5 5 0 0 1 10 0v2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="5" y="10.5" width="14" height="9.5" rx="1.5" stroke="currentColor" stroke-width="1.8"/></svg>
                                <span data-i18n="rowLocked">ประกาศระบบ</span>
                              </span>
                            <?php } else { ?>
                              <form method="post" onsubmit="return confirm(tr('askDelete'));">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                                <input type="hidden" name="id" value="<?php echo announcement_h($announcementId); ?>">
                                <button class="btn btn-danger btn-sm" type="submit" data-i18n="btnDelete">ลบ</button>
                              </form>
                            <?php } ?>
                          </div>

                          <div class="row-flags">
                            <?php if ($rowCatObj) { ?>
                              <span data-cat-tag
                                    data-name-th="<?php echo announcement_h(announcement_category_name($rowCatObj, 'th')); ?>"
                                    data-name-en="<?php echo announcement_h(announcement_category_name($rowCatObj, 'en')); ?>"
                                    data-name-my="<?php echo announcement_h(announcement_category_name($rowCatObj, 'my')); ?>"><?php echo announcement_h(announcement_category_name($rowCatObj, 'th')); ?></span>
                            <?php } ?>
                            <?php if ($isNew) { ?>
                              <span class="flag-new" data-i18n="tagNew">ใหม่</span>
                            <?php } ?>
                            <?php if (count($attachments) > 0) { ?>
                              <span data-files data-n="<?php echo count($attachments); ?>"><?php echo count($attachments); ?> ไฟล์</span>
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- ── ฟอร์มแก้ไข ── -->
                    <div id="edit_<?php echo announcement_h($safeId); ?>" class="fmodal" role="dialog" aria-modal="true" hidden>
                      <div class="fmodal-card is-wide">
                        <div class="fmodal-head">
                          <h2 data-i18n="formEditTitle">แก้ไขประกาศ</h2>
                          <button class="fmodal-x" type="button" data-fmodal-close aria-label="ปิด">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                          </button>
                        </div>
                        <div class="fmodal-body">

                      <form method="post" enctype="multipart/form-data" data-form>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                        <input type="hidden" name="id" value="<?php echo announcement_h($announcementId); ?>">
                        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo ANNOUNCEMENT_MAX_FILE_SIZE; ?>">

                        <!-- ══ ขั้นที่ 1: เนื้อหา ══ -->
                        <div class="wz-step is-on" data-wz="1">

                        <div class="field">
                          <label for="ec_<?php echo announcement_h($safeId); ?>" data-i18n="fTab">แท็บ</label>
                          <select id="ec_<?php echo announcement_h($safeId); ?>" name="category" required>
                            <option value="" disabled<?php echo $rowCat === '' ? ' selected' : ''; ?> data-i18n="fTabPick">— เลือกแท็บ —</option>
                            <?php foreach ($categories as $cat) { ?>
                              <option value="<?php echo announcement_h($cat['id']); ?>"<?php echo $rowCat === $cat['id'] ? ' selected' : ''; ?>><?php echo announcement_h(announcement_category_name($cat, 'th')); ?></option>
                            <?php } ?>
                          </select>
                        </div>

                        <div class="lang-tabs" role="tablist" aria-label="ภาษา">
                          <button class="lang-tab" type="button" role="tab" aria-selected="true" data-tab="th">ไทย</button>
                          <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="en">EN</button>
                          <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="my">MY</button>
                        </div>

                        <div class="lang-panel is-on" data-panel="th">
                          <div class="field">
                            <label for="et_<?php echo announcement_h($safeId); ?>" data-i18n="fTitle">หัวข้อ</label>
                            <input id="et_<?php echo announcement_h($safeId); ?>" name="title" type="text" maxlength="160" placeholder="กรอกหัวข้อภาษาไทย" value="<?php echo announcement_h(announcement_field($announcement, 'title')); ?>">
                          </div>
                          <div class="field">
                            <label for="eb_<?php echo announcement_h($safeId); ?>" data-i18n="fBody">รายละเอียด</label>
                            <textarea id="eb_<?php echo announcement_h($safeId); ?>" name="body" placeholder="กรอกรายละเอียดภาษาไทย"><?php echo announcement_h(announcement_field($announcement, 'body')); ?></textarea>
                          </div>
                        </div>

                        <div class="lang-panel" data-panel="en">
                          <div class="field">
                            <label for="ete_<?php echo announcement_h($safeId); ?>" data-i18n="fTitle">หัวข้อ</label>
                            <input id="ete_<?php echo announcement_h($safeId); ?>" name="title_en" type="text" maxlength="160" placeholder="Title in English" value="<?php echo announcement_h(announcement_field($announcement, 'title_en')); ?>">
                          </div>
                          <div class="field">
                            <label for="ebe_<?php echo announcement_h($safeId); ?>" data-i18n="fBody">รายละเอียด</label>
                            <textarea id="ebe_<?php echo announcement_h($safeId); ?>" name="body_en" placeholder="Details in English"><?php echo announcement_h(announcement_field($announcement, 'body_en')); ?></textarea>
                          </div>
                        </div>

                        <div class="lang-panel" data-panel="my">
                          <div class="field">
                            <label for="etm_<?php echo announcement_h($safeId); ?>" data-i18n="fTitle">หัวข้อ</label>
                            <input id="etm_<?php echo announcement_h($safeId); ?>" name="title_my" type="text" maxlength="160" placeholder="မြန်မာဘာသာဖြင့် ခေါင်းစဉ်" value="<?php echo announcement_h(announcement_field($announcement, 'title_my')); ?>">
                          </div>
                          <div class="field">
                            <label for="ebm_<?php echo announcement_h($safeId); ?>" data-i18n="fBody">รายละเอียด</label>
                            <textarea id="ebm_<?php echo announcement_h($safeId); ?>" name="body_my" placeholder="မြန်မာဘာသာဖြင့် အသေးစိတ်"><?php echo announcement_h(announcement_field($announcement, 'body_my')); ?></textarea>
                          </div>
                        </div>

                        <?php
                          /* ของที่แนบไว้แล้ว — แยกภาพกับเอกสาร แล้วแสดงเป็นไทล์แบบเดียวกับตอนเพิ่งแนบ */
                          $curImages = array();
                          $curDocs = array();
                          foreach ($attachments as $attIndex => $attachment) {
                            if (announcement_file_kind($attachment) === 'image') {
                              $curImages[$attIndex] = $attachment;
                            } else {
                              $curDocs[$attIndex] = $attachment;
                            }
                          }
                        ?>

                        <?php if (count($curImages) > 0) { ?>
                          <div class="field">
                            <label data-i18n="fPhotosNow">รูปภาพปัจจุบัน</label>
                            <div class="tile-grid">
                              <?php foreach ($curImages as $attIndex => $attachment) { ?>
                                <?php
                                  $fileLabel = isset($attachment['original_name']) ? $attachment['original_name'] : announcement_attachment_identifier($attachment);
                                  $attUrl = announcement_attachment_url($announcementId, $attachment);
                                ?>
                                <div class="tile">
                                  <img src="<?php echo announcement_h($attUrl); ?>" alt="" loading="lazy">
                                  <button class="tile-open" type="button" data-zoom="<?php echo announcement_h($attUrl); ?>"
                                          data-zoom-name="<?php echo announcement_h($fileLabel); ?>" aria-label="ดูภาพ"></button>
                                  <button class="tile-x" type="button" data-drop-file aria-label="ลบ <?php echo announcement_h($fileLabel); ?>" title="ลบ">×</button>
                                  <input type="hidden" name="remove_attachments[]" value="<?php echo announcement_h(announcement_attachment_identifier($attachment)); ?>" disabled data-drop-flag>
                                </div>
                              <?php } ?>
                            </div>
                          </div>
                        <?php } ?>

                        <?php if (count($curDocs) > 0) { ?>
                          <div class="field">
                            <label data-i18n="fFilesNow">ไฟล์แนบปัจจุบัน</label>
                            <div class="tile-grid">
                              <?php foreach ($curDocs as $attIndex => $attachment) { ?>
                                <?php
                                  $fileKind = announcement_file_kind($attachment);
                                  $fileIconUrl = announcement_file_icon_url($fileKind);
                                  $fileLabel = isset($attachment['original_name']) ? $attachment['original_name'] : announcement_attachment_identifier($attachment);
                                ?>
                                <div class="tile-item">
                                  <div class="tile is-doc">
                                    <?php if ($fileIconUrl !== '') { ?>
                                      <img src="<?php echo announcement_h($fileIconUrl); ?>" alt="">
                                    <?php } else { ?>
                                      <span class="asset-tag"><?php echo announcement_h(announcement_file_short_label($fileKind)); ?></span>
                                    <?php } ?>
                                    <button class="tile-x" type="button" data-drop-file aria-label="ลบ <?php echo announcement_h($fileLabel); ?>" title="ลบ">×</button>
                                    <input type="hidden" name="remove_attachments[]" value="<?php echo announcement_h(announcement_attachment_identifier($attachment)); ?>" disabled data-drop-flag>
                                  </div>
                                  <span class="tile-cap" title="<?php echo announcement_h($fileLabel); ?>"><?php echo announcement_h($fileLabel); ?></span>
                                </div>
                              <?php } ?>
                            </div>
                          </div>
                        <?php } ?>

                        <?php /* อัปโหลดเพิ่ม — ซ่อนไว้จนกว่าจะติ๊ก เหมือนหน้าเพิ่มประกาศ */ ?>
                        <label class="check">
                          <input type="checkbox" id="ehm_<?php echo announcement_h($safeId); ?>" data-toggle-media>
                          <span data-i18n="fHasMedia">แนบรูปภาพหรือไฟล์</span>
                        </label>

                        <div class="media-stack" data-media-fields hidden>
                          <div class="field">
                            <label for="ephoto_<?php echo announcement_h($safeId); ?>">
                              <span data-i18n="fPhotosName">รูปภาพ</span>
                              <b class="tag-ratio" data-i18n="fGallery">แกลเลอรี</b>
                            </label>
                            <input id="ephoto_<?php echo announcement_h($safeId); ?>" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp" data-photo-input>
                          </div>

                          <div class="field">
                            <label for="ef_<?php echo announcement_h($safeId); ?>" data-i18n="fFiles">ไฟล์แนบ</label>
                            <input id="ef_<?php echo announcement_h($safeId); ?>" name="attachments[]" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                            <p class="hint">PDF · DOC · XLS · PPT · <?php echo announcement_h(announcement_format_file_size(ANNOUNCEMENT_MAX_FILE_SIZE)); ?></p>
                          </div>
                        </div>

                        <button class="btn btn-primary btn-block" type="button" data-wz-next data-i18n="btnNext">ไปต่อ</button>
                        </div>

                        <!-- ══ ขั้นที่ 2: ภาพปก ══ -->
                        <div class="wz-step" data-wz="2">

                        <?php if ($hasCover || $cover2Url !== '') { ?>
                          <div class="field">
                            <label data-i18n="fCoverNow">ภาพปกปัจจุบัน</label>
                            <div class="cover-row">
                              <div>
                                <?php if ($hasCover) { ?>
                                  <div class="cover-slots">
                                    <div class="cover-card">
                                      <button class="cover-shot" type="button" data-zoom="<?php echo announcement_h($coverUrl); ?>" data-zoom-name="ภาพปก 1" title="กดเพื่อดูภาพ">
                                        <img src="<?php echo announcement_h($coverUrl); ?>" alt="">
                                      </button>
                                      <?php /* × ลบทันทีผ่าน AJAX โดยไม่ส่งข้อความที่กำลังแก้ไข */ ?>
                                      <button class="cover-x" type="button" data-drop-cover="1" aria-label="ลบภาพปก 1" title="ลบภาพปก 1">×</button>
                                      <input type="hidden" name="remove_cover" value="1" disabled data-drop-flag>
                                    </div>
                                  </div>
                                  <p class="cover-name" data-i18n="fCover1Short">ภาพปก 1 · หน้าแรก</p>
                                <?php } ?>
                              </div>

                              <div>
                                <?php if ($cover2Url !== '') { ?>
                                  <div class="cover-slots">
                                    <div class="cover-card">
                                      <button class="cover-shot" type="button" data-zoom="<?php echo announcement_h($cover2Url); ?>" data-zoom-name="ภาพปก 2" title="กดเพื่อดูภาพ">
                                        <img src="<?php echo announcement_h($cover2Url); ?>" alt="">
                                      </button>
                                      <button class="cover-x" type="button" data-drop-cover="2" aria-label="ลบภาพปก 2" title="ลบภาพปก 2">×</button>
                                      <input type="hidden" name="remove_cover_2" value="1" disabled data-drop-flag>
                                    </div>
                                  </div>
                                  <p class="cover-name" data-i18n="fCover2Short">ภาพปก 2 · หน้าอ่านประกาศ</p>
                                <?php } ?>
                              </div>
                            </div>
                          </div>
                        <?php } ?>

                        <div class="cover-row">
                        <div class="field" data-cover-slot="1" data-ratio="1.3333">
                          <label for="ecv_<?php echo announcement_h($safeId); ?>">
                            <span data-i18n="fCover1Name">ภาพปก 1</span>
                            <b class="tag-ratio">4:3 · <span data-i18n="fOnHome">หน้าแรก</span></b>
                          </label>
                          <div class="pick-row">
                            <input id="ecv_<?php echo announcement_h($safeId); ?>" name="cover_image" type="file" accept=".jpg,.jpeg,.png,.webp" data-cover-input>
                            <input type="hidden" name="cover_cropped" value="" data-cover-data>
                            <button class="btn btn-ghost btn-sm is-icon" type="button" data-crop-open hidden title="จัดภาพ">
                              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3v13a2 2 0 0 0 2 2h13M18 21V8a2 2 0 0 0-2-2H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                              <span class="sr-only" data-i18n="btnCrop">จัดภาพ</span>
                            </button>
                          </div>
                          <div class="pick-preview" data-cover-preview hidden></div>
                        </div>

                        <div class="field" data-cover-slot="2" data-ratio="3">
                          <label for="ecv2_<?php echo announcement_h($safeId); ?>">
                            <span data-i18n="fCover2Name">ภาพปก 2</span>
                            <b class="tag-ratio">3:1 · <span data-i18n="fOnArticle">หน้าอ่าน</span></b>
                          </label>
                          <div class="pick-row">
                            <input id="ecv2_<?php echo announcement_h($safeId); ?>" name="cover_image_2" type="file" accept=".jpg,.jpeg,.png,.webp" data-cover-input>
                            <input type="hidden" name="cover2_cropped" value="" data-cover-data>
                            <button class="btn btn-ghost btn-sm is-icon" type="button" data-crop-open hidden title="จัดภาพ">
                              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3v13a2 2 0 0 0 2 2h13M18 21V8a2 2 0 0 0-2-2H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                              <span class="sr-only" data-i18n="btnCrop">จัดภาพ</span>
                            </button>
                          </div>
                          <div class="pick-preview" data-cover-preview hidden></div>
                        </div>
                        </div>

                        <label class="check">
                          <input type="checkbox" name="pinned" value="1"<?php echo $isPinned ? ' checked' : ''; ?>>
                          <span data-i18n="fPin">ปักหมุด</span>
                        </label>

                        <div class="wz-acts">
                          <button class="btn btn-primary btn-block" type="submit" data-i18n="btnUpdate">อัปเดต</button>
                          <button class="wz-back" type="button" data-wz-back data-i18n="btnBack">ย้อนกลับ</button>
                        </div>
                        </div>
                      </form>

                      <!-- ฟอร์มลบแบบเดิมคงไว้เป็นทางสำรอง; ปุ่ม × ใช้ AJAX ไม่รีโหลดฟอร์มแก้ไข -->
                      <?php if ($hasCover) { ?>
                        <form id="dropCover_<?php echo announcement_h($safeId); ?>" method="post" class="drop-form">
                          <input type="hidden" name="action" value="drop_cover">
                          <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                          <input type="hidden" name="id" value="<?php echo announcement_h($announcementId); ?>">
                          <input type="hidden" name="slot" value="1">
                        </form>
                      <?php } ?>
                      <?php if ($cover2Url !== '') { ?>
                        <form id="dropCover2_<?php echo announcement_h($safeId); ?>" method="post" class="drop-form">
                          <input type="hidden" name="action" value="drop_cover">
                          <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                          <input type="hidden" name="id" value="<?php echo announcement_h($announcementId); ?>">
                          <input type="hidden" name="slot" value="2">
                        </form>
                      <?php } ?>
                      <?php foreach ($attachments as $attIndex => $attachment) { ?>
                        <form id="dropFile_<?php echo announcement_h($safeId); ?>_<?php echo $attIndex; ?>" method="post" class="drop-form">
                          <input type="hidden" name="action" value="drop_file">
                          <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                          <input type="hidden" name="id" value="<?php echo announcement_h($announcementId); ?>">
                          <input type="hidden" name="file_id" value="<?php echo announcement_h(announcement_attachment_identifier($attachment)); ?>">
                        </form>
                      <?php } ?>

                        </div>
                      </div>
                    </div>
                  </article>
                <?php } ?>
              </div>

              <div id="blankFilter" class="blank" hidden data-i18n="blankFilter">ไม่พบประกาศที่ค้นหา</div>
              <nav id="pager" class="pager" aria-label="แบ่งหน้า"></nav>
              </article>

              <!-- ══ MODAL: เพิ่มแท็บ ══ -->
              <div class="fmodal" id="mTab" role="dialog" aria-modal="true" aria-labelledby="mTabTitle" hidden>
                <div class="fmodal-card">
                  <div class="fmodal-head">
                    <h2 id="mTabTitle" data-i18n="step1Title">เพิ่มแท็บ</h2>
                    <button class="fmodal-x" type="button" data-fmodal-close aria-label="ปิด">
                      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                  </div>
                  <div class="fmodal-body">

                <form method="post" class="cat-form" data-form>
                  <input type="hidden" name="action" value="create_category">
                  <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">

                  <div class="lang-tabs" role="tablist" aria-label="ภาษา">
                    <button class="lang-tab" type="button" role="tab" aria-selected="true" data-tab="th">ไทย</button>
                    <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="en">EN</button>
                    <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="my">MY</button>
                  </div>

                  <div class="cat-form-row">
                    <div class="lang-panel is-on" data-panel="th">
                      <div class="field">
                        <label for="cat_th" data-i18n="fTabName">ชื่อแท็บ</label>
                        <input id="cat_th" name="cat_name_th" type="text" maxlength="60" required placeholder="เช่น ข่าวสาร, อาหารประจำสัปดาห์">
                      </div>
                    </div>

                    <div class="lang-panel" data-panel="en">
                      <div class="field">
                        <label for="cat_en" data-i18n="fTabName">ชื่อแท็บ</label>
                        <input id="cat_en" name="cat_name_en" type="text" maxlength="60" placeholder="e.g. News, Weekly Menu">
                      </div>
                    </div>

                    <div class="lang-panel" data-panel="my">
                      <div class="field">
                        <label for="cat_my" data-i18n="fTabName">ชื่อแท็บ</label>
                        <input id="cat_my" name="cat_name_my" type="text" maxlength="60" placeholder="ဥပမာ သတင်း, အပတ်စဉ်အစားအစာ">
                      </div>
                    </div>

                    <button class="btn btn-primary" type="submit" data-i18n="btnAddCat">เพิ่ม</button>
                  </div>
                </form>

                  </div>
                </div>
              </div>

              <!-- ══ MODAL: เพิ่มประกาศ ══ -->
              <div class="fmodal<?php echo $showCreateForm ? ' is-on' : ''; ?>" id="mPost" role="dialog" aria-modal="true" aria-labelledby="mPostTitle"<?php echo $showCreateForm ? '' : ' hidden'; ?>>
                <div class="fmodal-card is-wide">
                  <div class="fmodal-head">
                    <h2 id="mPostTitle" data-i18n="step2Title">เพิ่มประกาศ</h2>
                    <button class="fmodal-x" type="button" data-fmodal-close aria-label="ปิด">
                      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                  </div>
                  <div class="fmodal-body">

                <form id="createCard" method="post" enctype="multipart/form-data" data-form>
                  <input type="hidden" name="action" value="create">
                  <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                  <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo ANNOUNCEMENT_MAX_FILE_SIZE; ?>">

                  <!-- ══ ขั้นที่ 1: เนื้อหา ══ -->
                  <div class="wz-step is-on" data-wz="1">

                  <div class="field">
                    <label for="category" data-i18n="fTab">แท็บ</label>
                    <select id="category" name="category" required>
                      <option value="" disabled selected data-i18n="fTabPick">— เลือกแท็บ —</option>
                      <?php foreach ($categories as $cat) { ?>
                        <option value="<?php echo announcement_h($cat['id']); ?>"><?php echo announcement_h(announcement_category_name($cat, 'th')); ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <div class="lang-tabs" role="tablist" aria-label="ภาษา">
                    <button class="lang-tab" type="button" role="tab" aria-selected="true" data-tab="th">ไทย</button>
                    <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="en">EN</button>
                    <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="my">MY</button>
                  </div>

                  <div class="lang-panel is-on" data-panel="th">
                    <div class="field">
                      <label for="title" data-i18n="fTitle">หัวข้อ</label>
                      <input id="title" name="title" type="text" maxlength="160" placeholder="กรอกหัวข้อภาษาไทย">
                    </div>
                    <div class="field">
                      <label for="body" data-i18n="fBody">รายละเอียด</label>
                      <textarea id="body" name="body" placeholder="กรอกรายละเอียดภาษาไทย"></textarea>
                    </div>
                  </div>

                  <div class="lang-panel" data-panel="en">
                    <div class="field">
                      <label for="title_en" data-i18n="fTitle">หัวข้อ</label>
                      <input id="title_en" name="title_en" type="text" maxlength="160" placeholder="Title in English">
                    </div>
                    <div class="field">
                      <label for="body_en" data-i18n="fBody">รายละเอียด</label>
                      <textarea id="body_en" name="body_en" placeholder="Details in English"></textarea>
                    </div>
                  </div>

                  <div class="lang-panel" data-panel="my">
                    <div class="field">
                      <label for="title_my" data-i18n="fTitle">หัวข้อ</label>
                      <input id="title_my" name="title_my" type="text" maxlength="160" placeholder="မြန်မာဘာသာဖြင့် ခေါင်းစဉ်">
                    </div>
                    <div class="field">
                      <label for="body_my" data-i18n="fBody">รายละเอียด</label>
                      <textarea id="body_my" name="body_my" placeholder="မြန်မာဘာသာဖြင့် အသေးစိတ်"></textarea>
                    </div>
                  </div>

                  <label class="check">
                    <input type="checkbox" id="hasMedia" data-toggle-media>
                    <span data-i18n="fHasMedia">แนบรูปภาพหรือไฟล์</span>
                  </label>

                  <div class="media-stack" data-media-fields hidden>
                    <div class="field">
                      <label for="photo_files">
                        <span data-i18n="fPhotosName">รูปภาพ</span>
                        <b class="tag-ratio" data-i18n="fGallery">แกลเลอรี</b>
                      </label>
                      <input id="photo_files" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp" data-photo-input>
                    </div>

                    <div class="field">
                      <label for="attachments" data-i18n="fFiles">ไฟล์แนบ</label>
                      <input id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                      <p class="hint">PDF · DOC · XLS · PPT · <?php echo announcement_h(announcement_format_file_size(ANNOUNCEMENT_MAX_FILE_SIZE)); ?></p>
                    </div>
                  </div>

                  <button class="btn btn-primary btn-block" type="button" data-wz-next data-i18n="btnNext">ไปต่อ</button>
                  </div>

                  <!-- ══ ขั้นที่ 2: ภาพปก ══ -->
                  <div class="wz-step" data-wz="2">

                  <div class="cover-row">
                  <div class="field" data-cover-slot="1" data-ratio="1.3333">
                    <label for="cover_image">
                      <span data-i18n="fCover1Name">ภาพปก 1</span>
                      <b class="tag-ratio">4:3 · <span data-i18n="fOnHome">หน้าแรก</span></b>
                    </label>
                    <div class="pick-row">
                      <input id="cover_image" name="cover_image" type="file" accept=".jpg,.jpeg,.png,.webp" data-cover-input>
                      <input type="hidden" name="cover_cropped" value="" data-cover-data>
                      <button class="btn btn-ghost btn-sm is-icon" type="button" data-crop-open hidden title="จัดภาพ">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3v13a2 2 0 0 0 2 2h13M18 21V8a2 2 0 0 0-2-2H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="sr-only" data-i18n="btnCrop">จัดภาพ</span>
                      </button>
                    </div>
                    <div class="pick-preview" data-cover-preview hidden></div>
                  </div>

                  <div class="field" data-cover-slot="2" data-ratio="3">
                    <label for="cover_image_2">
                      <span data-i18n="fCover2Name">ภาพปก 2</span>
                      <b class="tag-ratio">3:1 · <span data-i18n="fOnArticle">หน้าอ่าน</span></b>
                    </label>
                    <div class="pick-row">
                      <input id="cover_image_2" name="cover_image_2" type="file" accept=".jpg,.jpeg,.png,.webp" data-cover-input>
                      <input type="hidden" name="cover2_cropped" value="" data-cover-data>
                      <button class="btn btn-ghost btn-sm is-icon" type="button" data-crop-open hidden title="จัดภาพ">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3v13a2 2 0 0 0 2 2h13M18 21V8a2 2 0 0 0-2-2H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="sr-only" data-i18n="btnCrop">จัดภาพ</span>
                      </button>
                    </div>
                    <div class="pick-preview" data-cover-preview hidden></div>
                  </div>
                  </div>

                  <label class="check">
                    <input type="checkbox" name="pinned" value="1">
                    <span data-i18n="fPin">ปักหมุด</span>
                  </label>

                  <div class="wz-acts">
                    <button class="btn btn-primary btn-block" type="submit" data-i18n="btnSave">บันทึก</button>
                    <button class="wz-back" type="button" data-wz-back data-i18n="btnBack">ย้อนกลับ</button>
                  </div>
                  </div>
                </form>

                  </div>
                </div>
              </div>
            </section>

            <!-- ══════════ VIEW: ตั้งภาพหน้าปก ══════════ -->
            <section class="view" data-view="hero">
              <div class="work-head">
                <h1 data-i18n="sideHero">ตั้งภาพหน้าปก</h1>
              </div>

              <!-- ══ 1. ภาพทั้งหมด ══ -->
              <article id="heroSlides" class="step">
                <div class="step-head">
                  <span class="step-no">1</span>
                  <div class="step-head-text">
                    <h2 data-i18n="heroStep2">ภาพทั้งหมด</h2>
                    <p data-i18n="step4Sub">ภาพพื้นหลังที่เลื่อนอัตโนมัติบนหน้าแรก</p>
                  </div>
                  <button id="btnHero" class="btn btn-primary btn-sm" type="button" aria-haspopup="dialog" aria-controls="mHero">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span data-i18n="btnAddSlide">เพิ่มภาพ</span>
                  </button>
                </div>

                <?php /* โมดัลแนบภาพสไลด์ */ ?>
                <div class="fmodal" id="mHero" role="dialog" aria-modal="true" aria-labelledby="mHeroTitle" hidden>
                  <div class="fmodal-card">
                    <div class="fmodal-head">
                      <h2 id="mHeroTitle" data-i18n="heroStep1">เพิ่มภาพ</h2>
                      <button class="fmodal-x" type="button" data-fmodal-close aria-label="ปิด">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                      </button>
                    </div>
                    <div class="fmodal-body">

                      <form id="heroForm" method="post" enctype="multipart/form-data" data-form>
                        <input type="hidden" name="action" value="hero_add">
                        <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo ANNOUNCEMENT_MAX_FILE_SIZE; ?>">

                        <div class="field">
                          <label for="hero_image" data-i18n="fSlideImage">ไฟล์ภาพ</label>
                          <input id="hero_image" name="hero_image" type="file" accept=".jpg,.jpeg,.png,.webp" data-photo-input required>
                          <p class="hint" data-i18n="fSlideHint">แนะนำภาพแนวนอน ขนาดใหญ่กว่า 1600px</p>
                        </div>

                        <button class="btn btn-primary btn-block" type="submit" data-i18n="btnUpload">อัปโหลด</button>
                      </form>

                    </div>
                  </div>
                </div>

                <?php if (count($heroSlides) === 0) { ?>
                  <div class="blank" data-i18n="blankSlides">ยังไม่มีภาพสไลด์</div>
                <?php } else { ?>
                  <div class="slide-grid">
                    <?php foreach ($heroSlides as $slideIndex => $slide) { ?>
                      <figure class="slide">
                        <img src="<?php echo announcement_h(hero_url($slide)); ?>" alt="" loading="lazy">

                        <span class="slide-no"><?php echo $slideIndex + 1; ?></span>

                        <?php /* ลบ — กากบาทมุมขวาบน */ ?>
                        <form class="slide-x-form" method="post" onsubmit="return confirm(tr('askDeleteSlide'));">
                          <input type="hidden" name="action" value="hero_delete">
                          <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                          <input type="hidden" name="slide_id" value="<?php echo announcement_h($slide['id']); ?>">
                          <button class="slide-x" type="submit" title="ลบภาพ" aria-label="ลบภาพ">×</button>
                        </form>

                        <?php /* สลับลำดับที่แสดง */ ?>
                        <div class="slide-acts">
                          <form method="post">
                            <input type="hidden" name="action" value="hero_move">
                            <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                            <input type="hidden" name="slide_id" value="<?php echo announcement_h($slide['id']); ?>">
                            <input type="hidden" name="dir" value="up">
                            <button class="slide-btn" type="submit" title="สลับไปก่อนหน้า" aria-label="สลับไปก่อนหน้า"<?php echo $slideIndex === 0 ? ' disabled' : ''; ?>>
                              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                          </form>
                          <form method="post">
                            <input type="hidden" name="action" value="hero_move">
                            <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                            <input type="hidden" name="slide_id" value="<?php echo announcement_h($slide['id']); ?>">
                            <input type="hidden" name="dir" value="down">
                            <button class="slide-btn" type="submit" title="สลับไปถัดไป" aria-label="สลับไปถัดไป"<?php echo $slideIndex === count($heroSlides) - 1 ? ' disabled' : ''; ?>>
                              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                          </form>
                        </div>
                      </figure>
                    <?php } ?>
                  </div>
                <?php } ?>
              </article>
            </section>

            <!-- ══════════ VIEW: จัดการแอปพลิเคชัน ══════════ -->
            <section class="view" data-view="apps">
              <div class="work-head">
                <h1 data-i18n="sideApps">จัดการแอปพลิเคชัน</h1>
              </div>

              <!-- ══ 1. แอปพลิเคชัน ══ -->
              <article id="appList" class="step">
                <div class="step-head">
                  <span class="step-no">1</span>
                  <div class="step-head-text">
                    <h2 data-i18n="step5Title">แอปพลิเคชัน</h2>
                    <p data-i18n="step5Sub">แอปที่แสดงในหน้าภาพรวม</p>
                  </div>
                  <button id="btnAppCat" class="btn btn-ghost btn-sm" type="button" aria-haspopup="dialog" aria-controls="mAppCat">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span data-i18n="btnAddAppCat">เพิ่มหมวด</span>
                  </button>
                  <button id="btnApp" class="btn btn-primary btn-sm" type="button" aria-haspopup="dialog" aria-controls="mApp">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span data-i18n="btnAddApp">เพิ่มระบบงาน</span>
                  </button>
                </div>

                <?php /* โมดัลเพิ่มหมวด */ ?>
                <div class="fmodal" id="mAppCat" role="dialog" aria-modal="true" aria-labelledby="mAppCatTitle" hidden>
                  <div class="fmodal-card">
                    <div class="fmodal-head">
                      <h2 id="mAppCatTitle" data-i18n="btnAddAppCat">เพิ่มหมวด</h2>
                      <button class="fmodal-x" type="button" data-fmodal-close aria-label="ปิด">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                      </button>
                    </div>
                    <div class="fmodal-body">

                      <form id="appCatForm" method="post" data-form>
                        <input type="hidden" name="action" value="app_cat_add">
                        <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">

                        <div class="lang-tabs" role="tablist" aria-label="ภาษา">
                          <button class="lang-tab" type="button" role="tab" aria-selected="true" data-tab="th">ไทย</button>
                          <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="en">EN</button>
                          <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="my">MY</button>
                        </div>

                        <div class="lang-panel is-on" data-panel="th">
                          <div class="field">
                            <label for="app_cat_th" data-i18n="fAppCatName">ชื่อหมวด</label>
                            <input id="app_cat_th" name="app_cat_th" type="text" maxlength="60" required placeholder="เช่น บุคคล, จอง/นัด">
                          </div>
                        </div>
                        <div class="lang-panel" data-panel="en">
                          <div class="field">
                            <label for="app_cat_en" data-i18n="fAppCatName">ชื่อหมวด</label>
                            <input id="app_cat_en" name="app_cat_en" type="text" maxlength="60" placeholder="e.g. HR, Booking">
                          </div>
                        </div>
                        <div class="lang-panel" data-panel="my">
                          <div class="field">
                            <label for="app_cat_my" data-i18n="fAppCatName">ชื่อหมวด</label>
                            <input id="app_cat_my" name="app_cat_my" type="text" maxlength="60" placeholder="ဥပမာ HR, ဘွတ်ကင်">
                          </div>
                        </div>

                        <button class="btn btn-primary btn-block" type="submit" data-i18n="btnAddCat">เพิ่ม</button>
                      </form>

                    </div>
                  </div>
                </div>

                <!-- หมวดที่สร้างไว้ — กดเพื่อกรองแอป (เหมือนแท็บในตัวอย่างประกาศ) -->
                <div class="bar">
                  <div class="segs" role="tablist" aria-label="กรองตามหมวด">
                    <button class="seg" type="button" role="tab" aria-selected="true" data-appcat="__all">
                      <span data-i18n="segAll">ทั้งหมด</span><b><?php echo count($appList); ?></b>
                    </button>
                    <?php foreach ($appCats as $ac) { ?>
                      <?php
                        $acCount = 0;
                        foreach ($appList as $appRow) {
                          if (isset($appRow['cat']) && $appRow['cat'] === $ac['id']) {
                            $acCount++;
                          }
                        }
                      ?>
                      <span class="seg-wrap">
                        <button class="seg" type="button" role="tab" aria-selected="false" data-appcat="<?php echo announcement_h($ac['id']); ?>"
                                data-name-th="<?php echo announcement_h($ac['th']); ?>"
                                data-name-en="<?php echo announcement_h($ac['en']); ?>"
                                data-name-my="<?php echo announcement_h($ac['my']); ?>">
                          <span><?php echo announcement_h($ac['th']); ?></span><b><?php echo $acCount; ?></b>
                        </button>
                        <form method="post" onsubmit="return confirm(tr('askDeleteAppCat'));">
                          <input type="hidden" name="action" value="app_cat_delete">
                          <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                          <input type="hidden" name="app_cat_id" value="<?php echo announcement_h($ac['id']); ?>">
                          <button type="submit" class="seg-del" aria-label="ลบหมวด <?php echo announcement_h($ac['th']); ?>" title="ลบ">×</button>
                        </form>
                      </span>
                    <?php } ?>
                  </div>
                </div>

                <?php /* โมดัลเพิ่มระบบงาน */ ?>
                <div class="fmodal" id="mApp" role="dialog" aria-modal="true" aria-labelledby="mAppTitle" hidden>
                  <div class="fmodal-card">
                    <div class="fmodal-head">
                      <h2 id="mAppTitle" data-i18n="btnAddApp">เพิ่มระบบงาน</h2>
                      <button class="fmodal-x" type="button" data-fmodal-close aria-label="ปิด">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                      </button>
                    </div>
                    <div class="fmodal-body">

                <form id="appForm" method="post" enctype="multipart/form-data" data-form>
                  <input type="hidden" name="action" value="app_add">
                  <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                  <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo ANNOUNCEMENT_MAX_FILE_SIZE; ?>">

                  <div class="field">
                    <label for="app_cat" data-i18n="fAppCat">หมวด</label>
                    <select id="app_cat" name="app_cat">
                      <?php foreach ($appCats as $ac) { ?>
                        <option value="<?php echo announcement_h($ac['id']); ?>"><?php echo announcement_h($ac['th']); ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <div class="field is-icon-pick" data-cover-slot="icon" data-ratio="1" data-crop-mode="icon">
                    <label for="app_icon">
                      <span data-i18n="fAppIcon">ไอคอน</span>
                      <b class="tag-ratio">1:1</b>
                    </label>
                    <div class="pick-row">
                      <input id="app_icon" name="app_icon" type="file" accept=".png,.jpg,.jpeg,.webp,.svg" data-cover-input>
                      <input type="hidden" name="app_icon_cropped" value="" data-cover-data>
                      <button class="btn btn-ghost btn-sm is-icon" type="button" data-crop-open hidden title="จัดภาพ">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3v13a2 2 0 0 0 2 2h13M18 21V8a2 2 0 0 0-2-2H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="sr-only" data-i18n="btnCrop">จัดภาพ</span>
                      </button>
                    </div>
                    <div class="pick-preview" data-cover-preview hidden></div>
                    <p class="hint" data-i18n="fAppIconHint">ล้อเมาส์ย่อ-ขยาย · ลากจัดตำแหน่ง</p>
                  </div>

                  <div class="field">
                    <label for="app_url" data-i18n="fAppUrl">ลิงก์</label>
                    <input id="app_url" name="app_url" type="text" placeholder="http://192.168.5.7/..." required>
                  </div>

                  <div class="lang-tabs" role="tablist" aria-label="ภาษา">
                    <button class="lang-tab" type="button" role="tab" aria-selected="true" data-tab="th">ไทย</button>
                    <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="en">EN</button>
                    <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="my">MY</button>
                  </div>

                  <div class="lang-panel is-on" data-panel="th">
                    <div class="field">
                      <label for="app_name_th" data-i18n="fAppName">ชื่อแอป</label>
                      <input id="app_name_th" name="app_name_th" type="text" maxlength="80" placeholder="เช่น ระบบจองห้องประชุม" required>
                    </div>
                    <div class="field">
                      <label for="app_desc_th" data-i18n="fAppDesc">คำอธิบาย</label>
                      <input id="app_desc_th" name="app_desc_th" type="text" maxlength="160" placeholder="เช่น จองห้องประชุมและอุปกรณ์ส่วนกลาง">
                    </div>
                  </div>

                  <div class="lang-panel" data-panel="en">
                    <div class="field">
                      <label for="app_name_en" data-i18n="fAppName">ชื่อแอป</label>
                      <input id="app_name_en" name="app_name_en" type="text" maxlength="80" placeholder="e.g. Meeting Room">
                    </div>
                    <div class="field">
                      <label for="app_desc_en" data-i18n="fAppDesc">คำอธิบาย</label>
                      <input id="app_desc_en" name="app_desc_en" type="text" maxlength="160" placeholder="e.g. Book meeting rooms and facilities">
                    </div>
                  </div>

                  <div class="lang-panel" data-panel="my">
                    <div class="field">
                      <label for="app_name_my" data-i18n="fAppName">ชื่อแอป</label>
                      <input id="app_name_my" name="app_name_my" type="text" maxlength="80" placeholder="ဥပမာ အစည်းအဝေးခန်း">
                    </div>
                    <div class="field">
                      <label for="app_desc_my" data-i18n="fAppDesc">คำอธิบาย</label>
                      <input id="app_desc_my" name="app_desc_my" type="text" maxlength="160" placeholder="ဥပမာ အစည်းအဝေးခန်း ဘွတ်ကင်">
                    </div>
                  </div>

                  <button class="btn btn-primary btn-block" type="submit" data-i18n="btnSave">บันทึก</button>
                </form>

                    </div>
                  </div>
                </div>

                <?php if (count($appList) === 0) { ?>
                  <div class="blank" data-i18n="blankApps">ยังไม่มีระบบงาน</div>
                <?php } else { ?>
                  <div class="app-grid">
                    <?php foreach ($appList as $app) { ?>
                      <?php
                        $appId = isset($app['id']) ? $app['id'] : '';
                        $appSafeId = preg_replace('/[^A-Za-z0-9_]/', '_', $appId);
                        $appIcon = app_icon_url($app);
                        $appCatId = isset($app['cat']) ? $app['cat'] : '';

                        /* ชื่อไฟล์ตอนดาวน์โหลด — ใช้ id ของแอป (ASCII ล้วน) + นามสกุลเดิม
                           กัน browser งงกับชื่อไฟล์ภาษาไทย/พม่า */
                        $appIconRaw = isset($app['icon']) ? (string) $app['icon'] : '';
                        $appIconExt = strtolower(pathinfo($appIconRaw, PATHINFO_EXTENSION));
                        $appIconFile = '';
                        if ($appIcon !== '') {
                          $appIconBase = preg_replace('/[^A-Za-z0-9._-]/', '-', $appId);
                          if ($appIconBase === '') { $appIconBase = 'app-icon'; }
                          $appIconFile = $appIconBase . ($appIconExt !== '' ? '.' . $appIconExt : '');
                        }
                      ?>
                      <article class="row" data-app-row data-cat="<?php echo announcement_h($appCatId); ?>">
                        <span class="app-cat-tag" data-app-cat-tag
                              data-name-th="<?php echo announcement_h(app_category_label($appCatId, 'th')); ?>"
                              data-name-en="<?php echo announcement_h(app_category_label($appCatId, 'en')); ?>"
                              data-name-my="<?php echo announcement_h(app_category_label($appCatId, 'my')); ?>"><?php echo announcement_h(app_category_label($appCatId, 'th')); ?></span>
                        <div class="row-main">
                          <?php if ($appIcon !== '') { ?>
                            <?php /* กดไอคอน → เปิดโมดัลดูภาพใหญ่ แล้วค่อยกดดาวน์โหลดในนั้น */ ?>
                            <button class="app-icon app-icon-btn" type="button"
                                    data-zoom="<?php echo announcement_h($appIcon); ?>"
                                    data-zoom-name="<?php echo announcement_h(app_name($app, 'th')); ?>"
                                    data-zoom-dl="<?php echo announcement_h($appIconFile); ?>"
                                    title="กดเพื่อดูภาพใหญ่และดาวน์โหลด"
                                    aria-label="ดูไอคอนของ <?php echo announcement_h(app_name($app, 'th')); ?> แบบขยาย">
                              <img src="<?php echo announcement_h($appIcon); ?>" alt="" loading="lazy">
                            </button>
                          <?php } else { ?>
                            <div class="app-icon">
                              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="4" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/></svg>
                            </div>
                          <?php } ?>

                          <div class="row-body">
                            <h3 data-app-title
                                data-name-th="<?php echo announcement_h(app_name($app, 'th')); ?>"
                                data-name-en="<?php echo announcement_h(app_name($app, 'en')); ?>"
                                data-name-my="<?php echo announcement_h(app_name($app, 'my')); ?>"><?php echo announcement_h(app_name($app, 'th')); ?></h3>
                            <p data-app-desc
                               data-desc-th="<?php echo announcement_h(app_desc($app, 'th')); ?>"
                               data-desc-en="<?php echo announcement_h(app_desc($app, 'en')); ?>"
                               data-desc-my="<?php echo announcement_h(app_desc($app, 'my')); ?>"><?php echo announcement_h(app_desc($app, 'th')); ?></p>
                            <a class="app-url" href="<?php echo announcement_h($app['url']); ?>" target="_blank" rel="noopener"><?php echo announcement_h($app['url']); ?></a>
                          </div>
                        </div>

                        <div class="row-acts">
                          <button class="btn btn-ghost btn-sm" type="button" data-edit aria-expanded="false" aria-controls="appedit_<?php echo announcement_h($appSafeId); ?>" data-i18n="btnEdit">แก้ไข</button>
                          <form method="post" onsubmit="return confirm(tr('askDeleteApp'));">
                            <input type="hidden" name="action" value="app_delete">
                            <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                            <input type="hidden" name="app_id" value="<?php echo announcement_h($appId); ?>">
                            <button class="btn btn-danger btn-sm" type="submit" data-i18n="btnDelete">ลบ</button>
                          </form>
                        </div>

                        <?php /* ── โมดัลแก้ไขระบบงาน ── */ ?>
                        <div id="appedit_<?php echo announcement_h($appSafeId); ?>" class="fmodal" role="dialog" aria-modal="true" hidden>
                          <div class="fmodal-card is-wide">
                            <div class="fmodal-head">
                              <h2 data-i18n="formEditApp">แก้ไขระบบงาน</h2>
                              <button class="fmodal-x" type="button" data-fmodal-close aria-label="ปิด">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                              </button>
                            </div>
                            <div class="fmodal-body">

                          <form method="post" enctype="multipart/form-data" data-form>
                            <input type="hidden" name="action" value="app_update">
                            <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                            <input type="hidden" name="app_id" value="<?php echo announcement_h($appId); ?>">
                            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo ANNOUNCEMENT_MAX_FILE_SIZE; ?>">

                            <?php if ($appIcon !== '') { ?>
                              <div class="field">
                                <label data-i18n="fAppIconNow">ไอคอนปัจจุบัน</label>
                                <div class="app-icon-now">
                                  <button class="app-icon app-icon-btn" type="button"
                                          data-zoom="<?php echo announcement_h($appIcon); ?>"
                                          data-zoom-name="<?php echo announcement_h(app_name($app, 'th')); ?>"
                                          data-zoom-dl="<?php echo announcement_h($appIconFile); ?>"
                                          title="กดเพื่อดูภาพใหญ่และดาวน์โหลด"
                                          aria-label="ดูไอคอนแบบขยาย">
                                    <img src="<?php echo announcement_h($appIcon); ?>" alt="" loading="lazy">
                                  </button>
                                  <span class="app-icon-now-text">
                                    <b><?php echo announcement_h($appIconFile); ?></b>
                                    <span data-i18n="fAppIconZoomHint">กดเพื่อดูใหญ่ · ดาวน์โหลดได้</span>
                                  </span>
                                </div>
                              </div>
                            <?php } ?>

                            <div class="grid-2">
                              <div class="field">
                                <label for="eac_<?php echo announcement_h($appSafeId); ?>" data-i18n="fAppCat">หมวด</label>
                                <select id="eac_<?php echo announcement_h($appSafeId); ?>" name="app_cat">
                                  <?php foreach ($appCats as $ac) { ?>
                                    <option value="<?php echo announcement_h($ac['id']); ?>"<?php echo $appCatId === $ac['id'] ? ' selected' : ''; ?>><?php echo announcement_h($ac['th']); ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                              <div class="field is-icon-pick" data-cover-slot="icon" data-ratio="1" data-crop-mode="icon">
                                <label for="eai_<?php echo announcement_h($appSafeId); ?>">
                                  <span data-i18n="fAppIconNew">เปลี่ยนไอคอน</span>
                                  <b class="tag-ratio">1:1</b>
                                </label>
                                <div class="pick-row">
                                  <input id="eai_<?php echo announcement_h($appSafeId); ?>" name="app_icon" type="file" accept=".png,.jpg,.jpeg,.webp,.svg" data-cover-input>
                                  <input type="hidden" name="app_icon_cropped" value="" data-cover-data>
                                  <button class="btn btn-ghost btn-sm is-icon" type="button" data-crop-open hidden title="จัดภาพ">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3v13a2 2 0 0 0 2 2h13M18 21V8a2 2 0 0 0-2-2H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span class="sr-only" data-i18n="btnCrop">จัดภาพ</span>
                                  </button>
                                </div>
                                <div class="pick-preview" data-cover-preview hidden></div>
                                <p class="hint" data-i18n="fAppIconHint">ล้อเมาส์ย่อ-ขยาย · ลากจัดตำแหน่ง</p>
                              </div>
                            </div>

                            <div class="field">
                              <label for="eau_<?php echo announcement_h($appSafeId); ?>" data-i18n="fAppUrl">ลิงก์</label>
                              <input id="eau_<?php echo announcement_h($appSafeId); ?>" name="app_url" type="text" value="<?php echo announcement_h($app['url']); ?>" required>
                            </div>

                            <div class="lang-tabs" role="tablist" aria-label="ภาษา">
                              <button class="lang-tab" type="button" role="tab" aria-selected="true" data-tab="th">ไทย</button>
                              <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="en">EN</button>
                              <button class="lang-tab" type="button" role="tab" aria-selected="false" data-tab="my">MY</button>
                            </div>

                            <div class="lang-panel is-on" data-panel="th">
                              <div class="field">
                                <label for="eant_<?php echo announcement_h($appSafeId); ?>" data-i18n="fAppName">ชื่อแอป</label>
                                <input id="eant_<?php echo announcement_h($appSafeId); ?>" name="app_name_th" type="text" maxlength="80" value="<?php echo announcement_h(announcement_field($app, 'name_th')); ?>" required>
                              </div>
                              <div class="field">
                                <label for="eadt_<?php echo announcement_h($appSafeId); ?>" data-i18n="fAppDesc">คำอธิบาย</label>
                                <input id="eadt_<?php echo announcement_h($appSafeId); ?>" name="app_desc_th" type="text" maxlength="160" value="<?php echo announcement_h(announcement_field($app, 'desc_th')); ?>">
                              </div>
                            </div>

                            <div class="lang-panel" data-panel="en">
                              <div class="field">
                                <label for="eane_<?php echo announcement_h($appSafeId); ?>" data-i18n="fAppName">ชื่อแอป</label>
                                <input id="eane_<?php echo announcement_h($appSafeId); ?>" name="app_name_en" type="text" maxlength="80" value="<?php echo announcement_h(announcement_field($app, 'name_en')); ?>">
                              </div>
                              <div class="field">
                                <label for="eade_<?php echo announcement_h($appSafeId); ?>" data-i18n="fAppDesc">คำอธิบาย</label>
                                <input id="eade_<?php echo announcement_h($appSafeId); ?>" name="app_desc_en" type="text" maxlength="160" value="<?php echo announcement_h(announcement_field($app, 'desc_en')); ?>">
                              </div>
                            </div>

                            <div class="lang-panel" data-panel="my">
                              <div class="field">
                                <label for="eanm_<?php echo announcement_h($appSafeId); ?>" data-i18n="fAppName">ชื่อแอป</label>
                                <input id="eanm_<?php echo announcement_h($appSafeId); ?>" name="app_name_my" type="text" maxlength="80" value="<?php echo announcement_h(announcement_field($app, 'name_my')); ?>">
                              </div>
                              <div class="field">
                                <label for="eadm_<?php echo announcement_h($appSafeId); ?>" data-i18n="fAppDesc">คำอธิบาย</label>
                                <input id="eadm_<?php echo announcement_h($appSafeId); ?>" name="app_desc_my" type="text" maxlength="160" value="<?php echo announcement_h(announcement_field($app, 'desc_my')); ?>">
                              </div>
                            </div>

                            <button class="btn btn-primary btn-block" type="submit" data-i18n="btnUpdate">อัปเดต</button>
                          </form>

                            </div>
                          </div>
                        </div>
                      </article>
                    <?php } ?>
                  </div>
                <?php } ?>
              </article>

            </section>

            <!-- ══════════ VIEW: ทะเบียนประกาศ ══════════ -->
            <section class="view" data-view="register">
              <div class="work-head">
                <h1 data-i18n="sideRegister">ทะเบียนประกาศ</h1>

                <?php if ($adminRegister && $adminRegister['ok']) { ?>
                  <p class="work-note">
                    <?php echo announcement_h(announcement_register_format_updated($adminRegister['updated_at'])); ?>
                    · <span data-i18n="regMetaRows">จำนวนแถว</span> <?php echo count($adminRegisterRows); ?>
                    · <span data-i18n="regMetaHidden">ซ่อนอยู่</span> <?php echo (int) $adminRegister['hidden_count']; ?>
                  </p>
                <?php } ?>
              </div>

              <?php if ($adminRegister && !$adminRegister['ok']) { ?>
                <div class="blank"><?php echo announcement_h($adminRegister['error']); ?></div>
              <?php } ?>

              <?php if (count($adminRegisterRows) === 0) { ?>
                <div class="reg-empty">
                  <p class="blank" data-i18n="regBlank">ยังไม่มีข้อมูล — อัปโหลดไฟล์ Excel ก่อน</p>
                </div>
              <?php } else { ?>
                <form method="post" id="registerForm">
                  <input type="hidden" name="action" value="register_save">
                  <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">

                  <div class="reg-bar">
                    <button id="btnRegisterUp" class="btn btn-ghost btn-sm" type="button" aria-haspopup="dialog" aria-controls="mRegisterUp">
                      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V5m0 0L8 9m4-4 4 4M5 18.5h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      <span data-i18n="regUpload">อัปโหลดไฟล์ Excel</span>
                    </button>

                    <button class="btn btn-ghost btn-sm" type="button" data-regall="1" data-i18n="regShowAll">แสดงทั้งหมด</button>
                    <button class="btn btn-ghost btn-sm" type="button" data-regall="0" data-i18n="regHideAll">ซ่อนทั้งหมด</button>

                    <label class="reg-find">
                      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.7" cy="10.7" r="6.2" stroke="currentColor" stroke-width="1.8"/><path d="m15.5 15.5 4.2 4.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                      <input id="registerFind" type="search" autocomplete="off" placeholder="ค้นหา" data-i18n-placeholder="regFind" aria-label="ค้นหาในตารางทะเบียน">
                    </label>

                    <button class="btn btn-primary btn-sm" type="submit" data-i18n="btnSave">บันทึก</button>
                  </div>

                  <div class="reg-wrap" tabindex="0">
                    <table class="reg-table">
                      <thead>
                        <tr>
                          <th class="reg-c-show" scope="col" data-i18n="regShow">แสดง</th>
                          <th class="reg-c-seq" scope="col"><?php echo announcement_h($adminRegister['headers']['A']); ?></th>
                          <th class="reg-c-no" scope="col"><?php echo announcement_h($adminRegister['headers']['B']); ?></th>
                          <th class="reg-c-sub" scope="col"><?php echo announcement_h($adminRegister['headers']['C']); ?></th>
                          <th class="reg-c-to" scope="col"><?php echo announcement_h($adminRegister['headers']['D']); ?></th>
                          <th class="reg-c-by" scope="col"><?php echo announcement_h($adminRegister['headers']['E']); ?></th>
                          <th class="reg-c-dep" scope="col"><?php echo announcement_h($adminRegister['headers']['F']); ?></th>
                          <th class="reg-c-date" scope="col"><?php echo announcement_h($adminRegister['headers']['G']); ?></th>
                          <th class="reg-c-link" scope="col" data-i18n="regLink">ลิงก์</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($adminRegisterRows as $rIndex => $rRow) { ?>
                          <?php
                            $rKey = $rRow['key'];
                            $rNameBase = 'field[' . announcement_h($rKey) . ']';
                            $rFind = implode(' ', array($rRow['sequence'], $rRow['number'], $rRow['subject'], $rRow['recipient'], $rRow['owner'], $rRow['department'], $rRow['date']));

                            /* ช่องลิงก์โชว์ "ชื่อไฟล์" แบบเดียวกับที่เห็นใน Excel
                               ถ้าแอดมินกรอกลิงก์เองไว้ ก็โชว์ลิงก์นั้นแทน */
                            $rLinkValue = $rRow['link_label'];
                          ?>
                          <tr data-reg-row data-find="<?php echo announcement_h($rFind); ?>"<?php echo $rRow['hidden'] ? ' class="is-off"' : ''; ?>>
                            <td class="reg-c-show">
                              <input type="hidden" name="row_key[]" value="<?php echo announcement_h($rKey); ?>">
                              <input class="reg-check" type="checkbox" name="visible[]" value="<?php echo announcement_h($rKey); ?>"<?php echo $rRow['hidden'] ? '' : ' checked'; ?> aria-label="แสดงแถวนี้บนหน้าเว็บ">
                            </td>
                            <td class="reg-c-seq"><input class="reg-in reg-in-mid" type="text" name="<?php echo $rNameBase; ?>[sequence]" value="<?php echo announcement_h($rRow['sequence']); ?>" maxlength="12"></td>
                            <td class="reg-c-no"><input class="reg-in" type="text" name="<?php echo $rNameBase; ?>[number]" value="<?php echo announcement_h($rRow['number']); ?>" maxlength="40"></td>
                            <td class="reg-c-sub"><textarea class="reg-in reg-in-area" name="<?php echo $rNameBase; ?>[subject]" rows="1" maxlength="500"><?php echo announcement_h($rRow['subject']); ?></textarea></td>
                            <td class="reg-c-to"><input class="reg-in" type="text" name="<?php echo $rNameBase; ?>[recipient]" value="<?php echo announcement_h($rRow['recipient']); ?>" maxlength="120"></td>
                            <td class="reg-c-by"><input class="reg-in" type="text" name="<?php echo $rNameBase; ?>[owner]" value="<?php echo announcement_h($rRow['owner']); ?>" maxlength="120"></td>
                            <td class="reg-c-dep"><input class="reg-in" type="text" name="<?php echo $rNameBase; ?>[department]" value="<?php echo announcement_h($rRow['department']); ?>" maxlength="80"></td>
                            <td class="reg-c-date"><input class="reg-in reg-in-mid" type="text" name="<?php echo $rNameBase; ?>[date]" value="<?php echo announcement_h($rRow['date']); ?>" maxlength="20"></td>
                            <td class="reg-c-link">
                              <?php /* ช่องข้อความอ่านอย่างเดียว — ลิงก์มาจากไฟล์ Excel ของฝ่าย HR
                                       แต่แนบ PDF ทับรายแถวได้ที่ปุ่มคลิปหนีบ (ไฟล์ที่แนบมาก่อน Excel) */ ?>
                              <div class="reg-link-cell<?php echo $rRow['link_kind'] === 'upload' ? ' has-doc' : ''; ?>">
                                <span class="reg-doc-ico" aria-hidden="true">
                                  <svg viewBox="0 0 24 24" fill="none"><path d="M6 3.5h7l5 5V20a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 6 20V3.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M13 3.5V9h5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                                </span>
                                <input class="reg-in reg-link-in" type="text" readonly tabindex="-1"
                                       value="<?php echo announcement_h($rLinkValue); ?>"
                                       placeholder="— ยังไม่มีเอกสาร —"
                                       title="<?php echo announcement_h($rLinkValue !== '' ? $rLinkValue : 'แถวนี้ยังไม่มีเอกสาร — แนบ PDF ได้ที่ปุ่มคลิปหนีบ'); ?>"
                                       data-auto-href="<?php echo announcement_h($rRow['link_url']); ?>">

                                <?php /* แนบ / เปลี่ยน / ลบไฟล์ของแถวนี้ */ ?>
                                <button class="reg-open reg-doc-btn" type="button" data-regdoc
                                        data-key="<?php echo announcement_h($rKey); ?>"
                                        data-subject="<?php echo announcement_h($rRow['subject']); ?>"
                                        data-file="<?php echo announcement_h($rRow['doc_name']); ?>"
                                        title="<?php echo $rRow['doc_name'] !== '' ? 'เปลี่ยน/ลบไฟล์ที่แนบ' : 'แนบไฟล์ PDF'; ?>"
                                        aria-label="แนบไฟล์เอกสาร">
                                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18.5 11.5 12 18a4 4 0 0 1-5.7-5.7l7-7a2.7 2.7 0 0 1 3.8 3.8l-7 7a1.4 1.4 0 0 1-2-2l6.4-6.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>

                                <button class="reg-open" type="button" data-regopen title="เปิดเอกสาร" aria-label="เปิดเอกสาร"<?php echo $rRow['link_url'] === '' ? ' disabled' : ''; ?>>
                                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 5h5v5m0-5-7 7M18 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                              </div>
                            </td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>

                  <p class="blank" id="registerFindNone" data-i18n="regFindNone" hidden>ไม่พบแถวที่ตรงกับคำค้นหา</p>
                </form>
              <?php } ?>

              <?php /* โมดัลอัปโหลดไฟล์ทะเบียน */ ?>
              <div class="fmodal" id="mRegisterUp" role="dialog" aria-modal="true" aria-labelledby="mRegisterUpTitle" hidden>
                <div class="fmodal-card">
                  <div class="fmodal-head">
                    <h2 id="mRegisterUpTitle" data-i18n="regUpload">อัปโหลดไฟล์ Excel</h2>
                    <button class="fmodal-x" type="button" data-fmodal-close aria-label="ปิด">
                      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                  </div>
                  <div class="fmodal-body">
                    <form id="registerUpForm" method="post" enctype="multipart/form-data" data-form>
                      <input type="hidden" name="action" value="register_upload">
                      <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                      <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo ANNOUNCEMENT_MAX_FILE_SIZE; ?>">

                      <div class="field">
                        <label for="register_file" data-i18n="regFileLabel">ไฟล์ .xlsx</label>
                        <input id="register_file" name="register_file" type="file" accept=".xlsx" required>
                        <p class="hint" data-i18n="regFileHint">ค่าที่แก้ไว้และลิงก์จะยังอยู่ครบ</p>
                      </div>

                      <button class="btn btn-primary btn-block" type="submit" data-i18n="btnUpload">อัปโหลด</button>
                    </form>
                  </div>
                </div>
              </div>

              <?php /* โมดัลใส่รหัสผ่านไฟล์ Excel — เปิดอัตโนมัติเมื่อมีไฟล์ล็อกพักรออยู่ */ ?>
              <div class="fmodal" id="mRegPass" role="dialog" aria-modal="true" aria-labelledby="mRegPassTitle" hidden>
                <div class="fmodal-card">
                  <div class="fmodal-head">
                    <h2 id="mRegPassTitle" data-i18n="regPassTitle">ไฟล์นี้ตั้งรหัสผ่านไว้</h2>
                    <button class="fmodal-x" type="button" data-regpass-cancel aria-label="ปิด">
                      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                  </div>
                  <div class="fmodal-body">
                    <p class="reg-pass-file">
                      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10.5V8a5 5 0 0 1 10 0v2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="5" y="10.5" width="14" height="9.5" rx="1.5" stroke="currentColor" stroke-width="1.8"/></svg>
                      <span id="regPassName"><?php echo $registerPending ? announcement_h($registerPending['name']) : ''; ?></span>
                    </p>

                    <form id="regPassForm" method="post" data-form>
                      <input type="hidden" name="action" value="register_unlock">
                      <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">

                      <div class="field">
                        <label for="register_password" data-i18n="regPassLabel">รหัสผ่านของไฟล์</label>
                        <div class="pw">
                          <input id="register_password" name="register_password" type="password" autocomplete="off"
                                 value="<?php echo announcement_h($registerSavedPassword); ?>" required>
                          <button class="pw-eye" id="regPassEye" type="button" aria-pressed="false" aria-controls="register_password" aria-label="แสดงรหัสผ่าน" title="แสดงรหัสผ่าน">
                            <svg class="eye-on" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.9" stroke="currentColor" stroke-width="1.7"/></svg>
                            <svg class="eye-off" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9.9 5.8A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3 3.8M6.4 7.7A16.8 16.8 0 0 0 2.5 12S6 18.5 12 18.5c1.4 0 2.6-.3 3.7-.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="m4 4 16 16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                          </button>
                        </div>
                        <p class="hint" data-i18n="regPassHint">รหัสจากฝ่าย HR · เปลี่ยนเมื่อไรก็พิมพ์ตัวใหม่ได้เลย</p>
                      </div>

                      <label class="check">
                        <input type="checkbox" name="remember_password" value="1" checked>
                        <span data-i18n="regPassRemember">จำรหัสนี้ไว้ ครั้งหน้าไม่ต้องพิมพ์ซ้ำ</span>
                      </label>

                      <button class="btn btn-primary btn-block" type="submit" data-i18n="regPassUnlock">ปลดล็อกและอัปโหลด</button>
                    </form>

                    <?php /* ยกเลิก = ทิ้งไฟล์ที่พักไว้ ไม่ให้ค้างในโฟลเดอร์ */ ?>
                    <form method="post" class="reg-pass-cancel">
                      <input type="hidden" name="action" value="register_cancel_unlock">
                      <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                      <button class="btn btn-ghost btn-sm btn-block" type="submit" data-i18n="btnCancel">ยกเลิก</button>
                    </form>
                  </div>
                </div>
              </div>

              <?php /* โมดัลแนบเอกสารรายแถว — ต้องอยู่นอก #registerForm เพราะ form ซ้อน form ไม่ได้ */ ?>
              <div class="fmodal" id="mRegDoc" role="dialog" aria-modal="true" aria-labelledby="mRegDocTitle" hidden>
                <div class="fmodal-card">
                  <div class="fmodal-head">
                    <h2 id="mRegDocTitle" data-i18n="regDocTitle">แนบเอกสารของแถวนี้</h2>
                    <button class="fmodal-x" type="button" data-fmodal-close aria-label="ปิด">
                      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                  </div>
                  <div class="fmodal-body">
                    <p class="reg-doc-subject" id="regDocSubject"></p>

                    <?php /* ไฟล์ที่แนบไว้แล้ว — ลบได้ (คนละฟอร์มกับตัวอัปโหลด) */ ?>
                    <div class="reg-doc-now" id="regDocNow" hidden>
                      <span class="reg-doc-now-name" id="regDocNowName"></span>
                      <form method="post" id="regDocDelForm" onsubmit="return confirm(tr('askDeleteRegDoc'));">
                        <input type="hidden" name="action" value="register_doc_delete">
                        <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                        <input type="hidden" name="row_key" id="regDocDelKey" value="">
                        <button class="btn btn-danger btn-sm" type="submit" data-i18n="btnDelete">ลบ</button>
                      </form>
                    </div>

                    <form id="regDocForm" method="post" enctype="multipart/form-data" data-form>
                      <input type="hidden" name="action" value="register_doc_upload">
                      <input type="hidden" name="csrf" value="<?php echo announcement_h($csrfToken); ?>">
                      <input type="hidden" name="row_key" id="regDocKey" value="">
                      <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo ANNOUNCEMENT_REGISTER_DOC_MAX; ?>">

                      <div class="field">
                        <label for="register_doc" data-i18n="regDocLabel">ไฟล์ PDF</label>
                        <input id="register_doc" name="register_doc" type="file" accept=".pdf,.jpg,.jpeg,.png" required>
                        <p class="hint" data-i18n="regDocHint">แนบแล้วลิงก์นี้จะไปแทนที่ลิงก์จาก Excel · ไม่เกิน 30MB</p>
                      </div>

                      <button class="btn btn-primary btn-block" type="submit" data-i18n="btnUpload">อัปโหลด</button>
                    </form>
                  </div>
                </div>
              </div>
            </section>

          </div>
        </div>
      </div>
    <?php } ?>
  </main>

  <?php if ($isAdmin && ($message !== '' || count($errors) > 0)) { ?>
    <!-- ══════════════════ MODAL: ผลลัพธ์ ══════════════════ -->
    <?php $resultOk = count($errors) === 0; ?>
    <div class="confirm" id="resultBox" role="dialog" aria-modal="true" aria-labelledby="rsText">
      <div class="confirm-card">
        <span class="confirm-mark<?php echo $resultOk ? '' : ' is-bad'; ?>" aria-hidden="true">
          <?php if ($resultOk) { ?>
            <svg viewBox="0 0 52 52">
              <circle class="cm-ring" cx="26" cy="26" r="23" fill="none"/>
              <path class="cm-tick" fill="none" d="M15 27l7.5 7.5L37 20"/>
            </svg>
          <?php } else { ?>
            <svg viewBox="0 0 52 52">
              <circle class="cm-ring" cx="26" cy="26" r="23" fill="none"/>
              <path class="cm-tick" fill="none" d="M18 18l16 16M34 18L18 34"/>
            </svg>
          <?php } ?>
        </span>

        <p class="confirm-text" id="rsText"><?php echo announcement_h($resultOk ? $message : $errors[0]); ?></p>

        <div class="confirm-acts is-single">
          <button class="btn btn-primary" id="rsOk" type="button" data-i18n="btnOk">ตกลง</button>
        </div>
      </div>
    </div>
  <?php } ?>

  <!-- ══════════════════ MODAL: ยืนยันการบันทึก ══════════════════ -->
  <div class="confirm" id="confirmBox" role="dialog" aria-modal="true" aria-labelledby="cfText" hidden>
    <div class="confirm-card">
      <span class="confirm-mark" aria-hidden="true">
        <svg viewBox="0 0 52 52">
          <circle class="cm-ring" cx="26" cy="26" r="23" fill="none"/>
          <path class="cm-tick" fill="none" d="M15 27l7.5 7.5L37 20"/>
        </svg>
      </span>

      <p class="confirm-text" id="cfText" data-i18n="askSave">ยืนยันการบันทึก?</p>

      <div class="confirm-acts">
        <button class="btn btn-primary" id="cfOk" type="button" data-i18n="btnConfirm">ยืนยัน</button>
        <button class="btn btn-ghost" id="cfNo" type="button" data-i18n="btnCancel">ยกเลิก</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════ MODAL: ดูภาพขยาย ══════════════════ -->
  <div class="zoombox" id="zoomBox" role="dialog" aria-modal="true" aria-label="ดูภาพ" hidden>
    <?php /* ดาวน์โหลดได้เฉพาะจากในโมดัลนี้ — ต้องกดซูมภาพก่อน */ ?>
    <a class="zb-dl" id="zbDl" href="#" download hidden>
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v11m0 0 4-4m-4 4-4-4M5 19h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <span data-i18n="zoomDownload">ดาวน์โหลดภาพ</span>
    </a>
    <button class="zb-close" id="zbClose" type="button" aria-label="ปิด">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <figure class="zb-stage">
      <img id="zbImg" src="" alt="">
      <figcaption id="zbCap"></figcaption>
    </figure>
    <span class="zb-hint" data-i18n="zoomHint">เลื่อนเมาส์เพื่อซูม · ดับเบิลคลิกเพื่อรีเซ็ต</span>
  </div>

  <!-- ══════════════════ MODAL: ครอปภาพปก ══════════════════ -->
  <div class="cropbox" id="cropBox" role="dialog" aria-modal="true" aria-label="ครอปภาพ" hidden>
    <div class="cb-panel">
      <div class="cb-head">
        <h2 id="cbTitle" data-i18n="cropTitle">เลือกส่วนที่จะใช้เป็นภาพปก</h2>
        <button class="cb-x" id="cbCancel" type="button" aria-label="ปิด">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
      </div>

      <!-- กรอบล็อกสัดส่วน — ลากภาพและซูมให้พอดีกรอบ -->
      <div class="cb-stage" id="cbStage">
        <div class="cb-frame" id="cbFrame">
          <img id="cbImg" src="" alt="">
          <div class="cb-grid" aria-hidden="true"></div>
        </div>
      </div>

      <div class="cb-foot">
        <span class="cb-zoom">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.9"/><path d="M8.5 11h5m2.5 5 4.5 4.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
          <input id="cbZoom" type="range" min="100" max="400" value="100" aria-label="ซูม">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.9"/><path d="M11 8.5v5M8.5 11h5m2.5 5 4.5 4.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
        </span>
        <span class="cb-actions">
          <button class="btn btn-ghost btn-sm" id="cbReset" type="button" data-i18n="cropReset">รีเซ็ต</button>
          <button class="btn btn-primary btn-sm" id="cbApply" type="button" data-i18n="cropApply">ใช้ภาพนี้</button>
        </span>
      </div>
    </div>
  </div>

  <!-- ══════════════════ FOOTER ══════════════════ -->
  <footer class="site-footer">
    <div class="shell footer-row">
      <span>SUPAVUT INDUSTRY</span>
      <span><span data-i18n="footerBy">พัฒนาโดยฝ่าย IT</span> · <a href="mailto:<?php echo announcement_h($mailIt); ?>"><?php echo announcement_h($mailIt); ?></a></span>
    </div>
  </footer>

  <script>
    'use strict';

    /* ══════════════════════════════════════════════════════════
       กันแถบเมนูบน/ล่างหลุดจอ

       หน้านี้เป็นเลย์เอาต์ความสูงคงที่ (body/.page/.app = overflow:hidden)
       มีแค่ .work ที่เลื่อนได้ แต่ตอนเปิดด้วย #allPosts เบราว์เซอร์จะ
       เลื่อนกล่องแม่ที่ overflow:hidden ตามไปด้วย (ยังเลื่อนได้ด้วยสคริปต์)
       ทำให้ header กับ footer ถูกดันพ้นจอ และไม่มีแถบเลื่อนให้เลื่อนกลับ
       → บังคับ scrollTop ของกล่องแม่ให้เป็น 0 เสมอ
       ══════════════════════════════════════════════════════════ */
    (function () {
      function outerBoxes() {
        return [
          document.documentElement,
          document.body,
          document.querySelector('.page'),
          document.querySelector('.app')
        ];
      }

      function lockOuterScroll() {
        var boxes = outerBoxes();
        for (var i = 0; i < boxes.length; i++) {
          if (!boxes[i]) { continue; }
          if (boxes[i].scrollTop !== 0) { boxes[i].scrollTop = 0; }
          if (boxes[i].scrollLeft !== 0) { boxes[i].scrollLeft = 0; }
        }
      }

      /* เลื่อนหา #anchor ภายในพื้นที่ที่เลื่อนได้จริงแทน */
      function scrollAnchorIntoWork() {
        var hash = (window.location.hash || '').replace('#', '');
        if (!hash) { return; }

        var target = document.getElementById(hash);
        var work = document.querySelector('.work');
        if (!target || !work) { return; }

        var top = target.getBoundingClientRect().top - work.getBoundingClientRect().top + work.scrollTop;
        work.scrollTop = top > 0 ? top : 0;
        lockOuterScroll();
      }

      lockOuterScroll();

      var boxes = outerBoxes();
      for (var i = 0; i < boxes.length; i++) {
        if (boxes[i]) { boxes[i].addEventListener('scroll', lockOuterScroll); }
      }

      window.addEventListener('load', function () {
        scrollAnchorIntoWork();
        lockOuterScroll();
      });

      window.addEventListener('hashchange', scrollAnchorIntoWork);
    })();

    /* โมดัลแก้ไขที่ต้องเปิดค้างไว้หลังรีโหลด (ตั้งจากฝั่ง PHP หลังลบภาพ/ไฟล์) */
    var REOPEN_ID = '<?php echo announcement_h(preg_replace('/[^A-Za-z0-9_]/', '_', $reopenId)); ?>';
    var REOPEN_STEP = <?php echo $reopenStep === 2 ? 2 : 1; ?>;

    /* มีไฟล์ Excel ที่ล็อกรหัสพักรออยู่ไหม — ถ้ามีให้เปิดโมดัลถามรหัสทันทีที่หน้าโหลด
       REG_PASS_TRIED = เคยลองรหัสแล้วไม่ผ่าน (เอาไว้เลือกข้อความให้ตรงสถานการณ์) */
    var REG_PASS_PENDING = <?php echo $registerPending ? 'true' : 'false'; ?>;
    var REG_PASS_TRIED = <?php echo ($registerPending && !empty($registerPending['tried'])) ? 'true' : 'false'; ?>;

    /* ══════════════════════════════════════════════════════════
       1. คำแปล 3 ภาษา
       ══════════════════════════════════════════════════════════ */
    var t = {
      th: {
        menuHome: 'หน้าแรก', menuLogout: 'ออกจากระบบ',
        loginTitle: 'เข้าสู่ระบบ',
        userLabel: 'ชื่อผู้ใช้', passLabel: 'รหัสผ่าน', loginBtn: 'เข้าสู่ระบบ',
        loginBusy: 'กำลังเข้าสู่ระบบ', pwShow: 'แสดงรหัสผ่าน', pwHide: 'ซ่อนรหัสผ่าน',
        sidePosts: 'ประกาศ',
        searchPh: 'ค้นหา', segAll: 'ทั้งหมด', segNone: 'ไม่มีแท็บ', segPinned: 'ปักหมุด', segPlain: 'ทั่วไป',
        btnAdd: 'เพิ่มประกาศ', btnClose: 'ปิดฟอร์ม',
        step1Title: 'เพิ่มแท็บ',
        step2Title: 'เพิ่มประกาศ', step2Sub: 'กรอกรายละเอียด แล้วเลือกแท็บที่จะให้ประกาศนี้ไปแสดง',
        step3Title: 'ตัวอย่างประกาศ',
        formNewTitle: 'ประกาศใหม่', formEditTitle: 'แก้ไขประกาศ',
        fTitle: 'หัวข้อ', fBody: 'รายละเอียด', fTab: 'แท็บ', fTabNone: '— ไม่ระบุ —', fTabPick: '— เลือกแท็บ —',
        fCover: 'ภาพปก', fCoverNow: 'ภาพปกปัจจุบัน', fCoverNew: 'เปลี่ยนภาพปก', fCoverRemove: 'ลบภาพปก',
        fFiles: 'ไฟล์แนบ', fFilesNow: 'ไฟล์แนบปัจจุบัน', fFilesAdd: 'เพิ่มไฟล์แนบ', fPhotosNow: 'รูปภาพปัจจุบัน',
        fFilesHint: 'ติ๊กไฟล์ที่ต้องการลบ แล้วกดอัปเดต',
        fPin: 'ปักหมุด', tagNew: 'ใหม่', unitFile: 'ไฟล์',
        rowLocked: 'ประกาศระบบ',
        btnSave: 'บันทึก', btnUpdate: 'อัปเดต', btnEdit: 'แก้ไข', btnHide: 'ปิด', btnDelete: 'ลบ', btnAddCat: 'เพิ่ม',
        blankNone: 'ยังไม่มีประกาศ', blankFilter: 'ไม่พบประกาศที่ค้นหา',
        fTabName: 'ชื่อแท็บ', pagePrev: 'ก่อนหน้า', pageNext: 'ถัดไป',
        step4Title: 'ภาพสไลด์หน้าแรก', step4Sub: 'ภาพพื้นหลังที่เลื่อนอัตโนมัติบนหน้าแรก',
        btnAddSlide: 'เพิ่มภาพ', btnUpload: 'อัปโหลด',
        fSlideImage: 'ไฟล์ภาพ', fSlideHint: 'แนะนำภาพแนวนอน ขนาดใหญ่กว่า 1600px',
        blankSlides: 'ยังไม่มีภาพสไลด์', askDeleteSlide: 'ลบภาพนี้?',
        step5Title: 'แอปพลิเคชัน', step5Sub: 'แอปที่แสดงในหน้าภาพรวม',
        btnAddApp: 'เพิ่มระบบงาน', formEditApp: 'แก้ไขระบบงาน',
        fAppCat: 'หมวด', fAppIcon: 'ไอคอน', fAppIconNew: 'เปลี่ยนไอคอน',
        fAppIconNow: 'ไอคอนปัจจุบัน', fAppIconZoomHint: 'กดเพื่อดูใหญ่ · ดาวน์โหลดได้',
        fAppIconHint: 'ล้อเมาส์ย่อ-ขยาย · ลากจัดตำแหน่ง',
        cropIconTitle: 'จัดไอคอนให้พอดีกรอบ 1:1',
        iconSaveHint: 'กดเพื่อดูใหญ่ · เซฟได้',
        fAppUrl: 'ลิงก์', fAppName: 'ชื่อแอป', fAppDesc: 'คำอธิบาย',
        blankApps: 'ยังไม่มีระบบงาน', askDeleteApp: 'ลบระบบงานนี้?',
        sideHero: 'ตั้งภาพหน้าปก', sideApps: 'จัดการแอปพลิเคชัน',
        sideRegister: 'ทะเบียนประกาศ',
        regUpload: 'อัปโหลดไฟล์ Excel', regFileLabel: 'ไฟล์ .xlsx',
        regFileHint: 'ค่าที่แก้ไว้และลิงก์จะยังอยู่ครบ',
        dropPending: 'จะลบเมื่อกดอัปเดต', dropUndo: 'เลิกทำ',
        dropBusy: 'กำลังลบ…', dropFailed: 'ลบไม่สำเร็จ กรุณาลองอีกครั้ง หากหมดเวลาใช้งาน ให้เข้าสู่ระบบใหม่',
        regPassTitle: 'ไฟล์นี้ตั้งรหัสผ่านไว้', regPassWrong: 'รหัสผ่านไม่ถูกต้อง ลองใหม่อีกครั้ง',
        regPassLabel: 'รหัสผ่านของไฟล์', regPassUnlock: 'ปลดล็อกและอัปโหลด',
        regPassHint: 'รหัสจากฝ่าย HR · เปลี่ยนเมื่อไรก็พิมพ์ตัวใหม่ได้เลย',
        regPassRemember: 'จำรหัสนี้ไว้ ครั้งหน้าไม่ต้องพิมพ์ซ้ำ', btnCancel: 'ยกเลิก',
        regDocTitle: 'แนบเอกสารของแถวนี้', regDocLabel: 'ไฟล์ PDF',
        regDocHint: 'แนบแล้วลิงก์นี้จะไปแทนที่ลิงก์จาก Excel · ไม่เกิน 30MB',
        askDeleteRegDoc: 'ลบไฟล์ที่แนบไว้?',
        regMetaRows: 'จำนวนแถว', regMetaHidden: 'ซ่อนอยู่',
        regBlank: 'ยังไม่มีข้อมูล — อัปโหลดไฟล์ Excel ก่อน',
        regShow: 'แสดง', regLink: 'ลิงก์',
        regShowAll: 'แสดงทั้งหมด', regHideAll: 'ซ่อนทั้งหมด',
        regFind: 'ค้นหา', regFindNone: 'ไม่พบแถวที่ตรงกับคำค้นหา',
        heroStep1: 'เพิ่มภาพ', heroStep2: 'ภาพทั้งหมด',
        btnAddAppCat: 'เพิ่มหมวด', fAppCatName: 'ชื่อหมวด',
        askDeleteAppCat: 'ลบหมวดนี้? แอปในหมวดจะกลายเป็นไม่ระบุหมวด',
        askDropCover: 'ลบภาพปกนี้?', askDropFile: 'ลบไฟล์นี้?',
        fPhotos: 'แนบรูปภาพ', fPhotosHint: 'แสดงเป็นแกลเลอรีในหน้าอ่านประกาศ',
        fCover1: 'ภาพปก 1 — หน้าแรก (4:3)', fCover2: 'ภาพปก 2 — หน้าอ่านประกาศ (3:1)',
        fCover1Hint: 'รูปย่อในรายการประกาศหน้าแรก', fCover2Hint: 'ภาพหัวเรื่องใหญ่ ถ้าไม่ใส่จะใช้ภาพปก 1 แทน',
        fCover1Short: 'ภาพปก 1 · หน้าแรก', fCover2Short: 'ภาพปก 2 · หน้าอ่านประกาศ',
        secMedia: 'ภาพและไฟล์', fCover1Name: 'ภาพปก 1', fCover2Name: 'ภาพปก 2',
        fOnHome: 'หน้าแรก', fOnArticle: 'หน้าอ่าน', fPhotosName: 'รูปภาพ', fGallery: 'แกลเลอรี',        btnCrop: 'จัดภาพ', cropTitle: 'เลือกส่วนที่จะใช้เป็นภาพปก',
        cropReset: 'รีเซ็ต', cropApply: 'ใช้ภาพนี้', zoomHint: 'เลื่อนเมาส์เพื่อซูม · ดับเบิลคลิกเพื่อรีเซ็ต',
        zoomDownload: 'ดาวน์โหลดภาพ',
        askSave: 'ยืนยันการบันทึก?', btnConfirm: 'ยืนยัน', btnCancel: 'ยกเลิก', btnOk: 'ตกลง',
        btnNext: 'ไปต่อ', btnBack: 'ย้อนกลับ', fHasMedia: 'แนบรูปภาพหรือไฟล์',
        askDelete: 'ลบประกาศนี้?', askDeleteCat: 'ลบแท็บนี้? ประกาศจะกลายเป็นไม่มีแท็บ',
        footerBy: 'พัฒนาโดยฝ่าย IT'
      },
      en: {
        menuHome: 'Home', menuLogout: 'Sign out',
        loginTitle: 'Sign in',
        userLabel: 'Username', passLabel: 'Password', loginBtn: 'Sign in',
        loginBusy: 'Signing in', pwShow: 'Show password', pwHide: 'Hide password',
        sidePosts: 'Announcements',
        searchPh: 'Search', segAll: 'All', segNone: 'No tab', segPinned: 'Pinned', segPlain: 'Normal',
        btnAdd: 'New announcement', btnClose: 'Close form',
        step1Title: 'Add tab',
        step2Title: 'Add announcement', step2Sub: 'Fill in the details, then choose which tab it appears under',
        step3Title: 'Announcements preview',
        formNewTitle: 'New announcement', formEditTitle: 'Edit announcement',
        fTitle: 'Title', fBody: 'Details', fTab: 'Tab', fTabNone: '— None —', fTabPick: '— Choose a tab —',
        fCover: 'Cover', fCoverNow: 'Current cover', fCoverNew: 'Replace cover', fCoverRemove: 'Remove cover',
        fFiles: 'Attachments', fFilesNow: 'Current files', fFilesAdd: 'Add files', fPhotosNow: 'Current photos',
        fFilesHint: 'Tick files to remove, then update',
        fPin: 'Pin', tagNew: 'New', unitFile: 'files',
        rowLocked: 'System post',
        btnSave: 'Save', btnUpdate: 'Update', btnEdit: 'Edit', btnHide: 'Close', btnDelete: 'Delete', btnAddCat: 'Add',
        blankNone: 'No announcements yet', blankFilter: 'No matching announcements',
        fTabName: 'Tab name', pagePrev: 'Previous', pageNext: 'Next',
        step4Title: 'Home slideshow', step4Sub: 'Background images that auto-scroll on the home page',
        btnAddSlide: 'Add image', btnUpload: 'Upload',
        fSlideImage: 'Image file', fSlideHint: 'Landscape images wider than 1600px work best',
        blankSlides: 'No slides yet', askDeleteSlide: 'Delete this image?',
        step5Title: 'Applications', step5Sub: 'Apps shown on the overview page',
        btnAddApp: 'Add application', formEditApp: 'Edit application',
        fAppCat: 'Category', fAppIcon: 'Icon', fAppIconNew: 'Replace icon',
        fAppIconNow: 'Current icon', fAppIconZoomHint: 'Click to enlarge · download',
        fAppIconHint: 'Scroll to resize · drag to position',
        cropIconTitle: 'Fit the icon to the 1:1 frame',
        iconSaveHint: 'Click to enlarge · save',
        fAppUrl: 'Link', fAppName: 'App name', fAppDesc: 'Description',
        blankApps: 'No applications yet', askDeleteApp: 'Delete this application?',
        sideHero: 'Home slideshow', sideApps: 'Applications',
        sideRegister: 'Announcement register',
        regUpload: 'Upload Excel file', regFileLabel: '.xlsx file',
        regFileHint: 'Your edits and links are kept.',
        dropPending: 'Removed on update', dropUndo: 'Undo',
        dropBusy: 'Deleting…', dropFailed: 'Could not delete. Please retry, or sign in again if your session expired.',
        regPassTitle: 'This file is password protected', regPassWrong: 'Wrong password, try again',
        regPassLabel: 'File password', regPassUnlock: 'Unlock and upload',
        regPassHint: 'The password from HR · type a new one whenever it changes',
        regPassRemember: 'Remember this password for next time', btnCancel: 'Cancel',
        regDocTitle: 'Attach a document to this row', regDocLabel: 'PDF file',
        regDocHint: 'This replaces the link from Excel · 30MB max',
        askDeleteRegDoc: 'Remove the attached file?',
        regMetaRows: 'Rows', regMetaHidden: 'Hidden',
        regBlank: 'No data yet — upload an Excel file first.',
        regShow: 'Show', regLink: 'Link',
        regShowAll: 'Show all', regHideAll: 'Hide all',
        regFind: 'Search', regFindNone: 'No rows match your search',
        heroStep1: 'Add image', heroStep2: 'All images',
        btnAddAppCat: 'Add category', fAppCatName: 'Category name',
        askDeleteAppCat: 'Delete this category? Its apps move to no category.',
        askDropCover: 'Delete this cover image?', askDropFile: 'Delete this file?',
        fPhotos: 'Attach photos', fPhotosHint: 'Shown as a gallery on the article page',
        fCover1: 'Cover 1 — home page (4:3)', fCover2: 'Cover 2 — article page (3:1)',
        fCover1Hint: 'Thumbnail in the home page list', fCover2Hint: 'Large header image; falls back to cover 1',
        fCover1Short: 'Cover 1 · home', fCover2Short: 'Cover 2 · article',
        secMedia: 'Media', fCover1Name: 'Cover 1', fCover2Name: 'Cover 2',
        fOnHome: 'home', fOnArticle: 'article', fPhotosName: 'Photos', fGallery: 'gallery',        btnCrop: 'Adjust', cropTitle: 'Choose the cover area',
        cropReset: 'Reset', cropApply: 'Use this', zoomHint: 'Scroll to zoom · double-click to reset',
        zoomDownload: 'Download image',
        askSave: 'Save these changes?', btnConfirm: 'Confirm', btnCancel: 'Cancel', btnOk: 'OK',
        btnNext: 'Next', btnBack: 'Back', fHasMedia: 'Attach photos or files',
        askDelete: 'Delete this announcement?', askDeleteCat: 'Delete this tab? Its announcements move to "No tab".',
        footerBy: 'Built by IT'
      },
      my: {
        menuHome: 'ပင်မ', menuLogout: 'ထွက်ရန်',
        loginTitle: 'ဝင်ရန်',
        userLabel: 'အသုံးပြုသူ', passLabel: 'စကားဝှက်', loginBtn: 'ဝင်ရန်',
        loginBusy: 'ဝင်နေသည်', pwShow: 'စကားဝှက် ပြရန်', pwHide: 'စကားဝှက် ဖျောက်ရန်',
        sidePosts: 'ကြေညာချက်',
        searchPh: 'ရှာရန်', segAll: 'အားလုံး', segNone: 'Tab မရှိ', segPinned: 'ပင်တွဲထား', segPlain: 'ပုံမှန်',
        btnAdd: 'ကြေညာချက် အသစ်', btnClose: 'ပိတ်ရန်',
        step1Title: 'Tab ထည့်ရန်',
        step2Title: 'ကြေညာချက် ထည့်ရန်', step2Sub: 'အသေးစိတ် ဖြည့်ပြီး ပြမည့် Tab ကို ရွေးပါ',
        step3Title: 'ကြေညာချက် နမူနာ',
        formNewTitle: 'ကြေညာချက် အသစ်', formEditTitle: 'ကြေညာချက် ပြင်ရန်',
        fTitle: 'ခေါင်းစဉ်', fBody: 'အသေးစိတ်', fTab: 'Tab', fTabNone: '— မရွေးပါ —', fTabPick: '— Tab ရွေးပါ —',
        fCover: 'မျက်နှာဖုံး', fCoverNow: 'လက်ရှိ မျက်နှာဖုံး', fCoverNew: 'မျက်နှာဖုံး ပြောင်းရန်', fCoverRemove: 'မျက်နှာဖုံး ဖျက်ရန်',
        fFiles: 'ပူးတွဲဖိုင်', fFilesNow: 'လက်ရှိ ဖိုင်များ', fFilesAdd: 'ဖိုင် ထပ်ထည့်ရန်', fPhotosNow: 'လက်ရှိ ဓာတ်ပုံများ',
        fFilesHint: 'ဖျက်လိုသော ဖိုင်ကို အမှတ်ခြစ်ပြီး Update နှိပ်ပါ',
        fPin: 'ပင်တွဲရန်', tagNew: 'အသစ်', unitFile: 'ဖိုင်',
        rowLocked: 'စနစ် ကြေညာချက်',
        btnSave: 'သိမ်းရန်', btnUpdate: 'Update', btnEdit: 'ပြင်ရန်', btnHide: 'ပိတ်ရန်', btnDelete: 'ဖျက်ရန်', btnAddCat: 'ထည့်ရန်',
        blankNone: 'ကြေညာချက် မရှိသေးပါ', blankFilter: 'ရှာဖွေမှုနှင့် ကိုက်ညီသည် မရှိပါ',
        fTabName: 'Tab အမည်', pagePrev: 'ရှေ့သို့', pageNext: 'နောက်သို့',
        step4Title: 'ပင်မ ဆလိုက်ရှိုး', step4Sub: 'ပင်မစာမျက်နှာတွင် အလိုအလျောက် ရွေ့သော နောက်ခံပုံများ',
        btnAddSlide: 'ပုံ ထည့်ရန်', btnUpload: 'တင်ရန်',
        fSlideImage: 'ပုံဖိုင်', fSlideHint: 'အလျားလိုက်ပုံ 1600px ထက်ကြီးသည် အကောင်းဆုံး',
        blankSlides: 'ဆလိုက် မရှိသေးပါ', askDeleteSlide: 'ဤပုံကို ဖျက်မလား?',
        step5Title: 'အက်ပ်များ', step5Sub: 'ခြုံငုံသုံးသပ်ချက် စာမျက်နှာတွင် ပြသည့် အက်ပ်များ',
        btnAddApp: 'အက်ပ် ထည့်ရန်', formEditApp: 'အက်ပ် ပြင်ရန်',
        fAppCat: 'အမျိုးအစား', fAppIcon: 'အိုင်ကွန်', fAppIconNew: 'အိုင်ကွန် ပြောင်းရန်',
        fAppIconNow: 'လက်ရှိ အိုင်ကွန်', fAppIconZoomHint: 'နှိပ်၍ ချဲ့ကြည့် · ဒေါင်းလုဒ်',
        fAppIconHint: 'လှိမ့်၍ ချုံ့-ချဲ့ · ဆွဲ၍ နေရာချ',
        cropIconTitle: '1:1 ဘောင်နှင့် အံကိုက် ချိန်ပါ',
        iconSaveHint: 'နှိပ်၍ ချဲ့ကြည့် · သိမ်းပါ',
        fAppUrl: 'လင့်ခ်', fAppName: 'အက်ပ်အမည်', fAppDesc: 'ဖော်ပြချက်',
        blankApps: 'အက်ပ် မရှိသေးပါ', askDeleteApp: 'ဤအက်ပ်ကို ဖျက်မလား?',
        sideHero: 'ပင်မ ဆလိုက်ရှိုး', sideApps: 'အက်ပ် စီမံရန်',
        sideRegister: 'ကြေညာချက် မှတ်ပုံတင်',
        regUpload: 'Excel ဖိုင် တင်ရန်', regFileLabel: '.xlsx ဖိုင်',
        regFileHint: 'ပြင်ထားသည်များနှင့် လင့်ခ်များ ကျန်နေမည်',
        dropPending: 'Update နှိပ်မှ ဖျက်မည်', dropUndo: 'ပြန်ဖျက်',
        dropBusy: 'ဖျက်နေသည်…', dropFailed: 'ဖျက်၍မရပါ။ ထပ်မံကြိုးစားပါ သို့မဟုတ် ပြန်လည်ဝင်ရောက်ပါ။',
        regPassTitle: 'ဤဖိုင်တွင် စကားဝှက် ရှိသည်', regPassWrong: 'စကားဝှက် မှားသည် ထပ်စမ်းပါ',
        regPassLabel: 'ဖိုင် စကားဝှက်', regPassUnlock: 'ဖွင့်၍ တင်ရန်',
        regPassHint: 'HR ထံမှ စကားဝှက် · ပြောင်းလျှင် အသစ် ရိုက်ပါ',
        regPassRemember: 'ဤစကားဝှက်ကို မှတ်ထားရန်', btnCancel: 'မလုပ်တော့',
        regDocTitle: 'ဤအတန်းအတွက် စာရွက်စာတမ်း တွဲရန်', regDocLabel: 'PDF ဖိုင်',
        regDocHint: 'Excel မှ လင့်ခ်ကို အစားထိုးမည် · 30MB ထက် မကျော်ရ',
        askDeleteRegDoc: 'တွဲထားသော ဖိုင်ကို ဖျက်မလား?',
        regMetaRows: 'အတန်း အရေအတွက်', regMetaHidden: 'ဖျောက်ထားသည်',
        regBlank: 'အချက်အလက် မရှိသေးပါ — Excel ဖိုင် အရင်တင်ပါ',
        regShow: 'ပြရန်', regLink: 'လင့်ခ်',
        regShowAll: 'အားလုံး ပြရန်', regHideAll: 'အားလုံး ဖျောက်ရန်',
        regFind: 'ရှာရန်', regFindNone: 'ကိုက်ညီသော အတန်း မတွေ့ပါ',
        heroStep1: 'ပုံ ထည့်ရန်', heroStep2: 'ပုံအားလုံး',
        btnAddAppCat: 'အမျိုးအစား ထည့်ရန်', fAppCatName: 'အမျိုးအစား အမည်',
        askDeleteAppCat: 'ဤအမျိုးအစားကို ဖျက်မလား? အက်ပ်များ အမျိုးအစားမရှိ ဖြစ်သွားမည်။',
        askDropCover: 'မျက်နှာဖုံးပုံ ဖျက်မလား?', askDropFile: 'ဤဖိုင်ကို ဖျက်မလား?',
        fPhotos: 'ဓာတ်ပုံ ပူးတွဲရန်', fPhotosHint: 'ကြေညာချက်စာမျက်နှာတွင် ဓာတ်ပုံစုအဖြစ် ပြမည်',
        fCover1: 'မျက်နှာဖုံး 1 — ပင်မ (4:3)', fCover2: 'မျက်နှာဖုံး 2 — ကြေညာချက် (3:1)',
        fCover1Hint: 'ပင်မစာမျက်နှာ စာရင်းရှိ ပုံငယ်', fCover2Hint: 'ခေါင်းစီးပုံကြီး၊ မထည့်လျှင် မျက်နှာဖုံး 1 သုံးမည်',
        fCover1Short: 'မျက်နှာဖုံး 1 · ပင်မ', fCover2Short: 'မျက်နှာဖုံး 2 · ကြေညာချက်',
        secMedia: 'ပုံနှင့်ဖိုင်', fCover1Name: 'မျက်နှာဖုံး 1', fCover2Name: 'မျက်နှာဖုံး 2',
        fOnHome: 'ပင်မ', fOnArticle: 'ကြေညာချက်', fPhotosName: 'ဓာတ်ပုံ', fGallery: 'ဓာတ်ပုံစု',        btnCrop: 'ချိန်ညှိရန်', cropTitle: 'မျက်နှာဖုံး အပိုင်း ရွေးပါ',
        cropReset: 'ပြန်စ', cropApply: 'ဤပုံ သုံးရန်', zoomHint: 'ဇူးမ်ရန် လှိမ့်ပါ · ပြန်စရန် နှစ်ချက်နှိပ်ပါ',
        zoomDownload: 'ပုံ ဒေါင်းလုဒ်',
        askSave: 'သိမ်းဆည်းရန် အတည်ပြုမလား?', btnConfirm: 'အတည်ပြု', btnCancel: 'ပယ်ဖျက်', btnOk: 'အိုကေ',
        btnNext: 'ရှေ့သို့', btnBack: 'နောက်သို့', fHasMedia: 'ဓာတ်ပုံ သို့ ဖိုင် ပူးတွဲရန်',
        askDelete: 'ဤကြေညာချက်ကို ဖျက်မလား?', askDeleteCat: 'ဤ Tab ကို ဖျက်မလား? ကြေညာချက်များ "Tab မရှိ" ဖြစ်သွားမည်။',
        footerBy: 'IT ဌာနမှ ဖန်တီးသည်'
      }
    };

    /* ══════════════════════════════════════════════════════════
       2. สถานะ + ตัวช่วย
       ══════════════════════════════════════════════════════════ */
    var LANG_KEY = 'simenu_language';
    var langs = ['th', 'en', 'my'];
    var lang = 'th';
    var PER_PAGE = 5;
    var page = 1;
    var cat = '__all';      /* แท็บ (แถวล่าง) */
    var pinCat = '__all';   /* ตัวกรองหลัก (แถวบน) */
    var query = '';

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
    function tr(key) { return (t[lang] && t[lang][key]) || t.th[key] || key; }

    var rows = $$('[data-row]');
    var createCard = $('#createCard');
    var btnCreate = $('#btnCreate');
    var pager = $('#pager');
    var blankFilter = $('#blankFilter');
    var heroForm = $('#heroForm');
    var btnHero = $('#btnHero');
    var appForm = $('#appForm');
    var btnApp = $('#btnApp');
    var workPane = $('.work');

    /* ══════════════════════════════════════════════════════════
       3. ภาษา
       ══════════════════════════════════════════════════════════ */
    function readLang() {
      try {
        var v = localStorage.getItem(LANG_KEY);
        return langs.indexOf(v) > -1 ? v : 'th';
      } catch (e) { return 'th'; }
    }

    function setLang(next, save) {
      lang = langs.indexOf(next) > -1 ? next : 'th';
      document.documentElement.lang = lang;

      if (save !== false) {
        try { localStorage.setItem(LANG_KEY, lang); } catch (e) {}
      }

      $$('[data-i18n]').forEach(function (el) {
        var key = el.getAttribute('data-i18n');
        if (t[lang] && t[lang][key]) { el.textContent = t[lang][key]; }
      });

      $$('[data-i18n-placeholder]').forEach(function (el) {
        var key = el.getAttribute('data-i18n-placeholder');
        if (t[lang] && t[lang][key]) { el.placeholder = t[lang][key]; }
      });

      /* หัวข้อ/เนื้อหาประกาศ */
      rows.forEach(function (row) {
        var title = row.getAttribute('data-title-' + lang) || row.getAttribute('data-title-th');
        var body = row.getAttribute('data-body-' + lang) || row.getAttribute('data-body-th');
        var tEl = $('[data-title]', row);
        var bEl = $('[data-body]', row);
        if (tEl && title) { tEl.textContent = title; }
        if (bEl) { bEl.textContent = body || ''; }
      });

      /* ชื่อแท็บ (ปุ่มกรอง) */
      $$('.seg[data-cat]').forEach(function (el) {
        var name = el.getAttribute('data-name-' + lang);
        var span = el.querySelector('span');
        if (name && span) { span.textContent = name; }
      });

      /* จำนวนไฟล์แนบ */
      $$('[data-files]').forEach(function (el) {
        el.textContent = el.getAttribute('data-n') + ' ' + tr('unitFile');
      });

      /* ชื่อหมวดแอป (ปุ่มกรอง + ป้ายบนการ์ด) */
      $$('.seg[data-appcat], [data-app-cat-tag], [data-cat-tag]').forEach(function (el) {
        var name = el.getAttribute('data-name-' + lang);
        if (!name) { return; }
        var span = el.querySelector('span');
        if (span) { span.textContent = name; } else { el.textContent = name; }
      });

      /* ชื่อ/คำอธิบายระบบงานตามภาษา */
      $$('[data-app-title]').forEach(function (el) {
        var name = el.getAttribute('data-name-' + lang) || el.getAttribute('data-name-th');
        if (name) { el.textContent = name; }
      });

      $$('[data-app-desc]').forEach(function (el) {
        var d = el.getAttribute('data-desc-' + lang) || el.getAttribute('data-desc-th');
        el.textContent = d || '';
      });

      syncEditLabels();
      syncPwEye();
      syncTargetTab();

      var flag = $('#langFlag');
      if (flag) { flag.src = 'img/flags/' + lang + '.png'; }

      $$('#langPanel .pop-item').forEach(function (b) {
        b.setAttribute('aria-current', b.getAttribute('data-lang') === lang ? 'true' : 'false');
      });

      /* ป้ายกำกับปุ่มแบ่งหน้าเปลี่ยนตามภาษา */
      render();
    }

    /* ══════════════════════════════════════════════════════════
       4. เมนูซ้าย — สลับหน้า
       ══════════════════════════════════════════════════════════ */
    function setView(name) {
      var found = false;

      $$('[data-view]').forEach(function (v) {
        var on = v.getAttribute('data-view') === name;
        v.classList.toggle('is-on', on);
        if (on) { found = true; }
      });

      if (!found) { return setView('posts'); }

      $$('[data-goto]').forEach(function (b) {
        var on = b.getAttribute('data-goto') === name;
        b.classList.toggle('is-on', on);
        b.setAttribute('aria-current', on ? 'page' : 'false');
      });

      /* textarea วัดความสูงไม่ได้ตอนกล่องยังถูกซ่อน ต้องวัดใหม่ตอนเปิดแท็บ */
      if (name === 'register') { growAllRegisterAreas(); }
    }

    /* กรองแอปตามหมวด */
    function filterApps(catId) {
      $$('[data-app-row]').forEach(function (row) {
        var rowCat = row.getAttribute('data-cat') || '';
        row.classList.toggle('is-filtered', catId !== '__all' && rowCat !== catId);
      });

      $$('.seg[data-appcat]').forEach(function (s) {
        s.setAttribute('aria-selected', s.getAttribute('data-appcat') === catId ? 'true' : 'false');
      });
    }

    /* เปิดแท็บให้ตรงกับ #hash ที่เด้งกลับมาหลังบันทึก */
    function viewFromHash() {
      var h = (window.location.hash || '').replace('#', '');
      if (h === 'heroSlides') { return 'hero'; }
      if (h === 'appList') { return 'apps'; }
      if (h === 'registerFile' || h === 'registerTable') { return 'register'; }
      return 'posts';
    }

    /* ══════════════════════════════════════════════════════════
       ทะเบียนประกาศ
       ══════════════════════════════════════════════════════════ */
    /* ค้นหาในตาราง — ซ่อนแถวที่ไม่ตรง แต่ยังส่ง row_key[] ครบทุกแถวตอนบันทึก
       (ใช้ class ซ่อน ไม่ได้ถอด input ออกจากฟอร์ม) */
    function filterRegister(term) {
      var rows = $$('[data-reg-row]');
      var needle = (term || '').trim().toLowerCase();
      var shown = 0;

      rows.forEach(function (row) {
        var hay = (row.getAttribute('data-find') || '').toLowerCase();
        var hit = needle === '' || hay.indexOf(needle) !== -1;
        row.classList.toggle('is-filtered', !hit);
        if (hit) { shown++; }
      });

      var none = $('#registerFindNone');
      if (none) { none.hidden = rows.length === 0 || shown > 0; }
    }

    /* ติ๊ก/ถอดติ๊กทั้งหมด — เฉพาะแถวที่มองเห็นอยู่ตอนนั้น
       ค้นหาไว้แล้วกด "ซ่อนทั้งหมด" จึงมีผลกับผลค้นหาเท่านั้น */
    function bulkRegister(on) {
      $$('[data-reg-row]').forEach(function (row) {
        if (row.classList.contains('is-filtered')) { return; }
        var box = row.querySelector('.reg-check');
        if (box) {
          box.checked = on;
          row.classList.toggle('is-off', !on);
        }
      });
    }

    /* ช่อง "เรื่อง" สูงตามข้อความเอง ไม่ต้องลากขยาย */
    function growRegisterArea(area) {
      if (!area) { return; }
      area.style.height = 'auto';
      area.style.height = (area.scrollHeight + 2) + 'px';
    }

    function growAllRegisterAreas() {
      $$('.reg-in-area').forEach(growRegisterArea);
      showRegisterLinkTails();
    }

    /* ช่องลิงก์เก็บพาธเต็ม ซึ่งยาวกว่าช่องเสมอ — เลื่อนไปให้เห็นท้ายพาธ (ชื่อไฟล์)
       ไม่งั้นจะเห็นแต่ชื่อโฟลเดอร์ซึ่งทุกแถวเหมือนกันหมด
       ต้องหักด้วย clientWidth ไม่งั้นเลื่อนเลยข้อความไปจนช่องดูว่างเปล่า
       และต้องรอเฟรมถัดไป เพราะตอนแท็บเพิ่งถูกเปิด ความกว้างยังวัดไม่ได้ */
    function showRegisterLinkTails() {
      window.requestAnimationFrame(function () {
        $$('.reg-link-in').forEach(function (input) {
          if (input === document.activeElement) { return; }

          var over = input.scrollWidth - input.clientWidth;
          input.scrollLeft = over > 0 ? over : 0;
        });
      });
    }

    /* ══════════════════════════════════════════════════════════
       5. กรอง + ค้นหา + แบ่งหน้า
       ══════════════════════════════════════════════════════════ */
    function syncTargetTab() {
      var select = $('#category');
      var special = ['__all', '__none', '__pinned', '__plain'];
      var isReal = special.indexOf(cat) === -1;
      if (select) { select.value = isReal ? cat : ''; }
    }

    /* แถวล่าง — กดซ้ำที่แท็บเดิมเพื่อยกเลิกการกรอง */
    function selectTab(id) {
      cat = (cat === id) ? '__all' : id;
      page = 1;

      $$('.seg[data-cat]').forEach(function (s) {
        s.setAttribute('aria-selected', s.getAttribute('data-cat') === cat ? 'true' : 'false');
      });

      syncTargetTab();
      render();
    }

    /* แถวบน — ทำงานแยกจากแท็บ ทั้งสองปุ่มติดสีพร้อมกันได้ */
    function selectPin(id) {
      pinCat = id;
      page = 1;

      $$('.seg[data-pin]').forEach(function (s) {
        s.setAttribute('aria-selected', s.getAttribute('data-pin') === id ? 'true' : 'false');
      });

      render();
    }

    function matches(row) {
      var rowCat = row.getAttribute('data-cat') || '';
      var isPin = row.getAttribute('data-pinned') === '1';

      /* ตัวกรองหลัก (ทั้งหมด / ปักหมุด / ทั่วไป) */
      if (pinCat === '__pinned' && !isPin) { return false; }
      if (pinCat === '__plain' && isPin) { return false; }

      /* ตัวกรองแท็บ — ทำงานร่วมกับตัวกรองหลัก */
      if (cat === '__none' && rowCat !== '') { return false; }
      if (cat !== '__all' && cat !== '__none' && rowCat !== cat) { return false; }

      if (query === '') { return true; }

      var hay = [
        row.getAttribute('data-title-th'), row.getAttribute('data-title-en'), row.getAttribute('data-title-my'),
        row.getAttribute('data-body-th'), row.getAttribute('data-body-en'), row.getAttribute('data-body-my')
      ].join(' ').toLowerCase();

      return hay.indexOf(query) > -1;
    }

    function render() {
      var visible = [];

      rows.forEach(function (row) {
        var ok = matches(row);
        row.classList.toggle('is-filtered', !ok);
        if (ok) { visible.push(row); }
      });

      var total = Math.max(1, Math.ceil(visible.length / PER_PAGE));
      if (page > total) { page = total; }
      if (page < 1) { page = 1; }

      visible.forEach(function (row, i) {
        row.classList.toggle('is-paged-out', Math.floor(i / PER_PAGE) + 1 !== page);

        /* เลขลำดับนับใหม่ตามผลกรองปัจจุบัน */
        var no = row.querySelector('.row-no');
        if (no) { no.textContent = i + 1; }
      });

      if (blankFilter) {
        blankFilter.hidden = !(rows.length > 0 && visible.length === 0);
      }

      if (!pager) { return; }

      if (total <= 1) {
        pager.innerHTML = '';
        return;
      }

      var prevSvg = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      var nextSvg = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

      var html = '<button type="button" class="pg-nav" data-page="' + (page - 1) + '"' +
        (page === 1 ? ' disabled' : '') + ' aria-label="' + tr('pagePrev') + '" title="' + tr('pagePrev') + '">' + prevSvg + '</button>';

      for (var p = 1; p <= total; p++) {
        html += '<button type="button" class="pg-num" data-page="' + p + '" aria-current="' + (p === page ? 'true' : 'false') + '">' + p + '</button>';
      }

      html += '<button type="button" class="pg-nav" data-page="' + (page + 1) + '"' +
        (page === total ? ' disabled' : '') + ' aria-label="' + tr('pageNext') + '" title="' + tr('pageNext') + '">' + nextSvg + '</button>';

      pager.innerHTML = html;
    }

    /* ══════════════════════════════════════════════════════════
       6. ฟอร์ม
       ══════════════════════════════════════════════════════════ */
    /* ติ๊ก "แนบรูปภาพหรือไฟล์" → เปิด/ปิดช่องแนบ
       ใช้ change ไม่ใช่ click เพราะกดที่ป้ายข้อความ target จะไม่ใช่ checkbox */
    /* ช่องค้นหาในตารางทะเบียน + ติ๊กแสดง/ซ่อนรายแถว */
    document.addEventListener('input', function (e) {
      if (!e.target) { return; }

      if (e.target.id === 'registerFind') {
        filterRegister(e.target.value);
        return;
      }

      if (e.target.classList && e.target.classList.contains('reg-in-area')) {
        growRegisterArea(e.target);
      }
    });

    document.addEventListener('change', function (e) {
      var regBox = e.target;
      if (regBox && regBox.classList && regBox.classList.contains('reg-check')) {
        var regRow = regBox.closest('[data-reg-row]');
        if (regRow) { regRow.classList.toggle('is-off', !regBox.checked); }
      }
    });

    document.addEventListener('change', function (e) {
      var tick = e.target;
      if (!tick || !tick.hasAttribute || !tick.hasAttribute('data-toggle-media')) { return; }

      var form = tick.closest('form');
      var box = form ? form.querySelector('[data-media-fields]') : null;
      if (!box) { return; }

      box.hidden = !tick.checked;

      /* ไม่ติ๊กแล้ว → ล้างไฟล์ที่เลือกไว้ ไม่ให้ค้างไปตอนบันทึก */
      if (!tick.checked) {
        $$('input[type="file"]', box).forEach(function (f) {
          f.value = '';
          renderPicked(f);
        });
      }
    });

    /* ── ฟอร์มแบบ 2 ขั้น ── */
    function wzGo(form, n) {
      if (!form) { return; }

      $$('.wz-step', form).forEach(function (s) {
        s.classList.toggle('is-on', s.getAttribute('data-wz') === String(n));
      });

      /* เลื่อนกลับขึ้นบนสุดของโมดัล */
      var body = form.closest('.fmodal-body');
      if (body) { body.scrollTop = 0; }
    }

    /* ตรวจเฉพาะช่องที่อยู่ในขั้นนั้น */
    function wzValid(form, n) {
      if (!form) { return true; }
      var step = form.querySelector('.wz-step[data-wz="' + n + '"]');
      if (!step) { return true; }

      var fields = $$('input, select, textarea', step);
      for (var i = 0; i < fields.length; i++) {
        if (typeof fields[i].reportValidity === 'function' && !fields[i].checkValidity()) {
          fields[i].reportValidity();
          return false;
        }
      }
      return true;
    }

    /* ── โมดัลฟอร์ม ── */
    function fmodalOpen(m, focusSel) {
      if (!m) { return; }
      m.hidden = false;
      document.body.style.overflow = 'hidden';

      if (focusSel) {
        var f = m.querySelector(focusSel);
        if (f) { f.focus(); }
      }
    }

    function fmodalClose(m) {
      if (!m) { return; }
      m.hidden = true;
      if (!document.querySelector('.fmodal:not([hidden])')) {
        document.body.style.overflow = '';
      }
    }

    function syncEditLabels() {
      $$('[data-edit]').forEach(function (b) {
        b.textContent = tr('btnEdit');
      });
    }

    /* ปุ่มเปิด/ปิดฟอร์มของภาพสไลด์และระบบงาน */
    function syncPanelLabel(btn, form, openKey, closeKey) {
      if (!btn || !form) { return; }
      var open = form.classList.contains('is-on');
      var label = $('span', btn);
      if (label) { label.textContent = open ? tr(closeKey) : tr(openKey); }
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function togglePanel(btn, form, openKey, closeKey) {
      if (!form) { return; }
      form.classList.toggle('is-on', !form.classList.contains('is-on'));
      syncPanelLabel(btn, form, openKey, closeKey);
    }

    /* ปุ่มแสดง/ซ่อนรหัสผ่าน */
    function syncPwEye() {
      var eye = $('#pwEye');
      if (!eye) { return; }
      var shown = eye.getAttribute('aria-pressed') === 'true';
      var label = tr(shown ? 'pwHide' : 'pwShow');
      eye.setAttribute('aria-label', label);
      eye.title = label;
    }

    function setFormTab(form, name) {
      $$('[data-tab]', form).forEach(function (b) {
        b.setAttribute('aria-selected', b.getAttribute('data-tab') === name ? 'true' : 'false');
      });
      $$('[data-panel]', form).forEach(function (p) {
        p.classList.toggle('is-on', p.getAttribute('data-panel') === name);
      });
    }

    /* ══════════════════════════════════════════════════════════
       7. เหตุการณ์
       ══════════════════════════════════════════════════════════ */
    document.addEventListener('click', function (e) {
      /* เมนูป๊อปอัป */
      if (e.target.closest('#menuBtn')) {
        var mp = $('#menuPanel');
        var mOpen = !mp.classList.contains('on');
        $$('.pop-panel').forEach(function (p) { p.classList.remove('on'); });
        mp.classList.toggle('on', mOpen);
        $('#menuBtn').setAttribute('aria-expanded', mOpen ? 'true' : 'false');
        return;
      }

      if (e.target.closest('#langBtn')) {
        var lp = $('#langPanel');
        var lOpen = !lp.classList.contains('on');
        $$('.pop-panel').forEach(function (p) { p.classList.remove('on'); });
        lp.classList.toggle('on', lOpen);
        $('#langBtn').setAttribute('aria-expanded', lOpen ? 'true' : 'false');
        return;
      }

      var langItem = e.target.closest('#langPanel [data-lang]');
      if (langItem) {
        setLang(langItem.getAttribute('data-lang'));
        closePops();
        return;
      }

      /* เมนูซ้าย — สลับหน้า */
      var goto = e.target.closest('[data-goto]');
      if (goto) {
        setView(goto.getAttribute('data-goto'));
        return;
      }

      /* กรองแอปตามหมวด */
      var appSeg = e.target.closest('.seg[data-appcat]');
      if (appSeg) {
        filterApps(appSeg.getAttribute('data-appcat'));
        return;
      }

      /* กรองหลัก (ทั้งหมด / ปักหมุด / ทั่วไป) */
      var pinSeg = e.target.closest('.seg[data-pin]');
      if (pinSeg) {
        selectPin(pinSeg.getAttribute('data-pin'));
        return;
      }

      /* กรองตามแท็บ (ตัวอย่างประกาศ) */
      var seg = e.target.closest('.seg[data-cat]');
      if (seg) {
        selectTab(seg.getAttribute('data-cat'));
        return;
      }

      /* แบ่งหน้า */
      var pg = e.target.closest('[data-page]');
      if (pg) {
        page = Number(pg.getAttribute('data-page'));
        render();
        if (workPane) { workPane.scrollTo({ top: 0, behavior: 'smooth' }); }
        return;
      }

      /* เปิดโมดัลเพิ่มประกาศ — เริ่มที่ขั้นที่ 1 เสมอ */
      if (e.target.closest('#btnCreate')) {
        syncTargetTab();
        wzGo($('#createCard'), 1);
        fmodalOpen($('#mPost'), 'select[name="category"]');
        return;
      }

      /* ไปต่อ — ตรวจช่องที่จำเป็นในขั้นที่ 1 ก่อน */
      var nextBtn = e.target.closest('[data-wz-next]');
      if (nextBtn) {
        var wForm = nextBtn.closest('form');
        if (!wzValid(wForm, 1)) { return; }
        wzGo(wForm, 2);
        return;
      }

      var backBtn = e.target.closest('[data-wz-back]');
      if (backBtn) {
        wzGo(backBtn.closest('form'), 1);
        return;
      }


      /* เปิดโมดัลเพิ่มแท็บ */
      if (e.target.closest('#btnTab')) {
        fmodalOpen($('#mTab'), 'input[name="cat_name_th"]');
        return;
      }

      /* ปิดโมดัล — ปุ่มกากบาท หรือคลิกพื้นหลัง */
      if (e.target.closest('[data-fmodal-close]')) {
        fmodalClose(e.target.closest('.fmodal'));
        return;
      }

      if (e.target.classList && e.target.classList.contains('fmodal')) {
        fmodalClose(e.target);
        return;
      }

      /* เปิดโมดัลเพิ่มภาพสไลด์ */
      if (e.target.closest('#btnHero')) {
        if (heroForm) { heroForm.reset(); renderPicked($('#hero_image')); }
        fmodalOpen($('#mHero'), 'input[type="file"]');
        return;
      }

      /* ── ทะเบียนประกาศ ── */
      if (e.target.closest('#btnRegisterUp')) {
        var ruForm = $('#registerUpForm');
        if (ruForm) { ruForm.reset(); }
        fmodalOpen($('#mRegisterUp'), 'input[type="file"]');
        return;
      }

      var regBulk = e.target.closest('[data-regall]');
      if (regBulk) {
        bulkRegister(regBulk.getAttribute('data-regall') === '1');
        return;
      }

      /* เปิดเอกสารของแถวนั้น — ถ้าช่องยังเป็นชื่อไฟล์เดิมให้เปิดผ่าน URL ที่ระบบเตรียมไว้
         ถ้าแอดมินพิมพ์ลิงก์ใหม่ทับ ก็เปิดลิงก์ที่พิมพ์ได้เลยโดยไม่ต้องบันทึกก่อน */
      /* Delete immediately without submitting or resetting the draft edit form. */
      var dropBtn = e.target.closest('[data-drop-cover], [data-drop-file]');
      if (dropBtn) {
        var holder = dropBtn.closest('.cover-card') || dropBtn.closest('.tile');
        if (!holder) { return; }
        var flag = holder.querySelector('[data-drop-flag]');
        var editForm = dropBtn.closest('form');
        if (!flag || !editForm || editForm.getAttribute('data-drop-busy') === '1') { return; }
        var payload = new FormData();
        payload.append('action', dropBtn.hasAttribute('data-drop-cover') ? 'drop_cover' : 'drop_file');
        payload.append('instant_drop', '1');
        payload.append('csrf', editForm.querySelector('[name="csrf"]').value);
        payload.append('id', editForm.querySelector('[name="id"]').value);
        if (dropBtn.hasAttribute('data-drop-cover')) {
          payload.append('slot', dropBtn.getAttribute('data-drop-cover'));
        } else {
          payload.append('file_id', flag.value);
        }
        editForm.setAttribute('data-drop-busy', '1');
        dropBtn.disabled = true;
        dropBtn.textContent = '…';
        dropBtn.title = tr('dropBusy');
        fetch(window.location.pathname, { method: 'POST', body: payload, credentials: 'same-origin' })
          .then(function (response) { if (!response.ok) { throw new Error('delete'); } return response.json(); })
          .then(function (result) {
            if (!result || result.ok !== true) { throw new Error('delete'); }
            var tileItem = holder.closest('.tile-item') || holder;
            tileItem.parentNode.removeChild(tileItem);
          })
          .catch(function () {
            dropBtn.disabled = false;
            dropBtn.textContent = '×';
            dropBtn.title = tr('btnDelete');
            window.alert(tr('dropFailed'));
          })
          .then(function () { editForm.removeAttribute('data-drop-busy'); });
        return;
      }

      /* ปุ่มคลิปหนีบ → โมดัลแนบเอกสารของแถวนั้น */
      var regDoc = e.target.closest('[data-regdoc]');
      if (regDoc) {
        var rdKey = regDoc.getAttribute('data-key');
        var rdFile = regDoc.getAttribute('data-file') || '';
        var rdForm = $('#regDocForm');

        if (rdForm) { rdForm.reset(); renderPicked($('#register_doc')); }
        $('#regDocKey').value = rdKey;
        $('#regDocDelKey').value = rdKey;
        $('#regDocSubject').textContent = regDoc.getAttribute('data-subject') || '';

        var rdNow = $('#regDocNow');
        rdNow.hidden = rdFile === '';
        $('#regDocNowName').textContent = rdFile;

        fmodalOpen($('#mRegDoc'), 'input[name="register_doc"]');
        return;
      }

      var regOpen = e.target.closest('[data-regopen]');
      if (regOpen) {
        var linkCell = regOpen.closest('.reg-link-cell');
        var linkInput = linkCell ? linkCell.querySelector('.reg-link-in') : null;
        var href = linkInput ? (linkInput.getAttribute('data-auto-href') || '') : '';
        if (href !== '') { window.open(href, '_blank', 'noopener'); }
        return;
      }


      /* เปิดโมดัลเพิ่มหมวด */
      if (e.target.closest('#btnAppCat')) {
        var acForm = $('#appCatForm');
        if (acForm) { acForm.reset(); }
        fmodalOpen($('#mAppCat'), 'input[name="app_cat_th"]');
        return;
      }

      /* เปิดโมดัลเพิ่มระบบงาน */
      if (e.target.closest('#btnApp')) {
        if (appForm) { appForm.reset(); renderPicked($('#app_icon')); }
        fmodalOpen($('#mApp'), 'input[name="app_url"]');
        return;
      }

      /* เปิดโมดัลแก้ไข — เริ่มที่ขั้นที่ 1 เสมอ */
      var editBtn = e.target.closest('[data-edit]');
      if (editBtn) {
        var eBox = document.getElementById(editBtn.getAttribute('aria-controls'));
        if (eBox) { wzGo(eBox.querySelector('form[data-form]'), 1); }
        /* ประกาศเริ่มที่ช่อง "หัวข้อ" · ระบบงานเริ่มที่ช่อง "ลิงก์" */
        fmodalOpen(eBox, 'input[name="title"], input[name="app_url"]');
        return;
      }

      /* แท็บภาษาในฟอร์ม */
      var formTab = e.target.closest('[data-tab]');
      if (formTab) {
        var form = formTab.closest('[data-form]');
        if (form) { setFormTab(form, formTab.getAttribute('data-tab')); }
        return;
      }

      /* แสดง/ซ่อนรหัสผ่าน */
      var eye = e.target.closest('#pwEye');
      if (eye) {
        var pw = $('#password');
        if (!pw) { return; }
        var show = eye.getAttribute('aria-pressed') !== 'true';
        pw.type = show ? 'text' : 'password';
        eye.setAttribute('aria-pressed', show ? 'true' : 'false');
        syncPwEye();
        pw.focus();
        return;
      }

      /* คลิกนอกเมนู */
      if (!e.target.closest('.pop')) { closePops(); }
    });

    /* สถานะกำลังเข้าสู่ระบบ — กันกดซ้ำ */
    var loginForm = $('#loginForm');
    if (loginForm) {
      loginForm.addEventListener('submit', function () {
        var btn = $('#loginBtn');
        if (!btn) { return; }
        var spin = $('.spin', btn);
        var label = $('span[data-i18n]', btn);
        btn.setAttribute('aria-busy', 'true');
        if (spin) { spin.hidden = false; }
        if (label) { label.textContent = tr('loginBusy'); }
        /* ปิดปุ่มหลังส่งข้อมูลแล้ว เพื่อไม่ให้ฟอร์มถูกยกเลิก */
        setTimeout(function () { btn.disabled = true; }, 0);
      });
    }

    function closePops() {
      $$('.pop-panel').forEach(function (p) { p.classList.remove('on'); });
      var mb = $('#menuBtn');
      var lb = $('#langBtn');
      if (mb) { mb.setAttribute('aria-expanded', 'false'); }
      if (lb) { lb.setAttribute('aria-expanded', 'false'); }
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closePops(); }
    });

    var searchInput = $('#search');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        query = searchInput.value.trim().toLowerCase();
        page = 1;
        render();
      });
    }

    window.addEventListener('storage', function (e) {
      if (e.key === LANG_KEY) { setLang(e.newValue || 'th', false); }
    });

    /* ══════════════════════════════════════════════════════════
       7.5 โมดัลยืนยันก่อนบันทึก
       ══════════════════════════════════════════════════════════ */
    var cfBox = $('#confirmBox');
    var cfPending = null;

    /* โมดัลผลลัพธ์หลังบันทึก — ล็อกการเลื่อนจนกว่าจะกดตกลง */
    var rsBox = $('#resultBox');
    if (rsBox) {
      document.body.style.overflow = 'hidden';

      /* ปิดผลลัพธ์แล้ว ถ้ายังมีโมดัลอื่นเปิดค้าง (เช่น แก้ไขประกาศ) ต้องล็อกหน้าไว้ต่อ */
      var rsClose = function () {
        rsBox.hidden = true;
        if (!document.querySelector('.fmodal:not([hidden])')) {
          document.body.style.overflow = '';
        }
      };

      $('#rsOk').addEventListener('click', rsClose);
      rsBox.addEventListener('click', function (e) {
        if (e.target === rsBox) { rsClose(); }
      });
    }

    /* action ที่ต้องถามยืนยันก่อนส่ง */
    var CONFIRM_ACTIONS = ['create', 'update', 'create_category', 'hero_add', 'app_cat_add', 'app_add', 'app_update',
      'register_upload', 'register_save', 'register_doc_upload'];

    function confirmOpen(form) {
      cfPending = form;
      cfBox.hidden = false;
      document.body.style.overflow = 'hidden';

      /* เล่นอนิเมชันเครื่องหมายถูกใหม่ทุกครั้ง */
      var mark = cfBox.querySelector('.confirm-mark');
      if (mark) {
        var clone = mark.cloneNode(true);
        mark.parentNode.replaceChild(clone, mark);
      }

      $('#cfOk').focus();
    }

    function confirmClose() {
      if (!cfBox) { return; }
      cfBox.hidden = true;
      cfPending = null;

      if (!document.querySelector('.fmodal:not([hidden])')) {
        document.body.style.overflow = '';
      }
    }

    if (cfBox) {
      $('#cfOk').addEventListener('click', function () {
        if (!cfPending) { return; }
        var form = cfPending;
        cfPending = null;
        cfBox.hidden = true;
        form.setAttribute('data-confirmed', '1');

        /* ส่งฟอร์มจริง — ใช้ปุ่ม submit เดิมเพื่อให้ค่าปุ่มถูกส่งไปด้วย */
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
        } else {
          form.submit();
        }
      });

      $('#cfNo').addEventListener('click', confirmClose);

      cfBox.addEventListener('click', function (e) {
        if (e.target === cfBox) { confirmClose(); }
      });
    }

    /* ดักการส่งฟอร์มทุกจุดที่อยู่ในรายการ */
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (form && form.getAttribute('data-drop-busy') === '1') { e.preventDefault(); return; }
      if (!form || form.getAttribute('data-confirmed') === '1') { return; }

      var actionEl = form.querySelector('input[name="action"]');
      if (!actionEl) { return; }
      if (CONFIRM_ACTIONS.indexOf(actionEl.value) === -1) { return; }

      /* ให้ HTML5 validation ทำงานก่อน */
      if (typeof form.checkValidity === 'function' && !form.checkValidity()) { return; }

      e.preventDefault();
      confirmOpen(form);
    });

    /* ══════════════════════════════════════════════════════════
       8. โมดัลดูภาพขยาย (ใช้ร่วมทุกจุด)
       ══════════════════════════════════════════════════════════ */
    var zb = $('#zoomBox');
    var zbImg = $('#zbImg');
    var zbCap = $('#zbCap');
    var zbDl = $('#zbDl');
    var zS = 1, zTx = 0, zTy = 0;

    function zbApply() {
      zbImg.style.transform = 'translate(' + zTx + 'px,' + zTy + 'px) scale(' + zS + ')';
      zbImg.classList.toggle('is-zoomed', zS > 1);
    }

    function zbReset() { zS = 1; zTx = 0; zTy = 0; zbApply(); }

    /* dlName = ชื่อไฟล์ตอนดาวน์โหลด — ใส่มาเมื่อไหร่ ปุ่มดาวน์โหลดถึงจะโผล่ */
    function zbOpen(src, name, dlName) {
      if (!zb) { return; }
      zbImg.src = src;
      zbImg.alt = name || '';
      zbCap.textContent = name || '';

      if (zbDl) {
        if (dlName) {
          zbDl.href = src;
          zbDl.setAttribute('download', dlName);
          zbDl.hidden = false;
        } else {
          zbDl.hidden = true;
          zbDl.removeAttribute('href');
        }
      }

      zbReset();
      zb.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    function zbClose() {
      if (!zb) { return; }
      zb.hidden = true;
      zbImg.src = '';

      if (zbDl) {
        zbDl.hidden = true;
        zbDl.removeAttribute('href');
      }

      /* ถ้ายังมีโมดัลฟอร์มเปิดอยู่ข้างหลัง ต้องล็อกการเลื่อนต่อ */
      if (!document.querySelector('.fmodal:not([hidden])')) {
        document.body.style.overflow = '';
      }
    }

    if (zb) {
      $('#zbClose').addEventListener('click', zbClose);

      zb.addEventListener('click', function (e) {
        if (e.target === zb) { zbClose(); }
      });

      zbImg.addEventListener('wheel', function (e) {
        e.preventDefault();
        var prev = zS;
        zS = Math.min(5, Math.max(1, zS * (e.deltaY < 0 ? 1.15 : 1 / 1.15)));

        if (zS === 1) {
          zTx = 0; zTy = 0;
        } else {
          var r = zbImg.getBoundingClientRect();
          var cx = e.clientX - (r.left + r.width / 2);
          var cy = e.clientY - (r.top + r.height / 2);
          var k = zS / prev;
          zTx = cx - (cx - zTx) * k;
          zTy = cy - (cy - zTy) * k;
        }
        zbApply();
      }, { passive: false });

      zbImg.addEventListener('dblclick', zbReset);

      var zDrag = false, zsx = 0, zsy = 0;

      zbImg.addEventListener('pointerdown', function (e) {
        if (zS <= 1) { return; }
        zDrag = true;
        zsx = e.clientX - zTx;
        zsy = e.clientY - zTy;
        zbImg.classList.add('is-dragging');
        zbImg.setPointerCapture(e.pointerId);
      });

      zbImg.addEventListener('pointermove', function (e) {
        if (!zDrag) { return; }
        zTx = e.clientX - zsx;
        zTy = e.clientY - zsy;
        zbApply();
      });

      zbImg.addEventListener('pointerup', function () {
        zDrag = false;
        zbImg.classList.remove('is-dragging');
      });
    }

    /* กดรูปย่อของไฟล์แนบเดิม → เปิดโมดัล */
    document.addEventListener('click', function (e) {
      var z = e.target.closest('[data-zoom]');
      if (z) {
        zbOpen(z.getAttribute('data-zoom'), z.getAttribute('data-zoom-name'), z.getAttribute('data-zoom-dl'));
      }
    });

    /* ══════════════════════════════════════════════════════════
       9. ตัวอย่างไฟล์ที่เลือก — ทุกช่องอัปโหลดในหน้านี้
       ══════════════════════════════════════════════════════════ */
    /* กล่องตัวอย่าง — วางไว้ "เหนือ" ช่องเลือกไฟล์เสมอ
       ถ้าฟิลด์นี้ชี้ไปยังกล่องรายการปัจจุบัน ให้ไปต่อท้ายในกล่องนั้นแทน */
    function pickBoxFor(input) {
      var fld = input.closest('[data-cover-slot]') || input.closest('.field') || input.parentNode;

      /* มีกล่อง "ปัจจุบัน" ที่ผูกไว้ไหม */
      var targetSel = fld.getAttribute && fld.getAttribute('data-assets-target');
      if (targetSel) {
        var host = document.querySelector(targetSel);
        if (host) {
          var inHost = host.querySelector('[data-picked-for="' + input.id + '"]');
          if (!inHost) {
            inHost = document.createElement('div');
            inHost.className = 'assets';
            inHost.setAttribute('data-picked-for', input.id);
            host.appendChild(inHost);
          }
          return inHost;
        }
      }

      /* ช่องภาพปกใช้กล่องเดียวกับผลลัพธ์การครอป */
      var box = fld.querySelector('[data-cover-preview], [data-picked]');

      if (!box) {
        box = document.createElement('div');
        box.setAttribute('data-picked', '');
      }

      /* ใช้สไตล์เดียวกับรายการปัจจุบัน และย้ายให้อยู่เหนือช่องเลือกไฟล์เสมอ */
      box.className = 'assets';
      var anchor = fld.querySelector('.pick-row') || input;

      if (box.nextSibling !== anchor) {
        fld.insertBefore(box, anchor);
      }

      return box;
    }

    /* ไอคอนตามชนิดไฟล์ — ชุดเดียวกับ announcement_file_icon_url() ฝั่ง PHP */
    function fileIconFor(name) {
      var ext = (name.split('.').pop() || '').toLowerCase();

      if (ext === 'pdf') { return 'img/file-icons/pdf.png'; }
      if (ext === 'doc' || ext === 'docx') { return 'img/file-icons/word.webp'; }
      if (ext === 'xls' || ext === 'xlsx') { return 'img/file-icons/excel.jpg'; }
      if (ext === 'ppt' || ext === 'pptx') { return 'img/file-icons/powerpoint.jpg'; }
      if (['jpg', 'jpeg', 'png', 'webp'].indexOf(ext) > -1) { return 'img/file-icons/image.png'; }

      return '';
    }

    /* ลบไฟล์ที่เลือกออกจาก input (ต้องสร้าง FileList ใหม่) */
    function dropPicked(input, index) {
      var dt = new DataTransfer();
      var files = input.files;

      for (var i = 0; i < files.length; i++) {
        if (i !== index) { dt.items.add(files[i]); }
      }

      input.files = dt.files;
      renderPicked(input);
    }
    /* ── ตัวอย่างไอคอนระบบงาน (กรอบ 1:1 จริง) ──
       ใช้ทั้งตอนเพิ่งเลือกไฟล์ และตอนครอปเสร็จ
       กดที่ภาพ = เปิดโมดัลดูภาพใหญ่ แล้วเซฟไฟล์ได้ */
    function iconSlotIs(input) {
      var fld = input.closest('[data-cover-slot]');
      return !!(fld && fld.getAttribute('data-crop-mode') === 'icon');
    }

    function iconPreview(input, box, src, name) {
      box.className = '';
      box.innerHTML = '';
      box.hidden = false;

      var wrap = document.createElement('div');
      wrap.className = 'icon-pick';

      var shot = document.createElement('button');
      shot.className = 'icon-pick-shot';
      shot.type = 'button';
      shot.title = tr('iconSaveHint');
      shot.setAttribute('aria-label', tr('iconSaveHint'));
      shot.addEventListener('click', function () { zbOpen(src, name, name); });

      var img = document.createElement('img');
      img.alt = '';
      img.src = src;
      shot.appendChild(img);

      var text = document.createElement('span');
      text.className = 'icon-pick-text';

      var nameEl = document.createElement('b');
      nameEl.textContent = name;

      var hintEl = document.createElement('span');
      hintEl.textContent = tr('iconSaveHint');

      text.appendChild(nameEl);
      text.appendChild(hintEl);

      var x = document.createElement('button');
      x.className = 'icon-pick-x';
      x.type = 'button';
      x.title = tr('btnDelete');
      x.setAttribute('aria-label', tr('btnDelete'));
      x.textContent = '×';
      x.addEventListener('click', function () {
        var fld = input.closest('[data-cover-slot]');
        var data = fld ? fld.querySelector('[data-cover-data]') : null;
        var crop = fld ? fld.querySelector('[data-crop-open]') : null;
        input.value = '';
        if (data) { data.value = ''; }
        if (crop) { crop.hidden = true; }
        box.hidden = true;
        box.innerHTML = '';
      });

      wrap.appendChild(shot);
      wrap.appendChild(text);
      wrap.appendChild(x);
      box.appendChild(wrap);
    }

    /* SVG ครอปไม่ได้ (วาดลง canvas แล้วเสียความคมชัด) — ปล่อยผ่านไปทั้งไฟล์ */
    function isSvgFile(file) {
      return file.type === 'image/svg+xml' || /\.svg$/i.test(file.name);
    }

    function renderIconPick(input, box) {
      var file = input.files && input.files.length ? input.files[0] : null;
      var fld = input.closest('[data-cover-slot]');
      var cropBtn = fld ? fld.querySelector('[data-crop-open]') : null;

      /* คืน object URL ของรอบก่อน กันหน่วยความจำค้าง */
      if (input._iconUrl) {
        URL.revokeObjectURL(input._iconUrl);
        input._iconUrl = '';
      }

      if (cropBtn) { cropBtn.hidden = !file || isSvgFile(file); }

      if (!file) {
        box.className = '';
        box.innerHTML = '';
        box.hidden = true;
        return;
      }

      var src = URL.createObjectURL(file);
      input._iconUrl = src;
      iconPreview(input, box, src, file.name);
    }

    function renderPicked(input) {
      var files = input.files;
      var box = pickBoxFor(input);

      if (iconSlotIs(input)) {
        renderIconPick(input, box);
        return;
      }

      box.innerHTML = '';
      var isCover = input.hasAttribute('data-cover-input');

      if (!files || !files.length) {
        /* ช่องภาพปกคงกล่องว่างไว้ ไม่ให้แถวขยับ */
        box.hidden = !isCover;
        box.classList.remove('has-img');

        if (isCover) {
          var f0 = input.closest('[data-cover-slot]') || input.closest('.field');
          var d0 = f0 ? f0.querySelector('[data-cover-data]') : null;
          var b0 = f0 ? f0.querySelector('[data-crop-open]') : null;
          if (d0) { d0.value = ''; }
          if (b0) { b0.hidden = true; }
        }
        return;
      }

      box.hidden = false;
      if (isCover) { box.classList.add('has-img'); }

      /* ช่องแนบรูปภาพหลายรูป → แสดงเป็นไทล์เล็กพร้อมกากบาทมุมขวาบน */
      var asTiles = input.hasAttribute('data-photo-input');
      if (asTiles) { box.className = 'tile-grid'; }

      Array.prototype.slice.call(files).forEach(function (file, i) {
        if (asTiles) {
          var tile = document.createElement('div');
          tile.className = 'tile';

          var timg = document.createElement('img');
          tile.appendChild(timg);

          var topen = document.createElement('button');
          topen.className = 'tile-open';
          topen.type = 'button';
          topen.setAttribute('aria-label', 'ดูภาพ');
          tile.appendChild(topen);

          var treader = new FileReader();
          treader.onload = function (ev) {
            timg.src = ev.target.result;
            topen.addEventListener('click', function () { zbOpen(ev.target.result, file.name); });
          };
          treader.readAsDataURL(file);

          var tx = document.createElement('button');
          tx.className = 'tile-x';
          tx.type = 'button';
          tx.setAttribute('aria-label', 'ลบ');
          tx.title = 'ลบออก';
          tx.textContent = '×';
          tx.addEventListener('click', function () { dropPicked(input, i); });
          tile.appendChild(tx);

          box.appendChild(tile);
          return;
        }

        var row = document.createElement('div');
        row.className = 'asset is-new';

        if (file.type.indexOf('image/') === 0) {
          var btn = document.createElement('button');
          btn.className = 'asset-thumb';
          btn.type = 'button';
          btn.title = 'กดเพื่อดูภาพ';

          var thumb = document.createElement('img');
          btn.appendChild(thumb);

          var reader = new FileReader();
          reader.onload = function (ev) {
            thumb.src = ev.target.result;
            btn.addEventListener('click', function () { zbOpen(ev.target.result, file.name); });
          };
          reader.readAsDataURL(file);

          row.appendChild(btn);
        } else {
          var iconUrl = fileIconFor(file.name);

          if (iconUrl) {
            var ic = document.createElement('img');
            ic.src = iconUrl;
            ic.alt = '';
            row.appendChild(ic);
          } else {
            var tag = document.createElement('span');
            tag.className = 'asset-tag';
            tag.textContent = (file.name.split('.').pop() || '?').toUpperCase().slice(0, 4);
            row.appendChild(tag);
          }
        }

        var nm = document.createElement('span');
        nm.textContent = file.name;
        row.appendChild(nm);

        var del = document.createElement('button');
        del.className = 'asset-del';
        del.type = 'button';
        del.setAttribute('aria-label', 'ลบ');
        del.title = 'ลบออก';
        del.innerHTML = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 7h14M10 7V5h4v2m-7 0 1 13h8l1-13" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        del.addEventListener('click', function () { dropPicked(input, i); });
        row.appendChild(del);

        box.appendChild(row);
      });

      /* ช่องภาพปก → โชว์ปุ่มจัดภาพ */
      if (input.hasAttribute('data-cover-input') && files.length) {
        var fld = input.closest('[data-cover-slot]') || input.closest('.field');
        var btn2 = fld ? fld.querySelector('[data-crop-open]') : null;
        if (btn2) { btn2.hidden = false; }
      }
    }

    /* ผูกทุก input[type=file] ในหน้า */
    $$('input[type="file"]').forEach(function (input) {
      input.addEventListener('change', function () {
        renderPicked(input);

        /* ช่องที่ล็อกสัดส่วน (ภาพปก / ไอคอน) → เปิดหน้าจัดภาพทันที */
        if (input.hasAttribute('data-cover-input') && input.files && input.files.length
            && !isSvgFile(input.files[0])) {
          cropOpen(input);
        }
      });
    });

    /* ══════════════════════════════════════════════════════════
       10. จัดภาพปก — กรอบล็อกสัดส่วน เลื่อน/ซูมได้ (ทำฝั่งเบราว์เซอร์ เซิร์ฟเวอร์ไม่มี GD)
       ══════════════════════════════════════════════════════════ */
    var cb = $('#cropBox');
    var cbImg = $('#cbImg');
    var cbFrame = $('#cbFrame');
    var cbZoom = $('#cbZoom');
    var cropInput = null;
    var cropRatio = 1.3333;
    var cropMode = 'cover';   /* 'cover' = ภาพปก · 'icon' = ไอคอนกรอบ 1:1 */
    var ICON_OUT = 256;       /* ขนาดไฟล์ไอคอนที่ส่งออก (จัตุรัส) */
    var vw = 0, vh = 0;      /* ขนาดกรอบ */
    var baseW = 0, baseH = 0; /* ขนาดภาพที่ scale=1 */
    var zm = 1, ox = 0, oy = 0;

    function frameFit() {
      vw = cbFrame.clientWidth;
      vh = Math.round(vw / cropRatio);
      cbFrame.style.height = vh + 'px';
    }

    /* ภาพปก: ต้องคลุมกรอบเสมอ (เหมือน object-fit: cover)
       ไอคอน: เริ่มที่เห็นทั้งภาพ (contain) แล้วเลื่อนล้อขยายเข้าไปเองได้ */
    function coverBase() {
      var iw = cbImg.naturalWidth;
      var ih = cbImg.naturalHeight;
      if (!iw || !ih) { return; }

      var s = cropMode === 'icon'
        ? Math.min(vw / iw, vh / ih)
        : Math.max(vw / iw, vh / ih);

      baseW = iw * s;
      baseH = ih * s;
    }

    /* ภาพปก: กันไม่ให้ลากจนเห็นขอบว่าง
       ไอคอน: ลากได้แต่ต้องไม่หลุดออกนอกกรอบ */
    function clampOffset() {
      var w = baseW * zm;
      var h = baseH * zm;
      var mx = cropMode === 'icon' ? Math.abs(w - vw) / 2 : Math.max(0, (w - vw) / 2);
      var my = cropMode === 'icon' ? Math.abs(h - vh) / 2 : Math.max(0, (h - vh) / 2);
      ox = Math.min(mx, Math.max(-mx, ox));
      oy = Math.min(my, Math.max(-my, oy));
    }

    function cropApply() {
      clampOffset();
      cbImg.style.width = (baseW * zm) + 'px';
      cbImg.style.height = (baseH * zm) + 'px';
      cbImg.style.transform = 'translate(calc(-50% + ' + ox + 'px), calc(-50% + ' + oy + 'px))';
    }

    function cropResetView() {
      zm = 1;
      ox = 0;
      oy = 0;
      if (cbZoom) { cbZoom.value = 100; }
      frameFit();
      coverBase();
      cropApply();
    }

    function cropOpen(input) {
      if (!cb || !input.files || !input.files.length) { return; }

      cropInput = input;
      var fld = input.closest('[data-cover-slot]') || input.closest('.field');
      cropRatio = parseFloat(fld && fld.getAttribute('data-ratio')) || 1.3333;
      cropMode = (fld && fld.getAttribute('data-crop-mode') === 'icon') ? 'icon' : 'cover';

      /* กรอบไอคอนโชว์พื้นลายตาราง จะได้เห็นส่วนโปร่งใส */
      cbFrame.classList.toggle('is-icon', cropMode === 'icon');

      /* หัวข้อเปลี่ยนตามโหมด — ตั้ง data-i18n ด้วย เผื่อสลับภาษาระหว่างเปิดอยู่ */
      var cbTitle = $('#cbTitle');
      if (cbTitle) {
        var titleKey = cropMode === 'icon' ? 'cropIconTitle' : 'cropTitle';
        cbTitle.setAttribute('data-i18n', titleKey);
        cbTitle.textContent = tr(titleKey);
      }

      var reader = new FileReader();
      reader.onload = function (ev) {
        cbImg.onload = function () {
          cb.hidden = false;
          document.body.style.overflow = 'hidden';
          cropResetView();
        };
        cbImg.src = ev.target.result;
      };
      reader.readAsDataURL(input.files[0]);
    }

    function cropClose() {
      if (!cb) { return; }
      cb.hidden = true;
      cropInput = null;

      /* ถ้ายังมีโมดัลฟอร์มเปิดอยู่ข้างหลัง ต้องล็อกการเลื่อนต่อ */
      if (!document.querySelector('.fmodal:not([hidden])')) {
        document.body.style.overflow = '';
      }
    }

    if (cb) {
      $('#cbCancel').addEventListener('click', cropClose);
      $('#cbReset').addEventListener('click', cropResetView);

      cb.addEventListener('click', function (e) {
        if (e.target === cb) { cropClose(); }
      });

      /* แถบซูม */
      cbZoom.addEventListener('input', function () {
        zm = parseInt(cbZoom.value, 10) / 100;
        cropApply();
      });

      /* ซูมด้วยล้อเมาส์ */
      cbFrame.addEventListener('wheel', function (e) {
        e.preventDefault();
        zm = Math.min(4, Math.max(1, zm * (e.deltaY < 0 ? 1.12 : 1 / 1.12)));
        cbZoom.value = Math.round(zm * 100);
        cropApply();
      }, { passive: false });

      /* ลากเลื่อนภาพ */
      var dragging = false, sx = 0, sy = 0;

      cbFrame.addEventListener('pointerdown', function (e) {
        dragging = true;
        sx = e.clientX - ox;
        sy = e.clientY - oy;
        cbFrame.classList.add('is-dragging');
        cbFrame.setPointerCapture(e.pointerId);
      });

      cbFrame.addEventListener('pointermove', function (e) {
        if (!dragging) { return; }
        ox = e.clientX - sx;
        oy = e.clientY - sy;
        cropApply();
      });

      cbFrame.addEventListener('pointerup', function () {
        dragging = false;
        cbFrame.classList.remove('is-dragging');
      });

      window.addEventListener('resize', function () {
        if (cb.hidden) { return; }
        frameFit();
        coverBase();
        cropApply();
      });

      /* ยืนยัน → วาดเฉพาะส่วนในกรอบลง canvas ตามสัดส่วนที่ล็อกไว้ */
      $('#cbApply').addEventListener('click', function () {
        if (!cropInput) { return; }

        /* ── โหมดไอคอน ──
           วางภาพลงผืน 256×256 โปร่งใสตามตำแหน่ง/ขนาดที่เห็นในกรอบ
           ออกเป็น PNG เพื่อรักษาพื้นหลังโปร่งของโลโก้
           หน้าเว็บแสดงไอคอนแค่ ~40px · 256 จึงเหลือเฟือแม้จอ 2x และไฟล์ไม่บวม */
        if (cropMode === 'icon') {
          var iw = baseW * zm;
          var ih = baseH * zm;
          var k = ICON_OUT / vw;

          var canvasI = document.createElement('canvas');
          canvasI.width = ICON_OUT;
          canvasI.height = ICON_OUT;
          canvasI.getContext('2d').drawImage(
            cbImg,
            ((vw - iw) / 2 + ox) * k,
            ((vh - ih) / 2 + oy) * k,
            iw * k,
            ih * k
          );

          var outI = canvasI.toDataURL('image/png');
          var inputI = cropInput;
          var fldI = inputI.closest('[data-cover-slot]') || inputI.closest('.field');
          var dataI = fldI ? fldI.querySelector('[data-cover-data]') : null;
          var prevI = fldI ? fldI.querySelector('[data-cover-preview]') : null;
          var nameI = inputI.files && inputI.files.length ? inputI.files[0].name : 'icon.png';

          if (dataI) { dataI.value = outI; }
          if (prevI) { iconPreview(inputI, prevI, outI, nameI.replace(/\.[^.]+$/, '') + '.png'); }

          cropClose();
          return;
        }

        var w = baseW * zm;
        var scale = cbImg.naturalWidth / w;

        /* มุมซ้ายบนของกรอบ เทียบกับภาพที่แสดงอยู่ */
        var left = (w - vw) / 2 - ox;
        var top = (baseH * zm - vh) / 2 - oy;

        var sxp = Math.max(0, left * scale);
        var syp = Math.max(0, top * scale);
        var swp = Math.min(cbImg.naturalWidth - sxp, vw * scale);
        var shp = Math.min(cbImg.naturalHeight - syp, vh * scale);

        /* ส่งออกกว้างสุด 1600px พอสำหรับจอใหญ่ ไม่ให้ไฟล์บวม */
        var outW = Math.min(1600, Math.round(swp));
        var outH = Math.round(outW / cropRatio);

        var canvas = document.createElement('canvas');
        canvas.width = outW;
        canvas.height = outH;
        canvas.getContext('2d').drawImage(cbImg, sxp, syp, swp, shp, 0, 0, outW, outH);

        var out = canvas.toDataURL('image/jpeg', 0.9);

        /* เก็บ input ไว้ในตัวแปรท้องถิ่น เพราะ cropClose() จะล้าง cropInput ทิ้ง */
        var theInput = cropInput;
        var fld = theInput.closest('[data-cover-slot]') || theInput.closest('.field');
        var data = fld ? fld.querySelector('[data-cover-data]') : null;
        var prev = fld ? fld.querySelector('[data-cover-preview]') : null;

        if (data) { data.value = out; }

        /* แสดงผลลัพธ์แทนภาพปกปัจจุบันทันที */
        if (prev) {
          prev.hidden = false;
          prev.className = 'cover-slots has-img';
          prev.innerHTML = '';

          var card = document.createElement('div');
          card.className = 'cover-card';

          var open = document.createElement('button');
          open.className = 'cover-shot';
          open.type = 'button';
          open.title = 'กดเพื่อดูภาพ';
          var im = document.createElement('img');
          im.src = out;
          open.appendChild(im);
          open.addEventListener('click', function () { zbOpen(out, 'cover'); });

          var x = document.createElement('button');
          x.className = 'cover-x';
          x.type = 'button';
          x.setAttribute('aria-label', 'ลบ');
          x.title = 'ลบออก';
          x.textContent = '×';
          x.addEventListener('click', function () {
            theInput.value = '';
            if (data) { data.value = ''; }
            renderPicked(theInput);
          });

          card.appendChild(open);
          card.appendChild(x);
          prev.appendChild(card);
        }

        cropClose();
      });
    }

    document.addEventListener('click', function (e) {
      var openBtn = e.target.closest('[data-crop-open]');
      if (openBtn) {
        var fld = openBtn.closest('[data-cover-slot]') || openBtn.closest('.field');
        var inp = fld ? fld.querySelector('[data-cover-input]') : null;
        if (inp) { cropOpen(inp); }
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') { return; }
      if (cb && !cb.hidden) { cropClose(); return; }
      if (zb && !zb.hidden) { zbClose(); return; }

      /* ปิดโมดัลฟอร์มที่เปิดอยู่บนสุด */
      var open = $$('.fmodal:not([hidden])');
      if (open.length) { fmodalClose(open[open.length - 1]); }
    });

    /* ══════════════════════════════════════════════════════════
       11. เริ่มทำงาน
       ══════════════════════════════════════════════════════════ */
    setLang(readLang(), false);
    setView(viewFromHash());
    render();

    /* ฟอนต์โหลดเสร็จแล้วความกว้างข้อความเปลี่ยน ต้องเลื่อนช่องลิงก์ใหม่อีกรอบ */
    window.addEventListener('load', showRegisterLinkTails);

    /* ── ไฟล์ Excel ที่ล็อกรหัส — เปิดโมดัลถามรหัสให้เลย ── */
    if (REG_PASS_PENDING) {
      setView('register');

      var rpBox = $('#mRegPass');
      var rpHead = $('#mRegPassTitle');

      if (rpHead) {
        var rpKey = REG_PASS_TRIED ? 'regPassWrong' : 'regPassTitle';
        rpHead.setAttribute('data-i18n', rpKey);
        rpHead.textContent = tr(rpKey);
      }

      fmodalOpen(rpBox, 'input[name="register_password"]');

      var rpInput = $('#register_password');
      if (rpInput) { rpInput.select(); }
    }

    /* ปุ่มปิด/ยกเลิกของโมดัลรหัส → ต้องทิ้งไฟล์ที่พักไว้ด้วย ไม่ใช่แค่ปิดหน้าต่าง */
    document.addEventListener('click', function (e) {
      var rpCancel = e.target.closest('[data-regpass-cancel]');
      if (!rpCancel) { return; }

      var f = $('.reg-pass-cancel');
      if (f) { f.submit(); }
    });

    /* แสดง/ซ่อนรหัสผ่านของไฟล์ Excel */
    document.addEventListener('click', function (e) {
      var rpEye = e.target.closest('#regPassEye');
      if (!rpEye) { return; }

      var pw = $('#register_password');
      if (!pw) { return; }

      var show = rpEye.getAttribute('aria-pressed') !== 'true';
      pw.type = show ? 'text' : 'password';
      rpEye.setAttribute('aria-pressed', show ? 'true' : 'false');
      rpEye.setAttribute('aria-label', tr(show ? 'pwHide' : 'pwShow'));
      rpEye.title = tr(show ? 'pwHide' : 'pwShow');
      pw.focus();
    });

    /* เพิ่งลบภาพ/ไฟล์จากในโมดัลแก้ไข → เปิดโมดัลเดิมกลับมาที่ขั้นเดิม */
    if (REOPEN_ID) {
      var reBox = document.getElementById('edit_' + REOPEN_ID);
      if (reBox) {
        var reRow = reBox.closest('[data-row]');
        /* ถ้าประกาศอยู่คนละหน้า ให้เลื่อนไปหน้าที่มีก่อน */
        if (reRow && reRow.classList.contains('is-paged-out')) {
          var vis = $$('[data-row]').filter(function (r) { return !r.classList.contains('is-filtered'); });
          var at = vis.indexOf(reRow);
          if (at >= 0) { page = Math.floor(at / PER_PAGE) + 1; render(); }
        }
        wzGo(reBox.querySelector('form[data-form]'), REOPEN_STEP);
        fmodalOpen(reBox, null);
      }
    }
  </script>
</body>

</html>
