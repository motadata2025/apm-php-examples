<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use Exception;
use PDO;
use Redis;

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
     * @var array
     */
    protected $helpers = [];

    private Client $httpClient;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $this->httpClient = new Client(['timeout' => 30]);
    }

    /**
     * Display the main APM dashboard (comprehensive like Simple PHP)
     */
    public function index()
    {
        $phpVersion = phpversion();
        $framework = 'CodeIgniter ' . \CodeIgniter\CodeIgniter::CI_VERSION;
        $deploymentType = $this->detectWebServerType();

        // Override with environment variable if set (for testing/debugging)
        if (isset($_ENV['DEPLOYMENT_DESC'])) {
            $deploymentType = $_ENV['DEPLOYMENT_DESC'];
        }

        // Generate random data for queue operations
        $randomData = $this->generateRandomData();

        $data = [
            'phpVersion' => $phpVersion,
            'frameworkVersion' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'webServer' => $deploymentType,
            'framework' => $framework,
            'deploymentType' => $deploymentType,
            'randomData' => $randomData,
        ];

        return view('apm/dashboard', $data);
    }

    /**
     * Handle AJAX requests (like Simple PHP)
     */
    public function handleAjax()
    {
        $action = $this->request->getPost('action');

        switch ($action) {
            case 'test_databases':
                return $this->testDatabases();
            case 'create_tables':
                return $this->createTables();
            case 'demo_crud':
                return $this->demoCrud();
            case 'fetch_api_data':
                return $this->fetchApiData();
            case 'test_queue':
                return $this->testQueue();
            case 'add_queue_data':
                return $this->addQueueData();
            case 'read_queue_data':
                return $this->readQueueData();
            case 'clear_queue':
                return $this->clearQueue();
            case 'generate_random_data':
                return $this->generateNewRandomData();
            case 'debug_env':
                return $this->debugEnv();
            default:
                return $this->response->setJSON(['success' => false, 'error' => 'Unknown action']);
        }
    }

    /**
     * Comprehensive health check endpoint (like Simple PHP)
     */
    public function healthCheck()
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => (new \DateTime())->format('c'),
                'php_version' => phpversion(),
                'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
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

            return $this->response->setJSON($health, 200);
        } catch (Exception $e) {
            return $this->response->setJSON([
                'status' => 'unhealthy',
                'timestamp' => (new \DateTime())->format('c'),
                'error' => $e->getMessage()
            ], 500);
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
     * Check service health (CodeIgniter ports)
     */
    private function checkServices(): array
    {
        $services = [];

        // Check Redis (CodeIgniter port: 6383)
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6383);
            $redis->ping();
            $redis->close();
            $services['redis'] = 'healthy';
        } catch (Exception $e) {
            $services['redis'] = 'unhealthy';
        }

        // Check MySQL (CodeIgniter port: 3310)
        try {
            $pdo = new \PDO('mysql:host=127.0.0.1;port=3310;dbname=codeigniter_app_db', 'root', 'rootpassword');
            $pdo->query('SELECT 1');
            $services['mysql'] = 'healthy';
        } catch (Exception $e) {
            $services['mysql'] = 'unhealthy';
        }

        // Check PostgreSQL (CodeIgniter port: 5436)
        try {
            $pdo = new \PDO('pgsql:host=127.0.0.1;port=5436;dbname=codeigniter_app_db', 'postgres', 'postgrespassword');
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
    public function testDatabases()
    {
        try {
            $results = [];

            // Test MySQL connection (CodeIgniter port: 3310)
            try {
                $pdo = new \PDO('mysql:host=127.0.0.1;port=3310;dbname=codeigniter_app_db', 'root', 'rootpassword');
                $pdo->query('SELECT 1');
                $results['mysql'] = 'Connected';
            } catch (Exception $e) {
                $results['mysql'] = 'Failed: ' . $e->getMessage();
            }

            // Test PostgreSQL connection (CodeIgniter port: 5436)
            try {
                $pdo = new \PDO('pgsql:host=127.0.0.1;port=5436;dbname=codeigniter_app_db', 'postgres', 'postgrespassword');
                $pdo->query('SELECT 1');
                $results['postgres'] = 'Connected';
            } catch (Exception $e) {
                $results['postgres'] = 'Failed: ' . $e->getMessage();
            }

            // Test Redis connection (CodeIgniter port: 6383)
            try {
                $redis = new \Redis();
                $redis->connect('127.0.0.1', 6383);
                $redis->ping();
                $redis->close();
                $results['redis'] = 'Connected';
            } catch (Exception $e) {
                $results['redis'] = 'Failed: ' . $e->getMessage();
            }

            // Test CodeIgniter DB connections
            try {
                $db = \Config\Database::connect();
                $db->query('SELECT 1');
                $results['codeigniter_mysql'] = 'Connected';
            } catch (Exception $e) {
                $results['codeigniter_mysql'] = 'Failed: ' . $e->getMessage();
            }

            return $this->response->setJSON(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Demo CRUD operations (comprehensive like Simple PHP)
     */
    public function demoCrud()
    {
        try {
            $results = [];

            // Perform MySQL CRUD operations
            $results['mysql'] = $this->performMySQLCrud();

            // Perform PostgreSQL CRUD operations
            $results['postgres'] = $this->performPostgreSQLCrud();

            return $this->response->setJSON(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Fetch data from external APIs using Guzzle and shared utilities
     */
    public function fetchApiData()
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

            // Using CodeIgniter HTTP Client (if available)
            try {
                $client = \Config\Services::curlrequest();
                $ciResponse = $client->get('https://api.github.com/users/codeigniter4');
                $githubData = json_decode($ciResponse->getBody(), true);

                $results['codeigniter_http'] = [
                    'source' => 'CodeIgniter HTTP Client',
                    'github_data' => [
                        'name' => $githubData['name'] ?? 'Unknown',
                        'public_repos' => $githubData['public_repos'] ?? 0,
                        'followers' => $githubData['followers'] ?? 0
                    ]
                ];
            } catch (Exception $e) {
                $results['codeigniter_http'] = ['error' => $e->getMessage()];
            }

            // Using CodeIgniter HTTP client
            try {
                $apiResults = $this->testExternalApis();
                $results['codeigniter_api'] = $apiResults;
            } catch (Exception $e) {
                $results['codeigniter_api'] = ['error' => $e->getMessage()];
            }

            log_message('info', 'External API data fetched', $results);

            return $this->response->setJSON(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            log_message('error', 'API fetch failed: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Test queue operations using shared utilities
     */
    public function testQueue()
    {
        try {
            $results = [];

            // Using shared queue manager
            try {
                $queueManager = new QueueManager();
                $sharedResults = $queueManager->demo();
                $results['shared_queue'] = $sharedResults;

                // Add some CodeIgniter-specific queue data
                $ciData = [
                    'framework' => 'CodeIgniter',
                    'message' => 'Hello from CodeIgniter Framework',
                    'timestamp' => date('c'),
                    'priority' => 1,
                    'version' => \CodeIgniter\CodeIgniter::CI_VERSION
                ];
                $queueManager->enqueue('codeigniter_queue', $ciData);
                $results['codeigniter_queue_added'] = $ciData;

            } catch (Exception $e) {
                $results['shared_queue'] = ['error' => $e->getMessage()];
            }

            log_message('info', 'Queue operations completed', $results);

            return $this->response->setJSON(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            log_message('error', 'Queue operations failed: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Add data to queue
     */
    public function addQueueData()
    {
        try {
            $data = json_decode($this->request->getPost('data') ?? '{}', true);

            $queueManager = new QueueManager();
            $result = $queueManager->enqueue('codeigniter_user_queue', $data);

            log_message('info', 'Data added to queue', ['data' => $data, 'result' => $result]);

            return $this->response->setJSON([
                'success' => $result,
                'message' => 'Data added to CodeIgniter queue successfully'
            ]);
        } catch (Exception $e) {
            log_message('error', 'Add queue data failed: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Read data from queue
     */
    public function readQueueData()
    {
        try {
            $queueManager = new QueueManager();
            $data = $queueManager->dequeue('codeigniter_user_queue');

            log_message('info', 'Data read from queue', ['data' => $data]);

            return $this->response->setJSON(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            log_message('error', 'Read queue data failed: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Clear queue
     */
    public function clearQueue()
    {
        try {
            $queueManager = new QueueManager();
            $result = $queueManager->clearQueue('codeigniter_user_queue');

            log_message('info', 'Queue cleared', ['result' => $result]);

            return $this->response->setJSON([
                'success' => $result,
                'message' => 'CodeIgniter queue cleared successfully'
            ]);
        } catch (Exception $e) {
            log_message('error', 'Clear queue failed: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Health check endpoint
     */
    public function health()
    {
        $healthData = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'php_version' => phpversion(),
            'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'environment' => ENVIRONMENT
        ];

        log_message('info', 'Health check requested', $healthData);

        return $this->response->setJSON($healthData);
    }

    /**
     * Perform MySQL CRUD operations using direct PDO (CodeIgniter ports)
     */
    private function performMySQLCrud(): array
    {
        try {
            $pdo = new \PDO('mysql:host=127.0.0.1;port=3310;dbname=codeigniter_app_db', 'root', 'rootpassword');
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
            $user1 = ['name' => 'CodeIgniter User ' . rand(1000, 9999), 'email' => $this->randomEmail('ci_mysql')];
            $user2 = ['name' => 'CodeIgniter User ' . rand(1000, 9999), 'email' => $this->randomEmail('ci_mysql')];

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
     * Perform PostgreSQL CRUD operations using direct PDO (CodeIgniter ports)
     */
    private function performPostgreSQLCrud(): array
    {
        try {
            $pdo = new \PDO('pgsql:host=127.0.0.1;port=5436;dbname=codeigniter_app_db', 'postgres', 'postgrespassword');
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
            $user1 = ['name' => 'CodeIgniter PG User ' . rand(1000, 9999), 'email' => $this->randomEmail('ci_postgres')];
            $user2 = ['name' => 'CodeIgniter PG User ' . rand(1000, 9999), 'email' => $this->randomEmail('ci_postgres')];

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
     * Generate application-specific queue name
     * Format: codeigniter_{php_version}_{web_server}
     */
    private function getQueueName(): string
    {
        $phpVersion = str_replace('.', '', phpversion()); // e.g., 84 for 8.4
        $webServer = $this->getWebServerType();

        return sprintf('codeigniter_%s_%s', $phpVersion, $webServer);
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
     * Generate random email
     */
    private function randomEmail(string $prefix = 'user'): string
    {
        return $prefix . '_' . rand(1000, 9999) . '_' . time() . '@example.com';
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
            'id' => uniqid('codeigniter_task_'),
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
                'notes' => 'Auto-generated CodeIgniter task for ' . $randomAction
            ]
        ];
    }

    /**
     * Generate new random data for queue operations
     */
    public function generateNewRandomData()
    {
        try {
            $randomData = $this->generateRandomData();
            return $this->response->setJSON(['success' => true, 'data' => $randomData]);
        } catch (Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Debug environment variables and configuration
     */
    public function debugEnv()
    {
        try {
            $envFiles = [
                ROOTPATH . '.env',
                APPPATH . '../config/app.env'
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

            return $this->response->setJSON(['success' => true, 'env' => $envVars, 'files' => $fileStatus]);
        } catch (Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Create database tables
     */
    public function createTables()
    {
        try {
            $results = [];

            // Create MySQL tables
            try {
                $pdo = new \PDO('mysql:host=127.0.0.1;port=3310;dbname=codeigniter_app_db', 'root', 'rootpassword');
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
                $pdo = new \PDO('pgsql:host=127.0.0.1;port=5436;dbname=codeigniter_app_db', 'postgres', 'postgrespassword');
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

            return $this->response->setJSON(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}