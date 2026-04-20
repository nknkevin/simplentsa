# Vehicle Tracking System - Setup Guide

## Table of Contents

1. [Database Configuration](#database-configuration)
2. [User Management](#user-management)
3. [Customizing Branding](#customizing-branding)
4. [File Structure](#file-structure)
5. [Access Points](#access-points)
6. [Security Notes](#security-notes)
7. [Next Steps](#next-steps)

---

## Database Configuration

This system uses two databases:
- **alexa** - Local database for user authentication (on same server as web)
- **uradi** - Remote database for vehicle data (similar to Traccar structure)

### Step 1: Set up the alexa database (users)

Run this on your local/web server MySQL:

```sql
CREATE DATABASE IF NOT EXISTS alexa;
USE alexa;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_active (active),
    INDEX idx_login (username, active),
    INDEX idx_role_active (role, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin user (password: admin123)
INSERT INTO users (username, password, role, active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1);
```

### Step 2: Set up the uradi database (vehicles)

Run this on your remote vehicle tracking server MySQL:

```sql
CREATE DATABASE IF NOT EXISTS uradi;
USE uradi;

-- Example Traccar-compatible tables (customize based on your needs)
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    uniqueId VARCHAR(50) UNIQUE NOT NULL,
    plate_number VARCHAR(20),
    model VARCHAR(50),
    contact VARCHAR(50),
    category VARCHAR(50),
    lastUpdate TIMESTAMP,
    INDEX idx_plate (plate_number),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS positions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    latitude DOUBLE NOT NULL,
    longitude DOUBLE NOT NULL,
    speed DOUBLE,
    course INT,
    address VARCHAR(255),
    last_update TIMESTAMP,
    attributes TEXT,
    INDEX idx_device (device_id),
    INDEX idx_last_update (last_update)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Step 3: Update Database Configuration

Edit `config/database.php`:

```php
// For alexa (users)
define('LOCAL_DB_HOST', 'localhost');
define('LOCAL_DB_NAME', 'alexa');
define('LOCAL_DB_USER', 'your_local_username');
define('LOCAL_DB_PASS', 'your_local_password');

// For uradi (vehicles)
define('DB_HOST', 'your-remote-db-host.com');
define('DB_NAME', 'uradi');
define('DB_USER', 'your_remote_username');
define('DB_PASS', 'your_remote_password');
```

## User Management

### Creating New Users

Admins can create new users through the web interface:

1. Login as admin (username: `admin`, password: `admin123`)
2. Click "User Management" in the dashboard header
3. Fill in the "Create New User" form:
   - Username
   - Password
   - Role (User or Admin)

### Changing Passwords

Admins can change any user's password:

1. Go to User Management page
2. In the "Change User Password" section:
   - Select the user from dropdown
   - Enter new password
   - Click "Change Password"

### Default Credentials

- **Username:** admin | **Password:** admin123 | **Role:** admin
- **Username:** user | **Password:** admin123 | **Role:** user

⚠️ **IMPORTANT:** Change default passwords immediately in production!

## Customizing Branding

### Logo and Colors

Edit `config/config.php`:

```php
// Branding - Customize these for your company
define('BRAND_LOGO', 'assets/images/logo.png');
define('BRAND_FAVICON', 'assets/images/favicon.ico');
define('BRAND_PRIMARY_COLOR', '#2563eb'); // Your brand color
define('LOGIN_BACKGROUND', 'assets/images/login-bg.jpg');
```

### Adding Your Logo

1. Place your logo file at `assets/images/logo.png`
2. Recommended size: 200x60px or similar aspect ratio
3. PNG format with transparent background works best

### Custom Login Background

1. Place your background image at `assets/images/login-bg.jpg`
2. Recommended size: 1920x1080px
3. The image will have a dark overlay for better text readability

### Example: jendientsa.co.ke Style

For a professional look:
- Use a clean, modern logo
- Choose high-quality stock photos (technology, fleet, logistics themes)
- Match the primary color to your brand
- Free stock photos: Unsplash.com, Pexels.com, Pixabay.com

## File Structure

```
/workspace
├── config/
│   ├── config.php          # Main configuration & branding
│   └── database.php        # Database connections (alexa & uradi)
├── api/
│   ├── login.php           # Authentication endpoint
│   ├── logout.php          # Logout endpoint
│   ├── vehicles.php        # Vehicle data endpoint
│   ├── telemetry.php       # Telemetry data endpoint
│   └── admin/
│       └── users.php       # User management API
├── admin/
│   └── users.php           # User management page (admin only)
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet
│   ├── js/
│   │   └── app.js          # Frontend JavaScript
│   └── images/             # Branding assets
│       ├── logo.png
│       ├── favicon.ico
│       └── login-bg.jpg
├── index.php               # Login page
├── dashboard.php           # Main dashboard
└── database_setup.sql      # Database setup script
```

## Access Points

- **Login Page:** `/index.php`
- **Dashboard:** `/dashboard.php`
- **User Management:** `/admin/users.php` (admin only)

## Security Notes

1. Always use HTTPS in production
2. Change default passwords immediately
3. Keep your database credentials secure
4. Regularly update passwords
5. Only grant admin access to trusted users
6. Implement rate limiting for login attempts
7. Use strong passwords (minimum 8 characters)
8. Regular database backups
9. Monitor access logs
10. Restrict database access to trusted IPs

## Next Steps

After completing setup:

1. **Test the system:**
   - Login with admin credentials
   - Create a test user
   - Search for vehicles
   - Check telemetry updates

2. **Customize branding:**
   - Add your company logo
   - Set brand colors
   - Upload background images

3. **Secure the installation:**
   - Change all default passwords
   - Enable HTTPS
   - Configure firewall rules

4. **Monitor performance:**
   - Check slow query logs
   - Monitor server resources
   - Review OPTIMIZATION.md for improvements

5. **Plan for growth:**
   - Set up regular backups
   - Consider caching (Redis)
   - Plan for scaling

---

**For detailed performance optimization tips, see [OPTIMIZATION.md](OPTIMIZATION.md)**  
**For version history and changes, see [CHANGELOG.md](CHANGELOG.md)**
