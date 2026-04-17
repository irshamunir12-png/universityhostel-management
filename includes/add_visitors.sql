-- 1. Create Visitors Table
CREATE TABLE IF NOT EXISTS visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(100) NOT NULL,
    visitor_phone VARCHAR(20),
    student_id INT,
    relation VARCHAR(50),
    purpose VARCHAR(255),
    check_in DATETIME DEFAULT CURRENT_TIMESTAMP,
    check_out DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 2. Register Page in Sidebar (Under Hostel Operations - ID 3)
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (3, 'Visitor Log', 'dashboards/super_admin/manage_visitors.php', 'bi bi-person-badge', 6);

-- 3. Permissions
INSERT INTO role_access (role_key, page_id) 
SELECT 'super_admin', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_visitors.php';

INSERT INTO role_access (role_key, page_id) 
SELECT 'warden', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_visitors.php';