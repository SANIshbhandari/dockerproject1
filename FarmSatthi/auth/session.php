<?php
/**
 * Session Management and Authentication Functions
 */

// Configure secure session parameters BEFORE any session is started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');
}

/**
 * Authenticate user with username and password
 * @param string $username Username
 * @param string $password Password
 * @return array|false User data array or false on failure
 */
function authenticateUser($username, $password) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    if (!$stmt) {
        error_log("Query error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            $stmt->close();
            return $user;
        }
    }
    
    $stmt->close();
    return false;
}

/**
 * Create user session
 * @param int $userId User ID
 * @param string $username Username
 * @param string $role User role
 */
function createSession($userId, $username, $role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if session variables are set
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        destroySession();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Require user to be logged in (redirect to login if not)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /auth/login.php");
        exit();
    }
}

/**
 * Check if user has required permission
 * @param string $requiredRole Required role (admin, manager, viewer)
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'];
    
    // Admin has all permissions
    if ($userRole === 'admin') {
        return true;
    }
    
    // Manager has manager and viewer permissions
    if ($userRole === 'manager' && in_array($requiredRole, ['manager', 'viewer'])) {
        return true;
    }
    
    // Viewer only has viewer permissions
    if ($userRole === 'viewer' && $requiredRole === 'viewer') {
        return true;
    }
    
    return false;
}

/**
 * Check if user can modify data (admin or manager only)
 * @return bool True if user can modify, false otherwise
 */
function canModify() {
    return hasPermission('manager');
}

/**
 * Require specific permission (redirect with error if not authorized)
 * @param string $requiredRole Required role
 */
function requirePermission($requiredRole) {
    if (!hasPermission($requiredRole)) {
        setFlashMessage("You don't have permission to access this page.", 'error');
        header("Location: /dashboard/index.php");
        exit();
    }
}

/**
 * Destroy user session (logout)
 */
function destroySession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Get current user ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 * @return string|null Username or null if not logged in
 */
function getCurrentUsername() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['username'] ?? null;
}

/**
 * Get current user role
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['role'] ?? null;
}
?>
