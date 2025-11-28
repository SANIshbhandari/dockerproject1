<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/session.php';

// Get database connection
$conn = getDBConnection();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../dashboard/index.php');
}

$errors = [];

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    
    // Authenticate user if no validation errors
    if (empty($errors)) {
        $user = authenticateUser($username, $password);
        
        if ($user) {
            // Log the login activity
            logActivity('login', 'auth', 'User logged in successfully');
            
            createSession($user['id'], $user['username'], $user['role']);
            setFlashMessage("Welcome back, " . $user['username'] . "!", 'success');
            redirect('../dashboard/index.php');
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}

$pageTitle = 'Login - FarmSaathi';
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
                    <h2>Welcome Back!</h2>
                    <p>Sign in to your account to continue</p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php displayFlashMessage(); ?>

                <form method="POST" action="login.php" class="auth-form">
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
                            placeholder="Enter your username"
                            value="<?php echo htmlspecialchars($username ?? ''); ?>"
                            required
                            autofocus
                        >
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
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Sign In
                    </button>
                </form>

                <div class="auth-divider">
                    <span>or</span>
                </div>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="signup.php" class="signup-link">Sign up here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo asset('js/main.js'); ?>"></script>
</body>
</html>
