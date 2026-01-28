-- ========================================
-- DATABASE SETUP FILE (SIAMS Approved Schema)
-- ========================================
-- Run this in phpMyAdmin: http://localhost/phpmyadmin
-- Click "SQL" tab, paste this code, click "Go"

-- ========================================
-- CREATE DATABASE
-- ========================================

CREATE DATABASE IF NOT EXISTS products_db;

USE products_db;

-- ========================================
-- TABLE 1: users
-- ========================================

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Manager', 'Staff', 'Auditor') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- TABLE 2: suppliers
-- ========================================

CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(50) NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    address VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- TABLE 3: inventory_items
-- ========================================

CREATE TABLE IF NOT EXISTS inventory_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NULL,
    quantity INT NOT NULL DEFAULT 0,
    min_threshold INT NOT NULL,
    supplier_id INT NULL,
    last_updated_by INT NULL,
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('Available', 'Low Stock', 'Out of Stock') NOT NULL,
    CONSTRAINT fk_inventory_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    CONSTRAINT fk_inventory_user FOREIGN KEY (last_updated_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- TABLE 4: stock_requests
-- ========================================

CREATE TABLE IF NOT EXISTS stock_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    requested_by INT NOT NULL,
    quantity INT NOT NULL,
    priority_score INT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending',
    manager_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_request_item FOREIGN KEY (item_id) REFERENCES inventory_items(item_id),
    CONSTRAINT fk_request_user FOREIGN KEY (requested_by) REFERENCES users(user_id),
    CONSTRAINT fk_request_manager FOREIGN KEY (manager_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- TABLE 5: audit_logs
-- ========================================

CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('CREATE', 'UPDATE', 'DELETE', 'APPROVE', 'REJECT', 'LOGIN', 'LOGOUT') NOT NULL,
    target_table VARCHAR(50) NOT NULL,
    target_id INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT NULL,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- TABLE 6: reports (Optional)
-- ========================================

CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(100) NOT NULL,
    generated_by INT NOT NULL,
    generated_for ENUM('Admin', 'Manager', 'Auditor') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Active', 'Archived') DEFAULT 'Active',
    CONSTRAINT fk_report_user FOREIGN KEY (generated_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Setup complete! Your database is ready to use.
