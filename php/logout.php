<?php
// php/logout.php
require_once '../includes/config.php';
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
header('Location: ' . SITE_URL . '/index.php');
exit;
