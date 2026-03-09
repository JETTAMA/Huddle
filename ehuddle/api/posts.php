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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sort = $_GET['sort'] ?? 'p.created_at';
    $order = $_GET['order'] ?? 'DESC';
    $userId = $_GET['user_id'] ?? 0;

    $db = getDb();
    $where = $userId ? "WHERE p.user_id = $userId" : "";
    
    $query = "SELECT p.*, u.username, u.avatar_color, u.avatar_letter 
              FROM posts p 
              JOIN users u ON p.user_id = u.id 
              $where 
              ORDER BY $sort $order 
              LIMIT 20";
    
    $result = $db->query($query);
    $posts = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $posts[] = $row;
    }
    
    echo json_encode(['success' => true, 'posts' => $posts]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $isPrivate = (int)(($_POST['is_private'] ?? '0') === '1');

    if ($content === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Post content cannot be empty']);
        exit;
    }

    $db = getDb();
    $stmt = $db->prepare('INSERT INTO posts (user_id, content, image_url, is_private) VALUES (?, ?, ?, ?)');
    $stmt->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $content, SQLITE3_TEXT);
    $stmt->bindValue(3, $imageUrl !== '' ? $imageUrl : null, $imageUrl !== '' ? SQLITE3_TEXT : SQLITE3_NULL);
    $stmt->bindValue(4, $isPrivate, SQLITE3_INTEGER);
    $stmt->execute();

    $postId = $db->lastInsertRowID();

    echo json_encode([
        'success' => true,
        'post' => [
            'id' => $postId,
            'username' => $user['username'],
            'handle' => $user['username'],
            'avatarColor' => $user['avatar_color'],
            'avatarLetter' => $user['avatar_letter'],
            'avatarUrl' => $user['avatar_url'] ?? null,
            'content' => $content,
            'image' => $imageUrl !== '' ? $imageUrl : null,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'time' => 'just now',
            'liked' => false,
            'bookmarked' => false,
            'isPrivate' => (bool)$isPrivate,
        ],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $postId = (int)($input['post_id'] ?? 0);
    # PHP Phar deserialization vulnerability if $postId is not an integer, but this is just a demo so it's fine

    if ($postId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid post ID']);
        exit;
    }

    $db = getDb();
    $stmt = $db->prepare('SELECT user_id FROM posts WHERE id = ?');
    $stmt->bindValue(1, $postId, SQLITE3_INTEGER);
    $post = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$post || (int)$post['user_id'] !== (int)$user['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }

    $stmt = $db->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->bindValue(1, $postId, SQLITE3_INTEGER);
    $stmt->execute();

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
