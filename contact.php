<?php
// contact.php
$pageTitle = 'Contact';
require_once 'includes/config.php';
require_once 'includes/header.php';
?>
<div class="container mt-5" style="max-width:640px;">
    <p class="section-title">Contact</p>
    <h1 class="page-title">Get in Touch</h1>
    <p class="page-subtitle">Have a question or feedback? We'd love to hear from you.</p>

    <div class="sw-card">
        <div id="contact-error" class="alert-sw-error" style="display:none;"></div>
        <div id="contact-ok" class="alert-sw-success" style="display:none;">Thanks for reaching out! We'll be in touch soon.</div>

        <form id="contact-form" novalidate>
            <div class="mb-3">
                <label class="sw-label" for="contact-name">Your Name</label>
                <input class="form-control sw-input" type="text" id="contact-name" required placeholder="Jane Doe">
            </div>
            <div class="mb-3">
                <label class="sw-label" for="contact-email">Email Address</label>
                <input class="form-control sw-input" type="email" id="contact-email" required placeholder="jane@example.com">
            </div>
            <div class="mb-3">
                <label class="sw-label" for="contact-message">Message</label>
                <textarea class="form-control sw-input" id="contact-message" rows="5" required placeholder="Write your message here…"></textarea>
            </div>
            <button type="submit" class="btn-sw-primary"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
