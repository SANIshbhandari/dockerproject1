<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../dashboard/index.php');
}

$conn = getDBConnection();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'manager'; // Default role for public signup
    
    // Validate username
    if ($error = validateRequired($username, 'Username')) {
        $errors[] = $error;
    } else {
        // Username length validation
        if (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        }
        if (strlen($username) > 50) {
            $errors[] = "Username must not exceed 50 characters.";
        }
        // Username format validation (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }
        // Username must start with a letter
        if (!preg_match('/^[a-zA-Z]/', $username)) {
            $errors[] = "Username must start with a letter.";
        }
    }
    
    // Validate password
    if ($error = validateRequired($password, 'Password')) {
        $errors[] = $error;
    } else {
        // Password length validation
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if (strlen($password) > 255) {
            $errors[] = "Password must not exceed 255 characters.";
        }
        
        // Password strength validation
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasLowercase = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecialChar = preg_match('/[^a-zA-Z0-9]/', $password);
        
        if (!$hasUppercase) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        if (!$hasLowercase) {
            $errors[] = "Password must contain at least one lowercase letter.";
        }
        if (!$hasNumber) {
            $errors[] = "Password must contain at least one number.";
        }
        if (!$hasSpecialChar) {
            $errors[] = "Password must contain at least one special character (!@#$%^&*).";
        }
        
        // Check for common weak passwords
        $weakPasswords = ['password', 'password123', '12345678', 'qwerty123', 'admin123'];
        if (in_array(strtolower($password), $weakPasswords)) {
            $errors[] = "This password is too common. Please choose a stronger password.";
        }
    }
    
    // Validate password confirmation
    if (empty($password) || empty($confirm_password)) {
        if (empty($confirm_password)) {
            $errors[] = "Please confirm your password.";
        }
    } else {
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }
    
    // Check if username already exists
    if (empty($errors) || !in_array("Username already exists.", $errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Username already exists. Please choose a different username.";
        }
        $stmt->close();
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $role);
        
        if ($stmt->execute()) {
            // Auto-login the new user
            $user = [
                'id' => $conn->insert_id,
                'username' => $username,
                'role' => 'manager'
            ];
            createSession($user['id'], $user['username'], $user['role']);
            setFlashMessage("Welcome to FarmSaathi, " . $username . "! Your account has been created successfully.", 'success');
            $stmt->close();
            redirect('../dashboard/index.php');
        } else {
            $errors[] = "Failed to create account. Please try again.";
            $stmt->close();
        }
    }
}

$pageTitle = 'Sign Up - FarmSaathi';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php require_once __DIR__ . '/../config/config.php'; ?>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">
</head>
<body class="auth-page-centered">
    <div class="auth-container-centered">
        <div class="auth-box-centered">
            <div class="auth-logo-header">
                <img src="<?php echo asset('images/logo.jpg'); ?>" alt="FarmSaathi Logo" class="auth-logo-centered" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span class="logo-fallback-centered" style="display:none;">🌾</span>
                <h1>FarmSaathi</h1>
                <p class="tagline">Your trusted partner in farm management</p>
            </div>
            
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Create Your Account</h2>
                    <p>Join FarmSaathi and start managing your farm</p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php displayFlashMessage(); ?>

                <form method="POST" action="signup.php" class="auth-form">
                    <div class="form-group">
                        <label for="username">
                            <span class="label-icon">👤</span>
                            Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control" 
                            placeholder="Choose a username (3-50 characters)"
                            value="<?php echo htmlspecialchars($username ?? ''); ?>"
                            minlength="3"
                            maxlength="50"
                            pattern="[a-zA-Z][a-zA-Z0-9_]*"
                            title="Username must start with a letter and contain only letters, numbers, and underscores"
                            required
                            autofocus
                        >
                        <small class="form-text">Must start with a letter, 3-50 characters, letters/numbers/underscores only</small>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <span class="label-icon">🔒</span>
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Create a strong password"
                            minlength="8"
                            maxlength="255"
                            required
                        >
                        <small class="form-text password-requirements">
                            <strong>Password must contain:</strong><br>
                            • At least 8 characters<br>
                            • One uppercase letter (A-Z)<br>
                            • One lowercase letter (a-z)<br>
                            • One number (0-9)<br>
                            • One special character (!@#$%^&*)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <span class="label-icon">🔒</span>
                            Confirm Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            placeholder="Confirm your password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Create Account
                    </button>
                </form>

                <div class="auth-divider">
                    <span>or</span>
                </div>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php" class="signup-link">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo asset('js/main.js'); ?>"></script>
</body>
</html>
