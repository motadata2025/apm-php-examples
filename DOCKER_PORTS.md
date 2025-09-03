# Docker Port Allocation - APM PHP Examples

## Overview

This document defines the standardized port allocation scheme for all PHP framework applications in the APM PHP Examples project. Each application runs with completely isolated port assignments to prevent conflicts and ensure true independence.

## ✅ **Finalized Port Allocation**

| Application | MySQL | PostgreSQL | Redis  |
|-------------|-------|------------|-------|
| **simple-php**     | 3307 | 5433 | 6380 |
| **symfony-app** | 3308 | 5434 | 6381 |
| **slim-framework** | 3309 | 5435 | 6382 |
| **codeigniter-app** | 3310 | 5436 | 6383 |
| **laravel-app** | 3311 | 5437 | 6384 |

## 🔧 **Issues Fixed**

### **Port Conflicts Resolved**
1. **Standard Port Conflicts**: Removed usage of standard database ports (3306, 5432, 6379) to prevent interference with system databases
2. **Cross-Application Conflicts**: Eliminated overlapping port assignments between applications
3. **Container State Issues**: Fixed "Created" state containers that couldn't bind to conflicting ports

### **Configuration Standardization**
1. **Dual Docker Files**: Ensured all applications use `docker-compose.yml` (primary) with consistent port allocation
2. **Container Naming**: Standardized container names to match application prefixes
3. **Database Credentials**: Aligned database names and users with application-specific naming

### **Isolated**
- Each application has unique port range ✅
- No conflicts with standard database ports ✅
- All containers healthy and running ✅

## 🎯 **Design Principles**

### **Sequential Allocation**
- **MySQL**: 3307-3311 (incremental by application)
- **PostgreSQL**: 5433-5437 (incremental by application)
- **Redis**: 6380-6384 (incremental by application)

### **Conflict Avoidance**
- **System Ports**: Avoids 3306 (MySQL), 5432 (PostgreSQL), 6379 (Redis)
- **Application Isolation**: Each app has dedicated port range
- **Management Tools**: Unique ports for phpMyAdmin, pgAdmin, Redis Commander

### **Scalability**
- **Future Applications**: Can continue sequential pattern (3312+, 5438+, 6385+)
- **Port Ranges**: Sufficient spacing to avoid conflicts
- **Consistent Pattern**: Easy to predict and manage

## 🚀 **Verification Commands**

### **Check All Running Containers**
```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | sort
```

### **Verify Port Allocation**
```bash
# Check port usage
netstat -tuln | grep -E "(3307|3308|3309|3310|3311|5433|5434|5435|5436|5437|6380|6381|6382|6383|6384)"
```

## 📋 **Container Health Status**

All containers should show "healthy" status after startup:

```
NAMES                 STATUS                    PORTS
simple_php_mysql      Up X minutes (healthy)    0.0.0.0:3307->3306/tcp
simple_php_postgres   Up X minutes (healthy)    0.0.0.0:5433->5432/tcp
simple_php_redis      Up X minutes (healthy)    0.0.0.0:6380->6379/tcp
symfony_app_mysql     Up X minutes (healthy)    0.0.0.0:3308->3306/tcp
[... and so on for all applications]
```

## 🔍 **Troubleshooting**

### **Common Issues**
1. **Port Already in Use**: Check for conflicting services with `netstat -tuln | grep <port>`
2. **Container Won't Start**: Verify port availability and Docker daemon status
3. **Health Check Failures**: Check container logs with `docker logs <container_name>`

### **Resolution Steps**
1. **Stop Conflicting Services**: `docker stop <container_name>`
2. **Clean Up**: `docker rm <container_name>`
3. **Verify Status**: `docker ps` to confirm healthy containers