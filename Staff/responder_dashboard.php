<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Dashboard - PROTEQ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin_css/admin-styles.css">
    <style>
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .assignment-card {
            transition: all 0.3s ease;
        }
        .assignment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        #map {
            height: 300px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'components/_sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
                    <h4 class="ms-3 mb-0">Responder Dashboard</h4>
                    <div class="ms-auto">
                        <span class="text-muted me-3">Last Updated: <span id="lastUpdate">Loading...</span></span>
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-4">
                <!-- Status Overview -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1">John Doe</h5>
                                        <p class="text-muted mb-0">Nurse • ID: RSP-001</p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success status-badge" id="currentStatus">Available</span>
                                        <button class="btn btn-outline-primary ms-2" id="toggleStatus">
                                            <i class="bi bi-toggle-on"></i> Toggle Status
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Assignment -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card assignment-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Current Assignment</h5>
                            </div>
                            <div class="card-body">
                                <div id="currentAssignment">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> No active assignments at the moment.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Location</h5>
                            </div>
                            <div class="card-body">
                                <div id="map"></div>
                                <div class="mt-3">
                                    <p class="mb-1"><strong>Current Location:</strong></p>
                                    <p class="text-muted" id="currentLocation">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Assignments -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Recent Assignments</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Location</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Response Time</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentAssignments">
                                            <!-- Will be populated dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Custom JS -->
    <script>
        // Initialize map
        const map = L.map('map').setView([16.4023, 120.5960], 13);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors, © CartoDB'
        }).addTo(map);

        // Current location marker
        let currentLocationMarker = null;

        // Update location
        function updateLocation(position) {
            const { latitude, longitude } = position.coords;
            
            // Update map
            if (currentLocationMarker) {
                currentLocationMarker.setLatLng([latitude, longitude]);
            } else {
                currentLocationMarker = L.marker([latitude, longitude]).addTo(map);
            }
            map.setView([latitude, longitude], 15);

            // Update location text
            document.getElementById('currentLocation').textContent = 
                `Lat: ${latitude.toFixed(6)}, Long: ${longitude.toFixed(6)}`;

            // Send location to server
            updateLocationOnServer(latitude, longitude);
        }

        // Handle location errors
        function handleLocationError(error) {
            console.error('Error getting location:', error);
            document.getElementById('currentLocation').textContent = 
                'Unable to get location. Please check your device settings.';
        }

        // Start location tracking
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(updateLocation, handleLocationError, {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 5000
            });
        }

        // Toggle status
        document.getElementById('toggleStatus').addEventListener('click', function() {
            const statusBadge = document.getElementById('currentStatus');
            const currentStatus = statusBadge.textContent;
            
            if (currentStatus === 'Available') {
                statusBadge.textContent = 'Busy';
                statusBadge.className = 'badge bg-warning status-badge';
            } else {
                statusBadge.textContent = 'Available';
                statusBadge.className = 'badge bg-success status-badge';
            }

            // Update status on server
            updateStatusOnServer(statusBadge.textContent);
        });

        // Update location on server
        function updateLocationOnServer(latitude, longitude) {
            // This would be an API call to update the server
            console.log('Updating location:', { latitude, longitude });
        }

        // Update status on server
        function updateStatusOnServer(status) {
            // This would be an API call to update the server
            console.log('Updating status:', status);
        }

        // Load recent assignments
        function loadRecentAssignments() {
            // This would be an API call to get recent assignments
            const recentAssignments = [
                {
                    date: '2024-03-20 10:30',
                    location: 'Baguio City Center',
                    type: 'Medical Emergency',
                    status: 'Completed',
                    responseTime: '8 minutes'
                },
                {
                    date: '2024-03-20 09:15',
                    location: 'North Baguio Area',
                    type: 'Security Check',
                    status: 'Completed',
                    responseTime: '12 minutes'
                }
            ];

            const tbody = document.getElementById('recentAssignments');
            tbody.innerHTML = recentAssignments.map(assignment => `
                <tr>
                    <td>${assignment.date}</td>
                    <td>${assignment.location}</td>
                    <td>${assignment.type}</td>
                    <td><span class="badge bg-success">${assignment.status}</span></td>
                    <td>${assignment.responseTime}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary">View Details</button>
                    </td>
                </tr>
            `).join('');
        }

        // Initialize
        loadRecentAssignments();
        document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();

        // Update last update time every minute
        setInterval(() => {
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        }, 60000);
    </script>
</body>
</html> 