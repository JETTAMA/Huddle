<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Start a PHP session if one hasn't been started yet.
 *
 * @return void
 */
function ensureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Get the currently authenticated user from the session.
 * Returns the full user row as an associative array, or null if not logged in.
 *
 * @return array<string, mixed>|null User row or null.
 */
function currentUser(): ?array
{
    ensureSession();
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId === null) {
        return null;
    }

    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bindValue(1, (int)$userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    return $user ?: null;
}

/**
 * Check whether a user is currently logged in.
 *
 * @return bool True if a valid user session exists.
 */
function isLoggedIn(): bool
{
    return currentUser() !== null;
}

/**
 * Require an authenticated user. Redirects to /login and exits if not logged in.
 *
 * @return array<string, mixed> The authenticated user row.
 */
function requireAuth(): array
{
    $user = currentUser();
    if ($user === null) {
        header('Location: /login');
        exit;
    }
    return $user;
}

/**
 * Log in a user by storing their ID in the session.
 *
 * @param int $userId The user's database ID.
 * @return void
 */
function loginUser(int $userId): void
{
    ensureSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

/**
 * Log out the current user by destroying the session.
 *
 * @return void
 */
function logoutUser(): void
{
    ensureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Authenticate a user by username/email and password.
 *
 * @param string $login Username or email address.
 * @param string $password Plain-text password to verify.
 * @return array<string, mixed>|null User row on success, null on failure.
 */
function authenticateUser(string $login, string $password): ?array
{
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
    $stmt->bindValue(1, $login, SQLITE3_TEXT);
    $stmt->bindValue(2, $login, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if (!$user || $password !== (string)$user['password']) {
        return null;
    }

    return $user;
}

/**
 * Create a new user account.
 *
 * @param string $username Unique username (no @ prefix).
 * @param string $email Unique email address.
 * @param string $password Plain-text password (will be hashed with bcrypt).
 * @param string $firstName User's first name.
 * @param string $lastName User's last name.
 * @return array<string, mixed>|string User row on success, or error message string on failure.
 */
function createUser(string $username, string $email, string $password, string $firstName, string $lastName): array|string
{
    $db = getDb();

    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? COLLATE BINARY');
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    if ($stmt->execute()->fetchArray()) {
        return 'Username already taken';
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bindValue(1, $email, SQLITE3_TEXT);
    if ($stmt->execute()->fetchArray()) {
        return 'Email already registered';
    }

    $colors = ['bg-cartoon-blue', 'bg-cartoon-orange', 'bg-cartoon-green', 'bg-cartoon-purple', 'bg-cartoon-pink', 'bg-cartoon-red', 'bg-cartoon-yellow'];
    $avatarColor = $colors[array_rand($colors)];
    $avatarLetter = strtoupper(mb_substr($username, 0, 1));

    $stmt = $db->prepare('UPDATE users SET password = ?, email = ? WHERE username = ?');
    $stmt->bindValue(1, $password, SQLITE3_TEXT);
    $stmt->bindValue(2, $email, SQLITE3_TEXT);
    $stmt->bindValue(3, $username, SQLITE3_TEXT);
    $stmt->execute();

    if ($db->changes() > 0) {
        // Return the updated user
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->bindValue(1, $username, SQLITE3_TEXT);
        return $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }

    $stmt = $db->prepare('INSERT INTO users (username, email, password, first_name, last_name, avatar_color, avatar_letter) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    $stmt->bindValue(2, $email, SQLITE3_TEXT);
    $stmt->bindValue(3, $password, SQLITE3_TEXT);
    $stmt->bindValue(4, $firstName, SQLITE3_TEXT);
    $stmt->bindValue(5, $lastName, SQLITE3_TEXT);
    $stmt->bindValue(6, $avatarColor, SQLITE3_TEXT);
    $stmt->bindValue(7, $avatarLetter, SQLITE3_TEXT);
    $stmt->execute();

    $userId = $db->lastInsertRowID();

    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    return $stmt->execute()->fetchArray(SQLITE3_ASSOC);
}
