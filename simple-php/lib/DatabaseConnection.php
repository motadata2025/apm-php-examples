<?php

namespace SimplePhp\Lib;

use PDO;
use PDOException;
use Redis;

/**
 * Database Connection Utility
 * Provides connections to MySQL, PostgreSQL, and Redis (MongoDB removed)
 */
class DatabaseConnection
{
    private static $mysqlConnection = null;
    private static $postgresConnection = null;
    private static $redisConnection = null;

    /**
     * Get MySQL PDO connection
     */
    public static function getMysqlConnection(): PDO
    {
        if (self::$mysqlConnection === null) {
            $host = $_ENV['DB_HOST'] ?? $_ENV['MYSQL_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? $_ENV['MYSQL_PORT'] ?? '3306';
            $dbname = $_ENV['DB_DATABASE'] ?? $_ENV['MYSQL_DATABASE'] ?? 'apm_examples';
            $username = $_ENV['DB_USERNAME'] ?? $_ENV['MYSQL_USERNAME'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
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
     * Get PostgreSQL PDO connection
     */
    public static function getPostgresConnection(): PDO
    {
        if (self::$postgresConnection === null) {
            $host = $_ENV['POSTGRES_HOST'] ?? 'localhost';
            $port = $_ENV['POSTGRES_PORT'] ?? '5432';
            $dbname = $_ENV['POSTGRES_DATABASE'] ?? 'apm_examples';
            $username = $_ENV['POSTGRES_USERNAME'] ?? 'postgres';
            $password = $_ENV['POSTGRES_PASSWORD'] ?? 'postgrespassword';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

            try {
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
     * Get Redis connection
     */
    public static function getRedisConnection(): Redis
    {
        if (self::$redisConnection === null) {
            $host = $_ENV['REDIS_HOST'] ?? 'localhost';
            $port = $_ENV['REDIS_PORT'] ?? 6379;
            $password = $_ENV['REDIS_PASSWORD'] ?? null;

            try {
                self::$redisConnection = new Redis();
                self::$redisConnection->connect($host, $port);
                
                if ($password) {
                    self::$redisConnection->auth($password);
                }
            } catch (\Exception $e) {
                throw new \Exception("Redis connection failed: " . $e->getMessage());
            }
        }

        return self::$redisConnection;
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

        // Test MySQL
        try {
            $mysql = self::getMysqlConnection();
            $mysql->query("SELECT 1");
            $results['mysql'] = 'Connected';
        } catch (\Exception $e) {
            $results['mysql'] = 'Failed: ' . $e->getMessage();
        }

        // Test PostgreSQL
        try {
            $postgres = self::getPostgresConnection();
            $postgres->query("SELECT 1");
            $results['postgres'] = 'Connected';
        } catch (\Exception $e) {
            $results['postgres'] = 'Failed: ' . $e->getMessage();
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
}
