<?php
session_start(); // Start session at the very beginning
// Show success message if exists and redirect to dashboard
if (isset($_SESSION['login_success'])) {
    echo '<div class="notification-snap-alert success">
            <i class="bi bi-check-circle-fill"></i>
            ' . htmlspecialchars($_SESSION['login_success']) . '
          </div>';
    unset($_SESSION['login_success']);
    
    // Redirect to dashboard after a short delay
    echo '<script>
        setTimeout(function() {
            window.location.href = "../User/Dashboard.php";
        }, 2000);
    </script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PROTEQ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/auth-styles.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <!-- reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
            background-color: #0d6efd; /* Coral button */
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            width: 100%;
        }
        .btn-login:hover {
            background-color: rgba(13, 109, 253, 0.85); /* Darker coral on hover */
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
    <?php if (isset($_SESSION['login_success'])): ?>
    <div class="notification-snap-alert success">
        <i class="bi bi-check-circle-fill"></i>
        <?php 
        echo htmlspecialchars($_SESSION['login_success']);
        unset($_SESSION['login_success']);
        ?>
    </div>
    <script src="../assets/js/notification-snap-alert.js"></script>

    <?php endif; ?>

    <?php if (isset($_SESSION['login_error'])): ?>
    <div class="notification-snap-alert error">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?php 
        echo $_SESSION['login_error'];
        unset($_SESSION['login_error']);
        ?>
    </div>
    <script src="../assets/js/notification-snap-alert.js"></script>

    <?php endif; ?>

    <div class="container-fluid d-flex justify-content-center align-items-center min-vh-100"> <!-- Changed to container-fluid and added flex utilities -->
        <div class="row login-container"> <!-- login-container now directly inside the fluid container -->
            <!-- Image Section -->
            <div class="col-md-6 login-image-section d-none d-md-flex">
                <div class="logo">PROTEQ</div> <!-- Or your actual logo text/image -->
                <!-- Replace with your actual image -->
                <img src="img/loginimg.png" alt="Login Illustration" class="img-fluid"> <!-- Added img-fluid -->
            </div>

            <!-- Form Section -->
            <div class="col-md-6 login-form-section"> <!-- This div will now have the border -->
                <a href="../index.html"><i class="bi bi-x close-btn"></i></a> <!-- Close button - Updated href -->
                <h2 class="text-center mb-4">Login</h2>
                <form action="login_process.php" method="POST" onsubmit="return validateForm()"> <!-- Point to your processing script -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                             <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="your.email@example.com" required value="<?php echo htmlspecialchars($_SESSION['login_email'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                         <div class="input-group">
                             <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
                              <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                <i class="bi bi-eye-slash"></i>
                            </span>
                        </div>
                    </div>
                    <div class="text-end mb-3">
                        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                    </div>
                    <!-- Add reCAPTCHA -->
                    <div class="mb-3">
                        <div class="g-recaptcha" data-sitekey="6LfVgHUqAAAAAJtQJXShsLo2QbyGby2jquueTZYV" required></div>
                    </div>
                    <button type="submit" class="btn btn-login mb-3">
                        <span class="button-text">Log In</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>


                <div class="text-center signup-link mt-4"> <!-- Added margin-top -->
                    Don't have an account? <a href="signup.php">Sign Up here</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Password Toggle Script -->
    <script src="../assets/js/eye_toggle_icon.js"></script>
    <script>
                function validateForm() {
                    var email = document.getElementById('email').value;
                    var password = document.getElementById('password').value;
                    var recaptcha = document.querySelector('.g-recaptcha-response').value;
                    
                    if (email.trim() === '') {
                        showNotification('Please check your email', 'error');
                        return false;
                    }
                    
                    if (password.trim() === '') {
                        showNotification('Please check your password', 'error');
                        return false;
                    }
                    
                    if (!recaptcha) {
                        showNotification('Please check the reCAPTCHA', 'error');
                        return false;
                    }
                    
                    return true;
                }

                function showNotification(message, type) {
                    // Create notification element
                    var notification = document.createElement('div');
                    notification.className = 'notification-snap-alert ' + type;
                    
                    // Add icon based on type
                    var icon = type === 'error' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill';
                    notification.innerHTML = '<i class="bi ' + icon + '"></i> ' + message;
                    
                    // Add to body
                    document.body.appendChild(notification);
                    
                    // Remove after 3 seconds
                    setTimeout(function() {
                        notification.remove();
                    }, 3000);
                }

                document.addEventListener('DOMContentLoaded', function() {
                    const loginForm = document.querySelector('form');
                    const loginButton = loginForm.querySelector('button[type="submit"]');
                    const buttonText = loginButton.querySelector('.button-text');
                    const spinner = loginButton.querySelector('.spinner-border');

                    loginForm.addEventListener('submit', function() {
                        // Show loading state
                        buttonText.textContent = 'Logging in...';
                        spinner.classList.remove('d-none');
                        loginButton.disabled = true;
                    });
                });
                </script>

</body>
</html>