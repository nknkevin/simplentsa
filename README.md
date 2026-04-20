# Vehicle Tracking System

A simple, mobile-friendly login and vehicle search system built with PHP, HTML, CSS, and jQuery.

## Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   Web Server    │     │  Database Server │     │ Tracking Server  │
│   (PHP/HTML)    │────▶│  (Vehicle Data)  │     │  (Telemetry)     │
│                 │     │                  │     │                  │
└─────────────────┘     └──────────────────┘     └──────────────────┘
        ▲
        │
   ┌────┴────┐
   │  User   │
   │(Mobile) │
   └─────────┘
```

## Features

- **Simple Login System**: Username/password authentication stored locally
- **Vehicle Search**: Search vehicles by ID, plate number, or name
- **Real-time Telemetry**: Auto-refresh tracking data every 5 seconds
- **Mobile-First Design**: Responsive UI optimized for mobile devices
- **Easy Customization**: Simple configuration files for database connections

## File Structure

```
/workspace
├── config/
│   ├── config.php          # Main configuration
│   └── database.php        # Database connection settings
├── api/
│   ├── login.php           # Login endpoint
│   ├── logout.php          # Logout endpoint
│   ├── vehicles.php        # Vehicle search endpoint
│   └── telemetry.php       # Telemetry data endpoint
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet
│   └── js/
│       └── app.js          # Frontend JavaScript
├── index.php               # Login page
├── dashboard.php           # Main dashboard after login
└── README.md               # This file
```

## Setup Instructions

1. Configure database settings in `config/database.php`
2. Set up your vehicle database on the remote server
3. Configure tracking server URL in `config/config.php`
4. Upload to your web server
5. Access via mobile browser or web browser

## Customization

- Change refresh interval in `assets/js/app.js` (default: 5000ms)
- Modify colors and styling in `assets/css/style.css`
- Add custom fields in vehicle search via `api/vehicles.php`

## Requirements

- PHP 7.4+
- MySQL/MariaDB
- Web server (Apache/Nginx)
- jQuery (included via CDN)

## Implementation Plan

### Phase 1: Setup & Configuration ✓
- [x] Create project structure
- [x] Configure main settings (config.php)
- [x] Set up database connections (database.php)
- [x] Create SQL setup script

### Phase 2: Authentication ✓
- [x] Build login page (index.php)
- [x] Create login API endpoint (api/login.php)
- [x] Create logout functionality (api/logout.php)
- [x] Implement session management

### Phase 3: Vehicle Management ✓
- [x] Build dashboard page (dashboard.php)
- [x] Create vehicle search API (api/vehicles.php)
- [x] Design mobile-friendly vehicle cards
- [x] Implement search functionality

### Phase 4: Telemetry Integration ✓
- [x] Create telemetry API endpoint (api/telemetry.php)
- [x] Connect to remote tracking server
- [x] Implement auto-refresh every 5 seconds
- [x] Display real-time data (speed, location, fuel, etc.)

### Phase 5: Styling & UX ✓
- [x] Mobile-first CSS design
- [x] Responsive layouts for all screen sizes
- [x] Loading states and animations
- [x] Error handling and alerts

### Next Steps (Optional Enhancements)
- [ ] Add Google Maps integration for vehicle location
- [ ] Implement vehicle history/tracking logs
- [ ] Add user role-based permissions
- [ ] Create admin panel for user management
- [ ] Add push notifications for alerts
- [ ] Implement geofencing features
- [ ] Add export functionality (CSV, PDF reports)
- [ ] Multi-language support

## Quick Start

1. **Set up databases:**
   ```bash
   # Run on local database server (for users)
   mysql -u root -p < database_setup.sql
   
   # Run on remote database server (for vehicles)
   # Copy the REMOTE DATABASE section and run separately
   ```

2. **Configure connections:**
   - Edit `config/database.php` with your database credentials
   - Edit `config/config.php` with your tracking server URL

3. **Deploy to web server:**
   ```bash
   # Copy all files to your web server's public directory
   # Ensure PHP has write permissions for sessions
   ```

4. **Login:**
   - Default username: `admin`
   - Default password: `admin123`
   - **Change these immediately!**

## Security Notes

- Change default passwords before production use
- Use HTTPS in production
- Keep configuration files outside web root if possible
- Regularly update dependencies
- Implement rate limiting for login attempts
- Use prepared statements (already implemented) to prevent SQL injection

## Troubleshooting

**Login not working?**
- Check local database connection in `config/database.php`
- Verify users table exists and has data
- Check PHP session configuration

**Vehicles not loading?**
- Verify remote database connection settings
- Check that vehicles table exists on remote server
- Look for errors in PHP error log

**Telemetry not updating?**
- Verify tracking server URL in `config/config.php`
- Check if tracking server is accessible from web server
- Look for CORS issues in browser console
- Verify cURL is enabled in PHP

## Support

For questions or issues, check the configuration files and ensure all database connections are properly set up.
