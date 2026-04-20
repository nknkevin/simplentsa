<?php
/**
 * Login API Endpoint
 * Handles user authentication
 */

session_start();
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($username) || empty($password)) {
    $response['message'] = 'Username and password are required';
    echo json_encode($response);
    exit;
}

try {
    // Check local database for user
    $pdo = getLocalDB();
    
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        $response['success'] = true;
        $response['message'] = 'Login successful';
        $response['data'] = [
            'username' => $user['username'],
            'role' => $user['role']
        ];
    } else {
        $response['message'] = 'Invalid username or password';
    }
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $response['message'] = 'Authentication failed';
}

echo json_encode($response);
