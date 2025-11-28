<?php
/**
 * CSRF Token Helper Functions
 */

/**
 * Output CSRF token hidden input field
 */
function csrfField() {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify CSRF token from POST request
 * @return bool True if valid, false otherwise
 */
function verifyCsrfToken() {
    $token = $_POST['csrf_token'] ?? '';
    return verifyCSRFToken($token);
}

/**
 * Require valid CSRF token or die
 */
function requireCsrfToken() {
    if (!verifyCsrfToken()) {
        http_response_code(403);
        die('Invalid CSRF token. Please try again.');
    }
}
?>
