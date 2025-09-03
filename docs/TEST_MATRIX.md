# APM PHP Examples - Test Matrix

## Overview

This document tracks validation results for all PHP applications across different OS and PHP version combinations. Each entry represents a complete validation run including database connectivity, Redis connectivity, and HTTP functionality.

## Test Matrix

| OS | OS_Version | PHP_Version | App | Validated | Timestamp | Notes | Logs_Link |
|---|---|---|---|---|---|---|---|
| | | | | | | | |

## Legend

- **Validated**: Y = All tests passed, N = One or more tests failed, P = Partial (some tests passed)
- **Timestamp**: Unix timestamp of validation run
- **Notes**: Brief description of any issues or special conditions
- **Logs_Link**: Path to detailed validation logs

## Validation Criteria

Each application must pass the following tests to be marked as "Y":

1. **Environment Loading**: Successfully parse .env configuration
2. **MySQL Connectivity**: Connect to MySQL database and perform basic operations
3. **PostgreSQL Connectivity**: Connect to PostgreSQL database and perform basic operations  
4. **Redis Connectivity**: Connect to Redis cache and perform basic operations
5. **HTTP Connectivity**: Successfully make external HTTP requests

## Port Allocation

Per DOCKER_PORTS.md:

| Application | MySQL | PostgreSQL | Redis |
|-------------|-------|------------|-------|
| simple-php | 3307 | 5433 | 6380 |
| symfony-app | 3308 | 5434 | 6381 |
| slim-framework | 3309 | 5435 | 6382 |
| codeigniter-app | 3310 | 5436 | 6383 |
| laravel-app | 3311 | 5437 | 6384 |

## Usage

This matrix is automatically updated by the validation scripts:

1. `scripts/run_all_services.sh` - Start all required services
2. `scripts/validate_all.sh` - Run validation for all applications
3. Results are captured in `augment/validation_results/`
4. This matrix is updated with the results

## Troubleshooting

Common issues and solutions:

- **Port Conflicts**: Check if system services are using the allocated ports
- **Container Health**: Verify all Docker containers are healthy before validation
- **PHP Extensions**: Ensure required PHP extensions (PDO, Redis, curl) are installed
- **Network Connectivity**: Verify external HTTP access for httpbin.org tests

## Manual Validation

For manual testing on specific OS/PHP combinations:

1. Install PHP and required extensions for target version
2. Start services: `./scripts/run_all_services.sh`
3. Run validation: `./scripts/validate_all.sh`
4. Check results in `augment/validation_results/`
5. Update this matrix with results
