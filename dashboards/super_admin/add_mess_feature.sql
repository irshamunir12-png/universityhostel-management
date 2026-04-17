-- 1. Create Mess Menu Table
CREATE TABLE IF NOT EXISTS mess_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week VARCHAR(20) NOT NULL UNIQUE,
    breakfast TEXT,
    lunch TEXT,
    dinner TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Insert Default Days (Empty Menu)
INSERT IGNORE INTO mess_menu (day_of_week) VALUES 
('Monday'), ('Tuesday'), ('Wednesday'), ('Thursday'), ('Friday'), ('Saturday'), ('Sunday');

-- 3. Register Pages in System
INSERT INTO sys_pages (page_name, page_url, icon_class, sort_order) VALUES 
('Mess Management', 'dashboards/super_admin/manage_mess.php', 'bi bi-egg-fried', 95),
('Weekly Menu', 'student/mess_menu.php', 'bi bi-egg-fried', 115);

-- 4. Grant Access
INSERT INTO role_access (role_key, page_id) SELECT 'super_admin', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_mess.php';
INSERT INTO role_access (role_key, page_id) SELECT 'warden', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_mess.php';
INSERT INTO role_access (role_key, page_id) SELECT 'student', id FROM sys_pages WHERE page_url = 'student/mess_menu.php';