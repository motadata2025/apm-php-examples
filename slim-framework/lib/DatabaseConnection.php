<?php

namespace App\Lib;

use PDO;
use PDOException;
use Redis;
use Psr\Container\ContainerInterface;

/**
 * Database Connection Utility for Slim Framework
 * Provides connections to MySQL, PostgreSQL, and Redis (MongoDB removed)
 * Integrates with Slim's DI container
 */
class DatabaseConnection
{
    private static $mysqlConnection = null;
    private static $postgresConnection = null;
    private static $redisConnection = null;
    private static $container = null;

    /**
     * Set Slim container for dependency injection
     */
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Get MySQL PDO connection (Slim style)
     */
    public static function getMysqlConnection(): PDO
    {
        if (self::$mysqlConnection === null) {
            try {
                // Try to use Slim's container settings first
                if (self::$container && self::$container->has('settings')) {
                    $settings = self::$container->get('settings');
                    $dbSettings = $settings['database']['mysql'] ?? [];
                    
                    $host = $dbSettings['host'] ?? $_ENV['MYSQL_HOST'] ?? 'localhost';
                    $dbname = $dbSettings['database'] ?? $_ENV['MYSQL_DATABASE'] ?? 'apm_examples';
                    $username = $dbSettings['username'] ?? $_ENV['MYSQL_USERNAME'] ?? 'root';
                    $password = $dbSettings['password'] ?? $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';
                    $port = $dbSettings['port'] ?? $_ENV['MYSQL_PORT'] ?? 3306;
                } else {
                    // Fallback to environment variables
                    $host = $_ENV['MYSQL_HOST'] ?? 'localhost';
                    $dbname = $_ENV['MYSQL_DATABASE'] ?? 'apm_examples';
                    $username = $_ENV['MYSQL_USERNAME'] ?? 'root';
                    $password = $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';
                    $port = $_ENV['MYSQL_PORT'] ?? 3306;
                }

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

                self::$mysqlConnection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new \Exception("MySQL connection failed: " . $e->getMessage());
            }
        }

        return self::$mysqlConnection;
    }

    /**
     * Get PostgreSQL PDO connection (Slim style)
     */
    public static function getPostgresConnection(): PDO
    {
        if (self::$postgresConnection === null) {
            try {
                // Try to use Slim's container settings first
                if (self::$container && self::$container->has('settings')) {
                    $settings = self::$container->get('settings');
                    $dbSettings = $settings['database']['postgres'] ?? [];
                    
                    $host = $dbSettings['host'] ?? $_ENV['POSTGRES_HOST'] ?? 'localhost';
                    $dbname = $dbSettings['database'] ?? $_ENV['POSTGRES_DATABASE'] ?? 'apm_examples';
                    $username = $dbSettings['username'] ?? $_ENV['POSTGRES_USERNAME'] ?? 'postgres';
                    $password = $dbSettings['password'] ?? $_ENV['POSTGRES_PASSWORD'] ?? 'postgrespassword';
                    $port = $dbSettings['port'] ?? $_ENV['POSTGRES_PORT'] ?? 5432;
                } else {
                    // Fallback to environment variables
                    $host = $_ENV['POSTGRES_HOST'] ?? 'localhost';
                    $dbname = $_ENV['POSTGRES_DATABASE'] ?? 'apm_examples';
                    $username = $_ENV['POSTGRES_USERNAME'] ?? 'postgres';
                    $password = $_ENV['POSTGRES_PASSWORD'] ?? 'postgrespassword';
                    $port = $_ENV['POSTGRES_PORT'] ?? 5432;
                }

                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

                self::$postgresConnection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new \Exception("PostgreSQL connection failed: " . $e->getMessage());
            }
        }

        return self::$postgresConnection;
    }

    /**
     * Get Redis connection (Slim style)
     */
    public static function getRedisConnection(): Redis
    {
        if (self::$redisConnection === null) {
            try {
                // Try to use Slim's container settings first
                if (self::$container && self::$container->has('settings')) {
                    $settings = self::$container->get('settings');
                    $redisSettings = $settings['redis'] ?? [];
                    
                    $host = $redisSettings['host'] ?? $_ENV['REDIS_HOST'] ?? 'localhost';
                    $port = $redisSettings['port'] ?? $_ENV['REDIS_PORT'] ?? 6379;
                    $password = $redisSettings['password'] ?? $_ENV['REDIS_PASSWORD'] ?? null;
                    $database = $redisSettings['database'] ?? $_ENV['REDIS_DATABASE'] ?? 0;
                } else {
                    // Fallback to environment variables
                    $host = $_ENV['REDIS_HOST'] ?? 'localhost';
                    $port = $_ENV['REDIS_PORT'] ?? 6379;
                    $password = $_ENV['REDIS_PASSWORD'] ?? null;
                    $database = $_ENV['REDIS_DATABASE'] ?? 0;
                }

                self::$redisConnection = new Redis();
                self::$redisConnection->connect($host, $port);
                
                if ($password) {
                    self::$redisConnection->auth($password);
                }
                
                self::$redisConnection->select($database);
            } catch (\Exception $e) {
                throw new \Exception("Redis connection failed: " . $e->getMessage());
            }
        }

        return self::$redisConnection;
    }

    /**
     * Get connection from Slim container (if available)
     */
    public static function getSlimConnection(string $connectionName = 'db'): ?PDO
    {
        if (self::$container && self::$container->has($connectionName)) {
            try {
                return self::$container->get($connectionName);
            } catch (\Exception $e) {
                // Fallback to null if connection is not available
                return null;
            }
        }
        
        return null;
    }

    /**
     * Generate random email for testing
     */
    public static function randomEmail(string $name): string
    {
        return strtolower($name) . '_' . time() . '_' . rand(1000, 9999) . '@example.com';
    }

    /**
     * Test all database connections (MongoDB removed)
     */
    public static function testConnections(): array
    {
        $results = [];

        // Test MySQL via PDO
        try {
            $mysql = self::getMysqlConnection();
            $mysql->query("SELECT 1");
            $results['mysql_pdo'] = 'Connected via PDO';
        } catch (\Exception $e) {
            $results['mysql_pdo'] = 'Failed: ' . $e->getMessage();
        }

        // Test MySQL via Slim container (if available)
        try {
            $slimDb = self::getSlimConnection('db');
            if ($slimDb) {
                $slimDb->query("SELECT 1");
                $results['mysql_slim'] = 'Connected via Slim container';
            } else {
                $results['mysql_slim'] = 'Slim container not available';
            }
        } catch (\Exception $e) {
            $results['mysql_slim'] = 'Failed: ' . $e->getMessage();
        }

        // Test PostgreSQL via PDO
        try {
            $postgres = self::getPostgresConnection();
            $postgres->query("SELECT 1");
            $results['postgres_pdo'] = 'Connected via PDO';
        } catch (\Exception $e) {
            $results['postgres_pdo'] = 'Failed: ' . $e->getMessage();
        }

        // Test Redis
        try {
            $redis = self::getRedisConnection();
            $redis->ping();
            $results['redis'] = 'Connected';
        } catch (\Exception $e) {
            $results['redis'] = 'Failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Execute raw SQL query with PDO
     */
    public static function executeRawQuery(string $query, array $bindings = [], string $connection = 'mysql'): array
    {
        try {
            if ($connection === 'mysql') {
                $pdo = self::getMysqlConnection();
            } elseif ($connection === 'postgres') {
                $pdo = self::getPostgresConnection();
            } else {
                throw new \Exception("Unsupported connection type: $connection");
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($bindings);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            throw new \Exception("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Execute query with Slim container connection
     */
    public static function executeSlimQuery(string $query, array $bindings = []): array
    {
        try {
            $connection = self::getSlimConnection('db');
            if (!$connection) {
                throw new \Exception("Slim database connection not available");
            }
            
            $stmt = $connection->prepare($query);
            $stmt->execute($bindings);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            throw new \Exception("Slim query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Get database schema information
     */
    public static function getDatabaseInfo(string $connection = 'mysql'): array
    {
        try {
            $info = [];
            
            if ($connection === 'mysql') {
                $tables = self::executeRawQuery("SHOW TABLES", [], 'mysql');
                $info['tables'] = count($tables);
                $version = self::executeRawQuery("SELECT VERSION() as version", [], 'mysql');
                $info['version'] = $version[0]['version'] ?? 'Unknown';
            } elseif ($connection === 'postgres') {
                $tables = self::executeRawQuery("SELECT tablename FROM pg_tables WHERE schemaname = 'public'", [], 'postgres');
                $info['tables'] = count($tables);
                $version = self::executeRawQuery("SELECT version()", [], 'postgres');
                $info['version'] = $version[0]['version'] ?? 'Unknown';
            }
            
            return $info;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Test database performance
     */
    public static function testPerformance(): array
    {
        $results = [];
        
        // Test MySQL performance
        try {
            $start = microtime(true);
            self::executeRawQuery("SELECT 1", [], 'mysql');
            $duration = microtime(true) - $start;
            $results['mysql_query_time'] = round($duration * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            $results['mysql_query_time'] = 'Failed: ' . $e->getMessage();
        }
        
        // Test PostgreSQL performance
        try {
            $start = microtime(true);
            self::executeRawQuery("SELECT 1", [], 'postgres');
            $duration = microtime(true) - $start;
            $results['postgres_query_time'] = round($duration * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            $results['postgres_query_time'] = 'Failed: ' . $e->getMessage();
        }
        
        // Test Redis performance
        try {
            $start = microtime(true);
            $redis = self::getRedisConnection();
            $redis->ping();
            $duration = microtime(true) - $start;
            $results['redis_ping_time'] = round($duration * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            $results['redis_ping_time'] = 'Failed: ' . $e->getMessage();
        }
        
        // Test Slim container performance (if available)
        try {
            $start = microtime(true);
            self::executeSlimQuery("SELECT 1");
            $duration = microtime(true) - $start;
            $results['slim_query_time'] = round($duration * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            $results['slim_query_time'] = 'Failed: ' . $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Demonstrate Slim-specific database operations
     */
    public static function demonstrateSlimFeatures(): array
    {
        try {
            $results = [];
            
            // Test container integration
            $results['container_available'] = self::$container !== null;
            
            // Test Slim database connection
            $slimDb = self::getSlimConnection('db');
            $results['slim_db_available'] = $slimDb !== null;
            
            if ($slimDb) {
                $slimDb->query("SELECT 1");
                $results['slim_db_test'] = 'Connected';
            }
            
            // Test settings integration
            if (self::$container && self::$container->has('settings')) {
                $settings = self::$container->get('settings');
                $results['settings_available'] = true;
                $results['database_settings'] = isset($settings['database']);
            } else {
                $results['settings_available'] = false;
            }
            
            return [
                'demonstration' => 'Slim Framework Database Features',
                'features' => $results,
                'status' => 'completed'
            ];
            
        } catch (\Exception $e) {
            return [
                'demonstration' => 'Slim Framework Database Features',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
