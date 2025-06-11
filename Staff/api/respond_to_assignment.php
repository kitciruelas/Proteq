<?php
session_start();
require_once '../includes/db.php';
require_once 'smart_match_incident.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['incident_id']) || !isset($data['response'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$incident_id = $data['incident_id'];
$response = $data['response']; // 'accept' or 'reject'
$staff_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    if ($response === 'accept') {
        // Update incident assignment
        $stmt = $conn->prepare("
            UPDATE incidents 
            SET assigned_to = ?,
                current_status = 'in_progress',
                assignment_date = CURRENT_TIMESTAMP
            WHERE incident_id = ?
        ");
        $stmt->bind_param("ii", $staff_id, $incident_id);
        $stmt->execute();

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
                'assignment_accepted',
                'A staff member has accepted your incident report and is on the way',
                CURRENT_TIMESTAMP
            FROM incidents
            WHERE incident_id = ?
        ");
        $stmt->bind_param("ii", $incident_id, $incident_id);
        $stmt->execute();

    } else {
        // Reject assignment and trigger new smart matching
        $stmt = $conn->prepare("
            UPDATE incidents 
            SET assigned_to = NULL,
                current_status = 'pending'
            WHERE incident_id = ?
        ");
        $stmt->bind_param("i", $incident_id);
        $stmt->execute();

        // Trigger new smart matching
        smartMatchIncident($incident_id);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $response === 'accept' ? 'Assignment accepted successfully' : 'Assignment rejected'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing assignment response: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 