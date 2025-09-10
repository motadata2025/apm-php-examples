<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use PDO;
use PDOException;
use Redis;
use Predis\Client as PredisClient;
use Exception;

/**
 * APM Controller for CodeIgniter Application
 * 
 * Provides endpoints for:
 * - Dashboard UI
 * - External API calls
 * - Database connectivity and CRUD operations
 * - Redis queue operations
 */
class ApmController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    private string $appName;
    private string $phpVersion;
    private string $queueName;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $this->appName = $_ENV['APP_NAME'] ?? 'codeigniter-app';
        $this->phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $this->queueName = $this->appName . '_' . $this->phpVersion;
    }

    /**
     * Dashboard index page
     */
    public function index(): string
    {
        $data = [
            'app_type' => 'CodeIgniter',
            'php_version' => PHP_VERSION,
            'web_server' => 'php_cli',
            'queue_name' => $this->queueName
        ];

        return view('apm_dashboard', $data);
    }

    /**
     * External API call endpoint
     */
    public function externalApi(): ResponseInterface
    {
        try {
            $apiUrl = $_ENV['EXTERNAL_API_URL'] ?? 'https://httpbin.org/get';
            $timeout = (int)($_ENV['HTTP_TIMEOUT'] ?? 10); // Reduced timeout for faster response

            // Try cURL first for better error handling
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_USERAGENT => 'CodeIgniter-APM/1.0',
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($response === false || !empty($error)) {
                    throw new Exception("cURL error: $error");
                }

                // Accept any HTTP response as long as we get data
                if (empty($response)) {
                    throw new Exception("Empty response from external API");
                }
            } else {
                // Fallback to file_get_contents
                $context = stream_context_create([
                    'http' => [
                        'timeout' => $timeout,
                        'method' => 'GET',
                        'header' => 'User-Agent: CodeIgniter-APM/1.0'
                    ]
                ]);

                $response = file_get_contents($apiUrl, false, $context);
            }
            
            if ($response === false) {
                throw new Exception('Failed to fetch external API');
            }

            // Try to decode as JSON, but don't fail if it's not JSON
            $decodedResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If not JSON, return the raw response (truncated for safety)
                $decodedResponse = [
                    'raw_response' => substr($response, 0, 500),
                    'content_type' => 'non-json',
                    'note' => 'External API returned non-JSON response'
                ];
            }

            return $this->response->setJSON([
                'ok' => true,
                'status' => 200,
                'body' => $decodedResponse,
                'url' => $apiUrl
            ]);

        } catch (Exception $e) {
            // Return 200 with error details for graceful error handling
            return $this->response->setJSON([
                'ok' => false,
                'error' => $e->getMessage(),
                'url' => $apiUrl ?? 'unknown'
            ]);
        }
    }

    /**
     * Database connection check endpoint
     */
    public function dbConnectionCheck(): ResponseInterface
    {
        $results = [
            'mysql' => $this->checkMysqlConnection(),
            'pg' => $this->checkPostgresConnection()
        ];

        return $this->response->setJSON($results);
    }

    /**
     * Database CRUD operations endpoint
     */
    public function dbCrud(): ResponseInterface
    {
        $results = [
            'mysql' => $this->performMysqlCrud(),
            'pg' => $this->performPostgresCrud()
        ];

        return $this->response->setJSON($results);
    }

    /**
     * Redis insert batch endpoint
     */
    public function redisInsertBatch(): ResponseInterface
    {
        try {
            $redis = $this->getRedisConnection();
            
            $messages = [];
            for ($i = 0; $i < 3; $i++) {
                $messages[] = 'batch_msg_' . bin2hex(random_bytes(4)) . '_' . time();
            }

            foreach ($messages as $message) {
                $redis->rPush($this->queueName, $message);
            }

            $count = $redis->lLen($this->queueName);

            return $this->response->setJSON([
                'ok' => true,
                'inserted' => count($messages),
                'messages' => $messages,
                'queue_length' => $count,
                'queue_name' => $this->queueName
            ]);

        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Redis insert one endpoint
     */
    public function redisInsertOne(): ResponseInterface
    {
        try {
            $redis = $this->getRedisConnection();
            
            $message = 'single_msg_' . bin2hex(random_bytes(4)) . '_' . time();
            $redis->rPush($this->queueName, $message);
            
            $count = $redis->lLen($this->queueName);

            return $this->response->setJSON([
                'ok' => true,
                'message' => $message,
                'queue_length' => $count,
                'queue_name' => $this->queueName
            ]);

        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Redis pop message endpoint
     */
    public function redisPop(): ResponseInterface
    {
        try {
            $redis = $this->getRedisConnection();
            
            $message = $redis->lPop($this->queueName);
            $count = $redis->lLen($this->queueName);

            return $this->response->setJSON([
                'ok' => true,
                'message' => $message,
                'queue_length' => $count,
                'queue_name' => $this->queueName
            ]);

        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Redis clear queue endpoint
     */
    public function redisClear(): ResponseInterface
    {
        try {
            $redis = $this->getRedisConnection();
            
            $redis->del($this->queueName);
            $count = $redis->lLen($this->queueName);

            return $this->response->setJSON([
                'ok' => true,
                'queue_length' => $count,
                'queue_name' => $this->queueName
            ]);

        } catch (Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check MySQL connection
     */
    private function checkMysqlConnection(): array
    {
        try {
            $host = $_ENV['DB_MYSQL_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_MYSQL_PORT'] ?? '3310';
            $database = $_ENV['DB_MYSQL_DATABASE'] ?? 'codeigniter_app_db';
            $username = $_ENV['DB_MYSQL_USERNAME'] ?? 'codeigniter_app_user';
            $password = $_ENV['DB_MYSQL_PASSWORD'] ?? 'codeigniter_app_password';

            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);

            return [
                'ok' => true,
                'message' => 'MySQL connection successful',
                'host' => $host,
                'port' => $port,
                'database' => $database
            ];

        } catch (PDOException $e) {
            return [
                'ok' => false,
                'message' => 'MySQL connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check PostgreSQL connection
     */
    private function checkPostgresConnection(): array
    {
        try {
            $host = $_ENV['DB_PGSQL_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PGSQL_PORT'] ?? '5436';
            $database = $_ENV['DB_PGSQL_DATABASE'] ?? 'codeigniter_app_db';
            $username = $_ENV['DB_PGSQL_USERNAME'] ?? 'postgres';
            $password = $_ENV['DB_PGSQL_PASSWORD'] ?? 'postgrespassword';

            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);

            return [
                'ok' => true,
                'message' => 'PostgreSQL connection successful',
                'host' => $host,
                'port' => $port,
                'database' => $database
            ];

        } catch (PDOException $e) {
            return [
                'ok' => false,
                'message' => 'PostgreSQL connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform MySQL CRUD operations
     */
    private function performMysqlCrud(): array
    {
        try {
            $host = $_ENV['DB_MYSQL_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_MYSQL_PORT'] ?? '3310';
            $database = $_ENV['DB_MYSQL_DATABASE'] ?? 'codeigniter_app_db';
            $username = $_ENV['DB_MYSQL_USERNAME'] ?? 'codeigniter_app_user';
            $password = $_ENV['DB_MYSQL_PASSWORD'] ?? 'codeigniter_app_password';

            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);

            // Create table if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Insert randomized data
            $insertedIds = [];
            $insertCount = rand(1, 3);

            for ($i = 0; $i < $insertCount; $i++) {
                $randomHex = bin2hex(random_bytes(4));
                $name = "ci_user_{$randomHex}";
                $email = "{$randomHex}@" . time() . ".example.test";

                $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
                $stmt->execute([$name, $email]);
                $insertedIds[] = $pdo->lastInsertId();
            }

            // Get count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            return [
                'ok' => true,
                'inserted_ids' => $insertedIds,
                'total_count' => (int)$count,
                'inserted_count' => $insertCount
            ];

        } catch (PDOException $e) {
            return [
                'ok' => false,
                'error' => 'MySQL CRUD failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform PostgreSQL CRUD operations
     */
    private function performPostgresCrud(): array
    {
        try {
            $host = $_ENV['DB_PGSQL_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PGSQL_PORT'] ?? '5436';
            $database = $_ENV['DB_PGSQL_DATABASE'] ?? 'codeigniter_app_db';
            $username = $_ENV['DB_PGSQL_USERNAME'] ?? 'postgres';
            $password = $_ENV['DB_PGSQL_PASSWORD'] ?? 'postgrespassword';

            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);

            // Create table if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Insert randomized data
            $insertedIds = [];
            $insertCount = rand(1, 3);

            for ($i = 0; $i < $insertCount; $i++) {
                $randomHex = bin2hex(random_bytes(4));
                $name = "ci_user_{$randomHex}";
                $email = "{$randomHex}@" . time() . ".example.test";

                $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?) RETURNING id");
                $stmt->execute([$name, $email]);
                $insertedIds[] = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            }

            // Get count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            return [
                'ok' => true,
                'inserted_ids' => $insertedIds,
                'total_count' => (int)$count,
                'inserted_count' => $insertCount
            ];

        } catch (PDOException $e) {
            return [
                'ok' => false,
                'error' => 'PostgreSQL CRUD failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get Redis connection (phpredis or Predis)
     */
    private function getRedisConnection()
    {
        $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
        $port = (int)($_ENV['REDIS_PORT'] ?? 6383);
        $password = $_ENV['REDIS_PASSWORD'] ?? '';

        // Try phpredis first
        if (extension_loaded('redis')) {
            $redis = new Redis();
            $redis->connect($host, $port, 3);
            if ($password) {
                $redis->auth($password);
            }
            return $redis;
        }

        // Fallback to Predis
        if (class_exists('Predis\Client')) {
            $config = [
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'timeout' => 3
            ];
            if ($password) {
                $config['password'] = $password;
            }
            return new PredisClient($config);
        }

        throw new Exception('No Redis client available. Install phpredis extension or predis/predis package.');
    }
}
