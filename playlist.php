<?php
// playlist.php — view a single playlist
$pageTitle = 'Playlist';
require_once 'includes/config.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, u.username FROM playlists p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$id]);
$playlist = $stmt->fetch();

if (!$playlist) { http_response_code(404); die('Playlist not found.'); }
if (!$playlist['is_public'] && (!isLoggedIn() || $_SESSION['user_id'] != $playlist['user_id'])) {
    redirect(SITE_URL . '/login.php');
}

$songs = $pdo->prepare(
    "SELECT s.*, a.artist_name FROM playlist_songs ps
     JOIN songs s ON ps.song_id = s.id
     JOIN artists a ON s.artist_id = a.id
     WHERE ps.playlist_id = ? ORDER BY ps.position ASC, ps.added_at ASC"
);
$songs->execute([$id]);
$songs = $songs->fetchAll();
$pageTitle = sanitize($playlist['name']);

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3 mb-4 text-center">
            <div style="background:var(--bg3);border-radius:12px;padding:40px 20px;border:1px solid var(--border);">
                <div style="font-size:5rem;">🎵</div>
                <h2 style="font-size:1.3rem;margin-top:12px;"><?= sanitize($playlist['name']) ?></h2>
                <p class="text-muted" style="font-size:0.85rem;">by <?= sanitize($playlist['username']) ?></p>
                <p class="text-muted" style="font-size:0.8rem;"><?= count($songs) ?> tracks</p>
                <?php if ($playlist['description']): ?>
                <p style="font-size:0.82rem;color:var(--text-muted);"><?= sanitize($playlist['description']) ?></p>
                <?php endif; ?>
                <?php if (!empty($songs)): ?>
                <?php $playlistSongs = htmlspecialchars(json_encode(array_map(fn($s) => [
                        'id'     => $s['id'],
                        'url'    => SITE_URL . '/' . $s['file_path'],
                        'title'  => $s['title'],
                        'artist' => $s['artist_name'],
                        'cover'  => SITE_URL . '/' . ($s['cover_image'] ?? 'assets/default_cover.png')
                    ], $songs)), ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-sw-primary mt-3 w-100 playlist-play-all-btn"
                    data-songs="<?= $playlistSongs ?>">
                    <i class="fas fa-play me-2"></i>Play All
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-9">
            <?php if (empty($songs)): ?>
            <div class="sw-card text-center" style="padding:60px;">
                <p class="text-muted">This playlist is empty.</p>
            </div>
            <?php else: ?>
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
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
