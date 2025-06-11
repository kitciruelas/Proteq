<?php
session_start();
require_once '../includes/db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['incident_type', 'description', 'latitude', 'longitude', 'priority_level', 'reporter_safe_status'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }

        // Sanitize and validate input
        $incident_type = filter_var($_POST['incident_type'], FILTER_SANITIZE_STRING);
        $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
        $latitude = filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT);
        $priority_level = filter_var($_POST['priority_level'], FILTER_SANITIZE_STRING);
        $reporter_safe_status = filter_var($_POST['reporter_safe_status'], FILTER_SANITIZE_STRING);

        // Validate coordinates
        if ($latitude === false || $longitude === false) {
            throw new Exception("Invalid location coordinates.");
        }

        // Validate priority level
        $valid_priorities = ['low', 'moderate', 'high', 'critical'];
        if (!in_array($priority_level, $valid_priorities)) {
            throw new Exception("Invalid priority level.");
        }

        // Validate safety status
        $valid_safety_statuses = ['safe', 'injured', 'unknown'];
        if (!in_array($reporter_safe_status, $valid_safety_statuses)) {
            throw new Exception("Invalid safety status.");
        }

        // Get user ID from session (assuming user is logged in)
        $reported_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        if (!$reported_by) {
            throw new Exception("User not logged in.");
        }

        // Prepare SQL statement
        $sql = "INSERT INTO incident_reports (
            incident_type, 
            description, 
            latitude, 
            longitude, 
            priority_level, 
            reporter_safe_status,
            reported_by,
            status,
            validation_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'unvalidated')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssddssi",
            $incident_type,
            $description,
            $latitude,
            $longitude,
            $priority_level,
            $reporter_safe_status,
            $reported_by
        );

        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['incident_success'] = "Incident report submitted successfully. Our team will review it shortly.";
            
            // If it's a critical incident, send immediate notification
            if ($priority_level === 'critical') {
                // TODO: Implement notification system for critical incidents
                // This could be email, SMS, or push notification
            }
        } else {
            throw new Exception("Error submitting report. Please try again.");
        }

        // Redirect back to the form
        header("Location: Incident_report.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['incident_error'] = $e->getMessage();
        header("Location: Incident_report.php");
        exit();
    }
} else {
    // If not POST request, redirect to form
    header("Location: Incident_report.php");
    exit();
}
?>