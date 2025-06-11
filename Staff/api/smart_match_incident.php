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
    // Get staff's current location
    $stmt = $conn->prepare("
        SELECT latitude, longitude, role, availability
        FROM staff
        WHERE staff_id = ?
    ");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff || $staff['availability'] !== 'available') {
        echo json_encode([
            'success' => false,
            'message' => 'Staff is not available for assignment'
        ]);
        exit;
    }

    // Get unassigned validated incidents
    $stmt = $conn->prepare("
        SELECT 
            ir.*,
            (
                6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(ir.latitude)) * 
                    cos(radians(ir.longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(ir.latitude))
                )
            ) AS distance
        FROM 
            incident_reports ir
        WHERE 
            ir.assigned_to IS NULL
            AND ir.validation_status = 'validated'
            AND ir.status = 'pending'
        HAVING 
            distance <= 10  -- Within 10km radius
        ORDER BY 
            CASE ir.priority_level
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'moderate' THEN 3
                WHEN 'low' THEN 4
            END,
            distance ASC
        LIMIT 5
    ");

    $stmt->execute([
        $staff['latitude'],
        $staff['longitude'],
        $staff['latitude']
    ]);

    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($incidents)) {
        echo json_encode([
            'success' => true,
            'message' => 'No matching incidents found',
            'incidents' => []
        ]);
        exit;
    }

    // Filter incidents based on staff role
    $role_specific_incidents = array_filter($incidents, function($incident) use ($staff) {
        // Define role-specific incident types
        $role_incidents = [
            'nurse' => ['medical_emergency', 'health_concern'],
            'security' => ['security_breach', 'suspicious_activity'],
            'fire_responder' => ['fire', 'smoke_detection'],
            'medical_responder' => ['medical_emergency', 'health_concern', 'injury'],
            'general_staff' => ['general_incident', 'maintenance']
        ];

        return in_array($incident['incident_type'], $role_incidents[$staff['role']] ?? ['general_incident']);
    });

    // If no role-specific incidents, return all incidents
    $matching_incidents = !empty($role_specific_incidents) ? $role_specific_incidents : $incidents;

    echo json_encode([
        'success' => true,
        'incidents' => array_values($matching_incidents)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

function smartMatchIncident($incident_id) {
    global $conn;
    
    try {
        // Get incident details
        $stmt = $conn->prepare("
            SELECT 
                incident_id,
                incident_type,
                latitude,
                longitude,
                priority_level
            FROM incidents
            WHERE incident_id = ?
        ");
        $stmt->bind_param("i", $incident_id);
        $stmt->execute();
        $incident = $stmt->get_result()->fetch_assoc();

        if (!$incident) {
            throw new Exception("Incident not found");
        }

        // Determine role requirements and distance limits based on priority
        $role_requirements = getRoleRequirements($incident['incident_type'], $incident['priority_level']);
        $max_distance = getMaxDistance($incident['priority_level']);

        // Find best matching staff using SQL-based distance calculation
        $stmt = $conn->prepare("
            SELECT 
                u.user_id,
                u.role,
                u.latitude,
                u.longitude,
                (
                    6371 * acos(
                        cos(radians(?)) *
                        cos(radians(u.latitude)) *
                        cos(radians(u.longitude) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(u.latitude))
                    )
                ) AS distance_km
            FROM users u
            WHERE u.user_type = 'staff'
                AND u.availability = 'available'
                AND u.is_active = 1
                AND u.role IN (" . implode(',', array_fill(0, count($role_requirements), '?')) . ")
            HAVING distance_km <= ?
            ORDER BY 
                CASE 
                    WHEN u.role = ? THEN 0  -- Exact role match gets highest priority
                    ELSE 1
                END,
                distance_km ASC
            LIMIT 1
        ");

        // Prepare parameters for the query
        $params = array_merge(
            [$incident['latitude'], $incident['longitude'], $incident['latitude']],
            $role_requirements,
            [$max_distance, $role_requirements[0]]  // First role is the primary requirement
        );
        $types = str_repeat('d', 3) . str_repeat('s', count($role_requirements)) . 'ds';
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $best_match = $result->fetch_assoc();

        // If best match found, assign incident
        if ($best_match) {
            $stmt = $conn->prepare("
                UPDATE incidents 
                SET assigned_to = ?,
                    current_status = 'pending_assignment',
                    assignment_date = CURRENT_TIMESTAMP
                WHERE incident_id = ?
            ");
            $stmt->bind_param("ii", $best_match['user_id'], $incident_id);
            $stmt->execute();

            // Create notification for assigned staff
            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    user_id,
                    incident_id,
                    notification_type,
                    message,
                    created_at
                ) VALUES (?, ?, 'assignment', 'You have been assigned to a new incident', CURRENT_TIMESTAMP)
            ");
            $stmt->bind_param("ii", $best_match['user_id'], $incident_id);
            $stmt->execute();

            return true;
        }

        return false;

    } catch (Exception $e) {
        error_log("Error in smart matching: " . $e->getMessage());
        return false;
    }
}

function getRoleRequirements($incident_type, $priority_level) {
    // Define role mappings for different incident types
    $role_mappings = [
        'medical_emergency' => ['medical_responder', 'nurse', 'general_staff'],
        'health_concern' => ['nurse', 'medical_responder', 'general_staff'],
        'security_breach' => ['security', 'general_staff'],
        'suspicious_activity' => ['security', 'general_staff'],
        'fire' => ['fire_responder', 'general_staff'],
        'smoke_detection' => ['fire_responder', 'general_staff'],
        'injury' => ['medical_responder', 'nurse', 'general_staff'],
        'general_incident' => ['general_staff'],
        'maintenance' => ['general_staff']
    ];

    // For critical incidents, we're more flexible with role requirements
    if ($priority_level === 'critical') {
        return $role_mappings[$incident_type] ?? ['general_staff'];
    }

    // For non-critical incidents, we're stricter with role matching
    return [$role_mappings[$incident_type][0] ?? 'general_staff'];
}

function getMaxDistance($priority_level) {
    // Define maximum distances based on priority
    $distances = [
        'critical' => 50.0,  // 50 km for critical incidents
        'high' => 25.0,      // 25 km for high priority
        'moderate' => 15.0,  // 15 km for moderate priority
        'low' => 10.0        // 10 km for low priority
    ];

    return $distances[$priority_level] ?? 15.0;  // Default to 15 km if priority not found
}
?> 