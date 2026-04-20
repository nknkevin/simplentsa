# Changelog

All notable changes to the Vehicle Tracking System will be documented in this file.

## [1.1.0] - 2025-01-20

### Changed - Architecture Restructure

#### Database Architecture
- **REFACTORED**: Complete dual-database architecture implementation
  - `alexa` database: Users + Vehicles (business data from external server)
  - `uradi` database: Devices + EventData (Traccar telemetry data)
  - Link: `alexa.vehicles.serial` = `uradi.devices.uniqueid`
  - Link: `uradi.devices.id` = `uradi.eventData.deviceid`

#### Database Configuration (`config/database.php`)
- **CHANGED**: Renamed connection functions for clarity
  - `getLocalDB()` → `getAlexaDB()` 
  - `getVehicleDB()` → `getUradiDB()`
- **UPDATED**: Connection constants
  - `LOCAL_DB_*` → `ALEXA_DB_*`
  - `DB_*` → `URADI_DB_*`

#### Vehicles API (`api/vehicles.php`)
- **CHANGED**: Now fetches from `alexa` database only
- **REMOVED**: Write operations (UPDATE, DELETE) - vehicles are read-only, synced from external server
- **UPDATED**: Schema to match external server format
  - Fields: reg_no, contact, cus_name, make, model, vin_no, chasis, dealer, action, tech, serial, date, status_renew, number, warning_sent, sms_sent, upd_time, online, online_i
- **ENHANCED**: Search across all 12 fields

#### Telemetry API (`api/telemetry.php`)
- **COMPLETE REWRITE**: Now uses direct database queries instead of HTTP API
- **NEW**: Two-step lookup process
  1. Find device in `uradi.devices` by `uniqueid` (matches `alexa.vehicles.serial`)
  2. Fetch latest telemetry from `uradi.eventData` by `deviceId`
- **PARAMS**: Changed from `vehicle_id` to `serial`
- **RETURNS**: Full Traccar telemetry data (lat, lon, speed, course, fuel, battery, motion, address)

#### Vehicle Viewer (`admin/vehicles.php`)
- **REPLACED**: Admin CRUD interface with read-only viewer
- **NEW**: Real-time telemetry display
  - Click "View Telemetry" button to fetch live data
  - Shows: location, speed, fuel, battery, motion status, address
- **DISPLAY**: All 19 vehicle fields from external server
- **SEARCH**: By reg_no, customer, serial, make, model, etc.

#### Database Setup (`database_setup.sql`)
- **RESTRUCTURED**: Complete schema rewrite
  - `alexa` database: users + vehicles tables
  - `uradi` database: devices + eventData tables (Traccar format)
- **ADDED**: Sample data with matching serial/uniqueid values
- **INDEXES**: Optimized for join performance

### Added

#### Documentation
- **NEW**: `ARCHITECTURE.md` - Comprehensive system architecture documentation
  - Dual-database diagram
  - Data flow explanations
  - API endpoint reference
  - Configuration guide
  - Troubleshooting section

### Technical Details

##### alexa.vehicles Schema
```sql
CREATE TABLE vehicles (
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
    online_i VARCHAR(5)
);
```

##### uradi.devices Schema (Traccar)
```sql
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    uniqueid VARCHAR(50) UNIQUE NOT NULL,  -- Links to alexa.vehicles.serial
    category VARCHAR(50),
    status TINYINT DEFAULT 0,
    disabled TINYINT DEFAULT 0,
    lastUpdate TIMESTAMP NULL
);
```

##### uradi.eventData Schema (Traccar)
```sql
CREATE TABLE eventData (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    deviceId INT NOT NULL,              -- Links to devices.id
    type VARCHAR(50),
    eventTime TIMESTAMP NOT NULL,
    latitude DOUBLE,
    longitude DOUBLE,
    speed DOUBLE,
    course DOUBLE,
    fuelLevel DOUBLE,
    batteryLevel DOUBLE,
    motion BOOLEAN,
    address VARCHAR(255)
);
```

### Migration Guide

1. **Backup existing databases**
2. **Run new setup script**:
   ```bash
   mysql -u root -p < database_setup.sql
   ```
3. **Update config/database.php** with correct credentials
4. **Ensure external server** syncs to alexa.vehicles
5. **Verify Traccar** is writing to uradi.devices and uradi.eventData

### Breaking Changes

⚠️ **API Changes**
- `api/telemetry.php` now requires `serial` parameter instead of `vehicle_id`
- `api/vehicles.php` no longer supports POST requests (read-only)

⚠️ **Database Changes**
- Old `vehicles` table schema replaced with external server schema
- New `uradi` database required for telemetry

---

## [1.0.0] - 2025-01-15

### Initial Release

#### Features
- User authentication with role-based access (admin/user)
- Vehicle management with extended fields
- User creation and password management (admin only)
- Customizable branding (logo, colors, backgrounds)
- Responsive design for mobile/desktop
- Search functionality across multiple fields

#### Database
- `alexa` database for users
- `uradi` database for vehicles (Traccar-compatible)

#### Security
- Password hashing with bcrypt
- Session-based authentication
- SQL injection prevention with prepared statements
- XSS protection with HTML escaping

#### Default Credentials
- Username: `admin`, Password: `admin123`
- Username: `user`, Password: `admin123`

---

## Notes

### Version Numbering
This project follows [Semantic Versioning](https://semver.org/):
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### External Server Integration
The system expects an external server to sync vehicle data to `alexa.vehicles`. This can be:
- Cron job running SQL imports
- Webhook-based real-time updates
- Manual CSV uploads
- Custom synchronization script

### Traccar Compatibility
The `uradi` database uses standard Traccar schema, making it compatible with:
- Traccar server installations
- GPS tracking devices
- Existing Traccar APIs and tools
