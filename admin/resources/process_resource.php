<?php
require_once '../includes/db.php';

// Get the action from POST data
$action = $_POST['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'add':
        // Add new resource
        $center_id = (int)$_POST['center_id'];
        $type = trim($_POST['type']);
        $quantity = (int)$_POST['quantity'];

        // Validate inputs
        if ($center_id <= 0 || empty($type) || $quantity < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid input data']);
            exit;
        }

        try {
            $stmt = $conn->prepare("INSERT INTO resources (center_id, type, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $center_id, $type, $quantity);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Resource added successfully']);
            } else {
                throw new Exception('Failed to add resource');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding resource: ' . $e->getMessage()]);
        }
        break;

    case 'update':
        // Update existing resource
        $resource_id = (int)$_POST['resource_id'];
        $type = trim($_POST['type']);
        $quantity = (int)$_POST['quantity'];

        // Validate inputs
        if ($resource_id <= 0 || empty($type) || $quantity < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid input data']);
            exit;
        }

        try {
            $stmt = $conn->prepare("UPDATE resources SET type = ?, quantity = ? WHERE resource_id = ?");
            $stmt->bind_param("sii", $type, $quantity, $resource_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Resource updated successfully']);
            } else {
                throw new Exception('Failed to update resource');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating resource: ' . $e->getMessage()]);
        }
        break;

    case 'delete':
        // Delete resource
        $resource_id = (int)$_POST['resource_id'];

        // Validate input
        if ($resource_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid resource ID']);
            exit;
        }

        try {
            $stmt = $conn->prepare("DELETE FROM resources WHERE resource_id = ?");
            $stmt->bind_param("i", $resource_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Resource deleted successfully']);
            } else {
                throw new Exception('Failed to delete resource');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting resource: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?> 