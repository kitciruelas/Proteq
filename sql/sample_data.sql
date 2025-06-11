-- Insert a sample user
INSERT INTO general_users (first_name, last_name, user_type, password, email, department, college) 
VALUES ('John', 'Doe', 'STUDENT', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john.doe@example.com', 'Computer Science', 'College of Engineering');

-- Insert user location (Batangas City coordinates)
INSERT INTO user_locations (user_id, latitude, longitude) 
VALUES (LAST_INSERT_ID(), 13.7563, 121.0583);

-- Insert another sample user
INSERT INTO general_users (first_name, last_name, user_type, password, email, department, college) 
VALUES ('Jane', 'Smith', 'FACULTY', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane.smith@example.com', 'Information Technology', 'College of Engineering');

-- Insert user location (near Batangas City)
INSERT INTO user_locations (user_id, latitude, longitude) 
VALUES (LAST_INSERT_ID(), 13.7600, 121.0600); 