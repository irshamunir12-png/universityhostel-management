-- 1. Create Asset Categories Table
CREATE TABLE IF NOT EXISTS asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create Assets Table
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_name VARCHAR(150) NOT NULL,
    asset_tag VARCHAR(50) NOT NULL UNIQUE,
    category_id INT,
    purchase_date DATE,
    purchase_price DECIMAL(10, 2),
    status ENUM('available', 'in_use', 'damaged', 'disposed') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create Asset Allocations Table
CREATE TABLE IF NOT EXISTS asset_allocations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add an index for faster lookups
CREATE INDEX idx_allocated_to ON asset_allocations (allocated_to_type, allocated_to_id);

-- Some default categories and assets to get started
INSERT INTO asset_categories (category_name) VALUES ('Bedding'), ('Furniture'), ('Electronics');

INSERT INTO assets (asset_name, asset_tag, category_id, status) VALUES
('Mattress - Single', 'MAT-001', 1, 'available'),
('Study Chair', 'CHR-001', 2, 'available'),
('Pillow', 'PIL-001', 1, 'available'),
('Mattress - Single', 'MAT-002', 1, 'available');