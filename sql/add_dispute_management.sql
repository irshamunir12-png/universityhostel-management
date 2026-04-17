-- 1. Table for Dispute Reports
CREATE TABLE IF NOT EXISTS dispute_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporting_user_id INT NOT NULL,
    reported_user_id INT NOT NULL,
    reason_category VARCHAR(100),
    description TEXT,
    status ENUM('open', 'warning_issued', 'resolved', 'closed') DEFAULT 'open',
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporting_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Table for Student Warnings
CREATE TABLE IF NOT EXISTS student_warnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dispute_id INT,
    warning_text TEXT,
    issued_by_id INT,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dispute_id) REFERENCES dispute_reports(id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. Add Pages to System Menu
INSERT INTO sys_pages (page_name, page_url, icon_class, sort_order) VALUES 
('Manage Disputes', 'dashboards/super_admin/manage_disputes.php', 'bi bi-shield-exclamation', 46),
('Report Roommate', 'student/report_dispute.php', 'bi bi-flag', 107);

-- 4. Grant Access
SET @admin_page = (SELECT id FROM sys_pages WHERE page_url = 'dashboards/super_admin/manage_disputes.php' LIMIT 1);
SET @student_page = (SELECT id FROM sys_pages WHERE page_url = 'student/report_dispute.php' LIMIT 1);

INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', @admin_page), ('warden', @admin_page);
INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('student', @student_page);