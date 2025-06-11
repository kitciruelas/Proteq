-- Staff table with availability and role
CREATE TABLE staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role ENUM('nurse', 'security', 'fire_responder', 'medical_responder', 'general_staff') NOT NULL,
    availability ENUM('available', 'busy', 'off_duty') NOT NULL DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Staff location tracking
CREATE TABLE staff_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
);

-- Staff notifications
CREATE TABLE staff_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    incident_id INT,
    message TEXT NOT NULL,
    type ENUM('assignment', 'alert', 'update', 'announcement') NOT NULL,
    priority ENUM('low', 'moderate', 'high', 'critical') NOT NULL DEFAULT 'moderate',
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (incident_id) REFERENCES incident_reports(incident_id) ON DELETE SET NULL
);

-- Staff incident responses
CREATE TABLE staff_incident_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    staff_id INT NOT NULL,
    status ENUM('acknowledged', 'in_progress', 'resolved') NOT NULL,
    notes TEXT,
    response_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incident_reports(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
);

-- Staff incident attachments
CREATE TABLE staff_incident_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    staff_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incident_reports(incident_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE
);

-- Staff messages
CREATE TABLE staff_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES staff(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES staff(staff_id) ON DELETE CASCADE
); 