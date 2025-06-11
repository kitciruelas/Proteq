document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.querySelector('.navbar-toggler');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    function toggleSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        }
    }

    if (toggleButton && sidebar && overlay) {
        // Toggle button click
        toggleButton.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });

        // Overlay click
        overlay.addEventListener('click', function() {
            toggleSidebar();
        });

        // Close on window resize if larger than tablet breakpoint
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991.98 && sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });
    }

    // Initialize dropdowns
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        new bootstrap.Dropdown(dropdown);
    });

    // Handle active states in sidebar
    const sidebarLinks = sidebar.querySelectorAll('.nav-link');
    sidebarLinks.forEach(link => {
        if (link.href === window.location.href) {
            link.classList.add('active');
        }
    });
});
