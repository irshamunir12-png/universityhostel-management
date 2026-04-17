-- 1. Create new Parent Category 'Reports & Analytics'
-- Using a high ID to avoid conflicts
INSERT INTO sys_pages (id, page_name, parent_id, page_url, icon_class, sort_order) 
VALUES (50, 'Reports & Analytics', 0, '#', 'bi bi-graph-up', 40);

-- 2. Get the ID of the new parent
SET @parent_id = LAST_INSERT_ID();

-- 3. Create the 'General Reports' page under the new parent
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (@parent_id, 'General Reports', 'dashboards/super_admin/reports.php', 'bi bi-file-earmark-bar-graph', 1);

-- 4. Grant access to new parent and child page for admin/warden
SET @page_id = LAST_INSERT_ID();
INSERT INTO role_access (role_key, page_id) VALUES ('super_admin', @parent_id), ('warden', @parent_id);
INSERT INTO role_access (role_key, page_id) VALUES ('super_admin', @page_id), ('warden', @page_id);