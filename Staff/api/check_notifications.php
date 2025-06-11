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
$last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));

try {
    // Get unread notifications
    $stmt = $conn->prepare("
        SELECT 
            n.notification_id,
            n.incident_id,
            n.message,
            n.type,
            n.priority,
            n.created_at,
            ir.incident_type,
            ir.priority_level as incident_priority
        FROM 
            staff_notifications n
        LEFT JOIN 
            incident_reports ir ON n.incident_id = ir.incident_id
        WHERE 
            n.staff_id = ?
            AND n.created_at > ?
            AND n.is_read = FALSE
        ORDER BY 
            CASE n.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'moderate' THEN 3
                WHEN 'low' THEN 4
            END,
            n.created_at DESC
    ");
    
    $stmt->execute([$staff_id, $last_check]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark notifications as read
    if (!empty($notifications)) {
        $notification_ids = array_column($notifications, 'notification_id');
        $placeholders = str_repeat('?,', count($notification_ids) - 1) . '?';
        
        $stmt = $conn->prepare("
            UPDATE staff_notifications 
            SET is_read = TRUE 
            WHERE notification_id IN ($placeholders)
        ");
        
        $stmt->execute($notification_ids);
    }

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 