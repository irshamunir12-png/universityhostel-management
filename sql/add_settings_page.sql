-- 1. Create settings table and add default values
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
);

-- Insert default settings if they don't exist
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
('system_name', 'Universal Hostel System'),
('footer_text', 'Copyright © 2024. All rights reserved.');

-- 2. Register the new settings page under 'System Admin' (parent_id = 2)
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (2, 'System Settings', 'dashboards/super_admin/manage_settings.php', 'bi bi-sliders', 4);

-- 3. Give Permissions to Super Admin
INSERT INTO role_access (role_key, page_id) 
SELECT 'super_admin', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_settings.php';