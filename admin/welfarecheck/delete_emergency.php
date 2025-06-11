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

$emergency_id = $_POST['emergency_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Delete welfare checks first (due to foreign key constraint)
    $sql = "DELETE FROM welfare_checks WHERE emergency_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $emergency_id);
    $stmt->execute();

    // Delete emergency
    $sql = "DELETE FROM emergencies WHERE emergency_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $emergency_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 