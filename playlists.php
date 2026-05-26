<?php
// playlists.php
$pageTitle = 'My Playlists';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');

$pdo   = getDB();
$error = $success = '';

// Create playlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } elseif ($_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) { $error = 'Playlist name is required.'; }
        else {
            // Handle optional cover image
            $coverPath = null;
            if (!empty($_FILES['cover_image']['name'])) {
                $imgAllowed = ['image/jpeg', 'image/png', 'image/webp'];
                $imgType    = mime_content_type($_FILES['cover_image']['tmp_name']);
                if (in_array($imgType, $imgAllowed) && $_FILES['cover_image']['size'] < 5 * 1024 * 1024) {
                    $imgExt  = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                    $imgName = uniqid('playlist_', true) . '.' . strtolower($imgExt);
                    $imgDest = UPLOAD_DIR . 'covers/' . $imgName;
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $imgDest)) {
                        $coverPath = COVER_DIR . $imgName;
                    }
                } else {
                    $error = 'Cover must be JPG, PNG, or WebP and under 5MB.';
                }
            }
            if (!$error) {
                $pdo->prepare('INSERT INTO playlists (user_id, name, description, cover_image) VALUES (?, ?, ?, ?)')
                    ->execute([$_SESSION['user_id'], $name, $desc, $coverPath]);
                $success = "Playlist \"$name\" created!";
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $pid = (int)$_POST['playlist_id'];
        $pdo->prepare('DELETE FROM playlists WHERE id = ? AND user_id = ?')
            ->execute([$pid, $_SESSION['user_id']]);
        $success = 'Playlist deleted.';
    }
}

// Fetch playlists
$playlists = $pdo->prepare(
    "SELECT p.*, COUNT(ps.song_id) as song_count
     FROM playlists p
     LEFT JOIN playlist_songs ps ON ps.playlist_id = p.id
     WHERE p.user_id = ?
     GROUP BY p.id
     ORDER BY p.created_at DESC"
);
$playlists->execute([$_SESSION['user_id']]);
$playlists = $playlists->fetchAll();

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Create Playlist -->
        <div class="col-md-4 mb-4">
            <div class="sw-card">
                <p class="section-title"><i class="fas fa-list-music me-2"></i>New Playlist</p>
                <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>
                <form method="POST" action="playlists.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="sw-label">Name</label>
                        <input class="form-control sw-input" type="text" name="name" required placeholder="My Playlist">
                    </div>
                    <div class="mb-3">
                        <label class="sw-label">Description</label>
                        <textarea class="form-control sw-input" name="description" rows="2" placeholder="Optional…"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label">Cover Image <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
                        <input class="form-control sw-input" type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
                        <small class="text-muted" style="font-size:0.75rem">JPG, PNG, WebP — max 5MB</small>
                    </div>
                    <button type="submit" class="btn-sw-primary"><i class="fas fa-plus me-1"></i>Create</button>
                </form>
            </div>
        </div>

        <!-- My Playlists -->
        <div class="col-md-8">
            <p class="section-title"><i class="fas fa-list-music me-2"></i>My Playlists</p>
            <?php if (empty($playlists)): ?>
            <div class="sw-card text-center" style="padding:50px">
                <i class="fas fa-list-music" style="font-size:3rem;color:var(--text-muted);display:block;margin-bottom:12px;"></i>
                <p class="text-muted">No playlists yet. Create your first one!</p>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($playlists as $pl): ?>
                <div class="col-sm-6">
                    <div class="sw-card" style="position:relative;">
                        <a href="playlist.php?id=<?= $pl['id'] ?>" class="text-decoration-none">
                            <?php if (!empty($pl['cover_image'])): ?>
                                <img src="<?= SITE_URL ?>/<?= sanitize($pl['cover_image']) ?>"
                                    style="width:100%;height:120px;object-fit:cover;border-radius:8px;margin-bottom:10px;" alt="Cover">
                            <?php else: ?>
                                <div style="width:100%;height:120px;border-radius:8px;background:var(--surface-2,#2a2a3a);display:flex;align-items:center;justify-content:center;margin-bottom:10px;color:var(--text-muted);font-size:2.5rem;">
                                    <i class="fas fa-music"></i>
                                </div>
                            <?php endif; ?>
                            <div style="font-weight:700;"><?= sanitize($pl['name']) ?></div>
                            <div class="text-muted" style="font-size:0.8rem;"><?= $pl['song_count'] ?> song<?= $pl['song_count'] != 1 ? 's' : '' ?></div>
                            <?php if ($pl['description']): ?><p class="text-muted mt-2" style="font-size:0.8rem;"><?= sanitize($pl['description']) ?></p><?php endif; ?>
                        </a>
                        <form method="POST" action="playlists.php" style="position:absolute;top:12px;right:12px;" onsubmit="return confirm('Delete this playlist?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="playlist_id" value="<?= $pl['id'] ?>">
                            <button type="submit" class="ctrl-btn" style="color:var(--danger);"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
