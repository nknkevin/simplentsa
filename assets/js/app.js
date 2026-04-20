/**
 * Vehicle Tracking System - Frontend JavaScript
 * Handles login, vehicle search, and telemetry updates
 */

const API_BASE = 'api/';
const TELEMETRY_INTERVAL = 5000; // 5 seconds

let telemetryTimer = null;
let selectedVehicleId = null;

// Initialize app when DOM is ready
$(document).ready(function() {
    console.log('Vehicle Tracking System initialized');
    
    // Check if on dashboard page
    if ($('#vehicle-list').length) {
        initDashboard();
    }
    
    // Check if on login page
    if ($('#login-form').length) {
        initLogin();
    }
});

/**
 * Login Page Functions
 */
function initLogin() {
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        const username = $('#username').val().trim();
        const password = $('#password').val();
        
        if (!username || !password) {
            showAlert('Please enter username and password', 'error');
            return;
        }
        
        const btn = $('#login-btn');
        btn.prop('disabled', true).html('<span class="loading"></span> Logging in...');
        
        $.ajax({
            url: API_BASE + 'login.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ username, password }),
            success: function(response) {
                if (response.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    showAlert(response.message, 'error');
                    btn.prop('disabled', false).text('Login');
                }
            },
            error: function() {
                showAlert('Connection error. Please try again.', 'error');
                btn.prop('disabled', false).text('Login');
            }
        });
    });
}

/**
 * Dashboard Functions
 */
function initDashboard() {
    loadVehicles();
    setupEventHandlers();
}

function setupEventHandlers() {
    // Search functionality
    $('#search-btn').on('click', function() {
        loadVehicles();
    });
    
    $('#search-input').on('keypress', function(e) {
        if (e.which === 13) {
            loadVehicles();
        }
    });
    
    // Logout
    $('#logout-btn').on('click', function() {
        $.ajax({
            url: API_BASE + 'logout.php',
            type: 'POST',
            success: function() {
                window.location.href = 'index.php';
            }
        });
    });
    
    // Refresh telemetry button
    $('#refresh-telemetry').on('click', function() {
        if (selectedVehicleId) {
            loadTelemetry(selectedVehicleId);
        }
    });
}

function loadVehicles() {
    const search = $('#search-input').val().trim();
    const vehicleList = $('#vehicle-list');
    
    vehicleList.html('<div class="text-center"><span class="loading"></span> Loading vehicles...</div>');
    
    $.ajax({
        url: API_BASE + 'vehicles.php',
        type: 'GET',
        data: { search: search, limit: 20 },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                displayVehicles(response.data);
            } else {
                vehicleList.html('<div class="text-center">No vehicles found</div>');
            }
        },
        error: function() {
            vehicleList.html('<div class="text-center">Error loading vehicles</div>');
        }
    });
}

function displayVehicles(vehicles) {
    const vehicleList = $('#vehicle-list');
    vehicleList.empty();
    
    vehicles.forEach(function(vehicle) {
        const card = $(`
            <div class="vehicle-card" data-vehicle-id="${vehicle.id}">
                <div class="vehicle-header">
                    <div class="vehicle-name">${escapeHtml(vehicle.vehicle_name)}</div>
                    <span class="vehicle-status ${vehicle.status === 'active' ? 'status-active' : 'status-inactive'}">
                        ${vehicle.status}
                    </span>
                </div>
                <div class="vehicle-details">
                    <div><strong>Plate:</strong> ${escapeHtml(vehicle.plate_number)}</div>
                    <div><strong>Model:</strong> ${escapeHtml(vehicle.model)}</div>
                    <div><strong>VIN:</strong> ${escapeHtml(vehicle.vin)}</div>
                    <div><strong>Year:</strong> ${vehicle.year}</div>
                </div>
            </div>
        `);
        
        card.on('click', function() {
            selectVehicle(vehicle.id, vehicle.vehicle_name);
        });
        
        vehicleList.append(card);
    });
}

function selectVehicle(vehicleId, vehicleName) {
    selectedVehicleId = vehicleId;
    
    // Update UI
    $('.vehicle-card').removeClass('selected');
    $(`.vehicle-card[data-vehicle-id="${vehicleId}"]`).addClass('selected');
    
    // Show telemetry panel
    $('#telemetry-panel').removeClass('hidden');
    $('#telemetry-vehicle-name').text(vehicleName);
    
    // Load telemetry immediately
    loadTelemetry(vehicleId);
    
    // Start auto-refresh
    startTelemetryRefresh();
}

function loadTelemetry(vehicleId) {
    $.ajax({
        url: API_BASE + 'telemetry.php',
        type: 'GET',
        data: { vehicle_id: vehicleId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                displayTelemetry(response.data);
            }
        },
        error: function() {
            console.error('Failed to load telemetry');
        }
    });
}

function displayTelemetry(data) {
    $('#telemetry-timestamp').text(new Date().toLocaleTimeString());
    $('#telemetry-speed').text((data.speed || 0) + ' km/h');
    $('#telemetry-location').text(`${(data.latitude || 0).toFixed(6)}, ${(data.longitude || 0).toFixed(6)}`);
    $('#telemetry-heading').text((data.heading || 0) + '°');
    $('#telemetry-fuel').text((data.fuel_level || 0) + '%');
    $('#telemetry-engine').text(data.engine_status || 'unknown');
    $('#telemetry-altitude').text((data.altitude || 0) + ' m');
    $('#telemetry-odometer').text((data.odometer || 0).toLocaleString() + ' km');
}

function startTelemetryRefresh() {
    // Clear existing timer
    if (telemetryTimer) {
        clearInterval(telemetryTimer);
    }
    
    // Set new timer
    telemetryTimer = setInterval(function() {
        if (selectedVehicleId) {
            loadTelemetry(selectedVehicleId);
        }
    }, TELEMETRY_INTERVAL);
}

function stopTelemetryRefresh() {
    if (telemetryTimer) {
        clearInterval(telemetryTimer);
        telemetryTimer = null;
    }
}

/**
 * Utility Functions
 */
function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    const alertHtml = `<div class="alert ${alertClass}">${escapeHtml(message)}</div>`;
    
    $('.login-card').prepend(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Clean up on page unload
$(window).on('beforeunload', function() {
    stopTelemetryRefresh();
});
