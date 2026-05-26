<?php
// about.php
$pageTitle = 'About';
require_once 'includes/config.php';
require_once 'includes/header.php';
?>
<div class="container mt-5" style="max-width:720px;">
    <p class="section-title">About</p>
    <h1 class="page-title">SoundWave</h1>
    <p class="page-subtitle">A platform built for music lovers and independent artists.</p>

    <div class="sw-card mb-4">
        <h4 style="font-family:var(--font-mono);font-size:1rem;">What is SoundWave?</h4>
        <p style="color:var(--text-muted);line-height:1.7;margin-top:8px;">
            SoundWave is a free music streaming platform that connects independent artists with listeners worldwide.
            Artists can upload their music, manage their profiles, and grow their audience.
            Listeners can discover new music, create playlists, and follow their favourite artists.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="sw-card text-center">
                <div style="font-size:2rem;color:var(--accent2);">🎵</div>
                <div style="font-weight:700;margin-top:8px;">Stream</div>
                <p class="text-muted" style="font-size:0.82rem;">Listen to thousands of tracks, anytime.</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="sw-card text-center">
                <div style="font-size:2rem;color:var(--accent2);">📋</div>
                <div style="font-weight:700;margin-top:8px;">Playlist</div>
                <p class="text-muted" style="font-size:0.82rem;">Build and share custom playlists.</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="sw-card text-center">
                <div style="font-size:2rem;color:var(--accent2);">🚀</div>
                <div style="font-weight:700;margin-top:8px;">Upload</div>
                <p class="text-muted" style="font-size:0.82rem;">Artists share music directly.</p>
            </div>
        </div>
    </div>

    <div class="sw-card">
        <h4 style="font-family:var(--font-mono);font-size:1rem;">Technology Stack</h4>
        <ul style="color:var(--text-muted);line-height:2;margin-top:8px;">
            <li><strong>Frontend:</strong> HTML5, CSS3, Bootstrap 5, JavaScript (ES6+)</li>
            <li><strong>Backend:</strong> PHP 8+</li>
            <li><strong>Database:</strong> MySQL 8</li>
            <li><strong>Security:</strong> CSRF protection, password hashing (bcrypt), prepared statements</li>
        </ul>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
