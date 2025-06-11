<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate data
if (!isset($data['availability'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing availability data']);
    exit();
}

// Validate availability status
$valid_statuses = ['available', 'busy', 'off_duty'];
$availability = $data['availability'];

if (!in_array($availability, $valid_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid availability status',
        'current_status' => $availability
    ]);
    exit();
}

$staff_id = $_SESSION['user_id'];

try {
    require_once '../../includes/db.php';
    
    // Update staff availability
    $query = "UPDATE staff SET availability = ?, updated_at = NOW() WHERE staff_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "si", $availability, $staff_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Database execute error: " . mysqli_stmt_error($stmt));
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Availability updated successfully',
        'status' => $availability,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Availability update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'current_status' => $availability
    ]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
}
?> 