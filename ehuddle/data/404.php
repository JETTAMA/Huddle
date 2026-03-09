<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

http_response_code(404);
renderLayoutStart('404 | E-Huddle');
?>
<div class="flex min-h-screen items-center justify-center bg-muted">
    <div class="text-center">
        <h1 class="mb-4 text-4xl font-bold">404</h1>
        <p class="mb-4 text-xl text-muted-foreground">Oops! Page not found</p>
        <a href="/" class="text-primary underline hover:text-primary/90">Return to Home</a>
    </div>
</div>
<?php
renderLayoutEnd();
