# Docker Port Allocation - APM PHP Examples

## Overview

This document defines the standardized port allocation scheme for all PHP framework applications in the APM PHP Examples project. Each application runs with completely isolated port assignments to prevent conflicts and ensure true independence.

## ✅ **Finalized Port Allocation**

|     Application     | MySQL | PostgreSQL | Redis | phpMyAdmin | pgAdmin | Redis Commander |
|---------------------|-------|------------|-------|------------|---------|-----------------|
| **simple-php**      | 3307  |     5433   | 6380  |    8080    |   8081  |       8082      |
| **symfony-app**     | 3308  |     5434   | 6381  |    8083    |   8084  |       8085      |
| **slim-framework**  | 3309  |     5435   | 6382  |    8086    |   8087  |       8088      |
| **codeigniter-app** | 3310  |     5436   | 6383  |    8089    |   8090  |       8091      |
| **laravel-app**     | 3311  |     5437   | 6384  |    8092    |   8093  |       8094      |

## 🔧 **Issues Fixed**

### **Port Conflicts Resolved**
1. **Standard Port Conflicts**: Removed usage of standard database ports (3306, 5432, 6379) to prevent interference with system databases
2. **Cross-Application Conflicts**: Eliminated overlapping port assignments between applications
3. **Container State Issues**: Fixed "Created" state containers that couldn't bind to conflicting ports

### **Configuration Standardization**
1. **Dual Docker Files**: Ensured all applications use `docker-compose.yml` (primary) with consistent port allocation
2. **Container Naming**: Standardized container names to match application prefixes
3. **Database Credentials**: Aligned database names and users with application-specific naming

### **Before vs After**

#### **Before (Conflicting)**
- simple-php: MySQL 3306 ❌, PostgreSQL 5432 ❌, Redis 6379 ❌
- Multiple applications sharing same ports ❌
- Containers in "Created" state due to port conflicts ❌

#### **After (Isolated)**
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

### **Test Application Setup**
```bash
# Test each application independently
cd simple-php && make setup
cd symfony-app && make setup
cd slim-framework && make setup
cd codeigniter-app && make setup
cd laravel-app && make setup
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

## 🔄 **Migration Notes**

### **Changes Made**
1. **Updated `docker-compose.yml`** files for all applications with correct port allocation
2. **Fixed container naming** to match application prefixes consistently
3. **Standardized database credentials** per application
4. **Removed port conflicts** with system services

### **Backward Compatibility**
- **Configuration Files**: Applications may need recompilation to pick up new port settings
- **Database Connections**: Application code already uses correct ports via environment variables
- **No Code Changes**: Application logic remains unchanged

## 🛡️ **Best Practices**

### **Adding New Applications**
1. **Follow Sequential Pattern**: Use next available ports (3312+, 5438+, 6385+)
2. **Update This Document**: Add new application to the port allocation table
3. **Test Isolation**: Verify no conflicts with existing applications
4. **Consistent Naming**: Use application-specific container and database names

### **Port Management**
- **Never Use Standard Ports**: Avoid 3306, 5432, 6379 for applications
- **Reserve Port Ranges**: Keep sufficient spacing between applications
- **Document Changes**: Update this file when modifying port allocations
- **Test Thoroughly**: Verify all services start and remain healthy

## 🔍 **Troubleshooting**

### **Common Issues**
1. **Port Already in Use**: Check for conflicting services with `netstat -tuln | grep <port>`
2. **Container Won't Start**: Verify port availability and Docker daemon status
3. **Health Check Failures**: Check container logs with `docker logs <container_name>`

### **Resolution Steps**
1. **Stop Conflicting Services**: `docker stop <container_name>`
2. **Clean Up**: `docker rm <container_name>`
3. **Restart Application**: `make setup` in application directory
4. **Verify Status**: `docker ps` to confirm healthy containers

---

**Last Updated**: 2024-12-19  
**Status**: ✅ All applications verified with conflict-free port allocation  
**Next Review**: When adding new applications or modifying existing configurations
