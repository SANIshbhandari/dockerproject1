<?php
/**
 * Database Configuration and Connection Handler
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'farm_management');

// Global connection variable
$conn = null;

/**
 * Get database connection
 * @return mysqli|null Returns MySQLi connection object or null on failure
 */
function getDBConnection() {
    global $conn;
    
    // Return existing connection if available
    if ($conn !== null && $conn->ping()) {
        return $conn;
    }
    
    // Create new connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database Connection Failed: " . $conn->connect_error);
        die("Database connection failed. Please contact the administrator.");
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Close database connection
 * @param mysqli $connection The connection to close
 */
function closeDBConnection($connection) {
    if ($connection !== null && $connection instanceof mysqli) {
        $connection->close();
    }
}

/**
 * Execute a prepared statement query
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types (i, d, s, b)
 * @param array $params Parameters to bind
 * @return mysqli_result|bool Query result or false on failure
 */
function executeQuery($query, $types = "", $params = []) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Query Preparation Failed: " . $conn->error);
        return false;
    }
    
    // Bind parameters if provided
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    // Execute query
    if (!$stmt->execute()) {
        error_log("Query Execution Failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    // Get result
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

// Initialize connection
getDBConnection();
?>
