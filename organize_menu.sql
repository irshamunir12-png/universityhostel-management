-- 1. Purana data saaf karein
-- 1. Purana data saaf karein (Clean slate)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE role_access;
TRUNCATE TABLE sys_pages;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. Parent Categories Banayen (Main Headings)
INSERT INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) VALUES
(1, 0, 'Dashboard', 'index.php', 'bi bi-speedometer2', 1),
(2, 0, 'System Admin', '#', 'bi bi-gear-wide-connected', 10),
(3, 0, 'Hostel Operations', '#', 'bi bi-building-gear', 20),
(4, 0, 'Academics & Finance', '#', 'bi bi-bank', 30),
(5, 0, 'Helpdesk & Support', '#', 'bi bi-headset', 40),
(6, 0, 'Student Portal', '#', 'bi bi-backpack', 50),
(7, 0, 'Reports & Analytics', '#', 'bi bi-graph-up', 60);

-- 3. Child Pages Insert Karein (Sub-menus)

-- System Admin (ID 2)
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES
(2, 'Manage Users', 'dashboards/super_admin/manage_users.php', 'bi bi-people', 1),
(2, 'Manage Roles', 'dashboards/super_admin/manage_roles.php', 'bi bi-shield-lock', 2),
(2, 'Manage Pages', 'dashboards/super_admin/manage_pages.php', 'bi bi-layers', 3),
(2, 'System Settings', 'dashboards/super_admin/manage_settings.php', 'bi bi-sliders', 4),
(2, 'Deletion History', 'dashboards/super_admin/deletion_history.php', 'bi bi-clock-history', 5),
(2, 'Role Matrix', 'includes/manage_access.php', 'bi bi-grid-3x3', 6);

-- Hostel Operations (ID 3)
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES
(3, 'Student Registration', 'dashboards/super_admin/student_registration.php', 'bi bi-person-plus', 1),
(3, 'Manage Rooms', 'dashboards/super_admin/manage_rooms.php', 'bi bi-building', 2),
(3, 'Allocate Rooms', 'dashboards/super_admin/allocate_rooms.php', 'bi bi-key', 3),
(3, 'Mark Attendance', 'dashboards/super_admin/mark_attendance.php', 'bi bi-calendar-check', 4),
(3, 'Gate Management', 'dashboards/super_admin/gate_management.php', 'bi bi-door-open', 5),
(3, 'Manage Mess', 'dashboards/super_admin/manage_mess.php', 'bi bi-egg-fried', 6),
(3, 'General Inventory', 'dashboards/super_admin/manage_inventory.php', 'bi bi-box-seam', 7);

-- Academics & Finance (ID 4)
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES
(4, 'Manage Courses', 'dashboards/super_admin/manage_courses.php', 'bi bi-book', 1),
(4, 'Manage Fees', 'dashboards/super_admin/manage_fees.php', 'bi bi-cash', 2);

-- Helpdesk & Support (ID 5)
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES
(5, 'Manage Complaints', 'dashboards/super_admin/manage_complaints.php', 'bi bi-exclamation-triangle', 1),
(5, 'Manage Announcements', 'dashboards/super_admin/manage_announcements.php', 'bi bi-megaphone', 2),
(5, 'Manage Leaves', 'dashboards/super_admin/manage_leaves.php', 'bi bi-calendar-x', 3),
(5, 'Manage Disputes', 'dashboards/super_admin/manage_disputes.php', 'bi bi-shield-exclamation', 4);

-- Reports & Analytics (ID 7)
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES
(7, 'General Reports', 'dashboards/super_admin/reports.php', 'bi bi-file-earmark-bar-graph', 1);

-- Student Portal (ID 6)
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES
(6, 'My Room', 'student/my_room.php', 'bi bi-house-door', 1),
(6, 'My Fees', 'student/my_fees.php', 'bi bi-wallet2', 2),
(6, 'My Courses', 'student/my_courses.php', 'bi bi-journal-check', 3),
(6, 'My Complaints', 'student/my_complaints.php', 'bi bi-chat-left-text', 4),
(6, 'Notice Board', 'student/announcements.php', 'bi bi-pin-angle', 5),
(6, 'Mess Menu', 'student/mess_menu.php', 'bi bi-cup-hot', 6);

-- 4. Permissions Wapis Dein (Restore Access)

-- Super Admin ko sab kuch (Student Portal ke ilawa)
INSERT INTO role_access (role_key, page_id) 
SELECT 'super_admin', id FROM sys_pages WHERE id != 6 AND parent_id != 6;

-- Student ko sirf Dashboard aur Student Portal
INSERT INTO role_access (role_key, page_id) VALUES ('student', 1), ('student', 6);
INSERT INTO role_access (role_key, page_id) SELECT 'student', id FROM sys_pages WHERE parent_id = 6;

-- Warden ko Dashboard + Hostel Ops + Helpdesk + Reports
INSERT INTO role_access (role_key, page_id) VALUES ('warden', 1), ('warden', 3), ('warden', 5), ('warden', 7);
INSERT INTO role_access (role_key, page_id) SELECT 'warden', id FROM sys_pages WHERE parent_id IN (3, 5, 7);