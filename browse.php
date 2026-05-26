<?php
// browse.php
$pageTitle = 'Browse';
require_once 'includes/config.php';
$pdo = getDB();

$q     = trim($_GET['q'] ?? '');
$genre = trim($_GET['genre'] ?? '');
$album = trim($_GET['album'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build dynamic query
$where  = ['s.is_active = 1'];
$params = [];
if ($q !== '') {
    $where[]  = '(s.title LIKE ? OR a.artist_name LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($album !== '') {
    $where[]  = 's.album = ?';
    $params[] = $album;
}
if ($genre !== '') {
    $where[]  = 's.genre = ?';
    $params[] = $genre;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM songs s JOIN artists a ON s.artist_id = a.id $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = ceil($total / $limit);

$params[] = $limit;
$params[] = $offset;
$songs = $pdo->prepare(
    "SELECT s.*, a.artist_name FROM songs s JOIN artists a ON s.artist_id = a.id $whereSQL ORDER BY s.uploaded_at DESC LIMIT ? OFFSET ?"
);
$songs->execute($params);
$songs = $songs->fetchAll();

// Genres for filter
$genres = $pdo->query("SELECT DISTINCT genre FROM songs WHERE genre IS NOT NULL AND genre != '' ORDER BY genre")->fetchAll(PDO::FETCH_COLUMN);

// Get liked song IDs for the current user so we can pre-fill hearts
$likedIds = [];
if (isLoggedIn()) {
    $likedStmt = $pdo->prepare("SELECT song_id FROM likes WHERE user_id = ?");
    $likedStmt->execute([$_SESSION['user_id']]);
    $likedIds = array_column($likedStmt->fetchAll(), 'song_id');
}

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Sidebar filters -->
        <div class="col-md-3 mb-4">
            <div class="sw-card">
                <p class="section-title">Filter</p>
                <form method="GET" action="browse.php">
                    <div class="mb-3">
                        <label class="sw-label">Search</label>
                        <input class="form-control sw-input" type="text" name="q" value="<?= sanitize($q) ?>" placeholder="Title, artist…">
                    </div>
                    <?php if ($album): ?>
                    <input type="hidden" name="album" value="<?= sanitize($album) ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="sw-label">Genre</label>
                        <select class="form-control sw-input" name="genre">
                            <option value="">All Genres</option>
                            <?php foreach ($genres as $g): ?>
                            <option value="<?= sanitize($g) ?>" <?= $genre === $g ? 'selected' : '' ?>><?= sanitize($g) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-sw-primary">Apply</button>
                    <?php if ($q || $genre): ?>
                    <a href="browse.php" class="btn btn-outline-sw mt-2 w-100 text-center d-block" style="padding:0.6rem">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="col-md-9">
            <?php if ($album): ?>
            <p class="page-subtitle">
                <?= $total ?> result<?= $total != 1 ? 's' : '' ?> for album "<strong><?= sanitize($album) ?></strong>"
            </p>
            <?php elseif ($q || $genre): ?>
            <p class="page-subtitle">
                <?= $total ?> result<?= $total != 1 ? 's' : '' ?>
                <?= $q ? ' for "<strong>' . sanitize($q) . '</strong>"' : '' ?>
                <?= $genre ? ' in <strong>' . sanitize($genre) . '</strong>' : '' ?>
            </p>
            <?php else: ?>
            <p class="section-title">All Music</p>
            <?php endif; ?>

            <?php if (empty($songs)): ?>
            <div class="sw-card text-center" style="padding:60px 20px;">
                <i class="fas fa-search" style="font-size:3rem;color:var(--text-muted);margin-bottom:16px;display:block;"></i>
                <p class="text-muted">No songs found. Try a different search or genre.</p>
            </div>
            <?php else: ?>
            <div class="sw-panel">
                <?php foreach ($songs as $i => $song):
                    $isLiked = in_array($song['id'], $likedIds);
                    $hasCover = !empty($song['cover_image']) && $song['cover_image'] !== 'assets/default_cover.png';
                ?>
                <div class="song-row"
                    data-song-id="<?= $song['id'] ?>"
                    data-song-url="<?= SITE_URL ?>/<?= sanitize($song['file_path']) ?>"
                    data-song-title="<?= sanitize($song['title']) ?>"
                    data-song-artist-id="<?= $song['artist_id'] ?>"
                    data-song-artist="<?= sanitize($song['artist_name']) ?>"
                    data-song-album="<?= sanitize($song['album'] ?? '') ?>"
                    data-song-cover="<?= $hasCover ? SITE_URL . '/' . sanitize($song['cover_image']) : '' ?>">
                    <div class="song-row-num"><?= $offset + $i + 1 ?></div>
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
                    <?php if ($song['genre']): ?><span class="genre-badge"><?= sanitize($song['genre']) ?></span><?php endif; ?>
                    <div class="song-row-duration"><?= formatDuration($song['duration']) ?></div>
                    <div class="song-row-actions">
                        <?php if (isLoggedIn()): ?>
                        <button class="like-btn <?= $isLiked ? 'liked' : '' ?>" data-song-id="<?= $song['id'] ?>">
                            <i class="<?= $isLiked ? 'fas' : 'far' ?> fa-heart"></i>
                        </button>
                        <button class="ctrl-btn add-to-playlist-btn" data-song-id="<?= $song['id'] ?>" title="Add to playlist">
                            <i class="fas fa-list-ul"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?q=<?= urlencode($q) ?>&genre=<?= urlencode($genre) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

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
