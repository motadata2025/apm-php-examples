<?php

namespace Shared\Utils;

use PDO;
use Exception;

/**
 * User Model for CRUD operations across multiple databases
 * Demonstrates multi-database operations similar to the Go example
 */
class UserModel
{
    private $mysqlConnection;
    private $postgresConnection;

    private $redisConnection;

    public function __construct()
    {
        $this->mysqlConnection = DatabaseConnection::getMysqlConnection();
        $this->postgresConnection = DatabaseConnection::getPostgresConnection();

        $this->redisConnection = DatabaseConnection::getRedisConnection();
    }

    /**
     * Create user in MySQL
     */
    public function createUserMySQL(string $email, string $name): int
    {
        $sql = "INSERT INTO users (email, name, created_at) VALUES (?, ?, NOW())";
        $stmt = $this->mysqlConnection->prepare($sql);
        $stmt->execute([$email, $name]);
        return $this->mysqlConnection->lastInsertId();
    }

    /**
     * Create user in PostgreSQL
     */
    public function createUserPostgreSQL(string $email, string $name): int
    {
        $sql = "INSERT INTO users (email, name, created_at) VALUES (?, ?, NOW()) RETURNING id";
        $stmt = $this->postgresConnection->prepare($sql);
        $stmt->execute([$email, $name]);
        $result = $stmt->fetch();
        return $result['id'];
    }



    /**
     * Get user by email from MySQL
     */
    public function getUserByEmailMySQL(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->mysqlConnection->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get user by email from PostgreSQL
     */
    public function getUserByEmailPostgreSQL(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->postgresConnection->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }



    /**
     * Update user name in MySQL
     */
    public function updateUserNameMySQL(int $id, string $name): bool
    {
        $sql = "UPDATE users SET name = ? WHERE id = ?";
        $stmt = $this->mysqlConnection->prepare($sql);
        return $stmt->execute([$name, $id]);
    }

    /**
     * Update user name in PostgreSQL
     */
    public function updateUserNamePostgreSQL(int $id, string $name): bool
    {
        $sql = "UPDATE users SET name = ? WHERE id = ?";
        $stmt = $this->postgresConnection->prepare($sql);
        return $stmt->execute([$name, $id]);
    }



    /**
     * Delete user from MySQL
     */
    public function deleteUserMySQL(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->mysqlConnection->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Delete user from PostgreSQL
     */
    public function deleteUserPostgreSQL(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->postgresConnection->prepare($sql);
        return $stmt->execute([$id]);
    }



    /**
     * Transaction example for MySQL - Transfer operation between users
     */
    public function transferOperationMySQL(int $fromUserId, int $toUserId): bool
    {
        try {
            $this->mysqlConnection->beginTransaction();

            // Simulate transfer logic - update both users
            $sql1 = "UPDATE users SET name = CONCAT(name, '_transferred_from') WHERE id = ?";
            $stmt1 = $this->mysqlConnection->prepare($sql1);
            $stmt1->execute([$fromUserId]);

            $sql2 = "UPDATE users SET name = CONCAT(name, '_transferred_to') WHERE id = ?";
            $stmt2 = $this->mysqlConnection->prepare($sql2);
            $stmt2->execute([$toUserId]);

            $this->mysqlConnection->commit();
            return true;
        } catch (Exception $e) {
            $this->mysqlConnection->rollBack();
            throw $e;
        }
    }

    /**
     * Transaction example for PostgreSQL - Swap suffix operation
     */
    public function swapSuffixPostgreSQL(int $userId1, int $userId2): bool
    {
        try {
            $this->postgresConnection->beginTransaction();

            // Simulate swap operation
            $sql1 = "UPDATE users SET name = name || '_swapped_1' WHERE id = ?";
            $stmt1 = $this->postgresConnection->prepare($sql1);
            $stmt1->execute([$userId1]);

            $sql2 = "UPDATE users SET name = name || '_swapped_2' WHERE id = ?";
            $stmt2 = $this->postgresConnection->prepare($sql2);
            $stmt2->execute([$userId2]);

            $this->postgresConnection->commit();
            return true;
        } catch (Exception $e) {
            $this->postgresConnection->rollBack();
            throw $e;
        }
    }

    /**
     * Demo method similar to the Go example
     * Demonstrates multi-database operations with random data
     */
    public function demo(): array
    {
        $results = [];

        // Generate random users
        $user1Email = DatabaseConnection::randomEmail("Alice");
        $user2Email = DatabaseConnection::randomEmail("Bob");

        $user4Email = DatabaseConnection::randomEmail("Dave");

        try {
            // Create users in different databases
            $mysqlId1 = $this->createUserMySQL($user1Email, "Alice");
            $postgresId1 = $this->createUserPostgreSQL($user2Email, "Bob");

            $results['created'] = [
                'mysql' => ['id' => $mysqlId1, 'email' => $user1Email],
                'postgres' => ['id' => $postgresId1, 'email' => $user2Email]
            ];

            // Read operations
            $mysqlUser = $this->getUserByEmailMySQL($user1Email);
            $postgresUser = $this->getUserByEmailPostgreSQL($user2Email);

            $results['read'] = [
                'mysql' => $mysqlUser,
                'postgres' => $postgresUser
            ];

            // Update operations
            $this->updateUserNameMySQL($mysqlId1, "AliceUpdated");
            $this->updateUserNamePostgreSQL($postgresId1, "BobUpdated");

            // Create additional users for transaction examples
            $mysqlId2 = $this->createUserMySQL($user4Email, "Dave");
            $postgresId2 = $this->createUserPostgreSQL(DatabaseConnection::randomEmail("Eve"), "Eve");

            // Transaction operations
            $this->transferOperationMySQL($mysqlId1, $mysqlId2);
            $this->swapSuffixPostgreSQL($postgresId1, $postgresId2);

            $results['transactions'] = [
                'mysql_transfer' => 'completed',
                'postgres_swap' => 'completed'
            ];

            $results['status'] = 'success';

        } catch (Exception $e) {
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get all users from a specific database
     */
    public function getAllUsers(string $database = 'mysql'): array
    {
        switch ($database) {
            case 'mysql':
                $stmt = $this->mysqlConnection->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
                return $stmt->fetchAll();

            case 'postgres':
                $stmt = $this->postgresConnection->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
                return $stmt->fetchAll();



            default:
                throw new Exception("Unsupported database: {$database}");
        }
    }
}