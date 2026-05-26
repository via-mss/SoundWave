<?php
// album.php — album detail page
$pageTitle = 'Album';
require_once 'includes/config.php';
$pdo = getDB();

$albumName = trim($_GET['name'] ?? '');
if (!$albumName) {
    redirect(SITE_URL . '/browse.php');
}

$stmt = $pdo->prepare(
    "SELECT s.*, a.artist_name, a.user_id AS artist_user_id, a.id AS artist_id FROM songs s
     JOIN artists a ON s.artist_id = a.id
     WHERE s.album = ? AND s.is_active = 1
     ORDER BY s.uploaded_at DESC"
);
$stmt->execute([$albumName]);
$songs = $stmt->fetchAll();
if (empty($songs)) {
    http_response_code(404);
    die('Album not found.');
}

$albumTitle = sanitize($albumName);
$artists = array_unique(array_map(fn($song) => $song['artist_name'], $songs));
$artistLabel = count($artists) === 1 ? sanitize($artists[0]) : 'Various Artists';
$coverImage = null;
$canManageAlbum = false;
if (count($songs) > 0) {
    $albumOwnerId = $songs[0]['artist_user_id'];
    $canManageAlbum = isLoggedIn() && count($artists) === 1 && $_SESSION['user_id'] === $albumOwnerId;
}
foreach ($songs as $song) {
    if (!empty($song['cover_image'])) {
        $coverImage = SITE_URL . '/' . sanitize($song['cover_image']);
        break;
    }
}
$pageTitle = $albumTitle;
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3 mb-4 text-center">
            <div class="sw-card" style="padding:30px 20px;">
                <?php if ($coverImage): ?>
                    <img src="<?= $coverImage ?>" alt="<?= $albumTitle ?>" style="width:100%;border-radius:16px;object-fit:cover;margin-bottom:18px;">
                <?php else: ?>
                    <div class="album-cover-placeholder" style="margin-bottom:18px;">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <h2 style="font-size:1.3rem;margin-bottom:0.5rem;"><?= $albumTitle ?></h2>
                <p class="text-muted" style="font-size:0.9rem;"><?= $artistLabel ?></p>
                <p class="text-muted" style="font-size:0.85rem;"><?= count($songs) ?> track<?= count($songs) !== 1 ? 's' : '' ?></p>
                <?php if ($canManageAlbum): ?>
                    <form method="POST" action="<?= SITE_URL ?>/php/delete_album.php" onsubmit="return confirm('Delete this album and all its songs?');" style="margin-top:14px;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="album" value="<?= sanitize($albumName) ?>">
                        <button type="submit" class="btn btn-outline-danger w-100">Delete Album</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-9">
            <div class="sw-panel">
                <?php foreach ($songs as $i => $song):
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
                        <div class="song-row-title"><?= sanitize($song['title']) ?></div>
                        <div class="song-row-artist"><a href="artist.php?id=<?= $song['artist_id'] ?>"><?= sanitize($song['artist_name']) ?></a></div>
                    </div>
                    <div class="song-row-duration"><?= formatDuration($song['duration']) ?></div>
                    <div class="song-row-actions">
                        <?php if (isLoggedIn()): ?>
                            <button class="like-btn" data-song-id="<?= $song['id'] ?>"><i class="far fa-heart"></i></button>
                        <?php endif; ?>
                        <?php if (isLoggedIn() && $_SESSION['user_id'] === $song['artist_user_id']): ?>
                            <form method="POST" action="<?= SITE_URL ?>/php/delete_song.php" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                                <button type="submit" class="delete-btn" onclick="return confirm('Delete this song?');"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>