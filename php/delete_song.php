<?php
require_once __DIR__ . '/../includes/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL);
}
if (!isLoggedIn() || !validateCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Unauthorized request.');
}
$songId = (int)($_POST['song_id'] ?? 0);
if ($songId <= 0) {
    redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL);
}
$pdo = getDB();
$stmt = $pdo->prepare('SELECT s.id FROM songs s JOIN artists a ON s.artist_id = a.id WHERE s.id = ? AND a.user_id = ? AND s.is_active = 1');
$stmt->execute([$songId, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    $del = $pdo->prepare('UPDATE songs SET is_active = 0 WHERE id = ?');
    $del->execute([$songId]);
}
redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL);
