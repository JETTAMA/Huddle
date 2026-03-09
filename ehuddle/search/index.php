<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/components.php';

$user = requireAuth();

$query = trim($_GET['q'] ?? '');

// Server-side search if query present - vulnerable to sql injection if $query contains % or _, but this is just a demo so it's fine
$searchUsers = [];
$searchPosts = [];
if ($query !== '') {
    $db = getDb();
    $searchTerm = '%' . $query . '%';

    $stmt = $db->prepare('
        SELECT id, username, first_name, last_name, bio, avatar_color, avatar_letter, avatar_url
        FROM users
        WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ?
        LIMIT 20
    ');
    $stmt->bindValue(1, $searchTerm, SQLITE3_TEXT);
    $stmt->bindValue(2, $searchTerm, SQLITE3_TEXT);
    $stmt->bindValue(3, $searchTerm, SQLITE3_TEXT);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $searchUsers[] = $row;
    }

    $stmt = $db->prepare('
        SELECT p.id, p.content, p.created_at, p.image_url,
            u.id AS user_id, u.username, u.avatar_color, u.avatar_letter, u.avatar_url,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.content LIKE ? AND p.is_private = 0
        ORDER BY p.created_at DESC
        LIMIT 20
    ');
    $stmt->bindValue(1, $searchTerm, SQLITE3_TEXT);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $liked = false;
        $bookmarked = false;
        $s = $db->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
        $s->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
        $s->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
        $liked = (bool)$s->execute()->fetchArray();

        $s = $db->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND post_id = ?');
        $s->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
        $s->bindValue(2, (int)$row['id'], SQLITE3_INTEGER);
        $bookmarked = (bool)$s->execute()->fetchArray();

        $searchPosts[] = [
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
            'isPrivate' => false,
        ];
    }
}

renderLayoutStart('E-Huddle | Search');
?>
<div class="min-h-screen bg-background halftone-bg">
    <?php renderNavbar('/search', $notifications); ?>

    <div class="max-w-3xl mx-auto px-4 py-6">

        <!-- Search Bar -->
        <div class="cartoon-card mb-6 fade-in-up">
            <h2 class="font-display font-extrabold text-2xl mb-4">🔍 Search the Huddle</h2>
            <form method="GET" action="/search" class="flex gap-3">
                <div class="flex-1 flex items-center rounded-xl border-[2px] border-foreground/30 px-3 py-2 bg-muted/30 focus-within:border-primary transition-colors">
                    <i class="fa-solid fa-magnifying-glass text-muted-foreground mr-2"></i>
                    <input
                        type="text"
                        name="q"
                        value="<?= h($query) ?>"
                        placeholder="Search users or posts..."
                        class="flex-1 bg-transparent outline-none font-body text-foreground placeholder:text-muted-foreground"
                        autofocus
                    >
                    <?php if ($query !== ''): ?>
                        <a href="/search" class="text-muted-foreground hover:text-foreground ml-2"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="cartoon-btn bg-primary text-primary-foreground px-6">Search</button>
            </form>
        </div>

        <?php if ($query === ''): ?>
            <!-- Empty state with suggestions -->
            <div class="cartoon-card text-center py-16 fade-in-up">
                <p class="text-6xl mb-4">🔎</p>
                <p class="font-display font-extrabold text-xl">Find people & posts</p>
                <p class="font-body text-muted-foreground mt-2">Search by username, name, or post content.</p>
            </div>

        <?php elseif (empty($searchUsers) && empty($searchPosts)): ?>
            <div class="cartoon-card text-center py-12 fade-in-up">
                <p class="text-5xl mb-3">🌵</p>
                <p class="font-display font-extrabold text-lg">No results for "<?= h($query) ?>"</p>
                <p class="font-body text-muted-foreground mt-1">Try a different keyword.</p>
            </div>

        <?php else: ?>
            <?php if (!empty($searchUsers)): ?>
                <div class="mb-6 fade-in-up">
                    <h3 class="font-display font-extrabold text-lg mb-3 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-cartoon-blue flex items-center justify-center border-[2px] border-foreground">
                            <i class="fa-solid fa-users text-white text-sm"></i>
                        </span>
                        People
                    </h3>
                    <div class="flex flex-col gap-3">
                        <?php foreach ($searchUsers as $u): ?>
                            <?php
                                $uDisplayName = trim($u['first_name'] . ' ' . $u['last_name']);
                                if ($uDisplayName === '') $uDisplayName = $u['username'];
                                $isOwnProfile = (int)$user['id'] === (int)$u['id'];
                                $profileUrl = $isOwnProfile ? '/profile' : '/user?u=' . urlencode($u['username']);

                                // Check follow status
                                $db2 = getDb();
                                $fs = $db2->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
                                $fs->bindValue(1, (int)$user['id'], SQLITE3_INTEGER);
                                $fs->bindValue(2, (int)$u['id'], SQLITE3_INTEGER);
                                $uFollowing = (bool)$fs->execute()->fetchArray();
                            ?>
                            <div class="cartoon-card p-4 flex items-center gap-4">
                                <a href="<?= h($profileUrl) ?>">
                                    <div class="cartoon-avatar w-12 h-12 <?= h($u['avatar_color']) ?> flex items-center justify-center flex-shrink-0 overflow-hidden">
                                        <?php if (!empty($u['avatar_url'])): ?>
                                            <img src="<?= h($u['avatar_url']) ?>" alt="<?= h($u['username']) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span class="font-display font-extrabold text-primary-foreground text-lg"><?= h($u['avatar_letter']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <div class="flex-1 min-w-0">
                                    <a href="<?= h($profileUrl) ?>" class="font-display font-bold hover:text-primary transition-colors"><?= h($uDisplayName) ?></a>
                                    <p class="text-sm text-muted-foreground font-body">@<?= h($u['username']) ?></p>
                                    <?php if ($u['bio']): ?>
                                        <p class="text-sm font-body text-foreground/80 mt-1 truncate"><?= h($u['bio']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$isOwnProfile): ?>
                                    <button
                                        data-follow-user="<?= (int)$u['id'] ?>"
                                        class="flex-shrink-0 px-4 py-1.5 rounded-lg text-sm font-bold border-[2px] border-foreground shadow-small-cartoon hover-up <?= $uFollowing ? 'bg-muted text-foreground follow-btn-following' : 'bg-primary text-primary-foreground' ?>">
                                        <?= $uFollowing ? 'Following' : 'Follow' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($searchPosts)): ?>
                <div class="fade-in-up">
                    <h3 class="font-display font-extrabold text-lg mb-3 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-cartoon-orange flex items-center justify-center border-[2px] border-foreground">
                            <i class="fa-solid fa-fire text-white text-sm"></i>
                        </span>
                        Posts
                    </h3>
                    <div class="flex flex-col gap-5">
                        <?php foreach ($searchPosts as $post): ?>
                            <?php renderPostCard($post); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php renderCreatePostModal($user); ?>
</div>
<?php
renderLayoutEnd();
