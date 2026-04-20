<?php
/**
 * Logout API Endpoint
 * Destroys user session
 */

session_start();
header('Content-Type: application/json');

// Destroy session
session_unset();
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
