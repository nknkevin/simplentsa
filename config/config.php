<?php
/**
 * Main Configuration File
 * Customize these settings for your environment
 */

// Application Settings
define('APP_NAME', 'Vehicle Tracking System');
define('APP_VERSION', '1.0.0');

// Branding - Customize these for your company
define('BRAND_LOGO', 'assets/images/logo.png'); // Path to your logo
define('BRAND_FAVICON', 'assets/images/favicon.ico'); // Path to favicon
define('BRAND_PRIMARY_COLOR', '#2563eb'); // Primary brand color
define('LOGIN_BACKGROUND', 'assets/images/login-bg.jpg'); // Login page background image

// Session Settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Tracking Server Configuration
define('TRACKING_SERVER_URL', 'http://tracking-server.example.com/api');
define('TRACKING_SERVER_TIMEOUT', 5); // seconds

// Telemetry Update Interval (milliseconds)
define('TELEMETRY_INTERVAL', 5000); // 5 seconds

// Security Settings
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 300); // 5 minutes

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');
