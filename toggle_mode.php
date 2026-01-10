<?php
require_once 'includes/config.php';

if (isset($_SESSION['security_mode'])) {
    $_SESSION['security_mode'] = ($_SESSION['security_mode'] === 'secure') ? 'vulnerable' : 'secure';
} else {
    $_SESSION['security_mode'] = 'secure';
}

// Redirect back to the referring page, or index.php if not available
$referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// If we are on a mode-specific page, try to switch the path
if ($_SESSION['security_mode'] === 'secure') {
    $referrer = str_replace('/vulnerable/', '/secure/', $referrer);
} else {
    $referrer = str_replace('/secure/', '/vulnerable/', $referrer);
}

header("Location: $referrer");
exit();
