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

$query = trim($_GET['q'] ?? '');
if ($query === '') {
    echo json_encode(['success' => true, 'posts' => [], 'users' => []]);
    exit;
}

$db = getDb();
$searchTerm = '%' . $query . '%';

// Search posts (only public)
$stmt = $db->prepare('
    SELECT p.id, p.content, p.created_at, u.username, u.avatar_color, u.avatar_letter
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.content LIKE ? AND p.is_private = 0
    ORDER BY p.created_at DESC
    LIMIT 10
');
$stmt->bindValue(1, $searchTerm, SQLITE3_TEXT);
$result = $stmt->execute();
$posts = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $posts[] = $row;
}

// Search users
$stmt = $db->prepare('
    SELECT id, username, first_name, last_name, bio, avatar_color, avatar_letter, avatar_url
    FROM users
    WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ?
    LIMIT 10
');
$stmt->bindValue(1, $searchTerm, SQLITE3_TEXT);
$stmt->bindValue(2, $searchTerm, SQLITE3_TEXT);
$stmt->bindValue(3, $searchTerm, SQLITE3_TEXT);
$result = $stmt->execute();
$users = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $row;
}

echo json_encode(['success' => true, 'posts' => $posts, 'users' => $users]);
