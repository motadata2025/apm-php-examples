<?php

/**
 * APM Controller for CodeIgniter Application
 * Handles dashboard view and API endpoints for database, Redis, and external API operations
 */

class ApmController
{
    private array $config = [];

    public function __construct()
    {
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'APP_NAME' => $_ENV['APP_NAME'] ?? 'CodeIgniter App',
            'DB_MYSQL_HOST' => $_ENV['DB_MYSQL_HOST'] ?? '127.0.0.1',
            'DB_MYSQL_PORT' => $_ENV['DB_MYSQL_PORT'] ?? '3310',
            'DB_MYSQL_DATABASE' => $_ENV['DB_MYSQL_DATABASE'] ?? 'codeigniter_app_db',
            // Fix MySQL credentials to match docker-compose.yml
            'DB_MYSQL_USERNAME' => 'codeigniter-app_user',
            'DB_MYSQL_PASSWORD' => 'codeigniter-app_password',
            'DB_PGSQL_HOST' => $_ENV['DB_PGSQL_HOST'] ?? '127.0.0.1',
            'DB_PGSQL_PORT' => $_ENV['DB_PGSQL_PORT'] ?? '5436',
            'DB_PGSQL_DATABASE' => $_ENV['DB_PGSQL_DATABASE'] ?? 'codeigniter_app_db',
            'DB_PGSQL_USERNAME' => $_ENV['DB_PGSQL_USERNAME'] ?? 'postgres',
            'DB_PGSQL_PASSWORD' => $_ENV['DB_PGSQL_PASSWORD'] ?? 'postgrespassword',
            'REDIS_HOST' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'REDIS_PORT' => $_ENV['REDIS_PORT'] ?? '6383',
            'REDIS_PASSWORD' => $_ENV['REDIS_PASSWORD'] ?? '',
            'EXTERNAL_API_URL' => $_ENV['EXTERNAL_API_URL'] ?? 'https://httpbin.org/get',
            'HTTP_TIMEOUT' => $_ENV['HTTP_TIMEOUT'] ?? '20',
        ];
    }

    public function index(): void
    {
        require_once APPPATH . 'Views/apm_dashboard.php';
    }

    public function externalApi(): void
    {
        header('Content-Type: application/json');
        
        try {
            $url = $this->config['EXTERNAL_API_URL'];
            $timeout = (int)$this->config['HTTP_TIMEOUT'];

            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_USERAGENT => 'CodeIgniter-APM/1.0',
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($response === false || !empty($error)) {
                    throw new Exception("cURL error: $error");
                }

                $body = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $body = substr($response, 0, 200) . '...';
                }

                echo json_encode([
                    'ok' => true,
                    'status' => $httpCode,
                    'body' => $body
                ]);
            } else {
                throw new Exception('cURL not available');
            }
        } catch (Exception $e) {
            echo json_encode([
                'ok' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function dbConnectionCheck(): void
    {
        header('Content-Type: application/json');
        
        $result = [
            'mysql' => $this->testMysqlConnection(),
            'pg' => $this->testPostgresConnection()
        ];
        
        echo json_encode($result);
    }

    private function testMysqlConnection(): array
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config['DB_MYSQL_HOST'],
                $this->config['DB_MYSQL_PORT'],
                $this->config['DB_MYSQL_DATABASE']
            );

            $pdo = new PDO($dsn, $this->config['DB_MYSQL_USERNAME'], $this->config['DB_MYSQL_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
            ]);

            return ['ok' => true, 'message' => 'MySQL connection successful'];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => 'MySQL connection failed: ' . $e->getMessage()];
        }
    }

    private function testPostgresConnection(): array
    {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $this->config['DB_PGSQL_HOST'],
                $this->config['DB_PGSQL_PORT'],
                $this->config['DB_PGSQL_DATABASE']
            );

            $pdo = new PDO($dsn, $this->config['DB_PGSQL_USERNAME'], $this->config['DB_PGSQL_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
            ]);

            return ['ok' => true, 'message' => 'PostgreSQL connection successful'];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => 'PostgreSQL connection failed: ' . $e->getMessage()];
        }
    }

    public function dbCrud(): void
    {
        header('Content-Type: application/json');
        
        $result = [
            'mysql' => $this->performMysqlCrud(),
            'pg' => $this->performPostgresCrud()
        ];
        
        echo json_encode($result);
    }

    private function performMysqlCrud(): array
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config['DB_MYSQL_HOST'],
                $this->config['DB_MYSQL_PORT'],
                $this->config['DB_MYSQL_DATABASE']
            );

            $pdo = new PDO($dsn, $this->config['DB_MYSQL_USERNAME'], $this->config['DB_MYSQL_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Create table if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Insert randomized data
            $random = bin2hex(random_bytes(4));
            $name = "ci_user_{$random}";
            $email = "{$random}@" . time() . ".example.test";

            $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $stmt->execute([$name, $email]);
            $insertId = $pdo->lastInsertId();

            // Get count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            return [
                'ok' => true,
                'operation' => 'CRUD completed',
                'inserted_id' => $insertId,
                'total_count' => $count,
                'inserted_data' => ['name' => $name, 'email' => $email]
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function performPostgresCrud(): array
    {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $this->config['DB_PGSQL_HOST'],
                $this->config['DB_PGSQL_PORT'],
                $this->config['DB_PGSQL_DATABASE']
            );

            $pdo = new PDO($dsn, $this->config['DB_PGSQL_USERNAME'], $this->config['DB_PGSQL_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Create table if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Insert randomized data
            $random = bin2hex(random_bytes(4));
            $name = "ci_user_{$random}";
            $email = "{$random}@" . time() . ".example.test";

            $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?) RETURNING id");
            $stmt->execute([$name, $email]);
            $insertId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

            // Get count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            return [
                'ok' => true,
                'operation' => 'CRUD completed',
                'inserted_id' => $insertId,
                'total_count' => $count,
                'inserted_data' => ['name' => $name, 'email' => $email]
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function getRedisQueueName(): string
    {
        $appName = str_replace(' ', '_', strtolower($this->config['APP_NAME']));
        $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        return "{$appName}_{$phpVersion}";
    }

    private function getRedisClient()
    {
        if (extension_loaded('redis')) {
            $redis = new Redis();
            $redis->connect($this->config['REDIS_HOST'], (int)$this->config['REDIS_PORT'], 10);
            
            if (!empty($this->config['REDIS_PASSWORD'])) {
                $redis->auth($this->config['REDIS_PASSWORD']);
            }
            
            return $redis;
        }
        
        throw new Exception('redis-client-not-installed');
    }

    public function redisInsertBatch(): void
    {
        header('Content-Type: application/json');
        
        try {
            $redis = $this->getRedisClient();
            $queue = $this->getRedisQueueName();
            
            $messages = [];
            for ($i = 0; $i < 3; $i++) {
                $messages[] = 'batch_msg_' . bin2hex(random_bytes(4)) . '_' . time();
            }
            
            foreach ($messages as $message) {
                $redis->rPush($queue, $message);
            }
            
            $count = $redis->lLen($queue);
            
            echo json_encode([
                'ok' => true,
                'operation' => 'batch_insert',
                'inserted' => count($messages),
                'queue_length' => $count,
                'messages' => $messages
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function redisInsertOne(): void
    {
        header('Content-Type: application/json');
        
        try {
            $redis = $this->getRedisClient();
            $queue = $this->getRedisQueueName();
            
            $message = 'single_msg_' . bin2hex(random_bytes(4)) . '_' . time();
            $redis->rPush($queue, $message);
            
            $count = $redis->lLen($queue);
            
            echo json_encode([
                'ok' => true,
                'operation' => 'single_insert',
                'message' => $message,
                'queue_length' => $count
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function redisPop(): void
    {
        header('Content-Type: application/json');
        
        try {
            $redis = $this->getRedisClient();
            $queue = $this->getRedisQueueName();
            
            $message = $redis->lPop($queue);
            $count = $redis->lLen($queue);
            
            echo json_encode([
                'ok' => true,
                'operation' => 'pop',
                'message' => $message,
                'remaining_count' => $count
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function redisClear(): void
    {
        header('Content-Type: application/json');
        
        try {
            $redis = $this->getRedisClient();
            $queue = $this->getRedisQueueName();
            
            $redis->del($queue);
            
            echo json_encode([
                'ok' => true,
                'operation' => 'clear',
                'queue_length' => 0
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
}
