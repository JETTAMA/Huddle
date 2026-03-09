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
    $postId = (int)($_GET['post_id'] ?? 0);
    if ($postId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid post ID']);
        exit;
    }

    $db = getDb();
    $stmt = $db->prepare('
        SELECT c.id, c.content, c.created_at, u.username, u.avatar_color, u.avatar_letter
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ');
    $stmt->bindValue(1, $postId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $comments = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $comments[] = $row;
    }

    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $postId = (int)($input['post_id'] ?? 0);
    $content = trim($input['content'] ?? '');

    if ($postId <= 0 || $content === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID and content are required']);
        exit;
    }

    $db = getDb();
    $stmt = $db->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
    $stmt->bindValue(1, $postId, SQLITE3_INTEGER);
    $stmt->bindValue(2, (int)$user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(3, $content, SQLITE3_TEXT);
    $stmt->execute();

    $commentId = $db->lastInsertRowID();

    // Get count
    $stmt = $db->prepare('SELECT COUNT(*) FROM comments WHERE post_id = ?');
    $stmt->bindValue(1, $postId, SQLITE3_INTEGER);
    $count = (int)$stmt->execute()->fetchArray()[0];

    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $commentId,
            'content' => $content,
            'username' => $user['username'],
            'avatar_color' => $user['avatar_color'],
            'avatar_letter' => $user['avatar_letter'],
        ],
        'count' => $count,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
