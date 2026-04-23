<?php
/**
 * Database Configuration File
 * Architecture:
 *   - alexa (local): users + vehicles (business data from external server)
 *   - uradi (remote Traccar): devices + eventData (telemetry data)
 *   - Link: alexa.vehicles.serial = uradi.devices.uniqueid
 */

// Local Database Settings (Alexa - Users + Vehicles)
define('ALEXA_DB_HOST', 'localhost');
define('ALEXA_DB_NAME', 'alexa');
define('ALEXA_DB_USER', 'testroot');
define('ALEXA_DB_PASS', 'test');
define('ALEXA_DB_CHARSET', 'utf8mb4');

// Remote Traccar Database Settings (Uradi - Telemetry Only)
define('URADI_DB_HOST', 'example.com');
define('URADI_DB_NAME', 'uradi');
define('URADI_DB_USER', 'testroot');
define('URADI_DB_PASS', 'test');
define('URADI_DB_CHARSET', 'utf8mb4');

/**
 * Get Alexa Database Connection (Users + Vehicles)
 */
function getAlexaDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . ALEXA_DB_HOST . ";dbname=" . ALEXA_DB_NAME . ";charset=" . ALEXA_DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, ALEXA_DB_USER, ALEXA_DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Alexa DB Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}

/**
 * Get Uradi Database Connection (Traccar - Telemetry)
 */
function getUradiDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . URADI_DB_HOST . ";dbname=" . URADI_DB_NAME . ";charset=" . URADI_DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, URADI_DB_USER, URADI_DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Uradi DB Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}
