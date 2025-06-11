-- Create emergencies table
CREATE TABLE IF NOT EXISTS emergencies (
    emergency_id INT NOT NULL AUTO_INCREMENT,
    emergency_type ENUM('FIRE', 'EARTHQUAKE', 'TYPHOON', 'OTHER') NOT NULL,
    description TEXT DEFAULT NULL,
    triggered_by INT DEFAULT NULL,
    triggered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    PRIMARY KEY (emergency_id),
    FOREIGN KEY (triggered_by) REFERENCES admin(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create welfare_checks table
CREATE TABLE IF NOT EXISTS welfare_checks (
    welfare_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    emergency_id INT NOT NULL,
    status ENUM('SAFE', 'NEEDS_HELP', 'NO_RESPONSE') DEFAULT 'NO_RESPONSE',
    remarks TEXT DEFAULT NULL,
    reported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (welfare_id),
    FOREIGN KEY (user_id) REFERENCES general_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (emergency_id) REFERENCES emergencies(emergency_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create protocol_responses table
CREATE TABLE IF NOT EXISTS protocol_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    protocol_id INT NOT NULL,
    incident_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('acknowledged', 'in_progress', 'completed') DEFAULT 'acknowledged',
    notes TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (protocol_id) REFERENCES safety_protocols(protocol_id) ON DELETE CASCADE,
    FOREIGN KEY (incident_id) REFERENCES emergencies(emergency_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES general_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for better query performance
CREATE INDEX idx_emergency_active ON emergencies(is_active);
CREATE INDEX idx_emergency_type ON emergencies(emergency_type);
CREATE INDEX idx_welfare_emergency ON welfare_checks(emergency_id);
CREATE INDEX idx_welfare_user ON welfare_checks(user_id);
CREATE INDEX idx_welfare_status ON welfare_checks(status);
CREATE INDEX idx_protocol_response_protocol ON protocol_responses(protocol_id);
CREATE INDEX idx_protocol_response_incident ON protocol_responses(incident_id);
CREATE INDEX idx_protocol_response_user ON protocol_responses(user_id);
CREATE INDEX idx_protocol_response_status ON protocol_responses(status); 