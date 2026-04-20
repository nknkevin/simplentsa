<?php
/**
 * Telemetry API Endpoint
 * Fetch real-time telemetry data from tracking server
 */

session_start();
header('Content-Type: application/json');

require_once '../config/config.php';

$response = ['success' => false, 'message' => '', 'data' => null];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

// Get vehicle ID
$vehicleId = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : 0;

if ($vehicleId <= 0) {
    $response['message'] = 'Invalid vehicle ID';
    echo json_encode($response);
    exit;
}

try {
    // Connect to remote tracking server
    $trackingUrl = TRACKING_SERVER_URL . '/vehicle/' . $vehicleId . '/telemetry';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $trackingUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, TRACKING_SERVER_TIMEOUT);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $telemetryData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($httpCode === 200 && $telemetryData) {
        $data = json_decode($telemetryData, true);
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        // Return mock data for demonstration (remove in production)
        $response['success'] = true;
        $response['data'] = [
            'vehicle_id' => $vehicleId,
            'timestamp' => date('Y-m-d H:i:s'),
            'latitude' => 40.7128 + (rand() / getrandmax() * 0.01),
            'longitude' => -74.0060 + (rand() / getrandmax() * 0.01),
            'speed' => rand(0, 120),
            'heading' => rand(0, 360),
            'altitude' => rand(0, 500),
            'fuel_level' => rand(10, 100),
            'engine_status' => rand(0, 1) ? 'running' : 'stopped',
            'odometer' => rand(10000, 100000)
        ];
        $response['message'] = 'Using mock data (tracking server unavailable)';
    }
    
} catch (Exception $e) {
    error_log("Telemetry error: " . $e->getMessage());
    $response['message'] = 'Failed to fetch telemetry data';
}

echo json_encode($response);
