# Vehicle Tracking System

A professional, mobile-friendly vehicle tracking and fleet management system with customizable branding, built with PHP, HTML, CSS, and jQuery.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 📋 Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Features](#features)
- [File Structure](#file-structure)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [User Management](#user-management)
- [Customization](#customization)
- [API Endpoints](#api-endpoints)
- [Security](#security)
- [Troubleshooting](#troubleshooting)
- [Documentation](#documentation)

## Overview

This vehicle tracking system provides:
- Secure user authentication with role-based access control
- Real-time vehicle telemetry monitoring
- Customizable branding to match your company identity
- Admin panel for user management
- Mobile-responsive design for on-the-go access

## Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   Web Server    │     │  alexa Database  │     │  uradi Database  │
│   (PHP/HTML)    │────▶│  (Users/Auth)    │     │  (Vehicles)      │
│                 │     │   [Local]        │     │   [Remote]       │
└─────────────────┘     └──────────────────┘     └──────────────────┘
        ▲                                               │
        │                                               ▼
   ┌────┴────┐                                 ┌──────────────────┐
   │  User   │                                 │ Tracking Server  │
   │(Mobile) │                                 │  (Telemetry)     │
   └─────────┘                                 └──────────────────┘
```

## Features

### Core Features
- **🔐 Secure Authentication**: Username/password with bcrypt hashing and session management
- **👥 User Management**: Create users, change passwords, manage roles (Admin/User)
- **🚗 Vehicle Search**: Search by ID, plate number, or name with instant results
- **📡 Real-time Telemetry**: Auto-refresh tracking data every 5 seconds
- **📱 Mobile-First Design**: Fully responsive UI optimized for all devices
- **🎨 Customizable Branding**: Logo, colors, and background images (jendientsa.co.ke style)

### Admin Features
- User creation and management
- Password reset capability
- User status toggle (active/inactive)
- Role assignment (admin/user)

### User Experience
- Clean, modern interface
- Loading states and animations
- Error handling with user-friendly messages
- Intuitive navigation

## File Structure

```
/workspace
├── config/
│   ├── config.php          # Main configuration & branding settings
│   └── database.php        # Database connections (alexa & uradi)
├── api/
│   ├── login.php           # Authentication endpoint
│   ├── logout.php          # Logout endpoint
│   ├── vehicles.php        # Vehicle data endpoint
│   ├── telemetry.php       # Telemetry data endpoint
│   └── admin/
│       └── users.php       # User management API (CRUD operations)
├── admin/
│   └── users.php           # User management page (admin only)
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet with brand variables
│   ├── js/
│   │   └── app.js          # Frontend JavaScript & interactions
│   └── images/
│       ├── logo.png        # Company logo
│       ├── favicon.ico     # Browser favicon
│       └── login-bg.jpg    # Login page background
├── index.php               # Login page
├── dashboard.php           # Main dashboard after login
├── database_setup.sql      # Database setup script
├── README.md               # This file
├── SETUP_GUIDE.md          # Detailed setup instructions
├── CHANGELOG.md            # Version history and changes
└── OPTIMIZATION.md         # Performance improvement guide
```

## Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP Extensions**: PDO, PDO_MySQL, cURL, JSON, Session
- **jQuery**: 3.6+ (loaded via CDN)

### Recommended
- HTTPS/SSL certificate for production
- PHP OPcache enabled
- MySQL query cache enabled
- Minimum 512MB RAM for web server

## Quick Start

### 1. Database Setup

```bash
# Create alexa database (users) on local server
mysql -u root -p < database_setup.sql

# Create uradi database (vehicles) on remote server
# See SETUP_GUIDE.md for detailed SQL scripts
```

### 2. Configuration

Edit `config/database.php`:
```php
// alexa (users) - Local
define('LOCAL_DB_HOST', 'localhost');
define('LOCAL_DB_NAME', 'alexa');
define('LOCAL_DB_USER', 'your_username');
define('LOCAL_DB_PASS', 'your_password');

// uradi (vehicles) - Remote
define('DB_HOST', 'remote-host.com');
define('DB_NAME', 'uradi');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

Edit `config/config.php` for branding:
```php
define('BRAND_LOGO', 'assets/images/logo.png');
define('BRAND_PRIMARY_COLOR', '#2563eb');
define('LOGIN_BACKGROUND', 'assets/images/login-bg.jpg');
```

### 3. Deploy

```bash
# Copy to web server
cp -r /workspace/* /var/www/html/

# Set permissions
chown -R www-data:www-data /var/www/html/
chmod 755 /var/www/html/
chmod 644 /var/www/html/config/*.php
```

### 4. Login

Access `http://your-domain.com/index.php`

**Default Credentials:**
- **Admin**: `admin` / `admin123`
- **User**: `user` / `admin123`

⚠️ **Change default passwords immediately!**

## Configuration

### Database Configuration

The system uses two separate databases:

| Database | Purpose | Location |
|----------|---------|----------|
| **alexa** | User authentication, sessions | Local (web server) |
| **uradi** | Vehicle data, telemetry | Remote (tracking server) |

### Branding Configuration

Customize the appearance in `config/config.php`:

```php
// Logo path (relative to web root)
define('BRAND_LOGO', 'assets/images/logo.png');

// Favicon for browser tab
define('BRAND_FAVICON', 'assets/images/favicon.ico');

// Primary brand color (hex)
define('BRAND_PRIMARY_COLOR', '#2563eb');

// Login page background image
define('LOGIN_BACKGROUND', 'assets/images/login-bg.jpg');
```

### Styling Tips

For a professional look like jendientsa.co.ke:
- Use high-quality logos (PNG with transparency, 200x60px)
- Choose professional stock photos for backgrounds (1920x1080px)
- Match brand colors to your company identity
- Free resources: Unsplash, Pexels, Pixabay

## User Management

### Creating Users

Admins can create users via the web interface:

1. Login as admin
2. Navigate to "User Management"
3. Fill in the form:
   - Username (unique)
   - Password (min 6 characters)
   - Role (User or Admin)
4. Click "Create User"

### Changing Passwords

1. Go to User Management page
2. Select user from dropdown
3. Enter new password
4. Click "Change Password"

### User Roles

| Role | Permissions |
|------|-------------|
| **Admin** | Full access: view vehicles, manage users, change passwords |
| **User** | View vehicles, search, access telemetry only |

## API Endpoints

### Authentication

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/login.php` | POST | Authenticate user |
| `/api/logout.php` | POST | End session |

### Vehicles & Telemetry

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/vehicles.php` | GET | Search/list vehicles |
| `/api/telemetry.php` | GET | Get real-time vehicle data |

### Admin (User Management)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/admin/users.php` | GET | List all users |
| `/api/admin/users.php` | POST | Create user or change password |
| `/api/admin/users.php` | PUT | Update user status |

### API Request Examples

**Login:**
```json
POST /api/login.php
{
  "username": "admin",
  "password": "admin123"
}
```

**Create User:**
```json
POST /api/admin/users.php
{
  "action": "create",
  "username": "newuser",
  "password": "securepass123",
  "role": "user"
}
```

**Change Password:**
```json
POST /api/admin/users.php
{
  "action": "change_password",
  "user_id": 5,
  "new_password": "newsecurepass"
}
```

## Security

### Implemented Security Measures

✅ **Password Hashing**: bcrypt with cost factor 10  
✅ **Prepared Statements**: SQL injection prevention  
✅ **Session Management**: Secure PHP sessions  
✅ **Role-Based Access**: Admin/User permissions  
✅ **Input Validation**: Server-side validation  
✅ **HTTPS Ready**: SSL/TLS support  

### Security Best Practices

1. **Change Defaults**: Immediately change default passwords
2. **Use HTTPS**: Always deploy with SSL certificate
3. **Secure Config**: Keep config files outside web root if possible
4. **Regular Updates**: Keep PHP and dependencies updated
5. **Rate Limiting**: Implement login attempt limits
6. **Strong Passwords**: Enforce minimum 8 characters, mixed case, numbers
7. **Session Timeout**: Configure appropriate session lifetimes
8. **Database Backups**: Regular automated backups
9. **Access Logs**: Monitor login attempts and admin actions
10. **Firewall**: Restrict database access to trusted IPs only

## Troubleshooting

### Common Issues

#### Login Not Working
- ✅ Verify alexa database connection in `config/database.php`
- ✅ Check users table exists: `SHOW TABLES LIKE 'users';`
- ✅ Confirm user exists: `SELECT * FROM users WHERE username='admin';`
- ✅ Check PHP sessions: `session_start()` enabled

#### Vehicles Not Loading
- ✅ Verify uradi database connection
- ✅ Check vehicles table exists on remote server
- ✅ Test remote connection: `telnet remote-host 3306`
- ✅ Review PHP error log: `/var/log/php/error.log`

#### Telemetry Not Updating
- ✅ Verify tracking server URL in `config/config.php`
- ✅ Test API endpoint: `curl http://tracking-server:5055/api/position`
- ✅ Check browser console for CORS errors
- ✅ Confirm cURL enabled: `php -m | grep curl`

#### Permission Denied
- ✅ Set correct ownership: `chown -R www-data:www-data /var/www/html`
- ✅ Set permissions: `chmod 755` for directories, `chmod 644` for files
- ✅ Ensure config files are readable: `chmod 644 config/*.php`

### Debug Mode

Enable debug mode in `config/config.php`:
```php
define('DEBUG_MODE', true);
```

Check logs:
```bash
# PHP error log
tail -f /var/log/php/error.log

# Apache access log
tail -f /var/log/apache2/access.log

# MySQL slow queries
tail -f /var/log/mysql/slow.log
```

## Documentation

Additional documentation files:

- **[SETUP_GUIDE.md](SETUP_GUIDE.md)**: Detailed setup instructions with SQL scripts
- **[CHANGELOG.md](CHANGELOG.md)**: Version history and recent changes
- **[OPTIMIZATION.md](OPTIMIZATION.md)**: Performance improvement recommendations

## Support

For questions or issues:
1. Check the documentation files
2. Review troubleshooting section above
3. Verify configuration files
4. Check server logs for errors

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**License**: MIT
