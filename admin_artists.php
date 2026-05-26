<?php
// admin_artists.php — admin artists view
$pageTitle = 'Admin - Artists';
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
        $targetId = (int)($_POST['artist_id'] ?? 0);
        if ($targetId <= 0) {
            $error = 'Invalid artist ID.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM artists WHERE id = ?');
            $stmt->execute([$targetId]);
            if (!$stmt->fetch()) {
                $error = 'Artist not found.';
            } else {
                $pdo->prepare('DELETE FROM artists WHERE id = ?')->execute([$targetId]);
                $success = 'Artist removed successfully.';
            }
        }
    }
}
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $term = '%' . strtolower($q) . '%';
    $stmt = $pdo->prepare('SELECT a.id, a.artist_name, a.genre, u.username AS owner, u.email FROM artists a JOIN users u ON a.user_id = u.id WHERE LOWER(a.artist_name) LIKE ? OR LOWER(a.genre) LIKE ? OR LOWER(u.username) LIKE ? OR LOWER(u.email) LIKE ? ORDER BY a.id DESC');
    $stmt->execute([$term, $term, $term, $term]);
    $artists = $stmt->fetchAll();
} else {
    $artists = $pdo->query('SELECT a.id, a.artist_name, a.genre, u.username AS owner, u.email FROM artists a JOIN users u ON a.user_id = u.id ORDER BY a.id DESC')->fetchAll();
}
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Artists</h1>
    <form class="mb-3" method="GET" action="admin_artists.php">
        <div class="input-group">
            <input class="form-control sw-input" type="search" name="q" placeholder="Search artists by name, genre, owner, email" value="<?= sanitize($q) ?>">
            <button class="btn btn-outline-sw" type="submit">Search</button>
        </div>
    </form>
    <div class="sw-card mb-4">
        <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>
        <p style="margin-bottom:1rem;color:var(--text-muted);">All artist profiles and their owners.</p>
        <div class="table-responsive">
            <table class="table table-dark table-borderless align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Genre</th>
                        <th>Owner</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artists as $a): ?>
                    <tr>
                        <td><?= sanitize($a['id']) ?></td>
                        <td><?= sanitize($a['artist_name']) ?></td>
                        <td><?= sanitize($a['genre']) ?></td>
                        <td><?= sanitize($a['owner']) ?></td>
                        <td><?= sanitize($a['email']) ?></td>
                        <td>
                            <div class="btn-group" role="group" aria-label="actions">
                                <a class="btn btn-sm btn-outline-sw view-btn" style="min-width:88px;" href="admin_artist.php?id=<?= sanitize($a['id']) ?>">View</a>
                                <form method="POST" action="admin_artists.php" style="display:inline;" onsubmit="return confirm('Delete this artist and all related songs?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="artist_id" value="<?= sanitize($a['id']) ?>">
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
