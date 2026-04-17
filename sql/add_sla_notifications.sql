-- 1. Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255),
    message TEXT,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Complaint Categories with SLA (Time Limit in Hours)
CREATE TABLE IF NOT EXISTS complaint_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sla_hours INT DEFAULT 24
);

INSERT IGNORE INTO complaint_categories (name, sla_hours) VALUES 
('Electricity / Power', 24),
('Plumbing / Water', 24),
('Internet / Wi-Fi', 48),
('Furniture / Carpentry', 72),
('Cleanliness / Hygiene', 12),
('Other', 48);

-- 3. Update Complaints Table (Run these lines manually if needed)
-- ALTER TABLE complaints ADD COLUMN category_id INT DEFAULT NULL;
-- ALTER TABLE complaints ADD COLUMN sla_breached TINYINT(1) DEFAULT 0;
-- Note: If you run this file as a whole, ensure your SQL client handles ALTERs gracefully.