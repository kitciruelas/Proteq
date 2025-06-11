<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../login.php");
    exit();
}
?>
<!-- Add this to your sidebar container -->
<div class="sidebar-overlay"></div>
<div class="sidebar bg-white shadow-sm">
   

    <!-- User Profile Section -->
    <div class="p-4 border-bottom user-profile-section">
        <div class="d-flex align-items-center gap-3 profile-dropdown" data-bs-toggle="dropdown" role="button">
            <div class="position-relative">
                <?php if (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])): ?>
                    <img src="<?php echo $_SESSION['profile_image']; ?>" 
                         alt="Profile" 
                         class="rounded-circle profile-image"
                         style="width: 48px; height: 48px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <?php else: ?>
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center profile-image"
                         style="width: 48px; height: 48px; font-size: 1.3rem; border: 2px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <?php echo isset($_SESSION['first_name']) ? strtoupper(substr($_SESSION['first_name'], 0, 1)) : 'U'; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-grow-1">
                <h6 class="mb-1 text-truncate fw-semibold" style="color: #2c3e50;"><?php echo isset($_SESSION['first_name']) && isset($_SESSION['last_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'User'; ?></h6>
                <small class="text-muted d-flex align-items-center gap-1 user-type">
                    <i class="bi bi-person-badge me-1"></i>
                    <span><?php echo isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'User'; ?></span>
                </small>
            </div>
            <i class="bi bi-chevron-down text-muted"></i>
        </div>
        <div class="dropdown-menu dropdown-menu-end shadow-sm">
            <a class="dropdown-item py-2" href="#">
                <i class="bi bi-person me-2"></i>Profile
            </a>
            <a class="dropdown-item py-2" href="#">
                <i class="bi bi-gear me-2"></i>Settings
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-danger py-2" href="#" onclick="confirmLogout()">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="p-3">
        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a href="Dashboard.php" class="nav-link d-flex align-items-center gap-2 <?php echo basename($_SERVER['PHP_SELF']) === 'Dashboard.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Go to Dashboard">
                    <i class="bi bi-house-door"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Incident_report.php" class="nav-link d-flex align-items-center gap-2 <?php echo basename($_SERVER['PHP_SELF']) === 'Incident_report.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Report an Incident">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Incident Report</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="evacuation_centers.php" class="nav-link d-flex align-items-center gap-2 <?php echo basename($_SERVER['PHP_SELF']) === 'evacuation_centers.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="View Evacuation Centers">
                    <i class="bi bi-building"></i>
                    <span>Evacuation Centers</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="WelfareCheck.php" class="nav-link d-flex align-items-center gap-2 <?php echo basename($_SERVER['PHP_SELF']) === 'WelfareCheck.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="Check Welfare Status">
                    <i class="bi bi-people"></i>
                    <span>Welfare Check</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="SafetyProtocol.php" class="nav-link d-flex align-items-center gap-2 <?php echo basename($_SERVER['PHP_SELF']) === 'SafetyProtocol.php' ? 'active' : ''; ?>" data-bs-toggle="tooltip" title="View Safety Protocols">
                    <i class="bi bi-shield-check"></i>
                    <span>Safety Protocol</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Logout Section -->
    <div class="mt-auto p-3 border-top">
        <a href="#" onclick="confirmLogout()" class="nav-link d-flex align-items-center gap-2 text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Log Out</span>
        </a>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-box-arrow-right text-primary" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-0">Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="logoutBtn" onclick="performLogout()">Yes, Logout</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmLogout() {
    // Show the modal instead of using confirm()
    var logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
    logoutModal.show();
}

function performLogout() {
    // Get the logout button
    const logoutBtn = document.getElementById('logoutBtn');
    
    // Store original content
    const originalContent = logoutBtn.innerHTML;
    
    // Add loading state
    logoutBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Logging out...
    `;
    logoutBtn.disabled = true;
    
    // Add a small delay to show the loading state
    setTimeout(() => {
        // Redirect to logout page
        window.location.href = '../auth/logout.php';
    }, 500);
}

// Initialize tooltips
$(document).ready(function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<style>
/* Modern and user-friendly sidebar styles */
.sidebar {
    height: 100vh;
    width: 280px;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1045;
    transition: transform 0.3s ease-in-out;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    background: linear-gradient(to bottom, #ffffff, #f8f9fa);
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
    backdrop-filter: blur(2px);
}

.sidebar .nav-link {
    padding: 0.85rem 1.25rem;
    border-radius: 0.75rem;
    transition: all 0.2s ease-in-out;
    color: #495057;
    font-weight: 500;
    margin: 0.25rem 0;
}

.sidebar .nav-link:hover {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    transform: translateX(5px);
}

.sidebar .nav-link.active {
    background: linear-gradient(45deg, #0d6efd, #0a58ca);
    color: white;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

.sidebar .nav-link.active .badge {
    background-color: white !important;
    color: #0d6efd;
    font-weight: 600;
}

.sidebar .nav-link i {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.sidebar .nav-link span {
    font-size: 0.95rem;
}

/* User profile section improvements */
.sidebar .border-bottom {
    border-color: rgba(0, 0, 0, 0.08) !important;
}

.sidebar .rounded-circle {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Badge styling */
.badge {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 600;
}

/* Logout section styling */
.sidebar .border-top {
    border-color: rgba(0, 0, 0, 0.08) !important;
}

.sidebar .text-danger {
    color: #dc3545 !important;
}

.sidebar .text-danger:hover {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

@media (max-width: 991.98px) {
    .sidebar {
        transform: translateX(-100%);
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .sidebar-overlay.show {
        display: block;
        opacity: 1;
    }
}

/* Scrollbar styling */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background-color: rgba(0, 0, 0, 0.3);
}

/* Enhanced User Profile Section */
.user-profile-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 1px solid rgba(0,0,0,0.05) !important;
    padding: 1.25rem !important;
    position: relative;
}

.profile-dropdown {
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.profile-dropdown:hover {
    background-color: rgba(0,0,0,0.03);
}

.profile-dropdown .bi-chevron-down {
    transition: transform 0.2s ease;
}

.profile-dropdown[aria-expanded="true"] .bi-chevron-down {
    transform: rotate(180deg);
}

.profile-image {
    transition: transform 0.2s ease;
}

.profile-image:hover {
    transform: scale(1.05);
}

.user-type {
    font-size: 0.85rem;
    color: #6c757d;
    background: rgba(0,0,0,0.03);
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
}

.dropdown-menu {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-top: 0.5rem;
}

.dropdown-item {
    border-radius: 0.5rem;
    margin: 0.25rem 0.5rem;
    width: calc(100% - 1rem);
}

.dropdown-item:hover {
    background-color: rgba(13, 110, 253, 0.1);
}

.dropdown-item.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.1);
}
</style>