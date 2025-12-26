<?php
$pageTitle = 'Add Health Record - FarmSaathi';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_type = sanitizeInput($_POST['record_type'] ?? '');
    $record_date = sanitizeInput($_POST['record_date'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $veterinarian = sanitizeInput($_POST['veterinarian'] ?? '');
    $cost = floatval($_POST['cost'] ?? 0);
    $next_due_date = sanitizeInput($_POST['next_due_date'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (empty($record_type)) $errors[] = "Record type is required.";
    if (empty($record_date)) $errors[] = "Date is required.";
    if (empty($description)) $errors[] = "Description is required.";
    
    if (empty($errors)) {
        // Get existing health records
        $healthRecords = json_decode($animal['health_records'] ?? '[]', true) ?: [];
        
        // Add new record
        $healthRecords[] = [
            'type' => $record_type,
            'date' => $record_date,
            'description' => $description,
            'veterinarian' => $veterinarian,
            'cost' => $cost,
            'next_due_date' => $next_due_date,
            'notes' => $notes,
            'recorded_at' => date('Y-m-d H:i:s')
        ];
        
        // Update database
        $stmt = $conn->prepare("UPDATE livestock SET health_records = ? WHERE id = ?");
        $healthJson = json_encode($healthRecords);
        $stmt->bind_param("si", $healthJson, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            logActivity('update', 'livestock', "Added health record for {$animal['animal_tag']}");
            setFlashMessage("Health record added successfully!", 'success');
            redirect("view.php?id=$id");
        } else {
            $errors[] = "Failed to add health record.";
            $stmt->close();
        }
    }
}
?>

<div class="form-container">
    <div class="form-header">
        <h2>💉 Add Health Record - <?php echo htmlspecialchars($animal['animal_tag'] ?? ''); ?></h2>
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
                <label for="record_type">Record Type *</label>
                <select id="record_type" name="record_type" class="form-control" required>
                    <option value="">Select Type</option>
                    <option value="vaccination">Vaccination</option>
                    <option value="treatment">Treatment</option>
                    <option value="checkup">Checkup</option>
                    <option value="deworming">Deworming</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="record_date">Date *</label>
                <input type="date" id="record_date" name="record_date" class="form-control" 
                    value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description *</label>
            <input type="text" id="description" name="description" class="form-control" 
                placeholder="e.g., FMD Vaccination, Antibiotic Treatment" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="veterinarian">Veterinarian</label>
                <input type="text" id="veterinarian" name="veterinarian" class="form-control" 
                    placeholder="Doctor's name">
            </div>
            
            <div class="form-group">
                <label for="cost">Cost</label>
                <input type="number" step="0.01" id="cost" name="cost" class="form-control" 
                    placeholder="0.00">
            </div>
        </div>

        <div class="form-group">
            <label for="next_due_date">Next Due Date</label>
            <input type="date" id="next_due_date" name="next_due_date" class="form-control">
            <small>For vaccinations or follow-up treatments</small>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Health Record</button>
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
