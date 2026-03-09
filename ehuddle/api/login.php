<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login');
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if ($login === '' || $password === '') {
    ensureSession();
    $_SESSION['login_error'] = 'Please fill in all fields.';
    header('Location: /login');
    exit;
}

$user = authenticateUser($login, $password);

if ($user === null) {
    ensureSession();
    $_SESSION['login_error'] = 'Invalid username/email or password.';
    header('Location: /login');
    exit;
}

loginUser((int)$user['id']);
header('Location: /home');
exit;
