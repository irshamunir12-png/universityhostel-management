-- 1. Add the new page for students under 'Student Portal' (parent_id = 6)
-- Note: We assume parent_id for 'Student Portal' is 6 from previous scripts.
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) 
VALUES (6, 'Mess Menu', 'student/mess_menu.php', 'bi bi-cup-hot', 6);

-- 2. Grant access to the 'student' role for the new page
INSERT INTO role_access (role_key, page_id) 
SELECT 'student', id FROM sys_pages WHERE page_url = 'student/mess_menu.php';