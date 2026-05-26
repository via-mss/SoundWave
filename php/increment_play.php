<?php
// php/increment_play.php
require_once '../includes/config.php';
$id = (int)($_GET['id'] ?? 0);
if ($id) getDB()->prepare('UPDATE songs SET play_count = play_count + 1 WHERE id = ?')->execute([$id]);
http_response_code(204);
