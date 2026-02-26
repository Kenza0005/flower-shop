<?php
require_once 'includes/config.php';

// Preserve security mode
$mode = $_SESSION['security_mode'] ?? 'vulnerable';

// Destroy session
session_unset();
session_destroy();

// Start a new session for the mode
session_start();
$_SESSION['security_mode'] = $mode;

// Redirect to home
header("Location: index.php");
exit();
?>