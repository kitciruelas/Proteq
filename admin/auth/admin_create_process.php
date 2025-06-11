<?php
session_start(); // Start session for feedback messages

// --- Security Check (Highly Recommended) ---
// Ensure only logged-in admins can access this script
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     // Redirect to admin login or show an error
//     header("Location: ../auth/admin-login.php");
//     exit;
// }
// --- End Security Check ---


require_once '../../includes/db.php'; // Adjust path to your DB connection file

// Check if the form was submitted using POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Get Data from Form ---
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    // Optional: Get role and status if you add fields for them in the form
    // $role = trim($_POST['role'] ?? 'admin'); // Default to 'admin' if not provided
    // $status = trim($_POST['status'] ?? 'active'); // Default to 'active' if not provided

    // --- 2. Validate Data ---
    $errors = []; // Array to hold validation errors

    if (empty($name)) {
        $errors[] = "Admin name is required.";
    }
    if (empty($email)) {
        $errors[] = "Admin email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if admin email already exists
        $sql_check = "SELECT admin_id FROM admin WHERE email = ? LIMIT 1"; // Check 'admin' table
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Admin email address is already registered.";
            }
            $stmt_check->close();
        } else {
             $errors[] = "Database error checking email. Please try again.";
             // Log error: error_log("Prepare failed (admin email check): " . $conn->error);
        }
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) { // Keep password requirements consistent
        $errors[] = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }




    // --- 3. Process Data (if no errors) ---
    if (empty($errors)) {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL INSERT statement for 'admin' table
        // Using default values for role and status as defined in the table schema
        $sql = "INSERT INTO admin (name, email, password) VALUES (?, ?, ?)";
        // If you want to explicitly set role/status:
        // $sql = "INSERT INTO admin (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters (s = string)
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            // If setting role/status explicitly:
            // $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $status);


            // Execute the statement
            if ($stmt->execute()) {
                // Success! Redirect to an admin management page or success message
                $_SESSION['admin_create_success'] = "Admin account created successfully!";
                header("Location: admin_manage.php"); // Redirect to admin management page (adjust as needed)
                exit;
            } else {
                // Execution failed
                $_SESSION['admin_create_error'] = "Admin creation failed. Please try again later.";
                // Log the detailed error: error_log("Execute failed (admin insert): " . $stmt->error);
                header("Location: admin_create.php"); // Redirect back to admin creation form
                exit;
            }
          
        } else {
            // Statement preparation failed
            $_SESSION['admin_create_error'] = "Database error. Please try again later.";
            // Log the detailed error: error_log("Prepare failed (admin insert): " . $conn->error);
            header("Location: admin_create.php"); // Redirect back to admin creation form
            exit;
        }
    } else {
        // --- 4. Handle Errors ---
        // Store errors in session and redirect back to admin creation form
        $_SESSION['admin_create_errors'] = $errors;
        // Optionally store submitted values to repopulate form
        $_SESSION['form_data'] = ['name' => $name, 'email' => $email]; // Don't store password
        header("Location: admin_create.php"); // Redirect back to admin creation form
        exit;
    }


} else {
    // Not a POST request, redirect to admin creation form (or admin dashboard)
    header("Location: admin_create.php");
    exit;
}
?>