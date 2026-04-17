-- 1. Hide Student Portal from Super Admin Sidebar
DELETE ra FROM role_access ra 
JOIN sys_pages p ON ra.page_id = p.id 
WHERE ra.role_key = 'super_admin' AND (p.id = 6 OR p.parent_id = 6);

-- 2. Add Target User to Announcements (For Private Notices)
ALTER TABLE announcements ADD COLUMN target_user_id INT DEFAULT NULL AFTER user_id;