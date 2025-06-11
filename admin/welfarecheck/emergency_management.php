<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Database connection
require_once '../../includes/db.php';

// Get active emergency
$sql = "SELECT * FROM emergencies WHERE is_active = 1 ORDER BY triggered_at DESC LIMIT 1";
$result = $conn->query($sql);
$active_emergency = $result->fetch_assoc();

// Get emergency history
$history_sql = "SELECT e.*, a.name as triggered_by_name 
                FROM emergencies e 
                LEFT JOIN admin a ON e.triggered_by = a.admin_id 
                ORDER BY e.triggered_at DESC";
$history_result = $conn->query($history_sql);
$emergency_history = $history_result->fetch_all(MYSQLI_ASSOC);

// Get welfare check responses for active emergency
$responses = [];
if ($active_emergency) {
    $sql = "
        SELECT wc.*, CONCAT(gu.first_name, ' ', gu.last_name) as name, gu.department, gu.college 
        FROM welfare_checks wc 
        JOIN general_users gu ON wc.user_id = gu.user_id 
        WHERE wc.emergency_id = ?
        ORDER BY wc.reported_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $active_emergency['emergency_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $responses[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Management - Admin Dashboard - PROTEQ</title>
    <!-- CSS imports -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../../assets/css/admin_css/admin-styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-search/dist/leaflet-search.min.css" />
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include '../components/_sidebar.php'; ?>

        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
                    <h4 class="ms-3 mb-0">Emergency Management</h4>
                    <div class="ms-auto">
                        <!-- Add top nav elements here if needed -->
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-4">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Emergency created successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0">
                            <i class="text-primary me-2"></i>
                            Emergency Management
                        </h5>
                        <?php if (!$active_emergency): ?>
                            <div class="ms-auto">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newEmergencyModal">
                                    <i class="bi bi-plus-circle me-1"></i> New Emergency
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($active_emergency): ?>
                            <div class="card mb-4 border-danger">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-exclamation-triangle-fill text-danger fs-1"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h2 class="mb-1">Active Emergency</h2>
                                            <h3 class="text-danger mb-2"><?php echo htmlspecialchars($active_emergency['emergency_type']); ?></h3>
                                            <p class="mb-2"><?php echo htmlspecialchars($active_emergency['description']); ?></p>
                                            <p class="text-muted mb-0">
                                                <i class="bi bi-clock me-1"></i>
                                                Triggered: <?php echo date('F j, Y g:i a', strtotime($active_emergency['triggered_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <button class="btn btn-danger" id="resolveEmergencyBtn" data-emergency-id="<?php echo $active_emergency['emergency_id']; ?>">
                                        <i class="bi bi-check-circle me-1"></i> Resolve Emergency
                                    </button>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header bg-white py-3">
                                    <h5 class="mb-0">
                                        <i class="bi bi-people-fill text-primary me-2"></i>
                                        Response Summary
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Response Filters -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card border-0 bg-light">
                                                <div class="card-body">
                                                    <h6 class="card-title mb-3">
                                                        <i class="bi bi-funnel me-2"></i>
                                                        Filter Responses
                                                    </h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-select" id="responseStatusFilter" data-bs-toggle="tooltip" title="Filter by response status">
                                                                <option value="">All Statuses</option>
                                                                <option value="SAFE">Safe</option>
                                                                <option value="NEEDS_HELP">Needs Help</option>
                                                                <option value="NO_RESPONSE">No Response</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Department</label>
                                                            <select class="form-select" id="departmentFilter" data-bs-toggle="tooltip" title="Filter by department">
                                                                <option value="">All Departments</option>
                                                                <?php
                                                                $departments = array_unique(array_column($responses, 'department'));
                                                                foreach ($departments as $dept) {
                                                                    echo "<option value='$dept'>$dept</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Search</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">
                                                                    <i class="bi bi-search"></i>
                                                                </span>
                                                                <input type="text" class="form-control" id="responseSearchFilter" placeholder="Search responses..." data-bs-toggle="tooltip" title="Search in all fields">
                                                                <button class="btn btn-outline-secondary" type="button" id="clearResponseFilters" data-bs-toggle="tooltip" title="Clear all filters">
                                                                    <i class="bi bi-x-circle"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="responseTable" class="table table-hover table-bordered align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Department</th>
                                                    <th>College</th>
                                                    <th>Status</th>
                                                    <th>Remarks</th>
                                                    <th>Reported At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($responses as $response): ?>
                                                    <tr data-status="<?php echo $response['status']; ?>" 
                                                        data-department="<?php echo $response['department']; ?>">
                                                        <td><?php echo htmlspecialchars($response['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($response['department']); ?></td>
                                                        <td><?php echo htmlspecialchars($response['college']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo match($response['status']) {
                                                                    'SAFE' => 'success',
                                                                    'NEEDS_HELP' => 'danger',
                                                                    'NO_RESPONSE' => 'warning',
                                                                    default => 'secondary'
                                                                };
                                                            ?>">
                                                                <i class="bi bi-<?php 
                                                                    echo match($response['status']) {
                                                                        'SAFE' => 'check-circle',
                                                                        'NEEDS_HELP' => 'exclamation-triangle',
                                                                        'NO_RESPONSE' => 'clock',
                                                                        default => 'question-circle'
                                                                    };
                                                                ?> me-1"></i>
                                                                <?php echo ucfirst(strtolower($response['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($response['remarks']); ?></td>
                                                        <td>
                                                            <i class="bi bi-clock me-1"></i>
                                                            <?php echo date('g:i a', strtotime($response['reported_at'])); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Emergency History Section -->
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock-history text-primary me-2"></i>
                                    Emergency History
                                </h5>
                                <?php if (!$active_emergency): ?>
                                    <div class="ms-auto">
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newEmergencyModal">
                                            <i class="bi bi-plus-circle me-1"></i> New Emergency
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <!-- Filters Section -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title mb-3">
                                                    <i class="bi bi-funnel me-2"></i>
                                                    Filter Emergencies
                                                </h6>
                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Emergency Type</label>
                                                        <select class="form-select" id="typeFilter" data-bs-toggle="tooltip" title="Filter by type of emergency">
                                                            <option value="all">All Types</option>
                                                            <option value="FIRE">Fire</option>
                                                            <option value="EARTHQUAKE">Earthquake</option>
                                                            <option value="TYPHOON">Typhoon</option>
                                                            <option value="OTHER">Other</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-select" id="statusFilter" data-bs-toggle="tooltip" title="Filter by emergency status">
                                                            <option value="all">All Status</option>
                                                            <option value="active">Active</option>
                                                            <option value="resolved">Resolved</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Date Range</label>
                                                        <select class="form-select" id="dateFilter" data-bs-toggle="tooltip" title="Filter by date range">
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
                                                            <input type="text" class="form-control" id="searchFilter" placeholder="Search emergencies..." data-bs-toggle="tooltip" title="Search in all fields">
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

                                <!-- Emergency History Table -->
                                <div class="table-responsive">
                                    <table id="emergencyTable" class="table table-hover table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th>Triggered</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($emergency_history as $emergency): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo match($emergency['emergency_type']) {
                                                                'FIRE' => 'danger',
                                                                'EARTHQUAKE' => 'warning',
                                                                'TYPHOON' => 'info',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <i class="bi bi-<?php 
                                                                echo match($emergency['emergency_type']) {
                                                                    'FIRE' => 'fire',
                                                                    'EARTHQUAKE' => 'geo-alt',
                                                                    'TYPHOON' => 'cloud-rain-heavy',
                                                                    default => 'exclamation-circle'
                                                                };
                                                            ?> me-1"></i>
                                                            <?php echo htmlspecialchars($emergency['emergency_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($emergency['description']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $emergency['is_active'] ? 'danger' : 'success'; ?>">
                                                            <?php echo $emergency['is_active'] ? 'Active' : 'Resolved'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span>
                                                                <i class="bi bi-clock me-1"></i>
                                                                <?php echo date('F j, Y g:i a', strtotime($emergency['triggered_at'])); ?>
                                                            </span>
                                                            <small class="text-muted">
                                                                By: <?php echo htmlspecialchars($emergency['triggered_by_name'] ?? 'Unknown'); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($emergency['is_active']): ?>
                                                            <button class="btn btn-sm btn-danger" onclick="resolveEmergency(<?php echo $emergency['emergency_id']; ?>)">
                                                                <i class="bi bi-check-circle me-1"></i> Resolve
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">
                                                                <i class="bi bi-check-circle me-1"></i> Resolved
                                                                <?php if ($emergency['resolved_at']): ?>
                                                                    <br>
                                                                    <small>
                                                                        <?php echo date('F j, Y g:i a', strtotime($emergency['resolved_at'])); ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endif; ?>
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
        </div>
    </div>

    <!-- New Emergency Modal -->
    <div class="modal fade" id="newEmergencyModal" tabindex="-1" aria-labelledby="newEmergencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="newEmergencyModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                        Create New Emergency
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newEmergencyForm" method="POST" action="create_emergency.php" onsubmit="return confirmEmergencyCreation(event)">
                        <div class="form-group mb-3">
                            <label for="emergency_type" class="form-label">
                                <i class="bi bi-shield-exclamation me-1"></i>
                                Emergency Type
                            </label>
                            <select id="emergency_type" name="emergency_type" class="form-select" required>
                                <option value="">Select Emergency Type</option>
                                <option value="FIRE">
                                    <i class="bi bi-fire"></i> Fire
                                </option>
                                <option value="EARTHQUAKE">
                                    <i class="bi bi-geo-alt"></i> Earthquake
                                </option>
                                <option value="TYPHOON">
                                    <i class="bi bi-cloud-rain-heavy"></i> Typhoon
                                </option>
                                <option value="OTHER">
                                    <i class="bi bi-exclamation-circle"></i> Other
                                </option>
                            </select>
                            <div class="form-text">Select the type of emergency that needs to be declared.</div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="description" class="form-label">
                                <i class="bi bi-card-text me-1"></i>
                                Description
                            </label>
                            <textarea id="description" name="description" class="form-control" required 
                                    minlength="10" maxlength="500" rows="4" 
                                    placeholder="Please provide detailed information about the emergency..."></textarea>
                            <div class="form-text">
                                <span id="charCount">0</span>/500 characters (minimum 10 required)
                            </div>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Warning:</strong> Creating a new emergency will:
                            <ul class="mb-0 mt-2">
                                <li>Notify all registered users</li>
                                <li>Trigger the welfare check system</li>
                                <li>Require immediate attention from all departments</li>
                            </ul>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="create_emergency" class="btn btn-danger">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> 
                                Trigger Emergency
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resolve Emergency Modal -->
    <div class="modal fade" id="resolveEmergencyModal" tabindex="-1" aria-labelledby="resolveEmergencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="resolveEmergencyModalLabel">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        Resolve Emergency
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resolveEmergencyForm" method="POST">
                        <input type="hidden" id="resolve_emergency_id" name="emergency_id">
                        <div class="form-group mb-3">
                            <label for="resolution_reason" class="form-label">
                                <i class="bi bi-card-text me-1"></i>
                                Resolution Reason
                            </label>
                            <textarea id="resolution_reason" name="resolution_reason" class="form-control" required 
                                    minlength="10" maxlength="500" rows="4" 
                                    placeholder="Please provide details about how the emergency was resolved..."></textarea>
                            <div class="form-text">
                                <span id="resolveCharCount">0</span>/500 characters (minimum 10 required)
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Note:</strong> Resolving an emergency will:
                            <ul class="mb-0 mt-2">
                                <li>Mark the emergency as inactive</li>
                                <li>Close the welfare check system for this emergency</li>
                                <li>Archive all responses for future reference</li>
                            </ul>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i> 
                                Confirm Resolution
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span id="toastMessage">Emergency has been successfully resolved.</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Script imports in correct order -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-search/dist/leaflet-search.min.js"></script>
    <script src="../assets/js/admin-script.js"></script>

    <script>
        // Define resolveEmergency function in global scope
        function resolveEmergency(emergencyId) {
            // Set the emergency ID in the hidden input
            document.getElementById('resolve_emergency_id').value = emergencyId;
            
            // Show the resolution modal
            const resolveModal = new bootstrap.Modal(document.getElementById('resolveEmergencyModal'));
            resolveModal.show();
        }

        $(document).ready(function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Bind resolve emergency button click event
            $('#resolveEmergencyBtn').on('click', function() {
                const emergencyId = $(this).data('emergency-id');
                resolveEmergency(emergencyId);
            });

            // Handle description character count
            const description = document.getElementById('description');
            const charCount = document.getElementById('charCount');
            
            description.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length;
                
                // Update color based on length
                if (length < 10) {
                    charCount.classList.add('text-danger');
                    charCount.classList.remove('text-success');
                } else {
                    charCount.classList.add('text-success');
                    charCount.classList.remove('text-danger');
                }
            });

            // Initialize DataTables
            const emergencyTable = $('#emergencyTable').DataTable({
                dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"B>>rt<"d-flex justify-content-between align-items-center mt-3"<"d-flex align-items-center"i><"d-flex align-items-center"p>>',
                buttons: [
                    {
                        extend: 'collection',
                        text: 'Export',
                        buttons: [
                            'excel', 'csv', 'pdf', 'print'
                        ]
                    }
                ],
                order: [[3, 'desc']], // Sort by Triggered column in descending order
                pageLength: 10
            });

            const responseTable = $('#responseTable').DataTable({
                dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"B>>rt<"d-flex justify-content-between align-items-center mt-3"<"d-flex align-items-center"i><"d-flex align-items-center"p>>',
                buttons: [
                    {
                        extend: 'collection',
                        text: 'Export',
                        buttons: [
                            'excel', 'csv', 'pdf', 'print'
                        ]
                    }
                ],
                order: [[5, 'desc']], // Sort by Reported At column in descending order
                pageLength: 10
            });

            // Emergency History Filters
            $('#typeFilter, #statusFilter, #dateFilter').on('change', function() {
                emergencyTable.draw();
            });

            $('#searchFilter').on('keyup', function() {
                emergencyTable.search(this.value).draw();
            });

            $('#clearFilters').on('click', function() {
                $('#typeFilter, #statusFilter, #dateFilter').val('all');
                $('#searchFilter').val('');
                emergencyTable.search('').draw();
            });

            // Response Filters
            $('#responseStatusFilter, #departmentFilter').on('change', function() {
                responseTable.draw();
            });

            $('#responseSearchFilter').on('keyup', function() {
                responseTable.search(this.value).draw();
            });

            $('#clearResponseFilters').on('click', function() {
                $('#responseStatusFilter, #departmentFilter').val('');
                $('#responseSearchFilter').val('');
                responseTable.search('').draw();
            });

            // Custom filtering function for responses
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const statusFilter = $('#responseStatusFilter').val();
                const departmentFilter = $('#departmentFilter').val();
                
                // If no filters are selected, show all rows
                if (!statusFilter && !departmentFilter) {
                    return true;
                }

                const row = responseTable.row(dataIndex).data();
                const status = $(row[3]).text().trim().toUpperCase();
                const department = row[1];

                // If status filter is empty (All Statuses), only check department
                if (!statusFilter) {
                    return !departmentFilter || department === departmentFilter;
                }

                // If department filter is empty, only check status
                if (!departmentFilter) {
                    return status === statusFilter;
                }

                // If both filters are set, check both
                return status === statusFilter && department === departmentFilter;
            });

            // Custom filtering function for emergency history
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                const typeFilter = $('#typeFilter').val();
                const statusFilter = $('#statusFilter').val();
                const dateFilter = $('#dateFilter').val();
                
                // If no filters are selected, show all rows
                if (typeFilter === 'all' && statusFilter === 'all' && dateFilter === 'all') {
                    return true;
                }

                const row = emergencyTable.row(dataIndex).data();
                const type = $(row[0]).text().trim();
                const status = $(row[2]).text().trim();
                const date = new Date(row[3].split('By:')[0].trim());

                // Check type filter
                if (typeFilter !== 'all' && type !== typeFilter) {
                    return false;
                }

                // Check status filter
                if (statusFilter !== 'all') {
                    const isActive = status === 'Active';
                    if ((statusFilter === 'active' && !isActive) || (statusFilter === 'resolved' && isActive)) {
                        return false;
                    }
                }

                // Check date filter
                if (dateFilter !== 'all') {
                    const now = new Date();
                    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    const weekAgo = new Date(today);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    const monthAgo = new Date(today);
                    monthAgo.setMonth(monthAgo.getMonth() - 1);

                    switch (dateFilter) {
                        case 'today':
                            if (date < today) return false;
                            break;
                        case 'week':
                            if (date < weekAgo) return false;
                            break;
                        case 'month':
                            if (date < monthAgo) return false;
                            break;
                    }
                }

                return true;
            });

            // Handle resolution form submission
            document.getElementById('resolveEmergencyForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const emergencyId = document.getElementById('resolve_emergency_id').value;
                const resolutionReason = document.getElementById('resolution_reason').value;
                
                if (resolutionReason.length < 10) {
                    alert('Please provide a resolution reason (minimum 10 characters)');
                    return;
                }
                
                // Send resolution request
                $.ajax({
                    url: 'resolve_emergency.php',
                    method: 'POST',
                    data: {
                        emergency_id: emergencyId,
                        resolution_reason: resolutionReason
                    },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // Close the modal
                            bootstrap.Modal.getInstance(document.getElementById('resolveEmergencyModal')).hide();
                            
                            // Show success toast
                            const toast = new bootstrap.Toast(document.getElementById('successToast'));
                            document.getElementById('toastMessage').textContent = 'Emergency has been successfully resolved.';
                            toast.show();
                            
                            // Reload the page after toast is hidden
                            document.getElementById('successToast').addEventListener('hidden.bs.toast', function () {
                                window.location.reload();
                            });
                        } else {
                            alert('Error resolving emergency: ' + (result.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Error resolving emergency. Please try again.');
                    }
                });
            });

            // Handle resolution reason character count
            const resolutionReason = document.getElementById('resolution_reason');
            const resolveCharCount = document.getElementById('resolveCharCount');
            
            resolutionReason.addEventListener('input', function() {
                const length = this.value.length;
                resolveCharCount.textContent = length;
                
                // Update color based on length
                if (length < 10) {
                    resolveCharCount.classList.add('text-danger');
                    resolveCharCount.classList.remove('text-success');
                } else {
                    resolveCharCount.classList.add('text-success');
                    resolveCharCount.classList.remove('text-danger');
                }
            });

            // Function to confirm emergency creation
            function confirmEmergencyCreation(event) {
                event.preventDefault();
                
                const type = document.getElementById('emergency_type').value;
                const description = document.getElementById('description').value;
                
                if (!type || !description) {
                    alert('Please fill in all required fields.');
                    return false;
                }
                
                if (description.length < 10) {
                    alert('Description must be at least 10 characters long.');
                    return false;
                }
                
                const confirmMessage = `Are you sure you want to trigger a ${type} emergency?\n\nDescription: ${description}\n\nThis will notify all users.`;
                
                if (confirm(confirmMessage)) {
                    document.getElementById('newEmergencyForm').submit();
                }
                
                return false;
            }
        });
    </script>
</body>
</html>