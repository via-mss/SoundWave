<?php
// admin_playlist.php — admin playlist detail and edit page
$pageTitle = 'Admin - Playlist';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}
$pdo = getDB();
$targetId = (int)($_GET['id'] ?? 0);
if ($targetId <= 0) {
    redirect('admin_playlists.php');
}
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $stmt = $pdo->prepare('SELECT p.*, u.username AS owner_name FROM playlists p JOIN users u ON p.user_id = u.id WHERE p.id = ?');
        $stmt->execute([$targetId]);
        $playlist = $stmt->fetch();
        if (!$playlist) {
            $error = 'Playlist not found.';
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM playlists WHERE id = ?')->execute([$targetId]);
            redirect('admin_playlists.php?deleted=1');
        } elseif ($action === 'save') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            if ($name === '') {
                $error = 'Playlist name cannot be empty.';
            } else {
                $pdo->prepare('UPDATE playlists SET name = ?, description = ?, is_public = ? WHERE id = ?')
                    ->execute([$name, $description, $isPublic, $targetId]);
                $success = 'Playlist updated successfully.';
            }
        }
    }
}
$stmt = $pdo->prepare('SELECT p.*, u.username AS owner_name FROM playlists p JOIN users u ON p.user_id = u.id WHERE p.id = ?');
$stmt->execute([$targetId]);
$playlist = $stmt->fetch();
if (!$playlist) {
    redirect('admin_playlists.php');
}
$songs = $pdo->prepare('SELECT s.id, s.title, s.album, s.genre, s.duration FROM playlist_songs ps JOIN songs s ON ps.song_id = s.id WHERE ps.playlist_id = ? ORDER BY ps.position ASC');
$songs->execute([$targetId]);
$songs = $songs->fetchAll();
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Playlist Details</h1>
    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>

    <div class="row gy-3">
        <div class="col-lg-5">
            <div class="sw-card">
                <h2 class="page-title" style="font-size:1.1rem;">Playlist Info</h2>
                <p><strong>ID:</strong> <?= sanitize($playlist['id']) ?></p>
                <p><strong>Name:</strong> <?= sanitize($playlist['name']) ?></p>
                <p><strong>Owner:</strong> <?= sanitize($playlist['owner_name']) ?></p>
                <p><strong>Public:</strong> <?= $playlist['is_public'] ? 'Yes' : 'No' ?></p>
                <p><strong>Created:</strong> <?= sanitize($playlist['created_at']) ?></p>
                <p><strong>Track count:</strong> <?= count($songs) ?></p>
                <div class="mt-3">
                    <form method="POST" action="admin_playlist.php?id=<?= sanitize($playlist['id']) ?>" onsubmit="return confirm('Delete this playlist?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-outline-danger" type="submit">Delete Playlist</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="sw-card">
                <h2 class="page-title" style="font-size:1.1rem;">Edit Playlist</h2>
                <form method="POST" action="admin_playlist.php?id=<?= sanitize($playlist['id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3">
                        <label class="sw-label" for="name">Playlist Name</label>
                        <input class="form-control sw-input" type="text" id="name" name="name" value="<?= sanitize($playlist['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="description">Description</label>
                        <textarea class="form-control sw-input" id="description" name="description" rows="4"><?= sanitize($playlist['description']) ?></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" <?= $playlist['is_public'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_public">Public playlist</label>
                    </div>
                    <button class="btn-sw-primary" type="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <div class="sw-card mt-3">
        <h2 class="page-title" style="font-size:1.1rem;">Playlist Songs</h2>
        <?php if (empty($songs)): ?>
            <p class="text-muted">No songs currently in this playlist.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-borderless align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Album</th>
                            <th>Genre</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($songs as $song): ?>
                        <tr>
                            <td><?= sanitize($song['id']) ?></td>
                            <td><?= sanitize($song['title']) ?></td>
                            <td><?= sanitize($song['album']) ?></td>
                            <td><?= sanitize($song['genre']) ?></td>
                            <td><?= sanitize(formatDuration((int)$song['duration'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <a class="btn btn-outline-sw mt-3" href="admin_playlists.php">Back to Playlists</a>
</div>

<?php require_once 'includes/footer.php'; ?>
