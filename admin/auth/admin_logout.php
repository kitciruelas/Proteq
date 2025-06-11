<?php
// Start the session
session_start();

try {
    // Store the admin name before clearing session for the success message
    $admin_name = $_SESSION['admin_name'] ?? '';

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Start a new session to store the success message
    session_start();
    $_SESSION['login_success'] = "You have been successfully logged out. Goodbye" . ($admin_name ? ", " . htmlspecialchars($admin_name) : "") . "!";

    // Add a small delay to show the loading state
    usleep(500000); // 0.5 second delay

    // Redirect to the admin login page
    header("Location: admin-login.php");
    exit;
} catch (Exception $e) {
    // Log the error
    error_log("Logout error: " . $e->getMessage());
    
    // Start a new session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set error message
    $_SESSION['login_error'] = "An error occurred during logout. Please try again.";
    
    // Add a small delay to show the loading state
    usleep(500000); // 0.5 second delay
    
    // Redirect to login page
    header("Location: admin-login.php");
    exit;
}
?>