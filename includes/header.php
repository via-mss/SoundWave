<?php
// includes/header.php
require_once __DIR__ . '/config.php';
$user = currentUser();
$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>

<!-- Persistent Audio Player -->
<div id="player-bar" class="player-bar">
    <div class="player-inner">
        <div class="player-info">
            <div id="player-cover" class="player-cover player-cover-placeholder"><i class="fas fa-user"></i></div>
            <div>
                <a id="player-title" class="player-title player-link player-link-disabled" href="#">No song playing</a>
                <a id="player-artist" class="player-artist player-link player-link-disabled" href="#">—</a>
            </div>
        </div>
        <div class="player-controls">
            <button id="btn-prev" class="ctrl-btn"><i class="fas fa-step-backward"></i></button>
            <button id="btn-play" class="ctrl-btn play-btn"><i class="fas fa-play"></i></button>
            <button id="btn-next" class="ctrl-btn"><i class="fas fa-step-forward"></i></button>
        </div>
        <div class="player-progress-wrap">
            <span id="time-current">0:00</span>
            <input type="range" id="progress-bar" min="0" max="100" value="0" class="progress-range">
            <span id="time-total">0:00</span>
        </div>
        <div class="player-volume-wrap">
            <i class="fas fa-volume-up"></i>
            <input type="range" id="volume-bar" min="0" max="1" step="0.05" value="0.8" class="volume-range">
        </div>
    </div>
    <audio id="audio-player"></audio>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sw-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand sw-brand" href="<?= SITE_URL ?>/index.php">
            <i class="fas fa-wave-square"></i> SoundWave
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/index.php"><i class="fas fa-home"></i> Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/browse.php"><i class="fas fa-compass"></i> Browse</a></li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/playlists.php"><i class="fas fa-list"></i> Playlists</a></li>
                <?php if ($user && $user['role'] === 'artist'): ?>
                <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/upload.php"><i class="fas fa-upload"></i> Upload</a></li>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/admin.php"><i class="fas fa-user-shield"></i> Admin</a></li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
            <form class="d-flex me-3" action="<?= SITE_URL ?>/browse.php" method="GET">
                <input class="form-control sw-search" type="search" name="q" placeholder="Search songs, artists…" value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>">
                <button class="btn sw-search-btn ms-1" type="submit"><i class="fas fa-search"></i></button>
            </form>
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle sw-user-menu" href="#" data-bs-toggle="dropdown">
                        <?php if (!empty($user['avatar_url']) && $user['avatar_url'] !== 'uploads/covers/default_avatar.png'): ?>
                        <span class="position-relative d-inline-flex" style="width:26px;height:26px;">
                            <img src="<?= SITE_URL ?>/<?= sanitize($user['avatar_url']) ?>" class="sw-avatar-sm" alt="Avatar"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                            <span class="sw-avatar-sm sw-avatar-placeholder d-none"><i class="fas fa-user"></i></span>
                        </span>
                        <?php else: ?>
                        <span class="sw-avatar-sm sw-avatar-placeholder"><i class="fas fa-user"></i></span>
                        <?php endif; ?>
                        <?= sanitize($user['display_name'] ?? $user['username']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end sw-dropdown">
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/php/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link sw-btn-login" href="<?= SITE_URL ?>/login.php">Log In</a></li>
                <li class="nav-item"><a class="nav-link sw-btn-register" href="<?= SITE_URL ?>/register.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="main-content">
