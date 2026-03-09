<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/components.php';

$currentUser = currentUser();

$username = trim($_GET['u'] ?? '');
if ($username === '') {
    header('Location: /home');
    exit;
}

$profileUser = getUserByUsername($username);
if (!$profileUser) {
    require __DIR__ . '/../404.php';
    exit;
}

// If viewing own profile, redirect to /profile
if ($currentUser && (int)$currentUser['id'] === (int)$profileUser['id']) {
    header('Location: /profile');
    exit;
}

$profileUserId = (int)$profileUser['id'];
$currentUserId = $currentUser ? (int)$currentUser['id'] : null;

// Only public posts visible here
$publicPosts = getUserPosts($profileUserId, $currentUserId, true);

$db = getDb();
$stmt = $db->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
$stmt->bindValue(1, $profileUserId, SQLITE3_INTEGER);
$followingCount = (int)$stmt->execute()->fetchArray()[0];

$stmt = $db->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
$stmt->bindValue(1, $profileUserId, SQLITE3_INTEGER);
$followersCount = (int)$stmt->execute()->fetchArray()[0];

$stmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ? AND is_private = 0');
$stmt->bindValue(1, $profileUserId, SQLITE3_INTEGER);
$publicPostCount = (int)$stmt->execute()->fetchArray()[0];

$isFollowing = false;
if ($currentUserId) {
    $stmt = $db->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->bindValue(1, $currentUserId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $profileUserId, SQLITE3_INTEGER);
    $isFollowing = (bool)$stmt->execute()->fetchArray();
}

$displayName = trim($profileUser['first_name'] . ' ' . $profileUser['last_name']);
if ($displayName === '') $displayName = $profileUser['username'];

renderLayoutStart('E-Huddle | @' . $profileUser['username']);
?>
<div class="min-h-screen bg-background halftone-bg">
    <?php renderNavbar('/home', $notifications ?? []); ?>

    <div class="max-w-3xl mx-auto px-4 py-6">
        <div class="cartoon-card overflow-hidden p-0 fade-in-up">
            <!-- Banner -->
            <div class="h-36 md:h-48 bg-gradient-to-r from-cartoon-blue via-cartoon-purple to-cartoon-pink relative overflow-hidden">
                <?php if (!empty($profileUser['banner_url'])): ?>
                    <img src="<?= h($profileUser['banner_url']) ?>" alt="Banner" class="absolute inset-0 w-full h-full object-cover">
                <?php else: ?>
                    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, hsl(0 0% 100% / 0.3) 2px, transparent 2px); background-size: 20px 20px;"></div>
                    <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-cartoon-yellow rounded-full border-[3px] border-foreground opacity-50"></div>
                    <div class="absolute -top-4 -right-4 w-16 h-16 bg-cartoon-orange rounded-full border-[3px] border-foreground opacity-50 spin-slow"></div>
                <?php endif; ?>
            </div>

            <div class="px-6 pb-6">
                <div class="flex items-end justify-between -mt-12 relative z-10">
                    <div class="cartoon-avatar w-24 h-24 <?= h($profileUser['avatar_color']) ?> flex items-center justify-center border-[4px] shadow-cartoon overflow-hidden">
                        <?php if (!empty($profileUser['avatar_url'])): ?>
                            <img src="<?= h($profileUser['avatar_url']) ?>" alt="Avatar" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="font-display font-extrabold text-primary-foreground text-3xl"><?= h($profileUser['avatar_letter']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($currentUser): ?>
                        <button
                            id="publicFollowBtn"
                            data-follow-user="<?= $profileUserId ?>"
                            class="cartoon-btn text-sm py-2 px-5 <?= $isFollowing ? 'bg-muted text-foreground follow-btn-following' : 'bg-primary text-primary-foreground' ?>">
                            <?= $isFollowing ? 'Following' : 'Follow' ?>
                        </button>
                    <?php else: ?>
                        <a href="/login" class="cartoon-btn bg-primary text-primary-foreground text-sm py-2 px-5">Follow</a>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <h1 class="font-display text-2xl font-extrabold"><?= h($displayName) ?> ✨</h1>
                    <p class="text-muted-foreground font-body">@<?= h($profileUser['username']) ?></p>
                    <p class="font-body mt-3 text-foreground"><?= h($profileUser['bio'] ?: 'No bio yet.') ?></p>

                    <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-muted-foreground font-body">
                        <?php if ($profileUser['location']): ?>
                            <span class="flex items-center gap-1"><i class="fa-solid fa-location-dot w-4 h-4"></i> <?= h($profileUser['location']) ?></span>
                        <?php endif; ?>
                        <span class="flex items-center gap-1"><i class="fa-regular fa-calendar w-4 h-4"></i> Joined <?= date('F Y', strtotime($profileUser['created_at'])) ?></span>
                        <?php if ($profileUser['website']): ?>
                            <span class="flex items-center gap-1"><i class="fa-solid fa-link w-4 h-4"></i> <?= h($profileUser['website']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-6 mt-4">
                        <div class="flex items-center gap-1">
                            <span class="font-display font-extrabold"><?= $publicPostCount ?></span>
                            <span class="text-muted-foreground text-sm font-body">Posts</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="font-display font-extrabold"><?= $followingCount ?></span>
                            <span class="text-muted-foreground text-sm font-body">Following</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="font-display font-extrabold"><?= formatCount($followersCount) ?></span>
                            <span class="text-muted-foreground text-sm font-body">Followers</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mt-4 cartoon-card p-0 overflow-hidden">
            <div class="flex border-b-[3px] border-foreground/10">
                <?php 
                $tabs = ["Posts", "Likes", "Media", "Replies"];
                foreach ($tabs as $index => $tab): 
                ?>
                    <button class="profile-tab flex-1 py-3 font-display font-bold text-sm transition-colors relative <?= $index === 0 ? 'text-primary border-b-[3px] border-primary active' : 'text-muted-foreground hover:text-foreground hover:bg-muted/30' ?>" 
                            data-tab="<?= strtolower($tab) ?>" 
                            data-user-id="<?= $profileUserId ?>">
                        <?= h($tab) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="profilePosts" class="flex flex-col gap-5 mt-5">
            <?php foreach ($publicPosts as $post): ?>
                <?php renderPostCard($post); ?>
            <?php endforeach; ?>

            <?php if (empty($publicPosts)): ?>
                <div class="cartoon-card text-center py-12">
                    <p class="text-4xl mb-3">📝</p>
                    <p class="font-display font-bold text-lg">No public posts yet!</p>
                    <p class="font-body text-muted-foreground">@<?= h($profileUser['username']) ?> hasn't shared anything public yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($currentUser): renderCreatePostModal($currentUser); endif; ?>
</div>
<?php
renderLayoutEnd();
