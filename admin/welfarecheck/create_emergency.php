<?php
session_start();
require_once '../../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin-login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_emergency'])) {
    // Validate required fields
    if (!isset($_POST['emergency_type']) || !isset($_POST['description'])) {
        header("Location: ../emergency_management.php?error=missing_fields");
        exit();
    }

    $emergency_type = $_POST['emergency_type'];
    $description = $_POST['description'];
    $triggered_by = $_SESSION['admin_id'];
    $triggered_at = date('Y-m-d H:i:s');

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert new emergency
        $sql = "INSERT INTO emergencies (emergency_type, description, triggered_by, triggered_at) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssis", $emergency_type, $description, $triggered_by, $triggered_at);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating emergency: " . $stmt->error);
        }

        // Commit transaction
        $conn->commit();
        header("Location: ../emergency_management.php?success=1");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: ../emergency_management.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If not POST request, redirect to emergency management page
    header("Location: ../emergency_management.php");
    exit();
}
?>