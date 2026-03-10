<?php
require 'init.php';
require 'db_connect.php';

// Config
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;
$maxSize = 2 * 1024 * 1024; // 2MB
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit();
}

// CSRF check
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: register.php?error=csrf');
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['password_confirm'] ?? '';

if ($password !== $confirm) {
    header('Location: register.php?error=pw_mismatch');
    exit();
}
if (strlen($password) < 6) {
    header('Location: register.php?error=pw_short');
    exit();
}

// Check existing user
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->bind_param('ss', $email, $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header('Location: register.php?error=exists');
    exit();
}
$stmt->close();

$avatarPath = null;
if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['avatar'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: register.php?error=file_error');
        exit();
    }
    if ($file['size'] > $maxSize) {
        header('Location: register.php?error=file_large');
        exit();
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!array_key_exists($mime, $allowed)) {
        header('Location: register.php?error=invalid_image');
        exit();
    }

    $ext = $allowed[$mime];
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Create safe filename
    $safeName = sha1(uniqid('av', true) . random_bytes(8)) . '.' . $ext;
    $dest = $uploadDir . $safeName;

    // Use GD to re-save image (strips EXIF) and re-scale to max 1024
    $imgInfo = getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        header('Location: register.php?error=invalid_image');
        exit();
    }
    list($w, $h) = $imgInfo;
    $maxDim = 1024;
    $scale = min(1, $maxDim / max($w, $h));
    $newW = (int)($w * $scale);
    $newH = (int)($h * $scale);

    // If GD functions are available, resample the image (strips EXIF). Otherwise just move the uploaded file.
    $gdAvailable = function_exists('imagecreatetruecolor') && (function_exists('imagecreatefromjpeg') || function_exists('imagecreatefrompng'));
    if ($gdAvailable) {
        if ($mime === 'image/jpeg') {
            $src = imagecreatefromjpeg($file['tmp_name']);
        } else {
            $src = imagecreatefrompng($file['tmp_name']);
        }
        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0,0,0,0, $newW, $newH, $w, $h);

        if ($ext === 'jpg') {
            imagejpeg($dst, $dest, 90);
        } else {
            imagepng($dst, $dest, 6);
        }
        imagedestroy($src);
        imagedestroy($dst);
    } else {
        // fallback: move uploaded file without processing
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            header('Location: register.php?error=file_error');
            exit();
        }
    }

    // create thumbnail 256x256 (center crop) if GD available
    $thumbName = 'thumb_' . $safeName;
    $thumbDest = $uploadDir . $thumbName;
    $thumbWeb = 'uploads/avatars/' . $thumbName;
    if ($gdAvailable) {
        // create square crop from center
        $srcInfo = getimagesize($dest);
        list($sw, $sh) = $srcInfo;
        $min = min($sw, $sh);
        $sx = (int)(($sw - $min) / 2);
        $sy = (int)(($sh - $min) / 2);
        $thumb = imagecreatetruecolor(256, 256);
        if ($ext === 'jpg') {
            $sourceImg = imagecreatefromjpeg($dest);
        } else {
            $sourceImg = imagecreatefrompng($dest);
        }
        imagecopyresampled($thumb, $sourceImg, 0,0, $sx, $sy, 256,256, $min, $min);
        if ($ext === 'jpg') imagejpeg($thumb, $thumbDest, 90); else imagepng($thumb, $thumbDest, 6);
        imagedestroy($sourceImg);
        imagedestroy($thumb);
        // use thumbnail as stored avatar path
        $avatarPath = $thumbWeb;
        // optionally remove the original large image
        if (file_exists($dest)) @unlink($dest);
    } else {
        // store web path relative to project root (no thumb)
        $avatarPath = 'uploads/avatars/' . $safeName;
    }
}

// Create user
$hash = password_hash($password, PASSWORD_DEFAULT);
$created = date('Y-m-d H:i:s');
$stmt = $conn->prepare('INSERT INTO users (username, email, password_hash, avatar_path, created_at) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('sssss', $username, $email, $hash, $avatarPath, $created);
    if ($stmt->execute()) {
        $newUserId = $stmt->insert_id;
        $stmt->close();

        // generate email verification token
        $token = bin2hex(random_bytes(32));
        $stmt = $conn->prepare('UPDATE users SET verification_token = ? WHERE id = ?');
        $stmt->bind_param('si', $token, $newUserId);
        $stmt->execute();
        $stmt->close();

        // send verification email (best-effort) via mailer wrapper
        $verifyUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' .
            $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/verify.php?token=' . $token;
        $subject = 'Verify your CyberGuard account';
        $message = "Hello $username,\n\nPlease verify your email by visiting the following link:\n$verifyUrl\n\nIf you didn't register, ignore this message.";
        if (file_exists(__DIR__ . '/mailer.php')) {
            require_once __DIR__ . '/mailer.php';
            mailer_send($email, $subject, nl2br(htmlspecialchars($message)), true);
        } else {
            @mail($email, $subject, $message);
        }


        
        header('Location: login.php?registered=1&verify_sent=1');
        exit();
    } else {
        $stmt->close();
        header('Location: register.php?error=db');
        exit();
    }

?>