<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        // Validate required fields
        $required_fields = ['name', 'status', 'capacity', 'current_occupancy', 'contact_person', 'contact_number', 'address', 'latitude', 'longitude'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                exit;
            }
        }

        // Prepare and execute insert query
        $stmt = $conn->prepare("INSERT INTO evacuation_centers (name, status, capacity, current_occupancy, contact_person, contact_number, address, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiisssd", 
            $_POST['name'],
            $_POST['status'],
            $_POST['capacity'],
            $_POST['current_occupancy'],
            $_POST['contact_person'],
            $_POST['contact_number'],
            $_POST['address'],
            $_POST['latitude'],
            $_POST['longitude']
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Center added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding center: ' . $stmt->error]);
        }
        break;

    case 'update':
        // Validate required fields
        if (!isset($_POST['center_id']) || empty($_POST['center_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing center ID']);
            exit;
        }

        $required_fields = ['name', 'status', 'capacity', 'current_occupancy', 'contact_person', 'contact_number', 'address', 'latitude', 'longitude'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                exit;
            }
        }

        // Prepare and execute update query
        $stmt = $conn->prepare("UPDATE evacuation_centers SET name = ?, status = ?, capacity = ?, current_occupancy = ?, contact_person = ?, contact_number = ?, address = ?, latitude = ?, longitude = ? WHERE center_id = ?");
        $stmt->bind_param("ssiiisssdi", 
            $_POST['name'],
            $_POST['status'],
            $_POST['capacity'],
            $_POST['current_occupancy'],
            $_POST['contact_person'],
            $_POST['contact_number'],
            $_POST['address'],
            $_POST['latitude'],
            $_POST['longitude'],
            $_POST['center_id']
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Center updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating center: ' . $stmt->error]);
        }
        break;

    case 'delete':
        if (!isset($_POST['center_id']) || empty($_POST['center_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing center ID']);
            exit;
        }

        // First delete associated resources
        $stmt = $conn->prepare("DELETE FROM resources WHERE center_id = ?");
        $stmt->bind_param("i", $_POST['center_id']);
        $stmt->execute();

        // Then delete the center
        $stmt = $conn->prepare("DELETE FROM evacuation_centers WHERE center_id = ?");
        $stmt->bind_param("i", $_POST['center_id']);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Center deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting center: ' . $stmt->error]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
} 