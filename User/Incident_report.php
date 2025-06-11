<?php
session_start();
require_once '../includes/db.php';

// Get user's incidents if logged in
$incidents = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM incident_reports WHERE reported_by = ? ORDER BY date_reported DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $incidents[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Reports - PROTEQ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/g_user.css">
    <style>
        /* Map styles */
        #map {
            height: 400px;
            width: 100%;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: crosshair;
        }
        #location-status {
            font-style: italic;
            color: #6c757d;
        }
        .leaflet-container { z-index: 0; }

        /* Incident card styles */
        .incident-card {
            transition: transform 0.2s;
        }
        .incident-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.5em 1em;
        }
        .priority-critical { background-color: #dc3545; }
        .priority-high { background-color: #fd7e14; }
        .priority-moderate { background-color: #ffc107; }
        .priority-low { background-color: #198754; }

        /* Tab styles */
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
            background: none;
        }
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'components/_sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/topbar.php'; ?>

        <div class="container-fluid p-4">
            <!-- Success/Error Messages -->
            <?php
            if (isset($_SESSION['incident_success'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>' . htmlspecialchars($_SESSION['incident_success']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
                unset($_SESSION['incident_success']);
            }
            if (isset($_SESSION['incident_error'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>' . htmlspecialchars($_SESSION['incident_error']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
                unset($_SESSION['incident_error']);
            }
            ?>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-4" id="incidentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="report-tab" data-bs-toggle="tab" data-bs-target="#report" type="button" role="tab">
                        <i class="bi bi-plus-circle me-2"></i>Report Incident
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                        <i class="bi bi-clock-history me-2"></i>My Reports
                        <?php if (count($incidents) > 0): ?>
                            <span class="badge bg-primary ms-2"><?php echo count($incidents); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="incidentTabsContent">
                <!-- Report Form Tab -->
                <div class="tab-pane fade show active" id="report" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Report an Incident</h5>
                        </div>
                        <div class="card-body">
                            <form action="process_incident.php" method="POST" id="incidentForm">
                                <!-- Incident Type -->
                                <div class="mb-3">
                                    <label for="incident_type" class="form-label">Incident Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="incident_type" name="incident_type" required>
                                        <option value="">Select incident type</option>
                                        <option value="fire">🔥 Fire</option>
                                        <option value="injury">🏥 Injury</option>
                                        <option value="theft">🔒 Theft</option>
                                        <option value="vandalism">🎨 Vandalism</option>
                                        <option value="hazard">⚠️ Safety Hazard</option>
                                        <option value="other">❓ Other</option>
                                    </select>
                                </div>

                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required 
                                            placeholder="Please describe:
• What happened?
• When did it happen?
• Who was involved?
• Any immediate actions taken?"></textarea>
                                </div>

                                <!-- Location -->
                                <div class="mb-3">
                                    <label class="form-label">Location <span class="text-danger">*</span></label>
                                    <div id="map"></div>
                                    <small id="location-status" class="form-text">Click on the map to mark the incident location</small>
                                    <input type="hidden" id="latitude" name="latitude" required>
                                    <input type="hidden" id="longitude" name="longitude" required>
                                </div>

                                <!-- Priority Level -->
                                <div class="mb-3">
                                    <label for="priority_level" class="form-label">Priority Level <span class="text-danger">*</span></label>
                                    <select class="form-select" id="priority_level" name="priority_level" required>
                                        <option value="low">🟢 Low - Minor concern</option>
                                        <option value="moderate" selected>🟡 Moderate - Important but not urgent</option>
                                        <option value="high">🟠 High - Needs urgent attention</option>
                                        <option value="critical">🔴 Critical - Life-threatening emergency</option>
                                    </select>
                                </div>

                                <!-- Reporter Safety Status -->
                                <div class="mb-3">
                                    <label for="reporter_safe_status" class="form-label">Your Safety Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="reporter_safe_status" name="reporter_safe_status" required>
                                        <option value="safe">✅ I am safe</option>
                                        <option value="injured">⚠️ I am injured</option>
                                        <option value="unknown">❓ Unknown</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-2"></i>Submit Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div class="tab-pane fade" id="history" role="tabpanel">
                    <?php if (count($incidents) > 0): ?>
                        <div class="row g-4">
                            <?php foreach ($incidents as $incident): ?>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card incident-card h-100 shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($incident['incident_type']); ?></span>
                                            <span class="badge status-badge <?php echo 'priority-' . $incident['priority_level']; ?>">
                                                <?php echo ucfirst($incident['priority_level']); ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text"><?php echo htmlspecialchars($incident['description']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo date('M d, Y H:i', strtotime($incident['date_reported'])); ?>
                                                </small>
                                                <span class="badge bg-<?php 
                                                    echo $incident['status'] === 'pending' ? 'warning' : 
                                                        ($incident['status'] === 'in_progress' ? 'info' : 
                                                        ($incident['status'] === 'resolved' ? 'success' : 'secondary')); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $incident['status'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    <?php echo number_format($incident['latitude'], 6) . ', ' . number_format($incident['longitude'], 6); ?>
                                                </small>
                                                <?php if ($incident['assigned_to']): ?>
                                                    <small class="text-muted">
                                                        <i class="bi bi-person me-1"></i>Assigned
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                            <h5>No incident reports yet</h5>
                            <p class="text-muted">You haven't submitted any incident reports.</p>
                            <button class="btn btn-primary" onclick="document.getElementById('report-tab').click()">
                                <i class="bi bi-plus-circle me-2"></i>Report an Incident
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>

    <!-- Map Initialization Script -->
    <script src="../assets/js/user-menu.js"></script>

    <script>
        // Initialize the map
        let map = L.map('map').setView([14.5995, 120.9842], 13); // Default to Manila coordinates
        let marker = null;

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Function to get address from coordinates
        async function getAddressFromCoordinates(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                const data = await response.json();
                return data.display_name;
            } catch (error) {
                console.error('Error getting address:', error);
                return null;
            }
        }

        // Handle map clicks
        map.on('click', async function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            // Update hidden inputs
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;

            // Update marker
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng]).addTo(map);

            // Get and display address
            document.getElementById('location-status').innerHTML = 
                '<i class="bi bi-geo-alt me-1"></i>Getting address...';
            
            const address = await getAddressFromCoordinates(lat, lng);
            if (address) {
                document.getElementById('location-status').innerHTML = 
                    `<i class="bi bi-geo-alt me-1"></i>Location: ${address}`;
            } else {
                document.getElementById('location-status').innerHTML = 
                    `<i class="bi bi-geo-alt me-1"></i>Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            }
        });

        // Form validation
        document.getElementById('incidentForm').addEventListener('submit', function(e) {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;

            if (!lat || !lng) {
                e.preventDefault();
                alert('Please select a location on the map');
            }
        });

        // Try to get user's location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                map.setView([lat, lng], 15);
                
                // Set initial marker
                marker = L.marker([lat, lng]).addTo(map);
                
                // Set initial values
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
                
                // Get and display address
                document.getElementById('location-status').innerHTML = 
                    '<i class="bi bi-geo-alt me-1"></i>Getting address...';
                
                const address = await getAddressFromCoordinates(lat, lng);
                if (address) {
                    document.getElementById('location-status').innerHTML = 
                        `<i class="bi bi-geo-alt me-1"></i>Current location: ${address}`;
                } else {
                    document.getElementById('location-status').innerHTML = 
                        `<i class="bi bi-geo-alt me-1"></i>Current location: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                }
            }, function(error) {
                console.log('Error getting location:', error);
                document.getElementById('location-status').innerHTML = 
                    '<i class="bi bi-geo-alt me-1"></i>Please click on the map to select the incident location';
            });
        }

        // Switch to report tab after successful submission
        <?php if (isset($_SESSION['incident_success'])): ?>
        document.getElementById('history-tab').click();
        <?php endif; ?>
    </script>
</body>
</html>