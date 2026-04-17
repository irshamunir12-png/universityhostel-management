-- 1. Register Page
INSERT INTO sys_pages (page_name, page_url, icon_class, sort_order) VALUES 
('Mark Attendance', 'dashboards/super_admin/mark_attendance.php', 'bi bi-calendar-check', 92);

-- 2. Give Permissions
INSERT INTO role_access (role_key, page_id) SELECT 'super_admin', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/mark_attendance.php';
INSERT INTO role_access (role_key, page_id) SELECT 'warden', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/mark_attendance.php';