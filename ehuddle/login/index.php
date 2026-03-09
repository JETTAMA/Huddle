<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/components.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /home');
    exit;
}

ensureSession();
$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

renderLayoutStart('E-Huddle | Login');
?>
<div class="min-h-screen bg-background halftone-bg flex items-center justify-center p-4">
    <div class="w-full max-w-md bounce-in">
        <div class="text-center mb-6">
            <a href="/" class="inline-flex items-center gap-2">
                <?php renderLogo('bg-accent', 'text-foreground', 'w-14 h-14 text-2xl', 'wobble-loop'); ?>
            </a>
            <h1 class="font-display text-4xl font-extrabold mt-4">Welcome Back! 👋</h1>
            <p class="font-body text-muted-foreground mt-1">Log in to rejoin the huddle</p>
        </div>

        <div class="cartoon-card p-8">
            <?php if ($loginError): ?>
                <div class="mb-4 p-3 rounded-xl bg-cartoon-red/10 border-[2px] border-cartoon-red text-cartoon-red font-body font-bold text-sm">
                    <?= h($loginError) ?>
                </div>
            <?php endif; ?>

            <form class="flex flex-col gap-5" action="/api/login" method="post">
                <div>
                    <label class="font-display font-bold text-sm mb-1.5 block">Email or Username</label>
                    <input type="text" name="login" placeholder="your@email.com" class="cartoon-input w-full" required>
                </div>
                <div>
                    <label class="font-display font-bold text-sm mb-1.5 block">Password</label>
                    <div class="relative">
                        <input id="login-pass" type="password" name="password" placeholder="Super secret..." class="cartoon-input w-full pr-12" required>
                        <button type="button" data-password-target="login-pass" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                            <i class="fa-regular fa-eye w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="cartoon-btn bg-primary text-primary-foreground w-full text-center">Let's Go! 🚀</button>
            </form>

            <div class="mt-6 text-center">
                <p class="font-body text-sm text-muted-foreground">
                    Don't have an account?
                    <a href="/signup" class="font-bold text-cartoon-pink hover:underline">Sign Up!</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php
renderLayoutEnd();
