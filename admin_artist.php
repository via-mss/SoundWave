<?php
// admin_artist.php — admin artist detail and edit page
$pageTitle = 'Admin - Artist';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}
$pdo = getDB();
$targetId = (int)($_GET['id'] ?? 0);
if ($targetId <= 0) {
    redirect('admin_artists.php');
}
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $stmt = $pdo->prepare('SELECT a.id, a.artist_name, a.user_id, u.username FROM artists a JOIN users u ON a.user_id = u.id WHERE a.id = ?');
        $stmt->execute([$targetId]);
        $artist = $stmt->fetch();
        if (!$artist) {
            $error = 'Artist not found.';
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM artists WHERE id = ?')->execute([$targetId]);
            redirect('admin_artists.php?deleted=1');
        } elseif ($action === 'save') {
            $name = trim($_POST['artist_name'] ?? '');
            $genre = trim($_POST['genre'] ?? '');
            $country = trim($_POST['country'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            if ($name === '') {
                $error = 'Artist name cannot be empty.';
            } else {
                $pdo->prepare('UPDATE artists SET artist_name = ?, genre = ?, country = ?, bio = ? WHERE id = ?')
                    ->execute([$name, $genre, $country, $bio, $targetId]);
                $success = 'Artist profile updated.';
            }
        }
    }
}
$stmt = $pdo->prepare('SELECT a.*, u.username AS owner_name, u.email AS owner_email FROM artists a JOIN users u ON a.user_id = u.id WHERE a.id = ?');
$stmt->execute([$targetId]);
$artist = $stmt->fetch();
if (!$artist) {
    redirect('admin_artists.php');
}
$songCount = $pdo->prepare('SELECT COUNT(*) FROM songs WHERE artist_id = ? AND is_active = 1');
$songCount->execute([$targetId]);
$songCount = $songCount->fetchColumn();
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Artist Details</h1>
    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>

    <div class="row gy-3">
        <div class="col-lg-5">
            <div class="sw-card">
                <h2 class="page-title" style="font-size:1.1rem;">Profile</h2>
                <p><strong>ID:</strong> <?= sanitize($artist['id']) ?></p>
                <p><strong>Name:</strong> <?= sanitize($artist['artist_name']) ?></p>
                <p><strong>Genre:</strong> <?= sanitize($artist['genre']) ?></p>
                <p><strong>Country:</strong> <?= sanitize($artist['country']) ?></p>
                <p><strong>Owner:</strong> <?= sanitize($artist['owner_name']) ?> (<?= sanitize($artist['owner_email']) ?>)</p>
                <p><strong>Tracks:</strong> <?= sanitize($songCount) ?></p>
                <div class="mt-3">
                    <a class="btn btn-outline-sw" href="artist.php?id=<?= sanitize($artist['id']) ?>">View public artist</a>
                    <form method="POST" action="admin_artist.php?id=<?= sanitize($artist['id']) ?>" style="display:inline; margin-left:0.5rem;" onsubmit="return confirm('Delete this artist and all related songs?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-outline-danger" type="submit">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="sw-card">
                <h2 class="page-title" style="font-size:1.1rem;">Edit Artist</h2>
                <form method="POST" action="admin_artist.php?id=<?= sanitize($artist['id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3">
                        <label class="sw-label" for="artist_name">Artist Name</label>
                        <input class="form-control sw-input" type="text" id="artist_name" name="artist_name" value="<?= sanitize($artist['artist_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="genre">Genre</label>
                        <input class="form-control sw-input" type="text" id="genre" name="genre" value="<?= sanitize($artist['genre']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="country">Country</label>
                        <input class="form-control sw-input" type="text" id="country" name="country" value="<?= sanitize($artist['country']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="bio">Bio</label>
                        <textarea class="form-control sw-input" id="bio" name="bio" rows="4"><?= sanitize($artist['bio']) ?></textarea>
                    </div>
                    <button class="btn-sw-primary" type="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <a class="btn btn-outline-sw mt-3" href="admin_artists.php">Back to Artists</a>
</div>

<?php require_once 'includes/footer.php'; ?>
