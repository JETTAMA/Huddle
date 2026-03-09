<?php

declare(strict_types=1);

/**
 * Get the singleton SQLite3 database connection.
 * Creates the database file and directory if they don't exist.
 * Initializes schema and runs migrations on first call.
 *
 * @return SQLite3 The database connection instance.
 */
function getDb(): SQLite3
{
    static $db = null;
    if ($db !== null) {
        return $db;
    }

    $dbPath = __DIR__ . '/../data/ehuddle.db';
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA foreign_keys = ON');

    initSchema($db);

    return $db;
}

/**
 * Create all tables if they don't exist, seed initial data, and run migrations.
 *
 * @param SQLite3 $db The database connection.
 * @return void
 */
function initSchema(SQLite3 $db): void
{
    $db->exec('
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE COLLATE NOCASE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            first_name TEXT NOT NULL DEFAULT "",
            last_name TEXT NOT NULL DEFAULT "",
            bio TEXT NOT NULL DEFAULT "",
            location TEXT NOT NULL DEFAULT "",
            website TEXT NOT NULL DEFAULT "",
            avatar_color TEXT NOT NULL DEFAULT "bg-cartoon-blue",
            avatar_letter TEXT NOT NULL DEFAULT "?",
            avatar_url TEXT DEFAULT NULL,
            banner_url TEXT DEFAULT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    $db->exec('
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            image_url TEXT DEFAULT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_private INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ');

    // Simple migration for existing DB
    @$db->exec('ALTER TABLE posts ADD COLUMN is_private INTEGER NOT NULL DEFAULT 0');

    $db->exec('
        CREATE TABLE IF NOT EXISTS likes (
            user_id INTEGER NOT NULL,
            post_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, post_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
    ');

    $db->exec('
        CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ');

    $db->exec('
        CREATE TABLE IF NOT EXISTS follows (
            follower_id INTEGER NOT NULL,
            following_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (follower_id, following_id),
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ');

    $db->exec('
        CREATE TABLE IF NOT EXISTS bookmarks (
            user_id INTEGER NOT NULL,
            post_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, post_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
    ');

    $db->exec('
        CREATE TABLE IF NOT EXISTS flags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            flag_name TEXT NOT NULL,
            flag_value TEXT NOT NULL
        )
    ');

    // Seed data if users table is empty
    $result = $db->querySingle('SELECT COUNT(*) FROM users');
    if ((int)$result === 0) {
        seedData($db);
    }

    // Add avatar_url and banner_url columns if not present (migration for existing DBs)
    $existingCols = [];
    $colInfo = $db->query('PRAGMA table_info(users)');
    while ($row = $colInfo->fetchArray(SQLITE3_ASSOC)) {
        $existingCols[] = $row['name'];
    }
    if (!in_array('avatar_url', $existingCols)) {
        $db->exec('ALTER TABLE users ADD COLUMN avatar_url TEXT DEFAULT NULL');
    }
    if (!in_array('banner_url', $existingCols)) {
        $db->exec('ALTER TABLE users ADD COLUMN banner_url TEXT DEFAULT NULL');
    }

    // Add is_private column to posts if not present
    $postCols = [];
    $postColInfo = $db->query('PRAGMA table_info(posts)');
    while ($row = $postColInfo->fetchArray(SQLITE3_ASSOC)) {
        $postCols[] = $row['name'];
    }
    if (!in_array('is_private', $postCols)) {
        $db->exec('ALTER TABLE posts ADD COLUMN is_private INTEGER NOT NULL DEFAULT 0');
    }
}

/**
 * Seed the database with initial sample users, posts, likes, comments, and follows.
 * Only called once when the users table is empty.
 *
 * @param SQLite3 $db The database connection.
 * @return void
 */
function seedData(SQLite3 $db): void
{
    $password = 'password123';

    $users = [
        // [username, email, first, last, bio, location, website, avatar_color, avatar_letter, avatar_url, banner_url]
        ['ZeroDayZara', 'zara@ehuddle.test', 'Zara', 'Khan', 'Pentester by day, bug bounty hunter by night 🐛💰 Yes, your password is "password123"', 'Kali Linux VM', 'zarakhan.sec', 'bg-cartoon-orange', 'Z', '/uploads/avatars/zerodayzara.png', '/uploads/banners/zerodayzara.png'],
        ['SQLiSam', 'sam@ehuddle.test', 'Sam', 'Rivers', 'Red teamer who drops tables, not beats 💉 OR 1=1 --', 'Behind Your Firewall', 'samrivers.hack', 'bg-cartoon-green', 'S', '/uploads/avatars/sqlisam.png', '/uploads/banners/sqlisam.png'],
        ['PhishPharm', 'phish@ehuddle.test', 'Priya', 'Sharma', 'Social engineer & phishing connoisseur 🎣 Your CEO just clicked my link btw', 'Your Inbox', 'phishpharm.io', 'bg-cartoon-purple', 'P', '/uploads/avatars/phishpharm.png', '/uploads/banners/phishpharm.png'],
        ['CVECollector', 'cve@ehuddle.test', 'Carlos', 'Vega', 'I collect CVEs like others collect Pokémon 🔥 Currently sitting on 3 unpatched zero-days, no big deal', 'The Dark Web (jk... unless?)', 'cvecollector.blog', 'bg-cartoon-pink', 'C', '/uploads/avatars/cvecollector.png', '/uploads/banners/cvecollector.png'],
        ['SudoSudo', 'sudo@ehuddle.test', 'Alex', 'Chen', 'SOC analyst. Yes, I read every single alert. No, I don\'t sleep 🤖☕', 'The NOC at 3 AM', 'sudosudo.dev', 'bg-cartoon-red', 'A', '/uploads/avatars/sudosudo.png', '/uploads/banners/sudosudo.png'],
        ['HashCracker', 'hash@ehuddle.test', 'Morgan', 'Blake', 'Crypto nerd (the ACTUAL cryptography kind, not your shitcoins) 🔐', 'Rainbow Table', 'hashcracker.net', 'bg-cartoon-blue', 'H', '/uploads/avatars/hashcracker.png', '/uploads/banners/hashcracker.png'],
        ['FirewallFiona', 'fiona@ehuddle.test', 'Fiona', 'Müller', 'CISO who has seen things. Terrible, unpatched things 🏆 Still waiting for that security budget', 'Behind 7 Proxies', 'firewallfiona.sec', 'bg-cartoon-green', 'F', '/uploads/avatars/firewallfiona.png', '/uploads/banners/firewallfiona.png'],
        ['testuser', 'test@mcsc.local', 'Test', 'User', 'Just here for the flags.', 'The Test Suite', 'mcsc.local', 'bg-cartoon-blue', 'T', null, null],
    ];

    $stmt = $db->prepare('INSERT INTO users (username, email, password, first_name, last_name, bio, location, website, avatar_color, avatar_letter, avatar_url, banner_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $testPassword = 'test@mcsc.local';
    foreach ($users as $u) {
        $stmt->bindValue(1, $u[0], SQLITE3_TEXT);
        $stmt->bindValue(2, $u[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $u[1] === 'test@mcsc.local' ? $testPassword : $password, SQLITE3_TEXT);
        $stmt->bindValue(4, $u[2], SQLITE3_TEXT);
        $stmt->bindValue(5, $u[3], SQLITE3_TEXT);
        $stmt->bindValue(6, $u[4], SQLITE3_TEXT);
        $stmt->bindValue(7, $u[5], SQLITE3_TEXT);
        $stmt->bindValue(8, $u[6], SQLITE3_TEXT);
        $stmt->bindValue(9, $u[7], SQLITE3_TEXT);
        $stmt->bindValue(10, $u[8], SQLITE3_TEXT);
        $stmt->bindValue(11, $u[9], $u[9] !== null ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(12, $u[10], $u[10] !== null ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->execute();
        $stmt->reset();
    }

    $db->exec('INSERT INTO flags (flag_name, flag_value) VALUES ("user_flag", "You found the secret table! But this is not the real flag.")');

    $posts = [
        [1, 'Just finished a pentest and found the admin password was literally "admin123" 🔑✨ I don\'t even know why companies pay me at this point. The real vulnerability is human confidence.', '/uploads/posts/admin_password_meme.png'],
        [2, 'Who else remembers when SQL injection was considered "advanced hacking"? Those were the golden days! 📺 Now every script kiddie with a Kali ISO thinks they\'re Mr. Robot. Back in MY day, we had to craft our own payloads UPHILL BOTH WAYS.', null],
        [3, 'New phishing template dropped! 🎣 Combining urgent CEO requests with fake invoice attachments. The click rate hits different when you add "URGENT: Action Required" to the subject line. 78% open rate btw 🏆', '/uploads/posts/phishing_email_meme.png'],
        [4, 'Hot take: the best era for vulnerabilities was pre-2010 and I will die on this hill 🏔️🔥 Buffer overflows everywhere, no ASLR, no DEP... it was basically a theme park for hackers', null],
        [5, 'Day 347 of being a SOC analyst. Got 14,000 alerts today. 13,998 were false positives. The other 2 were also false positives but they looked scary enough to escalate ☕🤖 This is fine. Everything is fine.', null],
        [6, 'Friendly reminder that MD5 is NOT a password hashing algorithm. It\'s barely a hashing algorithm at all at this point 🔐 If your app still uses MD5, your app doesn\'t have a security team. It has a thoughts-and-prayers team.', null],
        [7, 'Just told the board we need a bigger security budget. They said "but we haven\'t been hacked yet!" I said "that you KNOW of." Meeting ended early 🏆💀', '/uploads/posts/firewall_bypass_meme.png'],
        [1, 'PSA: "We take security very seriously" is corporate for "we got breached last Tuesday and the PR team is working overtime" 📢🔥', null],
        [4, 'Just found a critical RCE with a CVSS score of 9.8. Vendor response: "That\'s a feature, not a bug." Ah yes, the classic remote code FEATURE. My bad. 🙃', null],
        [2, 'SELECT * FROM companies WHERE security_budget > 0 AND CEO_reuses_password = false; -- 0 rows returned 💉😂', null],
        [8, 'This post is private lol. You shouldn\'t be able to see this unless you are me!', null],
        ];

        $stmt = $db->prepare('INSERT INTO posts (user_id, content, image_url, created_at, is_private) VALUES (?, ?, ?, datetime("now", ?), ?)');
        $offsets = ['-1 hours', '-2 hours', '-3 hours', '-5 hours', '-7 hours', '-9 hours', '-12 hours', '-18 hours', '-24 hours', '-30 hours', '-32 hours', '-36 hours'];
        foreach ($posts as $i => $p) {
        $stmt->bindValue(1, $p[0], SQLITE3_INTEGER);
        $stmt->bindValue(2, $p[1], SQLITE3_TEXT);
        if ($p[2] === null) {
            $stmt->bindValue(3, null, SQLITE3_NULL);
        } else {
            $stmt->bindValue(3, $p[2], SQLITE3_TEXT);
        }
        $stmt->bindValue(4, $offsets[$i], SQLITE3_TEXT);
        $stmt->bindValue(5, $p[1] === 'This post is private lol. You shouldn\'t be able to see this unless you are me!' ? 1 : 0, SQLITE3_INTEGER);
        $stmt->execute();
        $stmt->reset();
        }

    // Seed some likes (spread across all 10 posts)
    $likes = [
        [2, 1], [3, 1], [4, 1], [5, 1], [6, 1], [7, 1],
        [1, 2], [3, 2], [4, 2], [5, 2],
        [1, 3], [2, 3], [4, 3], [6, 3], [7, 3],
        [1, 4], [2, 4], [3, 4], [5, 4], [6, 4],
        [1, 5], [2, 5], [3, 5], [4, 5], [6, 5], [7, 5],
        [1, 6], [3, 6], [5, 6], [7, 6],
        [1, 7], [2, 7], [3, 7], [4, 7], [5, 7], [6, 7],
        [2, 8], [4, 8], [5, 8], [7, 8],
        [1, 9], [2, 9], [3, 9], [6, 9], [7, 9],
        [1, 10], [3, 10], [4, 10], [5, 10], [6, 10], [7, 10],
    ];
    $stmt = $db->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)');
    foreach ($likes as $l) {
        $stmt->bindValue(1, $l[0], SQLITE3_INTEGER);
        $stmt->bindValue(2, $l[1], SQLITE3_INTEGER);
        $stmt->execute();
        $stmt->reset();
    }

    // Seed some comments
    $comments = [
        [1, 2, 'Bruh, I literally just bypassed their WAF with a URL-encoded single quote 💀'],
        [1, 3, 'Love the social engineering energy here! Chef\'s kiss 🤌'],
        [2, 1, 'admin123?? That\'s generous. Last client had no password at all 🫠'],
        [3, 4, 'Spitting facts! No mitigations, no EDR, just vibes and stack smashing.'],
        [4, 1, 'The real CVE was the friends we made along the way 😂'],
        [5, 7, 'I felt this in my soul. My budget pitch got denied for the 4th quarter in a row 💸'],
        [5, 6, 'I once found an app hashing passwords with ROT13. TWICE. For extra security, they said. 😭'],
        [7, 5, 'Honestly the false positives keep me employed so I can\'t complain too much 🤷'],
        [3, 2, 'Mr. Robot ruined hacking for all of us. Now clients expect dramatic hoodie-wearing.'],
        [6, 3, 'Vendor: "It\'s a feature!" Me: *reports to MITRE anyway* 🗡️'],
        [1, 1, 'SQL injection jokes never get old. Unlike their unpatched databases.'],
        [7, 8, 'Just once I want a company to say "We take security somewhat seriously, we\'re working on it" 😂'],
    ];
    $stmt = $db->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
    foreach ($comments as $c) {
        $stmt->bindValue(1, $c[0], SQLITE3_INTEGER);
        $stmt->bindValue(2, $c[1], SQLITE3_INTEGER);
        $stmt->bindValue(3, $c[2], SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
    }

    // Seed some follows
    $followPairs = [[1, 2], [1, 3], [2, 1], [2, 4], [3, 1], [3, 2], [4, 1], [4, 3]];
    $stmt = $db->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)');
    foreach ($followPairs as $f) {
        $stmt->bindValue(1, $f[0], SQLITE3_INTEGER);
        $stmt->bindValue(2, $f[1], SQLITE3_INTEGER);
        $stmt->execute();
        $stmt->reset();
    }
}
