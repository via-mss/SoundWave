<?php
// admin_album.php — admin album detail page
$pageTitle = 'Admin - Album';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}
$pdo = getDB();
$albumName = trim($_GET['name'] ?? '');
if ($albumName === '') {
    redirect('admin_albums.php');
}
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            $pdo->prepare('UPDATE songs SET is_active = 0 WHERE album = ?')->execute([$albumName]);
            redirect('admin_albums.php?deleted=1');
        } elseif ($action === 'rename') {
            $newName = trim($_POST['new_name'] ?? '');
            if ($newName === '') {
                $error = 'New album name cannot be empty.';
            } else {
                $pdo->prepare('UPDATE songs SET album = ? WHERE album = ?')->execute([$newName, $albumName]);
                $success = 'Album renamed successfully.';
                $albumName = $newName;
            }
        }
    }
}
$songs = $pdo->prepare('SELECT s.id, s.title, s.genre, s.duration, u.username AS artist_name FROM songs s JOIN artists a ON s.artist_id = a.id JOIN users u ON a.user_id = u.id WHERE s.album = ? AND s.is_active = 1 ORDER BY s.uploaded_at DESC');
$songs->execute([$albumName]);
$songs = $songs->fetchAll();
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Album Detail</h1>
    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>

    <div class="sw-card mb-4">
        <h2 class="page-title" style="font-size:1.1rem;">Album</h2>
        <p><strong>Name:</strong> <?= sanitize($albumName) ?></p>
        <p><strong>Tracks:</strong> <?= count($songs) ?></p>
        <div class="mt-3">
            <a class="btn btn-outline-sw" href="album.php?name=<?= rawurlencode($albumName) ?>">View public album</a>
            <form method="POST" action="admin_album.php?name=<?= rawurlencode($albumName) ?>" style="display:inline; margin-left:0.5rem;" onsubmit="return confirm('Delete this entire album?');">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-outline-danger" type="submit">Delete Album</button>
            </form>
        </div>
    </div>

    <div class="sw-card mb-4">
        <h2 class="page-title" style="font-size:1.1rem;">Rename Album</h2>
        <form method="POST" action="admin_album.php?name=<?= rawurlencode($albumName) ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="rename">
            <div class="mb-3">
                <label class="sw-label" for="new_name">New album name</label>
                <input class="form-control sw-input" type="text" id="new_name" name="new_name" value="<?= sanitize($albumName) ?>" required>
            </div>
            <button class="btn-sw-primary" type="submit">Rename Album</button>
        </form>
    </div>

    <div class="sw-card">
        <h2 class="page-title" style="font-size:1.1rem;">Album Tracks</h2>
        <?php if (empty($songs)): ?>
            <p class="text-muted">No active tracks found in this album.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-borderless align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Artist</th>
                            <th>Genre</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($songs as $song): ?>
                        <tr>
                            <td><?= sanitize($song['id']) ?></td>
                            <td><?= sanitize($song['title']) ?></td>
                            <td><?= sanitize($song['artist_name']) ?></td>
                            <td><?= sanitize($song['genre']) ?></td>
                            <td><?= sanitize(formatDuration((int)$song['duration'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <a class="btn btn-outline-sw mt-3" href="admin_albums.php">Back to Albums</a>
</div>

<?php require_once 'includes/footer.php'; ?>
