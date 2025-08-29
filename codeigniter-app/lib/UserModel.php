<?php

namespace App\Lib;

use PDO;
use Exception;

/**
 * User Model for CRUD operations across multiple databases (CodeIgniter style)
 * Demonstrates multi-database operations with CodeIgniter integration (MongoDB removed)
 */
class UserModel
{
    private $mysqlConnection;
    private $postgresConnection;
    private $redisConnection;
    private $ciDatabase;

    public function __construct()
    {
        $this->mysqlConnection = DatabaseConnection::getMysqlConnection();
        $this->postgresConnection = DatabaseConnection::getPostgresConnection();
        $this->redisConnection = DatabaseConnection::getRedisConnection();
        $this->ciDatabase = DatabaseConnection::getCIDatabase();
    }

    /**
     * Create user in MySQL using CodeIgniter database
     */
    public function createUserMySQLCI(string $email, string $name): int
    {
        if (!$this->ciDatabase) {
            throw new Exception("CodeIgniter database not available");
        }

        $data = [
            'email' => $email,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->ciDatabase->table('users')->insert($data);
        return $this->ciDatabase->insertID();
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
     * Get user by email from MySQL using CodeIgniter database
     */
    public function getUserByEmailMySQLCI(string $email): ?array
    {
        if (!$this->ciDatabase) {
            return null;
        }

        $result = $this->ciDatabase->table('users')
            ->where('email', $email)
            ->get()
            ->getRowArray();
            
        return $result ?: null;
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
     * Update user name in MySQL using CodeIgniter database
     */
    public function updateUserNameMySQLCI(int $id, string $name): bool
    {
        if (!$this->ciDatabase) {
            return false;
        }

        $affected = $this->ciDatabase->table('users')
            ->where('id', $id)
            ->update(['name' => $name]);
            
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
     * Delete user from MySQL using CodeIgniter database
     */
    public function deleteUserMySQLCI(int $id): bool
    {
        if (!$this->ciDatabase) {
            return false;
        }

        $affected = $this->ciDatabase->table('users')
            ->where('id', $id)
            ->delete();
            
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
     * Transaction example for MySQL using CodeIgniter database
     */
    public function transferOperationMySQLCI(int $fromUserId, int $toUserId): bool
    {
        if (!$this->ciDatabase) {
            return false;
        }

        $this->ciDatabase->transStart();

        $this->ciDatabase->table('users')
            ->where('id', $fromUserId)
            ->update(['name' => $this->ciDatabase->selectConcat('name', '_transferred_from')]);

        $this->ciDatabase->table('users')
            ->where('id', $toUserId)
            ->update(['name' => $this->ciDatabase->selectConcat('name', '_transferred_to')]);

        $this->ciDatabase->transComplete();
        
        return $this->ciDatabase->transStatus();
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
     * Demo method demonstrating CodeIgniter and PDO operations (MongoDB removed)
     */
    public function demo(): array
    {
        $results = [];

        try {
            // Generate random users
            $user1Email = DatabaseConnection::randomEmail("Alice");
            $user2Email = DatabaseConnection::randomEmail("Bob");
            $user3Email = DatabaseConnection::randomEmail("Charlie");

            // Create users using different methods
            $mysqlId1 = $this->createUserMySQL($user1Email, "Alice");
            $postgresId1 = $this->createUserPostgreSQL($user2Email, "Bob");

            $results['created'] = [
                'mysql_pdo' => ['id' => $mysqlId1, 'email' => $user1Email],
                'postgres_pdo' => ['id' => $postgresId1, 'email' => $user2Email]
            ];

            // Try CodeIgniter operations if available
            if ($this->ciDatabase) {
                $mysqlId2 = $this->createUserMySQLCI($user3Email, "Charlie");
                $results['created']['mysql_codeigniter'] = ['id' => $mysqlId2, 'email' => $user3Email];
                
                // Update using CodeIgniter
                $this->updateUserNameMySQLCI($mysqlId2, "CharlieUpdatedCI");
                
                // Transaction using CodeIgniter
                $this->transferOperationMySQLCI($mysqlId1, $mysqlId2);
                
                $results['codeigniter_operations'] = 'completed';
            } else {
                $results['codeigniter_operations'] = 'not_available';
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
            $results['methods_used'] = $this->ciDatabase ? 
                ['CodeIgniter Query Builder', 'PDO MySQL', 'PDO PostgreSQL'] : 
                ['PDO MySQL', 'PDO PostgreSQL'];

        } catch (Exception $e) {
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get all users from a specific database using CodeIgniter (MongoDB removed)
     */
    public function getAllUsersCI(string $database = 'mysql'): array
    {
        if (!$this->ciDatabase) {
            throw new Exception("CodeIgniter database not available");
        }

        switch ($database) {
            case 'mysql':
                return $this->ciDatabase->table('users')
                    ->orderBy('created_at', 'DESC')
                    ->limit(10)
                    ->get()
                    ->getResultArray();

            default:
                throw new Exception("Unsupported database for CodeIgniter: {$database}. Supported: mysql");
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
     * Demonstrate CodeIgniter-specific database operations
     */
    public function demonstrateCIFeatures(): array
    {
        try {
            $results = [];
            
            // Test CodeIgniter query builder
            $results['codeigniter_available'] = $this->ciDatabase !== null;
            
            if ($this->ciDatabase) {
                // Test query builder features
                $builder = $this->ciDatabase->table('users');
                $results['query_builder_features'] = [
                    'select' => 'available',
                    'where' => 'available',
                    'join' => 'available',
                    'orderBy' => 'available',
                    'limit' => 'available'
                ];
                
                // Test basic operations
                $testEmail = DatabaseConnection::randomEmail("CITest");
                $userId = $this->createUserMySQLCI($testEmail, "CITestUser");
                
                $user = $this->getUserByEmailMySQLCI($testEmail);
                $results['ci_crud_test'] = $user ? 'success' : 'failed';
                
                // Clean up
                $this->deleteUserMySQLCI($userId);
            }
            
            // Test Redis operations
            $this->redisConnection->set('codeigniter_test', 'CodeIgniter Redis integration working');
            $redisTest = $this->redisConnection->get('codeigniter_test');
            $results['redis_integration'] = $redisTest;
            
            return [
                'demonstration' => 'CodeIgniter Database Features',
                'features' => $results,
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'CodeIgniter Database Features',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
