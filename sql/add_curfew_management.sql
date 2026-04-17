-- 1. Table for Gate Logs
CREATE TABLE IF NOT EXISTS gate_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    log_type ENUM('in', 'out') NOT NULL,
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_late TINYINT(1) DEFAULT 0,
    remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add Curfew Setting (Default 10 PM)
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('curfew_time', '22:00:00');

-- 3. Add Pages to System Menu
INSERT INTO sys_pages (page_name, page_url, icon_class, sort_order) VALUES 
('Gate Management', 'dashboards/super_admin/gate_management.php', 'bi bi-door-closed-fill', 47),
('My Gate Log', 'student/my_gate_log.php', 'bi bi-clock-history', 108);

-- 4. Grant Access
SET @admin_page = (SELECT id FROM sys_pages WHERE page_url = 'dashboards/super_admin/gate_management.php' LIMIT 1);
SET @student_page = (SELECT id FROM sys_pages WHERE page_url = 'student/my_gate_log.php' LIMIT 1);

INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', @admin_page), ('warden', @admin_page);
INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('student', @student_page);