<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/email_helper.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Log all incoming data
error_log('process_alert.php - Session data: ' . print_r($_SESSION, true));
error_log('process_alert.php - POST data: ' . print_r($_POST, true));
error_log('process_alert.php - Raw input: ' . file_get_contents('php://input'));

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    error_log('No admin_id in session');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Verify admin exists and is active
$stmt = $conn->prepare("SELECT * FROM admin WHERE admin_id = ? AND status = 'active'");
if (!$stmt) {
    error_log('Prepare failed: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("i", $_SESSION['admin_id']);
if (!$stmt->execute()) {
    error_log('Execute failed: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    error_log('Admin not found or inactive');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$stmt->close();

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input data
$alertType = isset($_POST['alert_type']) ? trim($_POST['alert_type']) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
$longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
$radiusKm = isset($_POST['radius_km']) ? floatval($_POST['radius_km']) : null;

// Debug: Log sanitized input data
error_log('Sanitized input data: ' . print_r([
    'alert_type' => $alertType,
    'title' => $title,
    'description' => $description,
    'latitude' => $latitude,
    'longitude' => $longitude,
    'radius_km' => $radiusKm
], true));

// Validate required fields with detailed error messages
$missingFields = [];
if (empty($alertType)) $missingFields[] = 'alert_type';
if (empty($title)) $missingFields[] = 'title';
if (empty($description)) $missingFields[] = 'description';
if ($latitude === null) $missingFields[] = 'latitude';
if ($longitude === null) $missingFields[] = 'longitude';
if ($radiusKm === null) $missingFields[] = 'radius_km';

if (!empty($missingFields)) {
    error_log('Missing required fields: ' . implode(', ', $missingFields));
    echo json_encode([
        'success' => false, 
        'message' => 'All fields are required',
        'debug' => [
            'missing_fields' => $missingFields,
            'received_data' => [
            'alert_type' => $alertType,
            'title' => $title,
            'description' => $description,
            'latitude' => $latitude,
            'longitude' => $longitude,
                'radius_km' => $radiusKm
            ]
        ]
    ]);
    exit;
}

// Validate coordinates
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}


try {
    // Start transaction
    $conn->begin_transaction();

    // Insert alert
    $stmt = $conn->prepare("INSERT INTO alerts (alert_type, title, description, latitude, longitude, radius_km, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare alert insert statement: ' . $conn->error);
    }

    $stmt->bind_param("sssddd", $alertType, $title, $description, $latitude, $longitude, $radiusKm);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert alert: ' . $stmt->error);
    }

    $alertId = $conn->insert_id;
    $stmt->close();

    // Get all users from the users table
    $stmt = $conn->prepare("SELECT email, first_name, last_name FROM general_users WHERE user_type IN ('FACULTY', 'STUDENT', 'UNIVERSITY_EMPLOYEE')");
    if (!$stmt) {
        throw new Exception('Failed to prepare user select statement: ' . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to get users: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    error_log('Found ' . count($users) . ' users to notify');

    // Send email notifications to all users
    if (!empty($users)) {
        $emailSuccessCount = 0;
        $emailFailCount = 0;
        $failedEmails = [];

        foreach ($users as $user) {
            if (empty($user['email'])) {
                error_log("Skipping user with no email: " . print_r($user, true));
                continue;
            }

            $alertData = [
                'title' => $title,
                'alert_type' => $alertType,
                'description' => $description,
                'location' => $title,
                'radius_km' => $radiusKm,
                'recipient_name' => $user['first_name'] . ' ' . $user['last_name'],
                'latitude' => $latitude,
                'longitude' => $longitude
            ];
            
            error_log("Attempting to send alert email to: " . $user['email']);
            $emailSent = sendAlertEmail($user['email'], $alertData);
            
            if ($emailSent) {
                $emailSuccessCount++;
                error_log("Successfully sent email to: " . $user['email']);
            } else {
                $emailFailCount++;
                $failedEmails[] = $user['email'];
                error_log("Failed to send email to: " . $user['email']);
            }
        }

        error_log("Email sending summary - Success: $emailSuccessCount, Failed: $emailFailCount");
        if (!empty($failedEmails)) {
            error_log("Failed emails: " . implode(", ", $failedEmails));
        }
    }

    // Commit transaction
    $conn->commit();

    $response = [
        'success' => true,
        'message' => 'Alert created successfully'
    ];
    if (!empty($failedEmails)) {
        $response['warning'] = 'Some emails failed to send.';
        $response['failed_emails'] = $failedEmails;
    }
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log('Error creating alert: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create alert: ' . $e->getMessage()
    ]);
}