<?php
// php/toggle_follow.php
require_once '../includes/config.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['redirect' => SITE_URL . '/login.php']); exit; }

$artistId = (int)($_GET['artist_id'] ?? 0);
if (!$artistId) { echo json_encode(['error' => 'Invalid artist.']); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT id FROM follows WHERE user_id = ? AND artist_id = ?');
$stmt->execute([$_SESSION['user_id'], $artistId]);

if ($stmt->fetch()) {
    $pdo->prepare('DELETE FROM follows WHERE user_id = ? AND artist_id = ?')->execute([$_SESSION['user_id'], $artistId]);
    echo json_encode(['following' => false]);
} else {
    $pdo->prepare('INSERT INTO follows (user_id, artist_id) VALUES (?, ?)')->execute([$_SESSION['user_id'], $artistId]);
    echo json_encode(['following' => true]);
}
