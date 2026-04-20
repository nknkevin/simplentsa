-- Vehicle Tracking System - Database Setup Script
-- Architecture:
--   alexa (local): users + vehicles (business data from external server)
--   uradi (remote Traccar): devices + eventData (telemetry data)
--   Link: alexa.vehicles.serial = uradi.devices.uniqueid
--         uradi.devices.id = uradi.eventData.deviceid

-- ============================================
-- LOCAL DATABASE: alexa (Users + Vehicles)
-- ============================================

CREATE DATABASE IF NOT EXISTS alexa;
USE alexa;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default user (username: admin, password: admin123)
-- IMPORTANT: Change this password in production!
INSERT INTO users (username, password, role, active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1);

-- Vehicles table - receives data from external server
-- serial field links to uradi.devices.uniqueid
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reg_no VARCHAR(225),
    contact VARCHAR(225),
    cus_name VARCHAR(225),
    make VARCHAR(225),
    model VARCHAR(225),
    vin_no VARCHAR(225),
    chasis VARCHAR(225),
    dealer VARCHAR(225),
    action VARCHAR(225),
    tech VARCHAR(225),
    serial VARCHAR(225),        -- Links to uradi.devices.uniqueid
    date DATE,
    status_renew INT(5) DEFAULT 1,
    number VARCHAR(225),
    warning_sent VARCHAR(255) DEFAULT 'false',
    sms_sent VARCHAR(255) DEFAULT 'false',
    upd_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    online VARCHAR(5),
    online_i VARCHAR(5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reg_no (reg_no),
    INDEX idx_cus_name (cus_name),
    INDEX idx_serial (serial),
    INDEX idx_status_renew (status_renew)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample vehicle data (simulating external server sync)
INSERT INTO vehicles (reg_no, contact, cus_name, make, model, vin_no, chasis, dealer, action, tech, serial, date, status_renew, number, warning_sent, sms_sent, online, online_i) VALUES
('KBA-123A', '0712345678', 'John Doe', 'Toyota', 'Land Cruiser', 'JT3HN86R0Y0123456', 'CHS123456', 'Dealer A', 'Service', 'Tech Mike', 'DEV001', '2025-01-15', 1, '12345', 'false', 'false', 'true', '1'),
('KBB-456B', '0723456789', 'Jane Smith', 'Nissan', 'Patrol', 'JN1TBNT30Z0123457', 'CHS234567', 'Dealer B', 'Repair', 'Tech John', 'DEV002', '2025-01-16', 1, '12346', 'false', 'false', 'true', '1'),
('KBC-789C', '0734567890', 'Bob Wilson', 'Mazda', 'CX-5', 'JM3KE4CY0F0123458', 'CHS345678', 'Dealer C', 'Inspection', 'Tech Sarah', 'DEV003', '2025-01-17', 1, '12347', 'false', 'false', 'false', '0');

-- ============================================
-- REMOTE DATABASE: uradi (Traccar Structure)
-- Run this on your Traccar server
-- ============================================

-- Note: This is reference schema. Your Traccar server should already have these tables.
-- Only run if setting up a new Traccar instance.

CREATE DATABASE IF NOT EXISTS uradi;
USE uradi;

-- Devices table (Traccar standard)
-- uniqueid field links to alexa.vehicles.serial
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    uniqueid VARCHAR(50) UNIQUE NOT NULL,  -- Links to alexa.vehicles.serial
    category VARCHAR(50),
    status TINYINT DEFAULT 0,
    disabled TINYINT DEFAULT 0,
    lastUpdate TIMESTAMP NULL,
    positionId INT DEFAULT 0,
    groupId INT DEFAULT 0,
    phone VARCHAR(50),
    model VARCHAR(50),
    contact VARCHAR(50),
    categoryColor VARCHAR(7),
    attributes TEXT,
    INDEX idx_uniqueid (uniqueid),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event/Telemetry data table (Traccar standard)
-- deviceid field links to devices.id
CREATE TABLE IF NOT EXISTS eventData (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    deviceId INT NOT NULL,              -- Links to devices.id
    type VARCHAR(50),
    serverTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    eventTime TIMESTAMP NOT NULL,
    latitude DOUBLE,
    longitude DOUBLE,
    altitude DOUBLE,
    speed DOUBLE,
    course DOUBLE,
    address VARCHAR(255),
    accuracy DOUBLE,
    batteryLevel DOUBLE,
    fuelLevel DOUBLE,
    rpm DOUBLE,
    power DOUBLE,
    motion BOOLEAN,
    totalDistance BIGINT,
    INDEX idx_deviceid (deviceId),
    INDEX idx_eventTime (eventTime),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample devices (matching alexa.vehicles.serial values)
INSERT INTO devices (name, uniqueid, category, status, lastUpdate, model, contact) VALUES
('Truck 001', 'DEV001', 'Truck', 1, NOW(), 'GPS Tracker X1', '0712345678'),
('Van 002', 'DEV002', 'Van', 1, NOW(), 'GPS Tracker X2', '0723456789'),
('Car 003', 'DEV003', 'Car', 0, NOW(), 'GPS Tracker X3', '0734567890');

-- Sample telemetry data
INSERT INTO eventData (deviceId, type, eventTime, latitude, longitude, speed, course, fuelLevel, motion) VALUES
(1, 'position', NOW(), -1.2921, 36.8219, 65.5, 180.0, 75.0, TRUE),
(2, 'position', NOW(), -1.3030, 36.8300, 45.0, 90.0, 60.0, TRUE),
(3, 'position', NOW(), -1.2800, 36.8100, 0.0, 0.0, 40.0, FALSE);

-- ============================================
-- USAGE NOTES:
-- ============================================
-- 1. Run ENTIRE script on local server (creates alexa with users + vehicles)
-- 2. Run uradi section ONLY on Traccar server (if not already set up)
-- 3. External server syncs data to alexa.vehicles automatically
-- 4. System fetches telemetry from uradi using:
--    - alexa.vehicles.serial → uradi.devices.uniqueid
--    - uradi.devices.id → uradi.eventData.deviceid
-- 5. Update config/database.php:
--    - LOCAL_DB_HOST/NAME/USER/PASS = alexa (users + vehicles)
--    - DB_HOST/NAME/USER/PASS = uradi (telemetry only)
-- 6. Default login: admin/admin123, user/admin123
