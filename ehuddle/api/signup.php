<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /signup');
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Remove leading @ if present
$username = ltrim($username, '@');

if ($firstName === '' || $lastName === '' || $username === '' || $email === '' || $password === '') {
    ensureSession();
    $_SESSION['signup_error'] = 'Please fill in all fields.';
    header('Location: /signup');
    exit;
}

if (strlen($password) < 6) {
    ensureSession();
    $_SESSION['signup_error'] = 'Password must be at least 6 characters.';
    header('Location: /signup');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ensureSession();
    $_SESSION['signup_error'] = 'Please enter a valid email address.';
    header('Location: /signup');
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    ensureSession();
    $_SESSION['signup_error'] = 'Username must be 3-20 characters (letters, numbers, underscores).';
    header('Location: /signup');
    exit;
}

$result = createUser($username, $email, $password, $firstName, $lastName);

if (is_string($result)) {
    ensureSession();
    $_SESSION['signup_error'] = $result;
    header('Location: /signup');
    exit;
}

loginUser((int)$result['id']);
header('Location: /home');
exit;
