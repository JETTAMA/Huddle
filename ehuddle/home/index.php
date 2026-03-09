<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/components.php';

$user = requireAuth();

renderLayoutStart('E-Huddle | Home Feed');
?>
<div class="min-h-screen bg-background halftone-bg">
    <?php renderNavbar('/home', $notifications); ?>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex gap-6">
            <?php renderSidebar($categories, $topics, $trendingTags); ?>

            <main class="flex-1 min-w-0">
                <button data-open-modal="createPostModal" class="cartoon-card mb-6 p-5 cursor-pointer hover:bg-muted/30 w-full text-left pop-in">
                    <div class="flex items-center gap-3">
                        <div class="cartoon-avatar w-10 h-10 <?= h($user['avatar_color']) ?> flex items-center justify-center">
                            <span class="font-display font-bold text-primary-foreground"><?= h($user['avatar_letter']) ?></span>
                        </div>
                        <div class="flex-1 bg-muted rounded-xl px-4 py-3 text-muted-foreground font-body">
                            What's on your mind? Share something awesome! ✨
                        </div>
                    </div>
                </button>

                <div id="postsFeed" class="flex flex-col gap-5">
                    <?php foreach ($samplePosts as $post): ?>
                        <?php renderPostCard($post); ?>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($samplePosts)): ?>
                    <div class="cartoon-card text-center py-12">
                        <p class="text-4xl mb-3">🦗</p>
                        <p class="font-display font-bold text-lg">No posts yet!</p>
                        <p class="font-body text-muted-foreground">Be the first to share something awesome.</p>
                    </div>
                <?php endif; ?>
            </main>

            <aside class="hidden xl:flex flex-col gap-4 w-64 flex-shrink-0">
                <div class="cartoon-card pop-in">
                    <h3 class="font-display font-extrabold text-lg mb-3">🌟 Who to Follow</h3>
                    <?php foreach ($whoToFollow as $wtf): ?>
                        <div class="flex items-center gap-3 py-2">
                            <div class="cartoon-avatar w-9 h-9 <?= h($wtf['color']) ?> flex items-center justify-center">
                                <span class="font-display font-bold text-primary-foreground text-sm"><?= h($wtf['letter']) ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-display font-bold text-sm truncate"><?= h($wtf['name']) ?></p>
                                <p class="text-xs text-muted-foreground truncate"><?= h($wtf['handle']) ?></p>
                            </div>
                            <button data-follow-user="<?= (int)$wtf['id'] ?>" class="px-3 py-1 rounded-lg bg-primary text-primary-foreground text-xs font-bold border-[2px] border-foreground shadow-small-cartoon hover-up">Follow</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cartoon-card bg-cartoon-yellow/20 pop-in" style="animation-delay: 0.1s;">
                    <h3 class="font-display font-extrabold text-lg mb-2">📊 Your Stats</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach ($stats as $stat): ?>
                            <div class="text-center p-2 rounded-xl bg-card border-[2px] border-foreground/20">
                                <p class="font-display font-extrabold text-lg"><?= h($stat['value']) ?></p>
                                <p class="text-xs text-muted-foreground font-body font-semibold"><?= h($stat['label']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <?php renderCreatePostModal($user); ?>
</div>
<?php
renderLayoutEnd();
