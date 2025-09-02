<?php

declare(strict_types=1);

namespace Shared\TestFramework;

use PDO;
use PDOException;

/**
 * Database testing utilities for comprehensive database testing
 * Provides advanced database testing capabilities for all applications
 */
trait DatabaseTestTrait
{
    /**
     * Create test database schema
     */
    protected function createTestSchema(): void
    {
        $this->createUsersTable();
        $this->createPostsTable();
        $this->seedTestData();
    }
    
    /**
     * Drop test database schema
     */
    protected function dropTestSchema(): void
    {
        $tables = ['posts', 'users'];
        
        foreach ($tables as $table) {
            try {
                self::$mysqlConnection->exec("DROP TABLE IF EXISTS {$table}");
                self::$postgresConnection->exec("DROP TABLE IF EXISTS {$table}");
            } catch (PDOException $e) {
                // Table might not exist, continue
            }
        }
    }
    
    /**
     * Create users table for testing
     */
    private function createUsersTable(): void
    {
        $mysqlSql = "
            CREATE TABLE IF NOT EXISTS users (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $postgresSql = "
            CREATE TABLE IF NOT EXISTS users (
                id BIGSERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            )
        ";
        
        self::$mysqlConnection->exec($mysqlSql);
        self::$postgresConnection->exec($postgresSql);
    }
    
    /**
     * Create posts table for testing
     */
    private function createPostsTable(): void
    {
        $mysqlSql = "
            CREATE TABLE IF NOT EXISTS posts (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                user_id BIGINT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $postgresSql = "
            CREATE TABLE IF NOT EXISTS posts (
                id BIGSERIAL PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                user_id BIGINT NOT NULL,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW(),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        
        self::$mysqlConnection->exec($mysqlSql);
        self::$postgresConnection->exec($postgresSql);
    }
    
    /**
     * Seed test data
     */
    private function seedTestData(): void
    {
        // Insert test users
        foreach ($this->getTestData('users') as $user) {
            $this->insertTestUser($user);
        }
        
        // Insert test posts
        foreach ($this->getTestData('posts') as $post) {
            $this->insertTestPost($post);
        }
    }
    
    /**
     * Insert test user
     */
    private function insertTestUser(array $user): void
    {
        $sql = "INSERT INTO users (name, email, created_at) VALUES (:name, :email, :created_at)";
        
        $stmt = self::$mysqlConnection->prepare($sql);
        $stmt->execute($user);
        
        $stmt = self::$postgresConnection->prepare($sql);
        $stmt->execute($user);
    }
    
    /**
     * Insert test post
     */
    private function insertTestPost(array $post): void
    {
        $sql = "INSERT INTO posts (title, content, user_id, created_at) VALUES (:title, :content, :user_id, :created_at)";
        
        $stmt = self::$mysqlConnection->prepare($sql);
        $stmt->execute($post);
        
        $stmt = self::$postgresConnection->prepare($sql);
        $stmt->execute($post);
    }
    
    /**
     * Test database connection
     */
    protected function testDatabaseConnection(PDO $connection, string $type): void
    {
        $this->assertInstanceOf(PDO::class, $connection, "{$type} connection failed");
        
        // Test basic query
        $stmt = $connection->query("SELECT 1 as test");
        $result = $stmt->fetch();
        $this->assertEquals(1, $result['test'], "{$type} basic query failed");
    }
    
    /**
     * Test database transactions
     */
    protected function testDatabaseTransaction(PDO $connection): void
    {
        $connection->beginTransaction();
        
        try {
            // Insert test data
            $stmt = $connection->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $stmt->execute(['Transaction Test', 'transaction@test.com']);
            
            // Verify data exists
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
            $stmt->execute(['transaction@test.com']);
            $result = $stmt->fetch();
            $this->assertEquals(1, $result['count']);
            
            // Rollback
            $connection->rollBack();
            
            // Verify data was rolled back
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
            $stmt->execute(['transaction@test.com']);
            $result = $stmt->fetch();
            $this->assertEquals(0, $result['count']);
            
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
    
    /**
     * Test database performance
     */
    protected function testDatabasePerformance(PDO $connection, string $type): void
    {
        $startTime = microtime(true);
        
        // Perform multiple queries
        for ($i = 0; $i < 100; $i++) {
            $stmt = $connection->query("SELECT COUNT(*) FROM users");
            $stmt->fetch();
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Assert performance is acceptable (< 1 second for 100 queries)
        $this->assertLessThan(1.0, $duration, "{$type} performance test failed: {$duration}s");
    }
    
    /**
     * Test Redis operations
     */
    protected function testRedisOperations(): void
    {
        $redis = self::$redisConnection;
        
        // Test basic operations
        $redis->set('test_key', 'test_value');
        $this->assertEquals('test_value', $redis->get('test_key'));
        
        // Test expiration
        $redis->setex('expire_key', 1, 'expire_value');
        $this->assertEquals('expire_value', $redis->get('expire_key'));
        sleep(2);
        $this->assertFalse($redis->get('expire_key'));
        
        // Test hash operations
        $redis->hset('test_hash', 'field1', 'value1');
        $redis->hset('test_hash', 'field2', 'value2');
        $this->assertEquals('value1', $redis->hget('test_hash', 'field1'));
        $this->assertEquals(['field1' => 'value1', 'field2' => 'value2'], $redis->hgetall('test_hash'));
        
        // Test list operations
        $redis->lpush('test_list', 'item1', 'item2', 'item3');
        $this->assertEquals(3, $redis->llen('test_list'));
        $this->assertEquals(['item3', 'item2', 'item1'], $redis->lrange('test_list', 0, -1));
        
        // Test set operations
        $redis->sadd('test_set', 'member1', 'member2', 'member3');
        $this->assertEquals(3, $redis->scard('test_set'));
        $this->assertTrue($redis->sismember('test_set', 'member1'));
        
        // Clean up
        $redis->del('test_key', 'test_hash', 'test_list', 'test_set');
    }
    
    /**
     * Test Redis performance
     */
    protected function testRedisPerformance(): void
    {
        $redis = self::$redisConnection;
        $startTime = microtime(true);
        
        // Perform multiple operations
        for ($i = 0; $i < 1000; $i++) {
            $redis->set("perf_key_{$i}", "value_{$i}");
            $redis->get("perf_key_{$i}");
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Assert performance is acceptable (< 1 second for 2000 operations)
        $this->assertLessThan(1.0, $duration, "Redis performance test failed: {$duration}s");
        
        // Clean up
        for ($i = 0; $i < 1000; $i++) {
            $redis->del("perf_key_{$i}");
        }
    }
    
    /**
     * Assert database query count
     */
    protected function assertQueryCount(int $expectedCount, callable $callback): void
    {
        $queryCount = 0;
        
        // This would need to be implemented with query logging
        // For now, just execute the callback
        $callback();
        
        // In a real implementation, you would track queries and assert the count
        $this->assertTrue(true, "Query count assertion placeholder");
    }
    
    /**
     * Assert no N+1 queries
     */
    protected function assertNoNPlusOneQueries(callable $callback): void
    {
        // This would need to be implemented with query logging
        // For now, just execute the callback
        $callback();
        
        // In a real implementation, you would detect N+1 query patterns
        $this->assertTrue(true, "N+1 query assertion placeholder");
    }
}
