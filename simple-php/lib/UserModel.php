<?php

namespace SimplePhp\Lib;

use PDO;
use Exception;

/**
 * User Model for CRUD operations across multiple databases
 * Demonstrates multi-database operations (MongoDB removed)
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
     * Comprehensive CRUD Demo
     * For each connected database (MySQL, PostgreSQL):
     * 1. Add 2 users with randomized data
     * 2. Update 1 user
     * 3. Delete 1 user
     * 4. Query all remaining data
     * Returns only the updated records in the response
     */
    public function demo(): array
    {
        $results = [];
        $timestamp = time();

        try {
            // ===========================================
            // MYSQL CRUD OPERATIONS
            // ===========================================
            $results['mysql'] = $this->performCrudOperations('mysql', $timestamp);

            // ===========================================
            // POSTGRESQL CRUD OPERATIONS
            // ===========================================
            $results['postgresql'] = $this->performCrudOperations('postgresql', $timestamp);

            // ===========================================
            // SUMMARY
            // ===========================================
            $results['summary'] = [
                'total_databases' => 2,
                'operations_per_db' => 'CREATE(2) → UPDATE(1) → DELETE(1) → READ(all)',
                'timestamp' => date('Y-m-d H:i:s', $timestamp),
                'status' => 'success'
            ];

        } catch (Exception $e) {
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Perform comprehensive CRUD operations for a specific database
     */
    private function performCrudOperations(string $database, int $timestamp): array
    {
        $dbResults = [];

        // Generate randomized user data
        $users = $this->generateRandomUsers(2, $database, $timestamp);

        // STEP 1: CREATE - Add 2 users
        $createdUsers = [];
        foreach ($users as $user) {
            if ($database === 'mysql') {
                $userId = $this->createUserMySQL($user['email'], $user['name']);
                $createdUsers[] = ['id' => $userId, 'email' => $user['email'], 'name' => $user['name']];
            } else {
                $userId = $this->createUserPostgreSQL($user['email'], $user['name']);
                $createdUsers[] = ['id' => $userId, 'email' => $user['email'], 'name' => $user['name']];
            }
        }

        $dbResults['created'] = $createdUsers;

        // STEP 2: UPDATE - Modify 1 user (first one)
        $userToUpdate = $createdUsers[0];
        $newName = $userToUpdate['name'] . '_UPDATED_' . $timestamp;

        if ($database === 'mysql') {
            $this->updateUserNameMySQL($userToUpdate['id'], $newName);
            $updatedUser = $this->getUserByIdMySQL($userToUpdate['id']);
        } else {
            $this->updateUserNamePostgreSQL($userToUpdate['id'], $newName);
            $updatedUser = $this->getUserByIdPostgreSQL($userToUpdate['id']);
        }

        $dbResults['updated'] = $updatedUser;

        // STEP 3: DELETE - Remove 1 user (second one)
        $userToDelete = $createdUsers[1];

        if ($database === 'mysql') {
            $this->deleteUserMySQL($userToDelete['id']);
        } else {
            $this->deleteUserPostgreSQL($userToDelete['id']);
        }

        $dbResults['deleted'] = [
            'id' => $userToDelete['id'],
            'email' => $userToDelete['email'],
            'name' => $userToDelete['name']
        ];

        // STEP 4: READ - Query all data (last 10 records)
        $allUsers = $this->getAllUsers($database);
        $dbResults['all_users'] = [
            'count' => count($allUsers),
            'records' => array_slice($allUsers, 0, 5) // Show only first 5 for brevity
        ];

        return $dbResults;
    }

    /**
     * Generate random user data for testing
     */
    private function generateRandomUsers(int $count, string $database, int $timestamp): array
    {
        $firstNames = ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
        $domains = ['example.com', 'test.org', 'demo.net', 'sample.io'];

        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $domain = $domains[array_rand($domains)];

            $users[] = [
                'name' => $firstName . ' ' . $lastName,
                'email' => strtolower($firstName . '.' . $lastName . '.' . $database . '.' . $timestamp . '.' . $i . '@' . $domain)
            ];
        }

        return $users;
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
            case 'postgresql':
                $stmt = $this->postgresConnection->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
                return $stmt->fetchAll();

            default:
                throw new Exception("Unsupported database: {$database}. Supported: mysql, postgres");
        }
    }

    /**
     * Get user by ID from MySQL
     */
    public function getUserByIdMySQL(int $id): ?array
    {
        $stmt = $this->mysqlConnection->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Get user by ID from PostgreSQL
     */
    public function getUserByIdPostgreSQL(int $id): ?array
    {
        $stmt = $this->postgresConnection->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }



    /**
     * Cache user data in Redis
     */
    public function cacheUser(string $key, array $userData): bool
    {
        try {
            $this->redisConnection->setex($key, 3600, json_encode($userData));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get cached user data from Redis
     */
    public function getCachedUser(string $key): ?array
    {
        try {
            $data = $this->redisConnection->get($key);
            return $data ? json_decode($data, true) : null;
        } catch (Exception $e) {
            return null;
        }
    }
}
