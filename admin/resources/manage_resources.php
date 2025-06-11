<?php
require_once '../includes/db.php';



// Fetch evacuation centers
$centers = [];
$sql = "SELECT * FROM evacuation_centers ORDER BY name";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $centers[] = $row;
    }
}

// Fetch resources for a specific center if requested
$selectedCenter = isset($_GET['center_id']) ? (int)$_GET['center_id'] : null;
$resources = [];
if ($selectedCenter) {
    $sql = "SELECT * FROM resources WHERE center_id = ? ORDER BY type";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selectedCenter);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resources - PROTEQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin_css/admin-styles.css">
    <style>
        .resource-card {
            transition: transform 0.2s;
        }
        .resource-card:hover {
            transform: translateY(-5px);
        }
        .quantity-badge {
            font-size: 1.2em;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'components/_sidebar.php'; ?>

        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
                    <h4 class="ms-3 mb-0">Manage Resources</h4>
                </div>
            </nav>

            <div class="container-fluid p-4">
                <!-- Center Selection -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Select Evacuation Center</h5>
                                <form method="GET" class="d-flex gap-2">
                                    <select name="center_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">Select a center...</option>
                                        <?php foreach ($centers as $center): ?>
                                            <option value="<?php echo $center['center_id']; ?>" 
                                                    <?php echo $selectedCenter == $center['center_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($center['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($selectedCenter): ?>
                    <!-- Resource Management -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Resources</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResourceModal">
                                        <i class="bi bi-plus-circle"></i> Add Resource
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row g-4">
                                        <?php foreach ($resources as $resource): ?>
                                            <div class="col-md-4">
                                                <div class="card resource-card h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                            <h5 class="card-title mb-0"><?php echo ucfirst($resource['type']); ?></h5>
                                                            <span class="badge bg-primary quantity-badge"><?php echo $resource['quantity']; ?></span>
                                                        </div>
                                                        <p class="card-text text-muted">
                                                            Last updated: <?php echo date('M d, Y H:i', strtotime($resource['last_updated'])); ?>
                                                        </p>
                                                        <div class="d-flex gap-2">
                                                            <button class="btn btn-sm btn-outline-primary" 
                                                                    onclick="editResource(<?php echo $resource['resource_id']; ?>, '<?php echo $resource['type']; ?>', <?php echo $resource['quantity']; ?>)">
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger" 
                                                                    onclick="deleteResource(<?php echo $resource['resource_id']; ?>)">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Resource Modal -->
    <div class="modal fade" id="addResourceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addResourceForm">
                        <input type="hidden" name="center_id" value="<?php echo $selectedCenter; ?>">
                        <div class="mb-3">
                            <label class="form-label">Resource Type</label>
                            <select class="form-select" name="type" required>
                                <option value="">Select type...</option>
                                <option value="food">Food</option>
                                <option value="water">Water</option>
                                <option value="medical">Medical</option>
                                <option value="shelter">Shelter</option>
                                <option value="clothing">Clothing</option>
                                <option value="blankets">Blankets</option>
                                <option value="hygiene">Hygiene Kits</option>
                                <option value="first_aid">First Aid Kits</option>
                                <option value="flashlights">Flashlights</option>
                                <option value="batteries">Batteries</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" min="0" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveResource()">Save Resource</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Resource Modal -->
    <div class="modal fade" id="editResourceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editResourceForm">
                        <input type="hidden" name="resource_id" id="editResourceId">
                        <div class="mb-3">
                            <label class="form-label">Resource Type</label>
                            <select class="form-select" name="type" id="editResourceType" required>
                                <option value="food">Food</option>
                                <option value="water">Water</option>
                                <option value="medical">Medical</option>
                                <option value="shelter">Shelter</option>
                                <option value="clothing">Clothing</option>
                                <option value="blankets">Blankets</option>
                                <option value="hygiene">Hygiene Kits</option>
                                <option value="first_aid">First Aid Kits</option>
                                <option value="flashlights">Flashlights</option>
                                <option value="batteries">Batteries</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" id="editResourceQuantity" min="0" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateResource()">Update Resource</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editResource(resourceId, type, quantity) {
            document.getElementById('editResourceId').value = resourceId;
            document.getElementById('editResourceType').value = type;
            document.getElementById('editResourceQuantity').value = quantity;
            new bootstrap.Modal(document.getElementById('editResourceModal')).show();
        }

        function deleteResource(resourceId) {
            if (confirm('Are you sure you want to delete this resource?')) {
                fetch('process_resource.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&resource_id=${resourceId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting resource: ' + data.message);
                    }
                });
            }
        }

        function saveResource() {
            const form = document.getElementById('addResourceForm');
            const formData = new FormData(form);
            formData.append('action', 'add');

            fetch('process_resource.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error adding resource: ' + data.message);
                }
            });
        }

        function updateResource() {
            const form = document.getElementById('editResourceForm');
            const formData = new FormData(form);
            formData.append('action', 'update');

            fetch('process_resource.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating resource: ' + data.message);
                }
            });
        }
    </script>
</body>
</html> 