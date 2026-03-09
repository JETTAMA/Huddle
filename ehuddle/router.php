<?php

declare(strict_types=1);

$publicPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $publicPath;

if ($publicPath !== '/' && is_file($file)) {
    return false;
}

$routes = [
    '/' => __DIR__ . '/index.php',
    '/login' => __DIR__ . '/login/index.php',
    '/signup' => __DIR__ . '/signup/index.php',
    '/home' => __DIR__ . '/home/index.php',
    '/profile' => __DIR__ . '/profile/index.php',
    '/user' => __DIR__ . '/user/index.php',
    '/search' => __DIR__ . '/search/index.php',
    '/api/login' => __DIR__ . '/api/login.php',
    '/api/signup' => __DIR__ . '/api/signup.php',
    '/api/logout' => __DIR__ . '/api/logout.php',
    '/api/posts' => __DIR__ . '/api/posts.php',
    '/api/likes' => __DIR__ . '/api/likes.php',
    '/api/comments' => __DIR__ . '/api/comments.php',
    '/api/follow' => __DIR__ . '/api/follow.php',
    '/api/bookmark' => __DIR__ . '/api/bookmark.php',
    '/api/search' => __DIR__ . '/api/search.php',
    '/api/profile' => __DIR__ . '/api/profile.php',
    '/api/upload' => __DIR__ . '/api/upload.php',
    '/api/insights' => __DIR__ . '/api/insights.php',
];

if (isset($routes[$publicPath])) {
    require $routes[$publicPath];
    exit;
}

require __DIR__ . '/404.php';
