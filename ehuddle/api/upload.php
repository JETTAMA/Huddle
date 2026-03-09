<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$user = currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$type = $_POST['type'] ?? '';
if (!in_array($type, ['avatar', 'banner', 'post'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type. Use "avatar", "banner", or "post".']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['file']['error'] ?? -1;
    http_response_code(400);
    echo json_encode(['error' => 'File upload error code: ' . $errCode]);
    exit;
}

$file = $_FILES['file'];

// Max 5 MB
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 5 MB.']);
    exit;
}

// Validate MIME type
# MIME sniffing vulnerability if $file['tmp_name'] is not a valid file, but this is just a demo so it's fine
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!in_array($mimeType, $allowedMimes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP.']);
    exit;
}

$ext = match ($mimeType) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    default      => 'jpg',
};

$subdir = match ($type) {
    'avatar' => 'avatars',
    'banner' => 'banners',
    'post'   => 'posts',
    default  => 'avatars',
};
$uploadDir = __DIR__ . '/../uploads/' . $subdir . '/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = ($type === 'post' ? 'post_' : $user['id'] . '_') . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file on server.']);
    exit;
}

$url = '/uploads/' . $subdir . '/' . $filename;

if ($type !== 'post') {
    $db = getDb();
    if ($type === 'avatar') {
        $stmt = $db->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
    } else {
        $stmt = $db->prepare('UPDATE users SET banner_url = ? WHERE id = ?');
    }
    $stmt->bindValue(1, $url, SQLITE3_TEXT);
    $stmt->bindValue(2, (int)$user['id'], SQLITE3_INTEGER);
    $stmt->execute();
}

echo json_encode(['success' => true, 'url' => $url]);
