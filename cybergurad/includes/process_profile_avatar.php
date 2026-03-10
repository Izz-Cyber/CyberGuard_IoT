<?php
require 'init.php';
require 'db_connect.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: profile.php?error=csrf');
    exit();
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;
$maxSize = 2 * 1024 * 1024; // 2MB
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
    header('Location: profile.php?error=no_file');
    exit();
}

$file = $_FILES['avatar'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: profile.php?error=file_error');
    exit();
}
if ($file['size'] > $maxSize) {
    header('Location: profile.php?error=file_large');
    exit();
}
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!array_key_exists($mime, $allowed)) {
    header('Location: profile.php?error=invalid_image');
    exit();
}
$ext = $allowed[$mime];
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// new filename
$safeName = sha1(uniqid('av', true) . random_bytes(8)) . '.' . $ext;
$dest = $uploadDir . $safeName;

$imgInfo = getimagesize($file['tmp_name']);
if ($imgInfo === false) { header('Location: profile.php?error=invalid_image'); exit(); }
list($w,$h) = $imgInfo;
$maxDim = 1024;
$scale = min(1, $maxDim / max($w,$h));
$newW = (int)($w * $scale);
$newH = (int)($h * $scale);
// If GD available, resample; otherwise move file as-is
$gdAvailable = function_exists('imagecreatetruecolor') && (function_exists('imagecreatefromjpeg') || function_exists('imagecreatefrompng'));
if ($gdAvailable) {
    if ($mime === 'image/jpeg') {
        $src = imagecreatefromjpeg($file['tmp_name']);
    } else {
        $src = imagecreatefrompng($file['tmp_name']);
    }
    $dst = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($dst, $src, 0,0,0,0, $newW, $newH, $w, $h);

    if ($ext === 'jpg') imagejpeg($dst, $dest, 90); else imagepng($dst, $dest, 6);
    imagedestroy($src);
    imagedestroy($dst);
} else {
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        header('Location: profile.php?error=file_error');
        exit();
    }
}

$webPath = 'uploads/avatars/' . $safeName;
// create thumbnail 256x256
$thumbName = 'thumb_' . $safeName;
$thumbDest = $uploadDir . $thumbName;
$thumbWeb = 'uploads/avatars/' . $thumbName;
$gdAvailable = function_exists('imagecreatetruecolor') && (function_exists('imagecreatefromjpeg') || function_exists('imagecreatefrompng'));
if ($gdAvailable) {
    $srcInfo = getimagesize($dest);
    list($sw,$sh) = $srcInfo;
    $min = min($sw,$sh);
    $sx = (int)(($sw - $min)/2);
    $sy = (int)(($sh - $min)/2);
    $thumb = imagecreatetruecolor(256,256);
    if ($ext === 'jpg') $sourceImg = imagecreatefromjpeg($dest); else $sourceImg = imagecreatefrompng($dest);
    imagecopyresampled($thumb, $sourceImg, 0,0, $sx, $sy, 256,256, $min, $min);
    if ($ext === 'jpg') imagejpeg($thumb, $thumbDest, 90); else imagepng($thumb, $thumbDest, 6);
    imagedestroy($sourceImg);
    imagedestroy($thumb);
    // use thumbnail
    $webPath = $thumbWeb;
    if (file_exists($dest)) @unlink($dest);
}

// update DB and remove previous avatar file if exists
$uid = (int)$_SESSION['user_id'];
$stmt = $conn->prepare('SELECT avatar_path FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$old = $stmt->get_result()->fetch_assoc();
$stmt->close();

// update
$stmt = $conn->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
$stmt->bind_param('si', $webPath, $uid);
if ($stmt->execute()) {
    $stmt->close();
    // delete old file if present and inside uploads/avatars
    if (!empty($old['avatar_path']) && strpos($old['avatar_path'], 'uploads/avatars/') === 0) {
        $oldFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $old['avatar_path'];
        if (file_exists($oldFile)) @unlink($oldFile);
    }
    // update session
    $_SESSION['avatar_path'] = $webPath;
    header('Location: profile.php?updated=1');
    exit();
} else {
    $stmt->close();
    header('Location: profile.php?error=db');
    exit();
}

?>