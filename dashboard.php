<?php
/**
 * Dashboard Page - Vehicle Tracking System
 */
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <div class="container">
            <!-- Header -->
            <div class="dashboard-header">
                <h1><?php echo APP_NAME; ?></h1>
                <div class="user-info">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/users.php" class="btn btn-secondary">User Management</a>
                    <?php endif; ?>
                    <button id="logout-btn" class="btn btn-danger">Logout</button>
                </div>
            </div>
            
            <!-- Search Box -->
            <div class="search-box">
                <div class="search-input-wrapper">
                    <input type="text" id="search-input" class="form-control" placeholder="Search vehicles by name, plate, or VIN...">
                    <button id="search-btn" class="btn btn-primary">Search</button>
                </div>
            </div>
            
            <!-- Vehicle List -->
            <div id="vehicle-list" class="vehicle-list">
                <div class="text-center"><span class="loading"></span> Loading vehicles...</div>
            </div>
            
            <!-- Telemetry Panel -->
            <div id="telemetry-panel" class="telemetry-panel hidden">
                <div class="telemetry-header">
                    <h3>Live Telemetry - <span id="telemetry-vehicle-name"></span></h3>
                    <div class="telemetry-indicator">
                        <span class="indicator-dot"></span>
                        <span>Updating every 5s</span>
                        <span id="telemetry-timestamp"></span>
                    </div>
                    <button id="refresh-telemetry" class="btn btn-secondary">Refresh Now</button>
                </div>
                
                <div class="telemetry-grid">
                    <div class="telemetry-item">
                        <div class="telemetry-value" id="telemetry-speed">--</div>
                        <div class="telemetry-label">Speed</div>
                    </div>
                    <div class="telemetry-item">
                        <div class="telemetry-value" id="telemetry-location">--</div>
                        <div class="telemetry-label">Location (Lat, Lng)</div>
                    </div>
                    <div class="telemetry-item">
                        <div class="telemetry-value" id="telemetry-heading">--</div>
                        <div class="telemetry-label">Heading</div>
                    </div>
                    <div class="telemetry-item">
                        <div class="telemetry-value" id="telemetry-fuel">--</div>
                        <div class="telemetry-label">Fuel Level</div>
                    </div>
                    <div class="telemetry-item">
                        <div class="telemetry-value" id="telemetry-engine">--</div>
                        <div class="telemetry-label">Engine Status</div>
                    </div>
                    <div class="telemetry-item">
                        <div class="telemetry-value" id="telemetry-altitude">--</div>
                        <div class="telemetry-label">Altitude</div>
                    </div>
                    <div class="telemetry-item">
                        <div class="telemetry-value" id="telemetry-odometer">--</div>
                        <div class="telemetry-label">Odometer</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
