<?php
$pageTitle = 'Add Employee - FarmSaathi';
$currentModule = 'employees';
require_once __DIR__ . '/../includes/header.php';

requirePermission('manager');

$conn = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $salary = sanitizeInput($_POST['salary'] ?? '');
    $hire_date = sanitizeInput($_POST['hire_date'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'active');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if ($error = validateRequired($name, 'Name')) $errors[] = $error;
    if ($error = validateRequired($role, 'Role')) $errors[] = $error;
    if ($error = validateRequired($phone, 'Phone')) $errors[] = $error;
    if (!empty($email) && ($error = validateEmail($email))) $errors[] = $error;
    if ($error = validatePositive($salary, 'Salary')) $errors[] = $error;
    if ($error = validateDate($hire_date, 'Hire date')) $errors[] = $error;
    
    if (empty($errors)) {
        $email = !empty($email) ? $email : null;
        
        $createdBy = getCreatedByUserId();
        $stmt = $conn->prepare("
            INSERT INTO employees (created_by, name, role, phone, email, salary, hire_date, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssdsss", $createdBy, $name, $role, $phone, $email, $salary, $hire_date, $status, $notes);
        
        if ($stmt->execute()) {
            $stmt->close();
            setFlashMessage("Employee added successfully!", 'success');
            redirect('index.php');
        } else {
            $errors[] = "Failed to add employee. Please try again.";
        }
        $stmt->close();
    }
}
?>

<div class="form-container">
    <div class="form-header">
        <h2>Add New Employee</h2>
        <a href="index.php" class="btn btn-outline">← Back to Employees</a>
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
                <label for="name">Full Name *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($name ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="role">Role *</label>
                <input 
                    type="text" 
                    id="role" 
                    name="role" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($role ?? ''); ?>"
                    placeholder="e.g., Farm Manager, Worker"
                    required
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone *</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="salary">Salary ($) *</label>
                <input 
                    type="number" 
                    id="salary" 
                    name="salary" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($salary ?? ''); ?>"
                    step="0.01"
                    min="0"
                    required
                >
            </div>

            <div class="form-group">
                <label for="hire_date">Hire Date *</label>
                <input 
                    type="date" 
                    id="hire_date" 
                    name="hire_date" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($hire_date ?? ''); ?>"
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status" class="form-control" required>
                <option value="active" <?php echo ($status ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($status ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="terminated" <?php echo ($status ?? '') === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
            </select>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea 
                id="notes" 
                name="notes" 
                class="form-control" 
                rows="4"
            ><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Employee</button>
            <a href="index.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
