<?php
require_once 'core/db.php';

try {
    // 1. Asset Categories Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS asset_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Assets Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS assets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_name VARCHAR(150) NOT NULL,
        asset_tag VARCHAR(50) NOT NULL UNIQUE,
        category_id INT,
        purchase_date DATE,
        purchase_price DECIMAL(10, 2),
        status ENUM('available', 'in_use', 'damaged', 'disposed') NOT NULL DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Asset Allocations Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS asset_allocations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        allocated_to_type ENUM('student', 'room') NOT NULL,
        allocated_to_id INT NOT NULL,
        issue_date DATE NOT NULL,
        return_date DATE DEFAULT NULL,
        condition_on_issue VARCHAR(100),
        condition_on_return VARCHAR(100),
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "<div style='font-family: sans-serif; padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h3>✅ Success! Database tables fixed.</h3>";
    echo "<p>Ab aap <strong>Hostel Operations > General Inventory</strong> par wapis ja sakte hain.</p>";
    echo "<a href='index.php'>Go to Dashboard</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<h3>Error:</h3> " . $e->getMessage();
}
?>