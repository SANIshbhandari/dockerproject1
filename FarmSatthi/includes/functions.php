<?php
/**
 * Core Utility Functions (PDO + PostgreSQL)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get PDO Database Connection
 * Update your PostgreSQL credentials accordingly
 */
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        $host = 'db'; // Docker service name
        $port = '5432';
        $dbname = 'farm_management';
        $user = 'sanish';
        $password = 'sanish123456';

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
        try {
            $conn = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $conn;
}

/**
 * Sanitize user input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $msg;
    }
    return null;
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $class = 'alert-' . $flash['type'];
        echo '<div class="alert ' . $class . '">' . htmlspecialchars($flash['message']) . '</div>';
    }
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Authentication
 */
function authenticateUser($username, $password) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

/**
 * Create session after login
 */
function createSession($userId, $username, $role) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID & role
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Activity logging
 */
function logActivity($action, $module, $description = '') {
    if (!isset($_SESSION['user_id'])) return;

    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare(
            "INSERT INTO activity_log (user_id, username, action, module, description, ip_address)
             VALUES (:user_id, :username, :action, :module, :description, :ip_address)"
        );
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':username' => $_SESSION['username'] ?? 'System',
            ':action' => $action,
            ':module' => $module,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

/**
 * Data isolation (admin vs manager)
 */
function getDataIsolationWhere($tableAlias = '') {
    $role = getCurrentUserRole();
    $userId = getCurrentUserId();
    if ($role === 'admin') return '1=1';
    $col = $tableAlias ? "$tableAlias.created_by" : 'created_by';
    return "$col = $userId";
}

function canAccessRecord($recordUserId) {
    $role = getCurrentUserRole();
    $userId = getCurrentUserId();
    if ($role === 'admin') return true;
    return $userId == $recordUserId;
}

/**
 * Verify record ownership before update/delete
 */
function verifyRecordOwnership($table, $recordId, $redirectUrl = 'index.php') {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT created_by FROM $table WHERE id = :id");
    $stmt->execute([':id' => $recordId]);
    $record = $stmt->fetch();

    if (!$record) {
        setFlashMessage("Record not found.", 'error');
        redirect($redirectUrl);
    }

    if (!canAccessRecord($record['created_by'])) {
        setFlashMessage("You don't have permission to access this record.", 'error');
        redirect($redirectUrl);
    }
}

/**
 * Dashboard statistics
 */
function getUserStatistics() {
    $conn = getDBConnection();
    $where = getDataIsolationWhere();
    $stats = [];

    $tables = ['crops', 'livestock', 'equipment', 'employees', 'expenses', 'inventory'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table WHERE $where");
        $stats[$table] = $stmt->fetch()['count'] ?? 0;
    }

    return $stats;
}

/**
 * Currency formatting
 */
function formatCurrency($amount) {
    return 'रू ' . number_format($amount ?? 0, 2);
}

/**
 * Date formatting
 */
function formatDate($date) {
    if (empty($date)) return '';
    return date('Y-m-d', strtotime($date));
}
?>
