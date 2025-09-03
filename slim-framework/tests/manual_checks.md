# Slim Framework APM - Manual Testing Checklist

## Prerequisites

- [ ] PHP >= 8.0 installed
- [ ] Composer installed
- [ ] Docker and Docker Compose available
- [ ] Ports 3309, 5435, 6382, and 8083 available

## Setup Steps

### 1. Environment Setup
- [ ] Navigate to `slim-framework/` directory
- [ ] Run `composer install --no-interaction --prefer-dist --no-scripts`
- [ ] Verify `.env` file exists and contains correct configuration
- [ ] Check that `vendor/` directory was created

### 2. Start Services
- [ ] Run `docker compose up -d`
- [ ] Wait for services to start (30-60 seconds)
- [ ] Verify services are running: `docker compose ps`
- [ ] Check that all containers show "healthy" status

### 3. Start Application
- [ ] Run `php -S 0.0.0.0:8083 -t public`
- [ ] Verify server starts without errors
- [ ] Note: Keep this terminal open during testing

## UI Testing

### 4. Access Dashboard
- [ ] Open browser to `http://localhost:8083`
- [ ] Verify page loads without errors
- [ ] Check that page title is "Slim Framework APM Dashboard"
- [ ] Verify responsive layout works on different screen sizes

### 5. Visual Verification
- [ ] **Card 1 (Application Information)**:
  - [ ] Shows "Application Type: slim-framework"
  - [ ] Shows "Running PHP Version: [current PHP version]"
  - [ ] Shows "Web Server: php_cli"
  - [ ] Contains "External API Test" button
- [ ] **Card 2 (Database Operations)**:
  - [ ] Contains "Connection Check" button
  - [ ] Contains "DB CRUD Operations" button
- [ ] **Card 3 (Redis Queue Operations)**:
  - [ ] Contains "Insert 3 Items" button
  - [ ] Contains "Insert 1 + Count" button
  - [ ] Contains "Read 1 Item" button
  - [ ] Contains "Clear Queue" button

## Functional Testing

### 6. External API Test
- [ ] Click "External API Test" button
- [ ] Verify spinner appears during request
- [ ] Check that success toast appears
- [ ] Verify result shows HTTP status and duration
- [ ] Expected: HTTP 200 with response time in milliseconds

### 7. Database Connection Test
- [ ] Click "Connection Check" button
- [ ] Verify spinner appears during request
- [ ] Check that success toast appears
- [ ] Verify result shows "MySQL: OK, PostgreSQL: OK"
- [ ] If errors occur, note specific error messages

### 8. Database CRUD Test
- [ ] Click "DB CRUD Operations" button
- [ ] Verify spinner appears during request
- [ ] Check that success toast appears
- [ ] Verify result shows inserted IDs for both databases
- [ ] Expected format: "MySQL ID: [number], PG ID: [number]"

### 9. Redis Queue Tests

#### Insert Operations
- [ ] Click "Insert 3 Items" button
- [ ] Verify success toast shows "Inserted 3 items. Queue length: [number]"
- [ ] Click "Insert 1 + Count" button
- [ ] Verify success toast shows "Inserted 1 item. Queue length: [number]"
- [ ] Note: Queue length should increase with each operation

#### Read Operations
- [ ] Click "Read 1 Item" button (after inserting items)
- [ ] Verify success toast shows "Read 1 item. Remaining: [number]"
- [ ] Verify result shows truncated message content
- [ ] Note: Remaining count should decrease

#### Clear Operation
- [ ] Click "Clear Queue" button
- [ ] Verify success toast shows "Queue cleared. Previous length: [number]"
- [ ] Click "Read 1 Item" button again
- [ ] Verify warning toast shows "Queue is empty"

## API Testing (cURL)

### 10. Direct API Tests
Run these commands in a separate terminal:

```bash
# Database connection check
curl -X POST http://localhost:8083/api/db/check

# Database CRUD operations
curl -X POST http://localhost:8083/api/db/crud

# Redis operations
curl -X POST http://localhost:8083/api/redis/insert_bulk
curl -X POST http://localhost:8083/api/redis/insert_single
curl -X POST http://localhost:8083/api/redis/read_single
curl -X POST http://localhost:8083/api/redis/clear

# External API test
curl -X POST http://localhost:8083/api/external
```

### 11. API Response Verification
For each API call, verify:
- [ ] Response is valid JSON
- [ ] Contains `"ok": true` for successful operations
- [ ] Contains appropriate payload data
- [ ] Error responses contain `"ok": false` and error details

## Error Handling Testing

### 12. Service Failure Simulation
- [ ] Stop MySQL container: `docker stop [mysql_container_name]`
- [ ] Test database operations - should show MySQL error
- [ ] Restart MySQL: `docker start [mysql_container_name]`
- [ ] Repeat for PostgreSQL and Redis containers

### 13. Network Error Simulation
- [ ] Modify `.env` to use invalid `EXTERNAL_API_URL`
- [ ] Test external API - should show connection error
- [ ] Restore correct URL

## Performance Testing

### 14. Response Time Verification
- [ ] All API calls complete within 10 seconds
- [ ] External API calls show reasonable response times
- [ ] Database operations complete quickly (< 2 seconds)
- [ ] Redis operations are near-instantaneous (< 500ms)

## Cleanup

### 15. Shutdown
- [ ] Stop PHP server (Ctrl+C in terminal)
- [ ] Stop Docker services: `docker compose down`
- [ ] Verify all containers stopped: `docker compose ps`

## Expected Results Summary

✅ **All tests should pass with these expected behaviors:**
- UI loads and displays correctly
- All buttons work and show appropriate feedback
- Database operations succeed for both MySQL and PostgreSQL
- Redis queue operations work with proper naming convention
- External API calls succeed with valid responses
- Error handling works gracefully
- Performance is acceptable for all operations

❌ **Common failure points to investigate:**
- Port conflicts (change ports if needed)
- Missing PHP extensions
- Docker services not healthy
- Network connectivity issues
- Incorrect environment configuration

## Notes Section
Use this space to record any issues, observations, or deviations from expected behavior:

```
Date: ___________
Tester: ___________
PHP Version: ___________
OS: ___________

Issues found:
- 
- 
- 

Additional observations:
- 
- 
- 
```
