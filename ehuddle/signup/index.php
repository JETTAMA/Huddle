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
$signupError = $_SESSION['signup_error'] ?? '';
unset($_SESSION['signup_error']);

renderLayoutStart('E-Huddle | Signup');
?>
<div class="min-h-screen bg-background halftone-bg flex items-center justify-center p-4">
    <div class="w-full max-w-md bounce-in">
        <div class="text-center mb-6">
            <a href="/" class="inline-flex items-center gap-2">
                <?php renderLogo('bg-cartoon-pink', 'text-primary-foreground', 'w-14 h-14 text-2xl', 'wobble-loop'); ?>
            </a>
            <h1 class="font-display text-4xl font-extrabold mt-4">Join the Huddle! 🎉</h1>
            <p class="font-body text-muted-foreground mt-1">Create your account and start vibing</p>
        </div>

        <div class="cartoon-card p-8">
            <?php if ($signupError): ?>
                <div class="mb-4 p-3 rounded-xl bg-cartoon-red/10 border-[2px] border-cartoon-red text-cartoon-red font-body font-bold text-sm">
                    <?= h($signupError) ?>
                </div>
            <?php endif; ?>

            <form class="flex flex-col gap-5" action="/api/signup" method="post">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="font-display font-bold text-sm mb-1.5 block">First Name</label>
                        <input type="text" name="first_name" placeholder="Retro" class="cartoon-input w-full" required>
                    </div>
                    <div>
                        <label class="font-display font-bold text-sm mb-1.5 block">Last Name</label>
                        <input type="text" name="last_name" placeholder="Kid" class="cartoon-input w-full" required>
                    </div>
                </div>
                <div>
                    <label class="font-display font-bold text-sm mb-1.5 block">Username</label>
                    <input type="text" name="username" placeholder="@coolhuman" class="cartoon-input w-full" required>
                </div>
                <div>
                    <label class="font-display font-bold text-sm mb-1.5 block">Email</label>
                    <input type="email" name="email" placeholder="your@email.com" class="cartoon-input w-full" required>
                </div>
                <div>
                    <label class="font-display font-bold text-sm mb-1.5 block">Password</label>
                    <div class="relative">
                        <input id="signup-pass" type="password" name="password" placeholder="Make it strong!" class="cartoon-input w-full pr-12" required minlength="6">
                        <button type="button" data-password-target="signup-pass" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                            <i class="fa-regular fa-eye w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="cartoon-btn bg-cartoon-pink text-primary-foreground w-full text-center">Create Account 🚀</button>
            </form>

            <div class="mt-6 text-center">
                <p class="font-body text-sm text-muted-foreground">
                    Already have an account?
                    <a href="/login" class="font-bold text-primary hover:underline">Log In!</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php
renderLayoutEnd();
