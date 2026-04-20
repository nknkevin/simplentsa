-- Vehicle Tracking System - Database Setup Script
-- Run this on your local database server for users
-- and on your remote database server for vehicles

-- ============================================
-- LOCAL DATABASE (Users - Same Server as Web)
-- ============================================

CREATE DATABASE IF NOT EXISTS users_database;
USE users_database;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default user (username: admin, password: admin123)
-- IMPORTANT: Change this password in production!
INSERT INTO users (username, password, role, active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1);

-- ============================================
-- REMOTE DATABASE (Vehicles - Different Server)
-- ============================================

CREATE DATABASE IF NOT EXISTS vehicle_database;
USE vehicle_database;

-- Vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_name VARCHAR(100) NOT NULL,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    vin VARCHAR(17) UNIQUE,
    model VARCHAR(50),
    year INT,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    tracking_device_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plate (plate_number),
    INDEX idx_vin (vin),
    INDEX idx_status (status)
);

-- Sample vehicle data for testing
INSERT INTO vehicles (vehicle_name, plate_number, vin, model, year, status) VALUES
('Truck 001', 'ABC-1234', '1HGBH41JXMN109186', 'Ford F-150', 2022, 'active'),
('Van 002', 'XYZ-5678', '2HGBH41JXMN109187', 'Mercedes Sprinter', 2021, 'active'),
('Car 003', 'DEF-9012', '3HGBH41JXMN109188', 'Toyota Camry', 2023, 'active'),
('Truck 004', 'GHI-3456', '4HGBH41JXMN109189', 'Volvo FH16', 2020, 'inactive'),
('Van 005', 'JKL-7890', '5HGBH41JXMN109190', 'Ford Transit', 2022, 'maintenance');

-- ============================================
-- NOTES:
-- ============================================
-- 1. Run the LOCAL DATABASE section on your web server's database
-- 2. Run the REMOTE DATABASE section on your separate database server
-- 3. Update config/database.php with correct connection details
-- 4. Default login credentials:
--    - Username: admin, Password: admin123
--    - Username: user, Password: admin123
-- 5. IMPORTANT: Change default passwords before production use!
