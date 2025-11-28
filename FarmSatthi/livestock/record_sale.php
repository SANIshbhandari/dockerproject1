<?php
$pageTitle = 'Record Sale - FarmSaathi';
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

if (!$animal || $animal['status'] !== 'active') {
    setFlashMessage("Livestock not available for sale.", 'error');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_date = sanitizeInput($_POST['sale_date'] ?? '');
    $buyer_name = sanitizeInput($_POST['buyer_name'] ?? '');
    $buyer_contact = sanitizeInput($_POST['buyer_contact'] ?? '');
    $selling_price = floatval($_POST['selling_price'] ?? 0);
    $quantity_sold = intval($_POST['quantity_sold'] ?? $animal['quantity']);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (empty($sale_date)) $errors[] = "Sale date is required.";
    if (empty($buyer_name)) $errors[] = "Buyer name is required.";
    if ($selling_price <= 0) $errors[] = "Selling price is required.";
    if ($quantity_sold <= 0 || $quantity_sold > $animal['quantity']) $errors[] = "Invalid quantity.";
    
    if (empty($errors)) {
        $profit_loss = $selling_price - $animal['purchase_cost'];
        
        // Get existing sales
        $salesData = json_decode($animal['sales'] ?? '[]', true) ?: [];
        
        // Add new sale
        $salesData[] = [
            'sale_date' => $sale_date,
            'quantity' => $quantity_sold,
            'selling_price' => $selling_price,
            'purchase_cost' => $animal['purchase_cost'],
            'profit_loss' => $profit_loss,
            'buyer_name' => $buyer_name,
            'buyer_contact' => $buyer_contact,
            'notes' => $notes,
            'recorded_at' => date('Y-m-d H:i:s')
        ];
        
        // Update status
        $newQuantity = $animal['quantity'] - $quantity_sold;
        $newStatus = $newQuantity <= 0 ? 'sold' : 'active';
        
        $stmt = $conn->prepare("UPDATE livestock SET sales = ?, quantity = ?, status = ? WHERE id = ?");
        $salesJson = json_encode($salesData);
        $stmt->bind_param("sisi", $salesJson, $newQuantity, $newStatus, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Record income
            $createdBy = getCreatedByUserId();
            $stmt = $conn->prepare("INSERT INTO finance (created_by, type, category, amount, transaction_date, description) VALUES (?, 'income', 'Livestock Sales', ?, ?, ?)");
            $description = "Sale of {$animal['animal_tag']} - $quantity_sold {$animal['animal_type']} to $buyer_name";
            $stmt->bind_param("idss", $createdBy, $selling_price, $sale_date, $description);
            $stmt->execute();
            $stmt->close();
            
            logActivity('update', 'livestock', "Recorded sale of {$animal['animal_tag']}");
            setFlashMessage("Sale recorded successfully!", 'success');
            redirect('index.php');
        } else {
            $errors[] = "Failed to record sale.";
            $stmt->close();
        }
    }
}
?>

<div class="form-container">
    <div class="form-header">
        <h2>💰 Record Sale - <?php echo htmlspecialchars($animal['animal_tag']); ?></h2>
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
                <label for="sale_date">Sale Date *</label>
                <input type="date" id="sale_date" name="sale_date" class="form-control" 
                    value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="quantity_sold">Quantity to Sell *</label>
                <input type="number" id="quantity_sold" name="quantity_sold" class="form-control" 
                    value="<?php echo $animal['quantity']; ?>" min="1" max="<?php echo $animal['quantity']; ?>" required>
                <small>Available: <?php echo $animal['quantity']; ?></small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="selling_price">Selling Price (Total) *</label>
                <input type="number" step="0.01" id="selling_price" name="selling_price" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Purchase Cost</label>
                <input type="text" class="form-control" value="<?php echo formatCurrency($animal['purchase_cost']); ?>" readonly>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="buyer_name">Buyer Name *</label>
                <input type="text" id="buyer_name" name="buyer_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="buyer_contact">Buyer Contact</label>
                <input type="text" id="buyer_contact" name="buyer_contact" class="form-control" 
                    placeholder="Phone or email">
            </div>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="2"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Record Sale</button>
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
