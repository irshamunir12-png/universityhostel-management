-- 1. Table for Room Change Requests
CREATE TABLE IF NOT EXISTS room_change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_room_id INT,
    reason_category ENUM('Roommate Issue', 'Medical Issue', 'Noise / Disturbance', 'Other') NOT NULL,
    description TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Add Pages to System Menu
INSERT INTO sys_pages (page_name, page_url, icon_class, sort_order) VALUES 
('Room Change Requests', 'dashboards/super_admin/manage_room_requests.php', 'bi bi-arrow-repeat', 45),
('Request Room Change', 'student/request_room_change.php', 'bi bi-arrow-repeat', 106);

-- 3. Grant Access
SET @admin_page = (SELECT id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_room_requests.php' LIMIT 1);
SET @student_page = (SELECT id FROM sys_pages WHERE page_url = 'student/request_room_change.php' LIMIT 1);

INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', @admin_page);
INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('student', @student_page);