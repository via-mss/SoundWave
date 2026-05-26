<?php
// admin_albums.php — admin albums view
$pageTitle = 'Admin - Albums';
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
        $albumName = trim($_POST['album'] ?? '');
        if ($albumName === '') {
            $error = 'Album name is required.';
        } else {
            $pdo->prepare('UPDATE songs SET is_active = 0 WHERE album = ?')->execute([$albumName]);
            $success = 'Album deleted successfully.';
        }
    }
}
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $term = '%' . strtolower($q) . '%';
    $stmt = $pdo->prepare("SELECT album, COUNT(*) AS track_count FROM songs WHERE album <> '' AND is_active = 1 AND LOWER(album) LIKE ? GROUP BY album ORDER BY MIN(uploaded_at) DESC");
    $stmt->execute([$term]);
    $albums = $stmt->fetchAll();
} else {
    $albums = $pdo->query("SELECT album, COUNT(*) AS track_count FROM songs WHERE album <> '' AND is_active = 1 GROUP BY album ORDER BY MIN(uploaded_at) DESC")->fetchAll();
}
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Albums</h1>
    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>
    <form class="mb-3" method="GET" action="admin_albums.php">
        <div class="input-group">
            <input class="form-control sw-input" type="search" name="q" placeholder="Search albums by name" value="<?= sanitize($q) ?>">
            <button class="btn btn-outline-sw" type="submit">Search</button>
        </div>
    </form>
    <div class="sw-card mb-4">
        <p style="margin-bottom:1rem;color:var(--text-muted);">All active albums on the platform.</p>
        <div class="table-responsive">
            <table class="table table-dark table-borderless align-middle mb-0">
                <thead>
                    <tr>
                        <th>Album</th>
                        <th>Tracks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($albums as $album): ?>
                    <tr>
                        <td><?= sanitize($album['album']) ?></td>
                        <td><?= sanitize($album['track_count']) ?></td>
                        <td>
                            <div class="btn-group" role="group" aria-label="actions">
                                <a class="btn btn-sm btn-outline-sw view-btn" style="min-width:88px;" href="admin_album.php?name=<?= rawurlencode($album['album']) ?>">View</a>
                                <form method="POST" action="admin_albums.php" style="display:inline;" onsubmit="return confirm('Delete this entire album?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="album" value="<?= sanitize($album['album']) ?>">
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
