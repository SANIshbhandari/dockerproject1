<?php
/**
 * Generic Error Page
 */
$pageTitle = 'Error - FarmSaathi';
$errorCode = $_GET['code'] ?? '500';
$errorMessage = $_GET['message'] ?? 'An unexpected error occurred.';

// Sanitize inputs
$errorCode = htmlspecialchars($errorCode);
$errorMessage = htmlspecialchars($errorMessage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div style="display: flex; align-items: center; gap: 0.75rem; justify-content: center; margin-bottom: 1rem;">
                    <img src="/Farmwebsite/assets/images/logo.jpg" alt="FarmSaathi Logo" style="height: 60px; width: auto;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                    <span style="display:none; font-size: 2rem;">🌾</span>
                    <h1 style="margin: 0;">FarmSaathi</h1>
                </div>
                <p>Error <?php echo $errorCode; ?></p>
            </div>

            <div class="alert alert-error">
                <p><?php echo $errorMessage; ?></p>
            </div>

            <div class="login-footer">
                <a href="/" class="btn btn-primary">← Go to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
