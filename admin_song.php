<?php
// admin_song.php — admin song detail and edit page
$pageTitle = 'Admin - Song';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}
$pdo = getDB();
$targetId = (int)($_GET['id'] ?? 0);
if ($targetId <= 0) {
    redirect('admin_songs.php');
}
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $stmt = $pdo->prepare('SELECT s.*, a.artist_name FROM songs s JOIN artists a ON s.artist_id = a.id WHERE s.id = ?');
        $stmt->execute([$targetId]);
        $song = $stmt->fetch();
        if (!$song) {
            $error = 'Song not found.';
        } elseif ($action === 'delete') {
            $pdo->prepare('UPDATE songs SET is_active = 0 WHERE id = ?')->execute([$targetId]);
            redirect('admin_songs.php?deleted=1');
        } elseif ($action === 'save') {
            $title = trim($_POST['title'] ?? '');
            $album = trim($_POST['album'] ?? '');
            $genre = trim($_POST['genre'] ?? '');
            $duration = (int)($_POST['duration'] ?? 0);
            if ($title === '') {
                $error = 'Song title cannot be empty.';
            } elseif ($duration <= 0) {
                $error = 'Duration must be greater than 0 seconds.';
            } else {
                $pdo->prepare('UPDATE songs SET title = ?, album = ?, genre = ?, duration = ? WHERE id = ?')
                    ->execute([$title, $album, $genre, $duration, $targetId]);
                $success = 'Song updated successfully.';
            }
        }
    }
}
$stmt = $pdo->prepare('SELECT s.*, a.artist_name FROM songs s JOIN artists a ON s.artist_id = a.id WHERE s.id = ?');
$stmt->execute([$targetId]);
$song = $stmt->fetch();
if (!$song) {
    redirect('admin_songs.php');
}
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Song Details</h1>
    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>

    <div class="row gy-3">
        <div class="col-lg-5">
            <div class="sw-card">
                <h2 class="page-title" style="font-size:1.1rem;">Song Information</h2>
                <p><strong>ID:</strong> <?= sanitize($song['id']) ?></p>
                <p><strong>Title:</strong> <?= sanitize($song['title']) ?></p>
                <p><strong>Album:</strong> <?= sanitize($song['album']) ?></p>
                <p><strong>Genre:</strong> <?= sanitize($song['genre']) ?></p>
                <p><strong>Artist:</strong> <?= sanitize($song['artist_name']) ?></p>
                <p><strong>Duration:</strong> <?= sanitize(formatDuration((int)$song['duration'])) ?></p>
                <p><strong>Uploaded:</strong> <?= sanitize($song['uploaded_at']) ?></p>
                <div class="mt-3">
                    <form method="POST" action="admin_song.php?id=<?= sanitize($song['id']) ?>" onsubmit="return confirm('Remove this song from the library?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-outline-danger" type="submit">Delete Song</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="sw-card">
                <h2 class="page-title" style="font-size:1.1rem;">Edit Song</h2>
                <form method="POST" action="admin_song.php?id=<?= sanitize($song['id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3">
                        <label class="sw-label" for="title">Title</label>
                        <input class="form-control sw-input" type="text" id="title" name="title" value="<?= sanitize($song['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="album">Album</label>
                        <input class="form-control sw-input" type="text" id="album" name="album" value="<?= sanitize($song['album']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="genre">Genre</label>
                        <input class="form-control sw-input" type="text" id="genre" name="genre" value="<?= sanitize($song['genre']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="duration">Duration (seconds)</label>
                        <input class="form-control sw-input" type="number" id="duration" name="duration" value="<?= sanitize($song['duration']) ?>" min="1" required>
                    </div>
                    <button class="btn-sw-primary" type="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <a class="btn btn-outline-sw mt-3" href="admin_songs.php">Back to Songs</a>
</div>

<?php require_once 'includes/footer.php'; ?>
