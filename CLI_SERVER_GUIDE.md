# PHP APM Applications - CLI Server Guide

## 🚀 CLI Server Mode Setup Complete

All PHP APM applications are now configured for **business-ready CLI server mode** with configurable IP:Port settings.

## 📋 Available Applications

| Application | Framework | Default Port | Status |
|-------------|-----------|--------------|--------|
| simple-php | Pure PHP | 8080 | ✅ Ready |
| laravel-app | Laravel | 8081 | ✅ Ready |
| symfony-app | Symfony | 8082 | ✅ Ready |
| slim-framework | Slim | 8083 | ✅ Ready |
| codeigniter-app | CodeIgniter | 8084 | ✅ Ready |

## 🔧 Usage Methods

### Method 1: Universal Startup Script

```bash
# Basic usage (defaults to simple-php on 0.0.0.0:8080)
./start-cli-server.sh

# Specify application
./start-cli-server.sh laravel-app

# Specify application and IP
./start-cli-server.sh symfony-app 127.0.0.1

# Full specification: application, IP, and port
./start-cli-server.sh slim-framework 0.0.0.0 9000
```

### Method 2: Individual Application Scripts

```bash
# Start specific applications with custom IP:Port
./start-simple-php.sh 127.0.0.1 8080
./start-laravel-app.sh 0.0.0.0 8081
./start-symfony-app.sh 127.0.0.1 8082
./start-slim-framework.sh 0.0.0.0 8083
./start-codeigniter-app.sh 127.0.0.1 8084
```

### Method 3: Direct PHP CLI Server

```bash
# Manual startup for any application
cd [application-name]
php -S [IP]:[PORT] -t public
```

## 🌐 Network Configuration

### IP Address Options

- **`0.0.0.0`** - Listen on all network interfaces (external access allowed)
- **`127.0.0.1`** - Listen on localhost only (local access only)
- **Custom IP** - Listen on specific network interface

### Port Configuration

- **Development**: 8080-8089
- **Testing**: 9000-9009
- **Staging**: 7000-7009
- **Valid Range**: 1024-65535

## 📊 Application Endpoints

### Common Endpoints (All Applications)

| Endpoint | Purpose | Response |
|----------|---------|----------|
| `/` | Main dashboard | HTML/JSON |
| `/health` | Health check | JSON status |
| `/monitoring` | System monitoring | HTML/JSON |

### Application-Specific Endpoints

#### Simple PHP
- `/api/health` - API health endpoint
- `/api/metrics` - Performance metrics

#### Laravel App
- `/apm` - APM dashboard
- `/api/health` - Laravel health check

#### Symfony App
- `/apm` - Symfony APM interface

#### Slim Framework
- `/apm` - Slim APM monitoring

#### CodeIgniter App
- `/apm` - CodeIgniter APM dashboard

## 🔍 Verification Steps

### 1. Test Application Startup

```bash
# Start application
./start-cli-server.sh [app-name] [ip] [port]

# Verify in browser or curl
curl http://[ip]:[port]/
curl http://[ip]:[port]/health
```

### 2. Test Different IP:Port Combinations

```bash
# Test localhost access
./start-cli-server.sh simple-php 127.0.0.1 8080

# Test external access
./start-cli-server.sh laravel-app 0.0.0.0 8081

# Test custom ports
./start-cli-server.sh symfony-app 127.0.0.1 9000
```

### 3. Verify Full Functionality

```bash
# Check all endpoints work
curl http://127.0.0.1:8080/          # Main page
curl http://127.0.0.1:8080/health    # Health check
curl http://127.0.0.1:8080/monitoring # Monitoring
```

## ⚡ Quick Start Examples

### Development Setup (All Applications)

```bash
# Terminal 1: Simple PHP
./start-simple-php.sh 127.0.0.1 8080

# Terminal 2: Laravel
./start-laravel-app.sh 127.0.0.1 8081

# Terminal 3: Symfony
./start-symfony-app.sh 127.0.0.1 8082

# Terminal 4: Slim Framework
./start-slim-framework.sh 127.0.0.1 8083

# Terminal 5: CodeIgniter
./start-codeigniter-app.sh 127.0.0.1 8084
```

### Production-Like Setup (External Access)

```bash
# Start all applications for external access
./start-simple-php.sh 0.0.0.0 8080
./start-laravel-app.sh 0.0.0.0 8081
./start-symfony-app.sh 0.0.0.0 8082
./start-slim-framework.sh 0.0.0.0 8083
./start-codeigniter-app.sh 0.0.0.0 8084
```

## 🛠️ Troubleshooting

### Port Already in Use
```bash
# Check what's using the port
netstat -tuln | grep :8080

# Use a different port
./start-cli-server.sh simple-php 127.0.0.1 8090
```

### Permission Issues
```bash
# Make scripts executable
chmod +x start-*.sh
chmod +x start-cli-server.sh
```

### Application Not Starting
```bash
# Check application directory exists
ls -la [application-name]/

# Check public directory exists
ls -la [application-name]/public/

# Check PHP syntax
php -l [application-name]/public/index.php
```

## ✅ Phase 2 Complete

**All applications now support:**
- ✅ Configurable IP:Port settings
- ✅ Universal and individual startup scripts
- ✅ Full CLI server functionality
- ✅ Business-ready operation
- ✅ External and localhost access options

**Ready for Phase 3: Multi-PHP Version Compatibility Testing**
