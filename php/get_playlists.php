<?php
// php/get_playlists.php
require_once '../includes/config.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['playlists' => []]); exit; }
$pdo   = getDB();
$stmt  = $pdo->prepare('SELECT id, name FROM playlists WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
echo json_encode(['playlists' => $stmt->fetchAll()]);
