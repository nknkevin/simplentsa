<?php
/**
 * Database Configuration File
 * Configure your remote vehicle database connection here
 */

// Vehicle Database Settings (Remote Server - Uradi)
define('DB_HOST', 'remote-db-server.example.com');
define('DB_NAME', 'uradi');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

// Local Users Database (for login - same server - Alexa)
define('LOCAL_DB_HOST', 'localhost');
define('LOCAL_DB_NAME', 'alexa');
define('LOCAL_DB_USER', 'local_username');
define('LOCAL_DB_PASS', 'local_password');
define('LOCAL_DB_CHARSET', 'utf8mb4');

/**
 * Get Vehicle Database Connection
 */
function getVehicleDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Vehicle DB Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}

/**
 * Get Local Database Connection (for users)
 */
function getLocalDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . LOCAL_DB_HOST . ";dbname=" . LOCAL_DB_NAME . ";charset=" . LOCAL_DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, LOCAL_DB_USER, LOCAL_DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Local DB Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}
