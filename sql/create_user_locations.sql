CREATE TABLE user_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES general_users(user_id) ON DELETE CASCADE
);

-- Add index for faster location-based queries
CREATE INDEX idx_user_locations_coords ON user_locations(latitude, longitude);