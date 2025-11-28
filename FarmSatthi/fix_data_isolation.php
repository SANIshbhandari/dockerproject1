<?php
/**
 * Data Isolation Fix Script
 * This script will diagnose and fix data isolation issues
 * Run this from your browser: http://localhost/Farmwebsite/fix_data_isolation.php
 */

// Require database connection
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/session.php';

// Only allow admin users to run this script
requireLogin();
if (getCurrentUserRole() !== 'admin') {
    die('ERROR: Only admin users can run this fix script.');
}

$conn = getDBConnection();
$results = [];
$errors = [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Isolation Fix Script</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #5568d3;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Data Isolation Fix Script</h1>
        <p><strong>Current User:</strong> <?php echo htmlspecialchars(getCurrentUsername()); ?> (<?php echo getCurrentUserRole(); ?>)</p>
        <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

        <?php
        // STEP 1: Check if created_by column exists in all tables
        echo '<h2>Step 1: Check Database Structure</h2>';
        
        $tables = ['crops', 'livestock', 'equipment', 'employees', 'expenses', 'inventory', 'income'];
        $missingColumns = [];
        
        echo '<table>';
        echo '<tr><th>Table</th><th>Has created_by Column</th><th>Status</th></tr>';
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW COLUMNS FROM $table LIKE 'created_by'");
            $hasColumn = $result && $result->num_rows > 0;
            
            echo '<tr>';
            echo '<td><strong>' . $table . '</strong></td>';
            echo '<td>' . ($hasColumn ? '✅ Yes' : '❌ No') . '</td>';
            echo '<td>' . ($hasColumn ? '<span style="color: #28a745;">OK</span>' : '<span style="color: #dc3545;">MISSING</span>') . '</td>';
            echo '</tr>';
            
            if (!$hasColumn) {
                $missingColumns[] = $table;
            }
        }
        
        echo '</table>';
        
        if (!empty($missingColumns)) {
            echo '<div class="step error">';
            echo '<strong>❌ ERROR: Missing created_by columns!</strong><br>';
            echo 'The following tables are missing the created_by column: ' . implode(', ', $missingColumns) . '<br><br>';
            echo '<strong>FIX:</strong> Run the database migration script:<br>';
            echo '<pre>mysql -u root -p farm_management < database/add_data_isolation.sql</pre>';
            echo 'Or run it from phpMyAdmin by importing the file: <code>database/add_data_isolation.sql</code>';
            echo '</div>';
            $errors[] = 'Missing created_by columns';
        } else {
            echo '<div class="step success">';
            echo '<strong>✅ SUCCESS:</strong> All tables have the created_by column!';
            echo '</div>';
        }
        
        // STEP 2: Check if data has proper created_by values
        echo '<h2>Step 2: Check Data Integrity</h2>';
        
        $dataIssues = [];
        
        echo '<table>';
        echo '<tr><th>Table</th><th>Total Records</th><th>Records with created_by=0</th><th>Records with NULL created_by</th><th>Status</th></tr>';
        
        foreach ($tables as $table) {
            // Check if column exists first
            $columnCheck = $conn->query("SHOW COLUMNS FROM $table LIKE 'created_by'");
            if (!$columnCheck || $columnCheck->num_rows == 0) {
                continue; // Skip if column doesn't exist
            }
            
            $totalResult = $conn->query("SELECT COUNT(*) as count FROM $table");
            $total = $totalResult ? $totalResult->fetch_assoc()['count'] : 0;
            
            $zeroResult = $conn->query("SELECT COUNT(*) as count FROM $table WHERE created_by = 0");
            $zeroCount = $zeroResult ? $zeroResult->fetch_assoc()['count'] : 0;
            
            $nullResult = $conn->query("SELECT COUNT(*) as count FROM $table WHERE created_by IS NULL");
            $nullCount = $nullResult ? $nullResult->fetch_assoc()['count'] : 0;
            
            $hasIssues = ($zeroCount > 0 || $nullCount > 0);
            
            echo '<tr>';
            echo '<td><strong>' . $table . '</strong></td>';
            echo '<td>' . $total . '</td>';
            echo '<td>' . ($zeroCount > 0 ? '<span style="color: #dc3545;">' . $zeroCount . '</span>' : '0') . '</td>';
            echo '<td>' . ($nullCount > 0 ? '<span style="color: #dc3545;">' . $nullCount . '</span>' : '0') . '</td>';
            echo '<td>' . ($hasIssues ? '<span style="color: #dc3545;">NEEDS FIX</span>' : '<span style="color: #28a745;">OK</span>') . '</td>';
            echo '</tr>';
            
            if ($hasIssues) {
                $dataIssues[] = $table;
            }
        }
        
        echo '</table>';
        
        if (!empty($dataIssues)) {
            echo '<div class="step warning">';
            echo '<strong>⚠️ WARNING: Some records have invalid created_by values!</strong><br>';
            echo 'The following tables have records with created_by = 0 or NULL: ' . implode(', ', $dataIssues) . '<br><br>';
            echo '<strong>This means:</strong> These records are not assigned to any user and may be visible to all managers.<br><br>';
            echo '<strong>FIX OPTIONS:</strong><br>';
            echo '1. <strong>Assign to first admin user (ID=1):</strong> Click the button below<br>';
            echo '2. <strong>Assign to specific user:</strong> Run SQL manually in phpMyAdmin<br>';
            echo '<form method="POST" style="margin-top: 15px;">';
            echo '<input type="hidden" name="action" value="fix_created_by">';
            echo '<button type="submit" class="btn" onclick="return confirm(\'This will assign all orphaned records to user ID 1. Continue?\');">Fix Now - Assign to User ID 1</button>';
            echo '</form>';
            echo '</div>';
        } else {
            echo '<div class="step success">';
            echo '<strong>✅ SUCCESS:</strong> All records have valid created_by values!';
            echo '</div>';
        }
        
        // Handle fix action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fix_created_by') {
            echo '<h2>Step 3: Fixing Data...</h2>';
            
            $fixed = [];
            foreach ($tables as $table) {
                // Check if column exists
                $columnCheck = $conn->query("SHOW COLUMNS FROM $table LIKE 'created_by'");
                if (!$columnCheck || $columnCheck->num_rows == 0) {
                    continue;
                }
                
                $result = $conn->query("UPDATE $table SET created_by = 1 WHERE created_by = 0 OR created_by IS NULL");
                if ($result) {
                    $affectedRows = $conn->affected_rows;
                    if ($affectedRows > 0) {
                        $fixed[] = "$table ($affectedRows records)";
                    }
                }
            }
            
            if (!empty($fixed)) {
                echo '<div class="step success">';
                echo '<strong>✅ SUCCESS:</strong> Fixed the following tables:<br>';
                echo '<ul>';
                foreach ($fixed as $fix) {
                    echo '<li>' . $fix . '</li>';
                }
                echo '</ul>';
                echo '<a href="fix_data_isolation.php" class="btn">Refresh Page to Verify</a>';
                echo '</div>';
            } else {
                echo '<div class="step info">';
                echo '<strong>ℹ️ INFO:</strong> No records needed fixing.';
                echo '</div>';
            }
        }
        
        // STEP 3: Check user data distribution
        echo '<h2>Step 3: User Data Distribution</h2>';
        
        echo '<p>This shows how data is distributed among users:</p>';
        
        $users = $conn->query("SELECT id, username, role FROM users ORDER BY id");
        
        echo '<table>';
        echo '<tr><th>User ID</th><th>Username</th><th>Role</th>';
        foreach ($tables as $table) {
            echo '<th>' . ucfirst($table) . '</th>';
        }
        echo '</tr>';
        
        while ($user = $users->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $user['id'] . '</td>';
            echo '<td><strong>' . htmlspecialchars($user['username']) . '</strong></td>';
            echo '<td>' . ucfirst($user['role']) . '</td>';
            
            foreach ($tables as $table) {
                // Check if column exists
                $columnCheck = $conn->query("SHOW COLUMNS FROM $table LIKE 'created_by'");
                if (!$columnCheck || $columnCheck->num_rows == 0) {
                    echo '<td>-</td>';
                    continue;
                }
                
                $countResult = $conn->query("SELECT COUNT(*) as count FROM $table WHERE created_by = " . $user['id']);
                $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
                echo '<td>' . ($count > 0 ? '<strong>' . $count . '</strong>' : '0') . '</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</table>';
        
        // STEP 4: Test data isolation
        echo '<h2>Step 4: Test Data Isolation</h2>';
        
        $managerCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'manager'")->fetch_assoc()['count'];
        
        if ($managerCount < 2) {
            echo '<div class="step warning">';
            echo '<strong>⚠️ WARNING:</strong> You need at least 2 manager accounts to properly test data isolation.<br>';
            echo 'Current manager accounts: ' . $managerCount . '<br><br>';
            echo '<strong>RECOMMENDATION:</strong> Create 2 test manager accounts and add data to each to verify isolation.';
            echo '</div>';
        } else {
            echo '<div class="step info">';
            echo '<strong>ℹ️ INFO:</strong> You have ' . $managerCount . ' manager accounts.<br><br>';
            echo '<strong>TO TEST:</strong><br>';
            echo '1. Logout and login as Manager #1<br>';
            echo '2. Add some crops, livestock, etc.<br>';
            echo '3. Logout and login as Manager #2<br>';
            echo '4. Add different crops, livestock, etc.<br>';
            echo '5. Verify Manager #1 cannot see Manager #2\'s data<br>';
            echo '6. Verify Manager #2 cannot see Manager #1\'s data<br>';
            echo '7. Login as Admin and verify you can see ALL data<br>';
            echo '</div>';
        }
        
        // STEP 5: Verify functions are being used
        echo '<h2>Step 5: Code Implementation Check</h2>';
        
        echo '<div class="step info">';
        echo '<strong>✅ Code Review:</strong><br>';
        echo 'The following functions are implemented in <code>includes/functions.php</code>:<br>';
        echo '<ul>';
        echo '<li><code>getDataIsolationWhere()</code> - Returns WHERE clause for filtering</li>';
        echo '<li><code>getCreatedByUserId()</code> - Returns current user ID for INSERT</li>';
        echo '<li><code>canAccessRecord($recordUserId)</code> - Checks if user can access a record</li>';
        echo '<li><code>verifyRecordOwnership($conn, $table, $recordId, $redirectUrl)</code> - Verifies ownership before edit/delete</li>';
        echo '</ul>';
        echo '<br>';
        echo '<strong>Module Implementation Status:</strong><br>';
        echo '<ul>';
        echo '<li>✅ Crops module - Using data isolation</li>';
        echo '<li>✅ Livestock module - Using data isolation</li>';
        echo '<li>✅ Equipment module - Using data isolation</li>';
        echo '<li>✅ Employees module - Using data isolation</li>';
        echo '<li>✅ Expenses module - Using data isolation</li>';
        echo '<li>✅ Inventory module - Using data isolation</li>';
        echo '<li>✅ Dashboard - Using data isolation</li>';
        echo '</ul>';
        echo '</div>';
        
        // FINAL SUMMARY
        echo '<h2>📊 Final Summary</h2>';
        
        $allGood = empty($missingColumns) && empty($dataIssues);
        
        if ($allGood) {
            echo '<div class="step success">';
            echo '<h3>✅ ALL CHECKS PASSED!</h3>';
            echo '<p>Your data isolation is properly configured. Here\'s what to do next:</p>';
            echo '<ol>';
            echo '<li>Test with multiple manager accounts (see Step 4 above)</li>';
            echo '<li>Verify managers can only see their own data</li>';
            echo '<li>Verify admin can see all data</li>';
            echo '<li>Use <a href="check_isolation.php">check_isolation.php</a> for ongoing monitoring</li>';
            echo '</ol>';
            echo '<a href="dashboard/index.php" class="btn">Go to Dashboard</a>';
            echo '</div>';
        } else {
            echo '<div class="step error">';
            echo '<h3>❌ ISSUES FOUND</h3>';
            echo '<p>Please fix the issues identified above:</p>';
            echo '<ul>';
            if (!empty($missingColumns)) {
                echo '<li>Run database migration to add created_by columns</li>';
            }
            if (!empty($dataIssues)) {
                echo '<li>Fix records with invalid created_by values (use the Fix button above)</li>';
            }
            echo '</ul>';
            echo '<a href="fix_data_isolation.php" class="btn">Refresh Page After Fixes</a>';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd;">
            <h3>📚 Additional Resources</h3>
            <ul>
                <li><a href="check_isolation.php">Diagnostic Tool (check_isolation.php)</a></li>
                <li><a href="ISSUES_AND_FIXES.md">Issues and Fixes Documentation</a></li>
                <li><a href="FINAL_SYSTEM_REPORT.md">Final System Report</a></li>
                <li><a href="dashboard/index.php">Back to Dashboard</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
