-- 1. Clear existing menu items to avoid confusion
TRUNCATE TABLE mess_menu;

-- 2. Insert Days again
INSERT INTO mess_menu (day_of_week) VALUES 
('Monday'), ('Tuesday'), ('Wednesday'), ('Thursday'), ('Friday'), ('Saturday'), ('Sunday');

-- 3. Update with the CORRECT formatted data (JSON for Tags)

-- Monday
UPDATE mess_menu SET 
breakfast = '[{"value":"Bread Slice"}]', 
lunch = '[{"value":"Chicken Pulao"}]', 
dinner = '[{"value":"Sabzi (Seasonal)"}]' 
WHERE day_of_week = 'Monday';

-- Tuesday
UPDATE mess_menu SET 
breakfast = '[{"value":"Bread Omelette"}, {"value":"Milk"}]', 
lunch = '[{"value":"Vegetable Biryani"}, {"value":"Raita"}]', 
dinner = '[{"value":"Beef Pulao"}, {"value":"Salad"}]' 
WHERE day_of_week = 'Tuesday';

-- Wednesday
UPDATE mess_menu SET 
breakfast = '[{"value":"Poori Chana"}, {"value":"Halwa"}]', 
lunch = '[{"value":"Aloo Gosht"}, {"value":"Roti"}]', 
dinner = '[{"value":"Daal Makhani"}, {"value":"Rice"}]' 
WHERE day_of_week = 'Wednesday';

-- Thursday
UPDATE mess_menu SET 
breakfast = '[{"value":"Dalia"}, {"value":"Anda"}]', 
lunch = '[{"value":"White Chawal"}, {"value":"Daal"}]', 
dinner = '[{"value":"Mix Sabzi"}, {"value":"Roti"}]' 
WHERE day_of_week = 'Thursday';

-- Friday
UPDATE mess_menu SET 
breakfast = '[{"value":"Daal"}, {"value":"Aloo Paratha"}]', 
lunch = '[{"value":"Palak Paneer"}, {"value":"Roti"}]', 
dinner = '[{"value":"Fish Fry"}, {"value":"Rice"}]' 
WHERE day_of_week = 'Friday';

-- Saturday
UPDATE mess_menu SET 
breakfast = '[{"value":"Aloo Paratha"}, {"value":"Dahi"}]', 
lunch = '[{"value":"Chicken Biryani"}, {"value":"Raita"}]', 
dinner = '[{"value":"Aloo Keema"}, {"value":"Roti"}]' 
WHERE day_of_week = 'Saturday';

-- Sunday
UPDATE mess_menu SET 
breakfast = '[{"value":"Halwa Poori"}, {"value":"Chana"}]', 
lunch = '[{"value":"Daal Maash"}, {"value":"Rice"}]', 
dinner = '[{"value":"BBQ Night (Chicken Tikka)"}]' 
WHERE day_of_week = 'Sunday';

-- 4. Also add these items to the Master List (Dropdown suggestions)
INSERT IGNORE INTO mess_items (item_name, category) VALUES 
('Bread Omelette', 'Breakfast'), ('Milk', 'Breakfast'), ('Poori Chana', 'Breakfast'), ('Halwa', 'Breakfast'),
('Dalia', 'Breakfast'), ('Anda', 'Breakfast'), ('Dahi', 'Breakfast'), ('Halwa Poori', 'Breakfast'),
('Vegetable Biryani', 'Lunch'), ('Beef Pulao', 'Dinner'), ('Daal Makhani', 'Dinner'), ('White Chawal', 'Lunch'),
('Mix Sabzi', 'Dinner'), ('Palak Paneer', 'Lunch'), ('Fish Fry', 'Dinner'), ('Aloo Keema', 'Dinner'),
('BBQ Night (Chicken Tikka)', 'Dinner');