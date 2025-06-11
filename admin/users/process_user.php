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

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$department = $_POST['department'] ?? '';
$college = $_POST['college'] ?? '';
$user_type = $_POST['user_type'] ?? '';
$status = $_POST['status'] ?? 1;
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email) || empty($department) || empty($college) || empty($user_type)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Check if email already exists (excluding current user for updates)
$check_sql = "SELECT user_id FROM general_users WHERE email = ? AND user_id != ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("si", $email, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit();
}

// If user_id is provided, update existing user
if ($user_id) {
    // Update user
    $sql = "UPDATE general_users SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            department = ?, 
            college = ?, 
            user_type = ?, 
            status = ? 
            WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssii", $first_name, $last_name, $email, $department, $college, $user_type, $status, $user_id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $conn->error]);
    }
} else {
    // Validate password for new users
    if (empty($password)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Password is required for new users']);
        exit();
    }

    // Validate password complexity
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long and contain at least one uppercase letter and one number']);
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $sql = "INSERT INTO general_users (first_name, last_name, email, department, college, user_type, password, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $first_name, $last_name, $email, $department, $college, $user_type, $hashed_password, $status);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error creating user: ' . $conn->error]);
    }
}

$conn->close(); 