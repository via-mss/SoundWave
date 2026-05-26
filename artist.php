<?php
// artist.php — public artist profile
require_once 'includes/config.php';
$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT a.*, u.avatar_url, u.bio FROM artists a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
$stmt->execute([$id]);
$artist = $stmt->fetch();
if (!$artist) { http_response_code(404); die('Artist not found.'); }

$songs = $pdo->prepare("SELECT * FROM songs WHERE artist_id = ? AND is_active = 1 ORDER BY uploaded_at DESC");
$songs->execute([$id]);
$songs = $songs->fetchAll();

$albumsStmt = $pdo->prepare(
    "SELECT album, cover_image, COUNT(*) AS track_count
     FROM songs
     WHERE artist_id = ? AND album <> '' AND is_active = 1
     GROUP BY album
     ORDER BY MAX(uploaded_at) DESC"
);
$albumsStmt->execute([$id]);
$albums = $albumsStmt->fetchAll();

$followers = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE artist_id = ?");
$followers->execute([$id]);
$followerCount = $followers->fetchColumn();

$isFollowing = false;
if (isLoggedIn()) {
    $chk = $pdo->prepare("SELECT id FROM follows WHERE user_id = ? AND artist_id = ?");
    $chk->execute([$_SESSION['user_id'], $id]);
    $isFollowing = (bool)$chk->fetch();
}

$pageTitle = sanitize($artist['artist_name']);
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-2 text-center mb-3">
            <?php $hasAvatar = !empty($artist['avatar_url']) && $artist['avatar_url'] !== 'uploads/covers/default_avatar.png'; ?>
            <div style="width:100px;height:100px;margin:0 auto 12px;position:relative;">
                <?php if ($hasAvatar): ?>
                    <img src="<?= SITE_URL ?>/<?= sanitize($artist['avatar_url']) ?>"
                        style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--accent);" alt=""
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                    <div class="artist-avatar-placeholder" style="display:none;width:100px;height:100px;border-radius:50%;border:3px solid var(--accent);margin:0 auto;background:var(--surface-2,#2a2a3a);color:var(--text-muted);font-size:2rem;">
                        <i class="fas fa-user"></i>
                    </div>
                <?php else: ?>
                    <div class="artist-avatar-placeholder" style="width:100px;height:100px;border-radius:50%;border:3px solid var(--accent);display:flex;align-items:center;justify-content:center;background:var(--surface-2,#2a2a3a);color:var(--text-muted);font-size:2rem;">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-10">
            <p class="section-title"><?= sanitize($artist['genre'] ?? 'Artist') ?></p>
            <h1 class="page-title"><?= sanitize($artist['artist_name']) ?></h1>
            <?php if ($artist['bio']): ?><p class="text-muted"><?= sanitize($artist['bio']) ?></p><?php endif; ?>
            <div class="d-flex align-items-center gap-3 mt-2 artist-meta-row">
                <span class="artist-followers text-muted" style="font-size:0.85rem;"><strong><?= $followerCount ?></strong> followers</span>
                <?php if (isLoggedIn() && $_SESSION['user_id'] != $artist['user_id']): ?>
                <button id="follow-btn"
                    class="btn <?= $isFollowing ? 'btn-outline-sw' : 'btn-primary-sw' ?>"
                    data-artist-id="<?= $id ?>"
                    style="padding:0.4rem 1rem;font-size:0.85rem;">
                    <?= $isFollowing ? 'Following' : 'Follow' ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <p class="section-title">Tracks</p>
    <?php if (empty($songs)): ?>
    <div class="sw-card text-center" style="padding:50px;"><p class="text-muted">No songs yet.</p></div>
    <?php else: ?>
    <div class="sw-panel">
        <?php foreach ($songs as $i => $song):
            $hasCover = !empty($song['cover_image']) && $song['cover_image'] !== 'assets/default_cover.png'; ?>
        <div class="song-row"
            data-song-id="<?= $song['id'] ?>"
            data-song-url="<?= SITE_URL ?>/<?= sanitize($song['file_path']) ?>"
            data-song-title="<?= sanitize($song['title']) ?>"
            data-song-artist-id="<?= $artist['id'] ?>"
            data-song-artist="<?= sanitize($artist['artist_name']) ?>"
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
                <div class="song-row-artist"><?= sanitize($song['album'] ?? '') ?></div>
            </div>
            <?php if ($song['genre']): ?><span class="genre-badge"><?= sanitize($song['genre']) ?></span><?php endif; ?>
            <div class="song-row-duration"><?= formatDuration($song['duration']) ?></div>
            <div class="song-row-actions">
                <?php if (isLoggedIn()): ?>
                    <button class="like-btn" data-song-id="<?= $song['id'] ?>"><i class="far fa-heart"></i></button>
                    <button class="ctrl-btn add-to-playlist-btn" data-song-id="<?= $song['id'] ?>" title="Add to playlist">
                        <i class="fas fa-list-ul"></i>
                    </button>
                <?php endif; ?>
                <?php if (isLoggedIn() && $_SESSION['user_id'] === $artist['user_id']): ?>
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
    <?php endif; ?>

    <?php if (!empty($albums)): ?>
    <p class="section-title">Albums</p>
    <div class="row g-3 mb-4">
        <?php foreach ($albums as $album): 
            $hasCover = !empty($album['cover_image']) && $album['cover_image'] !== 'assets/default_cover.png'; ?>
        <div class="col-sm-6 col-md-4">
            <a href="album.php?name=<?= rawurlencode($album['album']) ?>" class="sw-card d-block text-decoration-none" style="padding:16px;">
                <?php if ($hasCover): ?>
                    <img src="<?= SITE_URL ?>/<?= sanitize($album['cover_image']) ?>" alt="<?= sanitize($album['album']) ?>" style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:16px;">
                <?php else: ?>
                    <div class="album-cover-placeholder" style="margin-bottom:14px;"><i class="fas fa-compact-disc"></i></div>
                <?php endif; ?>
                <div style="margin-top:10px;">
                    <h3 style="font-size:1rem;font-weight:700;color:var(--text);margin-bottom:6px;"><?= sanitize($album['album']) ?></h3>
                    <p class="text-muted" style="font-size:0.8rem;margin-bottom:0;"><?= (int)$album['track_count'] ?> track<?= (int)$album['track_count'] !== 1 ? 's' : '' ?></p>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
const followBtn = document.getElementById('follow-btn');
if (followBtn) {
    followBtn.addEventListener('click', async () => {
        const aid = followBtn.dataset.artistId;
        const res = await fetch(`/soundwave/php/toggle_follow.php?artist_id=${aid}`, { method: 'POST' });
        const data = await res.json();
        if (data.following !== undefined) {
            followBtn.textContent = data.following ? 'Following' : 'Follow';
            followBtn.className   = data.following ? 'btn btn-outline-sw' : 'btn btn-primary-sw';
        }
    });
}
</script>

<!-- Add to Playlist Modal -->
<div class="modal fade" id="playlistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content sw-modal">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title" style="font-size:0.95rem;font-weight:700;">Add to Playlist</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2" id="playlist-modal-body">
                <p class="text-muted text-center py-3" style="font-size:0.85rem;">Loading playlists…</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
