-- 1. Get Parent ID for 'Hostel Operations'
SET @parent_id = (SELECT id FROM sys_pages WHERE page_name = 'Hostel Operations' LIMIT 1);

-- 2. Add 'Student Registration' Page if it doesn't exist
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
SELECT @parent_id, 'Student Registration', 'dashboards/super_admin/student_registration.php', 'bi bi-person-plus', 1
WHERE NOT EXISTS (SELECT 1 FROM sys_pages WHERE page_url = 'dashboards/super_admin/student_registration.php');

-- 3. Grant Permission to Super Admin
SET @page_id = (SELECT id FROM sys_pages WHERE page_url = 'dashboards/super_admin/student_registration.php');

INSERT INTO role_access (role_key, page_id) 
SELECT 'super_admin', @page_id
WHERE NOT EXISTS (SELECT 1 FROM role_access WHERE role_key = 'super_admin' AND page_id = @page_id);