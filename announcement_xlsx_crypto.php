<?php
/*
 * ถอดรหัสไฟล์ Excel ที่ตั้งรหัสผ่านเปิดไฟล์ (ECMA-376 Agile Encryption)
 *
 * ทำไมต้องเขียน AES เอง:
 *   เซิร์ฟเวอร์จริงเป็น AppServ PHP 5.2.6 — ไม่มี mcrypt และ openssl_decrypt()
 *   (openssl_decrypt เพิ่มเข้ามาใน PHP 5.3) เหลือแต่ ext/hash ที่มี sha512 ให้ใช้
 *   จึงต้องทำ AES-256-CBC เองแบบ byte-oriented (ไม่ใช้ตาราง 32 บิต เพราะ PHP 32 บิต
 *   จะล้นเป็น float เวลา shift)
 *
 * โค้ดทั้งไฟล์เป็น PHP 5.2-safe: array() · ไม่มี closure · ไม่มี ??
 */

/* ── ไฟล์ที่เข้ารหัสไว้เป็นตู้ OLE2/CFB ไม่ใช่ zip ── */
define('XLSX_CFB_MAGIC', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1");

/* ══════════════════════════════════════════════════════════
   1. อ่านตู้ CFB (OLE2) — ดึง stream ที่ต้องใช้ออกมา
   ══════════════════════════════════════════════════════════ */

function xlsx_cfb_u16($d, $o) { $v = unpack('v', substr($d, $o, 2)); return $v[1]; }
function xlsx_cfb_u32($d, $o) { $v = unpack('V', substr($d, $o, 4)); return $v[1]; }
function xlsx_cfb_sector($d, $i, $size) { return substr($d, 512 + $i * $size, $size); }

/* ไล่ลูกโซ่ sector จนกว่าจะเจอ END (0xFFFFFFFE) */
function xlsx_cfb_chain($fat, $start) {
  $out = array();
  $c = $start;
  $guard = 0;

  while ($c !== 0xFFFFFFFE && $c !== 0xFFFFFFFF && isset($fat[$c]) && $guard++ < 200000) {
    $out[] = $c;
    $c = $fat[$c];
  }

  return $out;
}

function xlsx_cfb_read($data) {
  if (substr($data, 0, 8) !== XLSX_CFB_MAGIC) {
    return false;
  }

  $secSize  = 1 << xlsx_cfb_u16($data, 0x1E);
  $miniSize = 1 << xlsx_cfb_u16($data, 0x20);
  $numFat   = xlsx_cfb_u32($data, 0x2C);
  $dirStart = xlsx_cfb_u32($data, 0x30);
  $miniCut  = xlsx_cfb_u32($data, 0x38);
  $miniFat  = xlsx_cfb_u32($data, 0x3C);
  $difStart = xlsx_cfb_u32($data, 0x44);

  if ($secSize < 128 || $secSize > 65536) {
    return false;
  }

  /* DIFAT 109 ช่องแรกอยู่ในหัวไฟล์ ที่เหลือไล่ต่อทาง sector */
  $fatSectors = array();
  for ($i = 0; $i < 109; $i++) {
    $v = xlsx_cfb_u32($data, 0x4C + $i * 4);
    if ($v === 0xFFFFFFFF) { break; }
    $fatSectors[] = $v;
  }

  $next = $difStart;
  $guard = 0;
  while ($next !== 0xFFFFFFFF && $next !== 0xFFFFFFFE && count($fatSectors) < $numFat && $guard++ < 10000) {
    $sec = xlsx_cfb_sector($data, $next, $secSize);
    if (strlen($sec) < $secSize) { break; }

    $per = ($secSize / 4) - 1;
    for ($i = 0; $i < $per; $i++) {
      $v = xlsx_cfb_u32($sec, $i * 4);
      if ($v === 0xFFFFFFFF) { break; }
      $fatSectors[] = $v;
    }
    $next = xlsx_cfb_u32($sec, $secSize - 4);
  }

  $fat = array();
  foreach ($fatSectors as $fs) {
    $sec = xlsx_cfb_sector($data, $fs, $secSize);
    if (strlen($sec) < $secSize) { continue; }
    for ($i = 0; $i < $secSize / 4; $i++) { $fat[] = xlsx_cfb_u32($sec, $i * 4); }
  }

  $mfat = array();
  foreach (xlsx_cfb_chain($fat, $miniFat) as $fs) {
    $sec = xlsx_cfb_sector($data, $fs, $secSize);
    if (strlen($sec) < $secSize) { continue; }
    for ($i = 0; $i < $secSize / 4; $i++) { $mfat[] = xlsx_cfb_u32($sec, $i * 4); }
  }

  $dir = '';
  foreach (xlsx_cfb_chain($fat, $dirStart) as $s) { $dir .= xlsx_cfb_sector($data, $s, $secSize); }

  $entries = array();
  $count = floor(strlen($dir) / 128);
  for ($i = 0; $i < $count; $i++) {
    $e = substr($dir, $i * 128, 128);
    $nlen = xlsx_cfb_u16($e, 0x40);
    if ($nlen < 2 || $nlen > 64) { continue; }

    $name = @iconv('UTF-16LE', 'UTF-8//IGNORE', substr($e, 0, $nlen - 2));
    $entries[] = array(
      'name'  => $name,
      'type'  => ord(substr($e, 0x42, 1)),
      'start' => xlsx_cfb_u32($e, 0x74),
      'size'  => xlsx_cfb_u32($e, 0x78)
    );
  }

  /* stream เล็กกว่า 4096 ไบต์อยู่ใน mini stream ของ Root Entry */
  $miniStream = '';
  if (isset($entries[0])) {
    foreach (xlsx_cfb_chain($fat, $entries[0]['start']) as $s) {
      $miniStream .= xlsx_cfb_sector($data, $s, $secSize);
    }
  }

  $streams = array();
  foreach ($entries as $e) {
    if ($e['type'] !== 2) { continue; }

    $buf = '';
    if ($e['size'] < $miniCut) {
      foreach (xlsx_cfb_chain($mfat, $e['start']) as $s) {
        $buf .= substr($miniStream, $s * $miniSize, $miniSize);
      }
    } else {
      foreach (xlsx_cfb_chain($fat, $e['start']) as $s) {
        $buf .= xlsx_cfb_sector($data, $s, $secSize);
      }
    }

    $streams[$e['name']] = substr($buf, 0, $e['size']);
  }

  return $streams;
}

/* ไฟล์นี้เป็น Excel ที่ใส่รหัสผ่านเปิดไฟล์หรือเปล่า
   (ไฟล์ .xls 97-2003 ก็เป็น OLE2 เหมือนกัน แต่ไม่มี stream สองตัวนี้) */
function xlsx_is_encrypted($data) {
  if (substr($data, 0, 8) !== XLSX_CFB_MAGIC) {
    return false;
  }

  $streams = xlsx_cfb_read($data);

  return is_array($streams)
    && isset($streams['EncryptionInfo'])
    && isset($streams['EncryptedPackage']);
}

/* ══════════════════════════════════════════════════════════
   2. AES-256-CBC ถอดรหัส (pure PHP แบบ byte-oriented)
   ══════════════════════════════════════════════════════════ */

/* หมุนซ้ายภายใน 8 บิต — ใช้ตอนสร้าง S-box */
function xlsx_rotl8($x, $n) {
  return (($x << $n) | ($x >> (8 - $n))) & 0xFF;
}

/* สร้างตาราง S-box ตามนิยามของ AES (inverse ใน GF(2^8) + affine transform)
   คำนวณเองแทนการพิมพ์ตาราง 256 ค่า จะได้ไม่มีโอกาสพิมพ์ตกหล่น */
function xlsx_aes_sbox() {
  static $sbox = null;
  if ($sbox !== null) { return $sbox; }

  $sbox = array_fill(0, 256, 0);
  $p = 1;
  $q = 1;

  do {
    /* p = p * 3 ใน GF(2^8) */
    $p = ($p ^ (($p << 1) & 0xFF) ^ (($p & 0x80) ? 0x1B : 0)) & 0xFF;

    /* q = q / 3 ใน GF(2^8) */
    $q ^= ($q << 1) & 0xFF;
    $q ^= ($q << 2) & 0xFF;
    $q ^= ($q << 4) & 0xFF;
    $q &= 0xFF;
    if ($q & 0x80) { $q ^= 0x09; }

    $sbox[$p] = ($q ^ xlsx_rotl8($q, 1) ^ xlsx_rotl8($q, 2) ^ xlsx_rotl8($q, 3) ^ xlsx_rotl8($q, 4) ^ 0x63) & 0xFF;
  } while ($p !== 1);

  $sbox[0] = 0x63;

  return $sbox;
}

function xlsx_aes_rsbox() {
  static $rsbox = null;
  if ($rsbox !== null) { return $rsbox; }

  $sbox = xlsx_aes_sbox();
  $rsbox = array_fill(0, 256, 0);
  for ($i = 0; $i < 256; $i++) { $rsbox[$sbox[$i]] = $i; }

  return $rsbox;
}

/* คูณใน GF(2^8) — ใช้สร้างตาราง 9/11/13/14 สำหรับ InvMixColumns */
function xlsx_gf_mul($a, $b) {
  $p = 0;
  for ($i = 0; $i < 8; $i++) {
    if ($b & 1) { $p ^= $a; }
    $hi = $a & 0x80;
    $a = ($a << 1) & 0xFF;
    if ($hi) { $a ^= 0x1B; }
    $b >>= 1;
  }
  return $p & 0xFF;
}

function xlsx_gf_table($n) {
  static $cache = array();
  if (isset($cache[$n])) { return $cache[$n]; }

  $t = array();
  for ($i = 0; $i < 256; $i++) { $t[$i] = xlsx_gf_mul($i, $n); }
  $cache[$n] = $t;

  return $t;
}

/* ขยายกุญแจ AES-256 → 15 ชุด ชุดละ 16 ไบต์ (Nk=8, Nr=14) */
function xlsx_aes_expand_key($key) {
  $sbox = xlsx_aes_sbox();
  $nk = 8;
  $nr = 14;
  $total = 4 * ($nr + 1);

  $w = array();
  for ($i = 0; $i < $nk; $i++) {
    $w[$i] = array(
      ord(substr($key, 4 * $i, 1)),
      ord(substr($key, 4 * $i + 1, 1)),
      ord(substr($key, 4 * $i + 2, 1)),
      ord(substr($key, 4 * $i + 3, 1))
    );
  }

  $rcon = 1;
  for ($i = $nk; $i < $total; $i++) {
    $t = $w[$i - 1];

    if ($i % $nk === 0) {
      $t = array($sbox[$t[1]] ^ $rcon, $sbox[$t[2]], $sbox[$t[3]], $sbox[$t[0]]);
      $rcon = xlsx_gf_mul($rcon, 2);
    } elseif ($i % $nk === 4) {
      $t = array($sbox[$t[0]], $sbox[$t[1]], $sbox[$t[2]], $sbox[$t[3]]);
    }

    $p = $w[$i - $nk];
    $w[$i] = array($p[0] ^ $t[0], $p[1] ^ $t[1], $p[2] ^ $t[2], $p[3] ^ $t[3]);
  }

  /* แปลงเป็นไบต์เรียงยาว เอาไว้ XOR ตรง ๆ ตอนถอด */
  $rk = array();
  for ($i = 0; $i < $total; $i++) {
    $rk[] = $w[$i][0];
    $rk[] = $w[$i][1];
    $rk[] = $w[$i][2];
    $rk[] = $w[$i][3];
  }

  return $rk;
}

/* ถอดรหัสหนึ่งบล็อก 16 ไบต์ — $s เป็น array ไบต์ (state เรียงตามคอลัมน์) */
function xlsx_aes_decrypt_block($s, $rk) {
  $rsbox = xlsx_aes_rsbox();
  $m9  = xlsx_gf_table(9);
  $m11 = xlsx_gf_table(11);
  $m13 = xlsx_gf_table(13);
  $m14 = xlsx_gf_table(14);
  $nr = 14;

  /* AddRoundKey ชุดสุดท้าย */
  $o = $nr * 16;
  for ($i = 0; $i < 16; $i++) { $s[$i] ^= $rk[$o + $i]; }

  for ($round = $nr - 1; $round >= 0; $round--) {
    /* InvShiftRows — เลื่อนแถวกลับทางขวา */
    $t = $s[13]; $s[13] = $s[9];  $s[9]  = $s[5];  $s[5]  = $s[1];  $s[1]  = $t;

    $t = $s[2];  $s[2]  = $s[10]; $s[10] = $t;
    $t = $s[6];  $s[6]  = $s[14]; $s[14] = $t;

    $t = $s[3];  $s[3]  = $s[7];  $s[7]  = $s[11]; $s[11] = $s[15]; $s[15] = $t;

    /* InvSubBytes */
    for ($i = 0; $i < 16; $i++) { $s[$i] = $rsbox[$s[$i]]; }

    /* AddRoundKey */
    $o = $round * 16;
    for ($i = 0; $i < 16; $i++) { $s[$i] ^= $rk[$o + $i]; }

    /* InvMixColumns (ข้ามรอบสุดท้าย) */
    if ($round > 0) {
      for ($c = 0; $c < 16; $c += 4) {
        $a0 = $s[$c]; $a1 = $s[$c + 1]; $a2 = $s[$c + 2]; $a3 = $s[$c + 3];

        $s[$c]     = $m14[$a0] ^ $m11[$a1] ^ $m13[$a2] ^ $m9[$a3];
        $s[$c + 1] = $m9[$a0]  ^ $m14[$a1] ^ $m11[$a2] ^ $m13[$a3];
        $s[$c + 2] = $m13[$a0] ^ $m9[$a1]  ^ $m14[$a2] ^ $m11[$a3];
        $s[$c + 3] = $m11[$a0] ^ $m13[$a1] ^ $m9[$a2]  ^ $m14[$a3];
      }
    }
  }

  return $s;
}

function xlsx_aes_cbc_decrypt($data, $key, $iv) {
  $len = strlen($data);
  if ($len === 0 || $len % 16 !== 0) {
    return false;
  }

  $rk = xlsx_aes_expand_key($key);
  $prev = array_values(unpack('C*', $iv));
  $out = '';

  for ($p = 0; $p < $len; $p += 16) {
    $blockStr = substr($data, $p, 16);
    $block = array_values(unpack('C*', $blockStr));

    $plain = xlsx_aes_decrypt_block($block, $rk);

    $chars = '';
    for ($i = 0; $i < 16; $i++) {
      $chars .= chr($plain[$i] ^ $prev[$i]);
    }

    $out .= $chars;
    $prev = $block;
  }

  return $out;
}

/* ══════════════════════════════════════════════════════════
   3. Agile Encryption — สร้างกุญแจจากรหัสผ่าน แล้วถอดทั้งไฟล์
   ══════════════════════════════════════════════════════════ */

/* blockKey ประจำแต่ละงาน ตามสเปก ECMA-376 */
function xlsx_block_key($which) {
  $keys = array(
    'verifier_input' => "\xfe\xa7\xd2\x76\x3b\x4b\x9e\x79",
    'verifier_value' => "\xd7\xaa\x0f\x6d\x30\x61\x34\x4e",
    'key_value'      => "\x14\x6e\x0b\xe7\xab\xac\xd0\xd6"
  );

  return isset($keys[$which]) ? $keys[$which] : '';
}

/* ตัด/เติมให้ยาวเท่าที่กุญแจต้องการ (เติมด้วย 0x36 ตามสเปก) */
function xlsx_fit($buf, $size) {
  if (strlen($buf) >= $size) {
    return substr($buf, 0, $size);
  }

  return $buf . str_repeat("\x36", $size - strlen($buf));
}

/* H_final = SHA512( H_spin + blockKey ) แล้วตัดให้เท่าความยาวกุญแจ */
function xlsx_derive_key($hSpin, $blockKey, $keyBytes, $algo) {
  return xlsx_fit(hash($algo, $hSpin . $blockKey, true), $keyBytes);
}

/* วนแฮชตาม spinCount — จุดที่หนักที่สุดของงานนี้ (ปกติ 100,000 รอบ) */
function xlsx_spin_hash($password, $salt, $spinCount, $algo) {
  /* รหัสผ่านต้องเป็น UTF-16LE ตามที่ Excel ใช้ */
  $pw = @iconv('UTF-8', 'UTF-16LE//IGNORE', $password);
  if ($pw === false) { $pw = $password; }

  $h = hash($algo, $salt . $pw, true);

  for ($i = 0; $i < $spinCount; $i++) {
    $h = hash($algo, pack('V', $i) . $h, true);
  }

  return $h;
}

/* อ่านค่า attribute จาก EncryptionInfo XML โดยไม่ง้อ SimpleXML
   (ข้อมูลชุดนี้เป็น XML แบนมาก ใช้ regex ตรง ๆ ปลอดภัยและเร็วกว่า) */
function xlsx_xml_attr($xml, $tag, $attr) {
  if (!preg_match('/<[^>]*' . preg_quote($tag, '/') . '\b[^>]*>/i', $xml, $m)) {
    return '';
  }

  if (!preg_match('/\b' . preg_quote($attr, '/') . '\s*=\s*"([^"]*)"/i', $m[0], $a)) {
    return '';
  }

  return $a[1];
}

/*
 * ถอดรหัสไฟล์ Excel ที่ตั้งรหัสผ่าน
 *
 * คืนค่า:
 *   string  = เนื้อไฟล์ .xlsx ที่ถอดแล้ว (พร้อมเขียนลงดิสก์)
 *   false   = ถอดไม่ได้ ดูสาเหตุที่ $error และชนิดที่ $reason
 *
 * $reason จะเป็น 'password' เมื่อรหัสผิด (หน้าเว็บเอาไปถามรหัสใหม่ได้)
 */
function xlsx_decrypt($data, $password, &$error, &$reason) {
  $error = '';
  $reason = '';

  $streams = xlsx_cfb_read($data);

  if (!is_array($streams) || !isset($streams['EncryptionInfo']) || !isset($streams['EncryptedPackage'])) {
    $error = 'ไฟล์นี้ไม่ใช่ไฟล์ Excel ที่ตั้งรหัสผ่าน';
    $reason = 'format';
    return false;
  }

  $info = $streams['EncryptionInfo'];
  $major = xlsx_cfb_u16($info, 0);
  $minor = xlsx_cfb_u16($info, 2);

  /* 4.4 = Agile (Excel 2010 ขึ้นไป) — เวอร์ชันเดียวที่รองรับ */
  if ($major !== 4 || $minor !== 4) {
    $error = 'ไฟล์นี้เข้ารหัสด้วยวิธีเก่า (เวอร์ชัน ' . $major . '.' . $minor . ') ที่ระบบยังอ่านไม่ได้ — กรุณาเปิดใน Excel แล้ว Save As ใหม่';
    $reason = 'format';
    return false;
  }

  $xml = substr($info, 8);

  $encKeyTag = 'encryptedKey';
  $spinCount = (int) xlsx_xml_attr($xml, $encKeyTag, 'spinCount');
  $pKeyBits  = (int) xlsx_xml_attr($xml, $encKeyTag, 'keyBits');
  $pSalt     = base64_decode(xlsx_xml_attr($xml, $encKeyTag, 'saltValue'));
  $pAlgo     = strtolower(xlsx_xml_attr($xml, $encKeyTag, 'hashAlgorithm'));
  $pCipher   = strtoupper(xlsx_xml_attr($xml, $encKeyTag, 'cipherAlgorithm'));
  $encVerIn  = base64_decode(xlsx_xml_attr($xml, $encKeyTag, 'encryptedVerifierHashInput'));
  $encVerVal = base64_decode(xlsx_xml_attr($xml, $encKeyTag, 'encryptedVerifierHashValue'));
  $encKeyVal = base64_decode(xlsx_xml_attr($xml, $encKeyTag, 'encryptedKeyValue'));

  $dKeyBits  = (int) xlsx_xml_attr($xml, 'keyData', 'keyBits');
  $dBlockSz  = (int) xlsx_xml_attr($xml, 'keyData', 'blockSize');
  $dSalt     = base64_decode(xlsx_xml_attr($xml, 'keyData', 'saltValue'));
  $dAlgo     = strtolower(xlsx_xml_attr($xml, 'keyData', 'hashAlgorithm'));

  if ($pAlgo !== 'sha512' || $dAlgo !== 'sha512') {
    $error = 'ไฟล์นี้ใช้วิธีแฮชที่ระบบยังไม่รองรับ (' . $pAlgo . ') — กรุณาเปิดใน Excel แล้ว Save As ใหม่';
    $reason = 'format';
    return false;
  }

  if ($pCipher !== 'AES' || $pKeyBits !== 256 || $dKeyBits !== 256) {
    $error = 'ไฟล์นี้ใช้วิธีเข้ารหัสที่ระบบยังไม่รองรับ — กรุณาเปิดใน Excel แล้ว Save As ใหม่';
    $reason = 'format';
    return false;
  }

  if ($spinCount < 1 || $spinCount > 1000000) {
    $spinCount = 100000;
  }
  if ($dBlockSz < 1) {
    $dBlockSz = 16;
  }

  $keyBytes = $pKeyBits / 8;

  /* ── ตรวจรหัสผ่านก่อน จะได้ไม่เสียเวลาถอดทั้งไฟล์ถ้ารหัสผิด ── */
  $hSpin = xlsx_spin_hash($password, $pSalt, $spinCount, $pAlgo);

  $keyIn  = xlsx_derive_key($hSpin, xlsx_block_key('verifier_input'), $keyBytes, $pAlgo);
  $keyVal = xlsx_derive_key($hSpin, xlsx_block_key('verifier_value'), $keyBytes, $pAlgo);

  $verInput = xlsx_aes_cbc_decrypt($encVerIn, $keyIn, xlsx_fit($pSalt, 16));
  $verValue = xlsx_aes_cbc_decrypt($encVerVal, $keyVal, xlsx_fit($pSalt, 16));

  if ($verInput === false || $verValue === false) {
    $error = 'ไฟล์เข้ารหัสเสียหาย อ่านส่วนตรวจรหัสผ่านไม่ได้';
    $reason = 'format';
    return false;
  }

  $check = hash($pAlgo, substr($verInput, 0, 16), true);

  if (substr($verValue, 0, strlen($check)) !== $check) {
    $error = 'รหัสผ่านไม่ถูกต้อง';
    $reason = 'password';
    return false;
  }

  /* ── รหัสถูก → ถอดกุญแจจริงของไฟล์ ── */
  $keyForKey = xlsx_derive_key($hSpin, xlsx_block_key('key_value'), $keyBytes, $pAlgo);
  $secretKey = xlsx_aes_cbc_decrypt($encKeyVal, $keyForKey, xlsx_fit($pSalt, 16));

  if ($secretKey === false) {
    $error = 'ถอดกุญแจของไฟล์ไม่สำเร็จ';
    $reason = 'format';
    return false;
  }

  $secretKey = substr($secretKey, 0, $keyBytes);

  /* ── ถอดตัวไฟล์ — แบ่งเป็นท่อนละ 4096 ไบต์ แต่ละท่อนมี IV ของตัวเอง ── */
  $package = $streams['EncryptedPackage'];
  $sizeLo = xlsx_cfb_u32($package, 0);
  $sizeHi = xlsx_cfb_u32($package, 4);

  /* ไฟล์ทะเบียนหลักสิบ KB — ถ้า high word ไม่ว่างแปลว่าไฟล์ผิดปกติ */
  if ($sizeHi !== 0) {
    $error = 'ไฟล์ใหญ่เกินกว่าที่ระบบจะถอดรหัสได้';
    $reason = 'format';
    return false;
  }

  $body = substr($package, 8);
  $out = '';
  $segment = 0;

  for ($p = 0; $p < strlen($body); $p += 4096) {
    $chunk = substr($body, $p, 4096);
    if (strlen($chunk) % 16 !== 0) {
      $chunk = substr($chunk, 0, floor(strlen($chunk) / 16) * 16);
    }
    if ($chunk === '') { break; }

    $iv = xlsx_fit(hash($dAlgo, $dSalt . pack('V', $segment), true), $dBlockSz);
    $plain = xlsx_aes_cbc_decrypt($chunk, $secretKey, $iv);

    if ($plain === false) {
      $error = 'ถอดรหัสเนื้อไฟล์ไม่สำเร็จ';
      $reason = 'format';
      return false;
    }

    $out .= $plain;
    $segment++;
  }

  $out = substr($out, 0, $sizeLo);

  if (substr($out, 0, 2) !== 'PK') {
    $error = 'ถอดรหัสแล้วแต่ไฟล์ที่ได้ไม่ใช่ .xlsx';
    $reason = 'format';
    return false;
  }

  return $out;
}
