<?php
// admin_users.php — admin users view
$pageTitle = 'Admin - Users';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}
$pdo = getDB();
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        if ($targetId <= 0 || $targetId === $user['id']) {
            $error = 'Unable to perform that action.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
            $stmt->execute([$targetId]);
            if (!$stmt->fetch()) {
                $error = 'User not found.';
            } else {
                switch ($action) {
                    case 'delete':
                        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
                        $success = 'User deleted successfully.';
                        break;
                    case 'suspend':
                        $pdo->prepare('UPDATE users SET account_status = ? WHERE id = ?')->execute(['suspended', $targetId]);
                        $success = 'User has been suspended.';
                        break;
                    case 'activate':
                        $pdo->prepare('UPDATE users SET account_status = ? WHERE id = ?')->execute(['active', $targetId]);
                        $success = 'User has been reactivated.';
                        break;
                }
            }
        }
    }
}
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $term = '%' . strtolower($q) . '%';
    $stmt = $pdo->prepare('SELECT id, username, email, display_name, role, account_status, created_at FROM users WHERE LOWER(username) LIKE ? OR LOWER(email) LIKE ? OR LOWER(display_name) LIKE ? OR LOWER(role) LIKE ? OR LOWER(account_status) LIKE ? ORDER BY created_at DESC');
    $stmt->execute([$term, $term, $term, $term, $term]);
    $users = $stmt->fetchAll();
} else {
    $users = $pdo->query('SELECT id, username, email, display_name, role, account_status, created_at FROM users ORDER BY created_at DESC')->fetchAll();
}
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Users</h1>
    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>
    <form class="mb-3" method="GET" action="admin_users.php">
        <div class="input-group">
            <input class="form-control sw-input" type="search" name="q" placeholder="Search users by username, email, display name, role" value="<?= sanitize($q) ?>">
            <button class="btn btn-outline-sw" type="submit">Search</button>
        </div>
    </form>
    <div class="sw-card mb-4">
        <p style="margin-bottom:1rem;color:var(--text-muted);">All registered users on the platform.</p>
        <div class="table-responsive">
            <table class="table table-dark table-borderless align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Display</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= sanitize($u['id']) ?></td>
                        <td><?= sanitize($u['username']) ?></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td><?= sanitize($u['display_name']) ?></td>
                        <td><?= sanitize($u['role']) ?></td>
                        <td><?= sanitize(ucfirst($u['account_status'])) ?></td>
                        <td><?= sanitize($u['created_at']) ?></td>
                        <td>
                            <div class="btn-group" role="group" aria-label="actions">
                                <a class="btn btn-sm btn-outline-sw view-btn" style="min-width:88px;" href="admin_user.php?id=<?= sanitize($u['id']) ?>">View</a>
                                <?php if ($u['account_status'] === 'active'): ?>
                                <form method="POST" action="admin_users.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id" value="<?= sanitize($u['id']) ?>">
                                    <input type="hidden" name="action" value="suspend">
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Suspend</button>
                                </form>
                                <?php elseif ($u['account_status'] === 'suspended' || $u['account_status'] === 'banned'): ?>
                                <form method="POST" action="admin_users.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id" value="<?= sanitize($u['id']) ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" action="admin_users.php" style="display:inline;" onsubmit="return confirm('Delete this user and all related content?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id" value="<?= sanitize($u['id']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <a class="btn btn-outline-sw" href="<?= SITE_URL ?>/admin.php">Back to Admin Panel</a>
</div>

<?php require_once 'includes/footer.php'; ?>
