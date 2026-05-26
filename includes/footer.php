<?php // includes/footer.php ?>
</main><!-- end .main-content -->

<footer class="sw-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5><i class="fas fa-wave-square"></i> SoundWave</h5>
                <p class="text-muted small">Your music, your world. Stream and share music freely.</p>
            </div>
            <div class="col-md-4">
                <h6>Navigation</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= SITE_URL ?>/browse.php">Browse</a></li>
                    <li><a href="<?= SITE_URL ?>/about.php">About</a></li>
                    <li><a href="<?= SITE_URL ?>/contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Account</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= SITE_URL ?>/register.php">Sign Up</a></li>
                    <li><a href="<?= SITE_URL ?>/login.php">Log In</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <p class="text-center text-muted small">&copy; <?= date('Y') ?> SoundWave. Built for Web Technologies course.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/js/player.js"></script>
<script src="<?= SITE_URL ?>/js/app.js"></script>
</body>
</html>
