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

$staff_id = $_SESSION['user_id'];

try {
    require_once '../../includes/db.php';
    
    // Get staff availability
    $query = "SELECT availability FROM staff WHERE staff_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $staff_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Database execute error: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $staff = mysqli_fetch_assoc($result);
    
    if (!$staff) {
        throw new Exception("Staff not found");
    }
    
    echo json_encode([
        'success' => true,
        'availability' => $staff['availability'] ?? 'off_duty' // Default to off_duty if null
    ]);
    
} catch (Exception $e) {
    error_log("Get availability error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
}
?> 