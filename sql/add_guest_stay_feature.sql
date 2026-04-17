-- Table for Guest Stay Requests
CREATE TABLE IF NOT EXISTS visitor_stay_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    visitor_name VARCHAR(100) NOT NULL,
    visitor_cnic VARCHAR(50),
    relation VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add Pages to System
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES
(3, 'Guest Stay Requests', 'dashboards/super_admin/manage_guest_stays.php', 'bi bi-house-heart', 6),
(6, 'Book Guest Room', 'student/book_guest_room.php', 'bi bi-people-fill', 6);

-- Grant Permissions (Super Admin & Student)
INSERT INTO role_access (role_key, page_id) SELECT 'super_admin', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_guest_stays.php';
INSERT INTO role_access (role_key, page_id) SELECT 'student', id FROM sys_pages WHERE page_url = 'student/book_guest_room.php';