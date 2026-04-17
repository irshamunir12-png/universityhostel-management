-- Creates rooms and room_allocations tables for University Hostel Management System

CREATE TABLE IF NOT EXISTS rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  building VARCHAR(100),
  block VARCHAR(50),
  room_no VARCHAR(50) NOT NULL,
  capacity INT NOT NULL DEFAULT 1,
  gender CHAR(1) DEFAULT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  notes TEXT,
  UNIQUE KEY uq_room_no (room_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS room_allocations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  room_id INT NOT NULL,
  bed_no VARCHAR(50),
  start_date DATE,
  end_date DATE,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_room (room_id),
  INDEX idx_user (user_id),
  CONSTRAINT fk_alloc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_alloc_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
