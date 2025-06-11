<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Database connection
require_once '../../includes/db.php';

// Fetch users
$sql = "SELECT * FROM general_users ORDER BY created_at DESC";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - PROTEQ Admin Dashboard</title>
    <!-- CSS imports -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/admin_css/admin-styles.css">
    <link rel="stylesheet" href="../../assets/css/notifications.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <style>
        .btn-next {
            background-color: #0d6efd;
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            width: 100%;
        }

        .btn-next:hover {
            background-color: rgba(13, 110, 253, 0.85);
            color: white;
        }

        .btn-prev {
            background-color: #6c757d;
            color: white;
            flex: 1;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
        }

        .btn-prev:hover {
            background-color: #5a6268;
            color: white;
        }

        .btn-navigation {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .form-check-input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Link Styles */
        .login-link {
            font-size: 0.9em;
            margin-top: 1rem;
        }

        .login-link a {
            color: #0d6efd;
            font-weight: bold;
            text-decoration: none;
        }

        .login-link a:hover {
            color: rgba(13, 110, 253, 0.85);
            text-decoration: underline;
        }

        /* Step Indicator Styles */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 0 10px;
        }

        .step {
            text-align: center;
            flex: 1;
            font-size: 0.9rem;
        }

        .step-number {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background-color: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
            font-size: 0.9rem;
        }

        .step.active .step-number {
            background-color: #0d6efd;
            color: white;
        }

        .step.completed .step-number {
            background-color: #198754;
            color: white;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        /* Enhanced Border Styles */
        .card {
            border: 1px solid rgba(0, 0, 0, 0.125);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            background-color: #fff;
            padding: 1rem;
        }

        .table {
            border: 1px solid #dee2e6;
        }

        .table th {
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .table td {
            border-bottom: 1px solid #dee2e6;
        }

        .form-control, .form-select {
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .input-group {
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .input-group-text {
            border: none;
            background-color: #f8f9fa;
        }

        .btn {
            border: 1px solid transparent;
        }

        .btn-outline-secondary {
            border-color: #6c757d;
        }

        .modal-content {
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }

        .modal-header {
            border-bottom: 1px solid #dee2e6;
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
        }

        .badge {
            border: 1px solid transparent;
        }

        .filter-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background-color: #fff;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .list-group-item {
            border: 1px solid rgba(0, 0, 0, 0.125);
        }

        .list-group-item:first-child {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .list-group-item:last-child {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
    </style>
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
                        Users Management
                    </h4>
                    <div class="ms-auto">
                  
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-4">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4" id="managementTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="true">
                            <i class="bi bi-people-fill me-2"></i>Users
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff" type="button" role="tab" aria-controls="staff" aria-selected="false">
                            <i class="bi bi-person-badge-fill me-2"></i>Staff
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="managementTabsContent">
                    <!-- Users Tab -->
                    <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
                        <div class="card border shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-lines-fill text-primary me-2"></i>
                                    User List
                                </h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal">
                                    <i class="bi bi-plus-circle me-1"></i> 
                                    <span class="button-text">New User</span>
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
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
                                                    Filter Users
                                                </h6>
                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label">User Type</label>
                                                        <select class="form-select border" id="userTypeFilter">
                                                            <option value="all">All Types</option>
                                                            <option value="STUDENT">Student</option>
                                                            <option value="FACULTY">Faculty</option>
                                                            <option value="UNIVERSITY_EMPLOYEE">University Employee</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-select border" id="statusFilter">
                                                            <option value="all">All Status</option>
                                                            <option value="1">Active</option>
                                                            <option value="0">Inactive</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Department</label>
                                                        <select class="form-select border" id="departmentFilter">
                                                            <option value="all">All Departments</option>
                                                            <option value="CTE">College of Teacher Education (CTE)</option>
                                                            <option value="CICS">College of Information and Computing Sciences (CICS)</option>
                                                            <option value="CABE">College of Accountancy and Business Education (CABE)</option>
                                                            <option value="CAS">College of Arts and Sciences (CAS)</option>
                                                            <option value="CET">College of Engineering and Technology (CET)</option>
                                                            <option value="N/A">Not Applicable (N/A)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Search</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text border-end-0">
                                                                <i class="bi bi-search"></i>
                                                            </span>
                                                            <input type="text" class="form-control border-start-0" id="searchFilter" placeholder="Search users...">
                                                            <button class="btn btn-outline-secondary border-start-0" type="button" id="clearFilters">
                                                                <i class="bi bi-x-circle"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <table id="usersTable" class="table table-striped table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border">ID</th>
                                            <th class="border">Name</th>
                                            <th class="border">Email</th>
                                            <th class="border">User Type</th>
                                            <th class="border">Department</th>
                                            <th class="border">College/Course</th>
                                            <th class="border">Status</th>
                                            <th class="border">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="border"><?php echo htmlspecialchars($user['user_id']); ?></td>
                                            <td class="border"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td class="border"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="border">
                                                <span class="badge bg-<?php 
                                                    echo match($user['user_type']) {
                                                        'FACULTY' => 'primary',
                                                        'STUDENT' => 'success',
                                                        'UNIVERSITY_EMPLOYEE' => 'info',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo htmlspecialchars($user['user_type']); ?>
                                                </span>
                                            </td>
                                            <td class="border"><?php echo htmlspecialchars($user['department']); ?></td>
                                            <td class="border"><?php echo htmlspecialchars($user['college']); ?></td>
                                            <td class="border">
                                                <span class="badge bg-<?php echo $user['status'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="border">
                                                <button class="btn btn-sm btn-warning edit-user" data-id="<?php echo $user['user_id']; ?>" title="Edit User">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Staff Tab -->
                    <div class="tab-pane fade" id="staff" role="tabpanel" aria-labelledby="staff-tab">
                        <div class="card border shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-badge-fill text-primary me-2"></i>
                                    Staff List
                                </h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newStaffModal">
                                    <i class="bi bi-plus-circle me-1"></i> 
                                    <span class="button-text">New Staff</span>
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
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
                                                    Filter Staff
                                                </h6>
                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Role</label>
                                                        <select class="form-select border" id="roleFilter">
                                                            <option value="all">All Roles</option>
                                                            <option value="nurse">Nurse</option>
                                                            <option value="paramedic">Paramedic</option>
                                                            <option value="security">Security</option>
                                                            <option value="firefighter">Firefighter</option>
                                                            <option value="others">Others</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Availability</label>
                                                        <select class="form-select border" id="availabilityFilter">
                                                            <option value="all">All Status</option>
                                                            <option value="available">Available</option>
                                                            <option value="busy">Busy</option>
                                                            <option value="off-duty">Off Duty</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-select border" id="staffStatusFilter">
                                                            <option value="all">All Status</option>
                                                            <option value="active">Active</option>
                                                            <option value="inactive">Inactive</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Search</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text border-end-0">
                                                                <i class="bi bi-search"></i>
                                                            </span>
                                                            <input type="text" class="form-control border-start-0" id="staffSearchFilter" placeholder="Search staff...">
                                                            <button class="btn btn-outline-secondary border-start-0" type="button" id="clearStaffFilters">
                                                                <i class="bi bi-x-circle"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Staff Table -->
                                <table id="staffTable" class="table table-striped table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border">ID</th>
                                            <th class="border">Name</th>
                                            <th class="border">Email</th>
                                            <th class="border">Role</th>
                                            <th class="border">Availability</th>
                                            <th class="border">Status</th>
                                            <th class="border">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch staff
                                        $sql = "SELECT * FROM staff ORDER BY created_at DESC";
                                        $result = $conn->query($sql);
                                        $staff = $result->fetch_all(MYSQLI_ASSOC);
                                        
                                        foreach ($staff as $member): ?>
                                        <tr>
                                            <td class="border"><?php echo htmlspecialchars($member['staff_id']); ?></td>
                                            <td class="border"><?php echo htmlspecialchars($member['name']); ?></td>
                                            <td class="border"><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td class="border">
                                                <span class="badge bg-<?php 
                                                    echo match($member['role']) {
                                                        'nurse' => 'primary',
                                                        'paramedic' => 'success',
                                                        'security' => 'warning',
                                                        'firefighter' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo htmlspecialchars(ucfirst($member['role'])); ?>
                                                </span>
                                            </td>
                                            <td class="border">
                                                <span class="badge bg-<?php 
                                                    echo match($member['availability']) {
                                                        'available' => 'success',
                                                        'busy' => 'warning',
                                                        'off-duty' => 'secondary',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo htmlspecialchars(ucfirst($member['availability'])); ?>
                                                </span>
                                            </td>
                                            <td class="border">
                                                <span class="badge bg-<?php echo $member['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($member['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="border">
                                                <button class="btn btn-sm btn-warning edit-staff" data-id="<?php echo $member['staff_id']; ?>" title="Edit Staff">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
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

    <!-- New User Modal -->
    <div class="modal fade" id="newUserModal" tabindex="-1" aria-labelledby="newUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="newUserModalLabel">
                        <i class="bi bi-person-plus-fill text-primary me-2"></i>
                        Create New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="step-indicator mb-4">
                        <div class="step active" data-step="1">
                            <div class="step-number">1</div>
                            <div class="step-title">General Info</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-number">2</div>
                            <div class="step-title">Security</div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="step-number">3</div>
                            <div class="step-title">Review</div>
                        </div>
                    </div>

                    <form id="userForm">
                        <!-- Step 1: General Info -->
                        <div class="step-content active" data-step="1">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="bi bi-person me-1"></i>
                                        First Name
                                    </label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="bi bi-person me-1"></i>
                                        Last Name
                                    </label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-envelope me-1"></i>
                                    Email
                                </label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-building me-1"></i>
                                        Department
                                    </label>
                                    <select class="form-select" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="CTE">College of Teacher Education (CTE)</option>
                                        <option value="CICS">College of Information and Computing Sciences (CICS)</option>
                                        <option value="CABE">College of Accountancy and Business Education (CABE)</option>
                                        <option value="CAS">College of Arts and Sciences (CAS)</option>
                                        <option value="CET">College of Engineering and Technology (CET)</option>
                                        <option value="N/A">Not Applicable (N/A)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-mortarboard me-1"></i>
                                        College/Course
                                    </label>
                                    <select class="form-select" name="college" required>
                                        <option value="">Select College/Course</option>
                                        <!-- CTE Courses -->
                                        <optgroup label="College of Teacher Education (CTE)">
                                            <option value="Bachelor of Elementary Education">Bachelor of Elementary Education</option>
                                            <option value="Bachelor of Secondary Education - English">Bachelor of Secondary Education - English</option>
                                            <option value="Bachelor of Secondary Education - Mathematics">Bachelor of Secondary Education - Mathematics</option>
                                            <option value="Bachelor of Secondary Education - Science">Bachelor of Secondary Education - Science</option>
                                        </optgroup>
                                        <!-- CICS Courses -->
                                        <optgroup label="College of Information and Computing Sciences (CICS)">
                                            <option value="Bachelor of Science in Information Technology">Bachelor of Science in Information Technology</option>
                                            <option value="Bachelor of Science in Computer Science">Bachelor of Science in Computer Science</option>
                                            <option value="Bachelor of Science in Information Systems">Bachelor of Science in Information Systems</option>
                                        </optgroup>
                                        <!-- CABE Courses -->
                                        <optgroup label="College of Accountancy and Business Education (CABE)">
                                            <option value="Bachelor of Science in Accountancy">Bachelor of Science in Accountancy</option>
                                            <option value="Bachelor of Science in Business Administration">Bachelor of Science in Business Administration</option>
                                            <option value="Bachelor of Science in Hospitality Management">Bachelor of Science in Hospitality Management</option>
                                        </optgroup>
                                        <!-- CAS Courses -->
                                        <optgroup label="College of Arts and Sciences (CAS)">
                                            <option value="Bachelor of Arts in Communication">Bachelor of Arts in Communication</option>
                                            <option value="Bachelor of Science in Psychology">Bachelor of Science in Psychology</option>
                                            <option value="Bachelor of Science in Biology">Bachelor of Science in Biology</option>
                                        </optgroup>
                                        <!-- CET Courses -->
                                        <optgroup label="College of Engineering and Technology (CET)">
                                            <option value="Bachelor of Science in Civil Engineering">Bachelor of Science in Civil Engineering</option>
                                            <option value="Bachelor of Science in Electrical Engineering">Bachelor of Science in Electrical Engineering</option>
                                            <option value="Bachelor of Science in Mechanical Engineering">Bachelor of Science in Mechanical Engineering</option>
                                        </optgroup>
                                        <!-- N/A Option -->
                                        <optgroup label="Not Applicable">
                                            <option value="Not Applicable">Not Applicable</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person-badge me-1"></i>
                                    User Type
                                </label>
                                <select class="form-select" name="user_type" required>
                                    <option value="STUDENT">Student</option>
                                    <option value="FACULTY">Faculty</option>
                                    <option value="UNIVERSITY_EMPLOYEE">University Employee</option>
                                </select>
                            </div>
                            <div class="btn-navigation">
                                <button type="button" class="btn btn-next" onclick="nextStep(1)">Next</button>
                            </div>
                        </div>

                        <!-- Step 2: Security -->
                        <div class="step-content" data-step="2">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-key me-1"></i>
                                    Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Password must contain:
                                    <ul class="mb-0">
                                        <li>At least 8 characters</li>
                                        <li>At least one uppercase letter</li>
                                        <li>At least one number</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="bi bi-shield-lock me-2"></i>
                                <strong>Security Note:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>User will be required to change password on first login</li>
                                    <li>Account will be set to active by default</li>
                                    <li>User will receive a welcome email with login instructions</li>
                                </ul>
                            </div>
                            <div class="btn-navigation">
                                <button type="button" class="btn btn-prev" onclick="prevStep(2)">Previous</button>
                                <button type="button" class="btn btn-next" onclick="nextStep(2)">Next</button>
                            </div>
                        </div>

                        <!-- Step 3: Review -->
                        <div class="step-content" data-step="3">
                            <div class="review-section">
                                <h4 class="mb-3">
                                    <i class="bi bi-clipboard-check me-2"></i>
                                    Review User Information
                                </h4>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <i class="bi bi-person-vcard me-2"></i>
                                            General Information
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <p><strong>Name:</strong> <span id="review_name"></span></p>
                                                <p><strong>Email:</strong> <span id="review_email"></span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Department:</strong> <span id="review_department"></span></p>
                                                <p><strong>College:</strong> <span id="review_college"></span></p>
                                            </div>
                                        </div>
                                        <p class="mb-0"><strong>User Type:</strong> <span id="review_user_type"></span></p>
                                    </div>
                                </div>

                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <i class="bi bi-shield-lock me-2"></i>
                                            Security Information
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0"><strong>Password:</strong> ********</p>
                                    </div>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <strong>Please Review:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>All information is correct and complete</li>
                                        <li>User type matches the department and college</li>
                                        <li>Email address is valid and active</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="btn-navigation">
                                <button type="button" class="btn btn-prev" onclick="prevStep(3)">Previous</button>
                                <button type="button" class="btn btn-primary" id="saveUser">
                                    <i class="bi bi-person-plus-fill me-1"></i>
                                    <span class="button-text">Create User</span>
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="edit_user_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_user_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" id="edit_department" name="department">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">College/Course</label>
                            <input type="text" class="form-control" id="edit_college_course" name="college">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Type</label>
                            <select class="form-select" id="edit_user_type" name="user_type" required>
                                <option value="STUDENT">Student</option>
                                <option value="UNIVERSITY_EMPLOYEE">University Employee</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- New Staff Modal -->
    <div class="modal fade" id="newStaffModal" tabindex="-1" aria-labelledby="newStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="newStaffModalLabel">
                        <i class="bi bi-person-plus-fill text-primary me-2"></i>
                        Create New Staff
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newStaffForm" novalidate>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-person me-1"></i>
                                Full Name
                            </label>
                            <input type="text" class="form-control" name="name" required>
                            <div class="invalid-feedback">Please enter the staff member's full name.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-envelope me-1"></i>
                                Email
                            </label>
                            <input type="email" class="form-control" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-person-badge me-1"></i>
                                Role
                            </label>
                            <select class="form-select" name="role" required>
                                <option value="">Select Role</option>
                                <option value="nurse">Nurse</option>
                                <option value="paramedic">Paramedic</option>
                                <option value="security">Security</option>
                                <option value="firefighter">Firefighter</option>
                                <option value="others">Others</option>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-key me-1"></i>
                                Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="newStaffPassword" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleNewStaffPassword">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Please enter a valid password.</div>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Password must contain:
                                <ul class="mb-0">
                                    <li>At least 8 characters</li>
                                    <li>At least one uppercase letter</li>
                                    <li>At least one number</li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveNewStaff">
                        <span class="button-text">Create Staff</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStaffModalLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i>
                        Edit Staff
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStaffForm">
                        <input type="hidden" name="staff_id" id="edit_staff_id">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" id="edit_staff_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_staff_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="edit_staff_role" required>
                                <option value="nurse">Nurse</option>
                                <option value="paramedic">Paramedic</option>
                                <option value="security">Security</option>
                                <option value="firefighter">Firefighter</option>
                                <option value="others">Others</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Availability</label>
                            <select class="form-select" name="availability" id="edit_staff_availability" required>
                                <option value="available">Available</option>
                                <option value="busy">Busy</option>
                                <option value="off-duty">Off Duty</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_staff_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateStaff">
                        <span class="button-text">Update Staff</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

   

    <!-- Required Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
    <script src="../../assets/js/admin-script.js"></script>

    <script>
        function nextStep(currentStep) {
            // Validate current step
            if (!validateStep(currentStep)) {
                return;
            }

            // If moving to review step, update the review information
            if (currentStep === 2) {
                updateReviewInformation();
            }

            // Hide current step
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('completed');
            
            // Show next step
            const nextStep = currentStep + 1;
            document.querySelector(`.step-content[data-step="${nextStep}"]`).classList.add('active');
            document.querySelector(`.step[data-step="${nextStep}"]`).classList.add('active');
        }

        function prevStep(currentStep) {
            // Hide current step
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
            
            // Show previous step
            const prevStep = currentStep - 1;
            document.querySelector(`.step-content[data-step="${prevStep}"]`).classList.add('active');
            document.querySelector(`.step[data-step="${prevStep}"]`).classList.remove('completed');
        }

        function validateStep(step) {
            const currentStepContent = document.querySelector(`.step-content[data-step="${step}"]`);
            const requiredInputs = currentStepContent.querySelectorAll('input[required], select[required]');
            
            let isValid = true;
            let errorMessages = [];

            requiredInputs.forEach(input => {
                if (!input.value) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Special validation for department and college
            if (step === 1) {
                const department = document.querySelector('select[name="department"]');
                const college = document.querySelector('select[name="college"]');
                
                if (!department.value) {
                    isValid = false;
                    department.classList.add('is-invalid');
                    errorMessages.push('Please select a department');
                }
                
                if (!college.value) {
                    isValid = false;
                    college.classList.add('is-invalid');
                    errorMessages.push('Please select a college/course');
                }
            }

            // Enhanced security validation
            if (step === 2) {
                const password = document.querySelector('input[name="password"]');
                let passwordErrors = [];

                // Password length check
                if (password.value.length < 8) {
                    passwordErrors.push('Password must be at least 8 characters long');
                }

                // Password complexity checks
                if (!/[A-Z]/.test(password.value)) {
                    passwordErrors.push('Password must contain at least one uppercase letter');
                }
                if (!/[0-9]/.test(password.value)) {
                    passwordErrors.push('Password must contain at least one number');
                }

                // Add visual feedback for password field
                if (passwordErrors.length > 0) {
                    password.classList.add('is-invalid');
                    isValid = false;
                    errorMessages.push(...passwordErrors);
                } else {
                    password.classList.remove('is-invalid');
                }
            }

            // Show custom notification if there are errors
            if (!isValid && errorMessages.length > 0) {
                const notificationDiv = document.createElement('div');
                notificationDiv.className = 'notification-snap-alert error';
                notificationDiv.innerHTML = `
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <ul class="mb-0">
                        ${errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                    </ul>
                `;
                document.body.appendChild(notificationDiv);

                // Remove notification after 3 seconds
                setTimeout(() => {
                    notificationDiv.style.animation = 'fadeOut 0.3s ease-out forwards';
                    setTimeout(() => {
                        notificationDiv.remove();
                    }, 300);
                }, 3000);
            }

            return isValid;
        }

        function updateReviewInformation() {
            // Update General Information
            const firstName = document.querySelector('input[name="first_name"]').value;
            const lastName = document.querySelector('input[name="last_name"]').value;
            document.getElementById('review_name').textContent = `${firstName} ${lastName}`;
            document.getElementById('review_email').textContent = document.querySelector('input[name="email"]').value;
            document.getElementById('review_department').textContent = document.querySelector('select[name="department"]').value;
            document.getElementById('review_college').textContent = document.querySelector('select[name="college"]').value;
            document.getElementById('review_user_type').textContent = document.querySelector('select[name="user_type"]').value;
        }

        // Function to handle department selection
        function handleDepartmentChange() {
            const departmentSelect = document.querySelector('select[name="department"]');
            const collegeSelect = document.querySelector('select[name="college"]');
            const userTypeSelect = document.querySelector('select[name="user_type"]');

            departmentSelect.addEventListener('change', function() {
                if (this.value === 'N/A') {
                    collegeSelect.value = 'Not Applicable';
                    Array.from(collegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                        if (group.label !== 'Not Applicable') {
                            group.style.display = 'none';
                        }
                    });
                    userTypeSelect.value = 'UNIVERSITY_EMPLOYEE';
                } else {
                    Array.from(collegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                        if (group.label.includes(this.value)) {
                            group.style.display = '';
                        } else {
                            group.style.display = 'none';
                        }
                    });
                    const selectedCollege = collegeSelect.value;
                    const selectedGroup = Array.from(collegeSelect.getElementsByTagName('optgroup'))
                        .find(group => group.querySelector(`option[value="${selectedCollege}"]`));
                    
                    if (!selectedGroup || !selectedGroup.label.includes(this.value)) {
                        collegeSelect.value = '';
                    }
                }
            });
        }

        // Function to handle user type selection
        function handleUserTypeChange() {
            const userTypeSelect = document.querySelector('select[name="user_type"]');
            const departmentSelect = document.querySelector('select[name="department"]');
            const collegeSelect = document.querySelector('select[name="college"]');

            userTypeSelect.addEventListener('change', function() {
                if (this.value === 'UNIVERSITY_EMPLOYEE') {
                    departmentSelect.value = 'N/A';
                    collegeSelect.value = 'Not Applicable';
                    departmentSelect.disabled = true;
                    collegeSelect.disabled = true;
                    handleDepartmentChange();
                } else {
                    departmentSelect.disabled = false;
                    collegeSelect.disabled = false;
                    
                    // Remove N/A option for STUDENT and FACULTY
                    const naOption = departmentSelect.querySelector('option[value="N/A"]');
                    if (naOption) {
                        naOption.style.display = 'none';
                    }
                    
                    // If current selection is N/A, reset to empty
                    if (departmentSelect.value === 'N/A') {
                        departmentSelect.value = '';
                        collegeSelect.value = '';
                    }
                    
                    // Show only the college options for the selected department
                    if (departmentSelect.value) {
                        Array.from(collegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                            if (group.label.includes(departmentSelect.value)) {
                                group.style.display = '';
                            } else {
                                group.style.display = 'none';
                            }
                        });
                    }
                }
            });
        }

        // Function to handle edit modal user type selection
        function handleEditUserTypeChange() {
            const editUserTypeSelect = document.querySelector('#edit_user_type');
            const editDepartmentSelect = document.querySelector('#edit_department');
            const editCollegeSelect = document.querySelector('#edit_college');

            editUserTypeSelect.addEventListener('change', function() {
                if (this.value === 'UNIVERSITY_EMPLOYEE') {
                    editDepartmentSelect.value = 'N/A';
                    editCollegeSelect.value = 'Not Applicable';
                    editDepartmentSelect.disabled = true;
                    editCollegeSelect.disabled = true;
                    
                    // Hide all other college options
                    Array.from(editCollegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                        if (group.label !== 'Not Applicable') {
                            group.style.display = 'none';
                        }
                    });
                } else {
                    editDepartmentSelect.disabled = false;
                    editCollegeSelect.disabled = false;
                    
                    // Remove N/A option for STUDENT and FACULTY
                    const naOption = editDepartmentSelect.querySelector('option[value="N/A"]');
                    if (naOption) {
                        naOption.style.display = 'none';
                    }
                    
                    // If current selection is N/A, reset to empty
                    if (editDepartmentSelect.value === 'N/A') {
                        editDepartmentSelect.value = '';
                        editCollegeSelect.value = '';
                    }
                    
                    // Show only the college options for the selected department
                    if (editDepartmentSelect.value) {
                        Array.from(editCollegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                            if (group.label.includes(editDepartmentSelect.value)) {
                                group.style.display = '';
                            } else {
                                group.style.display = 'none';
                            }
                        });
                    }
                }
            });

            // Also handle department changes in edit modal
            editDepartmentSelect.addEventListener('change', function() {
                if (this.value === 'N/A') {
                    editCollegeSelect.value = 'Not Applicable';
                    Array.from(editCollegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                        if (group.label !== 'Not Applicable') {
                            group.style.display = 'none';
                        }
                    });
                } else {
                    // Show only the college options for the selected department
                    Array.from(editCollegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                        if (group.label.includes(this.value)) {
                            group.style.display = '';
                        } else {
                            group.style.display = 'none';
                        }
                    });
                    // Reset college selection if it's from a different department
                    const selectedCollege = editCollegeSelect.value;
                    const selectedGroup = Array.from(editCollegeSelect.getElementsByTagName('optgroup'))
                        .find(group => group.querySelector(`option[value="${selectedCollege}"]`));
                    
                    if (!selectedGroup || !selectedGroup.label.includes(this.value)) {
                        editCollegeSelect.value = '';
                    }
                }
            });
        }

        // Initialize the handlers when document is loaded
        document.addEventListener('DOMContentLoaded', function() {
            handleDepartmentChange();
            handleUserTypeChange();
            handleEditUserTypeChange();

            // Password toggle functionality
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('input[name="password"]');

            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // Toggle the icon
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });

            // Initial state check for edit modal
            const editUserTypeSelect = document.querySelector('#edit_user_type');
            const editDepartmentSelect = document.querySelector('#edit_department');
            const editCollegeSelect = document.querySelector('#edit_college');

            if (editUserTypeSelect) {
                if (editUserTypeSelect.value === 'UNIVERSITY_EMPLOYEE') {
                    editDepartmentSelect.disabled = true;
                    editCollegeSelect.disabled = true;
                } else {
                    // Hide N/A option for STUDENT and FACULTY
                    const naOption = editDepartmentSelect.querySelector('option[value="N/A"]');
                    if (naOption) {
                        naOption.style.display = 'none';
                    }
                    
                    // Show only the college options for the selected department
                    if (editDepartmentSelect.value) {
                        Array.from(editCollegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                            if (group.label.includes(editDepartmentSelect.value)) {
                                group.style.display = '';
                            } else {
                                group.style.display = 'none';
                            }
                        });
                    }
                }
            }
        });

        // Handle form submission
        $('#saveUser').on('click', function() {
            const saveButton = $(this);
            const buttonText = saveButton.find('.button-text');
            const spinner = saveButton.find('.spinner-border');

            // Show loading state
            buttonText.text('Creating User...');
            spinner.removeClass('d-none');
            saveButton.prop('disabled', true);

            // Get form data
            const formData = {
                first_name: $('input[name="first_name"]').val(),
                last_name: $('input[name="last_name"]').val(),
                email: $('input[name="email"]').val(),
                user_type: $('select[name="user_type"]').val(),
                department: $('select[name="department"]').val(),
                college: $('select[name="college"]').val(),
                password: $('input[name="password"]').val(),
                status: 1 // Set status to active by default
            };

            // Validate department and college
            if (!formData.department || formData.department === '') {
                // Reset button state
                buttonText.text('Create User');
                spinner.addClass('d-none');
                saveButton.prop('disabled', false);

                // Show error notification
                const notification = $('<div>')
                    .addClass('notification-snap-alert error')
                    .html('<i class="bi bi-exclamation-circle-fill"></i> Please select a department')
                    .appendTo('body');

                setTimeout(() => {
                    notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
                return;
            }

            if (!formData.college || formData.college === '') {
                // Reset button state
                buttonText.text('Create User');
                spinner.addClass('d-none');
                saveButton.prop('disabled', false);

                // Show error notification
                const notification = $('<div>')
                    .addClass('notification-snap-alert error')
                    .html('<i class="bi bi-exclamation-circle-fill"></i> Please select a college/course')
                    .appendTo('body');

                setTimeout(() => {
                    notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
                return;
            }

            // Send AJAX request
            $.ajax({
                url: 'process_user.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    try {
                        const res = typeof response === 'object' ? response : JSON.parse(response);
                        
                        // Show notification
                        const notification = $('<div>')
                            .addClass('notification-snap-alert ' + (res.success ? 'success' : 'error'))
                            .html('<i class="bi bi-' + (res.success ? 'check-circle-fill' : 'exclamation-circle-fill') + '"></i> ' + res.message)
                            .appendTo('body');

                        // Remove notification after 3 seconds
                        setTimeout(() => {
                            notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);

                        if (res.success) {
                            // Close modal and reload page
                            $('#newUserModal').modal('hide');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            // Reset button state on error
                            buttonText.text('Create User');
                            spinner.addClass('d-none');
                            saveButton.prop('disabled', false);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        // Reset button state on parsing error
                        buttonText.text('Create User');
                        spinner.addClass('d-none');
                        saveButton.prop('disabled', false);

                        // Show error notification
                        const notification = $('<div>')
                            .addClass('notification-snap-alert error')
                            .html('<i class="bi bi-exclamation-circle-fill"></i> Error processing server response')
                            .appendTo('body');

                        setTimeout(() => {
                            notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    // Reset button state
                    buttonText.text('Create User');
                    spinner.addClass('d-none');
                    saveButton.prop('disabled', false);

                    // Show error notification
                    const notification = $('<div>')
                        .addClass('notification-snap-alert error')
                        .html('<i class="bi bi-exclamation-circle-fill"></i> Error creating user: ' + (error || 'Unknown error'))
                        .appendTo('body');

                    setTimeout(() => {
                        notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                        setTimeout(() => {
                            notification.remove();
                        }, 300);
                    }, 3000);
                }
            });
        });

        // Initialize DataTables with filters
        $(document).ready(function() {
            // Initialize Users DataTable if not already initialized
            if (!$.fn.DataTable.isDataTable('#usersTable')) {
                const usersTable = $('#usersTable').DataTable({
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
                                    }
                                },
                                {
                                    extend: 'print',
                                    text: 'Print',
                                    className: 'btn btn-secondary btn-sm',
                                    exportOptions: {
                                        columns: ':visible:not(:last-child)'
                                    }
                                }
                            ]
                        }
                    ],
                    order: [[0, 'desc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: {
                        lengthMenu: "Show _MENU_ users per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ users",
                        infoEmpty: "No users available",
                        infoFiltered: "(filtered from _MAX_ total users)",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });

                // Custom filtering function for Users table
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'usersTable') {
                        return true;
                    }

                    const userTypeFilter = $('#userTypeFilter').val();
                    const statusFilter = $('#statusFilter').val();
                    const departmentFilter = $('#departmentFilter').val();
                    
                    const userType = data[3]; // User Type column
                    const status = data[6]; // Status column
                    const department = data[4]; // Department column
                    
                    const userTypeMatch = userTypeFilter === 'all' || userType.toLowerCase().includes(userTypeFilter.toLowerCase());
                    const statusMatch = statusFilter === 'all' || (statusFilter === '1' && status.includes('Active')) || (statusFilter === '0' && status.includes('Inactive'));
                    const departmentMatch = departmentFilter === 'all' || department === departmentFilter;
                    
                    return userTypeMatch && statusMatch && departmentMatch;
                });

                // Apply filters when they change for Users table
                $('#userTypeFilter, #statusFilter, #departmentFilter').on('change', function() {
                    usersTable.draw();
                });

                // Clear Users filters
                $('#clearFilters').on('click', function() {
                    $('#userTypeFilter, #statusFilter, #departmentFilter').val('all');
                    $('#searchFilter').val('');
                    usersTable.search('').draw();
                });

                // Users search functionality
                $('#searchFilter').on('keyup', function() {
                    usersTable.search(this.value).draw();
                });
            }

            // Initialize Staff DataTable if not already initialized
            if (!$.fn.DataTable.isDataTable('#staffTable')) {
                const staffTable = $('#staffTable').DataTable({
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
                                    }
                                },
                                {
                                    extend: 'print',
                                    text: 'Print',
                                    className: 'btn btn-secondary btn-sm',
                                    exportOptions: {
                                        columns: ':visible:not(:last-child)'
                                    }
                                }
                            ]
                        }
                    ],
                    order: [[0, 'desc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: {
                        lengthMenu: "Show _MENU_ staff per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ staff",
                        infoEmpty: "No staff available",
                        infoFiltered: "(filtered from _MAX_ total staff)",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    }
                });

                // Custom filtering function for Staff table
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'staffTable') {
                        return true;
                    }

                    const roleFilter = $('#roleFilter').val();
                    const availabilityFilter = $('#availabilityFilter').val();
                    const statusFilter = $('#staffStatusFilter').val();
                    
                    const role = data[3]; // Role column
                    const availability = data[4]; // Availability column
                    const status = data[5]; // Status column
                    
                    const roleMatch = roleFilter === 'all' || role.toLowerCase().includes(roleFilter.toLowerCase());
                    const availabilityMatch = availabilityFilter === 'all' || availability.toLowerCase().includes(availabilityFilter.toLowerCase());
                    const statusMatch = statusFilter === 'all' || status.toLowerCase().includes(statusFilter.toLowerCase());
                    
                    return roleMatch && availabilityMatch && statusMatch;
                });

                // Apply filters when they change for Staff table
                $('#roleFilter, #availabilityFilter, #staffStatusFilter').on('change', function() {
                    staffTable.draw();
                });

                // Clear Staff filters
                $('#clearStaffFilters').on('click', function() {
                    $('#roleFilter, #availabilityFilter, #staffStatusFilter').val('all');
                    $('#staffSearchFilter').val('');
                    staffTable.search('').draw();
                });

                // Staff search functionality
                $('#staffSearchFilter').on('keyup', function() {
                    staffTable.search(this.value).draw();
                });
            }
        });

        // Handle new staff creation
        $('#saveNewStaff').on('click', function() {
            const form = $('#newStaffForm');
            const saveButton = $(this);
            const buttonText = saveButton.find('.button-text');
            const spinner = saveButton.find('.spinner-border');
            const passwordInput = $('#newStaffPassword');

            // Reset previous validation states
            form.find('.is-invalid').removeClass('is-invalid');

            // Validate form
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }

            // Validate password
            const password = passwordInput.val();
            let passwordErrors = [];

            if (password.length < 8) {
                passwordErrors.push('Password must be at least 8 characters long');
            }
            if (!/[A-Z]/.test(password)) {
                passwordErrors.push('Password must contain at least one uppercase letter');
            }
            if (!/[0-9]/.test(password)) {
                passwordErrors.push('Password must contain at least one number');
            }

            if (passwordErrors.length > 0) {
                passwordInput.addClass('is-invalid');
                const notification = $('<div>')
                    .addClass('notification-snap-alert error')
                    .html('<i class="bi bi-exclamation-circle-fill"></i><ul class="mb-0">' + 
                        passwordErrors.map(error => `<li>${error}</li>`).join('') + 
                        '</ul>')
                    .appendTo('body');

                setTimeout(() => {
                    notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
                return;
            }

            // Show loading state
            buttonText.text('Creating Staff...');
            spinner.removeClass('d-none');
            saveButton.prop('disabled', true);

            // Get form data
            const formData = {
                name: form.find('input[name="name"]').val(),
                email: form.find('input[name="email"]').val(),
                role: form.find('select[name="role"]').val(),
                password: password,
                availability: 'available',
                status: 'active'
            };

            // Send AJAX request
            $.ajax({
                url: 'process_staff.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    try {
                        const res = typeof response === 'object' ? response : JSON.parse(response);
                        
                        // Show notification
                        const notification = $('<div>')
                            .addClass('notification-snap-alert ' + (res.success ? 'success' : 'error'))
                            .html('<i class="bi bi-' + (res.success ? 'check-circle-fill' : 'exclamation-circle-fill') + '"></i> ' + res.message)
                            .appendTo('body');

                        setTimeout(() => {
                            notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);

                        if (res.success) {
                            // Reset form
                            form[0].reset();
                            // Close modal
                            $('#newStaffModal').modal('hide');
                            // Reload page after a short delay
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            // Reset button state on error
                            buttonText.text('Create Staff');
                            spinner.addClass('d-none');
                            saveButton.prop('disabled', false);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        buttonText.text('Create Staff');
                        spinner.addClass('d-none');
                        saveButton.prop('disabled', false);

                        const notification = $('<div>')
                            .addClass('notification-snap-alert error')
                            .html('<i class="bi bi-exclamation-circle-fill"></i> Error processing server response')
                            .appendTo('body');

                        setTimeout(() => {
                            notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    buttonText.text('Create Staff');
                    spinner.addClass('d-none');
                    saveButton.prop('disabled', false);

                    const notification = $('<div>')
                        .addClass('notification-snap-alert error')
                        .html('<i class="bi bi-exclamation-circle-fill"></i> Error creating staff: ' + (error || 'Unknown error'))
                        .appendTo('body');

                    setTimeout(() => {
                        notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                        setTimeout(() => {
                            notification.remove();
                        }, 300);
                    }, 3000);
                }
            });
        });

        // New staff password toggle functionality
        $('#toggleNewStaffPassword').on('click', function() {
            const passwordInput = $('#newStaffPassword');
            const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
            passwordInput.attr('type', type);
            
            // Toggle the icon
            const icon = $(this).find('i');
            if (type === 'password') {
                icon.removeClass('bi-eye').addClass('bi-eye-slash');
            } else {
                icon.removeClass('bi-eye-slash').addClass('bi-eye');
            }
        });

        // Reset form when modal is closed
        $('#newStaffModal').on('hidden.bs.modal', function() {
            const form = $('#newStaffForm');
            form[0].reset();
            form.find('.is-invalid').removeClass('is-invalid');
            const saveButton = $('#saveNewStaff');
            saveButton.find('.button-text').text('Create Staff');
            saveButton.find('.spinner-border').addClass('d-none');
            saveButton.prop('disabled', false);
        });

        // Handle edit staff button click
        $(document).on('click', '.edit-staff', function() {
            const staffId = $(this).data('id');
            
            // Show loading state
            const editButton = $(this);
            editButton.prop('disabled', true);
            editButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            
            // Fetch staff data
            $.ajax({
                url: 'process_staff.php',
                type: 'GET',
                data: { staff_id: staffId },
                success: function(response) {
                    try {
                        const staff = typeof response === 'object' ? response : JSON.parse(response);
                        
                        if (staff.success) {
                            // Populate form fields
                            $('#edit_staff_id').val(staff.data.staff_id);
                            $('#edit_staff_name').val(staff.data.name);
                            $('#edit_staff_email').val(staff.data.email);
                            $('#edit_staff_role').val(staff.data.role);
                            $('#edit_staff_availability').val(staff.data.availability);
                            $('#edit_staff_status').val(staff.data.status);
                            
                            // Show modal
                            $('#editStaffModal').modal('show');
                        } else {
                            // Show error notification
                            const notification = $('<div>')
                                .addClass('notification-snap-alert error')
                                .html('<i class="bi bi-exclamation-circle-fill"></i> ' + staff.message)
                                .appendTo('body');

                            setTimeout(() => {
                                notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                                setTimeout(() => {
                                    notification.remove();
                                }, 300);
                            }, 3000);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        // Show error notification
                        const notification = $('<div>')
                            .addClass('notification-snap-alert error')
                            .html('<i class="bi bi-exclamation-circle-fill"></i> Error processing server response')
                            .appendTo('body');

                        setTimeout(() => {
                            notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    // Show error notification
                    const notification = $('<div>')
                        .addClass('notification-snap-alert error')
                        .html('<i class="bi bi-exclamation-circle-fill"></i> Error fetching staff data: ' + (error || 'Unknown error'))
                        .appendTo('body');

                    setTimeout(() => {
                        notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                        setTimeout(() => {
                            notification.remove();
                        }, 300);
                    }, 3000);
                },
                complete: function() {
                    // Reset button state
                    editButton.prop('disabled', false);
                    editButton.html('<i class="bi bi-pencil"></i>');
                }
            });
        });

        // Handle update staff
        $('#updateStaff').on('click', function() {
            const updateButton = $(this);
            const buttonText = updateButton.find('.button-text');
            const spinner = updateButton.find('.spinner-border');

            // Show loading state
            buttonText.text('Updating...');
            spinner.removeClass('d-none');
            updateButton.prop('disabled', true);

            // Get form data
            const formData = {
                staff_id: $('#edit_staff_id').val(),
                name: $('#edit_staff_name').val(),
                email: $('#edit_staff_email').val(),
                role: $('#edit_staff_role').val(),
                availability: $('#edit_staff_availability').val(),
                status: $('#edit_staff_status').val()
            };

            // Send AJAX request
            $.ajax({
                url: 'process_staff.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    try {
                        const res = typeof response === 'object' ? response : JSON.parse(response);
                        
                        // Show notification
                        const notification = $('<div>')
                            .addClass('notification-snap-alert ' + (res.success ? 'success' : 'error'))
                            .html('<i class="bi bi-' + (res.success ? 'check-circle-fill' : 'exclamation-circle-fill') + '"></i> ' + res.message)
                            .appendTo('body');

                        setTimeout(() => {
                            notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);

                        if (res.success) {
                            $('#editStaffModal').modal('hide');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            buttonText.text('Update Staff');
                            spinner.addClass('d-none');
                            updateButton.prop('disabled', false);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        buttonText.text('Update Staff');
                        spinner.addClass('d-none');
                        updateButton.prop('disabled', false);

                        const notification = $('<div>')
                            .addClass('notification-snap-alert error')
                            .html('<i class="bi bi-exclamation-circle-fill"></i> Error processing server response')
                            .appendTo('body');

                        setTimeout(() => {
                            notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    buttonText.text('Update Staff');
                    spinner.addClass('d-none');
                    updateButton.prop('disabled', false);

                    const notification = $('<div>')
                        .addClass('notification-snap-alert error')
                        .html('<i class="bi bi-exclamation-circle-fill"></i> Error updating staff: ' + (error || 'Unknown error'))
                        .appendTo('body');

                    setTimeout(() => {
                        notification.css('animation', 'fadeOut 0.3s ease-out forwards');
                        setTimeout(() => {
                            notification.remove();
                        }, 300);
                    }, 3000);
                }
            });
        });

        // Staff password toggle functionality
        $('#toggleStaffPassword').on('click', function() {
            const passwordInput = $('input[name="password"]');
            const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
            passwordInput.attr('type', type);
            
            // Toggle the icon
            const icon = $(this).find('i');
            if (type === 'password') {
                icon.removeClass('bi-eye').addClass('bi-eye-slash');
            } else {
                icon.removeClass('bi-eye-slash').addClass('bi-eye');
            }
        });

        // Reset form when modal is closed
        $('#newStaffModal').on('hidden.bs.modal', function() {
            const form = $('#newStaffForm');
            form[0].reset();
            form.find('.is-invalid').removeClass('is-invalid');
            const saveButton = $('#saveNewStaff');
            saveButton.find('.button-text').text('Create Staff');
            saveButton.find('.spinner-border').addClass('d-none');
            saveButton.prop('disabled', false);
        });

        // Edit User Modal
        $(document).on('click', '.edit-user-btn', function() {
            const userId = $(this).data('id');
            const $btn = $(this);
            const originalText = $btn.html();
            
            // Show loading state
            $btn.html('<i class="fas fa-spinner fa-spin"></i>');
            $btn.prop('disabled', true);
            
            // Fetch user data
            $.ajax({
                url: 'process_user.php',
                method: 'GET',
                data: { id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const user = response.data;
                        
                        // Populate the form
                        $('#edit_user_id').val(user.user_id);
                        $('#edit_first_name').val(user.first_name);
                        $('#edit_last_name').val(user.last_name);
                        $('#edit_user_email').val(user.email);
                        $('#edit_department').val(user.department);
                        $('#edit_college_course').val(user.college);
                        $('#edit_user_type').val(user.user_type);
                        $('#edit_status').val(user.status);
                        
                        // Handle UNIVERSITY_EMPLOYEE type
                        if (user.user_type === 'UNIVERSITY_EMPLOYEE') {
                            $('#edit_department').prop('disabled', false);
                            $('#edit_college_course').prop('disabled', true);
                            $('#edit_college_course').val('');
                        } else {
                            $('#edit_department').prop('disabled', true);
                            $('#edit_college_course').prop('disabled', false);
                            $('#edit_department').val('');
                        }
                        
                        // Show the modal
                        $('#editUserModal').modal('show');
                    } else {
                        showNotification('error', 'Error', response.message || 'Failed to load user data');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('error', 'Error', 'Failed to load user data. Please try again.');
                    console.error('Error:', error);
                },
                complete: function() {
                    // Reset button state
                    $btn.html(originalText);
                    $btn.prop('disabled', false);
                }
            });
        });

        // Handle edit user form submission
        $('#editUserForm').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.html();
            
            // Show loading state
            $submitBtn.html('<i class="fas fa-spinner fa-spin"></i>');
            $submitBtn.prop('disabled', true);
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            $.ajax({
                url: 'process_user.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('success', 'Success', 'User updated successfully');
                        $('#editUserModal').modal('hide');
                        // Reload the page to show updated data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification('error', 'Error', response.message || 'Failed to update user');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('error', 'Error', 'Failed to update user. Please try again.');
                    console.error('Error:', error);
                },
                complete: function() {
                    // Reset button state
                    $submitBtn.html(originalText);
                    $submitBtn.prop('disabled', false);
                }
            });
        });
    </script>
</body>
</html> 
