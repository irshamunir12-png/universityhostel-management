-- 1. Create the new 'departments' table with soft delete columns
CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Update the 'sys_pages' table to replace 'Manage Courses' with 'Manage Departments'
-- This keeps the same page ID and permissions, just points to a new file and name.
UPDATE sys_pages 
SET 
    page_name = 'Manage Departments', 
    page_url = 'dashboards/super_admin/manage_departments.php',
    icon_class = 'bi bi-diagram-3'
WHERE 
    page_url = 'dashboards/super_admin/manage_courses.php';