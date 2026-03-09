<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';

header('Content-Type: application/json');

$user = currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tab = $_GET['tab'] ?? 'posts';
    $userId = (int)($_GET['user_id'] ?? $user['id']);
    $currentUserId = (int)$user['id'];

    $data = [];
    switch ($tab) {
        case 'posts':
            $data = getUserPosts($userId, $currentUserId, $userId !== $currentUserId);
            break;
        case 'likes':
            $data = getUserLikes($userId, $currentUserId);
            break;
        case 'media':
            $data = getUserMedia($userId, $currentUserId);
            break;
        case 'replies':
            $data = getUserReplies($userId);
            break;
        default:
            $data = getUserPosts($userId, $currentUserId);
    }

    echo json_encode(['success' => true, 'tab' => $tab, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$bio = trim($input['bio'] ?? $user['bio']);
$location = trim($input['location'] ?? $user['location']);
$website = trim($input['website'] ?? $user['website']);
$firstName = trim($input['first_name'] ?? $user['first_name']);
$lastName = trim($input['last_name'] ?? $user['last_name']);

$db = getDb();
$stmt = $db->prepare('UPDATE users SET bio = ?, location = ?, website = ?, first_name = ?, last_name = ? WHERE id = ?');
$stmt->bindValue(1, $bio, SQLITE3_TEXT);
$stmt->bindValue(2, $location, SQLITE3_TEXT);
$stmt->bindValue(3, $website, SQLITE3_TEXT);
$stmt->bindValue(4, $firstName, SQLITE3_TEXT);
$stmt->bindValue(5, $lastName, SQLITE3_TEXT);
$stmt->bindValue(6, (int)$user['id'], SQLITE3_INTEGER);
$stmt->execute();

echo json_encode([
    'success' => true,
    'user' => [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'bio' => $bio,
        'location' => $location,
        'website' => $website,
    ],
]);
