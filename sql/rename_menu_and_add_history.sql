-- 1. Rename Menu Item
UPDATE sys_pages SET page_name = 'Hostel Finance' WHERE id = 4;

-- 2. Add Soft Delete columns to tables
ALTER TABLE rooms ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE rooms ADD COLUMN deleted_at DATETIME DEFAULT NULL;

ALTER TABLE announcements ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE announcements ADD COLUMN deleted_at DATETIME DEFAULT NULL;

-- 3. Register Deletion History Page (Under System Admin - ID 2)
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (2, 'Deletion History', 'dashboards/super_admin/deletion_history.php', 'bi bi-clock-history', 5);

-- 4. Give Permissions to Super Admin
INSERT INTO role_access (role_key, page_id) 
SELECT 'super_admin', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/deletion_history.php';