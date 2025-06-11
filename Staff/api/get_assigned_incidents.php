<?php
    require_once '../includes/db.php';
    session_start();

// Check if user is logged in and is staff
if (!isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$staff_id = $_SESSION['staff_id'];

try {
    // Get assigned incidents with their latest status
    $stmt = $conn->prepare("
        SELECT 
            ir.incident_id,
            ir.incident_type,
            ir.description,
            ir.longitude,
            ir.latitude,
            ir.date_reported,
            ir.priority_level,
            ir.reporter_safe_status,
            COALESCE(sir.status, ir.status) as current_status,
            sir.notes as staff_notes,
            sir.response_time
        FROM 
            incident_reports ir
        LEFT JOIN 
            staff_incident_responses sir ON ir.incident_id = sir.incident_id 
            AND sir.staff_id = ?
        WHERE 
            ir.assigned_to = ?
            AND ir.status IN ('pending', 'in_progress')
        ORDER BY 
            CASE ir.priority_level
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'moderate' THEN 3
                WHEN 'low' THEN 4
            END,
            ir.date_reported DESC
    ");
    
    $stmt->execute([$staff_id, $staff_id]);
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get attachments for each incident
    foreach ($incidents as &$incident) {
        $stmt = $conn->prepare("
            SELECT 
                file_name,
                file_path,
                file_type
            FROM 
                staff_incident_attachments
            WHERE 
                incident_id = ?
            ORDER BY 
                uploaded_at DESC
        ");
        
        $stmt->execute([$incident['incident_id']]);
        $incident['attachments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'incidents' => $incidents
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 