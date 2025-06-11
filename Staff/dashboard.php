<?php
session_start();

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}



// Helper function to format coordinates with degrees
function formatCoordinatesWithDegrees($latitude, $longitude) {
    $latDirection = $latitude >= 0 ? 'N' : 'S';
    $lngDirection = $longitude >= 0 ? 'E' : 'W';
    return sprintf("%.6f°%s, %.6f°%s", 
        abs($latitude), $latDirection,
        abs($longitude), $lngDirection
    );
}

// Get staff's latest location
require_once '../includes/db.php';
$latest_location = null;

try {
    // First verify if staff exists
    $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Staff exists, now get their latest location
        $stmt = $conn->prepare("SELECT latitude, longitude, created_at FROM staff_locations WHERE staff_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $latest_location = $result->fetch_assoc();
        }
    } else {
        // Staff not found, log error
        error_log("Staff ID not found in database: " . $staff_id);
    }
    $stmt->close();
} catch (Exception $e) {
    // Log error but don't show to user
    error_log("Error fetching staff location: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - PROTEQ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin_css/admin-styles.css">
    <!-- Notifications CSS -->
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        .incident-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }
        .incident-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .priority-critical { 
            border-left: 4px solid #dc3545;
            background-color: rgba(220, 53, 69, 0.05);
        }
        .priority-high { 
            border-left: 4px solid #fd7e14;
            background-color: rgba(253, 126, 20, 0.05);
        }
        .priority-moderate { 
            border-left: 4px solid #ffc107;
            background-color: rgba(255, 193, 7, 0.05);
        }
        .priority-low { 
            border-left: 4px solid #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }
        .notification-container {
            max-height: 300px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(0,0,0,0.2) transparent;
        }
        .notification-container::-webkit-scrollbar {
            width: 6px;
        }
        .notification-container::-webkit-scrollbar-track {
            background: transparent;
        }
        .notification-container::-webkit-scrollbar-thumb {
            background-color: rgba(0,0,0,0.2);
            border-radius: 3px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 500;
        }
        #incidentMap {
            height: 400px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .availability-toggle {
            width: 60px;
            height: 30px;
        }
        .location-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .location-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .location-card .avatar-sm {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 12px;
        }
        .location-card .avatar-sm i {
            font-size: 1.5rem;
            color: #28a745;
        }
        .btn-group .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        .btn-group .dropdown-menu {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .btn-group .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        .btn-group .dropdown-item:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.25rem;
            border-radius: 12px 12px 0 0 !important;
        }
        .card-body {
            padding: 1.25rem;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            padding: 0.5rem 0.75rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        .btn-primary {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-outline-secondary {
            border-radius: 8px;
            font-weight: 500;
        }
        .notification-item {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: background-color 0.2s ease;
        }
        .notification-item:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item.unread {
            background-color: rgba(0,123,255,0.05);
        }
        .notification-item.unread:hover {
            background-color: rgba(0,123,255,0.08);
        }
        .notification-time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        .loading-spinner {
            width: 1.5rem;
            height: 1.5rem;
            border-width: 0.2em;
        }
        @media (max-width: 768px) {
            .card-body {
                padding: 1rem;
            }
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
            }
            .location-card .avatar-sm {
                width: 40px;
                height: 40px;
            }
            #incidentMap {
                height: 300px;
            }
            .container-fluid {
                padding: 1rem !important;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['login_success'])): ?>
    <div class="notification-snap-alert success">
        <i class="bi bi-check-circle-fill"></i>
        <?php 
        echo htmlspecialchars($_SESSION['login_success']);
        unset($_SESSION['login_success']);
        ?>
    </div>
    <script src="../assets/js/notification-snap-alert.js"></script>
    <?php endif; ?>
    <div class="d-flex" id="wrapper">
        <?php include 'components/_sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
                    <h4 class="ms-4 mb-0">Staff Dashboard</h4>
                    <div class="ms-auto d-flex align-items-center">
                        <div class="form-check form-switch me-3">
                            <select class="form-select availability-select" id="availabilitySelect">
                                <option value="available">Available</option>
                                <option value="busy">Busy</option>
                                <option value="off_duty">Off Duty</option>
                            </select>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-4">
                <!-- Status and Notifications Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card location-card h-100">
                            <div class="d-flex align-items-center p-3">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <i class="bi bi-geo-alt"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Current Location</h6>
                                    <p class="text-muted mb-0 location-display">
                                        <?php if ($latest_location && isset($latest_location['latitude']) && isset($latest_location['longitude'])): 
                                            $locationName = getLocationFromCoordinates(
                                                $latest_location['latitude'], 
                                                $latest_location['longitude']
                                            );
                                        ?>
                                            <small class="d-block mb-1">
                                                <i class="bi bi-geo-alt"></i> 
                                                <?php echo htmlspecialchars($locationName ?? 'Getting location...'); ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt-fill"></i> 
                                                <?php echo formatCoordinatesWithDegrees(
                                                    $latest_location['latitude'], 
                                                    $latest_location['longitude']
                                                ); ?>
                                            </small>
                                        <?php else: ?>
                                            <small>
                                                <i class="bi bi-geo-alt"></i> 
                                                Location not available
                                            </small>
                                        <?php endif; ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> 
                                        Updated: <?php echo $latest_location ? date('M d, H:i', strtotime($latest_location['created_at'])) : 'Never'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Notifications</h5>
                                <button class="btn btn-sm btn-outline-secondary" id="clearNotifications" data-bs-toggle="tooltip" title="Clear all notifications">
                                    <i class="bi bi-trash"></i> Clear All
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div id="notificationArea" class="notification-container">
                                    <!-- Notifications will be dynamically added here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Incident Map and Reports Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Incident Map</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" id="refreshMap">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        Filter
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" data-filter="all">All Incidents</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="unvalidated">Unvalidated</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="validated">Validated</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="rejected">Rejected</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="incidentMap"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Incident Reports</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" id="refreshIncidents">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        Filter
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" data-filter="all">All Reports</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="unvalidated">Unvalidated</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="validated">Validated</a></li>
                                        <li><a class="dropdown-item" href="#" data-filter="rejected">Rejected</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="incidentList" class="incident-list">
                                    <!-- Incidents will be dynamically added here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Incident Validation Form -->
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Validate Incident</h5>
                                <button class="btn btn-sm btn-outline-primary" id="toggleValidationForm">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                            <div class="card-body" id="validationFormContainer">
                                <form id="incidentValidationForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="validationIncidentId" class="form-label">Incident ID</label>
                                                <input type="text" class="form-control" id="validationIncidentId" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="validationStatus" class="form-label">Validation Status</label>
                                                <select class="form-select" id="validationStatus" required>
                                                    <option value="validated">Validate</option>
                                                    <option value="rejected">Reject</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="validationNotes" class="form-label">Validation Notes</label>
                                                <textarea class="form-control" id="validationNotes" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Submit Validation
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div class="modal fade" id="assignmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Incident Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> You have been selected as the best candidate for this incident.
                    </div>
                    <div class="mb-3">
                        <h6>Incident Details</h6>
                        <p><strong>ID:</strong> <span id="assignmentIncidentId"></span></p>
                        <p><strong>Type:</strong> <span id="assignmentIncidentType"></span></p>
                        <p><strong>Location:</strong> <span id="assignmentIncidentLocation"></span></p>
                        <p><strong>Priority:</strong> <span id="assignmentIncidentPriority"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="rejectAssignment">Reject</button>
                    <button type="button" class="btn btn-primary" id="acceptAssignment">Accept</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Incident Details Modal -->
    <div class="modal fade" id="incidentDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Incident Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Incident Information</h6>
                            <p><strong>ID:</strong> <span id="incidentDetailsId"></span></p>
                            <p><strong>Type:</strong> <span id="incidentDetailsType"></span></p>
                            <p><strong>Location:</strong> <span id="incidentDetailsLocation"></span></p>
                            <p><strong>Priority:</strong> <span id="incidentDetailsPriority"></span></p>
                            <p><strong>Description:</strong> <span id="incidentDetailsDescription"></span></p>
                        </div>
                        <div class="col-md-6">
                            <div id="routeMap" style="height: 300px;"></div>
                        </div>
                    </div>
                    
                    <div class="mt-4" id="statusUpdateForm">
                        <h6>Update Status</h6>
                        <form id="statusUpdateForm">
                            <div class="mb-3">
                                <label for="statusSelect" class="form-label">Status</label>
                                <select class="form-select" id="statusSelect" required>
                                    <option value="on_the_way">On the Way</option>
                                    <option value="on_site">On Site</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="needs_escalation">Needs Escalation</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="statusNotes" class="form-label">Notes</label>
                                <textarea class="form-control" id="statusNotes" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="statusAttachments" class="form-label">Attachments</label>
                                <input type="file" class="form-control" id="statusAttachments" multiple>
                                <div id="attachmentPreview" class="mt-2"></div>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Heatmap Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js"></script>
    <!-- Custom Admin JS -->
    <script src="../assets/js/admin-script.js"></script>
    <!-- Staff Dashboard JS -->
    <script src="../assets/js/staff-dashboard.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        let locationUpdateRetries = 0;
        const MAX_RETRIES = 3;

        // Location tracking functionality
        function updateLocation() {
            const locationDisplay = document.querySelector('.location-display');
            if (!locationDisplay) return;

            // Show loading state
            locationDisplay.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <small>Updating location...</small>
                </div>
            `;

            if ("geolocation" in navigator) {
                const options = {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 30000
                };

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        
                        // Send location to server
                        fetch('api/update_location.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                latitude: latitude,
                                longitude: longitude
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Reset retry counter on success
                                locationUpdateRetries = 0;
                                
                                // Update the location display with new data
                                locationDisplay.innerHTML = `
                                    <small class="d-block mb-1">
                                        <i class="bi bi-geo-alt"></i> 
                                        ${data.locationName || 'Getting location...'}
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt-fill"></i> 
                                        ${formatCoordinates(latitude, longitude)}
                                    </small>
                                `;
                                
                                // Update timestamp
                                const timestampElement = locationDisplay.nextElementSibling;
                                if (timestampElement) {
                                    timestampElement.innerHTML = `
                                        <i class="bi bi-clock"></i> 
                                        Updated: ${new Date().toLocaleString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}
                                    `;
                                }
                            } else {
                                throw new Error(data.message || 'Failed to update location');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            
                            // Implement retry logic
                            if (locationUpdateRetries < MAX_RETRIES) {
                                locationUpdateRetries++;
                                setTimeout(updateLocation, 2000); // Retry after 2 seconds
                                locationDisplay.innerHTML = `
                                    <small class="text-warning">
                                        <i class="bi bi-exclamation-triangle"></i> 
                                        Retrying location update (${locationUpdateRetries}/${MAX_RETRIES})...
                                    </small>
                                `;
                            } else {
                                locationDisplay.innerHTML = `
                                    <small class="text-danger">
                                        <i class="bi bi-exclamation-circle"></i> 
                                        ${error.message || 'Failed to update location'}
                                    </small>
                                `;
                                // Reset retry counter after max retries
                                locationUpdateRetries = 0;
                            }
                        });
                    },
                    function(error) {
                        console.error('Error getting location:', error.message);
                        let errorMessage = 'Location access denied';
                        
                        // Handle specific error cases
                        switch(error.code) {
                            case error.TIMEOUT:
                                errorMessage = 'Location request timed out. Please check your internet connection.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = 'Location information is unavailable.';
                                break;
                            case error.PERMISSION_DENIED:
                                errorMessage = 'Location access denied. Please enable location services.';
                                break;
                        }
                        
                        locationDisplay.innerHTML = `
                            <small class="text-danger">
                                <i class="bi bi-exclamation-circle"></i> 
                                ${errorMessage}
                            </small>
                        `;
                    },
                    options
                );
            } else {
                locationDisplay.innerHTML = `
                    <small class="text-danger">
                        <i class="bi bi-exclamation-circle"></i> 
                        Geolocation not supported
                    </small>
                `;
            }
        }

        // Helper function to format coordinates
        function formatCoordinates(lat, lng) {
            return `${lat.toFixed(6)}°N, ${lng.toFixed(6)}°E`;
        }

        // Update location every 5 minutes
        updateLocation(); // Initial update
        setInterval(updateLocation, 5 * 60 * 1000); // Update every 5 minutes (300000 ms)

        // Availability Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const availabilitySelect = document.getElementById('availabilitySelect');
            if (!availabilitySelect) return;

            // Function to fetch current availability status
            function fetchCurrentAvailability() {
                fetch('api/get_availability.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            availabilitySelect.value = data.availability;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching availability:', error);
                    });
            }

            // Fetch initial availability status
            fetchCurrentAvailability();

            // Function to update availability status
            function updateAvailabilityStatus(status) {
                fetch('api/update_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        availability: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Revert selection if update failed
                        availabilitySelect.value = data.current_status || 'off_duty';
                        throw new Error(data.message || 'Failed to update availability');
                    }
                    
                    // Show success notification
                    const notificationArea = document.getElementById('notificationArea');
                    if (notificationArea) {
                        const notification = document.createElement('div');
                        notification.className = 'notification-item unread';
                        
                        // Set icon and color based on status
                        let icon, color;
                        switch(status) {
                            case 'available':
                                icon = 'bi-check-circle';
                                color = 'text-success';
                                break;
                            case 'busy':
                                icon = 'bi-clock';
                                color = 'text-warning';
                                break;
                            case 'off_duty':
                                icon = 'bi-x-circle';
                                color = 'text-danger';
                                break;
                        }
                        
                        notification.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi ${icon} ${color}"></i>
                                    <span class="ms-2">Status updated to: ${status.replace('_', ' ').toUpperCase()}</span>
                                </div>
                                <small class="notification-time">Just now</small>
                            </div>
                        `;
                        notificationArea.insertBefore(notification, notificationArea.firstChild);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error notification
                    const notificationArea = document.getElementById('notificationArea');
                    if (notificationArea) {
                        const notification = document.createElement('div');
                        notification.className = 'notification-item unread';
                        notification.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-exclamation-circle text-danger"></i>
                                    <span class="ms-2">${error.message}</span>
                                </div>
                                <small class="notification-time">Just now</small>
                            </div>
                        `;
                        notificationArea.insertBefore(notification, notificationArea.firstChild);
                    }
                });
            }

            // Add event listener for selection changes
            availabilitySelect.addEventListener('change', function() {
                updateAvailabilityStatus(this.value);
            });
        });
    </script>
</body>
</html>