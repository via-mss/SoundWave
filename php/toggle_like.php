<?php
// php/toggle_like.php — AJAX endpoint
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['redirect' => SITE_URL . '/login.php']);
    exit;
}

$songId = (int)($_GET['song_id'] ?? 0);
if (!$songId) { echo json_encode(['error' => 'Invalid song.']); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id = ? AND song_id = ?');
$stmt->execute([$_SESSION['user_id'], $songId]);

if ($stmt->fetch()) {
    $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND song_id = ?')
        ->execute([$_SESSION['user_id'], $songId]);
    echo json_encode(['liked' => false]);
} else {
    $pdo->prepare('INSERT INTO likes (user_id, song_id) VALUES (?, ?)')
        ->execute([$_SESSION['user_id'], $songId]);
    echo json_encode(['liked' => true]);
}
