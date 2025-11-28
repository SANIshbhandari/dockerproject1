<?php
/**
 * Farm Management System - Entry Point
 * Redirects users to appropriate page based on authentication status
 */

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth/session.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard
    redirect('dashboard/index.php');
} else {
    // Show landing page for non-logged-in users
    redirect('home.php');
}
?>
