<?php
session_start();
require_once '../../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Handle file upload
function handleFileUpload($file) {
    $target_dir = "../../uploads/protocols/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }

    // Check file type
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, and PNG files are allowed'];
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    }

    return ['success' => false, 'message' => 'Error uploading file'];
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    switch ($action) {
        case 'create':
            // Validate required fields
            if (empty($_POST['type']) || empty($_POST['title']) || empty($_POST['description'])) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit();
            }

            $type = $conn->real_escape_string($_POST['type']);
            $title = $conn->real_escape_string($_POST['title']);
            $description = $conn->real_escape_string($_POST['description']);
            $created_by = $_SESSION['admin_id'];
            $file_attachment = null;

            // Handle file upload if present
            if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handleFileUpload($_FILES['file_attachment']);
                if (!$upload_result['success']) {
                    echo json_encode($upload_result);
                    exit();
                }
                $file_attachment = $upload_result['filename'];
            }

            // Insert into database
            $sql = "INSERT INTO safety_protocols (type, title, description, file_attachment, created_by) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $type, $title, $description, $file_attachment, $created_by);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Safety protocol created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creating safety protocol']);
            }
            break;

        case 'edit':
            if (empty($_POST['protocol_id'])) {
                echo json_encode(['success' => false, 'message' => 'Protocol ID is required']);
                exit();
            }

            $protocol_id = (int)$_POST['protocol_id'];
            $type = $conn->real_escape_string($_POST['type']);
            $title = $conn->real_escape_string($_POST['title']);
            $description = $conn->real_escape_string($_POST['description']);

            // Handle file upload if present
            $file_attachment = null;
            if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handleFileUpload($_FILES['file_attachment']);
                if (!$upload_result['success']) {
                    echo json_encode($upload_result);
                    exit();
                }
                $file_attachment = $upload_result['filename'];
            }

            // Update database
            $sql = "UPDATE safety_protocols SET type = ?, title = ?, description = ?";
            if ($file_attachment) {
                $sql .= ", file_attachment = ?";
            }
            $sql .= " WHERE protocol_id = ?";

            $stmt = $conn->prepare($sql);
            if ($file_attachment) {
                $stmt->bind_param("ssssi", $type, $title, $description, $file_attachment, $protocol_id);
            } else {
                $stmt->bind_param("sssi", $type, $title, $description, $protocol_id);
            }

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Safety protocol updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating safety protocol']);
            }
            break;

        case 'delete':
            if (empty($_POST['protocol_id'])) {
                echo json_encode(['success' => false, 'message' => 'Protocol ID is required']);
                exit();
            }

            $protocol_id = (int)$_POST['protocol_id'];

            // Get file attachment before deleting
            $sql = "SELECT file_attachment FROM safety_protocols WHERE protocol_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $protocol_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $protocol = $result->fetch_assoc();

            // Delete from database
            $sql = "DELETE FROM safety_protocols WHERE protocol_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $protocol_id);

            if ($stmt->execute()) {
                // Delete file if exists
                if ($protocol && $protocol['file_attachment']) {
                    $file_path = "../../uploads/protocols/" . $protocol['file_attachment'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                echo json_encode(['success' => true, 'message' => 'Safety protocol deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting safety protocol']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 