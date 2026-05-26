<?php
// upload.php — Artist song upload
$pageTitle = 'Upload Song';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
$user = currentUser();
if (!$user || $user['role'] !== 'artist') {
    redirect(SITE_URL . '/index.php');
}

$pdo    = getDB();
$artist = $pdo->prepare('SELECT * FROM artists WHERE user_id = ?');
$artist->execute([$user['id']]);
$artist = $artist->fetch();
if (!$artist) redirect(SITE_URL . '/index.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $title    = trim($_POST['title'] ?? '');
        $album    = trim($_POST['album'] ?? '');
        $genre    = trim($_POST['genre'] ?? '');
        $duration = (int)($_POST['duration'] ?? 0);

        if (!$title) $error = 'Song title is required.';
        elseif (empty($_FILES['song_file']['name'])) $error = 'Please upload an audio file.';
        else {
            // Upload song file
            $allowed = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/flac'];
            $ftype   = mime_content_type($_FILES['song_file']['tmp_name']);
            $fsize   = $_FILES['song_file']['size'];

            if (!in_array($ftype, $allowed)) {
                $error = 'Unsupported audio format. Allowed: MP3, WAV, OGG, FLAC.';
            } elseif ($fsize > MAX_FILE_SIZE) {
                $error = 'File too large. Max 50MB.';
            } else {
                $ext      = pathinfo($_FILES['song_file']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('song_', true) . '.' . strtolower($ext);
                $dest     = UPLOAD_DIR . 'songs/' . $filename;
                $filePath = SONG_DIR . $filename;

                if (!move_uploaded_file($_FILES['song_file']['tmp_name'], $dest)) {
                    $error = 'File upload failed. Check folder permissions.';
                } else {
                    // Cover image
                    $coverPath = 'assets/default_cover.png';
                    $existingAlbumCover = null;
                    if ($album !== '') {
                        $stmtCover = $pdo->prepare('SELECT cover_image FROM songs WHERE album = ? AND cover_image <> ? LIMIT 1');
                        $stmtCover->execute([$album, 'assets/default_cover.png']);
                        $existingAlbumCover = $stmtCover->fetchColumn();
                    }

                    if (!empty($_FILES['cover_image']['name'])) {
                        $imgAllowed = ['image/jpeg', 'image/png', 'image/webp'];
                        $imgType    = mime_content_type($_FILES['cover_image']['tmp_name']);
                        if (in_array($imgType, $imgAllowed) && $_FILES['cover_image']['size'] < 5 * 1024 * 1024) {
                            $imgExt   = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                            $imgName  = uniqid('cover_', true) . '.' . strtolower($imgExt);
                            $imgDest  = UPLOAD_DIR . 'covers/' . $imgName;
                            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $imgDest)) {
                                $coverPath = COVER_DIR . $imgName;
                            }
                        }
                    } elseif (!empty($existingAlbumCover)) {
                        $coverPath = $existingAlbumCover;
                    }

                    $pdo->prepare(
                        'INSERT INTO songs (artist_id, title, album, genre, duration, file_path, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?)'
                    )->execute([$artist['id'], $title, $album, $genre, $duration, $filePath, $coverPath]);
                    $success = 'Song uploaded successfully!';
                }
            }
        }
    }
}
require_once 'includes/header.php';
?>

<div class="container mt-5" style="max-width:640px;">
    <p class="section-title">Upload</p>
    <h1 class="page-title">Add a Song</h1>
    <p class="page-subtitle">Share your music with the SoundWave community.</p>

    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>

    <div class="sw-card">
        <form method="POST" action="upload.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <!-- Drop zone -->
            <div class="upload-drop mb-4" id="drop-zone">
                <i class="fas fa-music"></i>
                <p>Drag & drop your audio file here, or click to browse</p>
                <small class="text-muted">MP3, WAV, OGG, FLAC — max 50MB</small>
                <input type="file" id="song-file" name="song_file" accept=".mp3,.wav,.ogg,.flac" style="display:none">
            </div>

            <div class="mb-3">
                <label class="sw-label" for="title">Song Title *</label>
                <input class="form-control sw-input" type="text" id="title" name="title" required
                    value="<?= sanitize($_POST['title'] ?? '') ?>">
            </div>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label class="sw-label" for="album">Album</label>
                    <input class="form-control sw-input" type="text" id="album" name="album"
                        value="<?= sanitize($_POST['album'] ?? '') ?>">
                </div>
                <div class="col-sm-6 mb-3">
                    <label class="sw-label" for="genre">Genre</label>
                    <input class="form-control sw-input" type="text" id="genre" name="genre" placeholder="e.g. Electronic"
                        value="<?= sanitize($_POST['genre'] ?? '') ?>">
                </div>
            </div>
            <input type="hidden" id="duration" name="duration" value="<?= sanitize($_POST['duration'] ?? '') ?>">
            <div class="mb-2">
                <small id="duration-display" class="text-muted">Duration will be detected after you choose an audio file.</small>
            </div>
            <div class="mb-4">
                <label class="sw-label">Cover Image (optional)</label>
                <input class="form-control sw-input" type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
                <small class="text-muted" style="font-size:0.75rem">JPG, PNG, WebP — max 5MB</small>
            </div>

            <button type="submit" class="btn-sw-primary"><i class="fas fa-upload me-2"></i>Upload Song</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
