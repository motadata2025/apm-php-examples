<?php

namespace App\Lib;

use PDO;
use PDOException;
use Redis;
use Illuminate\Support\Facades\DB;

/**
 * Database Connection Utility for Laravel
 * Provides connections to MySQL, PostgreSQL, and Redis (MongoDB removed)
 */
class DatabaseConnection
{
    private static $mysqlConnection = null;
    private static $postgresConnection = null;
    private static $redisConnection = null;

    /**
     * Get MySQL PDO connection (Laravel style)
     */
    public static function getMysqlConnection(): PDO
    {
        if (self::$mysqlConnection === null) {
            try {
                // Use Laravel's database configuration
                $config = config('database.connections.mysql');
                
                $host = $config['host'] ?? 'localhost';
                $dbname = $config['database'] ?? 'apm_examples';
                $username = $config['username'] ?? 'root';
                $password = $config['password'] ?? 'rootpassword';
                $port = $config['port'] ?? 3306;

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
     * Get PostgreSQL PDO connection (Laravel style)
     */
    public static function getPostgresConnection(): PDO
    {
        if (self::$postgresConnection === null) {
            try {
                // Use Laravel's database configuration
                $config = config('database.connections.pgsql');
                
                $host = $config['host'] ?? 'localhost';
                $dbname = $config['database'] ?? 'apm_examples';
                $username = $config['username'] ?? 'postgres';
                $password = $config['password'] ?? 'postgrespassword';
                $port = $config['port'] ?? 5432;

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
     * Get Redis connection (Laravel style)
     */
    public static function getRedisConnection(): Redis
    {
        if (self::$redisConnection === null) {
            try {
                // Use Laravel's Redis configuration
                $config = config('database.redis.default');
                
                $host = $config['host'] ?? 'localhost';
                $port = $config['port'] ?? 6379;
                $password = $config['password'] ?? null;
                $database = $config['database'] ?? 0;

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
     * Get Laravel's Eloquent database connection
     */
    public static function getLaravelConnection(string $connection = 'mysql')
    {
        return DB::connection($connection);
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

        // Test MySQL via Laravel
        try {
            DB::connection('mysql')->getPdo();
            $results['mysql_laravel'] = 'Connected via Laravel';
        } catch (\Exception $e) {
            $results['mysql_laravel'] = 'Failed: ' . $e->getMessage();
        }

        // Test MySQL via PDO
        try {
            $mysql = self::getMysqlConnection();
            $mysql->query("SELECT 1");
            $results['mysql_pdo'] = 'Connected via PDO';
        } catch (\Exception $e) {
            $results['mysql_pdo'] = 'Failed: ' . $e->getMessage();
        }

        // Test PostgreSQL via Laravel (if configured)
        try {
            if (config('database.connections.pgsql')) {
                DB::connection('pgsql')->getPdo();
                $results['postgres_laravel'] = 'Connected via Laravel';
            } else {
                $results['postgres_laravel'] = 'Not configured';
            }
        } catch (\Exception $e) {
            $results['postgres_laravel'] = 'Failed: ' . $e->getMessage();
        }

        // Test PostgreSQL via PDO
        try {
            $postgres = self::getPostgresConnection();
            $postgres->query("SELECT 1");
            $results['postgres_pdo'] = 'Connected via PDO';
        } catch (\Exception $e) {
            $results['postgres_pdo'] = 'Failed: ' . $e->getMessage();
        }

        // Test Redis via Laravel
        try {
            \Illuminate\Support\Facades\Redis::ping();
            $results['redis_laravel'] = 'Connected via Laravel';
        } catch (\Exception $e) {
            $results['redis_laravel'] = 'Failed: ' . $e->getMessage();
        }

        // Test Redis via direct connection
        try {
            $redis = self::getRedisConnection();
            $redis->ping();
            $results['redis_direct'] = 'Connected directly';
        } catch (\Exception $e) {
            $results['redis_direct'] = 'Failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Execute raw SQL query with Laravel connection
     */
    public static function executeRawQuery(string $query, array $bindings = [], string $connection = 'mysql'): array
    {
        try {
            return DB::connection($connection)->select($query, $bindings);
        } catch (\Exception $e) {
            throw new \Exception("Query execution failed: " . $e->getMessage());
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
                $tables = DB::connection('mysql')->select("SHOW TABLES");
                $info['tables'] = count($tables);
                $info['version'] = DB::connection('mysql')->select("SELECT VERSION() as version")[0]->version;
            } elseif ($connection === 'pgsql') {
                $tables = DB::connection('pgsql')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                $info['tables'] = count($tables);
                $info['version'] = DB::connection('pgsql')->select("SELECT version()")[0]->version;
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
            DB::connection('mysql')->select("SELECT 1");
            $duration = microtime(true) - $start;
            $results['mysql_query_time'] = round($duration * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            $results['mysql_query_time'] = 'Failed: ' . $e->getMessage();
        }
        
        // Test Redis performance
        try {
            $start = microtime(true);
            \Illuminate\Support\Facades\Redis::ping();
            $duration = microtime(true) - $start;
            $results['redis_ping_time'] = round($duration * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            $results['redis_ping_time'] = 'Failed: ' . $e->getMessage();
        }
        
        return $results;
    }
}
