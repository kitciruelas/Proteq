<?php
require_once '../includes/db.php';

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
$required_fields = ['name', 'status', 'latitude', 'longitude', 'capacity', 'current_occupancy', 'contact_person', 'contact_number'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert evacuation center
    $sql = "INSERT INTO evacuation_centers (name, status, latitude, longitude, capacity, current_occupancy, contact_person, contact_number, last_updated) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
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
    
    if (!$stmt->execute()) {
        throw new Exception("Error inserting center: " . $stmt->error);
    }

    $center_id = $conn->insert_id;

    // Insert resources if any
    if (!empty($data['resources'])) {
        $sql = "INSERT INTO resources (center_id, type, quantity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        foreach ($data['resources'] as $resource) {
            if (empty($resource['type']) || empty($resource['quantity'])) {
                continue; // Skip if type or quantity is empty
            }
            $stmt->bind_param("isi", $center_id, $resource['type'], $resource['quantity']);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting resource: " . $stmt->error);
            }
        }
    }

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

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Center added successfully',
        'center' => $new_center
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 