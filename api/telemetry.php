<?php
/**
 * Telemetry Data API
 * Fetches real-time telemetry data from uradi.eventData
 * 
 * Parameters:
 * - serial: Device serial number (required)
 * - limit: Number of records to fetch (default: 100 for initial load, 50 for streaming)
 * - since_id: Fetch only records with id > since_id (for streaming new data only)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

try {
    $alexaDB = getAlexaDB();
    $uradiDB = getUradiDB();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $serial = isset($_GET['serial']) ? trim($_GET['serial']) : '';
        $serial = '0'.$serial;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $sinceId = isset($_GET['since_id']) ? intval($_GET['since_id']) : 0;
        
        if (empty($serial)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Serial number is required'
            ]);
            exit();
        }
        
        // Step 1: Find device in uradi.device using uniqueid = serial
        $stmt = $uradiDB->prepare("SELECT id, name, uniqueId FROM device WHERE uniqueid = ? LIMIT 1");
        $stmt->execute([$serial]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$device) {
            // Log for debugging
            error_log("Telemetry API: Device not found for serial: " . $serial);
            
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Device not found in Tracking system. Ensure serial is correct.',
                'debug' => [
                    'serial_searched' => $serial,
                    'suggestion' => 'Check if device exists in tracking server with serial = "' . $serial . '"'
                ]
            ]);
            exit();
        }
        
        $deviceId = $device['id'];
        
        // Step 2: Fetch telemetry data from eventData
        if ($sinceId > 0) {
            // Streaming mode: Get only NEW records since last known ID
            $stmt = $uradiDB->prepare("
                SELECT id, deviceid, devicetime, latitude, longitude, speed, course, attributes
                FROM eventData
                WHERE deviceid = ? AND id > ?
                ORDER BY id ASC
                LIMIT ?
            ");
            $stmt->execute([$deviceId, $sinceId, $limit]);
        } else {
            // Initial load: Get LAST N records (most recent first)
            $stmt = $uradiDB->prepare("
                SELECT id, deviceid, devicetime, latitude, longitude, speed, course, attributes
                FROM eventData
                WHERE deviceid = ?
                ORDER BY id DESC
                LIMIT ?
            ");
            $stmt->execute([$deviceId, $limit]);
        }
        
        $telemetryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If initial load (DESC), reverse to show oldest first in the list
        if ($sinceId === 0 && count($telemetryData) > 0) {
            $telemetryData = array_reverse($telemetryData);
        }
        
        // Format response
        $formattedData = [];
        foreach ($telemetryData as $row) {
            $attributes = !empty($row['attributes']) ? json_decode($row['attributes'], true) : [];
            $dataNotes = '';
            
            if (is_array($attributes)) {
                $notes = [];
                if (isset($attributes['alarm'])) $notes[] = "Alarm: " . $attributes['alarm'];
                if (isset($attributes['ignition'])) $notes[] = "Ignition: " . ($attributes['ignition'] ? 'ON' : 'OFF');
                if (isset($attributes['battery'])) $notes[] = "Battery: " . $attributes['battery'] . "V";
                if (isset($attributes['fuel'])) $notes[] = "Fuel: " . $attributes['fuel'] . "%";
                
                $dataNotes = implode(" | ", $notes);
            }
            
            $formattedData[] = [
                'id' => (int)$row['id'],
                'devicetime' => $row['devicetime'],
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'speed' => (float)($row['speed'] ?? 0),
                'course' => (float)($row['course'] ?? 0),
                'attributes' => $dataNotes
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $formattedData,
            'count' => count($formattedData),
            'device' => [
                'id' => $deviceId,
                'name' => $device['name'],
                'uniqueId' => $device['uniqueId']
            ],
            'query' => [
                'mode' => $sinceId > 0 ? 'streaming' : 'initial',
                'since_id' => $sinceId,
                'limit' => $limit
            ]
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (PDOException $e) {
    error_log("Telemetry API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Telemetry API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ]);
}
?>