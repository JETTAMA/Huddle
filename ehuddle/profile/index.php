<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/components.php';

$user = requireAuth();
$profileStats = getUserStats((int)$user['id']);

$db = getDb();
$stmt = $db->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
$stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
$followingCount = (int)$stmt->execute()->fetchArray()[0];

$stmt = $db->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
$stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
$followersCount = (int)$stmt->execute()->fetchArray()[0];

$displayName = trim($user['first_name'] . ' ' . $user['last_name']);
if ($displayName === '') $displayName = $user['username'];

renderLayoutStart('E-Huddle | Profile');
?>
<div class="min-h-screen bg-background halftone-bg">
    <?php renderNavbar('/profile', $notifications); ?>

    <div class="max-w-3xl mx-auto px-4 py-6">
        <div class="cartoon-card overflow-hidden p-0 fade-in-up">
            <div class="h-36 md:h-48 bg-gradient-to-r from-cartoon-blue via-cartoon-purple to-cartoon-pink relative overflow-hidden banner-upload-wrapper" id="profileBanner" onclick="document.getElementById('bannerUpload').click()">
                <?php if (!empty($user['banner_url'])): ?>
                    <img src="<?= h($user['banner_url']) ?>" alt="Banner" class="absolute inset-0 w-full h-full object-cover" id="bannerPreview">
                <?php else: ?>
                    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, hsl(0 0% 100% / 0.3) 2px, transparent 2px); background-size: 20px 20px;"></div>
                    <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-cartoon-yellow rounded-full border-[3px] border-foreground opacity-50"></div>
                    <div class="absolute -top-4 -right-4 w-16 h-16 bg-cartoon-orange rounded-full border-[3px] border-foreground opacity-50 spin-slow"></div>
                    <div class="absolute top-4 left-1/2 w-12 h-12 bg-cartoon-green blob-shape border-[3px] border-foreground opacity-40"></div>
                <?php endif; ?>
                <div class="banner-upload-hint">
                    <span class="bg-black/60 text-white font-display font-bold px-4 py-2 rounded-xl flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-camera"></i> Change Banner
                    </span>
                </div>
            </div>
            <input type="file" id="bannerUpload" accept="image/*" class="hidden">

            <div class="px-6 pb-6">
                <div class="flex items-end justify-between -mt-12 relative z-10">
                    <div class="avatar-upload-wrapper cartoon-avatar w-24 h-24 <?= h($user['avatar_color']) ?> flex items-center justify-center border-[4px] shadow-cartoon pulse-soft overflow-hidden" onclick="document.getElementById('avatarUpload').click()">
                        <?php if (!empty($user['avatar_url'])): ?>
                            <img src="<?= h($user['avatar_url']) ?>" alt="Avatar" class="w-full h-full object-cover" id="avatarPreview">
                        <?php else: ?>
                            <span class="font-display font-extrabold text-primary-foreground text-3xl" id="avatarLetter"><?= h($user['avatar_letter']) ?></span>
                        <?php endif; ?>
                        <div class="avatar-upload-overlay"><i class="fa-solid fa-camera"></i></div>
                    </div>
                    <input type="file" id="avatarUpload" accept="image/*" class="hidden">
                    <button id="editProfileBtn" class="cartoon-btn bg-card text-foreground text-sm py-2 px-4 flex items-center gap-2">
                        <i class="fa-regular fa-pen-to-square w-4 h-4"></i>
                        Edit Profile
                    </button>
                </div>

                <div class="mt-4" id="profileInfo">
                    <h1 class="font-display text-2xl font-extrabold" id="profileDisplayName"><?= h($displayName) ?> ✨</h1>
                    <p class="text-muted-foreground font-body">@<?= h($user['username']) ?></p>
                    <p class="font-body mt-3 text-foreground" id="profileBio"><?= h($user['bio'] ?: 'No bio yet. Click Edit Profile to add one!') ?></p>

                    <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-muted-foreground font-body">
                        <?php if ($user['location']): ?>
                            <span class="flex items-center gap-1" id="profileLocation"><i class="fa-solid fa-location-dot w-4 h-4"></i> <?= h($user['location']) ?></span>
                        <?php endif; ?>
                        <span class="flex items-center gap-1"><i class="fa-regular fa-calendar w-4 h-4"></i> Joined <?= date('F Y', strtotime($user['created_at'])) ?></span>
                        <?php if ($user['website']): ?>
                            <span class="flex items-center gap-1" id="profileWebsite"><i class="fa-solid fa-link w-4 h-4"></i> <?= h($user['website']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-6 mt-4">
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

                <!-- Edit Profile Form (hidden by default) -->
                <div id="editProfileForm" class="mt-4 hidden">
                    <form class="flex flex-col gap-4" onsubmit="return false;">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="font-display font-bold text-sm mb-1 block">First Name</label>
                                <input type="text" name="first_name" value="<?= h($user['first_name']) ?>" class="cartoon-input w-full text-sm">
                            </div>
                            <div>
                                <label class="font-display font-bold text-sm mb-1 block">Last Name</label>
                                <input type="text" name="last_name" value="<?= h($user['last_name']) ?>" class="cartoon-input w-full text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="font-display font-bold text-sm mb-1 block">Bio</label>
                            <textarea name="bio" class="cartoon-input w-full text-sm" rows="3"><?= h($user['bio']) ?></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="font-display font-bold text-sm mb-1 block">Location</label>
                                <input type="text" name="location" value="<?= h($user['location']) ?>" class="cartoon-input w-full text-sm">
                            </div>
                            <div>
                                <label class="font-display font-bold text-sm mb-1 block">Website</label>
                                <input type="text" name="website" value="<?= h($user['website']) ?>" class="cartoon-input w-full text-sm">
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" id="saveProfileBtn" class="cartoon-btn bg-primary text-primary-foreground text-sm py-2 px-6">Save ✨</button>
                            <button type="button" id="cancelEditBtn" class="cartoon-btn bg-card text-foreground text-sm py-2 px-6">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-4 cartoon-card p-0 overflow-hidden">
            <div class="flex border-b-[3px] border-foreground/10">
                <?php foreach ($tabs as $index => $tab): ?>
                    <button class="profile-tab flex-1 py-3 font-display font-bold text-sm transition-colors relative <?= $index === 0 ? 'text-primary border-b-[3px] border-primary active' : 'text-muted-foreground hover:text-foreground hover:bg-muted/30' ?>" data-tab="<?= strtolower($tab) ?>">
                        <?= h($tab) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="profilePosts" class="flex flex-col gap-5 mt-5">
            <?php foreach ($userPosts as $post): ?>
                <?php renderPostCard($post); ?>
            <?php endforeach; ?>

            <?php if (empty($userPosts)): ?>
                <div class="cartoon-card text-center py-12">
                    <p class="text-4xl mb-3">📝</p>
                    <p class="font-display font-bold text-lg">No posts yet!</p>
                    <p class="font-body text-muted-foreground">Share your first thought with the huddle.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php renderCreatePostModal($user); ?>
</div>
<?php
renderLayoutEnd();
