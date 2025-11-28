<?php
$pageTitle = 'Add Crop - FarmSaathi';
$currentModule = 'crops';
require_once __DIR__ . '/../includes/header.php';

requirePermission('manager');

$conn = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $crop_name = sanitizeInput($_POST['crop_name'] ?? '');
    $crop_type = sanitizeInput($_POST['crop_type'] ?? '');
    $planting_date = sanitizeInput($_POST['planting_date'] ?? '');
    $harvest_date = sanitizeInput($_POST['harvest_date'] ?? '');
    $area_hectares = sanitizeInput($_POST['area_hectares'] ?? '');
    $expected_yield = sanitizeInput($_POST['expected_yield'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'active');
    
    // Validate inputs
    if ($error = validateRequired($crop_name, 'Crop name')) $errors[] = $error;
    if ($error = validateRequired($crop_type, 'Crop type')) $errors[] = $error;
    if ($error = validateDate($planting_date, 'Planting date')) $errors[] = $error;
    if ($error = validatePositive($area_hectares, 'Area')) $errors[] = $error;
    
    // Insert if no errors
    if (empty($errors)) {
        $createdBy = getCreatedByUserId();
        
        // Check if table uses created_by or user_id
        $columnCheck = $conn->query("SHOW COLUMNS FROM crops LIKE 'created_by'");
        $useCreatedBy = $columnCheck && $columnCheck->num_rows > 0;
        $userColumn = $useCreatedBy ? 'created_by' : 'user_id';
        
        $stmt = $conn->prepare("
            INSERT INTO crops ($userColumn, crop_name, crop_type, planting_date, harvest_date, area_hectares, expected_yield, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("issssdds", $createdBy, $crop_name, $crop_type, $planting_date, $harvest_date, $area_hectares, $expected_yield, $status);
        
            if ($stmt->execute()) {
                $stmt->close();
                // Log activity
                logActivity('create', 'crops', "Added new crop: $crop_name ($crop_type)");
                setFlashMessage("Crop added successfully!", 'success');
                redirect('index.php');
            } else {
                $errors[] = "Failed to add crop: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}
?>

<div class="form-container">
    <div class="form-header">
        <h2>Add New Crop</h2>
        <a href="index.php" class="btn btn-outline">← Back to Crops</a>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="add.php" class="data-form">
        <div class="form-row">
            <div class="form-group">
                <label for="crop_name">Crop Name *</label>
                <input 
                    type="text" 
                    id="crop_name" 
                    name="crop_name" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($crop_name ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="crop_type">Crop Type *</label>
                <input 
                    type="text" 
                    id="crop_type" 
                    name="crop_type" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($crop_type ?? ''); ?>"
                    placeholder="e.g., Wheat, Corn, Rice"
                    required
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="planting_date">Planting Date *</label>
                <input 
                    type="date" 
                    id="planting_date" 
                    name="planting_date" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($planting_date ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="harvest_date">Harvest Date</label>
                <input 
                    type="date" 
                    id="harvest_date" 
                    name="harvest_date" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($harvest_date ?? ''); ?>"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="expected_yield">Expected Yield (kg)</label>
                <input 
                    type="number" 
                    id="expected_yield" 
                    name="expected_yield" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($expected_yield ?? ''); ?>"
                    step="0.01"
                    min="0"
                    placeholder="Expected yield in kg"
                >
            </div>

            <div class="form-group">
                <label for="area_hectares">Area (Hectares) *</label>
                <input 
                    type="number" 
                    id="area_hectares" 
                    name="area_hectares" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($area_hectares ?? ''); ?>"
                    step="0.01"
                    min="0"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status" class="form-control" required>
                <option value="active" <?php echo ($status ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="harvested" <?php echo ($status ?? '') === 'harvested' ? 'selected' : ''; ?>>Harvested</option>
                <option value="failed" <?php echo ($status ?? '') === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Crop</button>
            <a href="index.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
