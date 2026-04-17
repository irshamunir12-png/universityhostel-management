-- 1. Table for Campaigns (e.g., Water Cooler Fund)
CREATE TABLE IF NOT EXISTS community_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'completed', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table for Student Contributions
CREATE TABLE IF NOT EXISTS campaign_contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    user_id INT,
    amount DECIMAL(10,2),
    status ENUM('pending', 'verified') DEFAULT 'pending',
    contributed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES community_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Add Pages to System Menu
INSERT INTO sys_pages (page_name, page_url, icon_class, sort_order) VALUES 
('Community Funds', 'dashboards/super_admin/manage_community.php', 'bi bi-piggy-bank', 95),
('Community Funds', 'student/community.php', 'bi bi-heart-fill', 140);

-- 4. Grant Access
SET @admin_page = (SELECT id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_community.php' LIMIT 1);
SET @student_page = (SELECT id FROM sys_pages WHERE page_url = 'student/community.php' LIMIT 1);

INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', @admin_page);
INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('student', @student_page);