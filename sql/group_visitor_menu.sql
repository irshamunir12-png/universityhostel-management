-- 1. Create new Parent Category 'Visitor Management'
INSERT INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (7, 0, 'Visitor Management', '#', 'bi bi-people-fill', 25);

-- 2. Move 'Visitor Log' to this new parent
UPDATE sys_pages SET parent_id = 7 WHERE page_url = 'dashboards/super_admin/manage_visitors.php';

-- 3. Move 'Guest Stay Requests' to this new parent
UPDATE sys_pages SET parent_id = 7 WHERE page_url = 'dashboards/super_admin/manage_guest_stays.php';

-- 4. Grant access to new parent
INSERT INTO role_access (role_key, page_id) VALUES ('super_admin', 7);
INSERT INTO role_access (role_key, page_id) VALUES ('warden', 7);