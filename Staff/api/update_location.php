<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate data
if (!isset($data['latitude']) || !isset($data['longitude'])) {
    echo json_encode(['success' => false, 'message' => 'Missing location data']);
    exit();
}

// Validate coordinates
$latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
$longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);

if ($latitude === false || $longitude === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit();
}

// Validate coordinate ranges
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    echo json_encode(['success' => false, 'message' => 'Coordinates out of valid range']);
    exit();
}

try {
    require_once '../../includes/db.php';
    
    $user_id = $_SESSION['user_id'];
    
    // First check if a record exists for this staff
    $check_query = "SELECT id FROM staff_locations WHERE staff_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        // Update existing record
        $query = "UPDATE staff_locations SET latitude = ?, longitude = ?, last_updated = CURRENT_TIMESTAMP WHERE staff_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "ddi", $latitude, $longitude, $user_id);
    } else {
        // Insert new record if none exists
        $query = "INSERT INTO staff_locations (staff_id, latitude, longitude, last_updates) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "idd", $user_id, $latitude, $longitude);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Database execute error: " . mysqli_stmt_error($stmt));
    }
    
    // Get the location name using reverse geocoding
    require_once '../../includes/location_utils.php';
    $locationName = getLocationFromCoordinates($latitude, $longitude);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Location updated successfully',
        'locationName' => $locationName,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Location update error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
}
?> 