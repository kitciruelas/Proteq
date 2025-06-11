<?php
session_start(); // Start session to access error messages and form data

// Get form data from session if available (to repopulate fields after error)
$form_data = $_SESSION['form_data'] ?? [];
$errors = $_SESSION['signup_errors'] ?? [];
$signup_error = $_SESSION['signup_error'] ?? null;

// Clear session variables after retrieving them
unset($_SESSION['form_data'], $_SESSION['signup_errors'], $_SESSION['signup_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - PROTEQ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/auth-styles.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        .btn-next {
            background-color: #0d6efd;
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            width: 100%;
        }

        .btn-next:hover {
            background-color: rgba(13, 110, 253, 0.85);
            color: white;
        }

        .btn-prev {
            background-color: #6c757d;
            color: white;
            flex: 1;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
        }

        .btn-prev:hover {
            background-color: #5a6268;
            color: white;
        }

        .btn-navigation {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        /* Form Control Styles */
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .form-check-input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Link Styles */
        .login-link {
            font-size: 0.9em;
            margin-top: 1rem;
        }

        .login-link a {
            color: #0d6efd;
            font-weight: bold;
            text-decoration: none;
        }

        .login-link a:hover {
            color: rgba(13, 110, 253, 0.85);
            text-decoration: underline;
        }

        /* Step Indicator Styles */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 0 10px;
        }

        .step {
            text-align: center;
            flex: 1;
            font-size: 0.9rem;
        }

        .step-number {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background-color: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
            font-size: 0.9rem;
        }

        .step.active .step-number {
            background-color: #0d6efd;
            color: white;
        }

        .step.completed .step-number {
            background-color: #198754;
            color: white;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['signup_success'])): ?>
    <div class="notification-snap-alert success">
        <i class="bi bi-check-circle-fill"></i>
        <?php 
        echo htmlspecialchars($_SESSION['signup_success']);
        unset($_SESSION['signup_success']);
        ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['signup_error'])): ?>
    <div class="notification-snap-alert error">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?php 
        echo htmlspecialchars($_SESSION['signup_error']);
        unset($_SESSION['signup_error']);
        ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="notification-snap-alert error">
        <i class="bi bi-exclamation-circle-fill"></i>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="signup-container">
            <h2 class="text-center mb-4">Create Account</h2>

            <?php if ($signup_error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($signup_error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-warning" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="step-indicator">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-title">General Info</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-title">Security</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-title">Review</div>
                </div>
            </div>

            <form action="signup_process.php" method="POST" enctype="multipart/form-data" id="signupForm" onsubmit="return handleFormSubmit(event)">
                <!-- Step 1: General Info -->
                <div class="step-content active" data-step="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">
                                <i class="bi bi-person me-1"></i>
                                First Name
                            </label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required 
                                   value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">
                                <i class="bi bi-person me-1"></i>
                                Last Name
                            </label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required 
                                   value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i>
                            Email address
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" required>
                            <i class="bi bi-person-badge me-1"></i>
                            I am a
                        </label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="role_student" value="STUDENT" required>
                            <label class="form-check-label" for="role_student">
                                <i class="bi bi-mortarboard me-1"></i>
                                Student
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="role_faculty" value="FACULTY">
                            <label class="form-check-label" for="role_faculty">
                                <i class="bi bi-person-workspace me-1"></i>
                                Faculty
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="role_employee" value="UNIVERSITY_EMPLOYEE">
                            <label class="form-check-label" for="role_employee">
                                <i class="bi bi-person-gear me-1"></i>
                                University Employee
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">
                                <i class="bi bi-building me-1"></i>
                                Department
                            </label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="">Select Department</option>
                                <option value="CTE" <?php echo ($form_data['department'] ?? '') === 'CTE' ? 'selected' : ''; ?>>College of Teacher Education (CTE)</option>
                                <option value="CICS" <?php echo ($form_data['department'] ?? '') === 'CICS' ? 'selected' : ''; ?>>College of Information and Computing Sciences (CICS)</option>
                                <option value="CABE" <?php echo ($form_data['department'] ?? '') === 'CABE' ? 'selected' : ''; ?>>College of Accountancy and Business Education (CABE)</option>
                                <option value="CAS" <?php echo ($form_data['department'] ?? '') === 'CAS' ? 'selected' : ''; ?>>College of Arts and Sciences (CAS)</option>
                                <option value="CET" <?php echo ($form_data['department'] ?? '') === 'CET' ? 'selected' : ''; ?>>College of Engineering and Technology (CET)</option>
                                <option value="N/A" <?php echo ($form_data['department'] ?? '') === 'N/A' ? 'selected' : ''; ?>>Not Applicable (N/A)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="college" class="form-label">
                                <i class="bi bi-mortarboard me-1"></i>
                                College/Course
                            </label>
                            <select class="form-select" id="college" name="college" required>
                                <option value="">Select College/Course</option>
                                <!-- CTE Courses -->
                                <optgroup label="College of Teacher Education (CTE)">
                                    <option value="Bachelor of Elementary Education" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Elementary Education' ? 'selected' : ''; ?>>Bachelor of Elementary Education</option>
                                    <option value="Bachelor of Secondary Education - English" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Secondary Education - English' ? 'selected' : ''; ?>>Bachelor of Secondary Education - English</option>
                                    <option value="Bachelor of Secondary Education - Mathematics" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Secondary Education - Mathematics' ? 'selected' : ''; ?>>Bachelor of Secondary Education - Mathematics</option>
                                    <option value="Bachelor of Secondary Education - Science" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Secondary Education - Science' ? 'selected' : ''; ?>>Bachelor of Secondary Education - Science</option>
                                </optgroup>
                                <!-- CICS Courses -->
                                <optgroup label="College of Information and Computing Sciences (CICS)">
                                    <option value="Bachelor of Science in Information Technology" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Information Technology' ? 'selected' : ''; ?>>Bachelor of Science in Information Technology</option>
                                    <option value="Bachelor of Science in Computer Science" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Computer Science' ? 'selected' : ''; ?>>Bachelor of Science in Computer Science</option>
                                    <option value="Bachelor of Science in Information Systems" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Information Systems' ? 'selected' : ''; ?>>Bachelor of Science in Information Systems</option>
                                </optgroup>
                                <!-- CABE Courses -->
                                <optgroup label="College of Accountancy and Business Education (CABE)">
                                    <option value="Bachelor of Science in Accountancy" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Accountancy' ? 'selected' : ''; ?>>Bachelor of Science in Accountancy</option>
                                    <option value="Bachelor of Science in Business Administration" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Business Administration' ? 'selected' : ''; ?>>Bachelor of Science in Business Administration</option>
                                    <option value="Bachelor of Science in Hospitality Management" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Hospitality Management' ? 'selected' : ''; ?>>Bachelor of Science in Hospitality Management</option>
                                </optgroup>
                                <!-- CAS Courses -->
                                <optgroup label="College of Arts and Sciences (CAS)">
                                    <option value="Bachelor of Arts in Communication" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Arts in Communication' ? 'selected' : ''; ?>>Bachelor of Arts in Communication</option>
                                    <option value="Bachelor of Science in Psychology" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Psychology' ? 'selected' : ''; ?>>Bachelor of Science in Psychology</option>
                                    <option value="Bachelor of Science in Biology" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Biology' ? 'selected' : ''; ?>>Bachelor of Science in Biology</option>
                                </optgroup>
                                <!-- CET Courses -->
                                <optgroup label="College of Engineering and Technology (CET)">
                                    <option value="Bachelor of Science in Civil Engineering" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Civil Engineering' ? 'selected' : ''; ?>>Bachelor of Science in Civil Engineering</option>
                                    <option value="Bachelor of Science in Electrical Engineering" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Electrical Engineering' ? 'selected' : ''; ?>>Bachelor of Science in Electrical Engineering</option>
                                    <option value="Bachelor of Science in Mechanical Engineering" <?php echo ($form_data['college'] ?? '') === 'Bachelor of Science in Mechanical Engineering' ? 'selected' : ''; ?>>Bachelor of Science in Mechanical Engineering</option>
                                </optgroup>
                                <!-- N/A Option -->
                                <optgroup label="Not Applicable">
                                    <option value="Not Applicable" <?php echo ($form_data['college'] ?? '') === 'Not Applicable' ? 'selected' : ''; ?>>Not Applicable</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">
                            <i class="bi bi-image me-1"></i>
                            Profile Picture (Optional)
                        </label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" 
                               accept="image/*">
                    </div>

                    

                    <div class="btn-navigation">
                        <button type="button" class="btn btn-next" onclick="nextStep(1)">Next</button>
                    </div>
                </div>

                <!-- Step 2: Security -->
                <div class="step-content" data-step="2">
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-key me-1"></i>
                            Password
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required 
                                   minlength="8" placeholder="Enter your password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            <i class="bi bi-shield-check me-1"></i>
                            Password must contain:
                            <ul class="mb-0">
                                <li>At least 8 characters</li>
                                <li>At least one uppercase letter</li>
                                <li>At least one number</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-key-fill me-1"></i>
                            Confirm Password
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required minlength="8" placeholder="Confirm your password">
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Security Tips:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Use a strong, unique password</li>
                            <li>Never share your password with anyone</li>
                            <li>Enable two-factor authentication if available</li>
                        </ul>
                    </div>

                    <div class="btn-navigation">
                        <button type="button" class="btn btn-prev" onclick="prevStep(2)">Previous</button>
                        <button type="button" class="btn btn-next" onclick="nextStep(2)">Next</button>
                    </div>
                </div>

                <!-- Step 3: Review -->
                <div class="step-content" data-step="3">
                    <div class="review-section">
                        <h4 class="mb-3">
                            <i class="bi bi-clipboard-check me-2"></i>
                            Review Your Information
                        </h4>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-vcard me-2"></i>
                                    General Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <p><strong>Name:</strong> <span id="review_name"></span></p>
                                        <p><strong>Email:</strong> <span id="review_email"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Department:</strong> <span id="review_department"></span></p>
                                        <p><strong>College:</strong> <span id="review_college"></span></p>
                                    </div>
                                </div>
                                <p class="mb-0"><strong>Profile Picture:</strong> <span id="review_picture"></span></p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-shield-lock me-2"></i>
                                    Security Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><strong>Password:</strong> ********</p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-badge me-2"></i>
                                    Role Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><strong>I am a:</strong> <span id="review_role"></span></p>
                            </div>
                        </div>

                        <!-- Privacy Policy Consent -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Privacy Policy Consent
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="privacy_consent" name="privacy_consent" required>
                                    <label class="form-check-label" for="privacy_consent">
                                        I have read and agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Final Step:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Please review all information carefully</li>
                            <li>Ensure all details are accurate</li>
                            <li>Read and accept the Privacy Policy</li>
                        </ul>
                    </div>

                    <div class="btn-navigation">
                        <button type="button" class="btn btn-prev" onclick="prevStep(3)">Previous</button>
                        <button type="submit" class="btn btn-signup">Confirm & Sign Up</button>
                    </div>
                </div>

                <!-- Privacy Policy Modal -->
                <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <h6>Data Collection and Usage</h6>
                                <p>We collect and process your personal information for the following purposes:</p>
                                <ul>
                                    <li>To create and manage your account</li>
                                    <li>To provide you with access to our services</li>
                                    <li>To communicate with you about your account and our services</li>
                                    <li>To improve our services and user experience</li>
                                </ul>

                                <h6>Information We Collect</h6>
                                <ul>
                                    <li>Name and contact information</li>
                                    <li>Academic information (department, college, role)</li>
                                    <li>Profile picture (optional)</li>
                                    <li>Account credentials</li>
                                </ul>

                                <h6>Data Protection</h6>
                                <p>We implement appropriate security measures to protect your personal information:</p>
                                <ul>
                                    <li>Secure password storage using industry-standard encryption</li>
                                    <li>Regular security updates and monitoring</li>
                                    <li>Limited access to personal information</li>
                                </ul>

                                <h6>Your Rights</h6>
                                <p>You have the right to:</p>
                                <ul>
                                    <li>Access your personal information</li>
                                    <li>Correct inaccurate data</li>
                                    <li>Request deletion of your data</li>
                                    <li>Withdraw consent at any time</li>
                                </ul>

                                <h6>Contact Information</h6>
                                <p>For any privacy-related concerns, please contact:</p>
                                <p>Email: privacy@proteq.edu<br>
                                Phone: (123) 456-7890</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="text-center login-link mt-3">
                Already have an account? <a href="login.php">Log In here</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notification-snap-alert.js"></script>
   <script>
      

        function nextStep(currentStep) {
            // Validate current step
            if (!validateStep(currentStep)) {
                return;
            }

            // If moving to review step, update the review information
            if (currentStep === 2) {
                updateReviewInformation();
            }

            // Hide current step
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('completed');
            
            // Show next step
            const nextStep = currentStep + 1;
            document.querySelector(`.step-content[data-step="${nextStep}"]`).classList.add('active');
            document.querySelector(`.step[data-step="${nextStep}"]`).classList.add('active');
        }

        function prevStep(currentStep) {
            // Hide current step
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
            
            // Show previous step
            const prevStep = currentStep - 1;
            document.querySelector(`.step-content[data-step="${prevStep}"]`).classList.add('active');
            document.querySelector(`.step[data-step="${prevStep}"]`).classList.remove('completed');
        }

        function validateStep(step) {
            const currentStepContent = document.querySelector(`.step-content[data-step="${step}"]`);
            const requiredInputs = currentStepContent.querySelectorAll('input[required], select[required]');
            
            let isValid = true;
            let errorMessages = [];

            requiredInputs.forEach(input => {
                if (!input.value) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    errorMessages.push(`${input.name.replace('_', ' ')} is required`);
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Special validation for department and college
            if (step === 1) {
                const department = document.getElementById('department');
                const college = document.getElementById('college');
                const role = document.querySelector('input[name="role"]:checked');
                
                if (!department.value) {
                    isValid = false;
                    department.classList.add('is-invalid');
                    errorMessages.push('Please select a department');
                }
                
                if (!college.value) {
                    isValid = false;
                    college.classList.add('is-invalid');
                    errorMessages.push('Please select a college/course');
                }

                if (!role) {
                    isValid = false;
                    errorMessages.push('Please select your role');
                }
            }

            // Enhanced security validation
            if (step === 2) {
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                let passwordErrors = [];

                // Password length check
                if (password.value.length < 8) {
                    passwordErrors.push('Password must be at least 8 characters long');
                }

                // Password complexity checks
                if (!/[A-Z]/.test(password.value)) {
                    passwordErrors.push('Password must contain at least one uppercase letter');
                }
                if (!/[0-9]/.test(password.value)) {
                    passwordErrors.push('Password must contain at least one number');
                }

                // Password match check
                if (password.value !== confirmPassword.value) {
                    passwordErrors.push('Passwords do not match');
                }

                // Add visual feedback for password field
                if (passwordErrors.length > 0) {
                    password.classList.add('is-invalid');
                    isValid = false;
                    errorMessages.push(...passwordErrors);
                } else {
                    password.classList.remove('is-invalid');
                }

                // Add visual feedback for confirm password field
                if (password.value !== confirmPassword.value) {
                    confirmPassword.classList.add('is-invalid');
                } else {
                    confirmPassword.classList.remove('is-invalid');
                }
            }

            // Show custom notification if there are errors
            if (!isValid && errorMessages.length > 0) {
                showNotification('error', errorMessages);
            }

            return isValid;
        }

        function updateReviewInformation() {
            // Update General Information
            const firstName = document.getElementById('first_name').value;
            const lastName = document.getElementById('last_name').value;
            document.getElementById('review_name').textContent = `${firstName} ${lastName}`;
            document.getElementById('review_email').textContent = document.getElementById('email').value;
            document.getElementById('review_department').textContent = document.getElementById('department').value;
            document.getElementById('review_college').textContent = document.getElementById('college').value;

            // Update Profile Picture
            const profilePicture = document.getElementById('profile_picture');
            document.getElementById('review_picture').textContent = profilePicture.files.length > 0 ? 
                profilePicture.files[0].name : 'No file selected';

            // Update Role with proper display text
            const selectedRole = document.querySelector('input[name="role"]:checked');
            let roleDisplay = 'Not selected';
            if (selectedRole) {
                switch(selectedRole.value) {
                    case 'STUDENT':
                        roleDisplay = 'Student';
                        break;
                    case 'FACULTY':
                        roleDisplay = 'Faculty';
                        break;
                    case 'UNIVERSITY_EMPLOYEE':
                        roleDisplay = 'University Employee';
                        break;
                }
            }
            document.getElementById('review_role').textContent = roleDisplay;
        }

        function showNotification(type, messages) {
            const notificationDiv = document.createElement('div');
            notificationDiv.className = `notification-snap-alert ${type}`;
            
            let content = '';
            if (Array.isArray(messages)) {
                content = `
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <ul class="mb-0">
                        ${messages.map(msg => `<li>${msg}</li>`).join('')}
                    </ul>
                `;
            } else {
                content = `
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>${messages}</span>
                `;
            }
            
            notificationDiv.innerHTML = content;
            document.body.appendChild(notificationDiv);

            // Remove notification after 3 seconds
            setTimeout(() => {
                notificationDiv.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => {
                    notificationDiv.remove();
                }, 300);
            }, 3000);
        }

        function handleFormSubmit(event) {
            event.preventDefault();
            
            // Validate privacy policy consent before submission
            const privacyConsent = document.getElementById('privacy_consent');
            if (!privacyConsent.checked) {
                showNotification('error', 'You must agree to the Privacy Policy to create an account');
                return false;
            }
            
            // Show loading state
            const submitButton = document.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            // Submit the form
            const form = event.target;
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                showNotification('success', 'Account created successfully! Redirecting to login...');
                // Wait for 3 seconds before redirecting
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3000);
            })
            .catch(error => {
                showNotification('error', 'An error occurred. Please try again.');
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });

            return false;
        }

        function handleRoleChange() {
            const roleInputs = document.querySelectorAll('input[name="role"]');
            const departmentSelect = document.getElementById('department');
            const collegeSelect = document.getElementById('college');
            const naDepartmentOption = departmentSelect.querySelector('option[value="N/A"]');
            const naCollegeOption = collegeSelect.querySelector('optgroup[label="Not Applicable"]');

            roleInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.value === 'UNIVERSITY_EMPLOYEE') {
                        // Show N/A options for University Employees
                        naDepartmentOption.style.display = '';
                        naCollegeOption.style.display = '';
                        
                        // Set department to N/A and college to Not Applicable
                        departmentSelect.value = 'N/A';
                        collegeSelect.value = 'Not Applicable';
                        
                        // Hide other college options
                        Array.from(collegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                            if (group.label !== 'Not Applicable') {
                                group.style.display = 'none';
                            }
                        });
                    } else {
                        // Hide N/A options for other roles
                        naDepartmentOption.style.display = 'none';
                        naCollegeOption.style.display = 'none';
                        
                        // If N/A was selected, reset to first available department
                        if (departmentSelect.value === 'N/A') {
                            departmentSelect.value = 'CTE';
                        }
                        if (collegeSelect.value === 'Not Applicable') {
                            collegeSelect.value = '';
                        }
                        
                        // Show relevant college options
                        Array.from(collegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                            if (group.label.includes(departmentSelect.value)) {
                                group.style.display = '';
                            } else {
                                group.style.display = 'none';
                            }
                        });
                    }
                });
            });

            // Initial setup based on selected role
            const selectedRole = document.querySelector('input[name="role"]:checked');
            if (selectedRole) {
                const event = new Event('change');
                selectedRole.dispatchEvent(event);
            }
        }

        function handleDepartmentChange() {
            const departmentSelect = document.getElementById('department');
            const collegeSelect = document.getElementById('college');
            const employeeRole = document.getElementById('role_employee');
            const studentRole = document.getElementById('role_student');
            
            departmentSelect.addEventListener('change', function() {
                const selectedRole = document.querySelector('input[name="role"]:checked');
                
                if (this.value === 'N/A') {
                    // If N/A is selected, set college to Not Applicable
                    collegeSelect.value = 'Not Applicable';
                    // Hide all other college options
                    Array.from(collegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                        if (group.label !== 'Not Applicable') {
                            group.style.display = 'none';
                        }
                    });
                    
                    // If not University Employee, switch to it
                    if (selectedRole && selectedRole.value !== 'UNIVERSITY_EMPLOYEE') {
                        employeeRole.checked = true;
                        const event = new Event('change');
                        employeeRole.dispatchEvent(event);
                    }
                } else {
                    // Show relevant college options
                    Array.from(collegeSelect.getElementsByTagName('optgroup')).forEach(group => {
                        if (group.label.includes(this.value) || group.label === 'Not Applicable') {
                            group.style.display = '';
                        } else {
                            group.style.display = 'none';
                        }
                    });
                    
                    // Reset college selection if it's Not Applicable
                    if (collegeSelect.value === 'Not Applicable') {
                        collegeSelect.value = '';
                    }
                    
                    // If University Employee was selected, switch to Student
                    if (selectedRole && selectedRole.value === 'UNIVERSITY_EMPLOYEE') {
                        studentRole.checked = true;
                        const event = new Event('change');
                        studentRole.dispatchEvent(event);
                    }
                }
            });
        }

        // Initialize the handlers when document is loaded
        document.addEventListener('DOMContentLoaded', function() {
            handleRoleChange();
            handleDepartmentChange();

            // Password toggle functionality
            const togglePassword = document.querySelector('#togglePassword');
            const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
            const password = document.querySelector('#password');
            const confirmPassword = document.querySelector('#confirm_password');

            function togglePasswordVisibility(input, button) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                // Toggle the icon
                const icon = button.querySelector('i');
                if (type === 'password') {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }

            togglePassword.addEventListener('click', function() {
                togglePasswordVisibility(password, this);
            });

            toggleConfirmPassword.addEventListener('click', function() {
                togglePasswordVisibility(confirmPassword, this);
            });
        });
    </script>
</body>
</html>