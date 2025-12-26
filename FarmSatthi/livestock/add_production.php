<?php
$pageTitle = 'Add Production Record - FarmSaathi';
$currentModule = 'livestock';
require_once __DIR__ . '/../includes/header.php';

requirePermission('manager');

$conn = getDBConnection();
$errors = [];
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage("Invalid livestock ID.", 'error');
    redirect('index.php');
}

// Verify and get animal
verifyRecordOwnership($conn, 'livestock', $id, 'index.php');
$isolationWhere = getDataIsolationWhere();
$stmt = $conn->prepare("SELECT * FROM livestock WHERE id = ? AND $isolationWhere");
$stmt->bind_param("i", $id);
$stmt->execute();
$animal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$animal) {
    setFlashMessage("Livestock not found.", 'error');
    redirect('index.php');
}

// Determine production type based on animal
$productionType = '';
$unit = '';
if (in_array($animal['animal_type'], ['cow', 'buffalo', 'goat'])) {
    $productionType = 'milk';
    $unit = 'liters';
} elseif (in_array($animal['animal_type'], ['chicken', 'duck'])) {
    $productionType = 'eggs';
    $unit = 'pieces';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $production_date = sanitizeInput($_POST['production_date'] ?? '');
    $production_type = sanitizeInput($_POST['production_type'] ?? $productionType);
    $morning_qty = floatval($_POST['morning_qty'] ?? 0);
    $evening_qty = floatval($_POST['evening_qty'] ?? 0);
    $total_qty = $morning_qty + $evening_qty;
    $unit = sanitizeInput($_POST['unit'] ?? $unit);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (empty($production_date)) $errors[] = "Date is required.";
    if ($total_qty <= 0) $errors[] = "Quantity must be greater than 0.";
    
    if (empty($errors)) {
        // Get existing production records
        $productionRecords = json_decode($animal['production'] ?? '[]', true) ?: [];
        
        // Add new record
        $productionRecords[] = [
            'date' => $production_date,
            'type' => $production_type,
            'morning' => $morning_qty,
            'evening' => $evening_qty,
            'quantity' => $total_qty,
            'unit' => $unit,
            'notes' => $notes,
            'recorded_at' => date('Y-m-d H:i:s')
        ];
        
        // Update database
        $stmt = $conn->prepare("UPDATE livestock SET production = ? WHERE id = ?");
        $productionJson = json_encode($productionRecords);
        $stmt->bind_param("si", $productionJson, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            logActivity('update', 'livestock', "Added production record for {$animal['animal_tag']}");
            setFlashMessage("Production record added successfully!", 'success');
            redirect("view.php?id=$id");
        } else {
            $errors[] = "Failed to add production record.";
            $stmt->close();
        }
    }
}
?>

<div class="form-container">
    <div class="form-header">
        <h2>📊 Add Production Record - <?php echo htmlspecialchars($animal['animal_tag'] ?? ''); ?></h2>
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline">← Back</a>
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

    <form method="POST" class="data-form">
        <div class="form-row">
            <div class="form-group">
                <label for="production_date">Date *</label>
                <input type="date" id="production_date" name="production_date" class="form-control" 
                    value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="production_type">Production Type *</label>
                <select id="production_type" name="production_type" class="form-control" required>
                    <option value="milk" <?php echo $productionType === 'milk' ? 'selected' : ''; ?>>Milk</option>
                    <option value="eggs" <?php echo $productionType === 'eggs' ? 'selected' : ''; ?>>Eggs</option>
                    <option value="wool">Wool</option>
                    <option value="meat">Meat</option>
                </select>
            </div>
        </div>

        <?php if ($productionType === 'milk'): ?>
        <div class="form-row">
            <div class="form-group">
                <label for="morning_qty">Morning Quantity</label>
                <input type="number" step="0.01" id="morning_qty" name="morning_qty" class="form-control" 
                    placeholder="0.00" value="0">
            </div>
            
            <div class="form-group">
                <label for="evening_qty">Evening Quantity</label>
                <input type="number" step="0.01" id="evening_qty" name="evening_qty" class="form-control" 
                    placeholder="0.00" value="0">
            </div>
        </div>
        <?php else: ?>
        <div class="form-group">
            <label for="morning_qty">Quantity *</label>
            <input type="number" step="0.01" id="morning_qty" name="morning_qty" class="form-control" 
                placeholder="0" required>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="unit">Unit *</label>
            <select id="unit" name="unit" class="form-control" required>
                <option value="liters" <?php echo $unit === 'liters' ? 'selected' : ''; ?>>Liters</option>
                <option value="pieces" <?php echo $unit === 'pieces' ? 'selected' : ''; ?>>Pieces</option>
                <option value="kg" <?php echo $unit === 'kg' ? 'selected' : ''; ?>>Kilograms</option>
            </select>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="2" 
                placeholder="Quality, observations, etc."></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Production Record</button>
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
// Auto-calculate total for milk
document.getElementById('morning_qty').addEventListener('input', updateTotal);
document.getElementById('evening_qty').addEventListener('input', updateTotal);

function updateTotal() {
    const morning = parseFloat(document.getElementById('morning_qty').value) || 0;
    const evening = parseFloat(document.getElementById('evening_qty').value) || 0;
    console.log('Total:', morning + evening);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
