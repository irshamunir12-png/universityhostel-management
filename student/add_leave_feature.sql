-- 1. Create Leaves Table
CREATE TABLE IF NOT EXISTS student_leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL, -- e.g., Night Out, Home Visit, Emergency
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Register Pages
INSERT INTO sys_pages (page_name, page_url, icon_class, sort_order) VALUES 
('Leave Applications', 'dashboards/super_admin/manage_leaves.php', 'bi bi-send', 85),
('Apply for Leave', 'student/my_leaves.php', 'bi bi-send', 112);

-- 3. Permissions
INSERT INTO role_access (role_key, page_id) SELECT 'super_admin', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_leaves.php';
INSERT INTO role_access (role_key, page_id) SELECT 'warden', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_leaves.php';
INSERT INTO role_access (role_key, page_id) SELECT 'student', id FROM sys_pages WHERE page_url = 'student/my_leaves.php';