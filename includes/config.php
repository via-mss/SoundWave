<?php
// includes/config.php
// SoundWave - Database Configuration

define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Change to your DB username
define('DB_PASS', '');             // Change to your DB password
define('DB_NAME', 'soundwave');

define('SITE_NAME', 'SoundWave');
define('SITE_URL', 'http://localhost/soundwave');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('COVER_DIR', 'uploads/covers/');
define('SONG_DIR',  'uploads/songs/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600,
        'path'     => '/',
        'secure'   => false, // Set true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Connect to database
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            ensureUserSchema($pdo);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }
    return $pdo;
}

function ensureUserSchema(PDO $pdo): void {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'account_status'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN account_status ENUM('active','suspended','banned') DEFAULT 'active' AFTER role");
        }
    } catch (PDOException $e) {
        // Ignore schema updates on unsupported platforms or limited permissions.
    }
}

// Sanitize input
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// CSRF token generation and validation
function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRF(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Redirect helper
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// Check if user is logged in
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Get current user
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// Format duration seconds to m:ss
function formatDuration(int $seconds): string {
    return floor($seconds / 60) . ':' . str_pad($seconds % 60, 2, '0', STR_PAD_LEFT);
}
