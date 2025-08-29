# Laravel Application

A production-ready Laravel application demonstrating APM (Application Performance Monitoring) integration with comprehensive deployment options and scaling capabilities.

## 🚀 Features

- **Multiple Deployment Types**: PHP Built-in Server, Apache mod_php, Apache PHP-FPM, Nginx PHP-FPM
- **Smart Conflict Resolution**: Automatic detection and resolution of web server conflicts
- **Production Scaling**: Optimized for 200+ concurrent users
- **Dynamic PHP Version Support**: PHP 8.1 - 8.4 with automatic switching
- **Network Management**: Dynamic IP detection and port management
- **Comprehensive Monitoring**: Health checks, metrics, and performance monitoring
- **Database Integration**: MySQL, PostgreSQL, Redis support with connection pooling
- **Security**: Production-grade security headers and configurations
- **Laravel Framework**: Full Laravel 10.x framework with Eloquent ORM and Blade templating

## 📋 Prerequisites

- **Operating System**: Linux (Ubuntu/Debian recommended)
- **PHP**: Version 8.1 or higher (Laravel 10.x requirement)
- **Web Servers**: Apache 2.4+ and/or Nginx 1.18+
- **Databases**: MySQL 8.0+, PostgreSQL 12+, Redis 6.0+
- **System Tools**: curl, systemctl, composer
- **Laravel Requirements**: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML extensions

## 🛠️ Installation

### 1. System Setup
```bash
make setup
```
This command will:
- Check system requirements
- Verify PHP versions and extensions
- Validate web server installations
- Install Laravel-specific PHP extensions
- Offer optional production scaling configuration

### 2. Application Configuration
```bash
make compile
```
This command will:
- Select PHP version (8.1 - 8.4)
- Choose deployment type (PHP-CLI, Apache mod_php, Apache PHP-FPM, Nginx PHP-FPM)
- Configure network settings (localhost/public IP)
- Set up database connections
- Handle web server conflicts automatically
- Generate Laravel application key
- Configure Laravel environment

### 3. Start Application
```bash
make start
```
This command will:
- Deploy the application with selected configuration
- Start required services
- Run Laravel optimizations
- Display access information and management commands

## 📖 Available Commands

| Command | Description |
|---------|-------------|
| `make setup` | Check system requirements and setup |
| `make compile` | Configure application for deployment |
| `make start` | Start the application |
| `make stop` | Stop and cleanup application |
| `make status` | Show application status |
| `make enable` | Enable disabled application |
| `make disable` | Disable without cleanup |
| `make down` | Complete cleanup (remove all files and config) |
| `make php-status` | Show PHP version status and compatibility |
| `make network-status` | Show network status and IP changes |
| `make install` | Install Laravel dependencies |

## 🌐 Deployment Types

### 1. PHP Built-in Server (Development)
- **Use Case**: Local development and testing
- **Performance**: Single-threaded, not for production
- **Setup**: Automatic, no additional configuration required
- **Laravel Features**: Full framework support, hot reloading

### 2. Apache with mod_php (Production)
- **Use Case**: Traditional PHP hosting, shared hosting environments
- **Performance**: Good for moderate traffic
- **Features**: Direct PHP processing within Apache processes
- **Laravel Optimization**: OPcache enabled, route caching

### 3. Apache with PHP-FPM (Production)
- **Use Case**: High-performance production environments
- **Performance**: Excellent for high concurrency
- **Features**: Process pooling, better resource management
- **Laravel Optimization**: Optimized for Eloquent queries

### 4. Nginx with PHP-FPM (Production)
- **Use Case**: High-traffic applications, microservices
- **Performance**: Best for high concurrency and static content
- **Features**: Lightweight, excellent reverse proxy capabilities
- **Laravel Optimization**: Static asset optimization, gzip compression

## 🔧 Smart Conflict Resolution

The application automatically detects and resolves conflicts between different deployment types:

### mod_php ↔ PHP-FPM Switching
- Automatically stops conflicting PHP-FPM services
- Disables/enables appropriate Apache modules
- Restarts web servers with correct configuration
- Preserves Laravel configuration and cache

### Apache ↔ Nginx Switching
- Stops conflicting web servers
- Manages port conflicts
- Preserves application data during transitions
- Maintains Laravel route and view caches

## 📊 Production Scaling

Optimized configurations for 200+ concurrent users:

### PHP-FPM Configuration
- **Max Children**: 50 processes
- **Process Management**: Dynamic with optimal spare servers
- **Memory Optimization**: 256MB per process
- **OPcache**: Fully optimized for Laravel
- **Laravel Optimization**: Route caching, config caching, view caching

### Web Server Optimization
- **Apache**: MPM Event/Prefork optimization
- **Nginx**: Worker processes and connections tuning
- **Compression**: Gzip enabled for all text content
- **Caching**: Static assets cached for optimal performance
- **Laravel Assets**: Compiled and minified CSS/JS

### Database Scaling
- **Connection Pooling**: Optimized for Laravel Eloquent
- **Query Optimization**: Database query caching
- **Timeout Management**: Prevents connection leaks
- **Performance Tuning**: Buffer and cache optimization

## 🔍 Monitoring & Health Checks

### Built-in Endpoints
- **Health Check**: `/health` - Detailed system health with Laravel status
- **Dashboard**: `/` - Interactive APM dashboard
- **Database Test**: AJAX endpoints for database connectivity
- **Queue Monitoring**: Real-time queue status and management

### Monitoring Features
- Real-time performance metrics
- Database connection monitoring
- Memory usage tracking
- Load average monitoring
- Service availability checks
- Laravel-specific metrics (routes, middleware, cache)

## 🗄️ Database Support

### Supported Databases
- **MySQL**: Full Laravel Eloquent support with connection pooling
- **PostgreSQL**: Advanced features and optimization
- **Redis**: Caching, session management, and queue backend

### Laravel Database Features
- **Eloquent ORM**: Full object-relational mapping
- **Database Migrations**: Version control for database schema
- **Query Builder**: Fluent interface for database queries
- **Connection Pooling**: Optimized for production workloads

## 🔒 Security Features

- **Security Headers**: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection
- **File Protection**: Sensitive files (.env, composer files) are protected
- **Access Control**: Proper directory permissions and ownership
- **SSL Ready**: HTTPS support with security headers
- **Laravel Security**: CSRF protection, XSS prevention, SQL injection protection
- **Authentication**: Laravel's built-in authentication system ready

## 🚦 Network Management

### Dynamic IP Detection
- Automatically detects current network IP
- Survives network changes and system restarts
- Supports both localhost and public IP deployment
- Laravel APP_URL automatically configured

### Port Management
- Configurable port ranges (8000-9000 recommended)
- Automatic conflict detection
- Web server port configuration
- Laravel asset URL generation

## 🧪 Testing

### Unit Tests
```bash
composer test
# or
php artisan test
```

### Manual Testing
1. **PHP-CLI**: Test basic Laravel functionality
2. **Apache PHP-FPM**: Test production performance
3. **Apache mod_php**: Test traditional hosting
4. **Nginx PHP-FPM**: Test high-concurrency scenarios

### Laravel Testing Features
- **PHPUnit Integration**: Full test suite support
- **Feature Tests**: HTTP testing with Laravel
- **Database Testing**: Database factories and seeders
- **Mocking**: Service mocking and testing

## 📁 Project Structure

```
laravel-app/
├── app/                  # Laravel application code
│   ├── Http/Controllers/ # Controllers including ApmController
│   ├── Models/          # Eloquent models
│   └── Providers/       # Service providers
├── bootstrap/           # Laravel bootstrap files
├── config/              # Laravel configuration files
├── database/            # Migrations, factories, seeders
├── public/              # Web-accessible files
│   ├── index.php        # Laravel entry point
│   └── .htaccess        # Apache rewrite rules
├── resources/           # Views, assets, language files
│   └── views/apm/       # APM dashboard views
├── routes/              # Route definitions
├── scripts/             # Deployment and management scripts
├── storage/             # Laravel storage (logs, cache, sessions)
├── tests/               # Test files
├── vendor/              # Composer dependencies
├── .env                 # Environment configuration
├── artisan              # Laravel command-line interface
├── composer.json        # PHP dependencies
├── Makefile             # Build and deployment commands
└── README.md            # This file
```

## 🔧 Troubleshooting

### Common Issues

1. **Port Conflicts**: Use `make network-status` to check port availability
2. **PHP Version Issues**: Use `make php-status` to verify PHP installation
3. **Web Server Conflicts**: The application automatically resolves most conflicts
4. **Permission Issues**: Ensure proper file permissions with `make setup`
5. **Laravel Issues**: Check `storage/logs/laravel.log` for application errors
6. **Composer Issues**: Run `composer install` if dependencies are missing

### Log Files
- **Application Logs**: `storage/logs/laravel.log`
- **Web Server Logs**: `/var/log/apache2/` or `/var/log/nginx/`
- **PHP-FPM Logs**: `/var/log/php{version}-fpm.log`
- **Deployment Logs**: `logs/` directory

### Laravel-Specific Troubleshooting
- **Clear Cache**: `php artisan cache:clear`
- **Clear Config**: `php artisan config:clear`
- **Clear Routes**: `php artisan route:clear`
- **Clear Views**: `php artisan view:clear`
- **Regenerate Key**: `php artisan key:generate`

## 🎯 Laravel-Specific Features

### APM Dashboard
- **Interactive UI**: Real-time monitoring dashboard built with Blade templates
- **AJAX Operations**: Database testing, API calls, queue management
- **Laravel Integration**: Uses Laravel's routing, middleware, and service container
- **Responsive Design**: Mobile-friendly interface with Bootstrap-like styling

### Database Operations
- **Connection Testing**: Test MySQL, PostgreSQL, and Redis connections
- **CRUD Demonstrations**: Showcase Eloquent ORM capabilities
- **Migration Support**: Database schema versioning
- **Seeder Integration**: Sample data generation

### Queue Management
- **Queue Testing**: Demonstrate Laravel's queue system
- **Job Processing**: Background job execution
- **Queue Monitoring**: Real-time queue status
- **Multiple Drivers**: Support for sync, database, and Redis queues

### API Integration
- **HTTP Client**: Laravel's built-in HTTP client for external APIs
- **Response Handling**: JSON response processing
- **Error Management**: Comprehensive error handling
- **Rate Limiting**: Built-in rate limiting support

## 🔧 Laravel Artisan Commands

### Available Artisan Commands
```bash
# Application management
php artisan serve                    # Start development server
php artisan config:cache            # Cache configuration
php artisan route:cache              # Cache routes
php artisan view:cache               # Cache views

# Database management
php artisan migrate                  # Run migrations
php artisan migrate:rollback         # Rollback migrations
php artisan db:seed                  # Run seeders
php artisan migrate:fresh --seed     # Fresh migration with seeding

# Cache management
php artisan cache:clear              # Clear application cache
php artisan config:clear             # Clear configuration cache
php artisan route:clear              # Clear route cache
php artisan view:clear               # Clear compiled views

# Queue management
php artisan queue:work               # Start queue worker
php artisan queue:restart            # Restart queue workers
php artisan queue:failed             # List failed jobs
php artisan queue:retry all          # Retry failed jobs

# Optimization
php artisan optimize                 # Optimize for production
php artisan optimize:clear           # Clear optimization caches
```

## 🚀 Performance Optimization

### Production Optimizations
```bash
# Run these commands for production deployment
php artisan config:cache             # Cache configuration
php artisan route:cache              # Cache routes
php artisan view:cache               # Cache views
php artisan optimize                 # General optimization
composer install --optimize-autoloader --no-dev
```

### OPcache Configuration
- **Optimized for Laravel**: Specific OPcache settings for Laravel applications
- **Memory Management**: Efficient memory usage for large applications
- **File Validation**: Balanced between performance and development flexibility

### Database Optimization
- **Query Caching**: Automatic query result caching
- **Connection Pooling**: Efficient database connection management
- **Index Optimization**: Proper database indexing strategies

## 🔐 Environment Configuration

### Environment Variables
```bash
# Application
APP_NAME="Laravel APM"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=http://localhost:8001

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

# Cache & Sessions
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Redis (optional)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Security Configuration
- **APP_KEY**: Automatically generated encryption key
- **CSRF Protection**: Enabled by default
- **XSS Protection**: Automatic output escaping
- **SQL Injection Prevention**: Eloquent ORM protection

## 🤝 Contributing

This application serves as a template for APM PHP examples using Laravel. Follow the established patterns when extending functionality.

### Development Guidelines
- Follow Laravel coding standards
- Use Eloquent ORM for database operations
- Implement proper error handling
- Write comprehensive tests
- Document new features

## 📄 License

This project is part of the APM PHP Examples collection.

---

**Ready for production deployment with comprehensive monitoring and scaling capabilities! 🚀**
