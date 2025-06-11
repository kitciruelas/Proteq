<?php 
// Remove session_start() from here since it's called in the parent file
// Rest of the sidebar code
// Get the current script filename and path
$currentScript = $_SERVER['PHP_SELF'];
$currentPath = dirname($currentScript);
$currentPage = basename($currentScript);

// Get staff details from session
$staffName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Staff';
$staffEmail = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Logged In';
$staffRole = isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Staff';

?>
<!-- Sidebar -->
<div class="bg-light border-end" id="sidebar-wrapper">
    <div class="sidebar-heading border-bottom bg-light d-flex align-items-center gap-2 p-4">
        <span class="custom-logo d-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width: 30px; height: 30px; font-size: 1.2rem;">
            <i class="bi bi-triangle-fill"></i>
        </span>
        <span class="fw-bold fs-5 text-dark sidebar-text">PROTEQ</span>
    </div>
    <div class="list-group list-group-flush">
        <a href="/Proteq/Staff/dashboard.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Dashboard">
            <i class="bi bi-speedometer2 me-2"></i><span class="sidebar-text">Dashboard</span>
        </a>
        <a href="/Proteq/Staff/incidents/incidents.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'incidents.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Incidents">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><span class="sidebar-text">Incidents</span>
        </a>
        <a href="/Proteq/Staff/maps/map_view.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'map_view.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Incidents Map">
            <i class="bi bi-geo-alt-fill me-2"></i><span class="sidebar-text">Incidents Map</span>
        </a>
        <a href="/Proteq/Staff/profile/profile.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Profile">
            <i class="bi bi-person-fill me-2"></i><span class="sidebar-text">Profile</span>
        </a>
    </div>
    <div class="mt-auto p-3 border-top">
         <div class="d-flex align-items-center mb-2">
            <?php
            // Get the first letter of the staff's name
            $firstLetter = !empty($staffName) ? strtoupper(substr($staffName, 0, 1)) : 'S';
            ?>
            <div class="rounded-circle me-2 sidebar-text d-flex align-items-center justify-content-center bg-primary text-white" style="width: 30px; height: 30px; font-size: 1rem;">
                <?php echo $firstLetter; ?>
            </div>
            <div class="sidebar-text">
                <small class="d-block fw-bold"><?php echo $staffName; ?></small>
                <small class="text-muted"><?php echo $staffRole; ?></small>
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

    window.location.href = '/Proteq/auth/logout.php';
}

$(document).ready(function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>