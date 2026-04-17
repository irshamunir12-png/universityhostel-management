-- Add soft delete columns to room_allocations table
ALTER TABLE room_allocations ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN deleted_at DATETIME DEFAULT NULL;