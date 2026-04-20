# Performance Optimization Guide

## Vehicle Tracking System - Speed & Efficiency Improvements

This document provides actionable recommendations to make your vehicle tracking system faster, more scalable, and better performing.

---

## Table of Contents

1. [Database Optimization](#database-optimization)
2. [Caching Strategies](#caching-strategies)
3. [Code Optimization](#code-optimization)
4. [Frontend Performance](#frontend-performance)
5. [Infrastructure Improvements](#infrastructure-improvements)
6. [Monitoring & Profiling](#monitoring--profiling)
7. [Security vs Performance Balance](#security-vs-performance-balance)
8. [Quick Wins](#quick-wins)

---

## Database Optimization

### 1. Index Optimization

**Current State**: Basic indexes on username and active columns.

**Recommendations**:

```sql
-- Add composite indexes for common queries
USE alexa;

-- For login queries (username + active check)
CREATE INDEX idx_login ON users(username, active);

-- For admin user listings (role + active)
CREATE INDEX idx_admin_list ON users(role, active);

-- Analyze index usage
EXPLAIN SELECT * FROM users WHERE username = 'admin' AND active = 1;
```

```sql
-- For uradi database (vehicles)
USE uradi;

-- Add indexes on frequently searched columns
CREATE INDEX idx_plate_number ON devices(plate_number);
CREATE INDEX idx_device_id ON devices(device_id);
CREATE INDEX idx_last_update ON positions(last_update);

-- Composite index for vehicle searches
CREATE INDEX idx_vehicle_search ON devices(name, plate_number);
```

### 2. Query Optimization

**Problem**: N+1 query problem when loading vehicles with telemetry.

**Solution**: Use JOINs instead of separate queries:

```php
// Instead of:
foreach ($vehicles as $vehicle) {
    $telemetry = getTelemetry($vehicle['id']); // Separate query
}

// Do this:
SELECT v.*, t.speed, t.latitude, t.longitude 
FROM vehicles v
LEFT JOIN positions t ON v.id = t.device_id
WHERE v.active = 1;
```

### 3. Connection Pooling

**Issue**: Opening new database connections for each request.

**Solution**: Use persistent connections:

```php
// In config/database.php
define('DB_OPTIONS', [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
```

### 4. Database Configuration

**MySQL/MariaDB Tuning** (`/etc/mysql/my.cnf`):

```ini
[mysqld]
# Connection settings
max_connections = 200
thread_cache_size = 50

# Query cache (for MySQL 5.7, removed in 8.0)
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

# InnoDB optimization
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Temporary tables
tmp_table_size = 64M
max_heap_table_size = 64M

# Slow query logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### 5. Database Partitioning

For large datasets (millions of position records):

```sql
-- Partition positions table by date
ALTER TABLE positions 
PARTITION BY RANGE (YEAR(last_update)) (
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026)
);
```

---

## Caching Strategies

### 1. Application-Level Caching (Redis/Memcached)

**Install Redis**:
```bash
sudo apt-get install redis-server php-redis
```

**Implementation**:

```php
// lib/cache.php
class Cache {
    private static $redis;
    
    public static function init() {
        if (!self::$redis) {
            self::$redis = new Redis();
            self::$redis->connect('127.0.0.1', 6379);
        }
        return self::$redis;
    }
    
    public static function get($key) {
        return self::init()->get($key);
    }
    
    public static function set($key, $value, $ttl = 300) {
        return self::init()->setex($key, $ttl, json_encode($value));
    }
    
    public static function delete($key) {
        return self::init()->delete($key);
    }
}
```

**Cache User Sessions**:
```php
// Store session data in Redis instead of files
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://127.0.0.1:6379');
```

**Cache Vehicle Data**:
```php
// Cache vehicle search results for 30 seconds
$cacheKey = "vehicles_search_" . md5($searchTerm);
$cached = Cache::get($cacheKey);

if ($cached) {
    return json_decode($cached, true);
}

$results = searchVehicles($searchTerm);
Cache::set($cacheKey, $results, 30);
return $results;
```

### 2. HTTP Caching Headers

**Add to PHP responses**:

```php
// For static assets (CSS, JS, images)
header("Cache-Control: public, max-age=31536000");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");

// For API responses that change frequently
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// For semi-static data (vehicle lists)
header("Cache-Control: private, max-age=60");
```

### 3. OPcache Configuration

**Enable PHP OPcache** (`/etc/php/7.4/fpm/php.ini`):

```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=1
```

---

## Code Optimization

### 1. Reduce Database Queries

**Before** (Multiple queries):
```php
$user = getUser($userId);         // Query 1
$role = getRole($user['role_id']); // Query 2
$permissions = getPermissions($role['id']); // Query 3
```

**After** (Single query with JOIN):
```php
$sql = "SELECT u.*, r.name as role_name, p.permissions 
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN permissions p ON r.id = p.role_id
        WHERE u.id = ?";
```

### 2. Use Prepared Statements Efficiently

**Reuse prepared statements** for repeated queries:

```php
// Create once, execute multiple times
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");

foreach ($usernames as $username) {
    $stmt->execute([$username]);
    $user = $stmt->fetch();
}
```

### 3. Lazy Loading

Load data only when needed:

```php
class Vehicle {
    private $telemetry = null;
    
    public function getTelemetry() {
        if ($this->telemetry === null) {
            // Only fetch when requested
            $this->telemetry = $this->fetchTelemetry();
        }
        return $this->telemetry;
    }
}
```

### 4. Batch Operations

**Instead of**:
```php
foreach ($users as $user) {
    updateUserStatus($user['id'], $status); // One query per user
}
```

**Do**:
```php
$ids = array_column($users, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "UPDATE users SET active = ? WHERE id IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge([$status], $ids));
```

### 5. Avoid Unnecessary Data Fetching

**Select only needed columns**:

```php
// Bad: Fetches all columns
SELECT * FROM users WHERE id = 1;

// Good: Only fetch what you need
SELECT id, username, role FROM users WHERE id = 1;
```

### 6. Use Generator Functions for Large Datasets

```php
// Memory-efficient iteration over large result sets
function getVehiclesGenerator($pdo) {
    $stmt = $pdo->query("SELECT * FROM vehicles");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        yield $row;
    }
}

// Usage
foreach (getVehiclesGenerator($pdo) as $vehicle) {
    processVehicle($vehicle);
}
```

---

## Frontend Performance

### 1. Minimize HTTP Requests

**Combine CSS and JS files**:
```html
<!-- Instead of multiple files -->
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="dashboard.css">
<script src="jquery.js"></script>
<script src="app.js"></script>

<!-- Use bundled/minified versions -->
<link rel="stylesheet" href="bundle.min.css">
<script src="bundle.min.js"></script>
```

### 2. Enable Compression

**Apache** (`.htaccess`):
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/json
    AddOutputFilterByType DEFLATE application/javascript
</IfModule>
```

**Nginx** (`nginx.conf`):
```nginx
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_proxied expired no-cache no-store private auth;
gzip_types text/plain text/css text/xml text/javascript 
           application/x-javascript application/xml application/json;
```

### 3. Optimize Images

**Compress images**:
```bash
# Install ImageMagick
sudo apt-get install imagemagick

# Compress logo
convert logo.png -quality 85 logo-optimized.png

# Resize background image
convert login-bg.jpg -resize 1920x1080 login-bg-optimized.jpg
```

**Use modern formats**:
- WebP instead of PNG/JPG (30% smaller)
- SVG for logos and icons

### 4. Lazy Load Non-Critical Resources

```javascript
// Lazy load images
document.addEventListener("DOMContentLoaded", function() {
    var lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
    
    if ("IntersectionObserver" in window) {
        let lazyImageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    let lazyImage = entry.target;
                    lazyImage.src = lazyImage.dataset.src;
                    lazyImage.classList.remove("lazy");
                    lazyImageObserver.unobserve(lazyImage);
                }
            });
        });
        
        lazyImages.forEach(function(lazyImage) {
            lazyImageObserver.observe(lazyImage);
        });
    }
});
```

### 5. Reduce Telemetry Refresh Rate on Mobile

```javascript
// Adjust refresh rate based on connection speed
function getOptimalRefreshRate() {
    if ('connection' in navigator) {
        const type = navigator.connection.effectiveType;
        if (type === 'slow-2g' || type === '2g') {
            return 15000; // 15 seconds on slow connections
        } else if (type === '3g') {
            return 10000; // 10 seconds on 3G
        }
    }
    return 5000; // Default 5 seconds
}

const REFRESH_RATE = getOptimalRefreshRate();
```

### 6. Use CDN for jQuery

```html
<!-- Use Google CDN with fallback -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
window.jQuery || document.write('<script src="assets/js/jquery-3.6.0.min.js"><\/script>')
</script>
```

---

## Infrastructure Improvements

### 1. Use PHP-FPM Instead of mod_php

**Install and configure PHP-FPM**:
```bash
sudo apt-get install php-fpm
sudo systemctl enable php7.4-fpm
sudo systemctl start php7.4-fpm
```

**Nginx configuration**:
```nginx
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php7.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
    
    # FastCGI caching
    fastcgi_cache_path /var/cache/nginx levels=1:2 keys_zone=phpcache:100m inactive=60m;
    fastcgi_cache phpcache;
    fastcgi_cache_valid 200 60m;
    fastcgi_cache_valid 404 1m;
}
```

### 2. Load Balancing

For high-traffic scenarios, use multiple web servers:

```nginx
upstream backend {
    least_conn;
    server web1.example.com;
    server web2.example.com;
    server web3.example.com;
}

server {
    location / {
        proxy_pass http://backend;
    }
}
```

### 3. Database Replication

**Master-Slave Setup**:
- Master: Write operations (user creation, password changes)
- Slave: Read operations (login, vehicle searches)

```php
// Separate read/write connections
$writeDb = new PDO(...); // Master
$readDb = new PDO(...);  // Slave

// Use slave for reads
$stmt = $readDb->prepare("SELECT * FROM users WHERE username = ?");

// Use master for writes
$stmt = $writeDb->prepare("INSERT INTO users ...");
```

### 4. Use SSD Storage

- SSD for database storage (10x faster than HDD)
- SSD for session storage
- Consider NVMe for maximum performance

### 5. Increase Server Resources

**Minimum Recommended**:
- CPU: 2 cores
- RAM: 2GB
- Storage: SSD

**For Production (100+ concurrent users)**:
- CPU: 4+ cores
- RAM: 4-8GB
- Storage: NVMe SSD

---

## Monitoring & Profiling

### 1. Enable Slow Query Log

```sql
-- In MySQL
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

Analyze slow queries:
```bash
mysqldumpslow /var/log/mysql/slow.log
```

### 2. Use Blackfire.io for Profiling

```bash
# Install Blackfire agent
curl -A "Install" https://blackfire.io/api/installation.sh | sh

# Profile your application
blackfire run http://your-domain.com/dashboard.php
```

### 3. New Relic APM

Install New Relic for comprehensive monitoring:
```bash
# Add repository
echo "deb http://apt.newrelic.com/debian/ newrelic non-free" | tee /etc/apt/sources.list.d/newrelic.list

# Install
apt-get update
apt-get install newrelic-php5
```

### 4. Application Logging

Implement structured logging:

```php
// lib/logger.php
class Logger {
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    private static function log($level, $message, $context) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
        
        file_put_contents(
            '/var/log/app/application.log',
            json_encode($logEntry) . PHP_EOL,
            FILE_APPEND
        );
    }
}

// Usage
Logger::info('User login', ['user_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR']]);
```

### 5. Real-Time Monitoring Dashboard

Create a simple monitoring endpoint:

```php
// api/health.php
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => time(),
    'database' => [
        'alexa' => testConnection($alexaDb),
        'uradi' => testConnection($uradiDb),
    ],
    'memory_usage' => memory_get_usage(true),
    'response_time' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms',
];

echo json_encode($health);
```

---

## Security vs Performance Balance

### 1. Password Hashing

**Current**: bcrypt cost 10 (good balance)

**Options**:
- Cost 10: ~100ms per hash (recommended)
- Cost 12: ~400ms (more secure, slower)
- Cost 8: ~25ms (less secure, faster - not recommended)

**Recommendation**: Keep cost 10, it's only used during login (not on every request).

### 2. Session Security

**Secure but fast session configuration**:

```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Only if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 3600); // 1 hour
```

### 3. Rate Limiting with Redis

```php
function checkRateLimit($userId, $limit = 5, $window = 60) {
    $redis = Cache::init();
    $key = "rate_limit:" . $userId;
    
    $current = $redis->incr($key);
    if ($current == 1) {
        $redis->expire($key, $window);
    }
    
    return $current <= $limit;
}
```

---

## Quick Wins (Immediate Impact)

### Priority 1 (Do These First):

1. **Enable OPcache** - 2-3x faster PHP execution
   ```bash
   sudo phpenmod opcache
   sudo systemctl restart php7.4-fpm
   ```

2. **Add Database Indexes** - 10-100x faster queries
   ```sql
   CREATE INDEX idx_login ON users(username, active);
   ```

3. **Enable Gzip Compression** - 70% smaller transfers
   ```apache
   AddOutputFilterByType DEFLATE text/html text/css application/javascript
   ```

4. **Use Persistent Database Connections** - Faster DB access
   ```php
   PDO::ATTR_PERSISTENT => true
   ```

5. **Cache Vehicle Search Results** - Reduce DB load
   ```php
   Cache::set('vehicles_' . $searchTerm, $results, 30);
   ```

### Priority 2 (Next Steps):

6. **Install Redis for Session Storage** - Faster sessions
7. **Optimize Images** - Faster page loads
8. **Minify CSS/JS** - Smaller file sizes
9. **Use CDN for jQuery** - Faster loading, browser caching
10. **Enable MySQL Query Cache** - Faster repeated queries

### Priority 3 (Long-term):

11. **Implement Database Replication** - Scale reads
12. **Add Load Balancer** - Handle more concurrent users
13. **Upgrade to SSD/NVMe** - Faster disk I/O
14. **Implement Full-Page Caching** - For static pages
15. **Use HTTP/2** - Multiplexed requests

---

## Performance Checklist

### Database
- [ ] Indexes on frequently queried columns
- [ ] Query cache enabled
- [ ] Slow query log enabled and reviewed
- [ ] Connection pooling configured
- [ ] Tables optimized (OPTIMIZE TABLE)

### Application
- [ ] OPcache enabled
- [ ] Redis/Memcached installed
- [ ] Session storage in Redis
- [ ] Frequently accessed data cached
- [ ] Unnecessary queries eliminated

### Frontend
- [ ] Gzip compression enabled
- [ ] Images optimized
- [ ] CSS/JS minified
- [ ] CDN for third-party libraries
- [ ] Browser caching headers set

### Infrastructure
- [ ] PHP-FPM configured
- [ ] SSD storage used
- [ ] Adequate RAM (2GB+)
- [ ] Multiple CPU cores
- [ ] Monitoring in place

---

## Expected Performance Gains

| Optimization | Expected Improvement |
|-------------|---------------------|
| OPcache | 2-3x faster PHP |
| Database Indexes | 10-100x faster queries |
| Redis Caching | 5-10x faster reads |
| Gzip Compression | 70% smaller transfers |
| Persistent Connections | 20-30% faster DB access |
| Image Optimization | 50% faster page loads |
| Combined (all above) | 5-10x overall improvement |

---

## Testing Performance

### Before and After Comparison

```bash
# Test page load time
time curl http://your-domain.com/dashboard.php

# Test with Apache Bench (install: apt-get install apache2-utils)
ab -n 1000 -c 10 http://your-domain.com/dashboard.php

# Check database query time
mysql -u root -p -e "SHOW STATUS LIKE 'Slow_queries';"
```

### Key Metrics to Monitor

- Page load time: Target < 2 seconds
- API response time: Target < 200ms
- Database query time: Target < 50ms
- Concurrent users supported: Target 100+
- Memory usage: Target < 256MB per process

---

## Conclusion

Start with the **Quick Wins** section for immediate improvements, then work through the priorities based on your specific bottlenecks. Monitor performance before and after each change to measure impact.

Remember: **Measure first, optimize second.** Don't optimize blindly—identify actual bottlenecks using profiling tools.

For personalized recommendations, consider:
1. Running Blackfire.io profiler
2. Analyzing slow query logs
3. Monitoring server resources (CPU, RAM, disk I/O)
4. Checking network latency

---

**Last Updated**: 2024  
**Version**: 1.0.0
