<?php
/**
 * Quick Verification Script
 * Shows exactly what's happening with data isolation
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth/session.php';

requireLogin();

$conn = getDBConnection();
$currentUserId = getCurrentUserId();
$currentUserRole = getCurrentUserRole();
$isolationWhere = getDataIsolationWhere();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Isolation Verification</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        h1 { color: #2c3e50; }
        .section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Data Isolation Verification</h1>
    
    <div class="section">
        <h2>Current User Info</h2>
        <p><strong>User ID:</strong> <?php echo $currentUserId; ?></p>
        <p><strong>Username:</strong> <?php echo getCurrentUsername(); ?></p>
        <p><strong>Role:</strong> <?php echo ucfirst($currentUserRole); ?></p>
        <p><strong>Isolation WHERE:</strong> <code><?php echo htmlspecialchars($isolationWhere); ?></code></p>
    </div>

    <?php
    // Check livestock table structure
    $columnCheck = $conn->query("SHOW COLUMNS FROM livestock LIKE 'created_by'");
    $hasCreatedBy = $columnCheck && $columnCheck->num_rows > 0;
    ?>

    <div class="section <?php echo $hasCreatedBy ? 'success' : 'error'; ?>">
        <h2>Livestock Table Structure</h2>
        <?php if ($hasCreatedBy): ?>
            <p>✅ <strong>GOOD:</strong> The livestock table has the <code>created_by</code> column.</p>
        <?php else: ?>
            <p>❌ <strong>PROBLEM:</strong> The livestock table is missing the <code>created_by</code> column!</p>
            <p><strong>This is why data isolation isn't working.</strong></p>
            <p><strong>FIX:</strong> Run <a href="fix_data_isolation.php">fix_data_isolation.php</a></p>
        <?php endif; ?>
    </div>

    <?php if ($hasCreatedBy): ?>
    <div class="section">
        <h2>Livestock Data Analysis</h2>
        
        <?php
        // Get all livestock with their created_by values
        $allLivestock = $conn->query("SELECT id, animal_type, breed, count, created_by FROM livestock ORDER BY id");
        
        // Count by user
        $totalLivestock = $conn->query("SELECT COUNT(*) as count FROM livestock")->fetch_assoc()['count'];
        $visibleLivestock = $conn->query("SELECT COUNT(*) as count FROM livestock WHERE $isolationWhere")->fetch_assoc()['count'];
        $totalCount = $conn->query("SELECT COALESCE(SUM(count), 0) as count FROM livestock")->fetch_assoc()['count'];
        $visibleCount = $conn->query("SELECT COALESCE(SUM(count), 0) as count FROM livestock WHERE $isolationWhere")->fetch_assoc()['count'];
        ?>
        
        <p><strong>Total livestock records in database:</strong> <?php echo $totalLivestock; ?></p>
        <p><strong>Livestock records visible to you:</strong> <?php echo $visibleLivestock; ?></p>
        <p><strong>Total animal count in database:</strong> <?php echo $totalCount; ?></p>
        <p><strong>Animal count visible to you (shown on dashboard):</strong> <strong style="font-size: 1.2em; color: #dc3545;"><?php echo $visibleCount; ?></strong></p>
        
        <?php if ($totalLivestock > 0): ?>
        <h3>All Livestock Records:</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Animal Type</th>
                    <th>Breed</th>
                    <th>Count</th>
                    <th>Created By (User ID)</th>
                    <th>Visible to You?</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $allLivestock->fetch_assoc()): 
                    $isVisible = ($currentUserRole === 'admin' || $row['created_by'] == $currentUserId);
                    $bgColor = $isVisible ? '#d4edda' : '#f8d7da';
                ?>
                <tr style="background: <?php echo $bgColor; ?>;">
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['animal_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['breed']); ?></td>
                    <td><strong><?php echo $row['count']; ?></strong></td>
                    <td><?php echo $row['created_by']; ?></td>
                    <td><?php echo $isVisible ? '✅ YES' : '❌ NO (but you can see it!)'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php
        // Check for problematic records
        $zeroCreatedBy = $conn->query("SELECT COUNT(*) as count FROM livestock WHERE created_by = 0")->fetch_assoc()['count'];
        $nullCreatedBy = $conn->query("SELECT COUNT(*) as count FROM livestock WHERE created_by IS NULL")->fetch_assoc()['count'];
        ?>
        
        <?php if ($zeroCreatedBy > 0 || $nullCreatedBy > 0): ?>
        <div class="section error">
            <h3>⚠️ PROBLEM FOUND!</h3>
            <p><strong>Records with created_by = 0:</strong> <?php echo $zeroCreatedBy; ?></p>
            <p><strong>Records with created_by = NULL:</strong> <?php echo $nullCreatedBy; ?></p>
            <p><strong>Why this is a problem:</strong> These records are not assigned to any user, so the isolation WHERE clause might not filter them correctly.</p>
            <p><strong>Solution:</strong> Run the fix script to assign these records to a user.</p>
            <a href="fix_data_isolation.php" class="btn">Fix Now</a>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>What the Dashboard Query Returns</h2>
        <?php
        $dashboardQuery = "SELECT COALESCE(SUM(count), 0) as count FROM livestock WHERE $isolationWhere";
        $dashboardResult = $conn->query($dashboardQuery);
        $dashboardCount = $dashboardResult->fetch_assoc()['count'];
        ?>
        <p><strong>Query:</strong> <code><?php echo htmlspecialchars($dashboardQuery); ?></code></p>
        <p><strong>Result:</strong> <strong style="font-size: 1.5em; color: #dc3545;"><?php echo $dashboardCount; ?></strong> animals</p>
        
        <?php if ($currentUserRole === 'manager' && $dashboardCount > 0 && $visibleLivestock == 0): ?>
        <div class="section error">
            <h3>❌ DATA ISOLATION NOT WORKING!</h3>
            <p>You are a <strong>manager</strong> and should only see your own livestock.</p>
            <p>However, the dashboard shows <strong><?php echo $dashboardCount; ?></strong> animals.</p>
            <p>This means you're seeing livestock from other users!</p>
            <p><strong>Root Cause:</strong> The livestock records have <code>created_by = 0</code> or <code>NULL</code>, which makes them visible to everyone.</p>
            <p><strong>Fix:</strong> Run the fix script to properly assign ownership.</p>
            <a href="fix_data_isolation.php" class="btn">Fix Data Isolation Now</a>
        </div>
        <?php elseif ($currentUserRole === 'manager' && $dashboardCount == 0): ?>
        <div class="section success">
            <h3>✅ DATA ISOLATION WORKING!</h3>
            <p>You are a manager and the dashboard correctly shows 0 livestock (because you haven't added any yet).</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="section">
        <h2>🔧 Actions</h2>
        <a href="fix_data_isolation.php" class="btn">Run Full Fix Script</a>
        <a href="check_isolation.php" class="btn">Run Diagnostic Tool</a>
        <a href="dashboard/index.php" class="btn">Back to Dashboard</a>
        <a href="livestock/index.php" class="btn">View Livestock Page</a>
    </div>

    <div class="section">
        <h2>📚 Understanding the Issue</h2>
        <h3>How Data Isolation Should Work:</h3>
        <ol>
            <li>Each livestock record has a <code>created_by</code> column storing the user ID who created it</li>
            <li>When a manager views data, the query adds: <code>WHERE created_by = <?php echo $currentUserId; ?></code></li>
            <li>This filters out records from other users</li>
            <li>Admins see all data (no filter applied)</li>
        </ol>
        
        <h3>Why It's Not Working:</h3>
        <ul>
            <li>Livestock records have <code>created_by = 0</code> or <code>NULL</code></li>
            <li>The WHERE clause <code>created_by = <?php echo $currentUserId; ?></code> doesn't match</li>
            <li>But the records still appear because the query might be using <code>1=1</code> for admins or the column doesn't exist</li>
        </ul>
        
        <h3>The Fix:</h3>
        <ol>
            <li>Ensure <code>created_by</code> column exists in all tables</li>
            <li>Update all records to have valid <code>created_by</code> values</li>
            <li>Test with multiple manager accounts</li>
        </ol>
    </div>

</body>
</html>
<?php $conn->close(); ?>
