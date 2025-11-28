<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/session.php';

// Destroy session
destroySession();

// Set logout message
session_start();
setFlashMessage("You have been logged out successfully.", 'success');

// Redirect to login page
redirect('login.php');
?>
