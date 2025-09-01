<?php

namespace App\Controllers;

use GuzzleHttp\Client;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Exception;
use PDO;
use Redis;

class ApmController
{
    private Twig $view;
    private Logger $logger;
    private Client $httpClient;

    public function __construct(Twig $view, Logger $logger)
    {
        $this->view = $view;
        $this->logger = $logger;
        $this->httpClient = new Client(['timeout' => 30]);
    }

    /**
     * Display the main APM dashboard (comprehensive like Simple PHP)
     */
    public function dashboard(Request $request, Response $response): Response
    {
        $phpVersion = phpversion();
        $framework = 'Slim Framework 4.x';
        $deploymentType = $this->detectWebServerType();

        // Override with environment variable if set (for testing/debugging)
        if (isset($_ENV['DEPLOYMENT_DESC'])) {
            $deploymentType = $_ENV['DEPLOYMENT_DESC'];
        }

        // Generate random data for queue operations
        $randomData = $this->generateRandomData();

        return $this->view->render($response, 'dashboard.html.twig', [
            'phpVersion' => $phpVersion,
            'framework' => $framework,
            'deploymentType' => $deploymentType,
            'randomData' => $randomData,
        ]);
    }

    /**
     * Handle AJAX requests (like Simple PHP)
     */
    public function handleAjax(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $action = $parsedBody['action'] ?? '';

        switch ($action) {
            case 'test_databases':
                return $this->testDatabases($request, $response);
            case 'create_tables':
                return $this->createTables($request, $response);
            case 'demo_crud':
                return $this->demoCrud($request, $response);
            case 'fetch_api_data':
                return $this->fetchApiData($request, $response);
            case 'test_queue':
                return $this->testQueue($request, $response);
            case 'add_queue_data':
                return $this->addQueueData($request, $response);
            case 'read_queue_data':
                return $this->readQueueData($request, $response);
            case 'clear_queue':
                return $this->clearQueue($request, $response);
            case 'generate_random_data':
                return $this->generateNewRandomData($request, $response);
            case 'debug_env':
                return $this->debugEnv($request, $response);
            default:
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Unknown action']));
                return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Comprehensive health check endpoint (like Simple PHP)
     */
    public function healthCheck(Request $request, Response $response): Response
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => (new \DateTime())->format('c'),
                'php_version' => phpversion(),
                'slim_version' => '4.x',
                'memory_usage' => memory_get_usage(true),
                'uptime' => $this->getUptime(),
                'services' => $this->checkServices()
            ];

            // Determine overall status
            foreach ($health['services'] as $service => $status) {
                if ($status !== 'healthy') {
                    $health['status'] = 'unhealthy';
                    break;
                }
            }

            $response->getBody()->write(json_encode($health, JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'unhealthy',
                'timestamp' => (new \DateTime())->format('c'),
                'error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get system uptime
     */
    private function getUptime(): ?int
    {
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = floatval(explode(' ', $uptime)[0]);
            return round($uptime);
        }
        return null;
    }

    /**
     * Check service health (Slim Framework ports)
     */
    private function checkServices(): array
    {
        $services = [];

        // Check Redis (Slim port: 6382)
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6382);
            $redis->ping();
            $redis->close();
            $services['redis'] = 'healthy';
        } catch (Exception $e) {
            $services['redis'] = 'unhealthy';
        }

        // Check MySQL (Slim port: 3309)
        try {
            $pdo = new \PDO('mysql:host=127.0.0.1;port=3309;dbname=slim_framework_db', 'root', 'rootpassword');
            $pdo->query('SELECT 1');
            $services['mysql'] = 'healthy';
        } catch (Exception $e) {
            $services['mysql'] = 'unhealthy';
        }

        // Check PostgreSQL (Slim port: 5435)
        try {
            $pdo = new \PDO('pgsql:host=127.0.0.1;port=5435;dbname=slim_framework_db', 'postgres', 'postgrespassword');
            $pdo->query('SELECT 1');
            $services['postgres'] = 'healthy';
        } catch (Exception $e) {
            $services['postgres'] = 'unhealthy';
        }

        return $services;
    }

    /**
     * Test database connections (comprehensive like Simple PHP)
     */
    public function testDatabases(Request $request, Response $response): Response
    {
        try {
            $results = [];

            // Test MySQL connection (Slim port: 3309)
            try {
                $pdo = new \PDO('mysql:host=127.0.0.1;port=3309;dbname=slim_framework_db', 'root', 'rootpassword');
                $pdo->query('SELECT 1');
                $results['mysql'] = 'Connected';
            } catch (Exception $e) {
                $results['mysql'] = 'Failed: ' . $e->getMessage();
            }

            // Test PostgreSQL connection (Slim port: 5435)
            try {
                $pdo = new \PDO('pgsql:host=127.0.0.1;port=5435;dbname=slim_framework_db', 'postgres', 'postgrespassword');
                $pdo->query('SELECT 1');
                $results['postgres'] = 'Connected';
            } catch (Exception $e) {
                $results['postgres'] = 'Failed: ' . $e->getMessage();
            }

            // Test Redis connection (Slim port: 6382)
            try {
                $redis = new \Redis();
                $redis->connect('127.0.0.1', 6382);
                $redis->ping();
                $redis->close();
                $results['redis'] = 'Connected';
            } catch (Exception $e) {
                $results['redis'] = 'Failed: ' . $e->getMessage();
            }

            $this->logger->info('Database connections tested', $results);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $results]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('Database test failed', ['error' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Demo CRUD operations (comprehensive like Simple PHP)
     */
    public function demoCrud(Request $request, Response $response): Response
    {
        try {
            $results = [];

            // Perform MySQL CRUD operations
            $results['mysql'] = $this->performMySQLCrud();

            // Perform PostgreSQL CRUD operations
            $results['postgres'] = $this->performPostgreSQLCrud();

            $response->getBody()->write(json_encode(['success' => true, 'data' => $results]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Fetch data from external APIs using Guzzle and shared utilities
     */
    public function fetchApiData(Request $request, Response $response): Response
    {
        try {
            $results = [];

            // Using Guzzle HTTP Client
            try {
                $guzzleResponse = $this->httpClient->get('https://jsonplaceholder.typicode.com/posts');
                $posts = json_decode($guzzleResponse->getBody()->getContents(), true);
                $posts = array_slice($posts, 0, 3);

                $results['guzzle_http'] = [
                    'source' => 'Guzzle HTTP Client',
                    'posts' => $posts
                ];
            } catch (Exception $e) {
                $results['guzzle_http'] = ['error' => $e->getMessage()];
            }

            // Using Slim independent API client
            try {
                $apiResults = $this->testExternalApis();
                $results['slim_api'] = $apiResults;
            } catch (Exception $e) {
                $results['slim_api'] = ['error' => $e->getMessage()];
            }

            $this->logger->info('External API data fetched', $results);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $results]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('API fetch failed', ['error' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Test queue operations using shared utilities
     */
    public function testQueue(Request $request, Response $response): Response
    {
        try {
            $results = [];

            // Using Slim independent queue manager
            try {
                $queueResults = $this->testSlimQueue();
                $results['slim_queue'] = $queueResults;

                // Add some Slim-specific queue data
                $slimData = [
                    'framework' => 'Slim',
                    'message' => 'Hello from Slim Framework',
                    'timestamp' => date('c'),
                    'priority' => 1
                ];
                $queueManager->enqueue('slim_queue', $slimData);
                $results['slim_queue_added'] = $slimData;

            } catch (Exception $e) {
                $results['slim_queue'] = ['error' => $e->getMessage()];
            }

            $this->logger->info('Queue operations completed', $results);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $results]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('Queue operations failed', ['error' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Add data to queue
     */
    public function addQueueData(Request $request, Response $response): Response
    {
        try {
            $queueName = $this->getQueueName();

            // Generate batch of 3 randomized records
            $batchData = [];
            for ($i = 0; $i < 3; $i++) {
                $batchData[] = [
                    'id' => rand(1000, 9999),
                    'message' => 'Slim queue message ' . rand(100, 999),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'priority' => rand(1, 5),
                    'type' => 'slim_batch',
                    'batch_index' => $i + 1,
                    'queue_name' => $queueName,
                    'ttl' => 60 // 1 minute TTL (non-modifiable)
                ];
            }

            $queueManager = new QueueManager();
            $successCount = 0;

            // Add each item to queue
            foreach ($batchData as $data) {
                $result = $queueManager->enqueue($queueName, $data);
                if ($result) $successCount++;
            }

            $this->logger->info('Batch data added to queue', [
                'queue_name' => $queueName,
                'batch_size' => count($batchData),
                'success_count' => $successCount
            ]);

            $response->getBody()->write(json_encode([
                'success' => $successCount > 0,
                'message' => "Batch of {$successCount}/3 items added to queue successfully",
                'queue_name' => $queueName,
                'batch_data' => $batchData,
                'queue_status' => 'pending',
                'total_added' => $successCount,
                'message_ttl' => '1 minute (non-modifiable)'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('Add queue data failed', ['error' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Read data from queue
     */
    public function readQueueData(Request $request, Response $response): Response
    {
        try {
            $queueManager = new QueueManager();
            $data = $queueManager->dequeue('slim_user_queue');

            $this->logger->info('Data read from queue', ['data' => $data]);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $data]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('Read queue data failed', ['error' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Clear queue
     */
    public function clearQueue(Request $request, Response $response): Response
    {
        try {
            $queueManager = new QueueManager();
            $result = $queueManager->clearQueue('slim_user_queue');

            $this->logger->info('Queue cleared', ['result' => $result]);

            $response->getBody()->write(json_encode([
                'success' => $result,
                'message' => 'Slim queue cleared successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('Clear queue failed', ['error' => $e->getMessage()]);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Health check endpoint
     */
    public function health(Request $request, Response $response): Response
    {
        $healthData = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'php_version' => phpversion(),
            'slim_version' => '4.x',
            'environment' => $_ENV['APP_ENV'] ?? 'development',
            'web_server' => $this->getWebServerType(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ];

        $this->logger->info('Health check requested', $healthData);

        $response->getBody()->write(json_encode($healthData));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Perform MySQL CRUD operations using direct PDO (Slim ports)
     */
    private function performMySQLCrud(): array
    {
        try {
            $pdo = new \PDO('mysql:host=127.0.0.1;port=3309;dbname=slim_framework_db', 'root', 'rootpassword');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Create tables if not exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // Insert 2 randomized records
            $user1 = ['name' => 'Slim User ' . rand(1000, 9999), 'email' => $this->randomEmail('slim_mysql')];
            $user2 = ['name' => 'Slim User ' . rand(1000, 9999), 'email' => $this->randomEmail('slim_mysql')];

            $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $stmt->execute([$user1['name'], $user1['email']]);
            $insertId1 = $pdo->lastInsertId();

            $stmt->execute([$user2['name'], $user2['email']]);
            $insertId2 = $pdo->lastInsertId();

            // Update 1 record
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$user1['name'] . ' Updated', $insertId1]);

            // Delete 1 record
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$insertId2]);

            // Get all data
            $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
            $allData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'database' => 'MySQL',
                'operations' => [
                    'inserted' => 2,
                    'updated' => 1,
                    'deleted' => 1
                ],
                'sample_data' => $allData,
                'total_records' => count($allData)
            ];

        } catch (Exception $e) {
            return [
                'database' => 'MySQL',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Perform PostgreSQL CRUD operations using direct PDO (Slim ports)
     */
    private function performPostgreSQLCrud(): array
    {
        try {
            $pdo = new \PDO('pgsql:host=127.0.0.1;port=5435;dbname=slim_framework_db', 'postgres', 'postgrespassword');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Create tables if not exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Insert 2 randomized records
            $user1 = ['name' => 'Slim PG User ' . rand(1000, 9999), 'email' => $this->randomEmail('slim_postgres')];
            $user2 = ['name' => 'Slim PG User ' . rand(1000, 9999), 'email' => $this->randomEmail('slim_postgres')];

            $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?) RETURNING id");
            $stmt->execute([$user1['name'], $user1['email']]);
            $insertId1 = $stmt->fetchColumn();

            $stmt->execute([$user2['name'], $user2['email']]);
            $insertId2 = $stmt->fetchColumn();

            // Update 1 record
            $stmt = $pdo->prepare("UPDATE users SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user1['name'] . ' Updated', $insertId1]);

            // Delete 1 record
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$insertId2]);

            // Get all data
            $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
            $allData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'database' => 'PostgreSQL',
                'operations' => [
                    'inserted' => 2,
                    'updated' => 1,
                    'deleted' => 1
                ],
                'sample_data' => $allData,
                'total_records' => count($allData)
            ];

        } catch (Exception $e) {
            return [
                'database' => 'PostgreSQL',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate random email
     */
    private function randomEmail(string $prefix = 'user'): string
    {
        return $prefix . '_' . rand(1000, 9999) . '_' . time() . '@example.com';
    }

    /**
     * Generate application-specific queue name
     * Format: slim_{php_version}_{web_server}
     */
    private function getQueueName(): string
    {
        $phpVersion = str_replace('.', '', phpversion()); // e.g., 84 for 8.4
        $webServer = $this->getWebServerType();

        return sprintf('slim_%s_%s', $phpVersion, $webServer);
    }

    /**
     * Get web server type for queue naming
     */
    private function getWebServerType(): string
    {
        $sapi = php_sapi_name();
        $server = $_SERVER['SERVER_SOFTWARE'] ?? '';

        if ($sapi === 'cli-server') {
            return 'php_builtin';
        } elseif (strpos($server, 'Apache') !== false) {
            if (function_exists('apache_get_modules') && in_array('mod_php', apache_get_modules())) {
                return 'apache_modphp';
            } else {
                return 'apache_fpm';
            }
        } elseif (strpos($server, 'nginx') !== false) {
            return 'nginx_fpm';
        } else {
            return 'unknown';
        }
    }

    /**
     * Detect web server type (like Simple PHP)
     */
    private function detectWebServerType(): string
    {
        $sapi = php_sapi_name();
        $server = $_SERVER['SERVER_SOFTWARE'] ?? '';

        if ($sapi === 'cli-server') {
            return 'PHP Built-in Server';
        } elseif (strpos($server, 'Apache') !== false) {
            if (function_exists('apache_get_modules') && in_array('mod_php', apache_get_modules())) {
                return 'Apache mod_php';
            } else {
                return 'Apache PHP-FPM';
            }
        } elseif (strpos($server, 'nginx') !== false) {
            return 'Nginx PHP-FPM';
        } else {
            return 'Unknown';
        }
    }

    /**
     * Generate random demo data for queue operations (like Simple PHP)
     */
    private function generateRandomData(): array
    {
        $names = ['Alice Johnson', 'Bob Smith', 'Carol Davis', 'David Wilson', 'Eva Brown', 'Frank Miller', 'Grace Lee', 'Henry Taylor'];
        $actions = ['process_order', 'send_notification', 'generate_report', 'backup_data', 'sync_database', 'send_email'];
        $priorities = ['high', 'medium', 'low'];
        $departments = ['Sales', 'Marketing', 'Engineering', 'Support', 'Finance'];

        $randomName = $names[array_rand($names)];
        $randomAction = $actions[array_rand($actions)];
        $randomPriority = $priorities[array_rand($priorities)];
        $randomDepartment = $departments[array_rand($departments)];

        return [
            'id' => uniqid('slim_task_'),
            'name' => $randomName,
            'email' => strtolower(str_replace(' ', '.', $randomName)) . '@company.com',
            'action' => $randomAction,
            'priority' => $randomPriority,
            'department' => $randomDepartment,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 minute')),
            'data' => [
                'amount' => rand(100, 10000),
                'reference' => 'REF-' . rand(10000, 99999),
                'notes' => 'Auto-generated Slim task for ' . $randomAction
            ]
        ];
    }

    /**
     * Generate new random data for queue operations
     */
    public function generateNewRandomData(Request $request, Response $response): Response
    {
        try {
            $randomData = $this->generateRandomData();
            $response->getBody()->write(json_encode(['success' => true, 'data' => $randomData]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Debug environment variables and configuration
     */
    public function debugEnv(Request $request, Response $response): Response
    {
        try {
            $envFiles = [
                __DIR__ . '/../../.env',
                __DIR__ . '/../../config/app.env'
            ];

            $fileStatus = [];
            foreach ($envFiles as $file) {
                $fileStatus[$file] = file_exists($file) ? 'exists' : 'not found';
            }

            $envVars = [
                'MYSQL_HOST' => $_ENV['MYSQL_HOST'] ?? 'not set',
                'MYSQL_PORT' => $_ENV['MYSQL_PORT'] ?? 'not set',
                'MYSQL_DATABASE' => $_ENV['MYSQL_DATABASE'] ?? 'not set',
                'POSTGRES_HOST' => $_ENV['POSTGRES_HOST'] ?? 'not set',
                'POSTGRES_PORT' => $_ENV['POSTGRES_PORT'] ?? 'not set',
                'REDIS_HOST' => $_ENV['REDIS_HOST'] ?? 'not set',
                'REDIS_PORT' => $_ENV['REDIS_PORT'] ?? 'not set'
            ];

            $response->getBody()->write(json_encode(['success' => true, 'env' => $envVars, 'files' => $fileStatus]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Create database tables
     */
    public function createTables(Request $request, Response $response): Response
    {
        try {
            $results = [];

            // Create MySQL tables
            try {
                $pdo = new \PDO('mysql:host=127.0.0.1;port=3309;dbname=slim_framework_db', 'root', 'rootpassword');
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                ");

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS posts (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        title VARCHAR(255) NOT NULL,
                        content TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )
                ");

                $results['mysql'] = 'Tables created successfully';
            } catch (Exception $e) {
                $results['mysql'] = 'Failed: ' . $e->getMessage();
            }

            // Create PostgreSQL tables
            try {
                $pdo = new \PDO('pgsql:host=127.0.0.1;port=5435;dbname=slim_framework_db', 'postgres', 'postgrespassword');
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id SERIAL PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS posts (
                        id SERIAL PRIMARY KEY,
                        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                        title VARCHAR(255) NOT NULL,
                        content TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");

                $results['postgres'] = 'Tables created successfully';
            } catch (Exception $e) {
                $results['postgres'] = 'Failed: ' . $e->getMessage();
            }

            $response->getBody()->write(json_encode(['success' => true, 'data' => $results]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}