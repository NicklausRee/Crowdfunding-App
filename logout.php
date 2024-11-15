<?php
// Start the session if it hasn't been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear any other cookies that might have been set
// Add any other cookies that need to be cleared here
setcookie('user_preferences', '', time() - 3600, '/');
setcookie('remember_me', '', time() - 3600, '/');

// Set cache-control headers to prevent caching of sensitive data
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Clear output buffer before redirecting
if (ob_get_length()) {
    ob_end_clean();
}

// Redirect to the homepage
header("Location: index.php");
exit();
?>