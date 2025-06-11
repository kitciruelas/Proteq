<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin-login.php");
    exit();
}

// Database connection
require_once '../../includes/db.php';

// Fetch active alerts
$sql = "SELECT * FROM alerts ORDER BY created_at DESC";
$result = $conn->query($sql);
$alerts = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Alerts - PROTEQ Admin Dashboard</title>
    <!-- CSS imports -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/admin_css/admin-styles.css">
    <link rel="stylesheet" href="../../assets/css/notifications.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <!-- Add Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include '../components/_sidebar.php'; ?>

        <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
                    <h4 class="ms-3 mb-0">
                        <i class=" text-primary me-2"></i>
                        Emergency Alerts
                    </h4>
                    <div class="ms-auto">
                       
                    </div>
                </div>
         </nav>

            <div class="container-fluid p-4">
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0">
                            <i class=" text-primary me-2"></i>
                            Alert Management
                        </h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAlertModal" data-bs-toggle="tooltip" title="Create new alert">
                            <i class="bi bi-plus-circle me-1"></i> New Alert
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Filters Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">
                                            <i class="bi bi-funnel me-2"></i>
                                            Filter Alerts
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Alert Type</label>
                                                <select class="form-select" id="typeFilter" data-bs-toggle="tooltip" title="Filter by alert type">
                                                    <option value="all">All Types</option>
                                                    <option value="emergency">Emergency</option>
                                                    <option value="warning">Warning</option>
                                                    <option value="info">Information</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" id="statusFilter" data-bs-toggle="tooltip" title="Filter by status">
                                                    <option value="all">All Status</option>
                                                    <option value="active">Active</option>
                                                    <option value="resolved">Resolved</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Date Range</label>
                                                <select class="form-select" id="dateFilter" data-bs-toggle="tooltip" title="Filter by date">
                                                    <option value="all">All Time</option>
                                                    <option value="today">Today</option>
                                                    <option value="week">This Week</option>
                                                    <option value="month">This Month</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Search</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="bi bi-search"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="searchFilter" placeholder="Search alerts..." data-bs-toggle="tooltip" title="Search in all fields">
                                                    <button class="btn btn-outline-secondary" type="button" id="clearFilters" data-bs-toggle="tooltip" title="Clear all filters">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alerts Table -->
                        <div class="table-responsive">
                            <table id="alertsTable" class="table table-hover table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Area</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alerts as $alert): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($alert['alert_type']) {
                                                    'emergency' => 'danger',
                                                    'warning' => 'warning',
                                                    'info' => 'info',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <i class="bi bi-<?php 
                                                    echo match($alert['alert_type']) {
                                                        'emergency' => 'exclamation-triangle',
                                                        'warning' => 'exclamation-circle',
                                                        'info' => 'info-circle',
                                                        default => 'question-circle'
                                                    };
                                                ?> me-1"></i>
                                                <?php echo ucfirst(htmlspecialchars($alert['alert_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($alert['title']); ?></td>
                                        <td><?php echo htmlspecialchars($alert['description']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-map" 
                                                    data-lat="<?php echo $alert['latitude']; ?>" 
                                                    data-lng="<?php echo $alert['longitude']; ?>"
                                                    data-radius="<?php echo $alert['radius_km']; ?>">
                                                <i class="bi bi-map me-1"></i> View Area
                                            </button>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $alert['status'] == 'active' ? 'danger' : 'success'; ?>">
                                                <i class="bi bi-<?php echo $alert['status'] == 'active' ? 'exclamation-circle' : 'check-circle'; ?> me-1"></i>
                                                <?php echo ucfirst(htmlspecialchars($alert['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-success resolve-alert" data-id="<?php echo $alert['id']; ?>" data-bs-toggle="tooltip" title="Mark as resolved">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-alert" data-id="<?php echo $alert['id']; ?>" data-bs-toggle="tooltip" title="Delete alert">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Map Modal -->
    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mapModalLabel">Alert Area</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="alertMap" style="height: 500px;"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="newAlertModal" tabindex="-1" aria-labelledby="newAlertModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="newAlertModalLabel">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                    Create New Alert
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="alertForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-4">
                                <label for="alert_type" class="form-label">
                                    <i class="bi bi-shield-exclamation me-2"></i>
                                    Alert Type
                                </label>
                                <select id="alert_type" name="alert_type" class="form-select" required data-bs-toggle="tooltip" title="Select alert type">
                                    <option value="">Select Alert Type</option>
                                    <option value="typhoon">
                                        <i class="bi bi-cloud-rain-heavy"></i> Typhoon
                                    </option>
                                    <option value="flood">
                                        <i class="bi bi-water"></i> Flood
                                    </option>
                                    <option value="fire">
                                        <i class="bi bi-fire"></i> Fire
                                    </option>
                                    <option value="earthquake">
                                        <i class="bi bi-geo-alt"></i> Earthquake
                                    </option>
                                    <option value="other">
                                        <i class="bi bi-exclamation-circle"></i> Other
                                    </option>
                                </select>
                                <div class="form-text">Select the type of alert that needs to be created.</div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="title" class="form-label">
                                    <i class="bi bi-card-heading me-2"></i>
                                    Title
                                </label>
                                <input type="text" id="title" name="title" class="form-control" required 
                                       placeholder="Enter a clear and concise title for the alert">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-4">
                                <label for="radius_km" class="form-label">
                                    <i class="bi bi-arrows-angle-expand me-2"></i>
                                    Alert Radius (km)
                                </label>
                                <input type="number" id="radius_km" name="radius_km" class="form-control" required 
                                       min="0.1" step="0.1" placeholder="Enter the radius in kilometers"
                                       data-bs-toggle="tooltip" title="Set affected area radius">
                                <div class="form-text">Specify the area of effect for this alert.</div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    Location
                                </label>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="locationSearch" placeholder="Search for a location..."
                                           data-bs-toggle="tooltip" title="Search location">
                                    <button class="btn btn-outline-secondary" type="button" id="searchLocation" data-bs-toggle="tooltip" title="Search location">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                                <small class="text-muted">Or click on the map below to select location</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="description" class="form-label">
                            <i class="bi bi-card-text me-2"></i>
                            Description
                        </label>
                        <textarea id="description" name="description" class="form-control" required 
                                minlength="10" maxlength="500" rows="6" 
                                placeholder="Please provide detailed information about the alert..."></textarea>
                        <div class="form-text">
                            <span id="charCount">0</span>/500 characters (minimum 10 required)
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <div id="locationMap" style="height: 300px; border-radius: 5px;"></div>
                        <input type="hidden" name="latitude" id="selectedLat" required>
                        <input type="hidden" name="longitude" id="selectedLng" required>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Warning:</strong> Creating a new alert will:
                        <ul class="mb-0 mt-2">
                            <li>Notify all registered users in the affected area</li>
                            <li>Display on the public alert map</li>
                            <li>Require monitoring and updates</li>
                        </ul>
                    </div>

                    <div class="d-grid gap-3">
                        <button type="button" class="btn btn-danger" id="saveAlert" data-bs-toggle="tooltip" title="Create alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                            Create Alert
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-bs-toggle="tooltip" title="Cancel">
                            <i class="bi bi-x-circle me-2"></i>
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- Add Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmationMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmAction">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script imports in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <!-- DataTables and its dependencies -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="../../assets/js/admin-script.js"></script>

    <script>
        // Initialize map variables
        let map = null;
        let marker = null;
        let circle = null;
        let locationMap = null;
        let locationMarker = null;
        let searchTimeout = null;
        let searchCache = new Map();
        let isSearching = false;

        // Function to show notification
        function showNotification(message, type = 'error') {
            const notification = $('<div>')
                .addClass('notification-snap-alert ' + type)
                .html('<i class="bi bi-' + (type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill') + '"></i> ' + message)
                .appendTo('body');

            setTimeout(() => {
                notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Function to update location marker
        function updateLocationMarker(latlng) {
            if (!locationMap) {
                console.error('Location map not initialized');
                return;
            }
            if (locationMarker) {
                locationMarker.setLatLng(latlng);
            } else {
                locationMarker = L.marker(latlng).addTo(locationMap);
            }
            locationMap.setView(latlng, 13);
        }

        // Function to update location inputs
        function updateLocationInputs(latlng) {
            $('#selectedLat').val(latlng.lat);
            $('#selectedLng').val(latlng.lng);
            $('input[name="latitude"]').val(latlng.lat);
            $('input[name="longitude"]').val(latlng.lng);
        }

        // Wait for document ready
        $(document).ready(function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize location map for new alert modal
            $('#newAlertModal').on('shown.bs.modal', function() {
                if (locationMap) {
                    locationMap.remove();
                }

                // Create new map
                locationMap = L.map('locationMap').setView([14.5995, 120.9842], 13); // Default to Manila
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(locationMap);

                // Handle map clicks
                locationMap.on('click', function(e) {
                    updateLocationMarker(e.latlng);
                    updateLocationInputs(e.latlng);
                });
            });

            // Save alert
            $('#saveAlert').on('click', function() {
                const saveButton = $(this);
                const buttonText = saveButton.find('.button-text');
                const spinner = saveButton.find('.spinner-border');

                // Validate form
                const form = $('#alertForm');
                if (!form[0].checkValidity()) {
                    form[0].reportValidity();
                    return;
                }

                // Validate location
                const latitude = $('#selectedLat').val();
                const longitude = $('#selectedLng').val();
                if (!latitude || !longitude) {
                    showNotification('Please select a location on the map or search for a location.', 'error');
                    return;
                }

                // Show loading state
                buttonText.text('Creating Alert...');
                spinner.removeClass('d-none');
                saveButton.prop('disabled', true);

                // Get all form values
                const formData = {
                    action: 'create',
                    alert_type: $('select[name="alert_type"]').val(),
                    title: $('input[name="title"]').val(),
                    description: $('textarea[name="description"]').val(),
                    latitude: parseFloat(latitude),
                    longitude: parseFloat(longitude),
                    radius_km: parseFloat($('input[name="radius_km"]').val())
                };

                // Debug: Log form data
                console.log('Form data being sent:', formData);
                
                $.ajax({
                    url: 'process_alert.php',
                    type: 'POST',
                    data: formData,
                    contentType: 'application/x-www-form-urlencoded',
                    processData: true,
                    success: function(response) {
                        console.log('Server response:', response);
                        try {
                            const res = typeof response === 'object' ? response : JSON.parse(response);
                            
                            showNotification(res.message, res.success ? 'success' : 'error');

                            if (res.success) {
                                $('#newAlertModal').modal('hide');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                buttonText.text('Create Alert');
                                spinner.addClass('d-none');
                                saveButton.prop('disabled', false);
                                
                                console.error('Alert creation failed:', res.message);
                                if (res.debug) {
                                    console.error('Debug info:', res.debug);
                                }
                            }
                        } catch (e) {
                            buttonText.text('Create Alert');
                            spinner.addClass('d-none');
                            saveButton.prop('disabled', false);
                            
                            console.error('Response parsing error:', e);
                            console.error('Raw response:', response);
                            
                            showNotification('Error processing server response. Please try again.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        buttonText.text('Create Alert');
                        spinner.addClass('d-none');
                        saveButton.prop('disabled', false);

                        console.error('AJAX error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });

                        let errorMessage = 'Error creating alert';
                        
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.message) {
                                errorMessage = errorResponse.message;
                            }
                        } catch (e) {
                            errorMessage = xhr.statusText || error;
                        }

                        showNotification(errorMessage, 'error');
                    }
                });
            });

            // Reset form when modal is closed
            $('#newAlertModal').on('hidden.bs.modal', function() {
                const form = $('#alertForm');
                form[0].reset();
                $('#selectedLat').val('');
                $('#selectedLng').val('');
                if (locationMarker) {
                    locationMap.removeLayer(locationMarker);
                    locationMarker = null;
                }
                // Reset button state
                const saveButton = $('#saveAlert');
                saveButton.find('.button-text').text('Create Alert');
                saveButton.find('.spinner-border').addClass('d-none');
                saveButton.prop('disabled', false);
            });

            // Handle View Area button click
            $(document).on('click', '.view-map', function(e) {
                e.preventDefault();
                const lat = parseFloat($(this).data('lat'));
                const lng = parseFloat($(this).data('lng'));
                const radius = parseFloat($(this).data('radius'));

                if (isNaN(lat) || isNaN(lng) || isNaN(radius)) {
                    showNotification('Invalid location data. Please try again.', 'error');
                    return;
                }

                // Show the modal
                $('#mapModal').modal('show');

                // Initialize map after modal is shown
                $('#mapModal').on('shown.bs.modal', function() {
                    if (map) {
                        map.remove();
                    }

                    // Create new map
                    map = L.map('alertMap').setView([lat, lng], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    // Add marker
                    marker = L.marker([lat, lng]).addTo(map);

                    // Add circle
                    circle = L.circle([lat, lng], {
                        radius: radius * 1000, // Convert km to meters
                        color: 'red',
                        fillColor: '#f03',
                        fillOpacity: 0.2
                    }).addTo(map);

                    // Fit bounds to show the entire circle
                    map.fitBounds(circle.getBounds());
                });
            });

            // Clean up map when modal is hidden
            $('#mapModal').on('hidden.bs.modal', function() {
                if (map) {
                    map.remove();
                    map = null;
                }
            });

            // Initialize DataTable with export buttons
            const table = $('#alertsTable').DataTable({
                dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"B>>rt<"d-flex justify-content-between align-items-center mt-3"<"d-flex align-items-center"i><"d-flex align-items-center"p>>',
                buttons: [
                    {
                        extend: 'collection',
                        text: 'Export',
                        buttons: [
                            {
                                extend: 'excel',
                                text: 'Excel',
                                className: 'btn btn-success btn-sm',
                                exportOptions: {
                                    columns: ':visible:not(:last-child)'
                                }
                            },
                            {
                                extend: 'csv',
                                text: 'CSV',
                                className: 'btn btn-info btn-sm',
                                exportOptions: {
                                    columns: ':visible:not(:last-child)'
                                }
                            },
                            {
                                extend: 'pdf',
                                text: 'PDF',
                                className: 'btn btn-danger btn-sm',
                                exportOptions: {
                                    columns: ':visible:not(:last-child)'
                                },
                                customize: function(doc) {
                                    doc.defaultStyle.fontSize = 10;
                                    doc.styles.tableHeader.fontSize = 11;
                                    doc.styles.tableHeader.fillColor = '#dc3545';
                                    doc.styles.tableHeader.color = 'white';
                                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                                }
                            },
                            {
                                extend: 'print',
                                text: 'Print',
                                className: 'btn btn-secondary btn-sm',
                                exportOptions: {
                                    columns: ':visible:not(:last-child)'
                                },
                                customize: function(win) {
                                    $(win.document.body).css('font-size', '10pt');
                                    $(win.document.body).find('table')
                                        .addClass('compact')
                                        .css('font-size', 'inherit');
                                }
                            }
                        ]
                    }
                ],
                order: [[5, 'desc']], // Sort by Created column in descending order
                pageLength: 10,
                language: {
                    search: "",
                    info: "Showing _START_ to _END_ of _TOTAL_ alerts",
                    infoEmpty: "No alerts available",
                    infoFiltered: "(filtered from _MAX_ total alerts)"
                }
            });

            // Custom filtering function
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const typeFilter = $('#typeFilter').val();
                const statusFilter = $('#statusFilter').val();
                const dateFilter = $('#dateFilter').val();
                const searchFilter = $('#searchFilter').val().toLowerCase();

                // Get row data
                const row = table.row(dataIndex).data();
                if (!row) return false;

                // Get type and status from badges
                const typeBadge = $(row[0]).find('.badge');
                const statusBadge = $(row[4]).find('.badge');
                
                const type = typeBadge.text().toLowerCase();
                const status = statusBadge.text().toLowerCase().trim();
                const title = row[1].toLowerCase();
                const description = row[2].toLowerCase();
                const createdDate = new Date(row[5]);

                // Debug logging
                console.log('Row Status:', status);
                console.log('Status Filter:', statusFilter);
                console.log('Match:', status === statusFilter.toLowerCase());

                // Type filter
                if (typeFilter !== 'all' && type !== typeFilter.toLowerCase()) {
                    return false;
                }

                // Status filter
                if (statusFilter !== 'all') {
                    if (status !== statusFilter.toLowerCase()) {
                        return false;
                    }
                }

                // Date filter
                if (dateFilter !== 'all') {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (dateFilter === 'today' && createdDate < today) {
                        return false;
                    }
                    
                    if (dateFilter === 'week') {
                        const weekAgo = new Date(today);
                        weekAgo.setDate(today.getDate() - 7);
                        if (createdDate < weekAgo) {
                            return false;
                        }
                    }
                    
                    if (dateFilter === 'month') {
                        const monthAgo = new Date(today);
                        monthAgo.setMonth(today.getMonth() - 1);
                        if (createdDate < monthAgo) {
                            return false;
                        }
                    }
                }

                // Search filter
                if (searchFilter) {
                    return title.includes(searchFilter) || 
                           description.includes(searchFilter) || 
                           type.includes(searchFilter) ||
                           status.includes(searchFilter);
                }

                return true;
            });

            // Add event listeners for filters
            $('#typeFilter').on('change', function() {
                console.log('Type filter changed:', $(this).val());
                table.draw();
            });

            $('#statusFilter').on('change', function() {
                console.log('Status filter changed:', $(this).val());
                table.draw();
            });

            $('#dateFilter').on('change', function() {
                console.log('Date filter changed:', $(this).val());
                table.draw();
            });

            $('#searchFilter').on('keyup', function() {
                table.draw();
            });

            // Clear all filters
            $('#clearFilters').on('click', function() {
                $('#typeFilter').val('all');
                $('#statusFilter').val('all');
                $('#dateFilter').val('all');
                $('#searchFilter').val('');
                table.draw();
            });

            // Location search functionality
            $('#searchLocation').on('click', function() {
                const searchText = $('#locationSearch').val().trim();
                if (!searchText) {
                    showNotification('Please enter a location to search.', 'error');
                    return;
                }

                // Show loading state
                const searchButton = $(this);
                const originalText = searchButton.html();
                searchButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Searching...');
                searchButton.prop('disabled', true);

                // Set timeout for search
                const searchTimeout = setTimeout(() => {
                    searchButton.html(originalText);
                    searchButton.prop('disabled', false);
                    showNotification('Search timed out. Please try again.', 'error');
                }, 10000); // 10 second timeout

                // Perform search
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchText)}&countrycodes=ph&limit=3&addressdetails=1&accept-language=en`)
                    .then(response => response.json())
                    .then(data => {
                        clearTimeout(searchTimeout);
                        searchButton.html(originalText);
                        searchButton.prop('disabled', false);

                        if (data && data.length > 0) {
                            const { lat, lon, display_name } = data[0];
                            updateLocationMarker([lat, lon]);
                            updateLocationInputs({ lat: parseFloat(lat), lng: parseFloat(lon) });
                            $('#locationSearch').val(display_name);
                        } else {
                            showNotification('Location not found. Please try a different search term.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        clearTimeout(searchTimeout);
                        searchButton.html(originalText);
                        searchButton.prop('disabled', false);
                        showNotification('Error searching location. Please try again.', 'error');
                    });
            });

            // Handle enter key in search input
            $('#locationSearch').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#searchLocation').click();
                }
            });

            // Handle alert actions
            let currentAction = null;
            let currentAlertId = null;

            function showConfirmationModal(message, action, alertId) {
                currentAction = action;
                currentAlertId = alertId;
                $('#confirmationMessage').text(message);
                $('#confirmationModal').modal('show');
            }

            $('#confirmAction').on('click', function() {
                if (!currentAction || !currentAlertId) return;

                const button = $(this);
                const originalText = button.html();
                button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                button.prop('disabled', true);

                $.ajax({
                    url: 'process_alert.php',
                    type: 'POST',
                    data: {
                        action: currentAction,
                        alert_id: currentAlertId
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                $('#confirmationModal').modal('hide');
                                showNotification(result.message, 'success');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                showNotification(result.message || 'Error processing request', 'error');
                            }
                        } catch (e) {
                            showNotification('Error processing response', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Error processing request', 'error');
                    },
                    complete: function() {
                        button.html(originalText);
                        button.prop('disabled', false);
                    }
                });
            });

            $(document).on('click', '.resolve-alert', function() {
                const alertId = $(this).data('id');
                showConfirmationModal('Are you sure you want to mark this alert as resolved?', 'resolve', alertId);
            });

            $(document).on('click', '.delete-alert', function() {
                const alertId = $(this).data('id');
                showConfirmationModal('Are you sure you want to delete this alert? This action cannot be undone.', 'delete', alertId);
            });
        });
    </script>
</body>
</html>