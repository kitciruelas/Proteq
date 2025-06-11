<?php session_start(); // Start session to access potential error messages ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PROTEQ</title> <!-- Changed Title -->
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Notifications CSS -->
    <link rel="stylesheet" href="../../assets/css/notifications.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/auth-styles.css">
    <style>
        /* Basic styles inspired by the image - consider moving to auth-styles.css */
        body {
            background-color: #FFF7F5; /* Light pink background */
        }
        .login-container {
            max-width: 900px;
            margin: 5vh auto;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden; /* Ensures rounded corners apply to children */
        }
        .login-image-section {
            background-color:rgb(255, 255, 255); /* Slightly darker pink for image side */
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Center content vertically */
            align-items: center; /* Center content horizontally */
            position: relative; /* For positioning elements like the logo */
        }
        .login-image-section .logo {
             position: absolute;
             top: 20px;
             left: 20px;
             font-weight: bold;
             color: #0d6efd; /* Adjust color */
        }
        .login-image-section img {
            max-width: 100%;
            height: auto;
            /* Optional: Add some margin if needed */
            /* margin-top: 20px; */
        }
        .login-form-section {
            padding: 40px 50px; /* More padding */
            position: relative;
            border-left: 1px solid #dee2e6; /* Added left border */
        }
         .login-form-section .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            color: #6c757d;
            cursor: pointer;
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }
        .input-group .form-control {
             border-right: 0; /* Remove border between icon and input */
        }
        .input-group .input-group-text {
            background-color: #fff;
            border-left: 0;
             border-right: 1px solid #ced4da; /* Add border back to the right of icon */
             border-radius: 8px 0 0 8px; /* Match input radius */
        }
         .form-control:focus {
             border-color: #0d6efd; /* Bootstrap primary color border on focus */
             box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
         }
        .btn-primary {
            background-color: #0d6efd; /* Bootstrap primary color button */
            border-color: #0d6efd;
        }
        .btn-login {
            background-color:  #0d6efd; /* Coral button */
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            width: 100%;
        }
        .btn-login:hover {
            background-color:rgba(13, 109, 253, 0.85); /* Darker coral on hover */
            color: white;
        }
        .forgot-password {
            font-size: 0.9em;
            color: #0d6efd;
            text-decoration: none;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #adb5bd;
            margin: 25px 0;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        .divider:not(:empty)::before {
            margin-right: .25em;
        }
        .divider:not(:empty)::after {
            margin-left: .25em;
        }
        .social-login i {
            font-size: 1.8rem;
            margin: 0 10px;
            color: #6c757d;
            cursor: pointer;
            transition: color 0.2s;
        }
         
        .signup-link {
            font-size: 0.9em;
        }
        .signup-link a {
            color: #0d6efd;
            font-weight: bold;
            text-decoration: none;
        }
         .signup-link a:hover {
             text-decoration: underline;
         }

         /* Responsive adjustments */
         @media (max-width: 767.98px) {
             .login-image-section {
                 display: none; /* Hide image on smaller screens */
             }
             .login-form-section {
                 padding: 30px;
             }
         }

    </style>
</head>
<body>

    <div class="container-fluid d-flex justify-content-center align-items-center min-vh-100">
        <div class="row login-container">
            <!-- Image Section -->
            <div class="col-md-6 login-image-section d-none d-md-flex">
                <div class="logo">PROTEQ</div>
                <img src="../../assets/img/admin_img/admin-log.png" alt="Login Illustration" class="img-fluid">
            </div>

            <!-- Form Section -->
            <div class="col-md-6 login-form-section">
                <h2 class="text-center mb-4">Admin Login</h2>

                <?php
                // Display login errors if they exist
                if (isset($_SESSION['login_error'])) {
                    echo '<div class="notification-snap-alert error">
                            <i class="bi bi-exclamation-circle"></i>
                            ' . htmlspecialchars($_SESSION['login_error']) . '
                          </div>';
                    unset($_SESSION['login_error']); // Clear the error message after displaying it
                }
                if (isset($_SESSION['login_success'])) {
                    echo '<div class="notification-snap-alert success">
                            <i class="bi bi-check-circle"></i>
                            ' . htmlspecialchars($_SESSION['login_success']) . '
                          </div>';
                    unset($_SESSION['login_success']); // Clear the success message after displaying it
                }
                ?>

                <form action="admin_login_process.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                             <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="admin.email@example.com" required> <!-- Updated placeholder -->
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                         <div class="input-group">
                             <span class="input-group-text"><i class="bi bi-shield-lock"></i></span> <!-- Changed icon -->
                            <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
                            <!-- Add the toggle icon -->
                            <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                <i class="bi bi-eye-slash"></i>
                            </span>
                        </div>
                    </div>
                    <!-- Removed Forgot Password Link -->
                    <!-- <div class="text-end mb-3">
                        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                    </div> -->
                    <button type="submit" class="btn btn-login mb-3 mt-4" id="loginButton">
                        <span class="button-text">Log In</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Optional: Add JS for password visibility toggle -->
    <script src="../../assets/js/eye_toggle_icon.js"></script>

    <script>
        // Handle success message and redirect
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.querySelector('.notification-snap-alert.success');
            if (successMessage) {
                // Wait for 2 seconds before redirecting
                setTimeout(function() {
                    window.location.href = '../../admin/dashboard.php';
                }, 2000);
            }

            // Add loading state to login form
            const loginForm = document.querySelector('form');
            const loginButton = document.getElementById('loginButton');
            const buttonText = loginButton.querySelector('.button-text');
            const spinner = loginButton.querySelector('.spinner-border');

            loginForm.addEventListener('submit', function(e) {
                // Show loading state
                buttonText.textContent = 'Logging in...';
                spinner.classList.remove('d-none');
                loginButton.disabled = true;
            });
        });
    </script>
</body>
</html>