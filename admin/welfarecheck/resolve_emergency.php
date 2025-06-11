<?php
session_start();
require_once '../../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if emergency_id is provided
if (!isset($_POST['emergency_id'])) {
    echo json_encode(['success' => false, 'message' => 'Emergency ID is required']);
    exit();
}

// Check if resolution_reason is provided
if (!isset($_POST['resolution_reason']) || strlen($_POST['resolution_reason']) < 10) {
    echo json_encode(['success' => false, 'message' => 'Resolution reason is required (minimum 10 characters)']);
    exit();
}

$emergency_id = $_POST['emergency_id'];
$resolution_reason = $_POST['resolution_reason'];
$resolved_by = $_SESSION['admin_id'];
$resolved_at = date('Y-m-d H:i:s');

// Start transaction
$conn->begin_transaction();

try {
    // Update emergency status and add resolution details
    $sql = "UPDATE emergencies 
            SET is_active = 0, 
                resolution_reason = ?, 
                resolved_by = ?, 
                resolved_at = ? 
            WHERE emergency_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $resolution_reason, $resolved_by, $resolved_at, $emergency_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating emergency: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 