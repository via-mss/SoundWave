<?php
// admin_songs.php — admin songs view
$pageTitle = 'Admin - Songs';
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
        $targetId = (int)($_POST['song_id'] ?? 0);
        if ($targetId <= 0) {
            $error = 'Invalid song ID.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM songs WHERE id = ? AND is_active = 1');
            $stmt->execute([$targetId]);
            if (!$stmt->fetch()) {
                $error = 'Song not found.';
            } else {
                $pdo->prepare('UPDATE songs SET is_active = 0 WHERE id = ?')->execute([$targetId]);
                $success = 'Song removed successfully.';
            }
        }
    }
}
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $term = '%' . strtolower($q) . '%';
    $stmt = $pdo->prepare('SELECT s.id, s.title, s.album, s.genre, s.duration, u.username AS artist FROM songs s JOIN artists a ON s.artist_id = a.id JOIN users u ON a.user_id = u.id WHERE s.is_active = 1 AND (LOWER(s.title) LIKE ? OR LOWER(s.album) LIKE ? OR LOWER(s.genre) LIKE ? OR LOWER(u.username) LIKE ?) ORDER BY s.uploaded_at DESC');
    $stmt->execute([$term, $term, $term, $term]);
    $songs = $stmt->fetchAll();
} else {
    $songs = $pdo->query('SELECT s.id, s.title, s.album, s.genre, s.duration, u.username AS artist FROM songs s JOIN artists a ON s.artist_id = a.id JOIN users u ON a.user_id = u.id WHERE s.is_active = 1 ORDER BY s.uploaded_at DESC')->fetchAll();
}
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Songs</h1>
    <form class="mb-3" method="GET" action="admin_songs.php">
        <div class="input-group">
            <input class="form-control sw-input" type="search" name="q" placeholder="Search songs by title, album, genre, artist" value="<?= sanitize($q) ?>">
            <button class="btn btn-outline-sw" type="submit">Search</button>
        </div>
    </form>
    <div class="sw-card mb-4">
        <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>
        <p style="margin-bottom:1rem;color:var(--text-muted);">All active songs on the system.</p>
        <div class="table-responsive">
            <table class="table table-dark table-borderless align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Album</th>
                        <th>Genre</th>
                        <th>Artist</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($songs as $s): ?>
                    <tr>
                        <td><?= sanitize($s['id']) ?></td>
                        <td><?= sanitize($s['title']) ?></td>
                        <td><?= sanitize($s['album']) ?></td>
                        <td><?= sanitize($s['genre']) ?></td>
                        <td><?= sanitize($s['artist']) ?></td>
                        <td><?= sanitize(formatDuration((int)$s['duration'])) ?></td>
                        <td>
                            <div class="btn-group" role="group" aria-label="actions">
                                <a class="btn btn-sm btn-outline-sw view-btn" style="min-width:88px;" href="admin_song.php?id=<?= sanitize($s['id']) ?>">View</a>
                                <form method="POST" action="admin_songs.php" style="display:inline;" onsubmit="return confirm('Remove this song from the library?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="song_id" value="<?= sanitize($s['id']) ?>">
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
