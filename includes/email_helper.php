<?php
// Check if files exist before requiring them
$phpmailerPath = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';
$requiredFiles = [
    'Exception.php',
    'PHPMailer.php',
    'SMTP.php'
];

foreach ($requiredFiles as $file) {
    $fullPath = $phpmailerPath . $file;
    if (!file_exists($fullPath)) {
        error_log("PHPMailer file not found: " . $fullPath);
        die("Required PHPMailer file not found: " . $file);
    }
    require_once $fullPath;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getLocationFromCoordinates($latitude, $longitude) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=18&addressdetails=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Proteq Alert System');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if ($data && isset($data['display_name'])) {
            return $data['display_name'];
        }
    }
    
    // If geocoding fails, return coordinates
    return "Latitude: {$latitude}, Longitude: {$longitude}";
}

function sendAlertEmail($userEmail, $alertData) {
    // If $userEmail is an array, send to multiple recipients
    if (is_array($userEmail)) {
        $results = [];
        foreach ($userEmail as $email) {
            try {
                $results[$email] = sendAlertEmail($email, $alertData);
                // Add a small delay between emails
                usleep(100000); // 100ms delay
            } catch (Exception $e) {
                $results[$email] = false;
                error_log("Failed to send email to " . $email . ": " . $e->getMessage());
            }
        }
        return $results;
    }

    $mail = new PHPMailer(true);
    try {
        // Enable debug output
        $mail->SMTPDebug = 3; // Show all debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'fking6915@gmail.com';
        $mail->Password = 'azqa bnkd mbop dxgm';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Additional settings for better reliability
        $mail->SMTPKeepAlive = true;
        $mail->Timeout = 60;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Test SMTP connection before proceeding
        if (!$mail->smtpConnect()) {
            throw new Exception('Failed to connect to SMTP server');
        }

        // Set sender and recipient
        $mail->setFrom('alerts.proteq@gmail.com', 'Proteq Alert System');
        $mail->addAddress($userEmail, $alertData['recipient_name']);
        
        // Set subject line
        $mail->Subject = "🚨 EMERGENCY ALERT: " . strtoupper(htmlspecialchars($alertData['alert_type'])) . " - " . strtoupper(htmlspecialchars($alertData['title']));
        
        // Add reply-to header
        $mail->addReplyTo('alerts.proteq@gmail.com', 'Proteq Alert System');

        // Add additional headers to improve deliverability
        $mail->addCustomHeader('X-Priority', '1'); // High priority
        $mail->addCustomHeader('X-MSMail-Priority', 'High');
        $mail->addCustomHeader('Importance', 'High');
        $mail->addCustomHeader('X-Mailer', 'Proteq Alert System');
        $mail->addCustomHeader('List-Unsubscribe', '<mailto:alerts.proteq@gmail.com>');
        $mail->addCustomHeader('Precedence', 'bulk');

        // Get location from coordinates
        $location = getLocationFromCoordinates($alertData['latitude'], $alertData['longitude']);
        
        // Format coordinates
        $coordinates = number_format($alertData['latitude'], 6) . ', ' . number_format($alertData['longitude'], 6);
        
        // Create Google Maps link
        $mapsLink = "https://www.google.com/maps?q={$alertData['latitude']},{$alertData['longitude']}";
        
        // Build HTML body
        $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
                <div style='background-color: #dc3545; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
                    <h2 style='color: #ffffff; margin: 0; font-size: 24px;'>🚨 Emergency Alert</h2>
                    <p style='color: #ffffff; margin: 10px 0 0 0; font-size: 16px;'>This is an automated emergency alert from the university.</p>
                </div>
                
                <div style='background-color: #fff; padding: 20px; border-radius: 5px; border: 1px solid #e0e0e0;'>
                    <h3 style='color: #333; margin-top: 0; font-size: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;'>Alert Details</h3>
                    <ul style='list-style: none; padding: 0; margin: 0;'>
                        <li style='margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;'><strong style='color: #555;'>Type:</strong> <span style='color: #dc3545; font-weight: bold;'>" . htmlspecialchars($alertData['alert_type']) . "</span></li>
                        <li style='margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;'><strong style='color: #555;'>Title:</strong> <span style='color: #333;'>" . htmlspecialchars($alertData['title']) . "</span></li>
                        <li style='margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;'><strong style='color: #555;'>Description:</strong> <span style='color: #333; line-height: 1.5;'>" . nl2br(htmlspecialchars($alertData['description'])) . "</span></li>
                        <li style='margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;'><strong style='color: #555;'>Location:</strong> <span style='color: #333;'>" . htmlspecialchars($location) . "</span></li>
                        <li style='margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;'><strong style='color: #555;'>Radius:</strong> <span style='color: #333;'>" . htmlspecialchars($alertData['radius_km']) . " km</span></li>
                        <li style='margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;'><strong style='color: #555;'>Coordinates:</strong> <span style='color: #333; font-family: monospace;'>" . htmlspecialchars($coordinates) . "</span></li>
                        <li style='margin-bottom: 0;'><strong style='color: #555;'>Map:</strong> <a href='" . $mapsLink . "' class='map-link' target='_blank' style='color: #007bff; text-decoration: none; font-weight: bold;'>View on Google Maps →</a></li>
                    </ul>
                </div>
                
                <div style='margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #dc3545;'>
                    <p style='margin: 0; color: #666; font-size: 14px; line-height: 1.5;'>⚠️ Please take necessary precautions and follow safety protocols. Stay safe and follow official instructions.</p>
                </div>
            </div>
        ";

        // Build plain text body
        $plainBody = "🚨 EMERGENCY ALERT\n\n" .
                     "This is an automated emergency alert from the university.\n\n" .
                     "ALERT DETAILS:\n" .
                     "=============\n" .
                     "Type: " . $alertData['alert_type'] . "\n" .
                     "Title: " . $alertData['title'] . "\n" .
                     "Description: " . $alertData['description'] . "\n" .
                     "Location: " . $location . "\n" .
                     "Radius: " . $alertData['radius_km'] . " km\n" .
                     "Coordinates: " . $coordinates . "\n" .
                     "Map: " . $mapsLink . "\n\n" .
                     "⚠️ Please take necessary precautions and follow safety protocols.\n" .
                     "Stay safe and follow official instructions.";

        $mail->Body = $htmlBody;
        $mail->AltBody = $plainBody;

        error_log("Attempting to send email to: " . $userEmail);
        error_log("Email configuration: " . print_r([
            'host' => $mail->Host,
            'port' => $mail->Port,
            'username' => $mail->Username,
            'secure' => $mail->SMTPSecure,
            'debug_level' => $mail->SMTPDebug,
            'timeout' => $mail->Timeout
        ], true));

        $mail->send();
        error_log("Email sent successfully to: " . $userEmail);
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed to " . $userEmail . ": " . $mail->ErrorInfo);
        error_log("Full error details: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}