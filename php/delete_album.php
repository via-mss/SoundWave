<?php
require_once __DIR__ . '/../includes/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL);
}
if (!isLoggedIn() || !validateCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Unauthorized request.');
}
$album = trim($_POST['album'] ?? '');
if ($album === '') {
    redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL);
}
$pdo = getDB();
$stmt = $pdo->prepare('SELECT DISTINCT a.user_id FROM songs s JOIN artists a ON s.artist_id = a.id WHERE s.album = ? AND s.is_active = 1');
$stmt->execute([$album]);
$owners = array_unique(array_column($stmt->fetchAll(), 'user_id'));
if (count($owners) === 1 && $owners[0] === $_SESSION['user_id']) {
    $del = $pdo->prepare('UPDATE songs s JOIN artists a ON s.artist_id = a.id SET s.is_active = 0 WHERE s.album = ? AND a.user_id = ? AND s.is_active = 1');
    $del->execute([$album, $_SESSION['user_id']]);
}
redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL);
