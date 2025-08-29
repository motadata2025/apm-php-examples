<?php

namespace App\Lib;

use PDO;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * User Model for CRUD operations across multiple databases (Laravel style)
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
     * Create user in MySQL using Laravel Eloquent
     */
    public function createUserMySQLEloquent(string $email, string $name): int
    {
        $userId = DB::connection('mysql')->table('users')->insertGetId([
            'email' => $email,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return $userId;
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
     * Create user in PostgreSQL using Laravel Eloquent
     */
    public function createUserPostgreSQLEloquent(string $email, string $name): int
    {
        $userId = DB::connection('pgsql')->table('users')->insertGetId([
            'email' => $email,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return $userId;
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
     * Get user by email from MySQL using Laravel Eloquent
     */
    public function getUserByEmailMySQLEloquent(string $email): ?array
    {
        $user = DB::connection('mysql')->table('users')->where('email', $email)->first();
        return $user ? (array) $user : null;
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
     * Get user by email from PostgreSQL using Laravel Eloquent
     */
    public function getUserByEmailPostgreSQLEloquent(string $email): ?array
    {
        $user = DB::connection('pgsql')->table('users')->where('email', $email)->first();
        return $user ? (array) $user : null;
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
     * Update user name in MySQL using Laravel Eloquent
     */
    public function updateUserNameMySQLEloquent(int $id, string $name): bool
    {
        $affected = DB::connection('mysql')->table('users')
            ->where('id', $id)
            ->update(['name' => $name, 'updated_at' => now()]);
        
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
     * Update user name in PostgreSQL using Laravel Eloquent
     */
    public function updateUserNamePostgreSQLEloquent(int $id, string $name): bool
    {
        $affected = DB::connection('pgsql')->table('users')
            ->where('id', $id)
            ->update(['name' => $name, 'updated_at' => now()]);
        
        return $affected > 0;
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
     * Delete user from MySQL using Laravel Eloquent
     */
    public function deleteUserMySQLEloquent(int $id): bool
    {
        $affected = DB::connection('mysql')->table('users')->where('id', $id)->delete();
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
     * Delete user from PostgreSQL using Laravel Eloquent
     */
    public function deleteUserPostgreSQLEloquent(int $id): bool
    {
        $affected = DB::connection('pgsql')->table('users')->where('id', $id)->delete();
        return $affected > 0;
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
     * Transaction example for MySQL using Laravel
     */
    public function transferOperationMySQLEloquent(int $fromUserId, int $toUserId): bool
    {
        return DB::connection('mysql')->transaction(function () use ($fromUserId, $toUserId) {
            DB::connection('mysql')->table('users')
                ->where('id', $fromUserId)
                ->update(['name' => DB::raw("CONCAT(name, '_transferred_from')")]);
            
            DB::connection('mysql')->table('users')
                ->where('id', $toUserId)
                ->update(['name' => DB::raw("CONCAT(name, '_transferred_to')")]);
            
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
     * Demo method demonstrating Laravel and PDO operations (MongoDB removed)
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

            // Create users using Laravel Eloquent
            $mysqlId1 = $this->createUserMySQLEloquent($user1Email, "Alice");
            $postgresId1 = $this->createUserPostgreSQLEloquent($user2Email, "Bob");

            // Create users using PDO
            $mysqlId2 = $this->createUserMySQL($user3Email, "Charlie");
            $postgresId2 = $this->createUserPostgreSQL($user4Email, "Dave");

            $results['created'] = [
                'mysql_eloquent' => ['id' => $mysqlId1, 'email' => $user1Email],
                'postgres_eloquent' => ['id' => $postgresId1, 'email' => $user2Email],
                'mysql_pdo' => ['id' => $mysqlId2, 'email' => $user3Email],
                'postgres_pdo' => ['id' => $postgresId2, 'email' => $user4Email]
            ];

            // Update operations using both methods
            $this->updateUserNameMySQLEloquent($mysqlId1, "AliceUpdatedEloquent");
            $this->updateUserNameMySQL($mysqlId2, "CharlieUpdatedPDO");

            // Transaction operations
            $this->transferOperationMySQLEloquent($mysqlId1, $mysqlId2);

            $results['transactions'] = [
                'mysql_eloquent_transfer' => 'completed',
                'operations' => 'mixed_eloquent_and_pdo'
            ];

            // Cache operations using Laravel Redis
            $this->cacheUserLaravel("user_demo_" . $mysqlId1, [
                'id' => $mysqlId1,
                'email' => $user1Email,
                'name' => 'AliceUpdatedEloquent_transferred_from'
            ]);

            $results['status'] = 'success';
            $results['methods_used'] = ['Laravel Eloquent', 'PDO', 'Laravel Redis'];

        } catch (Exception $e) {
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get all users from a specific database using Laravel Eloquent (MongoDB removed)
     */
    public function getAllUsersEloquent(string $database = 'mysql'): array
    {
        switch ($database) {
            case 'mysql':
                return DB::connection('mysql')->table('users')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

            case 'postgres':
                return DB::connection('pgsql')->table('users')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

            default:
                throw new Exception("Unsupported database: {$database}. Supported: mysql, postgres");
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
     * Cache user data using Laravel Redis
     */
    public function cacheUserLaravel(string $key, array $userData): bool
    {
        try {
            Redis::setex($key, 3600, json_encode($userData));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get cached user data using Laravel Redis
     */
    public function getCachedUserLaravel(string $key): ?array
    {
        try {
            $data = Redis::get($key);
            return $data ? json_decode($data, true) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Cache user data using direct Redis connection
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
     * Get cached user data using direct Redis connection
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
