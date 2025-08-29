<?php

namespace App\Lib;

use PDO;
use PDOException;
use Redis;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Database Connection Utility for Symfony
 * Provides connections to MySQL, PostgreSQL, and Redis (MongoDB removed)
 * Integrates with Symfony's Doctrine DBAL
 */
class DatabaseConnection
{
    private static $mysqlConnection = null;
    private static $postgresConnection = null;
    private static $redisConnection = null;
    private static $container = null;

    /**
     * Set Symfony container for dependency injection
     */
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Get MySQL PDO connection (Symfony style)
     */
    public static function getMysqlConnection(): PDO
    {
        if (self::$mysqlConnection === null) {
            try {
                // Try to use Symfony's configuration first
                if (self::$container && self::$container->hasParameter('database_host')) {
                    $host = self::$container->getParameter('database_host');
                    $dbname = self::$container->getParameter('database_name');
                    $username = self::$container->getParameter('database_user');
                    $password = self::$container->getParameter('database_password');
                    $port = self::$container->getParameter('database_port') ?? 3306;
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
     * Get PostgreSQL PDO connection (Symfony style)
     */
    public static function getPostgresConnection(): PDO
    {
        if (self::$postgresConnection === null) {
            try {
                // Try to use Symfony's configuration first
                if (self::$container && self::$container->hasParameter('postgres_host')) {
                    $host = self::$container->getParameter('postgres_host');
                    $dbname = self::$container->getParameter('postgres_name');
                    $username = self::$container->getParameter('postgres_user');
                    $password = self::$container->getParameter('postgres_password');
                    $port = self::$container->getParameter('postgres_port') ?? 5432;
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
     * Get Redis connection (Symfony style)
     */
    public static function getRedisConnection(): Redis
    {
        if (self::$redisConnection === null) {
            try {
                // Try to use Symfony's configuration first
                if (self::$container && self::$container->hasParameter('redis_host')) {
                    $host = self::$container->getParameter('redis_host');
                    $port = self::$container->getParameter('redis_port') ?? 6379;
                    $password = self::$container->getParameter('redis_password') ?? null;
                    $database = self::$container->getParameter('redis_database') ?? 0;
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
     * Get Symfony's Doctrine DBAL connection
     */
    public static function getDoctrineConnection(string $connectionName = 'default'): ?Connection
    {
        if (self::$container && self::$container->has('doctrine.dbal.connection_factory')) {
            try {
                $connectionFactory = self::$container->get('doctrine.dbal.connection_factory');
                return $connectionFactory->createConnection([], $connectionName);
            } catch (\Exception $e) {
                // Fallback to null if Doctrine is not available
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

        // Test MySQL via Doctrine (if available)
        try {
            $doctrine = self::getDoctrineConnection();
            if ($doctrine) {
                $doctrine->executeQuery("SELECT 1");
                $results['mysql_doctrine'] = 'Connected via Doctrine DBAL';
            } else {
                $results['mysql_doctrine'] = 'Doctrine DBAL not available';
            }
        } catch (\Exception $e) {
            $results['mysql_doctrine'] = 'Failed: ' . $e->getMessage();
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
     * Execute raw SQL query with Doctrine DBAL
     */
    public static function executeDoctrineQuery(string $query, array $params = []): array
    {
        try {
            $connection = self::getDoctrineConnection();
            if (!$connection) {
                throw new \Exception("Doctrine DBAL not available");
            }
            
            $result = $connection->executeQuery($query, $params);
            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new \Exception("Doctrine query execution failed: " . $e->getMessage());
        }
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
        
        // Test Doctrine performance (if available)
        try {
            $start = microtime(true);
            self::executeDoctrineQuery("SELECT 1");
            $duration = microtime(true) - $start;
            $results['doctrine_query_time'] = round($duration * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            $results['doctrine_query_time'] = 'Failed: ' . $e->getMessage();
        }
        
        return $results;
    }
}
