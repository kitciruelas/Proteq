-- Create resources table
CREATE TABLE IF NOT EXISTS resources (
    resource_id INT PRIMARY KEY AUTO_INCREMENT,
    center_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (center_id) REFERENCES evacuation_centers(center_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for faster resource type queries
CREATE INDEX idx_resource_type ON resources(type);

-- Add index for faster center-based queries
CREATE INDEX idx_resource_center ON resources(center_id); 