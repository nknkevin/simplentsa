# Vehicle Tracking System - Architecture Documentation

## System Overview

This vehicle tracking system uses a **dual-database architecture** to separate business data from telemetry data:

```
┌─────────────────────────────────────────────────────────────────┐
│                         EXTERNAL SERVER                          │
│  (Updates alexa.vehicles automatically via sync process)         │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      WEB SERVER (Your App)                       │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    alexa Database                         │   │
│  │  ┌──────────────┐  ┌──────────────────────────────────┐  │   │
│  │  │    users     │  │            vehicles              │  │   │
│  │  │  - id        │  │  - id                            │  │   │
│  │  │  - username  │  │  - reg_no                        │  │   │
│  │  │  - password  │  │  - contact                       │  │   │
│  │  │  - role      │  │  - cus_name                      │  │   │
│  │  │  - active    │  │  - make                          │  │   │
│  │  └──────────────┘  │  - model                         │  │   │
│  │                     │  - vin_no                        │  │   │
│  │                     │  - chasis                        │  │   │
│  │                     │  - dealer                        │  │   │
│  │                     │  - action                        │  │   │
│  │                     │  - tech                          │  │   │
│  │                     │  - serial ←─────────────────────┼──┼──┼──┐
│  │                     │  - date                          │  │   │  │
│  │                     │  - status_renew                  │  │   │  │
│  │                     │  - number                        │  │   │  │
│  │                     │  - warning_sent                  │  │   │  │
│  │                     │  - sms_sent                      │  │   │  │
│  │                     │  - upd_time                      │  │   │  │
│  │                     │  - online                        │  │   │  │
│  │                     │  - online_i                      │  │   │  │
│  │                     └──────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────────┘   │
│                              │                                   │
│                              │ LINK:                             │
│                              │ serial = uniqueid                 │
│                              ↓                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    uradi Database                         │   │
│  │                   (Traccar Format)                        │   │
│  │  ┌─────────────────────────────────────────────────────┐ │   │
│  │  │              devices                                 │ │   │
│  │  │  - id ←────────────────────────────────────────────┼─┼───┼──┐
│  │  │  - name                                             │ │   │  │
│  │  │  - uniqueid ←──────────────────────────────────────┼─┼───┼──┤
│  │  │  - category                                         │ │   │  │
│  │  │  - status                                           │ │   │  │
│  │  │  - lastUpdate                                       │ │   │  │
│  │  └─────────────────────────────────────────────────────┘ │   │  │
│  │                              │                            │   │  │
│  │                              │ LINK:                      │   │  │
│  │                              │ id = deviceid              │   │  │
│  │                              ↓                            │   │  │
│  │  ┌─────────────────────────────────────────────────────┐ │   │  │
│  │  │            eventData (Telemetry)                     │ │   │  │
│  │  │  - id                                               │ │   │  │
│  │  │  - deviceId ────────────────────────────────────────┼─┼───┘  │
│  │  │  - type                                             │ │      │
│  │  │  - eventTime                                        │ │      │
│  │  │  - latitude                                         │ │      │
│  │  │  - longitude                                        │ │      │
│  │  │  - speed                                            │ │      │
│  │  │  - course                                           │ │      │
│  │  │  - fuelLevel                                        │ │      │
│  │  │  - batteryLevel                                     │ │      │
│  │  │  - motion                                           │ │      │
│  │  │  - address                                          │ │      │
│  │  └─────────────────────────────────────────────────────┘ │      │
│  └──────────────────────────────────────────────────────────┘      │
└────────────────────────────────────────────────────────────────────┘
```

## Database Schema

### alexa Database (Local - Business Data)

#### `users` Table
Authentication and authorization.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| username | VARCHAR(50) | Unique username |
| password | VARCHAR(255) | Hashed password |
| role | VARCHAR(20) | 'admin' or 'user' |
| active | TINYINT(1) | Account status |
| created_at | TIMESTAMP | Creation time |
| updated_at | TIMESTAMP | Last update |

#### `vehicles` Table
Business data synced from external server (read-only in this app).

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| reg_no | VARCHAR(225) | Registration number |
| contact | VARCHAR(225) | Contact person |
| cus_name | VARCHAR(225) | Customer name |
| make | VARCHAR(225) | Vehicle make |
| model | VARCHAR(225) | Vehicle model |
| vin_no | VARCHAR(225) | VIN number |
| chasis | VARCHAR(225) | Chassis number |
| dealer | VARCHAR(225) | Dealer name |
| action | VARCHAR(225) | Service action |
| tech | VARCHAR(225) | Technician |
| **serial** | VARCHAR(225) | **Device ID (links to uradi.devices.uniqueid)** |
| date | DATE | Service date |
| status_renew | INT(5) | Renewal status |
| number | VARCHAR(225) | Reference number |
| warning_sent | VARCHAR(255) | Warning notification flag |
| sms_sent | VARCHAR(255) | SMS notification flag |
| upd_time | TIMESTAMP | Last update time |
| online | VARCHAR(5) | Online status |
| online_i | VARCHAR(5) | Online indicator |

### uradi Database (Remote - Traccar Telemetry)

#### `devices` Table
Tracking devices (Traccar standard schema).

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| name | VARCHAR(100) | Device name |
| **uniqueid** | VARCHAR(50) | **Unique device ID (links to alexa.vehicles.serial)** |
| category | VARCHAR(50) | Device category |
| status | TINYINT | Device status |
| disabled | TINYINT | Disabled flag |
| lastUpdate | TIMESTAMP | Last communication |

#### `eventData` Table
Telemetry/GPS data (Traccar standard schema).

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| **deviceId** | INT | **Foreign key to devices.id** |
| type | VARCHAR(50) | Event type |
| eventTime | TIMESTAMP | Event timestamp |
| latitude | DOUBLE | GPS latitude |
| longitude | DOUBLE | GPS longitude |
| speed | DOUBLE | Speed (km/h) |
| course | DOUBLE | Heading (degrees) |
| fuelLevel | DOUBLE | Fuel level (%) |
| batteryLevel | DOUBLE | Battery voltage |
| motion | BOOLEAN | Motion status |
| address | VARCHAR(255) | Reverse geocoded address |

## Data Flow

### 1. Vehicle Data Sync (External → alexa)
```
External Server → [Automatic Sync] → alexa.vehicles
- Runs independently (cron job, webhook, or manual import)
- Updates all vehicle business data
- Maintains serial field for telemetry linking
```

### 2. User Login Flow
```
User Input → api/login.php → alexa.users → Session Created → Dashboard
```

### 3. Vehicle Display Flow
```
Dashboard → api/vehicles.php → alexa.vehicles → Display Cards
                ↓
         Search by: reg_no, cus_name, serial, make, model, etc.
```

### 4. Telemetry Fetch Flow
```
User clicks "View Telemetry" 
    ↓
api/telemetry.php?serial=DEV001
    ↓
Step 1: SELECT id FROM uradi.devices WHERE uniqueid='DEV001'
    ↓
Step 2: SELECT * FROM uradi.eventData WHERE deviceId=[id] ORDER BY eventTime DESC LIMIT 1
    ↓
Display: location, speed, fuel, battery, motion, address
```

## API Endpoints

### `/api/login.php`
- **Method**: POST
- **Body**: username, password
- **Returns**: Session token, user info

### `/api/vehicles.php`
- **Method**: GET
- **Params**: search (optional), limit (optional)
- **Returns**: Array of vehicles from alexa.vehicles
- **Search Fields**: reg_no, contact, cus_name, make, model, vin_no, chasis, dealer, action, tech, serial, number

### `/api/telemetry.php`
- **Method**: GET
- **Params**: serial (required)
- **Returns**: Latest telemetry data from uradi.eventData
- **Process**: 
  1. Find device by uniqueid
  2. Get latest eventData for that device

### `/api/admin/users.php`
- **Method**: GET/POST
- **Access**: Admin only
- **Functions**: Create users, change passwords, manage accounts

## File Structure

```
/workspace
├── config/
│   ├── config.php          # App configuration
│   └── database.php        # DB connections (getAlexaDB, getUradiDB)
├── api/
│   ├── login.php           # Authentication
│   ├── logout.php          # Session cleanup
│   ├── vehicles.php        # Vehicle data (alexa)
│   ├── telemetry.php       # Telemetry data (uradi)
│   └── admin/
│       └── users.php       # User management
├── admin/
│   ├── users.php           # Admin user panel
│   └── vehicles.php        # Vehicle viewer (with telemetry)
├── assets/
│   ├── css/style.css       # Styles
│   └── images/             # Logo, backgrounds
├── database_setup.sql      # Complete schema
├── index.php               # Landing page
├── login.php               # Login form
└── dashboard.php           # Main dashboard
```

## Configuration

Edit `/workspace/config/database.php`:

```php
// Alexa Database (Users + Vehicles)
define('ALEXA_DB_HOST', 'localhost');
define('ALEXA_DB_NAME', 'alexa');
define('ALEXA_DB_USER', 'your_user');
define('ALEXA_DB_PASS', 'your_pass');

// Uradi Database (Traccar Telemetry)
define('URADI_DB_HOST', 'traccar-server.com');
define('URADI_DB_NAME', 'uradi');
define('URADI_DB_USER', 'your_user');
define('URADI_DB_PASS', 'your_pass');
```

## Key Relationships

1. **alexa.vehicles.serial** = **uradi.devices.uniqueid**
   - This is the primary link between business data and devices
   
2. **uradi.devices.id** = **uradi.eventData.deviceid**
   - Standard Traccar relationship for telemetry data

## Security Considerations

1. **Read-Only Vehicle Data**: Vehicle data in alexa is managed by external server
2. **Session-Based Auth**: All APIs require valid session
3. **Role-Based Access**: Admin functions restricted to admin role
4. **Prepared Statements**: All SQL queries use PDO prepared statements
5. **Input Validation**: Search terms sanitized, IDs validated

## Performance Optimizations

1. **Indexes**: Created on serial, uniqueid, deviceId, eventTime
2. **Lazy Loading**: Telemetry fetched only when requested
3. **Connection Pooling**: Static PDO connections reused
4. **Limit Results**: Default 50 vehicles per request
5. **Search Optimization**: Indexed fields for common searches

## Troubleshooting

### No Telemetry Data
1. Check serial matches uniqueid exactly
2. Verify device is not disabled in uradi.devices
3. Confirm uradi.database connection works
4. Check eventData has records for deviceId

### Vehicle Not Found
1. Verify alexa.vehicles has data
2. Check external sync is running
3. Confirm alexa database connection

### Login Fails
1. Check alexa.users table exists
2. Verify password hash format (bcrypt)
3. Ensure session is started before auth check
