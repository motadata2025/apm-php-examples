# Database Setup Guide

## 🗄️ Database Configuration for PHP APM Applications

This guide explains how to set up databases for the PHP APM applications. **Note**: Database servers are optional for basic functionality demonstration.

---

## 📊 Current Status

When you see database connection failures like:
```json
{
  "success": true,
  "data": {
    "mysql": "Failed: MySQL connection failed: SQLSTATE[HY000] [2002] No such file or directory",
    "postgres": "Failed: PostgreSQL connection failed: SQLSTATE[08006] [7] connection to server...",
    "redis": "Connected"
  }
}
```

**This is normal and expected behavior** when database servers are not installed. The applications will still function for demonstration purposes.

---

## 🚀 Quick Demo Mode (No Database Setup Required)

For demonstration purposes, you can run all applications without setting up databases:

```bash
# Start applications (databases optional)
./start-cli-server.sh simple-php 0.0.0.0 8080 &
./start-cli-server.sh laravel-app 0.0.0.0 8081 &
./start-cli-server.sh symfony-app 0.0.0.0 8082 &
./start-cli-server.sh slim-framework 0.0.0.0 8083 &
./start-cli-server.sh codeigniter-app 0.0.0.0 8084 &

# Test applications (will show database connection status)
curl http://localhost:8080/
curl http://localhost:8081/
curl http://localhost:8082/
curl http://localhost:8083/
curl http://localhost:8084/
```

**Expected behavior**: Applications will load and show database connection status. Failed database connections are informational only.

---

## 🔧 Full Database Setup (Optional)

If you want to set up databases for full functionality:

### Ubuntu/Debian Database Installation

```bash
# Update package list
sudo apt update

# Install MySQL
sudo apt install -y mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Install Redis (already working in most setups)
sudo apt install -y redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

### CentOS/RHEL Database Installation

```bash
# Install MySQL
sudo dnf install -y mysql-server
sudo systemctl start mysqld
sudo systemctl enable mysqld

# Install PostgreSQL
sudo dnf install -y postgresql postgresql-server
sudo postgresql-setup --initdb
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Install Redis
sudo dnf install -y redis
sudo systemctl start redis
sudo systemctl enable redis
```

---

## 🔑 Database Configuration

### MySQL Setup

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
-- Create database
CREATE DATABASE apm_examples;

-- Create user
CREATE USER 'apm_user'@'localhost' IDENTIFIED BY 'apm_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON apm_examples.* TO 'apm_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### PostgreSQL Setup

```bash
# Switch to postgres user
sudo -u postgres psql
```

```sql
-- Create database
CREATE DATABASE apm_examples;

-- Create user
CREATE USER apm_user WITH PASSWORD 'apm_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE apm_examples TO apm_user;

-- Exit PostgreSQL
\q
```

### Redis Setup

Redis typically works out of the box. Test with:

```bash
redis-cli ping
# Should return: PONG
```

---

## ⚙️ Environment Configuration

Create `.env` files in each application directory:

### Simple PHP (.env)
```bash
# MySQL Configuration
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_DATABASE=apm_examples
MYSQL_USERNAME=apm_user
MYSQL_PASSWORD=apm_password

# PostgreSQL Configuration
POSTGRES_HOST=localhost
POSTGRES_PORT=5432
POSTGRES_DATABASE=apm_examples
POSTGRES_USERNAME=apm_user
POSTGRES_PASSWORD=apm_password

# Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
```

### Laravel (.env)
```bash
APP_NAME=Laravel_APM
APP_ENV=local
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=true
APP_URL=http://localhost:8081

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=apm_examples
DB_USERNAME=apm_user
DB_PASSWORD=apm_password

# Redis Configuration
REDIS_HOST=localhost
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Symfony (.env)
```bash
APP_ENV=dev
APP_SECRET=$(openssl rand -hex 32)

# Database Configuration
DATABASE_URL="mysql://apm_user:apm_password@localhost:3306/apm_examples"

# Redis Configuration
REDIS_URL=redis://localhost:6379
```

---

## 🧪 Testing Database Connections

### Test Individual Connections

```bash
# Test MySQL
mysql -u apm_user -p apm_examples -e "SELECT 1;"

# Test PostgreSQL
psql -h localhost -U apm_user -d apm_examples -c "SELECT 1;"

# Test Redis
redis-cli ping
```

### Test Application Connections

```bash
# Test Simple PHP database connections
curl -X POST http://localhost:8080/ -d "action=test_databases"

# Expected output with databases configured:
# {
#   "success": true,
#   "data": {
#     "mysql": "Connected",
#     "postgres": "Connected", 
#     "redis": "Connected"
#   }
# }
```

---

## 🔍 Troubleshooting

### Common Issues

#### MySQL Socket Error
```bash
# Error: No such file or directory
# Solution: Check MySQL service status
sudo systemctl status mysql
sudo systemctl start mysql
```

#### PostgreSQL Connection Refused
```bash
# Error: Connection refused
# Solution: Check PostgreSQL service status
sudo systemctl status postgresql
sudo systemctl start postgresql
```

#### Redis Connection Issues
```bash
# Error: Redis connection failed
# Solution: Check Redis service status
sudo systemctl status redis-server  # Ubuntu/Debian
sudo systemctl status redis         # CentOS/RHEL
```

#### Permission Issues
```bash
# MySQL permission denied
# Solution: Check user privileges
mysql -u root -p -e "SHOW GRANTS FOR 'apm_user'@'localhost';"

# PostgreSQL permission denied
# Solution: Check user privileges
sudo -u postgres psql -c "\du apm_user"
```

---

## 🎯 Production Considerations

### Security Best Practices

1. **Use Strong Passwords**
   ```bash
   # Generate secure passwords
   openssl rand -base64 32
   ```

2. **Restrict Database Access**
   ```sql
   -- MySQL: Limit to specific hosts
   CREATE USER 'apm_user'@'192.168.1.%' IDENTIFIED BY 'strong_password';
   
   -- PostgreSQL: Configure pg_hba.conf
   # host apm_examples apm_user 192.168.1.0/24 md5
   ```

3. **Use SSL/TLS Connections**
   ```bash
   # MySQL with SSL
   mysql --ssl-mode=REQUIRED -u apm_user -p
   
   # PostgreSQL with SSL
   psql "sslmode=require host=localhost user=apm_user dbname=apm_examples"
   ```

### Performance Optimization

1. **MySQL Configuration**
   ```ini
   # /etc/mysql/mysql.conf.d/mysqld.cnf
   [mysqld]
   innodb_buffer_pool_size = 1G
   query_cache_size = 256M
   max_connections = 200
   ```

2. **PostgreSQL Configuration**
   ```ini
   # /etc/postgresql/*/main/postgresql.conf
   shared_buffers = 256MB
   effective_cache_size = 1GB
   max_connections = 200
   ```

3. **Redis Configuration**
   ```ini
   # /etc/redis/redis.conf
   maxmemory 512mb
   maxmemory-policy allkeys-lru
   save 900 1
   ```

---

## 📊 Database Schema

The applications will automatically create required tables when databases are available. You can also manually create them:

```bash
# Create tables via application
curl -X POST http://localhost:8080/ -d "action=create_tables"
```

---

## ✅ Verification Checklist

- [ ] MySQL server installed and running
- [ ] PostgreSQL server installed and running  
- [ ] Redis server installed and running
- [ ] Database users created with proper privileges
- [ ] Environment files configured
- [ ] Application connections tested
- [ ] Tables created successfully

---

## 🎉 Conclusion

**For demonstration purposes, databases are optional.** The applications will show connection status and continue to function for APM monitoring demonstration.

**For production use, follow the full setup guide** to enable complete database functionality.

The applications are designed to gracefully handle missing database connections, making them perfect for:
- Quick demonstrations
- Development environments
- Gradual deployment scenarios
- Testing and validation

---

**✅ Your PHP APM applications are ready to showcase with or without databases!**
