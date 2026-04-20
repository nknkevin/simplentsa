<?php
/**
 * Login Page - Vehicle Tracking System
 */
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="icon" href="<?php echo BRAND_FAVICON; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?php echo LOGIN_BACKGROUND; ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .brand-logo {
            max-width: 200px;
            margin-bottom: 1rem;
        }
        :root {
            --primary-color: <?php echo BRAND_PRIMARY_COLOR; ?>;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="<?php echo BRAND_LOGO; ?>" alt="<?php echo APP_NAME; ?>" class="brand-logo" onerror="this.style.display='none'">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Please sign in to continue</p>
            </div>
            
            <form id="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" placeholder="Enter your username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" class="form-control" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" id="login-btn" class="btn btn-primary">Login</button>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
