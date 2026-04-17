-- 1. Find the parent_id of the existing room management pages
SET @parent_id = (SELECT parent_id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_rooms.php' LIMIT 1);

-- 2. Register the new page under the same parent
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (@parent_id, 'Residency History', 'dashboards/super_admin/residency_history.php', 'bi bi-archive-fill', 4);

-- 3. Give Permissions to Super Admin and Warden
-- Get the ID of the newly inserted page
SET @page_id = LAST_INSERT_ID();

-- Grant access
INSERT INTO role_access (role_key, page_id) VALUES ('super_admin', @page_id);
INSERT INTO role_access (role_key, page_id) VALUES ('warden', @page_id);