<?php
// change_password.php
$pageTitle = 'Change Password';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            $error = 'All fields are required.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif ($current === $new) {
            $error = 'New password must differ from current password.';
        } else {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($current, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([$hash, $_SESSION['user_id']]);
                $success = 'Password changed successfully.';
            }
        }
    }
}
require_once 'includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">
        <h1 class="auth-title">Change Password</h1>
        <p class="auth-sub">Update your SoundWave account password.</p>

        <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>

        <form method="POST" action="change_password.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="mb-3">
                <label class="sw-label" for="current_password">Current Password</label>
                <input class="form-control sw-input" type="password" id="current_password" name="current_password" required>
            </div>
            <div class="mb-3">
                <label class="sw-label" for="new_password">New Password</label>
                <input class="form-control sw-input" type="password" id="password" name="new_password" required minlength="8">
                <div class="pw-strength mt-1"><div class="pw-strength-fill" id="pw-strength-fill" style="width:0%"></div></div>
            </div>
            <div class="mb-3">
                <label class="sw-label" for="confirm_password">Confirm New Password</label>
                <input class="form-control sw-input" type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-sw-primary">Update Password</button>
            <p class="text-center mt-3"><a href="profile.php">← Back to Profile</a></p>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
