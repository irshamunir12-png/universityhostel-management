-- Table for Inventory Items
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL, -- e.g. Furniture, Electrical, Plumbing
    quantity INT NOT NULL DEFAULT 0,
    location VARCHAR(100) DEFAULT 'Store Room',
    item_condition ENUM('New', 'Good', 'Repair Needed', 'Damaged') DEFAULT 'Good',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add Page to Sidebar (under Hostel Operations)
SET @parent_id = (SELECT id FROM sys_pages WHERE page_name = 'Hostel Operations' LIMIT 1);
INSERT INTO sys_pages (parent_id, page_name, page_url, icon_class, sort_order) VALUES (@parent_id, 'Inventory / Assets', 'dashboards/super_admin/manage_inventory.php', 'bi bi-box-seam', 5);

-- Grant Access
INSERT INTO role_access (role_key, page_id) SELECT 'super_admin', id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_inventory.php';