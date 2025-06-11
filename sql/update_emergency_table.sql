-- Add resolution-related columns to emergencies table
ALTER TABLE emergencies
ADD COLUMN resolution_reason TEXT DEFAULT NULL AFTER is_active,
ADD COLUMN resolved_by INT DEFAULT NULL AFTER resolution_reason,
ADD COLUMN resolved_at DATETIME DEFAULT NULL AFTER resolved_by,
ADD FOREIGN KEY (resolved_by) REFERENCES admin(admin_id) ON DELETE SET NULL;

-- Add index for resolved_at for better query performance
CREATE INDEX idx_emergency_resolved_at ON emergencies(resolved_at); 