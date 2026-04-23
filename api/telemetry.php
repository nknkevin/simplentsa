<?php
/**
 * Telemetry API Endpoint
 * Fetches real-time telemetry data from uradi (Traccar) database
 * 
 * Data Flow:
 * 1. User views vehicle from alexa.vehicles (serial field)
 * 2. System finds device in uradi.devices where uniqueid = alexa.vehicles.serial
 * 3. System fetches telemetry from uradi.eventData where deviceid = uradi.devices.id
 */

session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../config/database.php';

$response = ['success' => false, 'message' => '', 'data' => null];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

// Get vehicle serial from request (links alexa.vehicles.serial to uradi.devices.uniqueid)
$serial = isset($_GET['serial']) ? trim($_GET['serial']) : '';
$serial = '0'.$serial;

if (empty($serial)) {
    $response['message'] = 'Vehicle serial required';
    echo json_encode($response);
    exit;
}

try {
    // Connect to Uradi database (Traccar - telemetry data)
    $uradiPDO = getUradiDB();
    
    // Step 1: Find device by uniqueid (which equals alexa.vehicles.serial)
    $deviceStmt = $uradiPDO->prepare("SELECT `id`, `name`, `online`, `rand`, `lastupdate` FROM `device` WHERE `uniqueid` = ? AND `disabled` = 0");
    $deviceStmt->execute([$serial]);
    $device = $deviceStmt->fetch();
    
    if (!$device) {
        $response['message'] = 'Device not found for serial: ' . $serial;
        echo json_encode($response);
        exit;
    }
    
    $deviceId = $device['id'];
    
    // Step 2: Get latest telemetry data from eventData for this device
    $telemetryStmt = $uradiPDO->prepare("
        SELECT 
            id, servertime, fixtime, eactime, latitude, longitude,
            speed, attributes, statuscode, signalwireconnected, powerwireconnected
        FROM eventData 
        WHERE deviceid = ? 
        ORDER BY fixtime DESC 
        LIMIT 1
    ");
    $telemetryStmt->execute([$deviceId]);
    $telemetry = $telemetryStmt->fetch();
    
    if ($telemetry) {
        // Add device info to response
        $telemetry['device_name'] = $device['name'];
        $telemetry['device_status'] = ($device['online'] == 0) ? 'online' : 'offline';
        $telemetry['last_update'] = $device['lastupdate'];
        $telemetry['serial'] = $serial;
        $response['success'] = true;
        $response['data'] = $telemetry;
    } else {
        $response['success'] = true;
        $response['data'] = [
            'device_name' => $device['uniqueid'],
            'device_status' => ($device['online'] == 0) ? 'online' : 'offline',
            'last_update' => $device['lastupdate'],
            'serial' => $serial,
            'message' => 'No telemetry data available'
        ];
        $response['message'] = 'Device found but no telemetry data';
    }
    
} catch (Exception $e) {
    error_log("Telemetry error: " . $e->getMessage());
    $response['message'] = 'Failed to fetch telemetry: ' . $e->getMessage();
}

echo json_encode($response);
