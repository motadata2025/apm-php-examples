<?php

declare(strict_types=1);

namespace SimplePhp;

use PDO;
use PDOException;
use Exception;

class DB
{
    private Config $config;

    public function __construct()
    {
        $this->config = Config::getInstance();
    }

    public function testConnection(string $type): array
    {
        try {
            $pdo = $this->createConnection($type);
            $pdo->query('SELECT 1');
            return ['ok' => true, 'msg' => 'Connection successful'];
        } catch (Exception $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function insertRandomUser(string $type): array
    {
        try {
            $pdo = $this->createConnection($type);
            
            $name = 'User_' . bin2hex(random_bytes(6));
            $email = 'user_' . time() . '_' . bin2hex(random_bytes(6)) . '@example.test';
            
            $stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
            $stmt->execute([$name, $email]);
            
            $id = $pdo->lastInsertId();
            
            return [
                'ok' => true,
                'id' => $id,
                'name' => $name,
                'email' => $email
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function performCRUD(string $type): array
    {
        try {
            $pdo = $this->createConnection($type);
            $pdo->beginTransaction();
            
            $results = [];
            
            // CREATE
            $name = 'CRUDUser_' . bin2hex(random_bytes(6));
            $email = 'crud_' . time() . '_' . bin2hex(random_bytes(6)) . '@example.test';
            
            $stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
            $stmt->execute([$name, $email]);
            $userId = $pdo->lastInsertId();
            $results['create'] = ['rows_affected' => $stmt->rowCount(), 'id' => $userId];
            
            // READ
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $results['read'] = ['rows_found' => $stmt->rowCount(), 'user' => $user];
            
            // UPDATE
            $newName = 'Updated_' . $name;
            $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
            $stmt->execute([$newName, $userId]);
            $results['update'] = ['rows_affected' => $stmt->rowCount()];
            
            // DELETE
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $results['delete'] = ['rows_affected' => $stmt->rowCount()];
            
            $pdo->commit();
            
            return [
                'ok' => true,
                'operations' => $results,
                'summary' => 'CRUD operations completed successfully'
            ];
            
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function createConnection(string $type): PDO
    {
        if ($type === 'mysql') {
            $config = $this->config->getMysqlConfig();
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            );
        } elseif ($type === 'postgres') {
            $config = $this->config->getPostgresConfig();
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $config['host'],
                $config['port'],
                $config['database']
            );
        } else {
            throw new Exception("Unsupported database type: $type");
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ];

        return new PDO($dsn, $config['username'], $config['password'], $options);
    }
}
