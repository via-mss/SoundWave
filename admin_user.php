<?php
// admin_user.php — admin user detail and edit page
$pageTitle = 'Admin - User';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}
$pdo = getDB();
$targetId = (int)($_GET['id'] ?? 0);
if ($targetId <= 0) {
    redirect('admin_users.php');
}
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            $error = 'User not found.';
        } elseif ($targetId === $user['id'] && in_array($action, ['delete', 'suspend'], true)) {
            $error = 'You cannot perform that action on your own account.';
        } else {
            if ($action === 'save') {
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $display = trim($_POST['display_name'] ?? '');
                $role = $_POST['role'] ?? 'listener';
                $status = $_POST['account_status'] ?? 'active';
                if (strlen($username) < 3 || strlen($username) > 50) {
                    $error = 'Username must be between 3 and 50 characters.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Email address is invalid.';
                } elseif (!in_array($role, ['listener', 'artist', 'admin'], true)) {
                    $error = 'Invalid role selection.';
                } elseif (!in_array($status, ['active', 'suspended'], true)) {
                    $error = 'Invalid account status.';
                } else {
                    $stmt = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?');
                    $stmt->execute([$username, $email, $targetId]);
                    if ($stmt->fetch()) {
                        $error = 'Username or email is already in use.';
                    } else {
                        $pdo->prepare('UPDATE users SET username = ?, email = ?, display_name = ?, role = ?, account_status = ? WHERE id = ?')
                            ->execute([$username, $email, $display, $role, $status, $targetId]);
                        $success = 'User profile updated successfully.';
                    }
                }
            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
                redirect('admin_users.php?deleted=1');
            } elseif ($action === 'suspend') {
                $pdo->prepare('UPDATE users SET account_status = ? WHERE id = ?')->execute(['suspended', $targetId]);
                $success = 'User has been suspended.';
            } elseif ($action === 'activate') {
                $pdo->prepare('UPDATE users SET account_status = ? WHERE id = ?')->execute(['active', $targetId]);
                $success = 'User has been reactivated.';
            }
        }
    }
}
$stmt = $pdo->prepare('SELECT id, username, email, display_name, role, account_status, created_at, updated_at FROM users WHERE id = ?');
$stmt->execute([$targetId]);
$targetUser = $stmt->fetch();
if (!$targetUser) {
    redirect('admin_users.php');
}
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">User Details</h1>
    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>

    <div class="row gy-3">
        <div class="col-lg-5">
            <div class="sw-card">
                <h2 class="page-title" style="font-size:1.1rem;">Profile</h2>
                <p><strong>ID:</strong> <?= sanitize($targetUser['id']) ?></p>
                <p><strong>Username:</strong> <?= sanitize($targetUser['username']) ?></p>
                <p><strong>Email:</strong> <?= sanitize($targetUser['email']) ?></p>
                <p><strong>Display Name:</strong> <?= sanitize($targetUser['display_name']) ?></p>
                <p><strong>Role:</strong> <?= sanitize($targetUser['role']) ?></p>
                <p><strong>Status:</strong> <?= sanitize(ucfirst($targetUser['account_status'])) ?></p>
                <p><strong>Created:</strong> <?= sanitize($targetUser['created_at']) ?></p>
                <p><strong>Updated:</strong> <?= sanitize($targetUser['updated_at']) ?></p>
                <div class="mt-3">
                    <form method="POST" action="admin_user.php?id=<?= sanitize($targetUser['id']) ?>" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-outline-danger" type="submit" onclick="return confirm('Delete this user and all related content?');">Delete</button>
                    </form>
                    <?php if ($targetUser['account_status'] === 'active'): ?>
                    <form method="POST" action="admin_user.php?id=<?= sanitize($targetUser['id']) ?>" style="display:inline;margin-left:0.5rem;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="suspend">
                        <button class="btn btn-outline-warning" type="submit">Suspend</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="admin_user.php?id=<?= sanitize($targetUser['id']) ?>" style="display:inline;margin-left:0.5rem;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="activate">
                        <button class="btn btn-outline-success" type="submit">Activate</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="sw-card">
                <h2 class="page-title" style="font-size:1.1rem;">Edit User</h2>
                <form method="POST" action="admin_user.php?id=<?= sanitize($targetUser['id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3">
                        <label class="sw-label" for="username">Username</label>
                        <input class="form-control sw-input" type="text" id="username" name="username" value="<?= sanitize($targetUser['username']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="email">Email</label>
                        <input class="form-control sw-input" type="email" id="email" name="email" value="<?= sanitize($targetUser['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="display_name">Display Name</label>
                        <input class="form-control sw-input" type="text" id="display_name" name="display_name" value="<?= sanitize($targetUser['display_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="role">Role</label>
                        <select class="form-select sw-input" id="role" name="role">
                            <?php foreach (['listener', 'artist', 'admin'] as $roleOption): ?>
                                <option value="<?= $roleOption ?>" <?= $targetUser['role'] === $roleOption ? 'selected' : '' ?>><?= ucfirst($roleOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="account_status">Account Status</label>
                        <select class="form-select sw-input" id="account_status" name="account_status">
                            <?php foreach (['active', 'suspended'] as $statusOption): ?>
                                <option value="<?= $statusOption ?>" <?= $targetUser['account_status'] === $statusOption ? 'selected' : '' ?>><?= ucfirst($statusOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn-sw-primary" type="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <a class="btn btn-outline-sw mt-3" href="admin_users.php">Back to Users</a>
</div>

<?php require_once 'includes/footer.php'; ?>
