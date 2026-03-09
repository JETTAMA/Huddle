<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/markdown.php';

/**
 * Render the E-Huddle circular logo.
 *
 * @param string $bg        Tailwind background class.
 * @param string $textColor Tailwind text color class.
 * @param string $sizeClass Tailwind size/text classes.
 * @param string $extraClass Additional CSS classes.
 * @return void
 */
function renderLogo(
    string $bg = 'bg-accent',
    string $textColor = 'text-foreground',
    string $sizeClass = 'w-12 h-12 text-xl',
    string $extraClass = ''
): void
{
    ?>
    <div class="<?= h($sizeClass) ?> <?= h($extraClass) ?> rounded-full border-[3px] border-foreground flex items-center justify-center overflow-hidden"
         style="box-shadow: 3px 3px 0px hsl(260 30% 15%); background: hsl(45, 100%, 96%);">
        <img src="/assets/logo.png" alt="E-Huddle" class="w-full h-full object-contain p-0.5">
    </div>
    <?php
}

/**
 * Render a navigation button/link.
 *
 * @param string $href      URL destination.
 * @param string $label     Button text label.
 * @param string $iconClass FontAwesome icon class.
 * @param bool   $active    Whether this nav item is active.
 * @return void
 */
function navButton(string $href, string $label, string $iconClass, bool $active): void
{
    $base = 'flex items-center gap-2 px-4 py-2 rounded-xl border-[2px] font-display font-bold text-sm transition-colors';
    $classes = $active
        ? 'bg-accent text-foreground border-foreground shadow-small-cartoon'
        : 'bg-white/10 text-primary-foreground border-white/30 hover:bg-white/20';
    ?>
    <a href="<?= h($href) ?>" class="<?= h($base . ' ' . $classes) ?>">
        <i class="fa-solid <?= h($iconClass) ?> w-4"></i>
        <?= h($label) ?>
    </a>
    <?php
}

/**
 * Render the notifications dropdown panel.
 *
 * @param array<int, array{icon: string, color: string, text: string, time: string}> $notifications
 * @return void
 */
function renderNotifications(array $notifications): void
{
    ?>
    <div id="notificationsMenu" class="hidden absolute right-0 top-14 w-80 rounded-2xl border-[3px] border-foreground bg-card p-0 overflow-hidden z-50 shadow-cartoon-lg pop-in">
        <div class="px-4 py-3 bg-cartoon-yellow border-b-[3px] border-foreground">
            <h3 class="font-display font-extrabold text-foreground text-lg">Notifications 🔔</h3>
        </div>
        <div class="divide-y-[2px] divide-foreground/20 bg-card">
            <?php foreach ($notifications as $n): ?>
                <div class="px-4 py-3 flex items-start gap-3 hover:bg-secondary cursor-pointer transition-colors border-b-[2px] border-foreground/10 last:border-b-0">
                    <div class="<?= h($n['color']) ?> w-8 h-8 rounded-full border-[2px] border-foreground flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid <?= h($n['icon']) ?> text-white text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-body font-semibold text-foreground leading-tight"><?= h($n['text']) ?></p>
                        <p class="text-xs text-muted-foreground mt-0.5"><?= h($n['time']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="w-full py-2 text-center text-sm font-display font-bold text-primary hover:bg-secondary border-t-[2px] border-foreground/20 bg-card">See All</button>
    </div>
    <?php
}

/**
 * Render the main navigation bar with search, links, and notifications.
 *
 * @param string $activePath Current URL path for active state highlighting.
 * @param array<int, array{icon: string, color: string, text: string, time: string}> $notifications
 * @return void
 */
function renderNavbar(string $activePath, array $notifications): void
{
    $user = currentUser();
    $notifCount = count($notifications);
    ?>
    <nav class="app-navbar sticky top-0 z-50 border-b-[3px] border-foreground bg-card slide-down">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="/" class="flex items-center gap-2 group">
                <?php renderLogo('bg-accent', 'text-foreground', 'w-10 h-10 text-lg', 'wobble-loop'); ?>
                <span class="font-display text-2xl font-extrabold text-primary-foreground hidden sm:block tracking-tight">E-Huddle</span>
            </a>

            <div class="hidden md:flex items-center rounded-xl border-[2px] border-foreground/20 px-3 py-1.5 w-72 relative">
                <i class="fa-solid fa-magnifying-glass w-4 h-4 text-foreground/60 mr-2"></i>
                <input type="text" id="searchInput" placeholder="Search the huddle..." class="bg-transparent text-foreground placeholder:text-foreground/40 outline-none text-sm font-body w-full" autocomplete="off">
                <div id="searchResults" class="hidden absolute top-full left-0 right-0 mt-2 rounded-2xl border-[3px] border-foreground bg-card p-0 overflow-hidden z-50 shadow-cartoon-lg max-h-96 overflow-y-auto"></div>
            </div>

            <div class="hidden md:flex items-center gap-2">
                <?php navButton('/home', 'Feed', 'fa-house', $activePath === '/home'); ?>
                <?php navButton('/search', 'Search', 'fa-magnifying-glass', $activePath === '/search'); ?>
                <?php navButton('/profile', 'Profile', 'fa-user', $activePath === '/profile'); ?>

                <button data-open-modal="createPostModal" class="nav-post-btn flex items-center gap-2 px-4 py-2 rounded-xl border-[2px] border-foreground font-display font-bold text-sm shadow-small-cartoon bg-secondary">
                    <i class="fa-solid fa-circle-plus"></i>
                    Post
                </button>

                <div class="relative">
                    <button id="notifToggle" class="relative p-2 rounded-xl border-[2px] border-foreground/20 bg-secondary text-foreground hover-up">
                        <i class="fa-regular fa-bell w-5 h-5"></i>
                        <?php if ($notifCount > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-cartoon-red rounded-full border-[2px] border-foreground text-white text-xs font-bold flex items-center justify-center"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </button>
                    <?php renderNotifications($notifications); ?>
                </div>

                <?php if ($user): ?>
                    <a href="/api/logout" class="flex items-center gap-2 px-3 py-2 rounded-xl border-[2px] border-foreground/20 bg-secondary text-foreground font-display font-bold text-sm hover:bg-secondary/80">
                        <i class="fa-solid fa-right-from-bracket w-4"></i>
                        Logout
                    </a>
                <?php endif; ?>
            </div>

            <button id="mobileMenuToggle" class="md:hidden text-foreground">
                <i class="fa-solid fa-bars w-6 h-6"></i>
            </button>
        </div>

        <div id="mobileMenu" class="hidden md:hidden overflow-hidden border-t-[2px] border-foreground/10 bg-card">
            <div class="p-4 flex flex-col gap-3">
                <div class="flex items-center rounded-xl border-[2px] border-foreground/20 px-3 py-2">
                    <i class="fa-solid fa-magnifying-glass w-4 h-4 text-foreground/60 mr-2"></i>
                    <input type="text" placeholder="Search..." class="bg-transparent text-foreground placeholder:text-foreground/40 outline-none text-sm font-body w-full search-input-mobile">
                </div>
                <a href="/home" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-secondary text-foreground font-display font-bold border-[2px] border-foreground/10">
                    <i class="fa-solid fa-house w-5 h-5"></i>
                    Feed
                </a>
                <a href="/search" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-secondary text-foreground font-display font-bold border-[2px] border-foreground/10">
                    <i class="fa-solid fa-magnifying-glass w-5 h-5"></i>
                    Search
                </a>
                <a href="/profile" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-secondary text-foreground font-display font-bold border-[2px] border-foreground/10">
                    <i class="fa-solid fa-user w-5 h-5"></i>
                    Profile
                </a>
                <button data-open-modal="createPostModal" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-cartoon-pink text-white font-display font-bold border-[2px] border-foreground shadow-small-cartoon">
                    <i class="fa-solid fa-circle-plus w-5 h-5"></i>
                    Create Post
                </button>
                <?php if ($user): ?>
                    <a href="/api/logout" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-cartoon-red text-white font-display font-bold border-[2px] border-foreground shadow-small-cartoon">
                        <i class="fa-solid fa-right-from-bracket w-5 h-5"></i>
                        Logout
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <?php
}

/**
 * Render the left sidebar with quick links, topics, and trending tags.
 *
 * @param array<int, array{icon: string, label: string, color: string}> $categories
 * @param array<int, array{icon: string, label: string, count: int}>    $topics
 * @param array<int, string> $trendingTags
 * @return void
 */
function renderSidebar(array $categories, array $topics, array $trendingTags): void
{
    ?>
    <aside class="hidden lg:flex flex-col gap-4 w-64 flex-shrink-0">
        <div class="cartoon-card pop-in">
            <h3 class="font-display font-extrabold text-lg mb-3">⚡ Quick Links</h3>
            <div class="flex flex-col gap-2">
                <?php foreach ($categories as $cat): ?>
                    <button class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-muted/50 transition-colors hover:translate-x-1">
                        <div class="<?= h($cat['color']) ?> w-8 h-8 rounded-lg border-[2px] border-foreground flex items-center justify-center">
                            <i class="fa-solid <?= h($cat['icon']) ?> text-primary-foreground text-sm"></i>
                        </div>
                        <span class="font-body font-bold text-sm"><?= h($cat['label']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cartoon-card pop-in" style="animation-delay: 0.08s;">
            <h3 class="font-display font-extrabold text-lg mb-3">🎯 Topics</h3>
            <div class="flex flex-col gap-2">
                <?php foreach ($topics as $topic): ?>
                    <button class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-muted/50 transition-colors hover:translate-x-1">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid <?= h($topic['icon']) ?> w-4 h-4 text-muted-foreground"></i>
                            <span class="font-body font-semibold text-sm"><?= h($topic['label']) ?></span>
                        </div>
                        <span class="text-xs font-bold bg-muted px-2 py-0.5 rounded-full"><?= h((string)$topic['count']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cartoon-card bg-cartoon-purple/10 pop-in" style="animation-delay: 0.16s;">
            <h3 class="font-display font-extrabold text-lg mb-3">🔥 Trending Tags</h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($trendingTags as $tag): ?>
                    <span class="px-3 py-1 rounded-full bg-cartoon-purple text-primary-foreground text-xs font-bold border-[2px] border-foreground cursor-pointer shadow-small-cartoon hover-up">
                        <?= h($tag) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
    <?php
}

/**
 * Render a single post card with interactions (like, comment, bookmark).
 *
 * @param array<string, mixed> $post Post data from getFeedPosts/getUserPosts.
 * @return void
 */
function renderPostCard(array $post): void
{
    $postId = (int)$post['id'];
    $liked = !empty($post['liked']);
    $bookmarked = !empty($post['bookmarked']);
    $comments = getPostComments($postId);
    ?>
    <div class="cartoon-card fade-in-up" data-post-id="<?= $postId ?>">
        <div class="flex items-start gap-3">
            <div class="cartoon-avatar w-12 h-12 <?= h($post['avatarColor']) ?> flex items-center justify-center flex-shrink-0 overflow-hidden">
                <?php if (!empty($post['avatarUrl'])): ?>
                    <img src="<?= h($post['avatarUrl']) ?>" alt="<?= h($post['username']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="font-display font-extrabold text-primary-foreground text-lg"><?= h($post['avatarLetter']) ?></span>
                <?php endif; ?>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="/user?u=<?= urlencode($post['username']) ?>" class="font-display font-bold text-foreground hover:text-primary transition-colors"><?= h($post['username']) ?></a>
                        <span class="text-muted-foreground text-sm ml-2 font-body">@<?= h($post['handle']) ?></span>
                        <span class="text-muted-foreground text-sm ml-2">· <?= h($post['time']) ?></span>
                        <?php if (!empty($post['isPrivate'])): ?>
                            <span class="ml-2 inline-flex items-center gap-1 text-xs font-bold text-cartoon-purple bg-cartoon-purple/10 border border-cartoon-purple/30 px-2 py-0.5 rounded-full">
                                <i class="fa-solid fa-lock text-[10px]"></i> Private
                            </span>
                        <?php endif; ?>
                    </div>
                    <button class="text-muted-foreground hover:text-foreground hover-up">
                        <i class="fa-solid fa-ellipsis w-5 h-5"></i>
                    </button>
                </div>

                <p class="mt-2 font-body text-foreground leading-relaxed"><?= parseMarkdown($post['content']) ?></p>

                <?php if (!empty($post['image'])): ?>
                    <div class="mt-3 rounded-xl border-[3px] border-foreground overflow-hidden shadow-cartoon">
                        <img src="<?= h($post['image']) ?>" alt="Post" class="w-full h-48 sm:h-64 object-cover" loading="lazy" onerror="this.onerror=null;this.src='/assets/post-placeholder.svg';">
                    </div>
                <?php endif; ?>

                <div class="flex items-center gap-1 mt-3 -ml-2">
                    <button data-like-post="<?= $postId ?>" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-body font-bold text-sm transition-colors <?= $liked ? 'text-cartoon-red' : 'text-muted-foreground hover:text-cartoon-red' ?> hover:bg-cartoon-red/10">
                        <i class="<?= $liked ? 'fa-solid' : 'fa-regular' ?> fa-heart w-5 h-5"></i>
                        <span class="like-count"><?= h((string)$post['likes']) ?></span>
                    </button>

                    <button data-toggle="comments-<?= $postId ?>" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-muted-foreground hover:text-cartoon-blue hover:bg-cartoon-blue/10 font-body font-bold text-sm transition-colors">
                        <i class="fa-regular fa-comment w-5 h-5"></i>
                        <span class="comment-count"><?= h((string)$post['comments']) ?></span>
                    </button>

                    <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-muted-foreground hover:text-cartoon-green hover:bg-cartoon-green/10 font-body font-bold text-sm transition-colors">
                        <i class="fa-solid fa-share-nodes w-5 h-5"></i>
                        <?= h((string)$post['shares']) ?>
                    </button>

                    <div class="flex-1"></div>

                    <button data-bookmark-post="<?= $postId ?>" class="p-1.5 rounded-xl transition-colors <?= $bookmarked ? 'text-cartoon-yellow' : 'text-muted-foreground hover:text-cartoon-yellow' ?>">
                        <i class="<?= $bookmarked ? 'fa-solid' : 'fa-regular' ?> fa-bookmark w-5 h-5"></i>
                    </button>
                </div>

                <div id="comments-<?= $postId ?>" class="hidden mt-3 overflow-hidden">
                    <div class="flex flex-col gap-3 pt-3 border-t-[2px] border-foreground/10">
                        <div class="comments-list" data-post-id="<?= $postId ?>">
                            <?php foreach ($comments as $comment): ?>
                                <div class="flex items-start gap-2 mb-3">
                                    <div class="cartoon-avatar w-8 h-8 <?= h($comment['avatar_color']) ?> flex items-center justify-center flex-shrink-0">
                                        <span class="font-display font-bold text-primary-foreground text-xs"><?= h($comment['avatar_letter']) ?></span>
                                    </div>
                                    <div class="cartoon-bubble flex-1">
                                        <span class="font-display font-bold text-sm"><?= h($comment['username']) ?></span>
                                        <p class="font-body text-sm mt-0.5"><?= h($comment['content']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <input type="text" placeholder="Drop a comment..." class="cartoon-input flex-1 text-sm py-2 comment-input" data-post-id="<?= $postId ?>">
                            <button data-submit-comment="<?= $postId ?>" class="cartoon-btn bg-primary text-primary-foreground text-sm py-2 px-4">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render the Create Post modal dialog.
 *
 * @param array<string, mixed>|null $user Current user data, or null for placeholder avatar.
 * @return void
 */
function renderCreatePostModal(?array $user = null): void
{
    $avatarColor = $user ? $user['avatar_color'] : 'bg-cartoon-blue';
    $avatarLetter = $user ? $user['avatar_letter'] : '?';
    ?>
    <div id="createPostModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
        <div class="modal-overlay absolute inset-0 bg-foreground/40" data-close-modal="createPostModal"></div>
        <div class="modal-card relative w-full max-w-lg rounded-2xl border-[3px] border-foreground bg-card overflow-hidden shadow-cartoon-lg bounce-in">
            <div class="flex items-center justify-between px-5 py-3 bg-cartoon-pink border-b-[3px] border-foreground">
                <h2 class="font-display font-extrabold text-xl text-primary-foreground">Create a Post ✨</h2>
                <button data-close-modal="createPostModal" class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-primary-foreground hover-up">
                    <i class="fa-solid fa-xmark w-5 h-5"></i>
                </button>
            </div>

            <div class="p-5">
                <div class="flex items-start gap-3">
                    <div class="cartoon-avatar w-10 h-10 <?= h($avatarColor) ?> flex items-center justify-center flex-shrink-0">
                        <span class="font-display font-bold text-primary-foreground"><?= h($avatarLetter) ?></span>
                    </div>
                    <textarea id="createPostContent" placeholder="What's on your mind? Share something awesome!" class="flex-1 bg-transparent resize-none outline-none font-body text-foreground placeholder:text-muted-foreground min-h-[120px] text-base"></textarea>
                </div>

                <div id="postImageUploadArea" class="mt-4 rounded-xl border-[2px] border-dashed border-foreground/30 p-4 flex items-center justify-center hover:bg-muted/30 cursor-pointer transition-colors overflow-hidden">
                    <div class="text-center w-full">
                        <div id="postImagePreview" class="hidden mb-3 rounded-lg border-[2px] border-foreground overflow-hidden max-h-40">
                            <img src="" alt="Preview" class="w-full h-full object-cover">
                        </div>
                        <div id="postImagePlaceholder">
                            <i class="fa-regular fa-image w-8 h-8 text-muted-foreground mx-auto mb-1"></i>
                            <p class="text-sm font-body text-muted-foreground font-semibold">Click to upload an image</p>
                        </div>
                        <input type="file" id="postImageFile" class="hidden" accept="image/*">
                        <input type="hidden" id="createPostImage" value="">
                    </div>
                </div>

                <div class="flex items-center justify-between mt-4">
                    <div class="flex items-center gap-2">
                        <button class="p-2 rounded-xl hover:bg-muted/50 text-cartoon-green"><i class="fa-regular fa-image w-5 h-5"></i></button>
                        <button class="p-2 rounded-xl hover:bg-muted/50 text-cartoon-yellow"><i class="fa-regular fa-face-smile w-5 h-5"></i></button>
                        <button class="p-2 rounded-xl hover:bg-muted/50 text-cartoon-red"><i class="fa-solid fa-location-dot w-5 h-5"></i></button>
                        <button class="p-2 rounded-xl hover:bg-muted/50 text-cartoon-purple"><i class="fa-solid fa-hashtag w-5 h-5"></i></button>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 cursor-pointer select-none" title="Only visible to you">
                            <input type="checkbox" id="postPrivateToggle" class="hidden">
                            <span id="postPrivacyBtn" class="flex items-center gap-1 px-3 py-1.5 rounded-xl border-[2px] border-foreground/20 text-sm font-display font-bold text-muted-foreground hover:border-cartoon-purple hover:text-cartoon-purple transition-colors">
                                <i class="fa-solid fa-globe w-4 h-4" id="postPrivacyIcon"></i>
                                <span id="postPrivacyLabel">Public</span>
                            </span>
                        </label>
                        <button id="submitPostBtn" class="cartoon-btn bg-primary text-primary-foreground">Post It! 🚀</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
