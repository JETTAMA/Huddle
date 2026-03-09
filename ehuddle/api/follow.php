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
$targetId = (int)($input['user_id'] ?? 0);

if ($targetId <= 0 || $targetId === (int)$user['id']) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$db = getDb();

$stmt = $db->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
$stmt->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
$stmt->bindValue(2, $targetId, SQLITE3_INTEGER);
$exists = $stmt->execute()->fetchArray();

if ($exists) {
    $stmt = $db->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $targetId, SQLITE3_INTEGER);
    $stmt->execute();
    $following = false;
} else {
    $stmt = $db->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)');
    $stmt->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $targetId, SQLITE3_INTEGER);
    $stmt->execute();
    $following = true;
}

echo json_encode(['success' => true, 'following' => $following]);
