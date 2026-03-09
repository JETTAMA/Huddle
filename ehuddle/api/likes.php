<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

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

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$postId = (int)($input['post_id'] ?? 0);

if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post ID']);
    exit;
}

$db = getDb();

// Check if already liked
$stmt = $db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
$stmt->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
$stmt->bindValue(2, $postId, SQLITE3_INTEGER);
$exists = $stmt->execute()->fetchArray();

if ($exists) {
    $stmt = $db->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?');
    $stmt->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $postId, SQLITE3_INTEGER);
    $stmt->execute();
    $liked = false;
} else {
    $stmt = $db->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)');
    $stmt->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $postId, SQLITE3_INTEGER);
    $stmt->execute();
    $liked = true;
}

// Get new count, sql injection if $postId is not an integer, but this is just a demo so it's fine
$stmt = $db->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$stmt->bindValue(1, $postId, SQLITE3_INTEGER);
$count = (int)$stmt->execute()->fetchArray()[0];

echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
