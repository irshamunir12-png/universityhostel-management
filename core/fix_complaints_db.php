<?php
require_once '../../core/db.php';

echo "<pre style='font-family: monospace; background: #333; color: #0f0; padding: 20px; border-radius: 5px; line-height: 1.6;'>";

try {
    $pdo->beginTransaction();

    echo ">> Fixing Complaints System...\n";

    // 1. Create complaint_categories table
    echo "  - Creating 'complaint_categories' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS complaint_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        sla_hours INT DEFAULT 24,
        is_deleted TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB;");
    echo "    - Table 'complaint_categories' checked/created.\n";

    // 2. Add default categories
    echo "  - Inserting default categories...\n";
    $pdo->exec("INSERT IGNORE INTO complaint_categories (id, category_name, sla_hours) VALUES 
        (1, 'Room Maintenance', 48), 
        (2, 'General Facility', 72), 
        (3, 'Mess & Food', 24),
        (4, 'Internet/Wi-Fi', 12),
        (5, 'Other', 96);
    ");
    echo "    - Default categories added.\n";

    // 3. Add columns to complaints table
    echo "  - Adding 'category_id' to 'complaints' table...\n";
    try { $pdo->exec("ALTER TABLE complaints ADD COLUMN category_id INT DEFAULT NULL AFTER user_id;"); } catch (Exception $e) { echo "    - Column 'category_id' already exists. Skipping.\n"; }
    
    echo "  - Adding 'sla_breached' to 'complaints' table...\n";
    try { $pdo->exec("ALTER TABLE complaints ADD COLUMN sla_breached TINYINT(1) DEFAULT 0 AFTER status;"); } catch (Exception $e) { echo "    - Column 'sla_breached' already exists. Skipping.\n"; }

    $pdo->commit();
    echo "\n\n<h2 style='color: #0f0;'>✅ SUCCESS: Complaints database has been fixed.</h2> <p style='color: #fff;'>You can now delete this script file (`fix_complaints_db.php`).</p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n\n<h2 style='color: #f00;'>❌ ERROR: An error occurred.</h2>\n";
    echo "Error Message: " . $e->getMessage();
}

echo "</pre>";
?>