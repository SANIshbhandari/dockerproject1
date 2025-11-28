<?php
/**
 * Data Isolation Diagnostic Tool
 * This page helps diagnose why crops aren't showing on dashboard
 */

require_once __DIR__ . '/includes/header.php';
requireLogin();

$conn = getDBConnection();
$currentUserId = getCurrentUserId();
$currentUserRole = getCurrentUserRole();
$isolationWhere = getDataIsolationWhere();

?>

<div class="module-header">
    <h2>🔍 Data Isolation Diagnostic</h2>
    <p>This page helps diagnose data visibility issues</p>
</div>

<div style="max-width: 1200px; margin: 30px auto;">
    
    <!-- Current User Info -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h3>👤 Current User Information</h3>
        <table class="data-table">
            <tr>
                <td><strong>User ID:</strong></td>
                <td><?php echo $currentUserId; ?></td>
            </tr>
            <tr>
                <td><strong>Username:</strong></td>
                <td><?php echo getCurrentUsername(); ?></td>
            </tr>
            <tr>
                <td><strong>Role:</strong></td>
                <td><?php echo ucfirst($currentUserRole); ?></td>
            </tr>
            <tr>
                <td><strong>Isolation WHERE Clause:</strong></td>
                <td><code><?php echo htmlspecialchars($isolationWhere); ?></code></td>
            </tr>
        </table>
    </div>

    <!-- Database Structure Check -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h3>🗄️ Database Structure Check</h3>
        <?php
        $tables = ['crops', 'livestock', 'equipment', 'employees', 'expenses', 'inventory'];
        echo '<table class="data-table">';
        echo '<thead><tr><th>Table</th><th>Has created_by Column</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW COLUMNS FROM $table LIKE 'created_by'");
            $hasColumn = $result && $result->num_rows > 0;
            $status = $hasColumn ? '✅ OK' : '❌ Missing';
            $color = $hasColumn ? '#28a745' : '#dc3545';
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>" . ($hasColumn ? 'Yes' : 'No') . "</td>";
            echo "<td style='color: $color; font-weight: bold;'>$status</td>";
            echo "</tr>";
        }
        
        echo '</tbody></table>';
        
        $allHaveColumn = true;
        foreach ($tables as $table) {
            $result = $conn->query("SHOW COLUMNS FROM $table LIKE 'created_by'");
            if (!$result || $result->num_rows == 0) {
                $allHaveColumn = false;
                break;
            }
        }
        
        if (!$allHaveColumn) {
            echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; margin-top: 15px; border-left: 4px solid #dc3545;">';
            echo '<strong>⚠️ Migration Required!</strong><br>';
            echo 'Run this command to add data isolation:<br>';
            echo '<code style="background: #fff; padding: 5px 10px; border-radius: 3px; display: inline-block; margin-top: 5px;">';
            echo 'mysql -u root -p farm_management &lt; database/add_data_isolation.sql';
            echo '</code>';
            echo '</div>';
        }
        ?>
    </div>

    <!-- Crops Data Analysis -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h3>🌾 Crops Data Analysis</h3>
        
        <?php
        // Check if created_by column exists
        $columnCheck = $conn->query("SHOW COLUMNS FROM crops LIKE 'created_by'");
        $hasCreatedBy = $columnCheck && $columnCheck->num_rows > 0;
        
        if (!$hasCreatedBy) {
            echo '<div style="background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;">';
            echo '<strong>❌ Error:</strong> The crops table does not have a created_by column. Please run the migration.';
            echo '</div>';
        } else {
            // Total crops in database
            $totalResult = $conn->query("SELECT COUNT(*) as count FROM crops");
            $totalCrops = $totalResult->fetch_assoc()['count'];
            
            // Crops visible to current user
            $visibleResult = $conn->query("SELECT COUNT(*) as count FROM crops WHERE $isolationWhere");
            $visibleCrops = $visibleResult->fetch_assoc()['count'];
            
            // Active crops visible to current user
            $activeResult = $conn->query("SELECT COUNT(*) as count FROM crops WHERE status = 'active' AND $isolationWhere");
            $activeCrops = $activeResult->fetch_assoc()['count'];
            
            // Crops by user
            $byUserResult = $conn->query("SELECT created_by, COUNT(*) as count FROM crops GROUP BY created_by");
            
            echo '<table class="data-table">';
            echo '<tr><td><strong>Total Crops in Database:</strong></td><td>' . $totalCrops . '</td></tr>';
            echo '<tr><td><strong>Crops Visible to You:</strong></td><td>' . $visibleCrops . '</td></tr>';
            echo '<tr><td><strong>Active Crops Visible to You:</strong></td><td style="font-size: 1.2em; color: #28a745;"><strong>' . $activeCrops . '</strong></td></tr>';
            echo '</table>';
            
            echo '<h4 style="margin-top: 20px;">Crops by User:</h4>';
            echo '<table class="data-table">';
            echo '<thead><tr><th>User ID</th><th>Username</th><th>Crop Count</th><th>Is You?</th></tr></thead>';
            echo '<tbody>';
            
            while ($row = $byUserResult->fetch_assoc()) {
                $userId = $row['created_by'];
                $count = $row['count'];
                $userInfo = $conn->query("SELECT username FROM users WHERE id = $userId");
                $username = $userInfo && $userInfo->num_rows > 0 ? $userInfo->fetch_assoc()['username'] : 'Unknown';
                $isYou = $userId == $currentUserId ? '✅ Yes' : '';
                $highlight = $userId == $currentUserId ? 'background: #d4edda;' : '';
                
                echo "<tr style='$highlight'>";
                echo "<td>$userId</td>";
                echo "<td>$username</td>";
                echo "<td><strong>$count</strong></td>";
                echo "<td>$isYou</td>";
                echo "</tr>";
            }
            
            echo '</tbody></table>';
            
            // Show recent crops
            echo '<h4 style="margin-top: 20px;">Recent Crops (Last 5):</h4>';
            $recentResult = $conn->query("SELECT id, crop_name, created_by, status, planting_date FROM crops ORDER BY id DESC LIMIT 5");
            
            if ($recentResult && $recentResult->num_rows > 0) {
                echo '<table class="data-table">';
                echo '<thead><tr><th>ID</th><th>Crop Name</th><th>Owner ID</th><th>Status</th><th>Planting Date</th><th>Visible to You?</th></tr></thead>';
                echo '<tbody>';
                
                while ($row = $recentResult->fetch_assoc()) {
                    $visible = ($currentUserRole === 'admin' || $row['created_by'] == $currentUserId) ? '✅ Yes' : '❌ No';
                    $highlight = ($currentUserRole === 'admin' || $row['created_by'] == $currentUserId) ? 'background: #d4edda;' : 'background: #f8d7da;';
                    
                    echo "<tr style='$highlight'>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['crop_name']) . "</td>";
                    echo "<td>" . $row['created_by'] . "</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "<td>" . $row['planting_date'] . "</td>";
                    echo "<td><strong>$visible</strong></td>";
                    echo "</tr>";
                }
                
                echo '</tbody></table>';
            } else {
                echo '<p style="color: #666;">No crops found in database.</p>';
            }
        }
        ?>
    </div>

    <!-- Recommendations -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); color: white;">
        <h3 style="color: white; margin-top: 0;">💡 Recommendations</h3>
        
        <?php if (!$allHaveColumn): ?>
        <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 6px; margin-bottom: 15px;">
            <strong>1. Run Database Migration</strong>
            <p style="margin: 5px 0 0 0;">The created_by column is missing. Run the migration script to add data isolation support.</p>
        </div>
        <?php endif; ?>
        
        <?php if ($hasCreatedBy && $visibleCrops == 0 && $totalCrops > 0): ?>
        <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 6px; margin-bottom: 15px;">
            <strong>2. Assign Crops to Your User</strong>
            <p style="margin: 5px 0 0 0;">There are crops in the database but none are assigned to you. Run this SQL:</p>
            <code style="background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 3px; display: block; margin-top: 5px;">
                UPDATE crops SET created_by = <?php echo $currentUserId; ?> WHERE created_by = 0 OR created_by IS NULL;
            </code>
        </div>
        <?php endif; ?>
        
        <?php if ($hasCreatedBy && $totalCrops == 0): ?>
        <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 6px; margin-bottom: 15px;">
            <strong>3. Add Your First Crop</strong>
            <p style="margin: 5px 0 0 0;">No crops in database yet. <a href="crops/add.php" style="color: white; text-decoration: underline;">Click here to add a crop</a></p>
        </div>
        <?php endif; ?>
        
        <?php if ($hasCreatedBy && $activeCrops > 0): ?>
        <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 6px;">
            <strong>✅ Everything Looks Good!</strong>
            <p style="margin: 5px 0 0 0;">You have <?php echo $activeCrops; ?> active crop(s) visible on your dashboard.</p>
        </div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="dashboard/index.php" class="btn btn-primary">← Back to Dashboard</a>
        <a href="crops/index.php" class="btn btn-secondary">View All Crops</a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
