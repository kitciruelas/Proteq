<?php 
// Remove session_start() from here since it's called in the parent file
// Rest of the sidebar code
 // Start the session to access logged-in user data
// Get the current script filename and path
$currentScript = $_SERVER['PHP_SELF'];
$currentPath = dirname($currentScript);
$currentPage = basename($currentScript);

// Get admin details from session, provide defaults if not set
$adminName = isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin';
$adminIdentifier = isset($_SESSION['admin_email']) ? htmlspecialchars($_SESSION['admin_email']) : 'Logged In'; // Or use a role if stored in session

?>
<!-- Sidebar -->
<div class="bg-light border-end" id="sidebar-wrapper">
    <div class="sidebar-heading border-bottom bg-light d-flex align-items-center gap-2 p-4">
        <span class="custom-logo d-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width: 30px; height: 30px; font-size: 1.2rem;">
            <i class="bi bi-triangle-fill"></i>
        </span>
        <span class="fw-bold fs-5 text-dark sidebar-text">PROTEQ</span> <!-- Added class -->
    </div>
    <div class="list-group list-group-flush">
        <a href="/Proteq/admin/dashboard.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Dashboard">
            <i class="bi bi-speedometer2 me-2"></i><span class="sidebar-text">Dashboard</span>
        </a>
        <a href="/Proteq/admin/alerts/Alerts.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'Alerts.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Alerts">
            <i class="bi bi-bell-fill me-2"></i><span class="sidebar-text">Alerts</span>
        </a>
        <a href="/Proteq/admin/welfarecheck/emergency_management.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'emergency_management.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Emergency Management">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><span class="sidebar-text">Emergency Management</span>
        </a>
        <a href="/Proteq/admin/resources/Resource_Evacuation_Mapping.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'Resource_Evacuation_Mapping.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Evacuation Map">
            <i class="bi bi-map-fill me-2"></i><span class="sidebar-text">Evacuation Map</span>
        </a>
        <a href="/Proteq/admin/maps/map_view_all_updated.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'map_view_all_updated.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Incidents Map">
            <i class="bi bi-geo-alt-fill me-2"></i><span class="sidebar-text">Incidents Map</span>
        </a>
        <a href="/Proteq/admin/users/Users.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'Users.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Users">
            <i class="bi bi-people-fill me-2"></i><span class="sidebar-text">Users</span>
        </a>
        <a href="/Proteq/admin/safety_protocol_management/Safety_Protocol_Management.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'Safety_Protocol_Management.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Safety Protocol Management">
            <i class="bi bi-shield-lock-fill me-2"></i><span class="sidebar-text">Safety Protocol</span>
        </a>
        <a href="#" class="list-group-item list-group-item-action list-group-item-light p-3" data-bs-toggle="tooltip" title="Settings">
            <i class="bi bi-gear-fill me-2"></i><span class="sidebar-text">Settings</span>
        </a>
    </div>
    <div class="mt-auto p-3 border-top">
         <div class="d-flex align-items-center mb-2">
            <?php
            // Get the first letter of the admin's first name
            $firstLetter = !empty($adminName) ? strtoupper(substr($adminName, 0, 1)) : 'A';
            ?>
            <div class="rounded-circle me-2 sidebar-text d-flex align-items-center justify-content-center bg-primary text-white" style="width: 30px; height: 30px; font-size: 1rem;">
                <?php echo $firstLetter; ?>
            </div>
            <div class="sidebar-text">
                <small class="d-block fw-bold"><?php echo $adminName; ?></small>
                <small class="text-muted"><?php echo $adminIdentifier; ?></small>
            </div>
        </div>
        <a href="#" onclick="confirmLogout()" class="list-group-item list-group-item-action list-group-item-light p-3">
            <i class="bi bi-box-arrow-left me-2"></i><span class="sidebar-text">Log out</span>
        </a>
    </div>
</div>
<!-- /#sidebar-wrapper -->

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-box-arrow-left text-primary" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-0">Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="performLogout()">Yes, Logout</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmLogout() {
    var logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
    logoutModal.show();
}

function performLogout() {
    const logoutBtn = document.querySelector('#logoutModal .btn-primary');
    const originalText = logoutBtn.innerHTML;
    logoutBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging out...';
    logoutBtn.disabled = true;

    window.location.href = '/Proteq/admin/auth/admin_logout.php';
}

$(document).ready(function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>