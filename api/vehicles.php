<?php
/**
 * Vehicle Search API Endpoint
 * Search vehicles from remote database
 */

session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../config/database.php';

$response = ['success' => false, 'message' => '', 'data' => []];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

try {
    // Get search parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    
    $pdo = getVehicleDB();
    
    // Build search query
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT id, vehicle_name, plate_number, vin, model, year, status 
            FROM vehicles 
            WHERE vehicle_name LIKE ? 
               OR plate_number LIKE ? 
               OR vin LIKE ?
            LIMIT ?
        ");
        $searchTerm = "%{$search}%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, vehicle_name, plate_number, vin, model, year, status 
            FROM vehicles 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
    }
    
    $vehicles = $stmt->fetchAll();
    
    $response['success'] = true;
    $response['data'] = $vehicles;
    
} catch (Exception $e) {
    error_log("Vehicle search error: " . $e->getMessage());
    $response['message'] = 'Failed to search vehicles';
}

echo json_encode($response);
