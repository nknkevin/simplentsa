<?php
/**
 * Vehicle Viewer Page
 * Displays vehicle data from alexa database (read-only, synced from external server)
 * Shows telemetry data from uradi (Traccar) when available
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vehicles</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 8px;
        }
        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .vehicle-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .vehicle-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .vehicle-card h3 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        .vehicle-detail {
            font-size: 0.9rem;
            color: #666;
            margin: 0.3rem 0;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-online { background: #d4edda; color: #155724; }
        .status-offline { background: #f8d7da; color: #721c24; }
        .btn-telemetry {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 1rem;
            width: 100%;
        }
        .btn-telemetry:hover {
            background: #218838;
        }
        .telemetry-data {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
            display: none;
        }
        .telemetry-data.active {
            display: block;
        }
        .loading {
            text-align: center;
            padding: 2rem;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>🚗 My Vehicles</h1>
            <p>View your fleet and real-time telemetry</p>
            <a href="../dashboard.php" style="color: white; text-decoration: underline;">← Back to Dashboard</a>
        </div>

        <!-- Search Bar -->
        <div style="margin-bottom: 2rem;">
            <input type="text" id="searchInput" placeholder="Search by reg_no, customer, serial, make, model..." 
                   style="width: 100%; padding: 0.75rem; border: 2px solid #ddd; border-radius: 4px; font-size: 1rem;">
        </div>

        <!-- Loading Indicator -->
        <div id="loading" class="loading">Loading vehicles...</div>

        <!-- Vehicles List -->
        <div id="vehiclesList" class="vehicle-grid"></div>
    </div>

    <script>
        // Load vehicles
        async function loadVehicles(search = '') {
            const loading = document.getElementById('loading');
            const container = document.getElementById('vehiclesList');
            
            try {
                const url = search 
                    ? `../api/vehicles.php?search=${encodeURIComponent(search)}`
                    : '../api/vehicles.php';
                
                const response = await fetch(url);
                const result = await response.json();
                
                loading.style.display = 'none';
                
                if (result.success) {
                    renderVehicles(result.data);
                } else {
                    container.innerHTML = `<p style="color: red;">Error: ${result.message}</p>`;
                }
            } catch (error) {
                console.error('Error loading vehicles:', error);
                loading.style.display = 'none';
                container.innerHTML = `<p style="color: red;">Failed to load vehicles</p>`;
            }
        }

        // Render vehicles list
        function renderVehicles(vehicles) {
            const container = document.getElementById('vehiclesList');
            
            if (vehicles.length === 0) {
                container.innerHTML = '<p>No vehicles found</p>';
                return;
            }
            
            container.innerHTML = vehicles.map(vehicle => `
                <div class="vehicle-card">
                    <h3>${escapeHtml(vehicle.cus_name || 'N/A')}</h3>
                    <div class="vehicle-detail"><strong>Reg No:</strong> ${escapeHtml(vehicle.reg_no || 'N/A')}</div>
                    <div class="vehicle-detail"><strong>Make:</strong> ${escapeHtml(vehicle.make || 'N/A')}</div>
                    <div class="vehicle-detail"><strong>Model:</strong> ${escapeHtml(vehicle.model || 'N/A')}</div>
                    <div class="vehicle-detail"><strong>VIN:</strong> ${escapeHtml(vehicle.vin_no || 'N/A')}</div>
                    <div class="vehicle-detail"><strong>Chassis:</strong> ${escapeHtml(vehicle.chasis || 'N/A')}</div>
                    <div class="vehicle-detail"><strong>Serial:</strong> ${escapeHtml(vehicle.serial || 'N/A')}</div>
                    <div class="vehicle-detail"><strong>Dealer:</strong> ${escapeHtml(vehicle.dealer || 'N/A')}</div>
                    <div class="vehicle-detail"><strong>Action:</strong> ${escapeHtml(vehicle.action || 'N/A')}</div>
                    <div class="vehicle-detail"><strong>Tech:</strong> ${escapeHtml(vehicle.tech || 'N/A')}</div>
                    <div class="vehicle-detail"><strong>Contact:</strong> ${escapeHtml(vehicle.contact || 'N/A')}</div>
                    <div class="vehicle-detail" style="margin-top: 0.5rem;">
                        <strong>Status:</strong> 
                        <span class="status-badge ${vehicle.online === 'true' || vehicle.online === '1' ? 'status-online' : 'status-offline'}">
                            ${vehicle.online === 'true' || vehicle.online === '1' ? 'ONLINE' : 'OFFLINE'}
                        </span>
                    </div>
                    <div class="vehicle-detail"><strong>Last Update:</strong> ${escapeHtml(vehicle.upd_time || 'N/A')}</div>
                    
                    <button class="btn-telemetry" onclick="toggleTelemetry(${vehicle.id}, '${escapeHtml(vehicle.serial)}')">
                        📡 View Telemetry
                    </button>
                    
                    <div id="telemetry-${vehicle.id}" class="telemetry-data"></div>
                </div>
            `).join('');
        }

        // Toggle telemetry display
        async function toggleTelemetry(vehicleId, serial) {
            const telemetryDiv = document.getElementById(`telemetry-${vehicleId}`);
            
            if (telemetryDiv.classList.contains('active')) {
                telemetryDiv.classList.remove('active');
                return;
            }
            
            telemetryDiv.innerHTML = '<div class="loading">Fetching telemetry...</div>';
            telemetryDiv.classList.add('active');
            
            try {
                const response = await fetch(`../api/telemetry.php?serial=${encodeURIComponent(serial)}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const data = result.data;
                    telemetryDiv.innerHTML = `
                        <h4 style="margin-bottom: 0.5rem; color: #667eea;">📍 Live Telemetry</h4>
                        <div class="vehicle-detail"><strong>Device:</strong> ${escapeHtml(data.device_name || 'N/A')}</div>
                        <div class="vehicle-detail"><strong>Latitude:</strong> ${data.latitude ? data.latitude.toFixed(6) : 'N/A'}</div>
                        <div class="vehicle-detail"><strong>Longitude:</strong> ${data.longitude ? data.longitude.toFixed(6) : 'N/A'}</div>
                        <div class="vehicle-detail"><strong>Speed:</strong> ${data.speed ? data.speed + ' km/h' : 'N/A'}</div>
                        <div class="vehicle-detail"><strong>Course:</strong> ${data.course ? data.course + '°' : 'N/A'}</div>
                        <div class="vehicle-detail"><strong>Fuel:</strong> ${data.fuelLevel ? data.fuelLevel + '%' : 'N/A'}</div>
                        <div class="vehicle-detail"><strong>Battery:</strong> ${data.batteryLevel ? data.batteryLevel + 'V' : 'N/A'}</div>
                        <div class="vehicle-detail"><strong>Motion:</strong> ${data.motion ? 'Moving' : 'Stopped'}</div>
                        <div class="vehicle-detail"><strong>Last Update:</strong> ${escapeHtml(data.eventTime || data.last_update || 'N/A')}</div>
                        ${data.address ? `<div class="vehicle-detail"><strong>Address:</strong> ${escapeHtml(data.address)}</div>` : ''}
                    `;
                } else {
                    telemetryDiv.innerHTML = `<p style="color: #dc3545;">${escapeHtml(result.message || 'No telemetry data available')}</p>`;
                }
            } catch (error) {
                console.error('Error fetching telemetry:', error);
                telemetryDiv.innerHTML = '<p style="color: #dc3545;">Failed to fetch telemetry</p>';
            }
        }

        // Search functionality
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadVehicles(e.target.value);
            }, 300);
        });

        // Utility: Escape HTML
        function escapeHtml(text) {
            if (!text) return 'N/A';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initial load
        loadVehicles();
    </script>
</body>
</html>
