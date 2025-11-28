<?php
/**
 * Application Configuration
 */

// Define base path - change this to match your installation directory
// Examples:
// For http://localhost/Farmwebsite/ use: '/Farmwebsite'
// For http://localhost/ use: ''
// For http://yourdomain.com/ use: ''
define('BASE_PATH', '/project1/FarmSatthi');

// Define base URL
define('BASE_URL', ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_PATH);

/**
 * Get full URL for a path
 * @param string $path Path relative to base
 * @return string Full URL
 */
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 * @param string $path Asset path
 * @return string Full asset URL
 */
function asset($path) {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}
?>
