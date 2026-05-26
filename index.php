<?php
// index.php — SoundWave Home Page
$pageTitle = 'Home';
require_once 'includes/config.php';
$pdo = getDB();

// Fetch recent songs
$recentSongs = $pdo->query(
    "SELECT s.*, a.artist_name, u.avatar_url
     FROM songs s
     JOIN artists a ON s.artist_id = a.id
     JOIN users u ON a.user_id = u.id
     WHERE s.is_active = 1
     ORDER BY s.uploaded_at DESC
     LIMIT 8"
)->fetchAll();

// Fetch popular songs
$popularSongs = $pdo->query(
    "SELECT s.*, a.artist_name
     FROM songs s
     JOIN artists a ON s.artist_id = a.id
     WHERE s.is_active = 1
     ORDER BY s.play_count DESC
     LIMIT 6"
)->fetchAll();

// Fetch featured artists
$artists = $pdo->query(
    "SELECT a.*, u.avatar_url, COUNT(s.id) as song_count
     FROM artists a
     JOIN users u ON a.user_id = u.id
     LEFT JOIN songs s ON s.artist_id = a.id AND s.is_active = 1
     GROUP BY a.id
     ORDER BY song_count DESC
     LIMIT 6"
)->fetchAll();

require_once 'includes/header.php';
?>

<!-- Hero -->
<section class="sw-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <p class="section-title">Free music streaming</p>
                <h1>Your <span class="highlight">music</span>,<br>your world.</h1>
                <p>Discover independent artists, stream your favourite tracks, and build playlists you'll love.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="browse.php" class="btn btn-primary-sw"><i class="fas fa-compass me-2"></i>Explore Music</a>
                    <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-outline-sw">Join Free</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-flex justify-content-end">
                <div style="font-size:9rem;opacity:0.08;">
                    <i class="fas fa-wave-square"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container mt-5">

    <!-- New Releases -->
    <?php if ($recentSongs): ?>
    <section class="mb-5">
        <p class="section-title">New Releases</p>
        <div class="row g-3">
            <?php foreach ($recentSongs as $song):
                $hasCover = !empty($song['cover_image']) && $song['cover_image'] !== 'assets/default_cover.png'; ?>
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="song-card"
                    data-song-id="<?= $song['id'] ?>"
                    data-song-url="<?= SITE_URL ?>/<?= sanitize($song['file_path']) ?>"
                    data-song-title="<?= sanitize($song['title']) ?>"
                    data-song-artist="<?= sanitize($song['artist_name']) ?>"
                    data-song-album="<?= sanitize($song['album'] ?? '') ?>"
                    data-song-artist-id="<?= $song['artist_id'] ?>"
                    data-song-cover="<?= $hasCover ? SITE_URL . '/' . sanitize($song['cover_image']) : '' ?>">
                    <div class="song-card-cover">
                        <?php if ($hasCover): ?>
                            <img src="<?= SITE_URL ?>/<?= sanitize($song['cover_image']) ?>" alt="<?= sanitize($song['title']) ?>">
                        <?php else: ?>
                            <div class="song-card-cover-placeholder"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                        <div class="song-card-play"><i class="fas fa-play-circle"></i></div>
                    </div>
                    <div class="song-card-body">
                        <div class="song-card-title">
                            <?php if (!empty($song['album'])): ?>
                                <a href="browse.php?album=<?= rawurlencode($song['album']) ?>"><?= sanitize($song['title']) ?></a>
                            <?php else: ?>
                                <?= sanitize($song['title']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="song-card-artist">
                            <a href="artist.php?id=<?= $song['artist_id'] ?>"><?= sanitize($song['artist_name']) ?></a>
                        </div>
                        <div class="song-card-meta">
                            <span><?= formatDuration($song['duration']) ?></span>
                            <span><i class="fas fa-play"></i> <?= number_format($song['play_count']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Popular Tracks -->
    <?php if ($popularSongs): ?>
    <section class="mb-5">
        <p class="section-title">Popular Tracks</p>
        <div class="sw-panel">
            <?php foreach ($popularSongs as $i => $song):
            $hasCover = !empty($song['cover_image']) && $song['cover_image'] !== 'assets/default_cover.png'; ?>
            <div class="song-row"
                data-song-id="<?= $song['id'] ?>"
                data-song-url="<?= SITE_URL ?>/<?= sanitize($song['file_path']) ?>"
                data-song-title="<?= sanitize($song['title']) ?>"
                data-song-artist-id="<?= $song['artist_id'] ?>"
                data-song-artist="<?= sanitize($song['artist_name']) ?>"
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
                <?php if ($song['genre']): ?><span class="genre-badge me-2"><?= sanitize($song['genre']) ?></span><?php endif; ?>
                <div class="song-row-duration"><?= formatDuration($song['duration']) ?></div>
                <div class="song-row-actions">
                    <?php if (isLoggedIn()): ?>
                    <button class="like-btn" data-song-id="<?= $song['id'] ?>"><i class="far fa-heart"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Artists -->
    <?php if ($artists): ?>
    <section class="mb-5">
        <p class="section-title">Featured Artists</p>
        <div class="row g-3">
            <?php foreach ($artists as $artist):
                $hasAvatar = !empty($artist['avatar_url']) && $artist['avatar_url'] !== 'uploads/covers/default_avatar.png'; ?>
            <div class="col-6 col-sm-4 col-md-2">
                <a href="artist.php?id=<?= $artist['id'] ?>" class="text-decoration-none">
                    <div class="artist-card">
                        <?php if ($hasAvatar): ?>
                            <img class="artist-avatar" src="<?= SITE_URL ?>/<?= sanitize($artist['avatar_url']) ?>" alt=""
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                            <div class="artist-avatar-placeholder d-none"><i class="fas fa-user"></i></div>
                        <?php else: ?>
                            <div class="artist-avatar-placeholder"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                        <div class="artist-name"><?= sanitize($artist['artist_name']) ?></div>
                        <div class="artist-genre"><?= sanitize($artist['genre'] ?? '') ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
