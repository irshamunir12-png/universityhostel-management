-- Universal Hostel ERP - Database Export
-- University Hostel - Final Consolidated Database
-- Everything in one file for easy setup

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- 1. Roles Table
CREATE TABLE `sys_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_key` varchar(50) NOT NULL UNIQUE,
  `role_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `sys_roles` (`role_key`, `role_name`) VALUES
('super_admin', 'Super Admin'),
('warden', 'Hostel Warden'),
('student', 'Student');

-- 2. Users Table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `registration_no` varchar(50) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `identity_no` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Admin (Password: 123456)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`) VALUES
('System Admin', 'admin@hostel.com', '$2y$10$8WkSIdY3X6QpY1Y/5.G0uekF6qR4F9pY.A8nCjZzMhV6fG5C7Y5Q.', 'super_admin', 1);

-- Default Warden & Student (Password: 123456)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`) VALUES ('Main Warden', 'warden@hostel.com', '$2y$10$8WkSIdY3X6QpY1Y/5.G0uekF6qR4F9pY.A8nCjZzMhV6fG5C7Y5Q.', 'warden', 1);
INSERT INTO `users` (`name`, `email`, `password`, `role`, `registration_no`, `is_active`) VALUES ('Test Student', 'student@hostel.com', '$2y$10$8WkSIdY3X6QpY1Y/5.G0uekF6qR4F9pY.A8nCjZzMhV6fG5C7Y5Q.', 'student', 'ST-2024-001', 1);

-- 3. System Pages & Menu
CREATE TABLE `sys_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT 0,
  `page_name` varchar(100) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `icon_class` varchar(100) DEFAULT 'bi bi-circle',
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clear and Insert Organized Menu Data
TRUNCATE TABLE `sys_pages`;
INSERT INTO `sys_pages` (`id`, `parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(1, 0, 'Dashboard', 'index.php', 'bi bi-speedometer2', 1),
(2, 0, 'System Admin', '#', 'bi bi-gear-wide-connected', 10),
(3, 0, 'Hostel Operations', '#', 'bi bi-building-gear', 20),
(4, 0, 'Academics & Finance', '#', 'bi bi-bank', 30),
(5, 0, 'Helpdesk & Support', '#', 'bi bi-headset', 40),
(6, 0, 'Student Portal', '#', 'bi bi-backpack', 50),
(7, 0, 'Reports & Analytics', '#', 'bi bi-graph-up', 60);

-- Sub-pages for System Admin
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(2, 'Manage Users', 'dashboards/super_admin/manage_users.php', 'bi bi-people', 1),
(2, 'Manage Roles', 'dashboards/super_admin/manage_roles.php', 'bi bi-shield-lock', 2),
(2, 'Manage Pages', 'dashboards/super_admin/manage_pages.php', 'bi bi-layers', 3),
(2, 'System Settings', 'dashboards/super_admin/manage_settings.php', 'bi bi-sliders', 4),
(2, 'Deletion History', 'dashboards/super_admin/deletion_history.php', 'bi bi-clock-history', 5),
(2, 'Role Matrix', 'includes/manage_access.php', 'bi bi-grid-3x3', 6);

-- Sub-pages for Hostel Operations
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(3, 'Student Registration', 'dashboards/super_admin/student_registration.php', 'bi bi-person-plus', 1),
(3, 'Manage Rooms', 'dashboards/super_admin/manage_rooms.php', 'bi bi-building', 2),
(3, 'Allocate Rooms', 'dashboards/super_admin/allocate_rooms.php', 'bi bi-key', 3),
(3, 'Residency History', 'dashboards/super_admin/residency_history.php', 'bi bi-archive-fill', 4),
(3, 'Mark Attendance', 'dashboards/super_admin/mark_attendance.php', 'bi bi-calendar-check', 5),
(3, 'Gate Management', 'dashboards/super_admin/gate_management.php', 'bi bi-door-open', 6),
(3, 'Manage Mess', 'dashboards/super_admin/manage_mess.php', 'bi bi-egg-fried', 7),
(3, 'General Inventory', 'dashboards/super_admin/manage_inventory.php', 'bi bi-box-seam', 8);

-- Sub-pages for Academics & Finance
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(4, 'Manage Courses', 'dashboards/super_admin/manage_courses.php', 'bi bi-book', 1),
(4, 'Manage Fees', 'dashboards/super_admin/manage_fees.php', 'bi bi-cash', 2);

-- Sub-pages for Helpdesk & Support
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(5, 'Manage Complaints', 'dashboards/super_admin/manage_complaints.php', 'bi bi-exclamation-triangle', 1),
(5, 'Manage Announcements', 'dashboards/super_admin/manage_announcements.php', 'bi bi-megaphone', 2),
(5, 'Manage Leaves', 'dashboards/super_admin/manage_leaves.php', 'bi bi-calendar-x', 3),
(5, 'Manage Disputes', 'dashboards/super_admin/manage_disputes.php', 'bi bi-shield-exclamation', 4);

-- Sub-pages for Reports
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(7, 'General Reports', 'dashboards/super_admin/reports.php', 'bi bi-file-earmark-bar-graph', 1);

-- Sub-pages for Student Portal
INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(6, 'My Room', 'student/my_room.php', 'bi bi-house-door', 1),
(6, 'My Fees', 'student/my_fees.php', 'bi bi-wallet2', 2),
(6, 'My Courses', 'student/my_courses.php', 'bi bi-journal-check', 3),
(6, 'My Complaints', 'student/my_complaints.php', 'bi bi-chat-left-text', 4),
(6, 'Notice Board', 'student/announcements.php', 'bi bi-pin-angle', 5),
(6, 'Mess Menu', 'student/mess_menu.php', 'bi bi-cup-hot', 6),
(6, 'Apply Leave', 'student/apply_leave.php', 'bi bi-calendar-plus', 7);

-- 4. Access Matrix
CREATE TABLE `role_access` (
  `role_key` varchar(50) NOT NULL,
  `page_id` int(11) NOT NULL,
  PRIMARY KEY (`role_key`,`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Restore Access Permissions
TRUNCATE TABLE `role_access`;

-- Super Admin permissions (Everything except Student Portal internals)
INSERT INTO `role_access` (`role_key`, `page_id`) 
SELECT 'super_admin', id FROM sys_pages WHERE id != 6 AND parent_id != 6;

-- Student permissions (Dashboard + Student Portal)
INSERT INTO `role_access` (`role_key`, `page_id`) VALUES ('student', 1), ('student', 6);
INSERT INTO `role_access` (`role_key`, `page_id`) SELECT 'student', id FROM sys_pages WHERE parent_id = 6;

-- Warden permissions (Dashboard + Hostel Ops + Helpdesk + Reports)
INSERT INTO `role_access` (`role_key`, `page_id`) VALUES ('warden', 1), ('warden', 3), ('warden', 5), ('warden', 7);
INSERT INTO `role_access` (`role_key`, `page_id`) SELECT 'warden', id FROM sys_pages WHERE parent_id IN (3, 5, 7);

-- 5. Rooms Table
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `building` varchar(100) NOT NULL,
  `block` varchar(50) DEFAULT NULL,
  `room_no` varchar(50) NOT NULL,
  `room_type` enum('student','staff','office') DEFAULT 'student',
  `capacity` int(11) DEFAULT 1,
  `gender` varchar(20) DEFAULT 'Any',
  `washroom_type` varchar(50) DEFAULT 'common',
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Room Allocations
CREATE TABLE `room_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `bed_no` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Student Fees
CREATE TABLE `student_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Unpaid',
  `payment_receipt` varchar(255) DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Complaints
CREATE TABLE `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Low',
  `status` enum('pending','in_progress','resolved','rejected') DEFAULT 'pending',
  `sla_breached` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7.1 Complaint Categories (For SLA Logic)
CREATE TABLE `complaint_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `sla_hours` int(11) DEFAULT 24,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `complaint_categories` (category_name, sla_hours) VALUES ('Plumbing', 12), ('Electrical', 6), ('Internet', 4), ('Other', 48);

-- 9. Inventory & Assets
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `location` varchar(255) DEFAULT NULL,
  `item_condition` varchar(50) DEFAULT 'Good',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `asset_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_name` varchar(100) NOT NULL UNIQUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `asset_categories` (category_name) VALUES ('Bedding'), ('Furniture'), ('Electronics');

CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_name` varchar(255) NOT NULL,
  `asset_tag` varchar(100) NOT NULL UNIQUE,
  `category_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'available',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `asset_allocations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `asset_id` int(11) NOT NULL,
    `allocated_to_type` enum('student', 'room') NOT NULL,
    `allocated_to_id` int(11) NOT NULL,
    `issue_date` date NOT NULL,
    `return_date` date DEFAULT NULL,
    `condition_on_issue` varchar(100),
    `condition_on_return` varchar(100),
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Gate Logs
CREATE TABLE `gate_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `log_type` enum('in','out') NOT NULL,
  `is_late` tinyint(1) DEFAULT 0,
  `log_time` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10.1 Visitors
CREATE TABLE `visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `visitor_cnic` varchar(20) DEFAULT NULL,
  `check_in` timestamp DEFAULT CURRENT_TIMESTAMP,
  `check_out` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Mess Menu
CREATE TABLE `mess_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `day_of_week` varchar(20) NOT NULL UNIQUE,
  `breakfast` text DEFAULT NULL,
  `lunch` text DEFAULT NULL,
  `dinner` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11.1 Mess Special Items
CREATE TABLE IF NOT EXISTS `mess_special_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `available_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11.2 Mess Special Orders
CREATE TABLE IF NOT EXISTS `mess_special_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `ordered_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. System Settings
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('system_name', 'University Hostel'),
('footer_text', 'Copyright © 2024 University Hostel. All rights reserved.'),
('curfew_time', '22:00:00'),
('currency_symbol', 'PKR');

-- 13. Attendance
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Leave') NOT NULL DEFAULT 'Present',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Notifications
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14.1 Student Leaves
CREATE TABLE `student_leaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 15. Student Profiles
CREATE TABLE `student_profiles` (
  `user_id` int(11) NOT NULL UNIQUE,
  `phone` varchar(25) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Announcements
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16.1 Courses Table
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL UNIQUE,
  `course_name` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `category_id` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16.2 Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) NOT NULL UNIQUE,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Dispute Reports
CREATE TABLE `dispute_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reporting_user_id` int(11) NOT NULL,
  `reported_user_id` int(11) NOT NULL,
  `reason_category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','warning_issued','resolved','closed') DEFAULT 'open',
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17.1 Student Warnings
CREATE TABLE `student_warnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dispute_id` int(11) DEFAULT NULL,
  `warning_text` text NOT NULL,
  `issued_by_id` int(11) NOT NULL,
  `issued_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;