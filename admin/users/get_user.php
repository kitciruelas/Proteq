<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
require_once '../../includes/db.php';

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$user_id = $_GET['user_id'];

// Fetch user data
$sql = "SELECT * FROM general_users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user = $result->fetch_assoc();

// Return user data
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $user
]); 