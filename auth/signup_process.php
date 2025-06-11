<?php
session_start(); // Start session for feedback messages
require_once '../includes/db.php'; // Include your database connection

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate password strength
function isStrongPassword($password) {
    // At least 8 characters, 1 uppercase, 1 number
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

// Initialize arrays for errors and form data
$errors = [];
$form_data = [];

// Get and sanitize form data
$form_data['first_name'] = trim($_POST['first_name'] ?? '');
$form_data['last_name'] = trim($_POST['last_name'] ?? '');
$form_data['email'] = trim($_POST['email'] ?? '');
$form_data['department'] = trim($_POST['department'] ?? '');
$form_data['college'] = trim($_POST['college'] ?? '');
$form_data['role'] = trim($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$privacy_consent = isset($_POST['privacy_consent']) ? true : false;

// Validate required fields with specific messages
if (empty($form_data['first_name'])) {
    $errors[] = 'First name is required';
} elseif (strlen($form_data['first_name']) < 2) {
    $errors[] = 'First name must be at least 2 characters long';
}

if (empty($form_data['last_name'])) {
    $errors[] = 'Last name is required';
} elseif (strlen($form_data['last_name']) < 2) {
    $errors[] = 'Last name must be at least 2 characters long';
}

if (empty($form_data['email'])) {
    $errors[] = 'Email address is required';
} elseif (!isValidEmail($form_data['email'])) {
    $errors[] = 'Please enter a valid email address';
}

// Validate department and college based on role
if (empty($form_data['department'])) {
    $errors[] = 'Please select a department';
} elseif ($form_data['department'] === 'N/A' && $form_data['role'] !== 'UNIVERSITY_EMPLOYEE') {
    $errors[] = 'Only University Employees can select N/A for department';
}

if (empty($form_data['college'])) {
    $errors[] = 'Please select a college/course';
} elseif ($form_data['college'] === 'Not Applicable' && $form_data['role'] !== 'UNIVERSITY_EMPLOYEE') {
    $errors[] = 'Only University Employees can select Not Applicable for college';
}

if (empty($form_data['role'])) {
    $errors[] = 'Please select your role';
}

if (empty($password)) {
    $errors[] = 'Password is required';
} elseif (!isStrongPassword($password)) {
    $errors[] = 'Password must be at least 8 characters long and contain uppercase and numbers';
}

if (empty($confirm_password)) {
    $errors[] = 'Please confirm your password';
} elseif ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

// Validate privacy policy consent
if (!$privacy_consent) {
    $errors[] = 'You must agree to the Privacy Policy to create an account';
}

// Check if email already exists
if (!empty($form_data['email']) && isValidEmail($form_data['email'])) {
    $stmt = $conn->prepare("SELECT user_id FROM general_users WHERE email = ?");
    $stmt->bind_param("s", $form_data['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = 'This email address is already registered';
    }
    $stmt->close();
}

// Handle profile picture upload with specific error messages
$profile_picture = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['profile_picture']['error'] === UPLOAD_ERR_FORM_SIZE) {
        $errors[] = 'Profile picture size exceeds the maximum limit of 5MB';
    } elseif ($_FILES['profile_picture']['error'] === UPLOAD_ERR_PARTIAL) {
        $errors[] = 'Profile picture upload was incomplete';
    } elseif ($_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_TMP_DIR) {
        $errors[] = 'Server configuration error: Missing temporary folder';
    } elseif ($_FILES['profile_picture']['error'] === UPLOAD_ERR_CANT_WRITE) {
        $errors[] = 'Server error: Failed to write profile picture to disk';
    } elseif ($_FILES['profile_picture']['error'] === UPLOAD_ERR_EXTENSION) {
        $errors[] = 'Profile picture upload was stopped by extension';
    } elseif (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
        $errors[] = 'Invalid file type. Only JPG, PNG, and GIF images are allowed';
    } elseif ($_FILES['profile_picture']['size'] > $max_size) {
        $errors[] = 'Profile picture size exceeds the maximum limit of 5MB';
    } else {
        $upload_dir = '../uploads/profile_pictures/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $errors[] = 'Failed to create upload directory';
            }
        }

        if (empty($errors)) {
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $errors[] = 'Failed to save profile picture. Please try again';
            } else {
                $profile_picture = 'uploads/profile_pictures/' . $new_filename;
            }
        }
    }
}

// If there are errors, store them in session and redirect back
if (!empty($errors)) {
    $_SESSION['signup_errors'] = $errors;
    $_SESSION['form_data'] = $form_data;
    header('Location: signup.php');
    exit;
}

try {
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO general_users (first_name, last_name, email, password, department, college, profile_picture, user_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    
    if (!$stmt) {
        throw new Exception('Database error: Failed to prepare statement');
    }

    $stmt->bind_param("ssssssss", 
        $form_data['first_name'],
        $form_data['last_name'],
        $form_data['email'],
        $hashed_password,
        $form_data['department'],
        $form_data['college'],
        $profile_picture,
        $form_data['role']
    );

    if (!$stmt->execute()) {
        throw new Exception('Database error: Failed to create account');
    }

    // Clear any existing session data
    session_unset();
    session_destroy();
    session_start();
    
    // Set success message
    $_SESSION['signup_success'] = 'Account created successfully! You can now log in.';
    header('Location: login.php');
    exit;

} catch (Exception $e) {
    $_SESSION['signup_error'] = 'An error occurred while creating your account: ' . $e->getMessage();
    $_SESSION['form_data'] = $form_data;
    header('Location: signup.php');
    exit;
}
?>