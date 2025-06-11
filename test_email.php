<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

// Set error log to a local file
$logFile = $logDir . '/error.log';
ini_set('error_log', $logFile);

// Clear the log file
file_put_contents($logFile, '');

require_once 'includes/email_helper.php';

// Test email data
$testEmails = [
    "ciruelaskeithandrei@gmail.com",
    "fking6915@gmail.com",
    "andreiagasopa@gmail.com"
];

$testData = [
    'title' => 'Test Alert - Please Check Spam',
    'alert_type' => 'emergency',
    'description' => 'This is a test alert to verify email sending. Please check your spam folder if you don\'t see this email in your inbox.',
    'location' => 'Test Location',
    'radius_km' => 5,
    'recipient_name' => 'Test User',
    // Add coordinates for testing (example: Manila coordinates)
    'latitude' => 14.5995,
    'longitude' => 120.9842
];

echo "<h1>Email Test Results</h1>";

try {
    error_log("Starting batch email test...");
    error_log("Test data: " . print_r($testData, true));
    error_log("Recipients: " . print_r($testEmails, true));
    
    // Set maximum execution time to 60 seconds
    set_time_limit(60);
    
    $results = sendAlertEmail($testEmails, $testData);
    
    echo "<div style='margin: 20px;'>";
    foreach ($results as $email => $success) {
        if ($success) {
            echo "<div style='color: green; margin-bottom: 10px;'>";
            echo "<p>✅ Test email sent successfully to: " . htmlspecialchars($email) . "</p>";
            echo "<p>Please check your email inbox AND spam folder.</p>";
            echo "<p>If you don't see the email, please:</p>";
            echo "<ol>";
            echo "<li>Check your spam folder</li>";
            echo "<li>Add fking6915@gmail.com to your contacts</li>";
            echo "<li>Mark the email as 'Not Spam' if it's in spam</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div style='color: red; margin-bottom: 10px;'>";
            echo "<p>❌ Failed to send test email to: " . htmlspecialchars($email) . "</p>";
            echo "</div>";
        }
        echo "<hr>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>Error Details:</h3>";
    echo "<pre>";
    echo "Error Message: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "File: " . htmlspecialchars($e->getFile()) . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "</div>";
    
    // Also log to error log
    error_log("Email test failed with error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
}

// Display the contents of the error log
echo "<h2>Error Log Contents:</h2>";
echo "<pre>";
echo htmlspecialchars(file_get_contents($logFile));
echo "</pre>";
?> 