<?php

/**
 * Dashboard Page - Vehicle Tracking System
 * Features: AJAX Search, Vehicle Details, Live Telemetry Stream, Printable Reports
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
    <style>
        /* Dashboard Specific Styles */
        .search-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-input-wrapper {
            display: flex;
            gap: 10px;
        }

        .search-input-wrapper input {
            flex: 1;
            padding: 12px 16px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 6px;
        }

        .search-input-wrapper input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        /* Vehicle Results Table */
        .vehicle-results {
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: none;
        }

        .vehicle-results.active {
            display: block;
        }

        .vehicle-table {
            width: 100%;
            border-collapse: collapse;
        }

        .vehicle-table th,
        .vehicle-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .vehicle-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            cursor: pointer;
        }

        .vehicle-table tr:hover {
            background: #f5f7fa;
            cursor: pointer;
        }

        .vehicle-table tr:last-child td {
            border-bottom: none;
        }

        /* Vehicle Details Panel */
        .vehicle-details-panel {
            margin: 2rem auto;
            max-width: 1200px;
            padding: 0 1rem;
            display: none;
        }

        .vehicle-details-panel.active {
            display: block;
        }

        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .detail-item {
            padding: 0.5rem;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }

        /* Telemetry Stream Table */
        .telemetry-stream-panel {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .telemetry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: #2e7d32;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        .telemetry-table-container {
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }

        .telemetry-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .telemetry-table th,
        .telemetry-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            white-space: nowrap;
        }

        .telemetry-table th {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
            font-weight: 600;
        }

        .telemetry-table tr:hover {
            background: #f5f7fa;
        }

        .map-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .map-link:hover {
            text-decoration: underline;
        }

        /* Print Styles */
        @media print {

            .dashboard-header,
            .search-container,
            .vehicle-results,
            .btn,
            #logout-btn {
                display: none !important;
            }

            .vehicle-details-panel,
            .telemetry-stream-panel {
                display: block !important;
                box-shadow: none;
                padding: 0;
            }

            .telemetry-table-container {
                max-height: none;
                overflow: visible;
            }

            body {
                background: white;
            }

            .details-header h2 {
                font-size: 18pt;
            }
        }

        .hidden {
            display: none;
        }

        .text-center {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
                <h1><?php echo APP_NAME; ?></h1>
                <div class="user-info">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/users.php" class="btn btn-secondary">Users</a>
                        <a href="admin/vehicles.php" class="btn btn-secondary">Manage Vehicles</a>
                    <?php endif; ?>
                    <button id="logout-btn" class="btn btn-danger">Logout</button>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-container">
            <div class="search-box">
                <div class="search-input-wrapper">
                    <input type="text" id="search-input" class="form-control" placeholder="Start typing to search vehicles..." autocomplete="off">
                </div>
            </div>

            <!-- Vehicle Results Table -->
            <div id="vehicle-results" class="vehicle-results">
                <table class="vehicle-table">
                    <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Reg No</th>
                            <th>Chassis</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="vehicle-results-body">
                        <!-- Results will be inserted here -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vehicle Details Panel -->
        <div id="vehicle-details-panel" class="vehicle-details-panel">
            <div class="details-header">
                <h2 id="details-vehicle-title">Vehicle Details</h2>
                <div>
                    <button id="close-details" class="btn btn-secondary">Back to Search</button>
                    <button id="print-report" class="btn btn-primary">🖨️ Print Report</button>
                </div>
            </div>

            <!-- Full Vehicle Data Grid -->
            <div id="vehicle-data-grid" class="details-grid">
                <!-- Dynamic content -->
            </div>

            <!-- Live Telemetry Stream -->
            <div class="telemetry-stream-panel">
                <div class="telemetry-header">
                    <h3>📡 Live Telemetry Stream</h3>
                    <div class="live-indicator">
                        <span class="live-dot"></span>
                        <span>LIVE • Updating every 5s</span>
                    </div>
                    <button id="refresh-telemetry" class="btn btn-sm btn-secondary">Refresh Now</button>
                </div>

                <div class="telemetry-table-container">
                    <table class="telemetry-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Speed (km/h)</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Map Link</th>
                                <th>Data Notes</th>
                            </tr>
                        </thead>
                        <tbody id="telemetry-stream-body">
                            <tr>
                                <td colspan="7" class="text-center">Select a vehicle to view telemetry data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem; font-size: 0.85rem; color: #666;">
                    <strong>Note:</strong> Data streams from Tracking Server. Reports can be printed using the button above.
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        $(document).ready(function() {
            let currentVehicleId = null;
            let telemetryInterval = null;

            // Logout functionality
            $('#logout-btn').click(function() {
                $.post('api/logout.php', function() {
                    window.location.href = 'index.php';
                });
            });

            // Search with debounce for smooth performance
            let searchTimeout;

            $('#search-input').on('input', function() {
                clearTimeout(searchTimeout); // Cancel previous pending search
                const query = $(this).val().trim();

                if (query.length >= 2) {
                    // Wait 300ms after user stops typing before searching
                    searchTimeout = setTimeout(() => performSearch(query), 300);
                } else if (query.length === 0) {
                    // Hide results if input is cleared
                    $('#vehicle-results').removeClass('active');
                    $('#vehicle-results-body').empty();
                }
            });

            function performSearch(query) {
                console.log("Searching for:", query); // Debug log

                $('#vehicle-results-body').html('<tr><td colspan="7" class="text-center"><span class="loading"></span> Searching...</td></tr>');
                $('#vehicle-results').addClass('active');

                $.ajax({
                    url: 'api/vehicles.php', // Ensure this path is correct relative to dashboard.php
                    method: 'GET',
                    data: {
                        search: query
                    },
                    success: function(response) {
                        console.log("API Response:", response); // Debug log

                        // Check if response is valid JSON and has success flag
                        if (response && response.success && response.data) {
                            if (response.data.length > 0) {
                                let html = '';
                                response.data.forEach(vehicle => {
                                    html += `
                            <tr data-id="${vehicle.id}" data-serial="${vehicle.serial || ''}">
                                <td>${escapeHtml(vehicle.serial || 'N/A')}</td>
                                <td>${escapeHtml(vehicle.reg_no || 'N/A')}</td>
                                <td>${escapeHtml(vehicle.chassis || 'N/A')}</td>
                                <td><span class="badge ${vehicle.status_renew == 1 ? 'badge-success' : 'badge-warning'}">${vehicle.status_renew == 1 ? 'Active' : 'Inactive'}</span></td>
                            </tr>
                        `;
                                });
                                $('#vehicle-results-body').html(html);

                                // Re-attach click events
                                $('.vehicle-table tbody tr').click(function() {
                                    const vehicleId = $(this).data('id');
                                    const serial = $(this).data('serial');
                                    loadVehicleDetails(vehicleId, serial);
                                });
                            } else {
                                $('#vehicle-results-body').html('<tr><td colspan="7" class="text-center">No vehicles found</td></tr>');
                            }
                        } else {
                            console.error("Invalid API response structure:", response);
                            $('#vehicle-results-body').html('<tr><td colspan="7" class="text-center" style="color: orange;">Unexpected response format. Check console.</td></tr>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        console.error("Response Text:", xhr.responseText);

                        let msg = "Error loading vehicles.";
                        if (xhr.status === 404) msg = "API endpoint not found (404). Check file path.";
                        if (xhr.status === 500) msg = "Server error (500). Check PHP logs.";

                        $('#vehicle-results-body').html(`<tr><td colspan="7" class="text-center" style="color: red;">${msg}</td></tr>`);
                    }
                });
            }

            /*
            function performSearch(query) {
                $('#vehicle-results-body').html('<tr><td colspan="7" class="text-center"><span class="loading"></span> Searching...</td></tr>');
                $('#vehicle-results').addClass('active');

                $.ajax({
                    url: 'api/vehicles.php',
                    method: 'GET',
                    data: {
                        search: query
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            let html = '';
                            response.data.forEach(vehicle => {
                                html += `
                                <tr data-id="${vehicle.id}" data-serial="${vehicle.serial || ''}">
                                    <td>${escapeHtml(vehicle.cus_name || vehicle.customer_name || 'N/A')}</td>
                                    <td>${escapeHtml(vehicle.serial || 'N/A')}</td>
                                    <td>${escapeHtml(vehicle.reg_no || 'N/A')}</td>
                                    <td>${escapeHtml(vehicle.vin_no || vehicle.vin || 'N/A')}</td>
                                    <td>${escapeHtml(vehicle.contact || 'N/A')}</td>
                                    <td><span class="badge ${vehicle.status_renew == 1 ? 'badge-success' : 'badge-warning'}">${vehicle.status_renew == 1 ? 'Active' : 'Inactive'}</span></td>
                                </tr>
                            `;
                            });
                            $('#vehicle-results-body').html(html);

                            // Click to view details
                            $('.vehicle-table tbody tr').click(function() {
                                const vehicleId = $(this).data('id');
                                const serial = $(this).data('serial');
                                loadVehicleDetails(vehicleId, serial);
                            });
                        } else {
                            $('#vehicle-results-body').html('<tr><td colspan="7" class="text-center">No vehicles found</td></tr>');
                        }
                    },
                    error: function() {
                        $('#vehicle-results-body').html('<tr><td colspan="7" class="text-center" style="color: red;">Error loading vehicles</td></tr>');
                    }
                });
            } */

            function loadVehicleDetails(vehicleId, serial) {
                // Hide results, show details
                $('#vehicle-results').removeClass('active');
                $('#vehicle-details-panel').addClass('active');

                // Load full vehicle data
                $.ajax({
                    url: 'api/vehicles.php',
                    method: 'GET',
                    data: {
                        id: vehicleId
                    },
                    success: function(response) {
                        if (response.success &&  response.data && response.data.length > 0) {
                            const v = response.data[0];
                            currentVehicleId = vehicleId;

                            $('#details-vehicle-title').text(`Vehicle: ${v.serial || v.reg_no || 'Unknown'}`);

                            // Build detailed grid
                            let gridHtml = `
                            <div class="detail-item"><div class="detail-label">Registration No</div><div class="detail-value">${escapeHtml(v.reg_no) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Customer Name</div><div class="detail-value">${escapeHtml(v.cus_name) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Contact</div><div class="detail-value">${escapeHtml(v.contact) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Make</div><div class="detail-value">${escapeHtml(v.make) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Model</div><div class="detail-value">${escapeHtml(v.model) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">VIN Number</div><div class="detail-value">${escapeHtml(v.vin_no) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Chassis Number</div><div class="detail-value">${escapeHtml(v.chasis) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Dealer</div><div class="detail-value">${escapeHtml(v.dealer) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Serial (Device ID)</div><div class="detail-value">${escapeHtml(v.serial) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Tech</div><div class="detail-value">${escapeHtml(v.tech) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Action</div><div class="detail-value">${escapeHtml(v.action) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value"><span class="badge ${v.status_renew == 1 ? 'badge-success' : 'badge-warning'}">${v.status_renew == 1 ? 'Active' : 'Inactive'}</span></div></div>
                            <div class="detail-item"><div class="detail-label">Last Updated</div><div class="detail-value">${escapeHtml(v.upd_time) || 'N/A'}</div></div>
                            <div class="detail-item"><div class="detail-label">Online Status</div><div class="detail-value">${escapeHtml(v.online) || 'N/A'}</div></div>
                        `;

                            $('#vehicle-data-grid').html(gridHtml);

                            // Start telemetry stream
                            startTelemetryStream(serial);
                        }
                    }
                });
            }

            function startTelemetryStream(serial) {
                if (!serial) {
                    $('#telemetry-stream-body').html('<tr><td colspan="7" class="text-center">No serial number available for telemetry</td></tr>');
                    return;
                }

                loadTelemetry(serial);
                telemetryInterval = setInterval(() => loadTelemetry(serial), 5000);
            }

            function loadTelemetry(serial) {
                $.ajax({
                    url: 'api/telemetry.php',
                    method: 'GET',
                    data: {
                        serial: serial,
                        limit: 100
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            let html = '';
                            response.data.forEach(point => {
                                const dateObj = new Date(point.devicetime);
                                const dateStr = dateObj.toLocaleDateString();
                                const timeStr = dateObj.toLocaleTimeString();
                                const mapUrl = `https://www.google.com/maps?q=${point.latitude},${point.longitude}`;

                                html += `
                                <tr>
                                    <td>${dateStr}</td>
                                    <td>${timeStr}</td>
                                    <td>${(point.speed || 0).toFixed(1)}</td>
                                    <td>${(point.latitude || 0).toFixed(6)}</td>
                                    <td>${(point.longitude || 0).toFixed(6)}</td>
                                    <td><a href="${mapUrl}" target="_blank" class="map-link">🗺️ View</a></td>
                                    <td>${escapeHtml(point.attributes || '')}</td>
                                </tr>
                            `;
                            });
                            $('#telemetry-stream-body').html(html);
                            $('#telemetry-timestamp').text('Last updated: ' + new Date().toLocaleTimeString());
                        } else {
                            $('#telemetry-stream-body').html('<tr><td colspan="7" class="text-center">No telemetry data available</td></tr>');
                        }
                    },
                    error: function() {
                        $('#telemetry-stream-body').html('<tr><td colspan="7" class="text-center" style="color: red;">Error loading telemetry</td></tr>');
                    }
                });
            }

            // Close details and return to search
            $('#close-details').click(function() {
                $('#vehicle-details-panel').removeClass('active');
                $('#search-input').val('');
                $('#vehicle-results').addClass('active');
                currentVehicleId = null;
                if (telemetryInterval) {
                    clearInterval(telemetryInterval);
                    telemetryInterval = null;
                }
            });

            // Print report
            $('#print-report').click(function() {
                window.print();
            });

            // Manual refresh telemetry
            $('#refresh-telemetry').click(function() {
                if (currentVehicleId) {
                    const serial = $('#vehicle-data-grid').contains('[data-label="Serial (Device ID)"]').text().trim();
                    loadTelemetry(serial);
                }
            });

            // Helper: Escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
</body>

</html>