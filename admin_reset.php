<?php
/**
 * Admin Password Reset Script
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your server root directory
 * 2. Run via command line: php reset_admin_password.php
 * 3. Note the new password displayed
 * 4. IMMEDIATELY DELETE THIS FILE from the server after use
 * 5. Login with username 'admin' and the new password
 * 
 * SECURITY WARNING: This file should NOT remain on your server after use!
 */

// Prevent web access - must be run from command line
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access denied. This script must be run from command line only.\n");
}

echo "===========================================\n";
echo "   ADMIN PASSWORD RESET TOOL\n";
echo "===========================================\n\n";

// Database configuration
$db_host = 'localhost';
$db_name = 'alexa';
$db_user = 'root';     // Change if needed
$db_pass = 'jameskinuthia_202S!!';         // Change if needed
/*
// Try to load from existing config if available
$config_file = __DIR__ . '/config/database.php';
if (file_exists($config_file)) {
    echo "✓ Found existing database configuration\n";
    require_once $config_file;
    
    // Override with config values if functions exist
    if (function_exists('getAlexaDB')) {
        $config = getAlexaDB();
        $db_host = $config['host'];
        $db_name = $config['dbname'];
        $db_user = $config['user'];
        $db_pass = $config['pass'];
        echo "✓ Loaded credentials from config/database.php\n";
    }
}

echo "\nConnecting to database '{$db_name}'...\n";
*/
try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Database connection successful\n\n";
    
    // Generate a strong random password (16 characters)
    $new_password = bin2hex(random_bytes(8));
    
    echo "Generated new password: {$new_password}\n\n";
    
    // Hash the password using bcrypt
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    echo "✓ Password hashed successfully\n";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ? LIMIT 1");
    $stmt->execute(['admin']);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin_user) {
        // Update existing admin password
        $update_stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE username = ?");
        $update_stmt->execute([$hashed_password, 'admin']);
        
        echo "✓ Password updated for existing admin user (ID: {$admin_user['id']})\n";
    } else {
        // Create admin user if doesn't exist
        $insert_stmt = $pdo->prepare("
            INSERT INTO users (username, password, role, status, created_at, updated_at) 
            VALUES (?, ?, 'admin', 1, NOW(), NOW())
        ");
        $insert_stmt->execute(['admin', $hashed_password]);
        $admin_id = $pdo->lastInsertId();
        
        echo "✓ Admin user created (ID: {$admin_id})\n";
    }
    
    echo "\n";
    echo "===========================================\n";
    echo "   PASSWORD RESET SUCCESSFUL!\n";
    echo "===========================================\n";
    echo "\n";
    echo "LOGIN CREDENTIALS:\n";
    echo "------------------\n";
    echo "Username: admin\n";
    echo "Password: {$new_password}\n";
    echo "\n";
    echo "⚠️  IMPORTANT SECURITY REMINDER:\n";
    echo "   DELETE THIS FILE IMMEDIATELY AFTER USE!\n";
    echo "   Command: rm " . basename(__FILE__) . "\n";
    echo "\n";
    echo "===========================================\n\n";
    
} catch (PDOException $e) {
    echo "\n✗ ERROR: Database connection failed!\n";
    echo "Error message: " . $e->getMessage() . "\n\n";
    echo "Please check your database credentials in this script or ensure config/database.php is correct.\n";
    exit(1);
}
?>