-- ========================================
-- SAMPLE DATA FOR SIAMS DEMO
-- ========================================
-- Run this AFTER database.sql to populate demo data
-- Navigate to: http://localhost/phpmyadmin
-- Select "products_db" database, click "SQL" tab, paste this code, click "Go"

USE products_db;

-- ========================================
-- SAMPLE USERS (Password: password123 for all)
-- ========================================

INSERT INTO users (username, password_hash, full_name, role, status) VALUES
('admin', '$2y$10$YmVjcnlwdGhhc2hlZHBhc3N3b3JkMTIzYWRtaW5oYXNoZWRwYXNz', 'System Administrator', 'Admin', 'Active'),
('manager_user', '$2y$10$YmVjcnlwdGhhc2hlZHBhc3N3b3JkMTIzYWRtaW5oYXNoZWRwYXNz', 'John Manager', 'Manager', 'Active'),
('staff_user', '$2y$10$YmVjcnlwdGhhc2hlZHBhc3N3b3JkMTIzYWRtaW5oYXNoZWRwYXNz', 'Jane Staff', 'Staff', 'Active'),
('auditor_user', '$2y$10$YmVjcnlwdGhhc2hlZHBhc3N3b3JkMTIzYWRtaW5oYXNoZWRwYXNz', 'Audit Officer', 'Auditor', 'Active');

-- ========================================
-- SAMPLE SUPPLIERS
-- ========================================

INSERT INTO suppliers (supplier_name, contact_person, email, phone, address) VALUES
('TechSupply Co.', 'Robert Chen', 'robert@techsupply.com', '+1-555-0101', '123 Tech Street, Silicon Valley, CA 94025'),
('Office Essentials Ltd.', 'Sarah Johnson', 'sarah@officeessentials.com', '+1-555-0102', '456 Business Ave, New York, NY 10001'),
('Hardware Plus', 'Michael Brown', 'michael@hardwareplus.com', '+1-555-0103', '789 Industrial Blvd, Chicago, IL 60601'),
('Premium Goods Inc.', 'Emily Davis', 'emily@premiumgoods.com', '+1-555-0104', '321 Quality Road, Austin, TX 78701'),
('FastShip Distributors', 'David Wilson', 'david@fastship.com', '+1-555-0105', '654 Logistics Lane, Seattle, WA 98101');

-- ========================================
-- SAMPLE INVENTORY ITEMS
-- ========================================

INSERT INTO inventory_items (item_name, category, quantity, min_threshold, supplier_id, last_updated_by, status) VALUES
-- Electronics
('Laptop Dell XPS 15', 'Electronics', 25, 10, 1, 1, 'Available'),
('Wireless Mouse Logitech', 'Electronics', 150, 50, 1, 1, 'Available'),
('USB-C Hub Multiport', 'Electronics', 45, 20, 1, 1, 'Available'),
('Monitor 27" 4K', 'Electronics', 8, 15, 1, 1, 'Low Stock'),
('Keyboard Mechanical RGB', 'Electronics', 62, 25, 1, 1, 'Available'),

-- Office Supplies
('Printer Paper A4 (500 sheets)', 'Office Supplies', 120, 50, 2, 1, 'Available'),
('Blue Pens (Box of 50)', 'Office Supplies', 35, 20, 2, 1, 'Available'),
('Sticky Notes Assorted', 'Office Supplies', 88, 40, 2, 1, 'Available'),
('File Folders Letter Size', 'Office Supplies', 15, 30, 2, 1, 'Low Stock'),
('Whiteboard Markers Set', 'Office Supplies', 42, 15, 2, 1, 'Available'),

-- Furniture
('Office Chair Ergonomic', 'Furniture', 18, 10, 3, 1, 'Available'),
('Standing Desk Adjustable', 'Furniture', 5, 8, 3, 1, 'Low Stock'),
('Filing Cabinet 4-Drawer', 'Furniture', 12, 5, 3, 1, 'Available'),
('Conference Table 8-Seater', 'Furniture', 3, 2, 3, 1, 'Available'),

-- Tools & Equipment
('Screwdriver Set Professional', 'Tools', 28, 10, 4, 1, 'Available'),
('Cordless Drill 20V', 'Tools', 14, 8, 4, 1, 'Available'),
('Measuring Tape 25ft', 'Tools', 35, 20, 4, 1, 'Available'),
('Safety Goggles', 'Safety Equipment', 75, 30, 5, 1, 'Available'),
('Hard Hat ANSI Approved', 'Safety Equipment', 42, 25, 5, 1, 'Available'),
('First Aid Kit Complete', 'Safety Equipment', 18, 10, 5, 1, 'Available');

-- ========================================
-- SAMPLE STOCK REQUESTS
-- ========================================

INSERT INTO stock_requests (item_id, requested_by, quantity, priority_score, status, manager_id) VALUES
-- Pending requests (Staff user requesting)
(4, 3, 10, 185, 'Pending', NULL),  -- Monitor (Low stock, high priority)
(9, 3, 20, 125, 'Pending', NULL),  -- File Folders (Low stock)
(12, 3, 5, 95, 'Pending', NULL),   -- Standing Desk (Low stock)

-- Approved requests (Manager approved)
(1, 3, 5, 75, 'Approved', 2),      -- Laptops
(6, 3, 50, 60, 'Approved', 2),     -- Printer Paper
(15, 3, 10, 45, 'Approved', 2),    -- Screwdriver Sets

-- Rejected requests
(14, 3, 2, 30, 'Rejected', 2),     -- Conference Table (not critical)
(2, 3, 100, 40, 'Rejected', 2);    -- Wireless Mouse (sufficient stock)

-- ========================================
-- SAMPLE AUDIT LOGS
-- ========================================

INSERT INTO audit_logs (user_id, action_type, target_table, target_id, description) VALUES
-- Login activities
(1, 'LOGIN', 'users', 1, 'Admin logged in successfully'),
(2, 'LOGIN', 'users', 2, 'Manager logged in successfully'),
(3, 'LOGIN', 'users', 3, 'Staff logged in successfully'),

-- Inventory operations
(1, 'CREATE', 'inventory_items', 1, 'Created new inventory item: Laptop Dell XPS 15'),
(1, 'CREATE', 'inventory_items', 2, 'Created new inventory item: Wireless Mouse Logitech'),
(1, 'UPDATE', 'inventory_items', 4, 'Updated quantity for Monitor 27" 4K'),

-- Request operations
(3, 'CREATE', 'stock_requests', 1, 'Submitted stock request for item ID 4 (qty: 10)'),
(3, 'CREATE', 'stock_requests', 4, 'Submitted stock request for item ID 1 (qty: 5)'),
(2, 'APPROVE', 'stock_requests', 4, 'Manager approved stock request ID 4'),
(2, 'REJECT', 'stock_requests', 7, 'Manager rejected stock request ID 7'),

-- User management
(1, 'CREATE', 'users', 2, 'Admin created new manager account'),
(1, 'CREATE', 'users', 3, 'Admin created new staff account'),
(1, 'UPDATE', 'users', 3, 'Admin updated staff user permissions');

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- Check users created
SELECT 'Users Created:' as Check_Type, COUNT(*) as Count FROM users;

-- Check inventory items
SELECT 'Inventory Items:' as Check_Type, COUNT(*) as Count FROM inventory_items;

-- Check stock requests
SELECT 'Stock Requests:' as Check_Type, COUNT(*) as Count FROM stock_requests;

-- Check suppliers
SELECT 'Suppliers:' as Check_Type, COUNT(*) as Count FROM suppliers;

-- Check audit logs
SELECT 'Audit Logs:' as Check_Type, COUNT(*) as Count FROM audit_logs;

-- ========================================
-- DEMO CREDENTIALS
-- ========================================
-- Username: admin          | Password: password123 | Role: Admin
-- Username: manager_user   | Password: password123 | Role: Manager
-- Username: staff_user     | Password: password123 | Role: Staff
-- Username: auditor_user   | Password: password123 | Role: Auditor
-- ========================================

-- Sample data import complete!
-- You can now login at: http://localhost/SWAP-Project/auth/login.php
