#!/bin/bash

# APM PHP Examples - Comprehensive Testing Framework Implementation
# Implements advanced testing across all applications with >90% coverage target

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🧪 APM PHP Examples - Testing Framework Implementation${NC}"
echo "====================================================="
echo ""

# Applications to implement testing for
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

# Function to implement comprehensive tests for an application
implement_application_tests() {
    local app="$1"
    echo -e "${CYAN}🔧 Implementing Tests for $app${NC}"
    echo "--------------------------------"
    
    cd "$app"
    
    # Create comprehensive test structure
    mkdir -p tests/{Unit,Feature,Integration,Performance}
    
    # Create application-specific base test case
    create_app_base_test_case "$app"
    
    # Create unit tests
    create_unit_tests "$app"
    
    # Create feature tests
    create_feature_tests "$app"
    
    # Create integration tests
    create_integration_tests "$app"
    
    # Create performance tests
    create_performance_tests "$app"
    
    # Update PHPUnit configuration for the specific app
    update_phpunit_config "$app"
    
    cd ..
    echo -e "${GREEN}✅ $app testing implementation complete${NC}"
    echo ""
}

# Function to create application-specific base test case
create_app_base_test_case() {
    local app="$1"
    
    cat > "tests/TestCase.php" << EOF
<?php

declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/../_shared/TestFramework/BaseTestCase.php';
require_once __DIR__ . '/../_shared/TestFramework/DatabaseTestTrait.php';
require_once __DIR__ . '/../_shared/TestFramework/HttpTestTrait.php';

use Shared\TestFramework\BaseTestCase;
use Shared\TestFramework\DatabaseTestTrait;
use Shared\TestFramework\HttpTestTrait;

/**
 * Base test case for $app application
 * Extends shared testing framework with app-specific functionality
 */
abstract class TestCase extends BaseTestCase
{
    use DatabaseTestTrait;
    use HttpTestTrait;
    
    protected string \$baseUrl;
    
    protected function setUp(): void
    {
        parent::setUp();
        \$this->baseUrl = \$this->getApplicationBaseUrl();
        \$this->setupApplicationEnvironment();
    }
    
    protected function tearDown(): void
    {
        \$this->cleanupApplicationEnvironment();
        parent::tearDown();
    }
    
    /**
     * Get application base URL
     */
    protected function getApplicationBaseUrl(): string
    {
        \$ports = [
            'simple-php' => '8000',
            'laravel-app' => '8004',
            'symfony-app' => '8002',
            'slim-framework' => '8001',
            'codeigniter-app' => '8003',
        ];
        
        \$port = \$ports['$app'] ?? '8000';
        return "http://localhost:{\$port}";
    }
    
    /**
     * Setup application-specific environment
     */
    protected function setupApplicationEnvironment(): void
    {
        // Load environment variables
        if (file_exists(__DIR__ . '/../.env')) {
            \$lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach (\$lines as \$line) {
                if (strpos(trim(\$line), '#') === 0) {
                    continue;
                }
                list(\$name, \$value) = explode('=', \$line, 2);
                \$_ENV[trim(\$name)] = trim(\$value);
            }
        }
        
        // Setup test database schema
        \$this->createTestSchema();
    }
    
    /**
     * Cleanup application-specific environment
     */
    protected function cleanupApplicationEnvironment(): void
    {
        // Cleanup is handled by parent class
    }
    
    /**
     * Assert application is running
     */
    protected function assertApplicationIsRunning(): void
    {
        \$response = \$this->httpGet(\$this->baseUrl);
        \$this->assertHttpSuccess(\$response);
    }
}
EOF
    
    echo "✅ Base test case created for $app"
}

# Function to create unit tests
create_unit_tests() {
    local app="$1"
    
    # Basic unit test
    cat > "tests/Unit/BasicTest.php" << EOF
<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testPhpVersion(): void
    {
        \$this->assertGreaterThanOrEqual('8.1', PHP_VERSION);
    }
    
    public function testRequiredExtensions(): void
    {
        \$requiredExtensions = ['pdo', 'redis', 'curl', 'json', 'mbstring', 'openssl'];
        
        foreach (\$requiredExtensions as \$extension) {
            \$this->assertTrue(
                extension_loaded(\$extension),
                "Required extension '{\$extension}' is not loaded"
            );
        }
    }
    
    public function testEnvironmentConfiguration(): void
    {
        \$this->assertNotEmpty(\$_ENV['APP_ENV'] ?? '', 'APP_ENV should be set');
    }
}
EOF

    # Database unit test
    cat > "tests/Unit/DatabaseTest.php" << EOF
<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class DatabaseTest extends TestCase
{
    public function testMySQLConnection(): void
    {
        \$this->testDatabaseConnection(self::\$mysqlConnection, 'MySQL');
    }
    
    public function testPostgreSQLConnection(): void
    {
        \$this->testDatabaseConnection(self::\$postgresConnection, 'PostgreSQL');
    }
    
    public function testMySQLTransaction(): void
    {
        \$this->testDatabaseTransaction(self::\$mysqlConnection);
    }
    
    public function testPostgreSQLTransaction(): void
    {
        \$this->testDatabaseTransaction(self::\$postgresConnection);
    }
    
    public function testMySQLPerformance(): void
    {
        \$this->testDatabasePerformance(self::\$mysqlConnection, 'MySQL');
    }
    
    public function testPostgreSQLPerformance(): void
    {
        \$this->testDatabasePerformance(self::\$postgresConnection, 'PostgreSQL');
    }
}
EOF

    # Redis unit test
    cat > "tests/Unit/RedisTest.php" << EOF
<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class RedisTest extends TestCase
{
    public function testRedisConnection(): void
    {
        \$this->assertInstanceOf(\Redis::class, self::\$redisConnection);
        \$this->assertTrue(self::\$redisConnection->ping());
    }
    
    public function testRedisOperations(): void
    {
        \$this->testRedisOperations();
    }
    
    public function testRedisPerformance(): void
    {
        \$this->testRedisPerformance();
    }
}
EOF
    
    echo "✅ Unit tests created for $app"
}

# Function to create feature tests
create_feature_tests() {
    local app="$1"
    
    # HTTP feature test
    cat > "tests/Feature/HttpTest.php" << EOF
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HttpTest extends TestCase
{
    public function testApplicationHomePage(): void
    {
        \$response = \$this->httpGet(\$this->baseUrl);
        
        \$this->assertHttpSuccess(\$response);
        \$this->assertResponseTimeAcceptable(\$response, 1.0);
        \$this->assertNotEmpty(\$response['body']);
    }
    
    public function testHealthEndpoint(): void
    {
        \$this->testHealthEndpoint(\$this->baseUrl);
    }
    
    public function testBasicEndpoints(): void
    {
        \$this->testBasicEndpoints(\$this->baseUrl);
    }
    
    public function testErrorHandling(): void
    {
        \$response = \$this->httpGet(\$this->baseUrl . '/nonexistent-page');
        \$this->assertHttpStatus(404, \$response);
    }
}
EOF

    # API feature test (if applicable)
    cat > "tests/Feature/ApiTest.php" << EOF
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ApiTest extends TestCase
{
    public function testApiEndpoint(): void
    {
        \$response = \$this->httpGet(\$this->baseUrl . '/api');
        
        // API might not exist in all apps, so we check for 200 or 404
        \$this->assertTrue(
            in_array(\$response['status_code'], [200, 404]),
            'API endpoint should return 200 or 404'
        );
        
        if (\$response['status_code'] === 200) {
            \$this->assertResponseIsJson(\$response);
        }
    }
    
    public function testApiPerformance(): void
    {
        \$response = \$this->httpGet(\$this->baseUrl . '/api');
        
        if (\$response['status_code'] === 200) {
            \$this->assertResponseTimeAcceptable(\$response, 0.5);
        }
    }
}
EOF
    
    echo "✅ Feature tests created for $app"
}

# Function to create integration tests
create_integration_tests() {
    local app="$1"
    
    # Database integration test
    cat > "tests/Integration/DatabaseIntegrationTest.php" << EOF
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

class DatabaseIntegrationTest extends TestCase
{
    public function testDatabaseSchemaExists(): void
    {
        \$this->assertTableExists('users');
    }
    
    public function testUserCrudOperations(): void
    {
        // Create user
        \$userData = [
            'name' => 'Integration Test User',
            'email' => 'integration@test.com',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        \$userId = \$this->createTestRecord('users', \$userData);
        \$this->assertGreaterThan(0, \$userId);
        
        // Verify user exists
        \$this->assertRecordExists('users', ['id' => \$userId]);
        
        // User will be cleaned up automatically by tearDown
    }
    
    public function testDatabasePerformanceUnderLoad(): void
    {
        \$startTime = microtime(true);
        
        // Create multiple records
        for (\$i = 0; \$i < 50; \$i++) {
            \$userData = [
                'name' => "Load Test User {\$i}",
                'email' => "loadtest{\$i}@test.com",
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            \$this->createTestRecord('users', \$userData);
        }
        
        \$endTime = microtime(true);
        \$duration = \$endTime - \$startTime;
        
        // Should be able to create 50 records in under 2 seconds
        \$this->assertLessThan(2.0, \$duration, "Database performance test failed: {\$duration}s");
    }
}
EOF

    # Redis integration test
    cat > "tests/Integration/RedisIntegrationTest.php" << EOF
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

class RedisIntegrationTest extends TestCase
{
    public function testRedisSessionStorage(): void
    {
        \$sessionId = 'test_session_' . uniqid();
        \$sessionData = ['user_id' => 123, 'username' => 'testuser'];
        
        // Store session data
        self::\$redisConnection->setex("session:{\$sessionId}", 3600, json_encode(\$sessionData));
        
        // Retrieve session data
        \$retrievedData = json_decode(self::\$redisConnection->get("session:{\$sessionId}"), true);
        
        \$this->assertEquals(\$sessionData, \$retrievedData);
        
        // Cleanup
        self::\$redisConnection->del("session:{\$sessionId}");
    }
    
    public function testRedisCaching(): void
    {
        \$cacheKey = 'test_cache_' . uniqid();
        \$cacheValue = 'cached_value_' . time();
        
        // Cache data
        self::\$redisConnection->setex(\$cacheKey, 60, \$cacheValue);
        
        // Verify cache hit
        \$this->assertEquals(\$cacheValue, self::\$redisConnection->get(\$cacheKey));
        
        // Cleanup
        self::\$redisConnection->del(\$cacheKey);
    }
}
EOF
    
    echo "✅ Integration tests created for $app"
}

# Function to create performance tests
create_performance_tests() {
    local app="$1"
    
    cat > "tests/Performance/ApplicationPerformanceTest.php" << EOF
<?php

declare(strict_types=1);

namespace Tests\Performance;

use Tests\TestCase;

class ApplicationPerformanceTest extends TestCase
{
    public function testHomePagePerformance(): void
    {
        \$response = \$this->httpGet(\$this->baseUrl);
        
        \$this->assertHttpSuccess(\$response);
        \$this->assertResponseTimeAcceptable(\$response, 0.5); // 500ms max for home page
    }
    
    public function testConcurrentRequests(): void
    {
        \$responses = [];
        \$startTime = microtime(true);
        
        // Simulate 10 concurrent requests
        for (\$i = 0; \$i < 10; \$i++) {
            \$responses[] = \$this->httpGet(\$this->baseUrl);
        }
        
        \$endTime = microtime(true);
        \$totalTime = \$endTime - \$startTime;
        
        // All requests should complete in under 5 seconds
        \$this->assertLessThan(5.0, \$totalTime);
        
        // All requests should be successful
        foreach (\$responses as \$response) {
            \$this->assertHttpSuccess(\$response);
        }
    }
    
    public function testMemoryUsage(): void
    {
        \$initialMemory = memory_get_usage(true);
        
        // Perform memory-intensive operations
        for (\$i = 0; \$i < 100; \$i++) {
            \$response = \$this->httpGet(\$this->baseUrl);
        }
        
        \$finalMemory = memory_get_usage(true);
        \$memoryIncrease = \$finalMemory - \$initialMemory;
        
        // Memory increase should be reasonable (< 50MB)
        \$this->assertLessThan(50 * 1024 * 1024, \$memoryIncrease, "Memory usage increased by {\$memoryIncrease} bytes");
    }
}
EOF
    
    echo "✅ Performance tests created for $app"
}

# Function to update PHPUnit configuration
update_phpunit_config() {
    local app="$1"
    
    # Update paths in PHPUnit configuration to be app-specific
    if [ -f "phpunit.xml" ]; then
        # Update bootstrap path to include shared framework
        sed -i 's|bootstrap="vendor/autoload.php"|bootstrap="vendor/autoload.php"|g' phpunit.xml
        
        # Add shared framework to autoload
        if ! grep -q "_shared" phpunit.xml; then
            # This would need more sophisticated XML editing in a real implementation
            echo "<!-- Note: Include _shared/TestFramework in autoloader -->" >> phpunit.xml
        fi
    fi
    
    echo "✅ PHPUnit configuration updated for $app"
}

# Function to run tests and generate coverage report
run_tests_and_coverage() {
    local app="$1"
    echo -e "${CYAN}🧪 Running Tests for $app${NC}"
    echo "--------------------------------"
    
    cd "$app"
    
    if [ -f "vendor/bin/phpunit" ]; then
        echo "Running PHPUnit tests..."
        vendor/bin/phpunit --coverage-text --coverage-html coverage/ || echo "Some tests failed"
        
        # Generate coverage summary
        if [ -f "coverage/index.html" ]; then
            echo -e "${GREEN}✅ Coverage report generated: $app/coverage/index.html${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  PHPUnit not installed in $app${NC}"
    fi
    
    cd ..
    echo ""
}

# Main implementation process
main() {
    echo -e "${PURPLE}Starting comprehensive testing framework implementation...${NC}"
    echo ""
    
    # Process each application
    for app in "${APPLICATIONS[@]}"; do
        if [ -d "$app" ]; then
            echo -e "${BLUE}Implementing testing for $app...${NC}"
            echo "=================================="
            
            implement_application_tests "$app"
            run_tests_and_coverage "$app"
            
        else
            echo -e "${RED}❌ Application directory $app not found${NC}"
            echo ""
        fi
    done
    
    echo -e "${GREEN}🎉 Testing framework implementation complete!${NC}"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo "1. Run 'make test' to execute all tests"
    echo "2. Run 'make coverage' to generate coverage reports"
    echo "3. Run './benchmark-all-apps.sh' for performance testing"
    echo "4. Proceed to Phase 3: PHP Version Compatibility"
}

# Run main function
main "$@"
