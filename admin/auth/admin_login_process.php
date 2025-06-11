<?php
session_start(); // Start the session to store login status and messages
require_once '../../includes/db.php'; // Adjust path as needed to include your database connection

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Email and password are required.";
        header("Location: admin-login.php");
        exit;
    }

    // Prepare SQL statement to prevent SQL injection
    $sql = "SELECT admin_id, name, email, password, status FROM admin WHERE email = ? LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        // Check if admin exists
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($admin_id, $name, $db_email, $hashed_password, $status);
            if ($stmt->fetch()) {
                // Verify password and check status
                if (password_verify($password, $hashed_password)) {
                    if ($status === 'active') {
                        // Password is correct and account is active, start session
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $admin_id;
                        $_SESSION['admin_name'] = $name;
                        $_SESSION['admin_email'] = $db_email;

                        // Clear any previous login errors
                        unset($_SESSION['login_error']);
                        
                        // Set success message and redirect back to login page first
                        $_SESSION['login_success'] = "Welcome back, " . htmlspecialchars($name) . "!";
                        
                        // Add a small delay to show the loading state
                        usleep(500000); // 0.5 second delay
                        
                        header("Location: admin-login.php");
                        exit;
                    } else {
                        // Account is inactive
                        $_SESSION['login_error'] = "Your account is inactive. Please contact support.";
                        header("Location: admin-login.php");
                        exit;
                    }
                } else {
                    // Password is not valid
                    $_SESSION['login_error'] = "Invalid email or password.";
                    header("Location: admin-login.php");
                    exit;
                }
            }
        } else {
            // No account found with that email
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: admin-login.php");
            exit;
        }

        $stmt->close();
    } else {
        // Error preparing statement
        $_SESSION['login_error'] = "Database error. Please try again later.";
        // Log the actual error for debugging: error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        header("Location: admin-login.php");
        exit;
    }

    $conn->close();
} else {
    // If not a POST request, redirect to login page
    header("Location: admin-login.php");
    exit;
}
?>