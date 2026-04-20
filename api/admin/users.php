<?php
/**
 * User Management API Endpoint
 * Handles user creation, password changes, and user listing
 */

session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/database.php';

$response = ['success' => false, 'message' => '', 'data' => null];

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    $pdo = getLocalDB();
    
    switch ($action) {
        case 'list':
            // Get all users
            $stmt = $pdo->query("SELECT id, username, role, active, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();
            $response['success'] = true;
            $response['data'] = $users;
            break;
            
        case 'create':
            // Create new user (POST request)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Invalid request method';
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $username = isset($input['username']) ? trim($input['username']) : '';
            $password = isset($input['password']) ? $input['password'] : '';
            $role = isset($input['role']) ? $input['role'] : 'user';
            
            if (empty($username) || empty($password)) {
                $response['message'] = 'Username and password are required';
                break;
            }
            
            if (!in_array($role, ['user', 'admin'])) {
                $response['message'] = 'Invalid role';
                break;
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $response['message'] = 'Username already exists';
                break;
            }
            
            // Create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$username, $hashedPassword, $role]);
            
            $response['success'] = true;
            $response['message'] = 'User created successfully';
            $response['data'] = ['id' => $pdo->lastInsertId(), 'username' => $username, 'role' => $role];
            break;
            
        case 'change_password':
            // Change user password (POST request)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Invalid request method';
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
            $newPassword = isset($input['new_password']) ? $input['new_password'] : '';
            
            if ($userId <= 0 || empty($newPassword)) {
                $response['message'] = 'Valid user ID and new password are required';
                break;
            }
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                $response['message'] = 'User not found';
                break;
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            $response['success'] = true;
            $response['message'] = 'Password changed successfully';
            break;
            
        case 'toggle_active':
            // Toggle user active status (POST request)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Invalid request method';
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
            
            if ($userId <= 0) {
                $response['message'] = 'Valid user ID is required';
                break;
            }
            
            // Prevent admin from disabling themselves
            if ($userId == $_SESSION['user_id']) {
                $response['message'] = 'Cannot disable your own account';
                break;
            }
            
            // Toggle active status
            $stmt = $pdo->prepare("UPDATE users SET active = NOT active WHERE id = ?");
            $stmt->execute([$userId]);
            
            $response['success'] = true;
            $response['message'] = 'User status updated successfully';
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
    
} catch (Exception $e) {
    error_log("User management error: " . $e->getMessage());
    $response['message'] = 'Operation failed: ' . $e->getMessage();
}

echo json_encode($response);
