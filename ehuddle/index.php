<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/components.php';

// If logged in, redirect to home feed
// Open redirection with xss vulnerability if ?redirect= is used without validation, but this is just a demo so it's fine
if (isLoggedIn()) {
    header('Location: /home');
    exit;
}

renderLayoutStart('E-Huddle | Landing');
?>
<div class="min-h-screen bg-background halftone-bg overflow-hidden">
    <header class="border-b-[3px] border-foreground bg-card" style="box-shadow: 0 4px 0px hsl(260 30% 15%);">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <?php renderLogo('bg-accent', 'text-foreground', 'w-12 h-12 text-xl', 'wobble-loop'); ?>
                <span class="font-display text-3xl font-extrabold text-foreground">E-Huddle</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="/login" class="cartoon-btn bg-card text-foreground">Log In</a>
                <a href="/signup" class="cartoon-btn bg-primary text-primary-foreground">Sign Up</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4">
        <section class="py-16 md:py-24 relative">
            <?php foreach ($floatingEmojis as $i => $emoji): ?>
                <div class="absolute text-3xl md:text-5xl select-none pointer-events-none float"
                     style="left: <?= 10 + ($i * 12) % 80 ?>%; top: <?= 10 + (($i * 17) % 60) ?>%; animation-delay: <?= $i * 0.2 ?>s;">
                    <?= h($emoji) ?>
                </div>
            <?php endforeach; ?>

            <div class="text-center relative z-10">
                <h1 class="font-display text-5xl md:text-7xl lg:text-8xl font-extrabold text-foreground leading-tight bounce-in">
                    Welcome to the<br>
                    <span class="relative inline-block">
                        <span class="relative z-10 text-primary">Huddle!</span>
                        <span class="absolute -inset-2 bg-accent rounded-2xl -z-0 border-[3px] border-foreground shadow-cartoon wobble-loop" style="transform: rotate(-1deg);"></span>
                    </span>
                </h1>

                <p class="mt-8 text-xl md:text-2xl font-body font-semibold text-muted-foreground max-w-2xl mx-auto fade-in-up" style="animation-delay: 0.12s;">
                    The most fun social network on the internet! Share, laugh, and connect with awesome people. 🎉
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 fade-in-up" style="animation-delay: 0.2s;">
                    <a href="/signup" class="cartoon-btn bg-cartoon-pink text-primary-foreground text-xl px-10 py-4">Join the Huddle 🚀</a>
                    <a href="/home" class="cartoon-btn bg-accent text-foreground text-xl px-10 py-4">Explore Feed 👀</a>
                </div>
            </div>
        </section>

        <section class="py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($features as $i => $feature): ?>
                    <div class="cartoon-card text-center p-8 pop-in" style="animation-delay: <?= $i * 0.12 ?>s;">
                        <div class="<?= h($feature['color']) ?> w-16 h-16 rounded-2xl border-[3px] border-foreground mx-auto flex items-center justify-center shadow-cartoon pulse-soft">
                            <i class="fa-solid <?= h($feature['icon']) ?> w-8 h-8 text-primary-foreground"></i>
                        </div>
                        <h3 class="font-display font-extrabold text-xl mt-5"><?= h($feature['title']) ?> <?= h($feature['emoji']) ?></h3>
                        <p class="font-body text-muted-foreground mt-2"><?= h($feature['desc']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="py-16">
            <div class="cartoon-card bg-cartoon-purple/10 p-10 text-center">
                <h2 class="font-display text-3xl md:text-4xl font-extrabold">Ready to huddle up? 🤗</h2>
                <p class="font-body text-lg text-muted-foreground mt-3 max-w-lg mx-auto">Join thousands of creative people sharing their best moments!</p>
                <a href="/signup" class="inline-block cartoon-btn bg-cartoon-orange text-primary-foreground text-lg mt-6">Get Started - It's Free! 🎉</a>
            </div>
        </section>
    </main>

    <footer class="border-t-[3px] border-foreground bg-card mt-10">
        <div class="max-w-7xl mx-auto px-4 py-8 text-center">
            <p class="font-display font-bold text-muted-foreground">Made with 💛 by E-Huddle · 2026</p>
        </div>
    </footer>
</div>
<?php
renderLayoutEnd();
