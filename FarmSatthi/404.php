<?php
/**
 * 404 Not Found Page
 */
$pageTitle = '404 Not Found - FarmSaathi';
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
                <p>404 - Page Not Found</p>
            </div>

            <div class="alert alert-warning">
                <p>The page you're looking for doesn't exist or has been moved.</p>
            </div>

            <div class="login-footer">
                <a href="/" class="btn btn-primary">← Go to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
