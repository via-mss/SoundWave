<?php
// php/add_to_playlist.php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Not logged in.']); exit; }

$pid    = (int)($_POST['playlist_id'] ?? 0);
$songId = (int)($_POST['song_id'] ?? 0);

if (!$pid || !$songId) { echo json_encode(['error' => 'Invalid input.']); exit; }

$pdo  = getDB();
// Verify playlist belongs to user
$stmt = $pdo->prepare('SELECT id FROM playlists WHERE id = ? AND user_id = ?');
$stmt->execute([$pid, $_SESSION['user_id']]);
if (!$stmt->fetch()) { echo json_encode(['error' => 'Playlist not found.']); exit; }

try {
    $pos = $pdo->prepare('SELECT COUNT(*) FROM playlist_songs WHERE playlist_id = ?');
    $pos->execute([$pid]);
    $position = (int)$pos->fetchColumn();

    $pdo->prepare('INSERT INTO playlist_songs (playlist_id, song_id, position) VALUES (?, ?, ?)')
        ->execute([$pid, $songId, $position]);
    echo json_encode(['message' => 'Song added to playlist!']);
} catch (PDOException $e) {
    echo json_encode(['message' => 'Song already in playlist.']);
}
