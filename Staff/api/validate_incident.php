<?php
    require_once '../includes/db.php';
    session_start();

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['incident_id']) || !isset($data['validation_status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$incident_id = $data['incident_id'];
$validation_status = $data['validation_status'];
$validation_notes = $data['validation_notes'] ?? '';
$staff_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Update incident validation status
    $stmt = $conn->prepare("
        UPDATE incidents 
        SET validation_status = ?, 
            validation_notes = ?,
            validated_by = ?,
            validation_date = CURRENT_TIMESTAMP
        WHERE incident_id = ?
    ");
    
    $stmt->bind_param("ssii", $validation_status, $validation_notes, $staff_id, $incident_id);
    $stmt->execute();

    // If incident is validated, update its status to 'pending'
    if ($validation_status === 'validated') {
        $stmt = $conn->prepare("
            UPDATE incidents 
            SET current_status = 'pending'
            WHERE incident_id = ?
        ");
        $stmt->bind_param("i", $incident_id);
        $stmt->execute();
    }

    // Create notification for the reporter
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            user_id,
            incident_id,
            notification_type,
            message,
            created_at
        )
        SELECT 
            reporter_id,
            ?,
            'validation_update',
            CASE 
                WHEN ? = 'validated' THEN 'Your incident report has been validated'
                WHEN ? = 'rejected' THEN 'Your incident report has been rejected'
                ELSE 'Your incident report validation status has been updated'
            END,
            CURRENT_TIMESTAMP
        FROM incidents
        WHERE incident_id = ?
    ");
    
    $stmt->bind_param("issi", $incident_id, $validation_status, $validation_status, $incident_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Incident validation updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating incident validation: ' . $e->getMessage()
    ]);
}

$conn->close();

// Helper function to calculate distance between two points
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km

    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);

    $d_lat = $lat2 - $lat1;
    $d_lon = $lon2 - $lon1;

    $a = sin($d_lat/2) * sin($d_lat/2) + cos($lat1) * cos($lat2) * sin($d_lon/2) * sin($d_lon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
} 