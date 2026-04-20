# Integration Notes: Extended Vehicle Data Management

## Overview

This document explains how the system now manages extended vehicle data from an external server schema, mapping the data to the local `uradi` database.

## Schema Mapping

The external server table schema has been integrated into the local `vehicles` table with the following field mappings:

| External Field | Local Field | Type | Description |
|---------------|-------------|------|-------------|
| id | id | INT(6) AUTO | Primary key |
| reg_no | reg_no | VARCHAR(225) | Registration number |
| contact | contact | VARCHAR(225) | Customer contact info |
| cus_name | cus_name | VARCHAR(225) | Customer name |
| make | make | VARCHAR(225) | Vehicle make |
| model | model | VARCHAR(225) | Vehicle model |
| vin_no | vin_no | VARCHAR(225) | VIN number |
| chasis | chasis | VARCHAR(225) | Chassis number |
| dealer | dealer | VARCHAR(225) | Dealer information |
| action | action | VARCHAR(225) | Action/status field |
| tech | tech | VARCHAR(225) | Technician assigned |
| **serial** | **serial** | VARCHAR(225) | **Tracking device ID** (was tracking_device_id) |
| date | - | DATE | (Use created_at if needed) |
| status_renew | status_renew | INT(5) | Renewal status (default: 1) |
| number | number | VARCHAR(225) | Additional number field |
| warning_sent | warning_sent | VARCHAR(255) | Warning sent flag |
| sms_sent | sms_sent | VARCHAR(255) | SMS sent flag |
| upd_time | updated_at | TIMESTAMP | Auto-updated timestamp |
| online | online | VARCHAR(5) | Online status |
| online_i | online_i | VARCHAR(5) | Online indicator |

### Key Mapping Note

**`tracking_device_id` is now referred to as `serial`** throughout the system. This matches the external server's naming convention and simplifies integration.

## Database Migration

Run the migration script to add all new columns:

```bash
mysql -u root -p uradi < database/migrate_extended_vehicles.sql
```

The migration script:
- Adds all 17 new columns safely (IF NOT EXISTS)
- Creates indexes on frequently searched fields
- Sets appropriate defaults
- Is safe to run multiple times

## API Usage

### Search Vehicles (GET)

```javascript
// Search by any field (name, plate, VIN, customer, serial, etc.)
GET /api/vehicles.php?search=ABC123&limit=50

// Returns all vehicle fields including extended data
{
  "success": true,
  "data": [
    {
      "id": 1,
      "vehicle_name": "Truck 001",
      "plate_number": "ABC123",
      "cus_name": "John Doe",
      "serial": "DEV001",
      "reg_no": "REG123",
      "contact": "+254700000000",
      // ... all other fields
    }
  ]
}
```

### Update Vehicle (POST - Admin Only)

```javascript
POST /api/vehicles.php
Content-Type: application/x-www-form-urlencoded

action=update
&id=1
&vehicle_name=Truck 001
&serial=DEV001
&cus_name=John Doe
&reg_no=REG123
&contact=+254700000000
&make=Toyota
&model=Hilux
&status_renew=1
&warning_sent=false
&sms_sent=false
&online=true
// ... include all fields you want to update
```

### Delete Vehicle (POST - Admin Only)

```javascript
POST /api/vehicles.php
action=delete
&id=1
```

## Admin Panel Features

Access the vehicle management panel at `/admin/vehicles.php` (admin users only).

### Features:
1. **Search**: Filter by reg_no, customer name, serial, plate, VIN, or contact
2. **View Details**: See all 22+ fields in organized sections
3. **Edit**: Update any vehicle information through modal form
4. **Status Badges**: Visual indicators for active/inactive/maintenance status

### Form Sections:
- **Basic Information**: Name, plate, VIN, model, year, status
- **Customer & Registration**: reg_no, customer name, contact, make, model
- **Device & Technical**: serial (device ID), VIN no, chasis, technician, dealer
- **Status & Notifications**: Action, status_renew, number, warning_sent, sms_sent
- **Online Status**: online, online_i

## Synchronization Strategy

If you need to sync data from the external server:

### Option 1: Direct Database Link
```sql
-- Create a federated table or use linked server
-- Then run periodic sync queries
INSERT INTO vehicles (serial, cus_name, reg_no, ...)
SELECT serial, cus_name, reg_no, ...
FROM external_server.database.table
ON DUPLICATE KEY UPDATE ...
```

### Option 2: PHP Sync Script
Create a cron job that:
1. Connects to external server via API or direct DB connection
2. Fetches updated records
3. Updates local `uradi.vehicles` table

### Option 3: Application-Level Sync
Add sync functionality to the admin panel:
- Button to "Sync from External Server"
- Shows last sync time
- Displays sync statistics

## Indexes Created

For optimal performance, the migration creates indexes on:
- `reg_no`: Fast registration lookups
- `cus_name`: Quick customer searches
- `serial`: Device ID lookups (critical for telemetry)
- `status_renew`: Filter by renewal status

## Security Considerations

1. **Admin Access Only**: Vehicle editing/deleting requires admin role
2. **SQL Injection Prevention**: All queries use prepared statements
3. **Session Validation**: Every API call checks authentication
4. **Input Sanitization**: All user inputs are sanitized

## Troubleshooting

### Column Not Found Errors
Run the migration script:
```bash
mysql -u root -p uradi < database/migrate_extended_vehicles.sql
```

### Permission Denied
Ensure MySQL user has ALTER and CREATE INDEX privileges:
```sql
GRANT ALTER, CREATE, INDEX ON uradi.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

### Slow Searches
Verify indexes exist:
```sql
SHOW INDEX FROM vehicles;
```

## Next Steps

1. Run the migration script
2. Test vehicle search with new fields
3. Configure data sync from external server
4. Train admins on new vehicle management features
5. Set up monitoring for sync processes

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**Database**: uradi  
**External Schema**: Compatible with provided schema (id, reg_no, cus_name, serial, etc.)
