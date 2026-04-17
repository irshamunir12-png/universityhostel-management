-- Add 'room_type' column to rooms table
ALTER TABLE rooms ADD COLUMN room_type ENUM('student', 'office', 'staff') DEFAULT 'student' AFTER room_no;