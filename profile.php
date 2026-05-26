<?php
// profile.php
$pageTitle = 'Profile';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');

$pdo    = getDB();
$user   = currentUser();
$error  = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $display = trim($_POST['display_name'] ?? '');
        $bio     = trim($_POST['bio'] ?? '');
        if (!$display) $error = 'Display name cannot be empty.';
        else {
            // Handle avatar upload
            $avatarPath = $user['avatar_url'];
            if (!empty($_FILES['avatar']['name'])) {
                $allowed = ['image/jpeg','image/png','image/webp'];
                $t = mime_content_type($_FILES['avatar']['tmp_name']);
                if (in_array($t, $allowed) && $_FILES['avatar']['size'] < 2 * 1024 * 1024) {
                    $ext  = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $name = 'avatar_' . $user['id'] . '.' . strtolower($ext);
                    $dest = UPLOAD_DIR . 'covers/' . $name;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                        $avatarPath = COVER_DIR . $name;
                    }
                }
            }
            $pdo->prepare('UPDATE users SET display_name = ?, bio = ?, avatar_url = ? WHERE id = ?')
                ->execute([$display, $bio, $avatarPath, $user['id']]);
            $success = 'Profile updated!';
            $user = currentUser();
        }
    }
}

// Liked songs
$liked = $pdo->prepare(
    "SELECT s.*, a.artist_name FROM likes l
     JOIN songs s ON l.song_id = s.id
     JOIN artists a ON s.artist_id = a.id
     WHERE l.user_id = ? ORDER BY l.liked_at DESC LIMIT 10"
);
$liked->execute([$user['id']]);
$liked = $liked->fetchAll();

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="sw-card text-center">
                <?php if (!empty($user['avatar_url']) && $user['avatar_url'] !== 'uploads/covers/default_avatar.png'): ?>
                <div style="width:90px;height:90px;margin:0 auto 12px;position:relative;">
                    <img src="<?= SITE_URL ?>/<?= sanitize($user['avatar_url']) ?>"
                        style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--accent);" alt="Avatar"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display:none;width:90px;height:90px;border-radius:50%;background:var(--surface-2,#2a2a3a);border:3px solid var(--border);align-items:center;justify-content:center;margin:0 auto 12px;color:var(--text-muted);font-size:2rem;">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <?php else: ?>
                <div style="width:90px;height:90px;border-radius:50%;background:var(--surface-2,#2a2a3a);border:3px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:var(--text-muted);font-size:2rem;">
                    <i class="fas fa-user"></i>
                </div>
                <?php endif; ?>
                <h2 style="font-size:1.3rem;"><?= sanitize($user['display_name'] ?? $user['username']) ?></h2>
                <p class="text-muted" style="font-size:0.85rem;">@<?= sanitize($user['username']) ?></p>
                <span class="genre-badge"><?= sanitize($user['role']) ?></span>
                <?php if ($user['bio']): ?><p class="mt-3" style="font-size:0.85rem;"><?= sanitize($user['bio']) ?></p><?php endif; ?>
            </div>

            <div class="sw-card mt-3">
                <p class="section-title">Edit Profile</p>
                <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>
                <form method="POST" action="profile.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="mb-3">
                        <label class="sw-label">Display Name</label>
                        <input class="form-control sw-input" type="text" name="display_name" required value="<?= sanitize($user['display_name'] ?? $user['username']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="sw-label">Bio</label>
                        <textarea class="form-control sw-input" name="bio" rows="3"><?= sanitize($user['bio'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label">Avatar</label>
                        <input class="form-control sw-input" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <button type="submit" class="btn-sw-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <p class="section-title">Liked Songs</p>
            <?php if (empty($liked)): ?>
            <div class="sw-card text-center" style="padding:50px;">
                <p class="text-muted">No liked songs yet. Start exploring!</p>
                <a href="browse.php" class="btn-primary-sw mt-2">Browse Music</a>
            </div>
            <?php else: ?>
            <div class="sw-panel">
                <?php foreach ($liked as $i => $song):
                    $hasCover = !empty($song['cover_image']) && $song['cover_image'] !== 'assets/default_cover.png'; ?>
                <div class="song-row"
                    data-song-id="<?= $song['id'] ?>"
                    data-song-url="<?= SITE_URL ?>/<?= sanitize($song['file_path']) ?>"
                    data-song-title="<?= sanitize($song['title']) ?>"
                    data-song-artist-id="<?= $song['artist_id'] ?>"
                    data-song-artist="<?= sanitize($song['artist_name']) ?>"
                    data-song-album="<?= sanitize($song['album'] ?? '') ?>"
                    data-song-cover="<?= $hasCover ? SITE_URL . '/' . sanitize($song['cover_image']) : '' ?>">
                    <div class="song-row-num"><?= $i + 1 ?></div>
                    <?php if ($hasCover): ?>
                        <img class="song-row-cover" src="<?= SITE_URL ?>/<?= sanitize($song['cover_image']) ?>" alt="">
                    <?php else: ?>
                        <div class="song-row-cover song-cover-placeholder"><i class="fas fa-music"></i></div>
                    <?php endif; ?>
                    <div class="song-row-info">
                        <div class="song-row-title">
                            <?php if (!empty($song['album'])): ?>
                                <a href="album.php?name=<?= rawurlencode($song['album']) ?>"><?= sanitize($song['title']) ?></a>
                            <?php else: ?>
                                <?= sanitize($song['title']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="song-row-artist"><a href="artist.php?id=<?= $song['artist_id'] ?>"><?= sanitize($song['artist_name']) ?></a></div>
                    </div>
                    <div class="song-row-duration"><?= formatDuration($song['duration']) ?></div>
                    <div class="song-row-actions">
                        <button class="like-btn liked" data-song-id="<?= $song['id'] ?>"><i class="fas fa-heart"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
