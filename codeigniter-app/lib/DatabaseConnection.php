<?php

namespace App\Lib;

use PDO;
use PDOException;
use Redis;
use Config\Database;

/**
 * Database Connection Utility for CodeIgniter
 * Provides connections to MySQL, PostgreSQL, and Redis (MongoDB removed)
 * Integrates with CodeIgniter's Database configuration
 */
class DatabaseConnection
{
    private static $mysqlConnection = null;
    private static $postgresConnection = null;
    private static $redisConnection = null;
    private static $ciDatabase = null;

    /**
     * Set CodeIgniter database instance
     */
    public static function setCIDatabase($database): void
    {
        self::$ciDatabase = $database;
    }

    /**
     * Get MySQL PDO connection (CodeIgniter style)
     */
    public static function getMysqlConnection(): PDO
    {
        if (self::$mysqlConnection === null) {
            try {
                // Try to use CodeIgniter's database configuration first
                $config = config('Database');
                if ($config && isset($config->default)) {
                    $dbConfig = $config->default;
                    
                    $host = $dbConfig['hostname'] ?? $_ENV['MYSQL_HOST'] ?? 'localhost';
                    $dbname = $dbConfig['database'] ?? $_ENV['MYSQL_DATABASE'] ?? 'apm_examples';
                    $username = $dbConfig['username'] ?? $_ENV['MYSQL_USERNAME'] ?? 'root';
                    $password = $dbConfig['password'] ?? $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';
                    $port = $dbConfig['port'] ?? $_ENV['MYSQL_PORT'] ?? 3306;
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
     * Get PostgreSQL PDO connection (CodeIgniter style)
     */
    public static function getPostgresConnection(): PDO
    {
        if (self::$postgresConnection === null) {
            try {
                // Try to use CodeIgniter's database configuration first
                $config = config('Database');
                if ($config && isset($config->postgres)) {
                    $dbConfig = $config->postgres;
                    
                    $host = $dbConfig['hostname'] ?? $_ENV['POSTGRES_HOST'] ?? 'localhost';
                    $dbname = $dbConfig['database'] ?? $_ENV['POSTGRES_DATABASE'] ?? 'apm_examples';
                    $username = $dbConfig['username'] ?? $_ENV['POSTGRES_USERNAME'] ?? 'postgres';
                    $password = $dbConfig['password'] ?? $_ENV['POSTGRES_PASSWORD'] ?? 'postgrespassword';
                    $port = $dbConfig['port'] ?? $_ENV['POSTGRES_PORT'] ?? 5432;
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
     * Get Redis connection (CodeIgniter style)
     */
    public static function getRedisConnection(): Redis
    {
        if (self::$redisConnection === null) {
            try {
                // Try to use CodeIgniter's cache configuration first
                $config = config('Cache');
                if ($config && isset($config->redis)) {
                    $redisConfig = $config->redis;
                    
                    $host = $redisConfig['host'] ?? $_ENV['REDIS_HOST'] ?? 'localhost';
                    $port = $redisConfig['port'] ?? $_ENV['REDIS_PORT'] ?? 6379;
                    $password = $redisConfig['password'] ?? $_ENV['REDIS_PASSWORD'] ?? null;
                    $database = $redisConfig['database'] ?? $_ENV['REDIS_DATABASE'] ?? 0;
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
     * Get CodeIgniter database instance
     */
    public static function getCIDatabase()
    {
        if (self::$ciDatabase === null) {
            try {
                // Try to get database instance from CodeIgniter
                if (function_exists('db_connect')) {
                    self::$ciDatabase = db_connect();
                } elseif (class_exists('\Config\Database')) {
                    $db = \Config\Database::connect();
                    self::$ciDatabase = $db;
                }
            } catch (\Exception $e) {
                // Fallback to null if CodeIgniter database is not available
                return null;
            }
        }
        
        return self::$ciDatabase;
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

        // Test MySQL via CodeIgniter (if available)
        try {
            $ciDb = self::getCIDatabase();
            if ($ciDb) {
                $ciDb->query("SELECT 1");
                $results['mysql_codeigniter'] = 'Connected via CodeIgniter';
            } else {
                $results['mysql_codeigniter'] = 'CodeIgniter database not available';
            }
        } catch (\Exception $e) {
            $results['mysql_codeigniter'] = 'Failed: ' . $e->getMessage();
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
     * Execute query with CodeIgniter database
     */
    public static function executeCIQuery(string $query, array $bindings = []): array
    {
        try {
            $db = self::getCIDatabase();
            if (!$db) {
                throw new \Exception("CodeIgniter database not available");
            }
            
            $result = $db->query($query, $bindings);
            return $result->getResultArray();
        } catch (\Exception $e) {
            throw new \Exception("CodeIgniter query execution failed: " . $e->getMessage());
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
        
        // Test CodeIgniter performance (if available)
        try {
            $start = microtime(true);
            self::executeCIQuery("SELECT 1");
            $duration = microtime(true) - $start;
            $results['codeigniter_query_time'] = round($duration * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            $results['codeigniter_query_time'] = 'Failed: ' . $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Demonstrate CodeIgniter-specific database operations
     */
    public static function demonstrateCIFeatures(): array
    {
        try {
            $results = [];
            
            // Test CodeIgniter database integration
            $ciDb = self::getCIDatabase();
            $results['codeigniter_db_available'] = $ciDb !== null;
            
            if ($ciDb) {
                // Test query builder
                $builder = $ciDb->table('users');
                $results['query_builder_available'] = true;
                
                // Test database utilities
                $results['database_utilities'] = [
                    'platform' => $ciDb->getPlatform(),
                    'version' => $ciDb->getVersion()
                ];
            }
            
            // Test Redis operations
            $redis = self::getRedisConnection();
            $redis->set('codeigniter_test', 'CodeIgniter Redis integration working');
            $redisTest = $redis->get('codeigniter_test');
            $results['redis_integration'] = $redisTest;
            
            return [
                'demonstration' => 'CodeIgniter Database Features',
                'features' => $results,
                'status' => 'completed'
            ];
            
        } catch (\Exception $e) {
            return [
                'demonstration' => 'CodeIgniter Database Features',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
