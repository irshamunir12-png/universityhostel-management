-- Table to store all possible food items for the mess menu
CREATE TABLE IF NOT EXISTS mess_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL UNIQUE,
    category ENUM('Breakfast', 'Lunch', 'Dinner', 'General') DEFAULT 'General'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some default food items to get started
INSERT IGNORE INTO mess_items (item_name, category) VALUES
('Paratha', 'Breakfast'),
('Fried Egg', 'Breakfast'),
('Omelette', 'Breakfast'),
('Chai', 'Breakfast'),
('Bread Slice', 'Breakfast'),
('Jam', 'Breakfast'),
('Daal Chawal', 'Lunch'),
('Daal Mash', 'Lunch'),
('Chicken Pulao', 'Lunch'),
('Aloo Gosht', 'Dinner'),
('Sabzi (Seasonal)', 'Lunch'),
('Roti', 'General'),
('Naan', 'General'),
('Chicken Karahi', 'Dinner'),
('Chicken Biryani', 'Dinner'),
('Raita', 'General'),
('Salad', 'General'),
('Kheer', 'Dinner'),
('Zarda', 'Dinner'),
('Fish (Fried)', 'Dinner');