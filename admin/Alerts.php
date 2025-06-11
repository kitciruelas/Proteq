<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Database connection
require_once '../includes/db.php';

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
    <title>Alerts - Admin Dashboard - PROTEQ</title>
    <!-- Existing CSS imports -->
    <!-- Add Leaflet CSS for maps -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../../assets/css/admin_css/admin-styles.css">
    <!-- Include Leaflet Control Search CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-search/dist/leaflet-search.min.css" />
    <!-- Include Notifications CSS -->
    <link rel="stylesheet" href="../../assets/css/notifications.css">
    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'components/_sidebar.php'; ?>

        <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
                    <h4 class="ms-3 mb-0">Alerts Management</h4>
                    <div class="ms-auto">
                        <!-- Add top nav elements here if needed -->
                    </div>
                </div>
         </nav>

            <div class="container-fluid p-4">
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Alerts Management</h5>
                        <div class="ms-auto">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAlertModal">
                                <i class="bi bi-plus-circle"></i> New Alert
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Alert Type</label>
                                                <select class="form-select" id="typeFilter">
                                                    <option value="all">All Types</option>
                                                    <option value="emergency">Emergency</option>
                                                    <option value="warning">Warning</option>
                                                    <option value="info">Information</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" id="statusFilter">
                                                    <option value="all">All Status</option>
                                                    <option value="active">Active</option>
                                                    <option value="resolved">Resolved</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Date Range</label>
                                                <select class="form-select" id="dateFilter">
                                                    <option value="all">All Time</option>
                                                    <option value="today">Today</option>
                                                    <option value="week">This Week</option>
                                                    <option value="month">This Month</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Search</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="searchFilter" placeholder="Search alerts...">
                                                    <button class="btn btn-outline-secondary" type="button" id="clearFilters">
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
                            <table id="alertsTable" class="table table-striped table-hover table-bordered">
                                <thead>
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
                                                <i class="bi bi-map"></i> View Area
                                            </button>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $alert['status'] == 'active' ? 'danger' : 'success'; ?>">
                                                <?php echo ucfirst($alert['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($alert['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-warning edit-alert" data-id="<?php echo $alert['id']; ?>" title="Edit Alert">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-success resolve-alert" data-id="<?php echo $alert['id']; ?>" title="Resolve Alert">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-alert" data-id="<?php echo $alert['id']; ?>" title="Delete Alert">
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

    <?php include 'components/modal/new_alert_modal.php'; ?>

    <!-- Script imports in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Include Leaflet Control Search JS -->
    <script src="https://unpkg.com/leaflet-search/dist/leaflet-search.min.js"></script>
    <script src="../assets/js/admin-script.js"></script>

    <script>
        $(document).ready(function() {
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

                // Get the row data
                const row = table.row(dataIndex).data();
                const type = $(row[0]).text().toLowerCase().trim(); // Type column (badge text)
                const title = row[1].toLowerCase(); // Title column
                const description = row[2].toLowerCase(); // Description column
                const status = $(row[4]).text().toLowerCase().trim(); // Status column (badge text)
                const createdDate = new Date(row[5]); // Created date column

                // Type filter
                if (typeFilter !== 'all' && type !== typeFilter) {
                    return false;
                }

                // Status filter
                if (statusFilter !== 'all' && status !== statusFilter) {
                    return false;
                }

                // Date filter
                if (dateFilter !== 'all') {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    switch(dateFilter) {
                        case 'today':
                            if (createdDate < today) return false;
                            break;
                        case 'week':
                            const weekAgo = new Date(today);
                            weekAgo.setDate(today.getDate() - 7);
                            if (createdDate < weekAgo) return false;
                            break;
                        case 'month':
                            const monthAgo = new Date(today);
                            monthAgo.setMonth(today.getMonth() - 1);
                            if (createdDate < monthAgo) return false;
                            break;
                    }
                }

                // Search filter
                if (searchFilter) {
                    const searchTerms = searchFilter.split(' ').filter(term => term.length > 0);
                    return searchTerms.every(term => 
                        title.includes(term) || 
                        description.includes(term) || 
                        type.includes(term) ||
                        status.includes(term)
                    );
                }

                return true;
            });

            // Add event listeners for filters
            $('#typeFilter, #statusFilter, #dateFilter').on('change', function() {
                table.draw();
            });

            $('#searchFilter').on('keyup', function() {
                table.draw();
            });

            // Clear all filters
            $('#clearFilters').on('click', function() {
                $('#typeFilter, #statusFilter, #dateFilter').val('all');
                $('#searchFilter').val('');
                table.draw();
            });

            // Handle alert actions
            $('.edit-alert').on('click', function() {
                const alertId = $(this).data('id');
                // Implement edit functionality
                console.log('Edit alert:', alertId);
            });

            $('.resolve-alert').on('click', function() {
                const alertId = $(this).data('id');
                // Implement resolve functionality
                console.log('Resolve alert:', alertId);
            });

            $('.delete-alert').on('click', function() {
                const alertId = $(this).data('id');
                // Implement delete functionality
                console.log('Delete alert:', alertId);
            });

            // Initialize map variable
            let map = null;
            let marker = null;
            let circle = null;

            // Handle View Area button click
            $('.view-map').on('click', function() {
                const lat = parseFloat($(this).data('lat'));
                const lng = parseFloat($(this).data('lng'));
                const radius = parseFloat($(this).data('radius'));

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
        });
    </script>
</body>
</html>