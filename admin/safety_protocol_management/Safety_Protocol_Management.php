<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin-login.php");
    exit();
}

// Database connection
require_once '../../includes/db.php';

// Fetch safety protocols
$sql = "SELECT * FROM safety_protocols ORDER BY created_at DESC";
$result = $conn->query($sql);
$safetyProtocols = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety Protocol Management - PROTEQ Admin Dashboard</title>
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
                        Safety Protocol Management
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
                            Protocol Management
                        </h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProtocolModal" data-bs-toggle="tooltip" title="Create new protocol">
                            <i class="bi bi-plus-circle me-1"></i> New Protocol
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
                                            Filter Protocols
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Protocol Type</label>
                                                <select class="form-select" id="typeFilter">
                                                    <option value="all">All Types</option>
                                                    <option value="fire">Fire Safety</option>
                                                    <option value="earthquake">Earthquake Safety</option>
                                                    <option value="medical">Medical Emergency</option>
                                                    <option value="intrusion">Security Intrusion</option>
                                                    <option value="general">General Safety</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Search</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="bi bi-search"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="searchFilter" placeholder="Search protocols...">
                                                    <button class="btn btn-outline-secondary" type="button" id="clearFilters">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Sort By</label>
                                                <select class="form-select" id="sortFilter">
                                                    <option value="newest">Newest First</option>
                                                    <option value="oldest">Oldest First</option>
                                                    <option value="title_asc">Title (A-Z)</option>
                                                    <option value="title_desc">Title (Z-A)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Protocols Table -->
                        <div class="table-responsive">
                            <table id="protocolsTable" class="table table-hover table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Attachment</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($safetyProtocols as $protocol): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($protocol['type']) {
                                                    'fire' => 'danger',
                                                    'earthquake' => 'warning',
                                                    'medical' => 'info',
                                                    'intrusion' => 'dark',
                                                    'general' => 'primary',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <i class="bi bi-<?php 
                                                    echo match($protocol['type']) {
                                                        'fire' => 'fire',
                                                        'earthquake' => 'geo-alt',
                                                        'medical' => 'heart-pulse',
                                                        'intrusion' => 'shield-lock',
                                                        'general' => 'shield-check',
                                                        default => 'question-circle'
                                                    };
                                                ?> me-1"></i>
                                                <?php echo ucfirst(htmlspecialchars($protocol['type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($protocol['title']); ?></td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($protocol['description']); ?>">
                                                <?php echo htmlspecialchars($protocol['description']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($protocol['file_attachment']): ?>
                                                <a href="../../uploads/protocols/<?php echo htmlspecialchars($protocol['file_attachment']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   target="_blank">
                                                    <i class="bi bi-file-earmark-text me-1"></i>
                                                    View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No attachment</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $admin_query = "SELECT name FROM admin WHERE admin_id = ?";
                                            $stmt = $conn->prepare($admin_query);
                                            $stmt->bind_param("i", $protocol['created_by']);
                                            $stmt->execute();
                                            $admin_result = $stmt->get_result();
                                            $admin = $admin_result->fetch_assoc();
                                            echo htmlspecialchars($admin['name'] ?? 'Unknown');
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary edit-protocol" 
                                                        data-id="<?php echo $protocol['protocol_id']; ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-protocol" 
                                                        data-id="<?php echo $protocol['protocol_id']; ?>">
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
                    <h5 class="modal-title" id="mapModalLabel">Protocol Area</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="protocolMap" style="height: 500px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Protocol Modal -->
    <div class="modal fade" id="newProtocolModal" tabindex="-1" aria-labelledby="newProtocolModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="newProtocolModalLabel">
                        <i class="bi bi-shield-lock-fill text-primary me-2"></i>
                        Create New Safety Protocol
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="protocolForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="protocol_type" class="form-label">
                                        <i class="bi bi-shield-exclamation me-2"></i>
                                        Protocol Type
                                    </label>
                                    <select id="protocol_type" name="type" class="form-select" required>
                                        <option value="">Select Protocol Type</option>
                                        <option value="fire">Fire Safety</option>
                                        <option value="earthquake">Earthquake Safety</option>
                                        <option value="medical">Medical Emergency</option>
                                        <option value="intrusion">Security Intrusion</option>
                                        <option value="general">General Safety</option>
                                    </select>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="title" class="form-label">
                                        <i class="bi bi-card-heading me-2"></i>
                                        Title
                                    </label>
                                    <input type="text" id="title" name="title" class="form-control" required 
                                           maxlength="100" placeholder="Enter protocol title">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="file_attachment" class="form-label">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        File Attachment
                                    </label>
                                    <input type="file" id="file_attachment" name="file_attachment" 
                                           class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="form-text">Supported formats: PDF, JPG, PNG (Max size: 5MB)</div>
                                </div>

                                <div id="file_preview" class="border rounded p-2 text-center mb-4" style="min-height: 100px;">
                                    <div class="text-muted">
                                        <i class="bi bi-file-earmark-text fs-1"></i>
                                        <p class="mb-0">No file selected</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="description" class="form-label">
                                <i class="bi bi-card-text me-2"></i>
                                Description
                            </label>
                            <textarea id="description" name="description" class="form-control" required 
                                    minlength="10" rows="6" 
                                    placeholder="Please provide detailed information about the safety protocol..."></textarea>
                            <div class="form-text">
                                <span id="charCount">0</span> characters (minimum 10 required)
                            </div>
                        </div>

                        <div class="d-grid gap-3">
                            <button type="button" class="btn btn-primary" id="saveProtocol">
                                <i class="bi bi-shield-lock-fill me-2"></i> 
                                Create Protocol
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
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
        // Wait for jQuery to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Check if jQuery is loaded
            if (typeof jQuery === 'undefined') {
                console.error('jQuery is not loaded!');
                return;
            }

            // Now we can safely use jQuery
            $(function() {
                // Initialize tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

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

                // Initialize location map for new alert modal
                $('#newProtocolModal').on('shown.bs.modal', function() {
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

                // Function to update location marker
                function updateLocationMarker(latlng) {
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

                // Save alert
                $('#saveProtocol').on('click', function() {
                    const saveButton = $(this);
                    const buttonText = saveButton.find('.button-text');
                    const spinner = saveButton.find('.spinner-border');

                    // Validate form
                    const form = $('#protocolForm');
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
                    buttonText.text('Creating Protocol...');
                    spinner.removeClass('d-none');
                    saveButton.prop('disabled', true);

                    // Get all form values
                    const formData = {
                        protocol_type: $('select[name="protocol_type"]').val(),
                        title: $('input[name="title"]').val(),
                        description: $('textarea[name="description"]').val(),
                        latitude: parseFloat(latitude),
                        longitude: parseFloat(longitude),
                        radius_km: parseFloat($('input[name="radius_km"]').val())
                    };

                    // Debug: Log form data
                    console.log('Form data being sent:', formData);
                    
                    $.ajax({
                        url: 'process_protocol.php',
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            console.log('Server response:', response);
                            try {
                                const res = typeof response === 'object' ? response : JSON.parse(response);
                                
                                showNotification(res.message, res.success ? 'success' : 'error');

                                if (res.success) {
                                    $('#newProtocolModal').modal('hide');
                                    setTimeout(() => {
                                        location.reload();
                                    }, 1000);
                                } else {
                                    buttonText.text('Create Protocol');
                                    spinner.addClass('d-none');
                                    saveButton.prop('disabled', false);
                                    
                                    console.error('Protocol creation failed:', res.message);
                                    if (res.debug) {
                                        console.error('Debug info:', res.debug);
                                    }
                                }
                            } catch (e) {
                                buttonText.text('Create Protocol');
                                spinner.addClass('d-none');
                                saveButton.prop('disabled', false);
                                
                                console.error('Response parsing error:', e);
                                console.error('Raw response:', response);
                                
                                showNotification('Error processing server response. Please try again.', 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            buttonText.text('Create Protocol');
                            spinner.addClass('d-none');
                            saveButton.prop('disabled', false);

                            console.error('AJAX error:', {
                                status: status,
                                error: error,
                                response: xhr.responseText
                            });

                            let errorMessage = 'Error creating protocol';
                            
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
                $('#newProtocolModal').on('hidden.bs.modal', function() {
                    const form = $('#protocolForm');
                    form[0].reset();
                    $('#selectedLat').val('');
                    $('#selectedLng').val('');
                    if (locationMarker) {
                        locationMap.removeLayer(locationMarker);
                        locationMarker = null;
                    }
                    // Reset button state
                    const saveButton = $('#saveProtocol');
                    saveButton.find('.button-text').text('Create Protocol');
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
                        map = L.map('protocolMap').setView([lat, lng], 13);
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
                const table = $('#protocolsTable').DataTable({
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
                        info: "Showing _START_ to _END_ of _TOTAL_ protocols",
                        infoEmpty: "No protocols available",
                        infoFiltered: "(filtered from _MAX_ total protocols)"
                    }
                });

                // Custom filtering function
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    const typeFilter = $('#typeFilter').val();
                    const searchFilter = $('#searchFilter').val().toLowerCase();

                    // Get row data
                    const row = table.row(dataIndex).data();
                    if (!row) return false;

                    // Get type and title from row
                    const type = row[0].toLowerCase();
                    const title = row[1].toLowerCase();
                    const description = row[2].toLowerCase();

                    // Debug logging
                    console.log('Row Status:', type);
                    console.log('Type Filter:', typeFilter);
                    console.log('Match:', type === typeFilter.toLowerCase());

                    // Type filter
                    if (typeFilter !== 'all' && type !== typeFilter.toLowerCase()) {
                        return false;
                    }

                    // Search filter
                    if (searchFilter) {
                        return title.includes(searchFilter) || 
                               description.includes(searchFilter) || 
                               type.includes(searchFilter);
                    }

                    return true;
                });

                // Add event listeners for filters
                $('#typeFilter').on('change', function() {
                    console.log('Type filter changed:', $(this).val());
                    table.draw();
                });

                $('#searchFilter').on('keyup', function() {
                    table.draw();
                });

                // Clear all filters
                $('#clearFilters').on('click', function() {
                    $('#typeFilter').val('all');
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
                        url: 'process_protocol.php',
                        type: 'POST',
                        data: {
                            action: currentAction,
                            protocol_id: currentAlertId
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

                $(document).on('click', '.edit-protocol', function() {
                    const protocolId = $(this).data('id');
                    showConfirmationModal('Are you sure you want to edit this protocol?', 'edit', protocolId);
                });

                $(document).on('click', '.delete-protocol', function() {
                    const protocolId = $(this).data('id');
                    showConfirmationModal('Are you sure you want to delete this protocol? This action cannot be undone.', 'delete', protocolId);
                });

                // File preview functionality
                $('#file_attachment').on('change', function() {
                    const file = this.files[0];
                    const preview = $('#file_preview');
                    
                    if (file) {
                        // Check file size (5MB limit)
                        if (file.size > 5 * 1024 * 1024) {
                            showNotification('File size exceeds 5MB limit', 'error');
                            this.value = '';
                            return;
                        }

                        // Check file type
                        const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                        if (!validTypes.includes(file.type)) {
                            showNotification('Invalid file type. Please upload PDF or image files only.', 'error');
                            this.value = '';
                            return;
                        }

                        // Create preview
                        if (file.type === 'application/pdf') {
                            preview.html(`
                                <div class="text-primary">
                                    <i class="bi bi-file-earmark-pdf fs-1"></i>
                                    <p class="mb-0">${file.name}</p>
                                </div>
                            `);
                        } else {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                preview.html(`
                                    <img src="${e.target.result}" class="img-fluid" style="max-height: 200px;">
                                    <p class="mb-0 mt-2">${file.name}</p>
                                `);
                            }
                            reader.readAsDataURL(file);
                        }
                    } else {
                        preview.html(`
                            <div class="text-muted">
                                <i class="bi bi-file-earmark-text fs-1"></i>
                                <p class="mb-0">No file selected</p>
                            </div>
                        `);
                    }
                });

                // Character count for description
                $('#description').on('input', function() {
                    const count = $(this).val().length;
                    $('#charCount').text(count);
                });

                // Form submission
                $('#saveProtocol').on('click', function() {
                    const form = $('#protocolForm')[0];
                    const formData = new FormData(form);

                    // Validate form
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }

                    // Show loading state
                    const saveButton = $(this);
                    const originalText = saveButton.html();
                    saveButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...');
                    saveButton.prop('disabled', true);

                    $.ajax({
                        url: 'process_protocol.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            try {
                                const res = JSON.parse(response);
                                showNotification(res.message, res.success ? 'success' : 'error');

                                if (res.success) {
                                    $('#newProtocolModal').modal('hide');
                                    setTimeout(() => {
                                        location.reload();
                                    }, 1000);
                                }
                            } catch (e) {
                                showNotification('Error processing response', 'error');
                            }
                        },
                        error: function() {
                            showNotification('Error creating protocol', 'error');
                        },
                        complete: function() {
                            saveButton.html(originalText);
                            saveButton.prop('disabled', false);
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>