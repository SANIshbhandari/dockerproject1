<?php
$pageTitle = 'Edit Inventory Item - FarmSaathi';
$currentModule = 'inventory';
require_once __DIR__ . '/../includes/header.php';

requirePermission('manager');

$conn = getDBConnection();
$errors = [];
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage("Invalid inventory ID.", 'error');
    redirect('index.php');
}

// Verify record ownership
verifyRecordOwnership($conn, 'inventory', $id, 'index.php');

$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    setFlashMessage("Inventory item not found.", 'error');
    redirect('index.php');
}

$item = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = sanitizeInput($_POST['item_name'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $quantity = sanitizeInput($_POST['quantity'] ?? '');
    $unit = sanitizeInput($_POST['unit'] ?? '');
    $reorder_level = sanitizeInput($_POST['reorder_level'] ?? '');
    
    if ($error = validateRequired($item_name, 'Item name')) $errors[] = $error;
    if ($error = validateRequired($category, 'Category')) $errors[] = $error;
    if ($error = validatePositive($quantity, 'Quantity')) $errors[] = $error;
    if ($error = validateRequired($unit, 'Unit')) $errors[] = $error;
    if ($error = validatePositive($reorder_level, 'Reorder level')) $errors[] = $error;
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE inventory 
            SET item_name = ?, category = ?, quantity = ?, unit = ?, reorder_level = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssdsdi", $item_name, $category, $quantity, $unit, $reorder_level, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            setFlashMessage("Inventory item updated successfully!", 'success');
            redirect('index.php');
        } else {
            $errors[] = "Failed to update inventory item. Please try again.";
        }
        $stmt->close();
    }
} else {
    $item_name = $item['item_name'];
    $category = $item['category'];
    $quantity = $item['quantity'];
    $unit = $item['unit'];
    $reorder_level = $item['reorder_level'];
}
?>

<div class="form-container">
    <div class="form-header">
        <h2>Edit Inventory Item</h2>
        <a href="index.php" class="btn btn-outline">← Back to Inventory</a>
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

    <form method="POST" action="edit.php?id=<?php echo $id; ?>" class="data-form">
        <div class="form-row">
            <div class="form-group">
                <label for="item_name">Item Name *</label>
                <input 
                    type="text" 
                    id="item_name" 
                    name="item_name" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($item_name); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="category">Category *</label>
                <input 
                    type="text" 
                    id="category" 
                    name="category" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($category); ?>"
                    required
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="quantity">Quantity *</label>
                <input 
                    type="number" 
                    id="quantity" 
                    name="quantity" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($quantity); ?>"
                    step="0.01"
                    min="0"
                    required
                >
            </div>

            <div class="form-group">
                <label for="unit">Unit *</label>
                <input 
                    type="text" 
                    id="unit" 
                    name="unit" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($unit); ?>"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="reorder_level">Reorder Level *</label>
            <input 
                type="number" 
                id="reorder_level" 
                name="reorder_level" 
                class="form-control" 
                value="<?php echo htmlspecialchars($reorder_level); ?>"
                step="0.01"
                min="0"
                required
            >
            <small class="form-text">Alert will be shown when quantity falls below this level</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Item</button>
            <a href="index.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
