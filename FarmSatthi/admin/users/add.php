<?php
$pageTitle = 'Create User - FarmSaathi';
$currentModule = 'users';
require_once __DIR__ . '/../../includes/header.php';

// Only admin can access
requirePermission('admin');

$conn = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'manager');
    
    // Validate inputs
    if ($error = validateRequired($username, 'Username')) $errors[] = $error;
    if ($error = validateRequired($password, 'Password')) $errors[] = $error;
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Username already exists.";
        }
        $stmt->close();
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $role);
        
        if ($stmt->execute()) {
            $stmt->close();
            logActivity('create', 'users', "Created user: $username");
            setFlashMessage("User created successfully!", 'success');
            redirect('index.php');
        } else {
            $errors[] = "Failed to create user. Please try again.";
            $stmt->close();
        }
    }
}
?>

<div class="form-container">
    <div class="form-header">
        <h2>Create New User</h2>
        <a href="index.php" class="btn btn-outline">← Back to Users</a>
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

    <form method="POST" action="add.php" class="data-form" id="userForm">
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username *</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($username ?? ''); ?>"
                    required
                    minlength="3"
                    autofocus
                >
                <small class="form-text">Minimum 3 characters</small>
            </div>

            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="manager" <?php echo ($role ?? 'manager') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                    <option value="admin" <?php echo ($role ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    minlength="6"
                    required
                >
                <small class="form-text">Minimum 6 characters</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-control" 
                    required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role" class="form-control" required>
                <option value="manager" <?php echo ($role ?? 'manager') === 'manager' ? 'selected' : ''; ?>>Manager (Farm Operations)</option>
                <option value="admin" <?php echo ($role ?? '') === 'admin' ? 'selected' : ''; ?>>Admin (User Management)</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="index.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>

    <script>
    document.getElementById('userForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long!');
            return false;
        }
    });
    </script>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
