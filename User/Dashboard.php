<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../auth/login.php");
    exit();
}

// Get user data and emergency information from database
try {
    require_once '../includes/db.php';
    require_once '../includes/location_utils.php';
    
    $user_id = $_SESSION['user_id'];
    $query = "SELECT first_name, last_name FROM general_users WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $full_name = $user['first_name'] . ' ' . $user['last_name'];
    } else {
        header("Location: ../auth/login.php");
        exit();
    }

    // Fetch active alerts
    $alerts_query = "SELECT * FROM alerts WHERE status = 'active' ORDER BY created_at asc LIMIT 5";
    $alerts_result = mysqli_query($conn, $alerts_query);
    $active_alerts = [];
    if ($alerts_result) {
        while ($alert = mysqli_fetch_assoc($alerts_result)) {
            $active_alerts[] = $alert;
        }
    }

    // Fetch nearest evacuation center
    $nearest_center_query = "SELECT * FROM evacuation_centers WHERE status = 'open' ORDER BY current_occupancy ASC LIMIT 1";
    $nearest_center_result = mysqli_query($conn, $nearest_center_query);
    $nearest_center = $nearest_center_result ? mysqli_fetch_assoc($nearest_center_result) : null;

    // Fetch latest welfare check
    $welfare_query = "SELECT wc.*, e.emergency_type 
                     FROM welfare_checks wc 
                     JOIN emergencies e ON wc.emergency_id = e.emergency_id 
                     WHERE wc.user_id = ? 
                     ORDER BY wc.reported_at DESC 
                     LIMIT 1";
    $welfare_stmt = mysqli_prepare($conn, $welfare_query);
    mysqli_stmt_bind_param($welfare_stmt, "i", $user_id);
    mysqli_stmt_execute($welfare_stmt);
    $welfare_result = mysqli_stmt_get_result($welfare_stmt);
    $latest_welfare = $welfare_result ? mysqli_fetch_assoc($welfare_result) : null;

    // Calculate next welfare check time (24 hours after last check)
    $next_check_time = null;
    if ($latest_welfare) {
        $last_check = new DateTime($latest_welfare['reported_at']);
        $next_check = $last_check->modify('+24 hours');
        $now = new DateTime();
        $interval = $now->diff($next_check);
        $next_check_time = $interval->format('%h hours %i minutes');
    }

    // Fetch evacuation centers
    $centers_query = "SELECT * FROM evacuation_centers WHERE status = 'open' ORDER BY current_occupancy ASC LIMIT 3";
    $centers_result = mysqli_query($conn, $centers_query);
    $evacuation_centers = [];
    if ($centers_result) {
        while ($center = mysqli_fetch_assoc($centers_result)) {
            $evacuation_centers[] = $center;
        }
    }

    // Fetch safety protocols
    $protocols_query = "SELECT * FROM safety_protocols ORDER BY created_at DESC LIMIT 3";
    $protocols_result = mysqli_query($conn, $protocols_query);
    $safety_protocols = [];
    if ($protocols_result) {
        while ($protocol = mysqli_fetch_assoc($protocols_result)) {
            $safety_protocols[] = $protocol;
        }
    }

    // Fetch user's latest location
    $location_query = "SELECT * FROM user_locations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $location_stmt = mysqli_prepare($conn, $location_query);
    mysqli_stmt_bind_param($location_stmt, "i", $user_id);
    mysqli_stmt_execute($location_stmt);
    $location_result = mysqli_stmt_get_result($location_stmt);
    $latest_location = $location_result ? mysqli_fetch_assoc($location_result) : null;

    // Debug location data
    error_log("User ID: " . $user_id);
    error_log("Location Query: " . $location_query);
    error_log("Location Result: " . print_r($latest_location, true));
    if (!$location_result) {
        error_log("Location Query Error: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Proteq</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Notifications CSS -->
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/g_user.css">
    <!-- Location Tracker -->
    <script src="../assets/js/location-tracker.js"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #4f46e5;
            --success-color: #16a34a;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --info-color: #0891b2;
            --light-bg: #f8fafc;
            --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .home-banner {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            margin-bottom: 1rem;
            background: rgba(37, 99, 235, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            transform: translateY(-1px);
        }

        .list-group-item {
            padding: 1.25rem;
            border: none;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .avatar-sm {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
        }

        .hover-bg-light:hover {
            background-color: rgba(37, 99, 235, 0.05);
            transition: background-color 0.2s ease;
        }

        .card-header {
            padding: 1.25rem;
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .home-banner {
                padding: 1.5rem;
            }
            
            .card-body {
                padding: 1.25rem;
            }

            .btn {
                padding: 0.625rem 1.25rem;
            }

            .avatar-sm {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'components/_sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/topbar.php'; ?>

        <div class="p-4">
            <!-- Welcome Banner -->
            <div class="home-banner">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-8 col-md-12">
                        <h2 class="display-6 mb-2">Welcome, <?php echo htmlspecialchars($full_name); ?>! 👋</h2>
                        <p class="lead mb-0">Here's your personalized dashboard</p>
                    </div>
                    <div class="col-lg-4 col-md-12 text-lg-end text-center">
                        <p class="mb-1 fs-5"><?php echo date('l'); ?></p>
                        <h4 class="mb-0"><?php echo date('F d, Y'); ?></h4>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Bar - Responsive -->
            <div class="row mb-4">
                 <!-- Active Alerts -->
  <div class="col">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white border-bottom-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm bg-danger bg-opacity-10 rounded-circle">
                                        <i class="bi bi-megaphone text-danger fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Emergency Announcement</h6>
                                    <small class="text-muted">Important Information</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (count($active_alerts) > 0): ?>
                                <?php $latest_alert = $active_alerts[0]; ?>
                                <div class="announcement-content">
                                    <!-- Alert Type Badge -->
                                    <div class="mb-3">
                                        <span class="badge bg-danger px-3 py-2">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            <?php echo htmlspecialchars($latest_alert['alert_type']); ?>
                                        </span>
                                    </div>

                                    <!-- Title and Description -->
                                    <div class="announcement-body mb-4">
                                        <h5 class="mb-3"><?php echo htmlspecialchars($latest_alert['title']); ?></h5>
                                        <p class="text-muted mb-0">
                                            <?php echo htmlspecialchars($latest_alert['description']); ?>
                                        </p>
                                    </div>
                                    
                                    <!-- Location Information -->
                                    <div class="announcement-location bg-light rounded p-3 mb-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-geo-alt text-danger me-2"></i>
                                            <span class="fw-medium">Location Information</span>
                                        </div>
                                        <p class="text-muted small mb-2 ps-4">
                                            Coordinates: <?php echo htmlspecialchars($latest_alert['latitude'] . ', ' . $latest_alert['longitude']); ?>
                                        </p>
                                        <a href="https://www.google.com/maps?q=<?php echo $latest_alert['latitude'] . ',' . $latest_alert['longitude']; ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-danger w-100">
                                            <i class="bi bi-map me-1"></i> View Location on Map
                                        </a>
                                    </div>

                                    <!-- Footer Information -->
                                    <div class="announcement-footer">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-signpost-split text-danger me-2"></i>
                                                <span class="text-danger">
                                                    Affected Area: <?php echo number_format($latest_alert['radius_km'], 1); ?> km radius
                                                </span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-clock text-muted me-2"></i>
                                                <span class="text-muted">
                                                    Posted: <?php echo date('M d, Y H:i', strtotime($latest_alert['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="bi bi-check-circle text-success fs-1"></i>
                                    </div>
                                    <h6 class="text-success mb-2">No Active Alerts</h6>
                                    <p class="text-muted mb-0">Your area is currently safe</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
 
            <!-- Quick Summary Cards -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                <!-- Active Alerts -->
                <div class="col">
                    <div class="card shadow-sm h-100 border-start border-4 border-danger hover-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm bg-danger bg-opacity-10 rounded">
                                        <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Active Alert</h6>
                                </div>
                            </div>
                            <?php if (count($active_alerts) > 0): ?>
                                <?php $latest_alert = $active_alerts[0]; ?>
                                <div class="alert-content">
                                    <div class="mb-3">
                                        <span class="badge bg-danger mb-2"><?php echo htmlspecialchars($latest_alert['alert_type']); ?></span>
                                        <h6 class="text-danger mb-2">
                                            <?php echo htmlspecialchars($latest_alert['title']); ?>
                                        </h6>
                                        <p class="text-muted small mb-2">
                                            <?php echo htmlspecialchars($latest_alert['description']); ?>
                                        </p>
                                    </div>
                                    
                                
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No active alerts in your area</p>
                                <small class="text-success d-block mt-2">
                                    <i class="bi bi-check-circle"></i> All clear
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Evacuation Centers -->
                <div class="col">
                    <div class="card shadow-sm h-100 border-start border-4 border-success hover-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm bg-success bg-opacity-10 rounded">
                                        <i class="bi bi-building text-success fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Evacuation Centers</h6>
                                </div>
                            </div>
                            <p class="text-muted mb-0">
                                <?php echo count($evacuation_centers); ?> centers available
                            </p>
                            <small class="text-success d-block mt-2">
                                <i class="bi bi-geo-alt"></i> View locations
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Nearest Evacuation -->
                <div class="col">
                    <div class="card shadow-sm h-100 border-start border-4 border-info hover-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm bg-info bg-opacity-10 rounded">
                                        <i class="bi bi-geo-alt text-info fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Nearest Evacuation</h6>
                                </div>
                            </div>
                            <?php if ($nearest_center): ?>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($nearest_center['name']); ?></p>
                            <small class="text-info d-block mt-2">
                                <i class="bi bi-people"></i> <?php echo $nearest_center['current_occupancy']; ?>/<?php echo $nearest_center['capacity']; ?> capacity
                            </small>
                            <?php else: ?>
                            <p class="text-muted mb-0">No evacuation centers available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Welfare Check -->
                <div class="col">
                    <div class="card shadow-sm h-100 border-start border-4 border-warning hover-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm bg-warning bg-opacity-10 rounded">
                                        <i class="bi bi-check-circle text-warning fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Welfare Check</h6>
                                </div>
                            </div>
                            <?php if ($latest_welfare): ?>
                            <p class="text-muted mb-0">
                                <?php echo ucfirst(strtolower($latest_welfare['status'])); ?> - 
                                <?php echo htmlspecialchars($latest_welfare['remarks'] ?: 'No issues'); ?>
                            </p>
                            <small class="text-warning d-block mt-2">
                                <i class="bi bi-clock"></i> Next check due in <?php echo $next_check_time; ?>
                            </small>
                            <?php else: ?>
                            <p class="text-muted mb-0">No welfare checks submitted yet</p>
                            <small class="text-warning d-block mt-2">
                                <i class="bi bi-exclamation-circle"></i> Please submit your first welfare check
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="row mt-4 g-4">
                <!-- Left Column -->
                <div class="col-lg-4">
                    <!-- Active Alerts List -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Active Alerts</h5>
                            <span class="badge bg-danger"><?php echo count($active_alerts); ?> Active</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($active_alerts as $alert): ?>
                                <div class="list-group-item hover-bg-light">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($alert['title']); ?></h6>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($alert['description']); ?></p>
                                            <small class="text-danger">
                                                <i class="bi bi-geo-alt"></i> 
                                                <?php echo number_format($alert['radius_km'], 1); ?> km radius
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M d, H:i', strtotime($alert['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Evacuation Centers -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Nearby Evacuation Centers</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($evacuation_centers as $center): ?>
                                <div class="list-group-item hover-bg-light">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($center['name']); ?></h6>
                                            <p class="text-muted small mb-0">
                                                Capacity: <?php echo $center['current_occupancy']; ?>/<?php echo $center['capacity']; ?>
                                            </p>
                                            <?php if ($center['contact_person']): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($center['contact_person']); ?>
                                                <?php if ($center['contact_number']): ?>
                                                <br><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($center['contact_number']); ?>
                                                <?php endif; ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge bg-success"><?php echo ucfirst($center['status']); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-8">
                    <!-- Safety Protocols -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Safety Protocols</h5>
                            <a href="SafetyProtocol.php" class="btn btn-sm btn-outline-primary">View All Protocols</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (count($safety_protocols) > 0): ?>
                                    <?php foreach ($safety_protocols as $protocol): ?>
                                        <div class="list-group-item hover-bg-light">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($protocol['title']); ?></h6>
                                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($protocol['description']); ?></p>
                                                </div>
                                                <div class="text-end">
                                                    <?php
                                                    $badge_class = 'bg-info';
                                                    switch ($protocol['type']) {
                                                        case 'fire':
                                                            $badge_class = 'bg-danger';
                                                            break;
                                                        case 'earthquake':
                                                            $badge_class = 'bg-warning';
                                                            break;
                                                        case 'medical':
                                                            $badge_class = 'bg-success';
                                                            break;
                                                        case 'intrusion':
                                                            $badge_class = 'bg-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?> mb-2">
                                                        <?php echo ucfirst($protocol['type']); ?>
                                                    </span>
                                                    <?php if ($protocol['file_attachment']): ?>
                                                        <a href="../uploads/protocols/<?php echo htmlspecialchars($protocol['file_attachment']); ?>" 
                                                           class="btn btn-sm btn-link" 
                                                           target="_blank">
                                                            View Document
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-link">View Details</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="list-group-item">
                                        <div class="text-center py-3">
                                            <p class="text-muted mb-0">No safety protocols available</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Status Panel -->
                    <div class="card shadow-sm mt-4">
                        <div class="card-body">
                            <div class="row row-cols-1 row-cols-sm-3 g-3">
                                <div class="col">
                                    <div class="d-flex align-items-center p-3 rounded bg-light hover-bg-light">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm bg-primary bg-opacity-10 rounded">
                                                <i class="bi bi-person-check text-primary fs-4"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Student Account</h6>
                                            <p class="text-success mb-0">
                                                <i class="bi bi-check-circle"></i> Active
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="d-flex align-items-center p-3 rounded bg-light hover-bg-light">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm bg-info bg-opacity-10 rounded">
                                                <i class="bi bi-clock-history text-info fs-4"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Last Login</h6>
                                            <p class="text-muted mb-0"><?php echo date('M d, Y H:i'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="d-flex align-items-center p-3 rounded bg-light hover-bg-light">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm bg-warning bg-opacity-10 rounded">
                                                <i class="bi bi-cloud-sun text-warning fs-4"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Weather</h6>
                                            <p class="text-muted mb-0">Sunny, 25°C</p>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($latest_location): ?>
                                <div class="col">
                                    <div class="d-flex align-items-center p-3 rounded bg-light hover-bg-light">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm bg-success bg-opacity-10 rounded">
                                                <i class="bi bi-geo-alt text-success fs-4"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Current Location</h6>
                                            <p class="text-muted mb-0 location-display">
                                                <?php if ($latest_location && isset($latest_location['latitude']) && isset($latest_location['longitude'])): ?>
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
                                <?php else: ?>
                                <div class="col">
                                    <div class="d-flex align-items-center p-3 rounded bg-light hover-bg-light">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-sm bg-warning bg-opacity-10 rounded">
                                                <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0">Location Status</h6>
                                            <p class="text-warning mb-0">
                                                <i class="bi bi-exclamation-circle"></i> 
                                                Location tracking not enabled
                                            </p>
                                            <small class="text-muted">
                                                Please enable location services in your browser
                                            </small>
                                            <button onclick="enableLocation()" class="btn btn-sm btn-primary mt-2">
                                                <i class="bi bi-geo-alt"></i> Enable Location
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/js/user-menu.js"></script>
        <script src="../assets/js/notification-snap-alert.js"></script>
        <script>
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            let locationUpdateRetries = 0;
            const MAX_RETRIES = 3;

            // Function to enable location
            function enableLocation() {
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported by your browser. Please use a modern browser.');
                    return;
                }

                // Show loading state
                const locationDisplay = document.querySelector('.location-display');
                if (locationDisplay) {
                    locationDisplay.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <small>Requesting location access...</small>
                        </div>
                    `;
                }

                const options = {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                };

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Success - update location
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        
                        // Send location to server
                        fetch('update_location.php', {
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
                                // Update the location display
                                if (locationDisplay) {
                                    locationDisplay.innerHTML = `
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt-fill"></i> 
                                            ${formatCoordinatesWithDegrees(latitude, longitude)}
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
                                }
                                
                                // Show success message
                                const successAlert = document.createElement('div');
                                successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                                successAlert.style.zIndex = '9999';
                                successAlert.innerHTML = `
                                    <i class="bi bi-check-circle me-2"></i>
                                    Location updated successfully
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                `;
                                document.body.appendChild(successAlert);

                                // Remove alert after 3 seconds
                                setTimeout(() => {
                                    successAlert.remove();
                                }, 3000);
                            } else {
                                throw new Error(data.message || 'Failed to update location');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            if (locationDisplay) {
                                locationDisplay.innerHTML = `
                                    <small class="text-danger">
                                        <i class="bi bi-exclamation-circle"></i> 
                                        ${error.message || 'Failed to update location'}
                                    </small>
                                `;
                            }
                            showAlert('danger', error.message || 'Failed to update location');
                        });
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        let errorMessage = 'Location access denied';
                        
                        switch(error.code) {
                            case error.TIMEOUT:
                                errorMessage = 'Location request timed out. Please check your internet connection.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = 'Location information is unavailable.';
                                break;
                            case error.PERMISSION_DENIED:
                                errorMessage = 'Please allow location access in your browser settings.';
                                break;
                        }
                        
                        // Show error message
                        if (locationDisplay) {
                            locationDisplay.innerHTML = `
                                <small class="text-danger">
                                    <i class="bi bi-exclamation-circle"></i> 
                                    ${errorMessage}
                                </small>
                            `;
                        }

                        // Show browser settings instructions
                        showAlert('warning', `
                            To enable location services:
                            <ol class="mb-0 mt-2">
                                <li>Click the lock/info icon in your browser's address bar</li>
                                <li>Find "Location" or "Location Services"</li>
                                <li>Change it to "Allow"</li>
                                <li>Refresh this page</li>
                            </ol>
                        `, null, 10000);
                    },
                    options
                );
            }

            // Helper function to show alerts
            function showAlert(type, message, callback = null, duration = 3000) {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
                alert.style.zIndex = '9999';
                alert.innerHTML = `
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.body.appendChild(alert);

                setTimeout(() => {
                    alert.remove();
                    if (callback) callback();
                }, duration);
            }

            // Helper function to format coordinates with degrees
            function formatCoordinatesWithDegrees(lat, lng) {
                const latDirection = lat >= 0 ? 'N' : 'S';
                const longDirection = lng >= 0 ? 'E' : 'W';
                
                const latDegrees = Math.abs(lat);
                const longDegrees = Math.abs(lng);
                
                return `${latDegrees.toFixed(4)}° ${latDirection}, ${longDegrees.toFixed(4)}° ${longDirection}`;
            }

            // Helper function to format coordinates (keep this for backward compatibility)
            function formatCoordinates(lat, lng) {
                return formatCoordinatesWithDegrees(lat, lng);
            }

            // Function to start periodic location updates
            function startLocationUpdates() {
                // Update immediately
                enableLocation();
                
                // Then update every 5 minutes
                setInterval(enableLocation, 5 * 60 * 1000);
            }

            // Update location when page loads
            document.addEventListener('DOMContentLoaded', function() {
                // Check if location is already enabled
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            // Location is enabled, start periodic updates
                            startLocationUpdates();
                        },
                        function(error) {
                            // Location is not enabled, show the enable button
                            console.log('Location not enabled:', error);
                        }
                    );
                }
            });
        </script>
    </main>
</body>
</html>