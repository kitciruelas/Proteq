<?php
// Get the current page title from the URL
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_title = $current_page === 'Dashboard' ? 'Home' : ucwords(str_replace('_', ' ', $current_page));
?>
<nav class="navbar navbar-expand-lg shadow-sm sticky-top py-2 py-md-3 border-bottom bg-white p-4">
    <div class="container-fluid px-2 px-md-3">
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0 d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Page Title and Date -->
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <h2 class="h4 h3-md mb-0 text-truncate fw-semibold"><?php echo $page_title; ?></h2>
        </div>

        <!-- Right Side Elements -->
        <div class="ms-auto d-flex align-items-center gap-2">
            <!-- Notifications Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light rounded-circle d-flex align-items-center justify-content-center position-relative notification-btn" 
                        style="width: 38px; height: 38px;" 
                        type="button" 
                        data-bs-toggle="dropdown"
                        data-bs-toggle="tooltip"
                        title="Notifications">
                    <i class="bi bi-bell-fill"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        2
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-sm notification-dropdown">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <h6 class="mb-0 fw-semibold">Notifications</h6>
                        <span class="badge bg-primary rounded-pill">2 new</span>
                    </div>
                    <div class="notification-list">
                        <a class="dropdown-item py-3 notification-item" href="#">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="notification-icon bg-warning-subtle text-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0 fw-medium">New incident reported nearby</p>
                                    <small class="text-muted">5 minutes ago</small>
                                </div>
                            </div>
                        </a>
                        <a class="dropdown-item py-3 notification-item" href="#">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="notification-icon bg-success-subtle text-success">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0 fw-medium">Safety protocol updated</p>
                                    <small class="text-muted">1 hour ago</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center py-2 text-primary fw-medium" href="#">
                        View all notifications
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- User Profile -->
            <div class="dropdown">
              
            </div>
        </div>
    </div>
</nav>

<style>
/* Enhanced custom styles */
.dropdown-menu {
    min-width: 300px;
    border: none;
    border-radius: 0.5rem;
}

.notification-dropdown {
    padding: 0;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar img {
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.notification-btn:hover {
    background-color: #e9ecef;
}

@media (min-width: 992px) {
    .h3-md {
        font-size: 1.75rem;
    }
}

/* Custom scrollbar for notification list */
.notification-list::-webkit-scrollbar {
    width: 6px;
}

.notification-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.notification-list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>