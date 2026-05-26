<?php
// admin_playlists.php — admin playlists view
$pageTitle = 'Admin - Playlists';
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
        $targetId = (int)($_POST['playlist_id'] ?? 0);
        if ($targetId <= 0) {
            $error = 'Invalid playlist ID.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM playlists WHERE id = ?');
            $stmt->execute([$targetId]);
            if (!$stmt->fetch()) {
                $error = 'Playlist not found.';
            } else {
                $pdo->prepare('DELETE FROM playlists WHERE id = ?')->execute([$targetId]);
                $success = 'Playlist deleted successfully.';
            }
        }
    }
}
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $term = '%' . strtolower($q) . '%';
    $stmt = $pdo->prepare('SELECT p.id, p.name, p.description, p.is_public, u.username AS owner, COUNT(ps.song_id) AS song_count FROM playlists p LEFT JOIN users u ON p.user_id = u.id LEFT JOIN playlist_songs ps ON ps.playlist_id = p.id WHERE LOWER(p.name) LIKE ? OR LOWER(p.description) LIKE ? OR LOWER(u.username) LIKE ? GROUP BY p.id ORDER BY p.created_at DESC');
    $stmt->execute([$term, $term, $term]);
    $playlists = $stmt->fetchAll();
} else {
    $playlists = $pdo->query('SELECT p.id, p.name, p.description, p.is_public, u.username AS owner, COUNT(ps.song_id) AS song_count FROM playlists p LEFT JOIN users u ON p.user_id = u.id LEFT JOIN playlist_songs ps ON ps.playlist_id = p.id GROUP BY p.id ORDER BY p.created_at DESC')->fetchAll();
}
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Playlists</h1>
    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>
    <form class="mb-3" method="GET" action="admin_playlists.php">
        <div class="input-group">
            <input class="form-control sw-input" type="search" name="q" placeholder="Search playlists by name, description, owner" value="<?= sanitize($q) ?>">
            <button class="btn btn-outline-sw" type="submit">Search</button>
        </div>
    </form>
    <div class="sw-card mb-4">
        <p style="margin-bottom:1rem;color:var(--text-muted);">All playlists on the system.</p>
        <div class="table-responsive">
            <table class="table table-dark table-borderless align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Owner</th>
                        <th>Public</th>
                        <th>Songs</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($playlists as $pl): ?>
                    <tr>
                        <td><?= sanitize($pl['id']) ?></td>
                        <td><?= sanitize($pl['name']) ?></td>
                        <td><?= sanitize($pl['owner']) ?></td>
                        <td><?= $pl['is_public'] ? 'Yes' : 'No' ?></td>
                        <td><?= sanitize($pl['song_count']) ?></td>
                        <td>
                            <div class="btn-group" role="group" aria-label="actions">
                                <a class="btn btn-sm btn-outline-sw view-btn" style="min-width:88px;" href="admin_playlist.php?id=<?= sanitize($pl['id']) ?>">View</a>
                                <form method="POST" action="admin_playlists.php" style="display:inline;" onsubmit="return confirm('Delete this playlist?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="playlist_id" value="<?= sanitize($pl['id']) ?>">
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
