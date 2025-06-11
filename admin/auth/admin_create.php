<?php
session_start(); // Start session to access feedback messages and form data

// Get form data and errors from session if available
$form_data = $_SESSION['form_data'] ?? [];
$errors = $_SESSION['admin_create_errors'] ?? [];
$general_error = $_SESSION['admin_create_error'] ?? null;
$success_message = $_SESSION['admin_create_success'] ?? null;

// Clear session variables after retrieving them
unset($_SESSION['form_data'], $_SESSION['admin_create_errors'], $_SESSION['admin_create_error'], $_SESSION['admin_create_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - PROTEQ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Optional: Link to a shared admin CSS or add styles here -->
    <style>
        body { background-color: #f8f9fa; }
        .create-admin-container { max-width: 600px; margin: 5vh auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        /* Add more specific admin styles if needed */
    </style>
</head>
<body>
    <!-- Optional: Include an admin navigation bar here -->
    <!-- Example: <?php // include '../includes/admin_nav.php'; ?> -->

    <div class="container">
        <div class="create-admin-container">
            <h2 class="text-center mb-4">Create New Admin User</h2>

            <?php // Display Success Message
            if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php // Display General Error (e.g., database connection issues)
            if ($general_error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($general_error); ?>
                </div>
            <?php endif; ?>

            <?php // Display Specific Validation Errors
            if (!empty($errors)): ?>
                <div class="alert alert-warning" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="admin_create_process.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Admin Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g., Jane Smith" required value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Admin Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="admin@example.com" required value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Minimum 8 characters" required>
                    <div class="form-text">Password must be at least 8 characters long.</div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>

                

                <button type="submit" class="btn btn-primary w-100">Create Admin</button>
            </form>

            <!-- Optional: Link back to admin dashboard or management page -->
            <div class="text-center mt-3">
                <a href="admin_dashboard.php">Back to Dashboard</a> <!-- Adjust link as needed -->
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>