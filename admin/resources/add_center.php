<?php
require_once '../../includes/db.php';
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON data from request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Debug log
error_log('Received data: ' . print_r($data, true));

// Validate required fields
if (!$data || !isset($data['name']) || !isset($data['status']) || !isset($data['latitude']) || 
    !isset($data['longitude']) || !isset($data['capacity']) || !isset($data['current_occupancy']) || 
    !isset($data['contact_person']) || !isset($data['contact_number'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Validate capacity and current occupancy
$capacity = intval($data['capacity']);
$current_occupancy = intval($data['current_occupancy']);
$status = strtolower($data['status']);

// Validate capacity is positive
if ($capacity <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Capacity must be greater than 0']);
    exit();
}

// Validate current occupancy is not negative
if ($current_occupancy < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Current occupancy cannot be negative']);
    exit();
}

// Validate status-specific rules
switch ($status) {
    case 'open':
        if ($current_occupancy >= $capacity) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'For open status, current occupancy must be less than capacity']);
            exit();
        }
        break;
    case 'full':
        if ($current_occupancy != $capacity) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'For full status, current occupancy must equal capacity']);
            exit();
        }
        break;
    case 'closed':
        // No specific validation for closed status
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid status. Must be open, full, or closed']);
        exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert evacuation center
    $stmt = $conn->prepare("INSERT INTO evacuation_centers (name, status, latitude, longitude, capacity, current_occupancy, contact_person, contact_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddiiss", 
        $data['name'],
        $data['status'],
        $data['latitude'],
        $data['longitude'],
        $data['capacity'],
        $data['current_occupancy'],
        $data['contact_person'],
        $data['contact_number']
    );
    $stmt->execute();
    $center_id = $conn->insert_id;

    // Insert resources if any
    if (!empty($data['resources'])) {
        // Debug log
        error_log('Processing resources: ' . print_r($data['resources'], true));
        
        $stmt = $conn->prepare("INSERT INTO resources (center_id, type, quantity) VALUES (?, ?, ?)");
        foreach ($data['resources'] as $resource) {
            // Validate resource data
            if (!isset($resource['type']) || !isset($resource['quantity']) || 
                empty($resource['type']) || !is_numeric($resource['quantity'])) {
                throw new Exception('Invalid resource data');
            }
            
            $type = $resource['type'];
            $quantity = intval($resource['quantity']);
            
            // Validate quantity is positive
            if ($quantity <= 0) {
                throw new Exception('Resource quantity must be greater than 0');
            }
            
            $stmt->bind_param("isi", $center_id, $type, $quantity);
            if (!$stmt->execute()) {
                throw new Exception('Error inserting resource: ' . $stmt->error);
            }
        }
    }

    // Commit transaction
    $conn->commit();

    // Get the newly created center with its resources
    $sql = "SELECT ec.*, 
            GROUP_CONCAT(CONCAT(r.type, ':', r.quantity) SEPARATOR '|') as resources
            FROM evacuation_centers ec
            LEFT JOIN resources r ON ec.center_id = r.center_id
            WHERE ec.center_id = ?
            GROUP BY ec.center_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $center_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $new_center = $result->fetch_assoc();

    // Debug log
    error_log('New center created: ' . print_r($new_center, true));

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Evacuation center added successfully',
        'center' => $new_center
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Debug log
    error_log('Error: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error adding evacuation center: ' . $e->getMessage()
    ]);
} 