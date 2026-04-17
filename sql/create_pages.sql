CREATE TABLE IF NOT EXISTS sys_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT DEFAULT 0,
    page_name VARCHAR(100),
    page_url VARCHAR(255),
    icon_class VARCHAR(50),
    sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS role_access (
    role_key VARCHAR(50),
    page_id INT
);

-- Purana data saaf karein taake duplicate na ho
TRUNCATE TABLE sys_pages;
TRUNCATE TABLE role_access;

-- Pages Insert Karein
INSERT INTO sys_pages (page_name, page_url, icon_class, sort_order) VALUES 
('Manage Users', 'dashboards/super_admin/manage_users.php', 'bi bi-people', 10),
('Student Registration', 'dashboards/super_admin/student_registration.php', 'bi bi-person-plus', 15),
('Manage Roles', 'dashboards/super_admin/manage_roles.php', 'bi bi-shield-lock', 20),
('Manage Pages', 'dashboards/super_admin/manage_pages.php', 'bi bi-layers', 30),
('Manage Rooms', 'dashboards/super_admin/manage_rooms.php', 'bi bi-building', 40),
('Allocate Rooms', 'dashboards/super_admin/allocate_rooms.php', 'bi bi-key', 50),
('Manage Courses', 'dashboards/super_admin/manage_courses.php', 'bi bi-book', 60),
('Manage Fees', 'dashboards/super_admin/manage_fees.php', 'bi bi-cash', 70),
('Manage Complaints', 'dashboards/super_admin/manage_complaints.php', 'bi bi-exclamation-triangle', 80),
('Manage Announcements', 'dashboards/super_admin/manage_announcements.php', 'bi bi-megaphone', 90),
('My Fees', 'student/my_fees.php', 'bi bi-wallet2', 100),
('My Room', 'student/my_room.php', 'bi bi-house-door', 105),
('My Complaints', 'student/my_complaints.php', 'bi bi-chat-left-text', 110),
('My Courses', 'student/my_courses.php', 'bi bi-journal-check', 120),
('Notice Board', 'student/announcements.php', 'bi bi-pin-angle', 130);

-- Super Admin ko Access dein
INSERT INTO role_access (role_key, page_id) SELECT 'super_admin', id FROM sys_pages WHERE page_url LIKE 'dashboards/super_admin/%';

-- Student ko Access dein
INSERT INTO role_access (role_key, page_id) SELECT 'student', id FROM sys_pages WHERE page_url LIKE 'student/%';