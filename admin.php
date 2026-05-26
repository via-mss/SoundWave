<?php
// admin.php — admin control panel
$pageTitle = 'Admin Panel';
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    redirect(SITE_URL . '/index.php');
}

$error = '';
$success = '';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'create_admin') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $display  = trim($_POST['display_name'] ?? $username);

        if (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Username must be 3–50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username may only contain letters, numbers, and underscores.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare(
                    'INSERT INTO users (username, email, password_hash, display_name, role) VALUES (?, ?, ?, ?, ?)' 
                )->execute([$username, $email, $hash, $display, 'admin']);
                $success = 'Admin account created successfully.';
            }
        }
    }
}

$stats = [
    'users'     => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'artists'   => $pdo->query('SELECT COUNT(*) FROM artists')->fetchColumn(),
    'songs'     => $pdo->query('SELECT COUNT(*) FROM songs WHERE is_active = 1')->fetchColumn(),
    'albums'    => $pdo->query("SELECT COUNT(DISTINCT album) FROM songs WHERE album <> '' AND is_active = 1")->fetchColumn(),
    'playlists' => $pdo->query('SELECT COUNT(*) FROM playlists')->fetchColumn(),
];

$users = $pdo->query('SELECT id, username, email, display_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 20')->fetchAll();
$artists = $pdo->query('SELECT a.id, a.artist_name, a.genre, u.username AS owner, u.email FROM artists a JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 20')->fetchAll();
$songs = $pdo->query('SELECT s.id, s.title, s.album, s.genre, s.duration, u.username AS artist FROM songs s JOIN artists a ON s.artist_id = a.id JOIN users u ON a.user_id = u.id WHERE s.is_active = 1 ORDER BY s.uploaded_at DESC LIMIT 20')->fetchAll();
$albums = $pdo->query("SELECT album, COUNT(*) AS track_count FROM songs WHERE album <> '' AND is_active = 1 GROUP BY album ORDER BY MIN(uploaded_at) DESC LIMIT 20")->fetchAll();
$playlists = $pdo->query('SELECT p.id, p.name, p.description, p.is_public, u.username AS owner, COUNT(ps.song_id) AS song_count FROM playlists p LEFT JOIN users u ON p.user_id = u.id LEFT JOIN playlist_songs ps ON ps.playlist_id = p.id GROUP BY p.id ORDER BY p.created_at DESC LIMIT 20')->fetchAll();

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <p class="section-title">Admin</p>
    <h1 class="page-title">Control Panel</h1>
    <?php if ($error): ?><div class="alert-sw-error"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-sw-success"><?= sanitize($success) ?></div><?php endif; ?>

    <div class="row gy-3">
        <?php foreach ($stats as $label => $value): ?>
        <div class="col-sm-6 col-md-4">
            <div class="sw-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                    <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;"><?= sanitize(ucfirst($label)) ?></div>
                    <a class="btn btn-outline-sw btn-sm" href="<?= SITE_URL ?>/admin_<?= strtolower($label) ?>.php">View</a>
                </div>
                <div style="font-size:2rem;font-weight:700;margin-top:8px;"><?= (int)$value ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row mt-4">
        <div class="col-lg-5">
            <div class="sw-card">
                <h2 style="font-size:1.1rem;margin-bottom:1rem;">Create Admin User</h2>
                <form method="POST" action="admin.php">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="create_admin">
                    <div class="mb-3">
                        <label class="sw-label" for="username">Username</label>
                        <input class="form-control sw-input" type="text" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="display_name">Display Name</label>
                        <input class="form-control sw-input" type="text" id="display_name" name="display_name">
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="email">Email</label>
                        <input class="form-control sw-input" type="email" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="sw-label" for="password">Password</label>
                        <input class="form-control sw-input" type="password" id="password" name="password" required minlength="8">
                    </div>
                    <button type="submit" class="btn-sw-primary">Create Admin</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="sw-card">
                <h2 style="font-size:1.1rem;margin-bottom:1rem;">Recent Users</h2>
                <div class="table-responsive">
                    <table class="table table-dark table-borderless align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= sanitize($u['username']) ?></td>
                                <td><?= sanitize($u['email']) ?></td>
                                <td><?= sanitize($u['role']) ?></td>
                                <td><?= sanitize($u['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
