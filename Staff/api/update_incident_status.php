<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['incident_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$incident_id = $data['incident_id'];
$status = $data['status'];
$notes = $data['notes'] ?? '';
$staff_id = $_SESSION['user_id'];

// Validate status
$valid_statuses = ['on_the_way', 'on_site', 'resolved', 'needs_escalation'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Update incident status
    $stmt = $conn->prepare("
        UPDATE incidents 
        SET current_status = ?,
            status_notes = ?,
            last_updated = CURRENT_TIMESTAMP
        WHERE incident_id = ? AND assigned_to = ?
    ");
    $stmt->bind_param("ssii", $status, $notes, $incident_id, $staff_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Incident not found or not assigned to you');
    }

    // If status is resolved, update resolution time
    if ($status === 'resolved') {
        $stmt = $conn->prepare("
            UPDATE incidents 
            SET resolution_date = CURRENT_TIMESTAMP
            WHERE incident_id = ?
        ");
        $stmt->bind_param("i", $incident_id);
        $stmt->execute();
    }

    // Create notification for the reporter
    $notification_message = '';
    switch ($status) {
        case 'on_the_way':
            $notification_message = 'Staff is on the way to the incident location';
            break;
        case 'on_site':
            $notification_message = 'Staff has arrived at the incident location';
            break;
        case 'resolved':
            $notification_message = 'The incident has been resolved';
            break;
        case 'needs_escalation':
            $notification_message = 'The incident requires additional attention';
            break;
    }

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
            'status_update',
            ?,
            CURRENT_TIMESTAMP
        FROM incidents
        WHERE incident_id = ?
    ");
    $stmt->bind_param("isi", $incident_id, $notification_message, $incident_id);
    $stmt->execute();

    // Handle file uploads if any
    if (isset($_FILES['attachments'])) {
        $upload_dir = '../../uploads/incident_responses/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['attachments']['name'][$key];
            $file_path = $upload_dir . time() . '_' . $file_name;
            
            if (move_uploaded_file($tmp_name, $file_path)) {
                $stmt = $conn->prepare("
                    INSERT INTO incident_attachments (
                        incident_id,
                        file_name,
                        file_path,
                        uploaded_by,
                        upload_date
                    ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $relative_path = 'uploads/incident_responses/' . time() . '_' . $file_name;
                $stmt->bind_param("issi", $incident_id, $file_name, $relative_path, $staff_id);
                $stmt->execute();
            }
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Incident status updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating incident status: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 