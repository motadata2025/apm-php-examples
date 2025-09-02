<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\ProcessDataMessage;
use App\Entity\User;
use Exception;
use PDO;
use Redis;

class ApmController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private HttpClientInterface $httpClient;
    private MessageBusInterface $messageBus;

    public function __construct(
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->messageBus = $messageBus;
    }

    #[Route('/', name: 'apm_dashboard')]
    public function dashboard(): Response
    {
        $phpVersion = phpversion();
        $framework = 'Symfony ' . \Symfony\Component\HttpKernel\Kernel::VERSION;
        $deploymentType = $this->detectWebServerType();

        // Override with environment variable if set (for testing/debugging)
        if (isset($_ENV['DEPLOYMENT_DESC'])) {
            $deploymentType = $_ENV['DEPLOYMENT_DESC'];
        }

        // Generate random data for queue operations
        $randomData = $this->generateRandomData();

        return $this->render('apm/dashboard.html.twig', [
            'phpVersion' => $phpVersion,
            'framework' => $framework,
            'deploymentType' => $deploymentType,
            'randomData' => $randomData,
        ]);
    }

    /**
     * Handle AJAX requests (like Simple PHP)
     */
    #[Route('/', name: 'apm_ajax', methods: ['POST'])]
    public function handleAjax(Request $request): JsonResponse
    {
        $action = $request->request->get('action');

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
                return $this->addQueueData($request);
            case 'read_queue_data':
                return $this->readQueueData();
            case 'clear_queue':
                return $this->clearQueue();
            case 'generate_random_data':
                return $this->generateNewRandomData();
            case 'debug_env':
                return $this->debugEnv();
            default:
                return new JsonResponse(['success' => false, 'error' => 'Unknown action']);
        }
    }

    /**
     * Comprehensive health check endpoint (like Simple PHP)
     */
    #[Route('/health', name: 'apm_health', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => (new \DateTime())->format('c'),
                'php_version' => phpversion(),
                'symfony_version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
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

            return new JsonResponse($health, 200, [], false);
        } catch (Exception $e) {
            return new JsonResponse([
                'status' => 'unhealthy',
                'timestamp' => (new \DateTime())->format('c'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system uptime
     */
    private function getUptime(): ?float
    {
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = floatval(explode(' ', $uptime)[0]);
            return round($uptime);
        }
        return null;
    }

    /**
     * Check service health
     */
    private function checkServices(): array
    {
        $services = [];

        // Check Redis
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6381);
            $redis->ping();
            $redis->close();
            $services['redis'] = 'healthy';
        } catch (Exception $e) {
            $services['redis'] = 'unhealthy';
        }

        // Check MySQL
        try {
            $pdo = new PDO('mysql:host=127.0.0.1;port=3308;dbname=symfony_app_db', 'root', 'rootpassword');
            $pdo->query('SELECT 1');
            $services['mysql'] = 'healthy';
        } catch (Exception $e) {
            $services['mysql'] = 'unhealthy';
        }

        // Check PostgreSQL
        try {
            $pdo = new PDO('pgsql:host=127.0.0.1;port=5434;dbname=symfony_app_db', 'postgres', 'postgrespassword');
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
    public function testDatabases(): JsonResponse
    {
        try {
            $results = [];

            // Test MySQL connection
            try {
                $pdo = new PDO('mysql:host=127.0.0.1;port=3308;dbname=symfony_app_db', 'root', 'rootpassword');
                $pdo->query('SELECT 1');
                $results['mysql'] = 'Connected';
            } catch (Exception $e) {
                $results['mysql'] = 'Failed: ' . $e->getMessage();
            }

            // Test PostgreSQL connection
            try {
                $pdo = new PDO('pgsql:host=127.0.0.1;port=5434;dbname=symfony_app_db', 'postgres', 'postgrespassword');
                $pdo->query('SELECT 1');
                $results['postgres'] = 'Connected';
            } catch (Exception $e) {
                $results['postgres'] = 'Failed: ' . $e->getMessage();
            }

            // Test Redis connection
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6381);
                $redis->ping();
                $redis->close();
                $results['redis'] = 'Connected';
            } catch (Exception $e) {
                $results['redis'] = 'Failed: ' . $e->getMessage();
            }

            // Test Doctrine DBAL connection
            try {
                $connection = $this->entityManager->getConnection();
                $connection->executeQuery('SELECT 1');
                $results['doctrine'] = 'Connected';
            } catch (Exception $e) {
                $results['doctrine'] = 'Failed: ' . $e->getMessage();
            }

            return new JsonResponse(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/demo-crud', name: 'apm_demo_crud', methods: ['POST'])]
    public function demoCrud(): JsonResponse
    {
        try {
            $results = [];

            // Demo CRUD Operations for MySQL
            try {
                $mysqlResults = $this->performMySQLCrud();
                $results['mysql'] = $mysqlResults;
            } catch (Exception $e) {
                $results['mysql'] = ['error' => $e->getMessage()];
            }

            // Demo CRUD Operations for PostgreSQL
            try {
                $postgresResults = $this->performPostgreSQLCrud();
                $results['postgres'] = $postgresResults;
            } catch (Exception $e) {
                $results['postgres'] = ['error' => $e->getMessage()];
            }

            // Demo CRUD Operations for MySQL
            try {
                $mysqlResults = $this->performMySQLCrud();
                $results['mysql'] = $mysqlResults;
            } catch (Exception $e) {
                $results['mysql'] = ['error' => $e->getMessage()];
            }

            // Demo CRUD Operations for PostgreSQL
            try {
                $postgresResults = $this->performPostgreSQLCrud();
                $results['postgres'] = $postgresResults;
            } catch (Exception $e) {
                $results['postgres'] = ['error' => $e->getMessage()];
            }

            // Demonstrate Doctrine ORM operations
            try {
                $user = new User();
                $user->setEmail($this->randomEmail('Symfony'));
                $user->setName('Symfony User');

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $results['doctrine_created'] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName()
                ];

                // Read users using Doctrine
                $userRepository = $this->entityManager->getRepository(User::class);
                $users = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

                $results['doctrine_users'] = array_map(function($user) {
                    return [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'name' => $user->getName(),
                        'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
                    ];
                }, $users);

            } catch (Exception $e) {
                $results['doctrine_error'] = $e->getMessage();
            }

            // Demonstrate Symfony CRUD operations
            try {
                $crudResults = $this->performCrudOperations();
                $results['symfony_crud'] = $crudResults;
            } catch (Exception $e) {
                $results['symfony_crud'] = ['error' => $e->getMessage()];
            }

            return new JsonResponse(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Fetch external API data (comprehensive like Simple PHP)
     */
    public function fetchApiData(): JsonResponse
    {
        try {
            $results = [];

            // Test JSONPlaceholder API
            $results['jsonplaceholder'] = $this->testJsonPlaceholder();

            // Test HTTPBin API
            $results['httpbin'] = $this->testHttpBin();

            // Test slow API simulation
            $results['slow_api'] = $this->testSlowApi();

            return new JsonResponse(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Test JSONPlaceholder API
     */
    private function testJsonPlaceholder(): array
    {
        try {
            $startTime = microtime(true);

            // Get posts
            $postsResponse = $this->httpClient->request('GET', 'https://jsonplaceholder.typicode.com/posts', [
                'query' => ['_limit' => 5],
                'timeout' => 10
            ]);
            $postsData = $postsResponse->toArray();
            $postsDuration = microtime(true) - $startTime;

            // Get specific post
            $startTime = microtime(true);
            $postResponse = $this->httpClient->request('GET', 'https://jsonplaceholder.typicode.com/posts/1', [
                'timeout' => 10
            ]);
            $postData = $postResponse->toArray();
            $postDuration = microtime(true) - $startTime;

            // Create new post
            $startTime = microtime(true);
            $newPostResponse = $this->httpClient->request('POST', 'https://jsonplaceholder.typicode.com/posts', [
                'json' => [
                    'title' => 'Symfony APM Test Post',
                    'body' => 'This is a test post from Symfony APM Examples',
                    'userId' => 1
                ],
                'timeout' => 10
            ]);
            $newPostData = $newPostResponse->toArray();
            $newPostDuration = microtime(true) - $startTime;

            return [
                'service' => 'JSONPlaceholder',
                'tests' => [
                    'get_posts' => [
                        'success' => $postsResponse->getStatusCode() === 200,
                        'status_code' => $postsResponse->getStatusCode(),
                        'duration' => round($postsDuration * 1000, 2),
                        'data_count' => count($postsData)
                    ],
                    'get_post' => [
                        'success' => $postResponse->getStatusCode() === 200,
                        'status_code' => $postResponse->getStatusCode(),
                        'duration' => round($postDuration * 1000, 2),
                        'data' => $postData
                    ],
                    'create_post' => [
                        'success' => $newPostResponse->getStatusCode() === 201,
                        'status_code' => $newPostResponse->getStatusCode(),
                        'duration' => round($newPostDuration * 1000, 2),
                        'data' => $newPostData
                    ]
                ],
                'status' => 'completed'
            ];

        } catch (Exception $e) {
            return [
                'service' => 'JSONPlaceholder',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test HTTPBin API
     */
    private function testHttpBin(): array
    {
        try {
            // Test GET with parameters
            $startTime = microtime(true);
            $getResponse = $this->httpClient->request('GET', 'https://httpbin.org/get', [
                'query' => ['param1' => 'value1', 'param2' => 'value2'],
                'timeout' => 10
            ]);
            $getDuration = microtime(true) - $startTime;

            // Test POST with data
            $startTime = microtime(true);
            $postResponse = $this->httpClient->request('POST', 'https://httpbin.org/post', [
                'json' => ['test' => 'data', 'timestamp' => time()],
                'timeout' => 10
            ]);
            $postDuration = microtime(true) - $startTime;

            // Test delay endpoint
            $startTime = microtime(true);
            $delayResponse = $this->httpClient->request('GET', 'https://httpbin.org/delay/2', [
                'timeout' => 15
            ]);
            $delayDuration = microtime(true) - $startTime;

            return [
                'service' => 'HTTPBin',
                'tests' => [
                    'get_test' => [
                        'success' => $getResponse->getStatusCode() === 200,
                        'status_code' => $getResponse->getStatusCode(),
                        'duration' => round($getDuration * 1000, 2)
                    ],
                    'post_test' => [
                        'success' => $postResponse->getStatusCode() === 200,
                        'status_code' => $postResponse->getStatusCode(),
                        'duration' => round($postDuration * 1000, 2)
                    ],
                    'delay_test' => [
                        'success' => $delayResponse->getStatusCode() === 200,
                        'status_code' => $delayResponse->getStatusCode(),
                        'duration' => round($delayDuration * 1000, 2)
                    ]
                ],
                'status' => 'completed'
            ];

        } catch (Exception $e) {
            return [
                'service' => 'HTTPBin',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test slow API simulation
     */
    private function testSlowApi(): array
    {
        try {
            // Simulate slow API call
            $startTime = microtime(true);
            sleep(1); // Simulate 1 second delay
            $duration = microtime(true) - $startTime;

            return [
                'service' => 'Slow API Simulation',
                'duration' => round($duration * 1000, 2),
                'status' => 'completed',
                'message' => 'Simulated slow API response from Symfony'
            ];

        } catch (Exception $e) {
            return [
                'service' => 'Slow API Simulation',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test queue system (comprehensive like Simple PHP)
     */
    public function testQueue(): JsonResponse
    {
        try {
            $queueName = $this->getQueueName();
            $results = [];

            // Add some demo data using Redis directly (Symfony style)
            $demoData = [
                ['name' => 'Symfony Demo User 1', 'email' => 'demo1@symfony.com', 'action' => 'process_user'],
                ['name' => 'Symfony Demo User 2', 'email' => 'demo2@symfony.com', 'action' => 'send_email'],
                ['name' => 'Symfony Demo User 3', 'email' => 'demo3@symfony.com', 'action' => 'generate_report']
            ];

            $redis = new Redis();
            $redis->connect('127.0.0.1', 6381);

            $successCount = 0;
            foreach ($demoData as $data) {
                try {
                    $job = [
                        'id' => uniqid('symfony_'),
                        'data' => $data,
                        'created_at' => time(),
                        'status' => 'pending',
                        'queue_name' => $queueName
                    ];

                    // Add to Redis queue
                    $redis->lpush($queueName, json_encode($job));
                    $redis->expire($queueName, 60); // 1 minute TTL
                    $successCount++;
                } catch (Exception $e) {
                    // Continue with other items
                }
            }

            $redis->close();

            $results['demo_data_added'] = $successCount;
            $results['queue_name'] = $queueName;
            $results['queue_length'] = $redis->llen($queueName);
            $results['message'] = 'Symfony demo queue operations completed';

            return new JsonResponse(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Add data to queue (comprehensive like Simple PHP)
     */
    public function addQueueData(Request $request): JsonResponse
    {
        try {
            $queueName = $this->getQueueName();
            $data = $request->request->get('data', '{}');

            // Validate JSON
            $decodedData = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid JSON data']);
            }

            $redis = new Redis();
            $redis->connect('127.0.0.1', 6381);

            // Add 3 items to queue (like Simple PHP)
            $successCount = 0;
            for ($i = 1; $i <= 3; $i++) {
                try {
                    $job = [
                        'id' => uniqid('symfony_'),
                        'data' => array_merge($decodedData, ['batch_item' => $i]),
                        'created_at' => time(),
                        'status' => 'pending',
                        'queue_name' => $queueName
                    ];

                    $redis->lpush($queueName, json_encode($job));
                    $redis->expire($queueName, 60); // 1 minute TTL
                    $successCount++;
                } catch (Exception $e) {
                    // Continue with other items
                }
            }

            $redis->close();

            return new JsonResponse([
                'success' => true,
                'message' => "Added {$successCount} items to Symfony queue successfully",
                'queue_name' => $queueName,
                'items_added' => $successCount,
                'ttl' => '1 minute'
            ]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Read data from queue (comprehensive like Simple PHP)
     */
    public function readQueueData(): JsonResponse
    {
        try {
            $queueName = $this->getQueueName();
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6381);

            $allData = $redis->lrange($queueName, 0, -1);
            $redis->close();

            $results = [];
            foreach ($allData as $jobData) {
                $job = json_decode($jobData, true);
                if ($job) {
                    $results[] = $job;
                }
            }

            return new JsonResponse([
                'success' => true,
                'data' => $results,
                'count' => count($results),
                'queue_name' => $queueName
            ]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Clear queue data (comprehensive like Simple PHP)
     */
    public function clearQueue(): JsonResponse
    {
        try {
            $queueName = $this->getQueueName();
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6381);

            $itemCount = $redis->llen($queueName);

            // Clear the queue
            $redis->del($queueName);
            $redis->close();

            return new JsonResponse([
                'success' => true,
                'message' => 'Symfony queue cleared successfully',
                'queue_name' => $queueName,
                'items_removed' => $itemCount
            ]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/health', name: 'apm_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => (new \DateTime())->format('c'),
            'php_version' => phpversion(),
            'symfony_version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
            'environment' => $this->getParameter('kernel.environment')
        ]);
    }

    // Independent Database Connection Methods
    private function testMySQLConnection(): string
    {
        try {
            $dsn = "mysql:host=127.0.0.1;port=3306;dbname=apm_test";
            $pdo = new PDO($dsn, 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            $pdo->query('SELECT 1');
            return 'Connected';
        } catch (Exception $e) {
            return 'Failed: ' . $e->getMessage();
        }
    }

    private function testPostgreSQLConnection(): string
    {
        try {
            $dsn = "pgsql:host=127.0.0.1;port=5432;dbname=apm_test";
            $pdo = new PDO($dsn, 'postgres', 'password', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            $pdo->query('SELECT 1');
            return 'Connected';
        } catch (Exception $e) {
            return 'Failed: ' . $e->getMessage();
        }
    }

    private function testRedisConnection(): string
    {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->ping();
            $redis->close();
            return 'Connected';
        } catch (Exception $e) {
            return 'Failed: ' . $e->getMessage();
        }
    }

    private function getWebServerInfo(): array
    {
        $webServer = 'Unknown';
        $version = 'Unknown';

        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            $serverSoftware = $_SERVER['SERVER_SOFTWARE'];
            if (strpos($serverSoftware, 'Apache') !== false) {
                $webServer = 'Apache';
                if (preg_match('/Apache\/([0-9.]+)/', $serverSoftware, $matches)) {
                    $version = $matches[1];
                }
            } elseif (strpos($serverSoftware, 'nginx') !== false) {
                $webServer = 'Nginx';
                if (preg_match('/nginx\/([0-9.]+)/', $serverSoftware, $matches)) {
                    $version = $matches[1];
                }
            } elseif (strpos($serverSoftware, 'PHP') !== false) {
                $webServer = 'PHP Built-in Server';
                $version = phpversion();
            }
        }

        return [
            'name' => $webServer,
            'version' => $version,
            'deployment_type' => $this->getDeploymentType()
        ];
    }

    private function getDeploymentType(): string
    {
        if (php_sapi_name() === 'cli-server') {
            return 'PHP Built-in Server';
        } elseif (function_exists('apache_get_version')) {
            return 'Apache mod_php';
        } elseif (isset($_SERVER['SERVER_SOFTWARE'])) {
            $serverSoftware = $_SERVER['SERVER_SOFTWARE'];
            if (strpos($serverSoftware, 'Apache') !== false) {
                return 'Apache PHP-FPM';
            } elseif (strpos($serverSoftware, 'nginx') !== false) {
                return 'Nginx PHP-FPM';
            }
        }
        return 'Unknown';
    }

    // CRUD Operations following Simple PHP pattern
    private function performCrudOperations(): array
    {
        $results = [];
        $timestamp = time();

        try {
            // STEP 1: CREATE - Add 2 users with randomized data
            $users = $this->generateRandomUsers(2, $timestamp);
            $createdUsers = [];

            foreach ($users as $userData) {
                $user = new User();
                $user->setEmail($userData['email']);
                $user->setName($userData['name']);

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $createdUsers[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName()
                ];
            }

            $results['created'] = $createdUsers;

            // STEP 2: UPDATE - Modify 1 user (first one)
            if (!empty($createdUsers)) {
                $userToUpdate = $this->entityManager->getRepository(User::class)->find($createdUsers[0]['id']);
                if ($userToUpdate) {
                    $newName = $userToUpdate->getName() . '_UPDATED_' . $timestamp;
                    $userToUpdate->setName($newName);
                    $this->entityManager->flush();

                    $results['updated'] = [
                        'id' => $userToUpdate->getId(),
                        'email' => $userToUpdate->getEmail(),
                        'name' => $userToUpdate->getName()
                    ];
                }
            }

            // STEP 3: DELETE - Remove 1 user (second one)
            if (count($createdUsers) > 1) {
                $userToDelete = $this->entityManager->getRepository(User::class)->find($createdUsers[1]['id']);
                if ($userToDelete) {
                    $results['deleted'] = [
                        'id' => $userToDelete->getId(),
                        'email' => $userToDelete->getEmail(),
                        'name' => $userToDelete->getName()
                    ];

                    $this->entityManager->remove($userToDelete);
                    $this->entityManager->flush();
                }
            }

            // STEP 4: READ - Query remaining data
            $remainingUsers = $this->entityManager->getRepository(User::class)->findAll();
            $results['remaining_count'] = count($remainingUsers);

            return $results;

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function generateRandomUsers(int $count, int $timestamp): array
    {
        $users = [];
        $names = ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry'];
        $domains = ['example.com', 'test.org', 'demo.net', 'sample.io'];

        for ($i = 0; $i < $count; $i++) {
            $name = $names[array_rand($names)] . '_' . $timestamp . '_' . $i;
            $email = strtolower($name) . '@' . $domains[array_rand($domains)];

            $users[] = [
                'name' => $name,
                'email' => $email
            ];
        }

        return $users;
    }

    // API Operations following Simple PHP pattern
    private function testExternalApis(): array
    {
        $results = [];

        try {
            // Test JSONPlaceholder API
            $response = $this->httpClient->request('GET', 'https://jsonplaceholder.typicode.com/posts/1', [
                'timeout' => 10
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                $results['jsonplaceholder'] = [
                    'status' => 'success',
                    'title' => $data['title'] ?? 'No title',
                    'response_time' => 'Fast'
                ];
            } else {
                $results['jsonplaceholder'] = ['status' => 'failed', 'error' => 'HTTP ' . $response->getStatusCode()];
            }
        } catch (Exception $e) {
            $results['jsonplaceholder'] = ['status' => 'failed', 'error' => $e->getMessage()];
        }

        try {
            // Test HTTPBin API
            $response = $this->httpClient->request('GET', 'https://httpbin.org/json', [
                'timeout' => 10
            ]);

            if ($response->getStatusCode() === 200) {
                $results['httpbin'] = [
                    'status' => 'success',
                    'response_time' => 'Fast'
                ];
            } else {
                $results['httpbin'] = ['status' => 'failed', 'error' => 'HTTP ' . $response->getStatusCode()];
            }
        } catch (Exception $e) {
            $results['httpbin'] = ['status' => 'failed', 'error' => $e->getMessage()];
        }

        return $results;
    }

    // Queue Operations following Simple PHP pattern
    private function testSymfonyQueue(): array
    {
        $results = [];

        try {
            // Add demo messages to Symfony Messenger
            $demoData = [
                ['name' => 'Demo User 1', 'email' => 'demo1@example.com', 'action' => 'process_data'],
                ['name' => 'Demo User 2', 'email' => 'demo2@example.com', 'action' => 'send_email'],
                ['name' => 'Demo User 3', 'email' => 'demo3@example.com', 'action' => 'generate_report']
            ];

            foreach ($demoData as $data) {
                $message = new ProcessDataMessage($data);
                $this->messageBus->dispatch($message);
            }

            $results['demo_data_added'] = count($demoData);
            $results['message'] = 'Demo queue operations completed';
            $results['queue_status'] = 'Active';

            return $results;

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function performMySQLCrud(): array
    {
        $host = $_ENV['MYSQL_HOST'] ?? '127.0.0.1';
        $port = $_ENV['MYSQL_PORT'] ?? '3308';
        $database = $_ENV['MYSQL_DATABASE'] ?? 'symfony_app_db';
        $username = $_ENV['MYSQL_USERNAME'] ?? 'root';
        $password = $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';

        $dsn = "mysql:host={$host};port={$port};dbname={$database}";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        $user1 = ['name' => 'MySQL User ' . rand(1000, 9999), 'email' => $this->randomEmail('mysql')];
        $user2 = ['name' => 'MySQL User ' . rand(1000, 9999), 'email' => $this->randomEmail('mysql')];

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
        $allData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'inserted' => 2,
            'updated' => 1,
            'deleted' => 1,
            'total_records' => count($allData),
            'data' => $allData
        ];
    }

    private function performPostgreSQLCrud(): array
    {
        $host = $_ENV['POSTGRES_HOST'] ?? '127.0.0.1';
        $port = $_ENV['POSTGRES_PORT'] ?? '5434';
        $database = $_ENV['POSTGRES_DATABASE'] ?? 'symfony_app_db';
        $username = $_ENV['POSTGRES_USERNAME'] ?? 'postgres';
        $password = $_ENV['POSTGRES_PASSWORD'] ?? 'rootpassword';

        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        $user1 = ['name' => 'PostgreSQL User ' . rand(1000, 9999), 'email' => $this->randomEmail('postgres')];
        $user2 = ['name' => 'PostgreSQL User ' . rand(1000, 9999), 'email' => $this->randomEmail('postgres')];

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
        $allData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'inserted' => 2,
            'updated' => 1,
            'deleted' => 1,
            'total_records' => count($allData),
            'data' => $allData
        ];
    }

    private function randomEmail(string $prefix = 'user'): string
    {
        return $prefix . '_' . rand(1000, 9999) . '_' . time() . '@example.com';
    }

    /**
     * Generate application-specific queue name
     * Format: symfony_{php_version}_{web_server}
     */
    private function getQueueName(): string
    {
        $phpVersion = str_replace('.', '', phpversion()); // e.g., 84 for 8.4
        $webServer = $this->getWebServerType();

        return sprintf('symfony_%s_%s', $phpVersion, $webServer);
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
            'id' => uniqid('symfony_task_'),
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
                'notes' => 'Auto-generated Symfony task for ' . $randomAction
            ]
        ];
    }

    /**
     * Generate new random data for queue operations
     */
    public function generateNewRandomData(): JsonResponse
    {
        try {
            $randomData = $this->generateRandomData();
            return new JsonResponse(['success' => true, 'data' => $randomData]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Debug environment variables and configuration
     */
    public function debugEnv(): JsonResponse
    {
        try {
            $envFiles = [
                $this->getParameter('kernel.project_dir') . '/.env',
                $this->getParameter('kernel.project_dir') . '/config/app.env'
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

            return new JsonResponse(['success' => true, 'env' => $envVars, 'files' => $fileStatus]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Create database tables
     */
    public function createTables(): JsonResponse
    {
        try {
            $results = [];

            // Create MySQL tables
            try {
                $pdo = new PDO('mysql:host=127.0.0.1;port=3308;dbname=symfony_app_db', 'root', 'rootpassword');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
                $pdo = new PDO('pgsql:host=127.0.0.1;port=5434;dbname=symfony_app_db', 'postgres', 'postgrespassword');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

            return new JsonResponse(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }




}