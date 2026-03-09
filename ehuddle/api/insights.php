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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$postId = (int)($_GET['post_id'] ?? 0);
if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post ID']);
    exit;
}

$db = getDb();

$stmt = $db->prepare('
    SELECT p.*, 
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p 
    WHERE p.id = ?
');
$stmt->bindValue(1, $postId, SQLITE3_INTEGER);
$post = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$post) {
    http_response_code(404);
    echo json_encode(['error' => 'Post not found']);
    exit;
}

$views = ($postId * 13) % 1000 + 42; 
$reach = $views * 3;

echo json_encode([
    'success' => true,
    'insights' => [
        'post_id' => $post['id'],
        'views' => $views,
        'reach' => $reach,
        'engagement' => $post['like_count'] + $post['comment_count'],
        'details' => [
            'content_snippet' => $post['content'],
            'has_image' => !empty($post['image_url']),
            'is_private' => (bool)$post['is_private']
        ]
    ]
]);
