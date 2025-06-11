<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get user's incidents
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM incident_reports WHERE reported_by = ? ORDER BY date_reported DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Incident Reports - PROTEQ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/g_user.css">
    <style>
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
    </style>
</head>
<body class="bg-light">
    <?php include 'components/_sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/topbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class="bi bi-list-check me-2"></i>My Incident Reports</h4>
                <a href="Incident_report.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Report New Incident
                </a>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($incident = $result->fetch_assoc()): ?>
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
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                    <h5>No incident reports yet</h5>
                    <p class="text-muted">You haven't submitted any incident reports.</p>
                    <a href="Incident_report.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Report an Incident
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/user-menu.js"></script>
</body>
</html> 