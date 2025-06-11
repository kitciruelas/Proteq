document.addEventListener("DOMContentLoaded", event => {
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    const sidebarToggleIcon = sidebarToggle ? sidebarToggle.querySelector('i') : null;
    const wrapper = document.getElementById('wrapper'); // Get the main wrapper

    // Function to update the toggle button icon based on wrapper class
    const updateToggleIcon = () => {
        if (!sidebarToggleIcon || !wrapper) return;
        // Use a different icon pair if you like
        if (wrapper.classList.contains('sidebar-icon-only')) {
            // Sidebar is icon-only - show icon to expand (e.g., list)
            sidebarToggleIcon.classList.remove('bi-x'); // Or your 'collapse' icon
            sidebarToggleIcon.classList.add('bi-list'); // Or your 'expand' icon
        } else {
            // Sidebar is full - show icon to collapse (e.g., x)
            sidebarToggleIcon.classList.remove('bi-list');
            sidebarToggleIcon.classList.add('bi-x');
        }
    };

    if (sidebarToggle && sidebarToggleIcon && wrapper) {
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            // Toggle the new class on the wrapper element
            wrapper.classList.toggle('sidebar-icon-only');
            // Update localStorage with the new state/class
            localStorage.setItem('sb|sidebar-toggle-icon-only', wrapper.classList.contains('sidebar-icon-only'));
            updateToggleIcon(); // Update the button icon
        });
    }

    // Restore sidebar state on page load using the new class/key
    if (localStorage.getItem('sb|sidebar-toggle-icon-only') === 'true' && wrapper) {
         wrapper.classList.add('sidebar-icon-only');
    }

    updateToggleIcon(); // Set the correct initial button icon
});