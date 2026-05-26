<?php
// register.php
$pageTitle = 'Sign Up';
require_once 'includes/config.php';
if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        $role     = in_array($_POST['role'] ?? '', ['listener', 'artist']) ? $_POST['role'] : 'listener';
        $display  = trim($_POST['display_name'] ?? $username);

        // Server-side validation
        if (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username must be 3–50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username may only contain letters, numbers, and underscores.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $pdo = getDB();
            // Check uniqueness
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare(
                    'INSERT INTO users (username, email, password_hash, display_name, role) VALUES (?, ?, ?, ?, ?)'
                )->execute([$username, $email, $hash, $display, $role]);
                $userId = $pdo->lastInsertId();
                // If artist, create artist record
                if ($role === 'artist') {
                    $artistName = trim($_POST['artist_name'] ?? $display);
                    $genre      = trim($_POST['genre'] ?? '');
                    $pdo->prepare(
                        'INSERT INTO artists (user_id, artist_name, genre) VALUES (?, ?, ?)'
                    )->execute([$userId, $artistName, $genre]);
                }
                $success = 'Account created! You can now <a href="login.php">log in</a>.';
            }
        }
    }
}
require_once 'includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title">Create account</h1>
        <p class="auth-sub">Join SoundWave and start listening for free.</p>

        <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert-sw-success"><?= $success ?></div><?php endif; ?>

        <form id="register-form" method="POST" action="register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div id="form-error" class="alert-sw-error" style="display:none;"></div>

            <div class="mb-3">
                <label class="sw-label" for="username">Username</label>
                <input class="form-control sw-input" type="text" id="username" name="username" required
                    pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50"
                    value="<?= sanitize($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="sw-label" for="display_name">Display Name</label>
                <input class="form-control sw-input" type="text" id="display_name" name="display_name"
                    value="<?= sanitize($_POST['display_name'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="sw-label" for="email">Email</label>
                <input class="form-control sw-input" type="email" id="email" name="email" required
                    value="<?= sanitize($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="sw-label" for="password">Password</label>
                <input class="form-control sw-input" type="password" id="password" name="password" required minlength="8">
                <div class="pw-strength mt-1"><div class="pw-strength-fill" id="pw-strength-fill" style="width:0%"></div></div>
                <small class="text-muted" style="font-size:0.75rem">Min. 8 characters, mix of letters, numbers & symbols recommended.</small>
            </div>
            <div class="mb-3">
                <label class="sw-label" for="password_confirm">Confirm Password</label>
                <input class="form-control sw-input" type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <div class="mb-3">
                <label class="sw-label">Account type</label>
                <div class="d-flex gap-3">
                    <label class="d-flex align-items-center gap-2 cursor-pointer">
                        <input type="radio" name="role" value="listener" <?= ($_POST['role'] ?? 'listener') === 'listener' ? 'checked' : '' ?>> Listener
                    </label>
                    <label class="d-flex align-items-center gap-2 cursor-pointer">
                        <input type="radio" name="role" value="artist" <?= ($_POST['role'] ?? '') === 'artist' ? 'checked' : '' ?>> Artist
                    </label>
                </div>
            </div>

            <!-- Artist fields (shown if role = artist) -->
            <div id="artist-fields" style="display:none;">
                <div class="mb-3">
                    <label class="sw-label" for="artist_name">Artist / Band Name</label>
                    <input class="form-control sw-input" type="text" id="artist_name" name="artist_name"
                        value="<?= sanitize($_POST['artist_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="sw-label" for="genre">Genre</label>
                    <input class="form-control sw-input" type="text" id="genre" name="genre" placeholder="e.g. Electronic, Rock…"
                        value="<?= sanitize($_POST['genre'] ?? '') ?>">
                </div>
            </div>

            <button type="submit" class="btn-sw-primary mt-2">Create Account</button>
            <p class="text-center mt-3 text-muted" style="font-size:0.85rem">
                Already have an account? <a href="login.php">Log in</a>
            </p>
        </form>
    </div>
</div>

<script>
// Show artist fields dynamically
document.querySelectorAll('input[name="role"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('artist-fields').style.display =
            r.value === 'artist' ? 'block' : 'none';
    });
    if (r.checked && r.value === 'artist') {
        document.getElementById('artist-fields').style.display = 'block';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
