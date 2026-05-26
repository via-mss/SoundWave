<?php
// login.php
$pageTitle = 'Log In';
require_once 'includes/config.php';
if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $identity = trim($_POST['identity'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$identity || !$password) {
            $error = 'Please enter your username/email and password.';
        } else {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$identity, $identity]);
            $user = $stmt->fetch();

            $isPasswordValid = false;
            if ($user) {
                if (password_verify($password, $user['password_hash'])) {
                    $isPasswordValid = true;
                } elseif (!str_starts_with($user['password_hash'], '$2y$') && $password === $user['password_hash']) {
                    // Legacy plain-text password entry: accept it once and upgrade to a hash.
                    $isPasswordValid = true;
                }
            }

            if ($user && $isPasswordValid) {
                if ($user['account_status'] !== 'active') {
                    $error = $user['account_status'] === 'banned'
                        ? 'Your account has been banned. Contact support for help.'
                        : 'Your account is suspended. Contact support for help.';
                } else {
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['role']      = $user['role'];

                    // Rehash or upgrade plain-text legacy passwords
                    if (!str_starts_with($user['password_hash'], '$2y$') || password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
                        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, $user['id']]);
                    }
                    redirect(SITE_URL . '/index.php');
                }
            } else {
                $error = 'Incorrect username/email or password.';
            }
        }
    }
}
require_once 'includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-sub">Log in to your SoundWave account.</p>

        <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
        <div class="alert-sw-success">Account created! Please log in.</div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="mb-3">
                <label class="sw-label" for="identity">Username or Email</label>
                <input class="form-control sw-input" type="text" id="identity" name="identity" required autofocus
                    value="<?= sanitize($_POST['identity'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="sw-label" for="password">Password</label>
                <input class="form-control sw-input" type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-sw-primary mt-1">Log In</button>
            <p class="text-center mt-3 text-muted" style="font-size:0.85rem">
                Don't have an account? <a href="register.php">Sign up for free</a>
            </p>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
