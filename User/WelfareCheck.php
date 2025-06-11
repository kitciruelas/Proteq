<?php
session_start();
require_once '../includes/db.php';

// Get active emergency
$query = "SELECT * FROM emergencies WHERE is_active = 1 ORDER BY triggered_at DESC LIMIT 1";
$result = $conn->query($query);
$active_emergency = $result->fetch_assoc();

// Check if user has already responded
$has_responded = false;
$user_response = null;
if ($active_emergency) {
    $query = "SELECT * FROM welfare_checks WHERE emergency_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $active_emergency['emergency_id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_response = $result->fetch_assoc();
    $has_responded = $user_response !== null;
}

// Handle welfare check response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_response'])) {
    // Check if user has already responded
    if ($has_responded) {
        header('Location: WelfareCheck.php?error=already_submitted');
        exit();
    }

    $status = $_POST['status'];
    $remarks = $_POST['remarks'];
    
    // Create new response
    $query = "INSERT INTO welfare_checks (emergency_id, user_id, status, remarks) 
             VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $active_emergency['emergency_id'], $_SESSION['user_id'], $status, $remarks);
    $stmt->execute();
    
    // Redirect to prevent form resubmission
    header('Location: WelfareCheck.php?success=1');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welfare Check - PROTEQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/g_user.css">
    <style>
        .status-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-card.safe {
            background-color: #d4edda;
            border: 2px solid #28a745;
        }
        .status-card.help {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
        }
        .status-card.selected {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .status-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .status-card.safe .status-icon {
            color: #28a745;
        }
        .status-card.help .status-icon {
            color: #dc3545;
        }
        .emergency-banner {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .emergency-banner h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .emergency-banner .timestamp {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .form-floating textarea {
            height: 120px;
        }
        .submit-btn {
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .no-emergency-card {
            text-align: center;
            padding: 3rem 2rem;
            background-color: #f8f9fa;
            border-radius: 15px;
        }
        .no-emergency-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .success-alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            animation: fadeIn 0.5s ease;
        }
        .submitted-card {
            text-align: center;
            padding: 3rem 2rem;
            background-color: #d4edda;
            border-radius: 15px;
            border: 2px solid #28a745;
        }
        .submitted-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'components/_sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/topbar.php'; ?>

        <div class="container-fluid p-4">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success success-alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Your response has been successfully recorded!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'already_submitted'): ?>
                <div class="alert alert-warning success-alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    You have already submitted your response for this emergency.
                </div>
            <?php endif; ?>

            <?php if ($active_emergency): ?>
                <div class="emergency-banner">
                    <h2><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($active_emergency['emergency_type']); ?></h2>
                    <p class="mb-2"><?php echo htmlspecialchars($active_emergency['description']); ?></p>
                    <p class="timestamp mb-0"><i class="bi bi-clock me-1"></i>Issued: <?php echo date('F j, Y g:i a', strtotime($active_emergency['triggered_at'])); ?></p>
                </div>

                <?php if ($has_responded): ?>
                    <div class="card shadow-sm">
                        <div class="card-body submitted-card">
                            <i class="bi bi-check-circle-fill submitted-icon"></i>
                            <h3 class="mb-3">Response Submitted</h3>
                            <p class="text-muted mb-2">You have already submitted your status for this emergency:</p>
                            <div class="alert <?php echo $user_response['status'] === 'SAFE' ? 'alert-success' : 'alert-danger'; ?> mb-3">
                                <strong>Status:</strong> <?php echo $user_response['status'] === 'SAFE' ? 'Safe' : 'Need Help'; ?>
                                <?php if (!empty($user_response['remarks'])): ?>
                                    <br><strong>Remarks:</strong> <?php echo htmlspecialchars($user_response['remarks']); ?>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted mb-0">Submitted on: <?php echo date('F j, Y g:i a', strtotime($user_response['reported_at'])); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="card-title h4 mb-4">Please Update Your Status</h3>
                            
                            <form method="POST" class="welfare-form">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="status-card safe p-4 text-center" onclick="selectStatus('SAFE')">
                                            <i class="bi bi-check-circle-fill status-icon"></i>
                                            <h4>I'm Safe</h4>
                                            <p class="text-muted mb-0">I am in a safe location and don't need assistance</p>
                                            <input type="radio" name="status" value="SAFE" class="d-none" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="status-card help p-4 text-center" onclick="selectStatus('NEEDS_HELP')">
                                            <i class="bi bi-exclamation-triangle-fill status-icon"></i>
                                            <h4>Need Help</h4>
                                            <p class="text-muted mb-0">I need assistance or am in an unsafe situation</p>
                                            <input type="radio" name="status" value="NEEDS_HELP" class="d-none">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="remarks" class="form-label">Additional Information (Optional)</label>
                                    <textarea id="remarks" name="remarks" class="form-control" rows="4" placeholder="Please provide any additional details about your situation..."></textarea>
                                </div>
                                
                                <button type="submit" name="submit_response" class="btn btn-primary submit-btn">
                                    <i class="bi bi-send-fill me-2"></i>
                                    Submit Response
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body no-emergency-card">
                        <i class="bi bi-shield-check no-emergency-icon"></i>
                        <h3 class="mb-3">No Active Emergencies</h3>
                        <p class="text-muted mb-0">There are currently no active emergency situations that require your response.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/user-menu.js"></script>
    <script>
        function selectStatus(status) {
            // Remove selected class from all cards
            document.querySelectorAll('.status-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            const selectedCard = document.querySelector(`.status-card.${status === 'SAFE' ? 'safe' : 'help'}`);
            selectedCard.classList.add('selected');
            
            // Set the radio button value
            document.querySelector(`input[name="status"][value="${status}"]`).checked = true;
        }
    </script>
</body>
</html>