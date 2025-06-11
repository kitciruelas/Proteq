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

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate password
function isValidPassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    // Get form data
    $staff_id = isset($_POST['staff_id']) ? $_POST['staff_id'] : null;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';
    $availability = isset($_POST['availability']) ? trim($_POST['availability']) : 'available';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

    // Validate required fields
    if (empty($name) || empty($email) || empty($role)) {
        $response['message'] = 'All fields are required';
        echo json_encode($response);
        exit();
    }

    // Validate email
    if (!isValidEmail($email)) {
        $response['message'] = 'Invalid email format';
        echo json_encode($response);
        exit();
    }

    // Check if email already exists
    $check_email = $conn->prepare("SELECT staff_id FROM staff WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        $response['message'] = 'Email already exists';
        echo json_encode($response);
        exit();
    }

    // If creating new staff member
    if (!$staff_id) {
        // Validate password for new staff
        if (empty($password) || !isValidPassword($password)) {
            $response['message'] = 'Password must be at least 8 characters long and contain at least one uppercase letter and one number';
            echo json_encode($response);
            exit();
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new staff member
        $stmt = $conn->prepare("INSERT INTO staff (name, email, role, availability, password, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $role, $availability, $hashed_password, $status);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Staff member created successfully';
        } else {
            $response['message'] = 'Error creating staff member: ' . $conn->error;
        }
    } else {
        // Update existing staff member
        $stmt = $conn->prepare("UPDATE staff SET name = ?, email = ?, role = ?, availability = ?, status = ? WHERE staff_id = ?");
        $stmt->bind_param("sssssi", $name, $email, $role, $availability, $status, $staff_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Staff member updated successfully';
        } else {
            $response['message'] = 'Error updating staff member: ' . $conn->error;
        }
    }

    echo json_encode($response);
    exit();
}

// Handle GET request for fetching staff member
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['staff_id'])) {
    $staff_id = (int)$_GET['staff_id'];
    
    $stmt = $conn->prepare("SELECT staff_id, name, email, role, availability, status FROM staff WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($staff = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $staff]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Staff member not found']);
    }
    exit();
}

// Invalid request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit(); 