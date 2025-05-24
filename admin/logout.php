<?php
require_once '../config/database.php';
require_once 'auth.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['admin_id'])) {
    try {
        logActivity($db, 'logout', 'admin', $_SESSION['admin_id'], 'User logged out');
    } catch (PDOException $e) {
        error_log("Error logging logout: " . $e->getMessage());
    }
}

// Destroy session
session_start();
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirect to login page
header('Location: login.php');
exit();
?>
