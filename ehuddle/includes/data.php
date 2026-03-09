<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Static data that doesn't come from DB
$floatingEmojis = ["🎮", "🎨", "🎵", "⭐", "🔥", "💫", "🚀", "🌈"];

$features = [
    ["icon" => "fa-comment-dots", "title" => "Express Yourself", "desc" => "Share your thoughts with bold, fun posts that stand out!", "color" => "bg-cartoon-blue", "emoji" => "💬"],
    ["icon" => "fa-users", "title" => "Find Your Crew", "desc" => "Connect with like-minded people who get your vibe!", "color" => "bg-cartoon-purple", "emoji" => "👥"],
    ["icon" => "fa-star", "title" => "Stay Inspired", "desc" => "Discover trending content and fresh ideas every day!", "color" => "bg-cartoon-orange", "emoji" => "✨"],
];

$categories = [
    ["icon" => "fa-fire", "label" => "Trending", "color" => "bg-cartoon-orange"],
    ["icon" => "fa-user-group", "label" => "Friends", "color" => "bg-cartoon-blue"],
    ["icon" => "fa-bookmark", "label" => "Saved", "color" => "bg-cartoon-green"],
    ["icon" => "fa-star", "label" => "Favorites", "color" => "bg-cartoon-yellow"],
];

$topics = [
    ["icon" => "fa-gamepad", "label" => "Gaming", "count" => 42],
    ["icon" => "fa-music", "label" => "Music", "count" => 28],
    ["icon" => "fa-palette", "label" => "Art", "count" => 35],
    ["icon" => "fa-chart-line", "label" => "Tech", "count" => 19],
];

$trendingTags = ["#RetroVibes", "#CartoonLife", "#90sKids", "#PixelArt", "#ChillBeats"];

$tabs = ["Posts", "Likes", "Media", "Replies"];

/**
 * Format a UTC datetime string into a human-readable relative time.
 *
 * @param string $datetime UTC datetime string (e.g. "2026-03-03 12:00:00").
 * @return string Relative time string (e.g. "2h", "3d", "just now").
 */
function formatTimeAgo(string $datetime): string
{
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $then = new DateTime($datetime, new DateTimeZone('UTC'));
    $diff = $now->getTimestamp() - $then->getTimestamp();

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return (string)(int)floor($diff / 60) . 'm';
    if ($diff < 86400) return (string)(int)floor($diff / 3600) . 'h';
    if ($diff < 604800) return (string)(int)floor($diff / 86400) . 'd';
    return $then->format('M j');
}

/**
 * Get all posts for the feed, ordered by creation time descending.
 * Includes like/bookmark state for the given user.
 *
 * @param int|null $currentUserId The logged-in user's ID, or null for anonymous.
 * @return array<int, array<string, mixed>> Array of post data arrays.
 */
function getFeedPosts(?int $currentUserId = null): array
{
    $db = getDb();
    if ($currentUserId !== null) {
        $stmt = $db->prepare('
            SELECT p.*, u.username, u.avatar_color, u.avatar_letter, u.avatar_url,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE (p.is_private = 0 OR p.user_id = ?)
            ORDER BY p.created_at DESC
        ');
        $stmt->bindValue(1, $currentUserId, SQLITE3_INTEGER);
        $result = $stmt->execute();
    } else {
        $result = $db->query('
            SELECT p.*, u.username, u.avatar_color, u.avatar_letter, u.avatar_url,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.is_private = 0
            ORDER BY p.created_at DESC
        ');
    }

    $posts = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $liked = false;
        $bookmarked = false;
        if ($currentUserId !== null) {
            $stmt = $db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
            $stmt->bindValue(1, $currentUserId, SQLITE3_INTEGER);
            $stmt->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
            $liked = (bool)$stmt->execute()->fetchArray();

            $stmt = $db->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND post_id = ?');
            $stmt->bindValue(1, $currentUserId, SQLITE3_INTEGER);
            $stmt->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
            $bookmarked = (bool)$stmt->execute()->fetchArray();
        }

        $posts[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'username' => (string)$row['username'],
            'handle' => (string)$row['username'],
            'avatarColor' => (string)$row['avatar_color'],
            'avatarLetter' => (string)$row['avatar_letter'],
            'avatarUrl' => $row['avatar_url'] !== null ? (string)$row['avatar_url'] : null,
            'content' => (string)$row['content'],
            'image' => $row['image_url'] !== null ? (string)$row['image_url'] : null,
            'likes' => (int)$row['like_count'],
            'comments' => (int)$row['comment_count'],
            'shares' => 0,
            'time' => formatTimeAgo((string)$row['created_at']),
            'liked' => $liked,
            'bookmarked' => $bookmarked,
            'isPrivate' => (bool)($row['is_private'] ?? 0),
        ];
    }

    return $posts;
}

/**
 * Get all posts by a specific user, ordered by creation time descending.
 *
 * @param int $userId The user whose posts to retrieve.
 * @param int|null $currentUserId The logged-in user's ID for like/bookmark state.
 * @param bool $publicOnly If true, only return public posts (for viewing other users' profiles).
 * @return array<int, array<string, mixed>> Array of post data arrays.
 */
function getUserPosts(int $userId, ?int $currentUserId = null, bool $publicOnly = false): array
{
    $db = getDb();
    if ($publicOnly) {
        $stmt = $db->prepare('
            SELECT p.*, u.username, u.avatar_color, u.avatar_letter, u.avatar_url,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ? AND p.is_private = 0
            ORDER BY p.created_at DESC
        ');
    } else {
        $stmt = $db->prepare('
            SELECT p.*, u.username, u.avatar_color, u.avatar_letter, u.avatar_url,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ');
    }
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $posts = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $liked = false;
        $bookmarked = false;
        if ($currentUserId !== null) {
            $s = $db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
            $s->bindValue(1, $currentUserId, SQLITE3_INTEGER);
            $s->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
            $liked = (bool)$s->execute()->fetchArray();

            $s = $db->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND post_id = ?');
            $s->bindValue(1, $currentUserId, SQLITE3_INTEGER);
            $s->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
            $bookmarked = (bool)$s->execute()->fetchArray();
        }

        $posts[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'username' => (string)$row['username'],
            'handle' => (string)$row['username'],
            'avatarColor' => (string)$row['avatar_color'],
            'avatarLetter' => (string)$row['avatar_letter'],
            'avatarUrl' => $row['avatar_url'] !== null ? (string)$row['avatar_url'] : null,
            'content' => (string)$row['content'],
            'image' => $row['image_url'] !== null ? (string)$row['image_url'] : null,
            'likes' => (int)$row['like_count'],
            'comments' => (int)$row['comment_count'],
            'shares' => 0,
            'time' => formatTimeAgo((string)$row['created_at']),
            'liked' => $liked,
            'bookmarked' => $bookmarked,
            'isPrivate' => (bool)($row['is_private'] ?? 0),
        ];
    }

    return $posts;
}

/**
 * Get all posts liked by a specific user.
 *
 * @param int $userId The user who liked the posts.
 * @param int|null $currentUserId The logged-in user's ID for state.
 * @return array<int, array<string, mixed>>
 */
function getUserLikes(int $userId, ?int $currentUserId = null): array
{
    $db = getDb();
    $stmt = $db->prepare('
        SELECT p.*, u.username, u.avatar_color, u.avatar_letter, u.avatar_url,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        JOIN likes l ON p.id = l.post_id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
    ');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $posts = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $liked = false;
        $bookmarked = false;
        if ($currentUserId !== null) {
            $s = $db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
            $s->bindValue(1, $currentUserId, SQLITE3_INTEGER);
            $s->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
            $liked = (bool)$s->execute()->fetchArray();

            $s = $db->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND post_id = ?');
            $s->bindValue(1, $currentUserId, SQLITE3_INTEGER);
            $s->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
            $bookmarked = (bool)$s->execute()->fetchArray();
        }

        $posts[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'username' => (string)$row['username'],
            'handle' => (string)$row['username'],
            'avatarColor' => (string)$row['avatar_color'],
            'avatarLetter' => (string)$row['avatar_letter'],
            'avatarUrl' => $row['avatar_url'] !== null ? (string)$row['avatar_url'] : null,
            'content' => (string)$row['content'],
            'image' => $row['image_url'] !== null ? (string)$row['image_url'] : null,
            'likes' => (int)$row['like_count'],
            'comments' => (int)$row['comment_count'],
            'shares' => 0,
            'time' => formatTimeAgo((string)$row['created_at']),
            'liked' => $liked,
            'bookmarked' => $bookmarked,
            'isPrivate' => (bool)($row['is_private'] ?? 0),
        ];
    }
    return $posts;
}

/**
 * Get all posts by a specific user that contain media.
 *
 * @param int $userId The user whose media posts to retrieve.
 * @param int|null $currentUserId The logged-in user's ID for state.
 * @return array<int, array<string, mixed>>
 */
function getUserMedia(int $userId, ?int $currentUserId = null): array
{
    $db = getDb();
    $stmt = $db->prepare('
        SELECT p.*, u.username, u.avatar_color, u.avatar_letter, u.avatar_url,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.user_id = ? AND p.image_url IS NOT NULL
        ORDER BY p.created_at DESC
    ');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $posts = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $liked = false;
        $bookmarked = false;
        if ($currentUserId !== null) {
            $s = $db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
            $s->bindValue(1, $currentUserId, SQLITE3_INTEGER);
            $s->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
            $liked = (bool)$s->execute()->fetchArray();

            $s = $db->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND post_id = ?');
            $s->bindValue(1, $currentUserId, SQLITE3_INTEGER);
            $s->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
            $bookmarked = (bool)$s->execute()->fetchArray();
        }

        $posts[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'username' => (string)$row['username'],
            'handle' => (string)$row['username'],
            'avatarColor' => (string)$row['avatar_color'],
            'avatarLetter' => (string)$row['avatar_letter'],
            'avatarUrl' => $row['avatar_url'] !== null ? (string)$row['avatar_url'] : null,
            'content' => (string)$row['content'],
            'image' => $row['image_url'] !== null ? (string)$row['image_url'] : null,
            'likes' => (int)$row['like_count'],
            'comments' => (int)$row['comment_count'],
            'shares' => 0,
            'time' => formatTimeAgo((string)$row['created_at']),
            'liked' => $liked,
            'bookmarked' => $bookmarked,
            'isPrivate' => (bool)($row['is_private'] ?? 0),
        ];
    }
    return $posts;
}

/**
 * Get all replies (comments) by a specific user.
 *
 * @param int $userId The user whose replies to retrieve.
 * @return array<int, array<string, mixed>>
 */
function getUserReplies(int $userId): array
{
    $db = getDb();
    $stmt = $db->prepare('
        SELECT c.*, p.content AS post_content, u.username AS post_author
        FROM comments c
        JOIN posts p ON c.post_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $replies = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $replies[] = [
            'id' => (int)$row['id'],
            'post_id' => (int)$row['post_id'],
            'content' => (string)$row['content'],
            'post_content' => (string)$row['post_content'],
            'post_author' => (string)$row['post_author'],
            'time' => formatTimeAgo((string)$row['created_at']),
        ];
    }
    return $replies;
}

/**
 * Get a user by username.
 *
 * @param string $username The username to look up.
 * @return array<string, mixed>|null User row or null if not found.
 */
function getUserByUsername(string $username): ?array
{
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $result ?: null;
}

/**
 * Get suggested users to follow (excludes already-followed users).
 *
 * @param int $currentUserId The current user's ID.
 * @return array<int, array<string, mixed>> Array of user suggestion arrays.
 */
function getWhoToFollow(int $currentUserId): array
{
    $db = getDb();
    $stmt = $db->prepare('
        SELECT u.id, u.username AS name, u.avatar_letter AS letter, u.avatar_color AS color, u.username AS handle_raw
        FROM users u
        WHERE u.id != ?
        AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
        ORDER BY RANDOM()
        LIMIT 3
    ');
    $stmt->bindValue(1, $currentUserId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $currentUserId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'letter' => $row['letter'],
            'color' => $row['color'],
            'handle' => '@' . $row['handle_raw'],
        ];
    }

    return $users;
}

/**
 * Get aggregate stats for a user (post count, following, followers, likes received).
 *
 * @param int $userId The user's database ID.
 * @return array<int, array{label: string, value: string}> Array of stat arrays.
 */
function getUserStats(int $userId): array
{
    $db = getDb();

    $stmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ?');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $postCount = (int)$stmt->execute()->fetchArray()[0];

    $stmt = $db->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $followingCount = (int)$stmt->execute()->fetchArray()[0];

    $stmt = $db->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $followersCount = (int)$stmt->execute()->fetchArray()[0];

    $stmt = $db->prepare('SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $likesReceived = (int)$stmt->execute()->fetchArray()[0];

    return [
        ['label' => 'Posts', 'value' => (string)$postCount],
        ['label' => 'Friends', 'value' => formatCount($followingCount)],
        ['label' => 'Likes', 'value' => formatCount($likesReceived)],
        ['label' => 'Followers', 'value' => formatCount($followersCount)],
    ];
}

/**
 * Format an integer count into a human-readable abbreviated string.
 *
 * @param int $n The count to format.
 * @return string Formatted string (e.g. "1.2k", "3.5M", "42").
 */
function formatCount(int $n): string
{
    if ($n >= 1000000) return (string)round($n / 1000000, 1) . 'M';
    if ($n >= 1000) return (string)round($n / 1000, 1) . 'k';
    return (string)$n;
}

/**
 * Get comments for a post, ordered by creation time ascending. Limited to 5.
 *
 * @param int $postId The post's database ID.
 * @return array<int, array<string, mixed>> Array of comment rows.
 */
function getPostComments(int $postId): array
{
    $db = getDb();
    $stmt = $db->prepare('
        SELECT c.*, u.username, u.avatar_color, u.avatar_letter
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
        LIMIT 5
    ');
    $stmt->bindValue(1, $postId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $comments = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $comments[] = $row;
    }
    return $comments;
}

/**
 * Get recent notifications for a user (likes, comments, follows on their content).
 *
 * @param int $userId The user's database ID.
 * @return array<int, array{icon: string, color: string, text: string, time: string}> Notification arrays.
 */
function getNotifications(int $userId): array
{
    $db = getDb();
    $notifs = [];

    // Recent likes on user's posts
    $stmt = $db->prepare('
        SELECT u.username, u.avatar_color, l.created_at
        FROM likes l
        JOIN users u ON l.user_id = u.id
        JOIN posts p ON l.post_id = p.id
        WHERE p.user_id = ? AND l.user_id != ?
        ORDER BY l.created_at DESC
        LIMIT 3
    ');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $notifs[] = [
            'icon' => 'fa-heart',
            'color' => 'bg-cartoon-red',
            'text' => $row['username'] . ' liked your post!',
            'time' => formatTimeAgo($row['created_at']),
        ];
    }

    // Recent comments on user's posts
    $stmt = $db->prepare('
        SELECT u.username, c.content, c.created_at
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN posts p ON c.post_id = p.id
        WHERE p.user_id = ? AND c.user_id != ?
        ORDER BY c.created_at DESC
        LIMIT 3
    ');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $preview = mb_strlen($row['content']) > 30 ? mb_substr($row['content'], 0, 30) . '...' : $row['content'];
        $notifs[] = [
            'icon' => 'fa-comment-dots',
            'color' => 'bg-cartoon-blue',
            'text' => $row['username'] . ' commented: "' . $preview . '"',
            'time' => formatTimeAgo($row['created_at']),
        ];
    }

    // Recent follows
    $stmt = $db->prepare('
        SELECT u.username, f.created_at
        FROM follows f
        JOIN users u ON f.follower_id = u.id
        WHERE f.following_id = ?
        ORDER BY f.created_at DESC
        LIMIT 3
    ');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $notifs[] = [
            'icon' => 'fa-user-plus',
            'color' => 'bg-cartoon-green',
            'text' => $row['username'] . ' started following you',
            'time' => formatTimeAgo($row['created_at']),
        ];
    }

    // Sort by time (most recent first) and limit
    usort($notifs, fn($a, $b) => strcmp($a['time'], $b['time']));
    return array_slice($notifs, 0, 5);
}

// Legacy compatibility: populate global variables for pages that use them
$currentUser = currentUser();
$notifications = $currentUser ? getNotifications((int)$currentUser['id']) : [
    ["icon" => "fa-heart", "color" => "bg-cartoon-red", "text" => "CartoonKid liked your post!", "time" => "2m ago"],
    ["icon" => "fa-comment-dots", "color" => "bg-cartoon-blue", "text" => "PixelPal commented: \"This is rad!\"", "time" => "15m ago"],
    ["icon" => "fa-user-plus", "color" => "bg-cartoon-green", "text" => "RetroFan started following you", "time" => "1h ago"],
];

if ($currentUser) {
    $samplePosts = getFeedPosts((int)$currentUser['id']);
    $userPosts = getUserPosts((int)$currentUser['id'], (int)$currentUser['id']);
    $whoToFollow = getWhoToFollow((int)$currentUser['id']);
    $stats = getUserStats((int)$currentUser['id']);
} else {
    $samplePosts = getFeedPosts();
    $userPosts = [];
    $whoToFollow = [
        ["id" => 0, "name" => "ArtBot", "letter" => "A", "color" => "bg-cartoon-red", "handle" => "@artbot3000"],
        ["id" => 0, "name" => "MusicMix", "letter" => "M", "color" => "bg-cartoon-blue", "handle" => "@musicmix"],
        ["id" => 0, "name" => "GameGuru", "letter" => "G", "color" => "bg-cartoon-green", "handle" => "@gameguru"],
    ];
    $stats = [
        ["label" => "Posts", "value" => "0"],
        ["label" => "Friends", "value" => "0"],
        ["label" => "Likes", "value" => "0"],
        ["label" => "Followers", "value" => "0"],
    ];
}
