<?php
session_start();
require_once '../includes/db.php';

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to verify reCAPTCHA
function verifyRecaptcha($recaptcha_response) {
    $secret_key = "6LfVgHUqAAAAAPu4IsmVAI8j0uqaBhrILi7i5pQW"; // Replace with your reCAPTCHA secret key
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => $secret_key,
        'response' => $recaptcha_response
    );

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $result = json_decode($response);

    return $result->success;
}

// Initialize arrays for errors
$errors = [];

// Get and sanitize form data
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!isValidEmail($email)) {
    $errors[] = 'Invalid email format';
}

if (empty($password)) {
    $errors[] = 'Password is required';
}

// Verify reCAPTCHA
if (!isset($_POST['g-recaptcha-response']) || !verifyRecaptcha($_POST['g-recaptcha-response'])) {
    $_SESSION['login_error'] = "Please complete the reCAPTCHA verification.";
    $_SESSION['login_email'] = $email; // Store email for repopulating the form
    header('Location: login.php');
    exit();
}

// If there are validation errors, store them and redirect back
if (!empty($errors)) {
    $_SESSION['login_error'] = implode('<br>', $errors);
    $_SESSION['login_email'] = $email; // Store email for repopulating the form
    header('Location: login.php');
    exit;
}

try {
    // First check staff table
    $stmt = $conn->prepare("SELECT staff_id, name, email, password, role, status FROM staff WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if staff is active
        if ($user['status'] !== 'active') {
            $_SESSION['login_error'] = 'Your account is inactive. Please contact support.';
            $_SESSION['login_email'] = $email;
            header('Location: login.php');
            exit;
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['staff_id'] = $user['staff_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_type'] = 'staff';
            $_SESSION['logged_in'] = true;
            $_SESSION['login_success'] = 'Welcome back, ' . htmlspecialchars($user['name']) . '!';
            
            // Redirect to staff dashboard
            header('Location: ../Staff/dashboard.php');
            exit;
        }
    }

    // If not found in staff table, check general_users table
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, user_type, profile_picture, status FROM general_users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if user is active
        if ($user['status'] != 1) {
            $_SESSION['login_error'] = 'Your account is inactive. Please contact support.';
            $_SESSION['login_email'] = $email;
            header('Location: login.php');
            exit;
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_success'] = 'Welcome back, ' . htmlspecialchars($user['first_name']) . '!';
            
            // Redirect to user dashboard
            header('Location: ../User/Dashboard.php');
            exit;
        }
    }

    // If we get here, either the email wasn't found or the password was incorrect
    $_SESSION['login_error'] = 'Invalid email or password';
    $_SESSION['login_email'] = $email;
    header('Location: login.php');
    exit;

} catch (Exception $e) {
    // Log the error (in a production environment)
    error_log("Login error: " . $e->getMessage());
    
    $_SESSION['login_error'] = 'An error occurred during login. Please try again.';
    $_SESSION['login_email'] = $email;
    header('Location: login.php');
    exit;
}

// Close the statement and connection
?>
