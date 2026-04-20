<?php
/**
 * Vehicle Management API Endpoint
 * Fetches vehicle data from alexa database (synced from external server)
 * Schema matches external server: reg_no, contact, cus_name, make, model, vin_no, 
 * chasis, dealer, action, tech, serial, date, status_renew, number, warning_sent, 
 * sms_sent, upd_time, online, online_i
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
    // Connect to Alexa database (contains users + vehicles)
    $pdo = getAlexaDB();
    
    // Handle GET requests (search/view operations)
    // Note: Vehicle data is managed by external server, read-only here
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    
    // Build search query across all relevant fields
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT id, reg_no, contact, cus_name, make, model, vin_no, chasis, 
                   dealer, action, tech, serial, date, status_renew, number, 
                   warning_sent, sms_sent, upd_time, online, online_i
            FROM vehicles 
            WHERE reg_no LIKE ? 
               OR contact LIKE ?
               OR cus_name LIKE ?
               OR make LIKE ?
               OR model LIKE ?
               OR vin_no LIKE ?
               OR chasis LIKE ?
               OR dealer LIKE ?
               OR action LIKE ?
               OR tech LIKE ?
               OR serial LIKE ?
               OR number LIKE ?
            LIMIT ?
        ");
        $searchTerm = "%{$search}%";
        $stmt->execute([
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm, $limit
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, reg_no, contact, cus_name, make, model, vin_no, chasis, 
                   dealer, action, tech, serial, date, status_renew, number, 
                   warning_sent, sms_sent, upd_time, online, online_i
            FROM vehicles 
            ORDER BY upd_time DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
    }
    
    $vehicles = $stmt->fetchAll();
    
    $response['success'] = true;
    $response['data'] = $vehicles;
    $response['count'] = count($vehicles);
    
} catch (Exception $e) {
    error_log("Vehicle API error: " . $e->getMessage());
    $response['message'] = 'Failed to fetch vehicles: ' . $e->getMessage();
}

echo json_encode($response);
