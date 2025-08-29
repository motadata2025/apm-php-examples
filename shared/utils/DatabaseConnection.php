<?php

namespace Shared\Utils;

use PDO;
use PDOException;

use Redis;

/**
 * Database Connection Utility
 * Provides connections to MySQL, PostgreSQL, and Redis
 */
class DatabaseConnection
{
    private static $mysqlConnection = null;
    private static $postgresConnection = null;

    private static ?\Redis $redisConnection = null;

    /**
     * Get network IP for Docker services
     */
    private static function getNetworkIP(): string
    {
        // Try to get the actual network IP
        $ip = shell_exec("ip route get 1.1.1.1 | grep -oP 'src \\K\\S+' 2>/dev/null");
        if ($ip) {
            return trim($ip);
        }

        // Fallback to localhost
        return '127.0.0.1';
    }

    /**
     * Get MySQL PDO connection
     */
    public static function getMysqlConnection(): PDO
    {
        if (self::$mysqlConnection === null) {
            // Try to get network IP for Docker services
            $network_ip = self::getNetworkIP();
            $host = $_ENV['MYSQL_HOST'] ?? $network_ip;
            $dbname = $_ENV['MYSQL_DATABASE'] ?? 'apm_examples';
            $username = $_ENV['MYSQL_USERNAME'] ?? 'root';
            $password = $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';

            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

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
            // Try to get network IP for Docker services
            $network_ip = self::getNetworkIP();
            $host = $_ENV['POSTGRES_HOST'] ?? $network_ip;
            $dbname = $_ENV['POSTGRES_DATABASE'] ?? 'apm_examples';
            $username = $_ENV['POSTGRES_USERNAME'] ?? 'postgres';
            $password = $_ENV['POSTGRES_PASSWORD'] ?? 'postgrespassword';

            $dsn = "pgsql:host={$host};dbname={$dbname}";

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
    public static function getRedisConnection(): \Redis
    {
        if (self::$redisConnection === null) {
            // Try to get network IP for Docker services
            $network_ip = self::getNetworkIP();
            $host = $_ENV['REDIS_HOST'] ?? $network_ip;
            $port = $_ENV['REDIS_PORT'] ?? 6379;

            try {
                self::$redisConnection = new \Redis();
                self::$redisConnection->connect($host, $port);

                // Authenticate if password is provided
                $password = $_ENV['REDIS_PASSWORD'] ?? 'redispassword';
                if (!empty($password)) {
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
     * Test all database connections
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