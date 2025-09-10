<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

/**
 * PostgreSQL Model for APM Application
 * 
 * Provides PostgreSQL database operations using PDO
 */
class PostgresModel
{
    private PDO $pdo;
    private string $host;
    private string $port;
    private string $database;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->host = $_ENV['DB_PGSQL_HOST'] ?? '127.0.0.1';
        $this->port = $_ENV['DB_PGSQL_PORT'] ?? '5436';
        $this->database = $_ENV['DB_PGSQL_DATABASE'] ?? 'codeigniter_app_db';
        $this->username = $_ENV['DB_PGSQL_USERNAME'] ?? 'postgres';
        $this->password = $_ENV['DB_PGSQL_PASSWORD'] ?? 'postgrespassword';
        
        $this->connect();
    }

    /**
     * Establish database connection
     */
    private function connect(): void
    {
        $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
        
        $this->pdo = new PDO($dsn, $this->username, $this->password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10
        ]);
    }

    /**
     * Test database connection
     */
    public function testConnection(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT 1 as test');
            $result = $stmt->fetch();
            
            return [
                'ok' => true,
                'message' => 'PostgreSQL connection successful',
                'host' => $this->host,
                'port' => $this->port,
                'database' => $this->database,
                'test_result' => $result
            ];
        } catch (PDOException $e) {
            return [
                'ok' => false,
                'message' => 'PostgreSQL connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create users table if not exists
     */
    public function createUsersTable(): bool
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            throw new PDOException('Failed to create users table: ' . $e->getMessage());
        }
    }

    /**
     * Insert a user
     */
    public function insertUser(string $name, string $email): int
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?) RETURNING id");
            $stmt->execute([$name, $email]);
            
            $result = $stmt->fetch();
            return (int)$result['id'];
        } catch (PDOException $e) {
            throw new PDOException('Failed to insert user: ' . $e->getMessage());
        }
    }

    /**
     * Get user count
     */
    public function getUserCount(): int
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            
            return (int)$result['count'];
        } catch (PDOException $e) {
            throw new PDOException('Failed to get user count: ' . $e->getMessage());
        }
    }

    /**
     * Get recent users
     */
    public function getRecentUsers(int $limit = 5): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new PDOException('Failed to get recent users: ' . $e->getMessage());
        }
    }

    /**
     * Perform CRUD operations with randomized data
     */
    public function performCrudOperations(): array
    {
        try {
            // Ensure table exists
            $this->createUsersTable();
            
            // Insert randomized data
            $insertedIds = [];
            $insertCount = rand(1, 3);
            
            for ($i = 0; $i < $insertCount; $i++) {
                $randomHex = bin2hex(random_bytes(4));
                $name = "ci_user_{$randomHex}";
                $email = "{$randomHex}@" . time() . ".example.test";
                
                $insertedIds[] = $this->insertUser($name, $email);
            }
            
            // Get total count
            $totalCount = $this->getUserCount();
            
            // Get recent users
            $recentUsers = $this->getRecentUsers(3);
            
            return [
                'ok' => true,
                'inserted_ids' => $insertedIds,
                'inserted_count' => $insertCount,
                'total_count' => $totalCount,
                'recent_users' => $recentUsers
            ];
            
        } catch (PDOException $e) {
            return [
                'ok' => false,
                'error' => 'PostgreSQL CRUD operations failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get PDO instance for advanced operations
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
