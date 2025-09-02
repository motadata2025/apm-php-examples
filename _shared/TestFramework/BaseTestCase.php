<?php

declare(strict_types=1);

namespace Shared\TestFramework;

use PHPUnit\Framework\TestCase;
use PDO;
use Redis;

/**
 * Base test case providing common functionality across all applications
 * Implements enterprise-grade testing patterns for APM PHP Examples
 */
abstract class BaseTestCase extends TestCase
{
    protected static ?PDO $mysqlConnection = null;
    protected static ?PDO $postgresConnection = null;
    protected static ?Redis $redisConnection = null;
    
    protected array $testData = [];
    protected array $createdRecords = [];
    
    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeTestData();
        $this->setupDatabaseConnections();
    }
    
    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        $this->cleanupCreatedRecords();
        $this->resetTestData();
        parent::tearDown();
    }
    
    /**
     * Set up database connections for testing
     */
    protected function setupDatabaseConnections(): void
    {
        if (self::$mysqlConnection === null) {
            self::$mysqlConnection = $this->createMySQLConnection();
        }
        
        if (self::$postgresConnection === null) {
            self::$postgresConnection = $this->createPostgreSQLConnection();
        }
        
        if (self::$redisConnection === null) {
            self::$redisConnection = $this->createRedisConnection();
        }
    }
    
    /**
     * Create MySQL test connection
     */
    protected function createMySQLConnection(): PDO
    {
        $host = $_ENV['MYSQL_HOST'] ?? 'localhost';
        $port = $_ENV['MYSQL_PORT'] ?? '3306';
        $database = $_ENV['MYSQL_DATABASE'] ?? 'test_db';
        $username = $_ENV['MYSQL_USERNAME'] ?? 'root';
        $password = $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';
        
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    
    /**
     * Create PostgreSQL test connection
     */
    protected function createPostgreSQLConnection(): PDO
    {
        $host = $_ENV['POSTGRES_HOST'] ?? 'localhost';
        $port = $_ENV['POSTGRES_PORT'] ?? '5432';
        $database = $_ENV['POSTGRES_DATABASE'] ?? 'test_db';
        $username = $_ENV['POSTGRES_USERNAME'] ?? 'postgres';
        $password = $_ENV['POSTGRES_PASSWORD'] ?? 'postgrespassword';
        
        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    
    /**
     * Create Redis test connection
     */
    protected function createRedisConnection(): Redis
    {
        $redis = new Redis();
        $host = $_ENV['REDIS_HOST'] ?? 'localhost';
        $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
        
        $redis->connect($host, $port);
        $redis->select(15); // Use database 15 for testing
        
        return $redis;
    }
    
    /**
     * Initialize test data
     */
    protected function initializeTestData(): void
    {
        $this->testData = [
            'users' => [
                [
                    'id' => 1,
                    'name' => 'Test User 1',
                    'email' => 'test1@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'id' => 2,
                    'name' => 'Test User 2',
                    'email' => 'test2@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ],
            'posts' => [
                [
                    'id' => 1,
                    'title' => 'Test Post 1',
                    'content' => 'This is test content for post 1',
                    'user_id' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ],
        ];
    }
    
    /**
     * Reset test data
     */
    protected function resetTestData(): void
    {
        $this->testData = [];
    }
    
    /**
     * Clean up created records
     */
    protected function cleanupCreatedRecords(): void
    {
        foreach ($this->createdRecords as $record) {
            $this->deleteRecord($record['table'], $record['id']);
        }
        $this->createdRecords = [];
        
        // Clear Redis test data
        if (self::$redisConnection) {
            self::$redisConnection->flushDB();
        }
    }
    
    /**
     * Create a test record and track it for cleanup
     */
    protected function createTestRecord(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = self::$mysqlConnection->prepare($sql);
        $stmt->execute($data);
        
        $id = (int)self::$mysqlConnection->lastInsertId();
        $this->createdRecords[] = ['table' => $table, 'id' => $id];
        
        return $id;
    }
    
    /**
     * Delete a record
     */
    protected function deleteRecord(string $table, int $id): void
    {
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = self::$mysqlConnection->prepare($sql);
        $stmt->execute(['id' => $id]);
    }
    
    /**
     * Assert that a database table exists
     */
    protected function assertTableExists(string $tableName, ?PDO $connection = null): void
    {
        $connection = $connection ?? self::$mysqlConnection;
        
        $sql = "SHOW TABLES LIKE :table";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['table' => $tableName]);
        
        $this->assertNotEmpty(
            $stmt->fetchAll(),
            "Table '{$tableName}' does not exist"
        );
    }
    
    /**
     * Assert that a record exists in database
     */
    protected function assertRecordExists(string $table, array $conditions, ?PDO $connection = null): void
    {
        $connection = $connection ?? self::$mysqlConnection;
        
        $whereClause = implode(' AND ', array_map(fn($key) => "{$key} = :{$key}", array_keys($conditions)));
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$whereClause}";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($conditions);
        $result = $stmt->fetch();
        
        $this->assertGreaterThan(0, $result['count'], "Record not found in table '{$table}'");
    }
    
    /**
     * Assert Redis key exists
     */
    protected function assertRedisKeyExists(string $key): void
    {
        $this->assertTrue(
            self::$redisConnection->exists($key),
            "Redis key '{$key}' does not exist"
        );
    }
    
    /**
     * Assert HTTP response is successful
     */
    protected function assertHttpSuccess(int $statusCode): void
    {
        $this->assertGreaterThanOrEqual(200, $statusCode);
        $this->assertLessThan(300, $statusCode);
    }
    
    /**
     * Assert response time is within acceptable limits
     */
    protected function assertResponseTimeAcceptable(float $responseTime, float $maxTime = 1.0): void
    {
        $this->assertLessThanOrEqual(
            $maxTime,
            $responseTime,
            "Response time {$responseTime}s exceeds maximum {$maxTime}s"
        );
    }
    
    /**
     * Get test data for a specific entity
     */
    protected function getTestData(string $entity): array
    {
        return $this->testData[$entity] ?? [];
    }
    
    /**
     * Create a mock HTTP response
     */
    protected function createMockHttpResponse(int $statusCode, array $data = []): array
    {
        return [
            'status_code' => $statusCode,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($data),
            'response_time' => 0.1,
        ];
    }
}
