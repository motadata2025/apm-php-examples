<?php

namespace App\Lib;

use PDO;
use Exception;
use Doctrine\DBAL\Connection;

/**
 * User Model for CRUD operations across multiple databases (Symfony style)
 * Demonstrates multi-database operations with Doctrine DBAL integration (MongoDB removed)
 */
class UserModel
{
    private $mysqlConnection;
    private $postgresConnection;
    private $redisConnection;
    private $doctrineConnection;

    public function __construct()
    {
        $this->mysqlConnection = DatabaseConnection::getMysqlConnection();
        $this->postgresConnection = DatabaseConnection::getPostgresConnection();
        $this->redisConnection = DatabaseConnection::getRedisConnection();
        $this->doctrineConnection = DatabaseConnection::getDoctrineConnection();
    }

    /**
     * Create user in MySQL using Doctrine DBAL
     */
    public function createUserMySQLDoctrine(string $email, string $name): int
    {
        if (!$this->doctrineConnection) {
            throw new Exception("Doctrine DBAL not available");
        }

        $sql = "INSERT INTO users (email, name, created_at) VALUES (?, ?, NOW())";
        $this->doctrineConnection->executeStatement($sql, [$email, $name]);
        
        return $this->doctrineConnection->lastInsertId();
    }

    /**
     * Create user in MySQL using PDO
     */
    public function createUserMySQL(string $email, string $name): int
    {
        $sql = "INSERT INTO users (email, name, created_at) VALUES (?, ?, NOW())";
        $stmt = $this->mysqlConnection->prepare($sql);
        $stmt->execute([$email, $name]);
        return $this->mysqlConnection->lastInsertId();
    }

    /**
     * Create user in PostgreSQL using PDO
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
     * Get user by email from MySQL using Doctrine DBAL
     */
    public function getUserByEmailMySQLDoctrine(string $email): ?array
    {
        if (!$this->doctrineConnection) {
            return null;
        }

        $sql = "SELECT * FROM users WHERE email = ?";
        $result = $this->doctrineConnection->executeQuery($sql, [$email]);
        $user = $result->fetchAssociative();
        
        return $user ?: null;
    }

    /**
     * Get user by email from MySQL using PDO
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
     * Get user by email from PostgreSQL using PDO
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
     * Update user name in MySQL using Doctrine DBAL
     */
    public function updateUserNameMySQLDoctrine(int $id, string $name): bool
    {
        if (!$this->doctrineConnection) {
            return false;
        }

        $sql = "UPDATE users SET name = ? WHERE id = ?";
        $affected = $this->doctrineConnection->executeStatement($sql, [$name, $id]);
        
        return $affected > 0;
    }

    /**
     * Update user name in MySQL using PDO
     */
    public function updateUserNameMySQL(int $id, string $name): bool
    {
        $sql = "UPDATE users SET name = ? WHERE id = ?";
        $stmt = $this->mysqlConnection->prepare($sql);
        return $stmt->execute([$name, $id]);
    }

    /**
     * Update user name in PostgreSQL using PDO
     */
    public function updateUserNamePostgreSQL(int $id, string $name): bool
    {
        $sql = "UPDATE users SET name = ? WHERE id = ?";
        $stmt = $this->postgresConnection->prepare($sql);
        return $stmt->execute([$name, $id]);
    }

    /**
     * Delete user from MySQL using Doctrine DBAL
     */
    public function deleteUserMySQLDoctrine(int $id): bool
    {
        if (!$this->doctrineConnection) {
            return false;
        }

        $sql = "DELETE FROM users WHERE id = ?";
        $affected = $this->doctrineConnection->executeStatement($sql, [$id]);
        
        return $affected > 0;
    }

    /**
     * Delete user from MySQL using PDO
     */
    public function deleteUserMySQL(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->mysqlConnection->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Delete user from PostgreSQL using PDO
     */
    public function deleteUserPostgreSQL(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->postgresConnection->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Transaction example for MySQL using Doctrine DBAL
     */
    public function transferOperationMySQLDoctrine(int $fromUserId, int $toUserId): bool
    {
        if (!$this->doctrineConnection) {
            return false;
        }

        return $this->doctrineConnection->transactional(function() use ($fromUserId, $toUserId) {
            $sql1 = "UPDATE users SET name = CONCAT(name, '_transferred_from') WHERE id = ?";
            $this->doctrineConnection->executeStatement($sql1, [$fromUserId]);

            $sql2 = "UPDATE users SET name = CONCAT(name, '_transferred_to') WHERE id = ?";
            $this->doctrineConnection->executeStatement($sql2, [$toUserId]);

            return true;
        });
    }

    /**
     * Transaction example for MySQL using PDO
     */
    public function transferOperationMySQL(int $fromUserId, int $toUserId): bool
    {
        try {
            $this->mysqlConnection->beginTransaction();

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
     * Transaction example for PostgreSQL using PDO
     */
    public function swapSuffixPostgreSQL(int $userId1, int $userId2): bool
    {
        try {
            $this->postgresConnection->beginTransaction();

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
     * Demo method demonstrating Symfony Doctrine and PDO operations (MongoDB removed)
     */
    public function demo(): array
    {
        $results = [];

        try {
            // Generate random users
            $user1Email = DatabaseConnection::randomEmail("Alice");
            $user2Email = DatabaseConnection::randomEmail("Bob");
            $user3Email = DatabaseConnection::randomEmail("Charlie");
            $user4Email = DatabaseConnection::randomEmail("Dave");

            // Create users using different methods
            $mysqlId1 = $this->createUserMySQL($user1Email, "Alice");
            $postgresId1 = $this->createUserPostgreSQL($user2Email, "Bob");

            $results['created'] = [
                'mysql_pdo' => ['id' => $mysqlId1, 'email' => $user1Email],
                'postgres_pdo' => ['id' => $postgresId1, 'email' => $user2Email]
            ];

            // Try Doctrine operations if available
            if ($this->doctrineConnection) {
                $mysqlId2 = $this->createUserMySQLDoctrine($user3Email, "Charlie");
                $results['created']['mysql_doctrine'] = ['id' => $mysqlId2, 'email' => $user3Email];
                
                // Update using Doctrine
                $this->updateUserNameMySQLDoctrine($mysqlId2, "CharlieUpdatedDoctrine");
                
                // Transaction using Doctrine
                $this->transferOperationMySQLDoctrine($mysqlId1, $mysqlId2);
                
                $results['doctrine_operations'] = 'completed';
            } else {
                $results['doctrine_operations'] = 'not_available';
            }

            // Update operations using PDO
            $this->updateUserNameMySQL($mysqlId1, "AliceUpdatedPDO");
            $this->updateUserNamePostgreSQL($postgresId1, "BobUpdatedPDO");

            // Create additional user for PostgreSQL transaction
            $postgresId2 = $this->createUserPostgreSQL(DatabaseConnection::randomEmail("Eve"), "Eve");

            // Transaction operations
            $this->swapSuffixPostgreSQL($postgresId1, $postgresId2);

            $results['transactions'] = [
                'mysql_pdo_transfer' => 'completed',
                'postgres_swap' => 'completed'
            ];

            $results['status'] = 'success';
            $results['methods_used'] = $this->doctrineConnection ? 
                ['Doctrine DBAL', 'PDO MySQL', 'PDO PostgreSQL'] : 
                ['PDO MySQL', 'PDO PostgreSQL'];

        } catch (Exception $e) {
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get all users from a specific database using Doctrine DBAL (MongoDB removed)
     */
    public function getAllUsersDoctrine(string $database = 'mysql'): array
    {
        if (!$this->doctrineConnection) {
            throw new Exception("Doctrine DBAL not available");
        }

        switch ($database) {
            case 'mysql':
                $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT 10";
                $result = $this->doctrineConnection->executeQuery($sql);
                return $result->fetchAllAssociative();

            default:
                throw new Exception("Unsupported database for Doctrine: {$database}. Supported: mysql");
        }
    }

    /**
     * Get all users using PDO
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
                throw new Exception("Unsupported database: {$database}. Supported: mysql, postgres");
        }
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

    /**
     * Demonstrate Symfony-specific database operations
     */
    public function demonstrateSymfonyFeatures(): array
    {
        try {
            $results = [];
            
            // Test Doctrine DBAL features
            if ($this->doctrineConnection) {
                // Schema introspection
                $schemaManager = $this->doctrineConnection->createSchemaManager();
                $tables = $schemaManager->listTableNames();
                $results['doctrine_tables'] = $tables;
                
                // Query builder
                $queryBuilder = $this->doctrineConnection->createQueryBuilder();
                $users = $queryBuilder
                    ->select('*')
                    ->from('users')
                    ->setMaxResults(5)
                    ->executeQuery()
                    ->fetchAllAssociative();
                
                $results['doctrine_query_builder'] = count($users) . ' users found';
            }
            
            // Test Redis operations
            $this->redisConnection->set('symfony_test', 'Symfony Redis integration working');
            $redisTest = $this->redisConnection->get('symfony_test');
            $results['redis_integration'] = $redisTest;
            
            return [
                'demonstration' => 'Symfony Database Features',
                'features' => $results,
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'Symfony Database Features',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
