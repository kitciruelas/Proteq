<?php
require_once '../../includes/db.php';

// Create staff table
$sql = "CREATE TABLE IF NOT EXISTS staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('nurse', 'paramedic', 'security', 'firefighter', 'others') NOT NULL,
    availability ENUM('available', 'busy', 'off-duty') NOT NULL DEFAULT 'available',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Staff table created successfully";
} else {
    echo "Error creating staff table: " . $conn->error;
}

$conn->close();
?> 