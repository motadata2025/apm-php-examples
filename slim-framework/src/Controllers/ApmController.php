<?php

namespace App\Controllers;

use GuzzleHttp\Client;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shared\Utils\DatabaseConnection;
use Shared\Utils\UserModel;
use Shared\Utils\ApiClient;
use Shared\Utils\QueueManager;
use Slim\Views\Twig;
use Exception;

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
     * Display the main APM dashboard
     */
    public function dashboard(Request $request, Response $response): Response
    {
        $phpVersion = phpversion();
        $slimVersion = '4.x'; // Slim doesn't have a version constant
        $environment = $_ENV['APP_ENV'] ?? 'development';

        return $this->view->render($response, 'dashboard.html.twig', [
            'phpVersion' => $phpVersion,
            'slimVersion' => $slimVersion,
            'environment' => $environment,
        ]);
    }

    /**
     * Test database connections
     */
    public function testDatabases(Request $request, Response $response): Response
    {
        try {
            $results = [];

            // Test MySQL connection using PDO
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $_ENV['DB_HOST'],
                    $_ENV['DB_PORT'],
                    $_ENV['DB_NAME']
                );
                $pdo = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
                $pdo->query('SELECT 1');
                $results['mysql_pdo'] = 'Connected';
            } catch (Exception $e) {
                $results['mysql_pdo'] = 'Failed: ' . $e->getMessage();
            }

            // Test PostgreSQL connection using PDO
            try {
                $dsn = sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $_ENV['DB_POSTGRES_HOST'],
                    $_ENV['DB_POSTGRES_PORT'],
                    $_ENV['DB_POSTGRES_NAME']
                );
                $pdo = new \PDO($dsn, $_ENV['DB_POSTGRES_USER'], $_ENV['DB_POSTGRES_PASS']);
                $pdo->query('SELECT 1');
                $results['postgres_pdo'] = 'Connected';
            } catch (Exception $e) {
                $results['postgres_pdo'] = 'Failed: ' . $e->getMessage();
            }

            // Test shared database utilities
            try {
                $sharedResults = DatabaseConnection::testConnections();
                $results = array_merge($results, $sharedResults);
            } catch (Exception $e) {
                $results['shared_utilities'] = 'Failed: ' . $e->getMessage();
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
     * Demo CRUD operations using PDO and shared utilities
     */
    public function demoCrud(Request $request, Response $response): Response
    {
        try {
            $results = [];

            // Demonstrate PDO operations
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $_ENV['DB_HOST'],
                    $_ENV['DB_PORT'],
                    $_ENV['DB_NAME']
                );
                $pdo = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);

                // Create a user using PDO
                $email = DatabaseConnection::randomEmail('Slim');
                $name = 'Slim User';

                $stmt = $pdo->prepare('INSERT INTO users (email, name, created_at) VALUES (?, ?, NOW())');
                $stmt->execute([$email, $name]);
                $userId = $pdo->lastInsertId();

                $results['pdo_created'] = [
                    'id' => $userId,
                    'email' => $email,
                    'name' => $name
                ];

                // Read users using PDO
                $stmt = $pdo->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 5');
                $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                $results['pdo_users'] = $users;

            } catch (Exception $e) {
                $results['pdo_error'] = $e->getMessage();
            }

            // Also demonstrate shared utilities
            try {
                $userModel = new UserModel();
                $sharedResults = $userModel->demo();
                $results['shared_utilities'] = $sharedResults;
            } catch (Exception $e) {
                $results['shared_utilities'] = ['error' => $e->getMessage()];
            }

            $this->logger->info('CRUD operations completed', $results);

            $response->getBody()->write(json_encode(['success' => true, 'data' => $results]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('CRUD operations failed', ['error' => $e->getMessage()]);
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

            // Using shared API client
            try {
                $apiClient = new ApiClient();
                $sharedResults = $apiClient->testMultipleApis();
                $results['shared_client'] = $sharedResults;
            } catch (Exception $e) {
                $results['shared_client'] = ['error' => $e->getMessage()];
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

            // Using shared queue manager
            try {
                $queueManager = new QueueManager();
                $sharedResults = $queueManager->demo();
                $results['shared_queue'] = $sharedResults;

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
                $results['shared_queue'] = ['error' => $e->getMessage()];
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
            $parsedBody = $request->getParsedBody();
            $data = json_decode($parsedBody['data'] ?? '{}', true);

            $queueManager = new QueueManager();
            $result = $queueManager->enqueue('slim_user_queue', $data);

            $this->logger->info('Data added to queue', ['data' => $data, 'result' => $result]);

            $response->getBody()->write(json_encode([
                'success' => $result,
                'message' => 'Data added to Slim queue successfully'
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
            'environment' => $_ENV['APP_ENV'] ?? 'development'
        ];

        $this->logger->info('Health check requested', $healthData);

        $response->getBody()->write(json_encode($healthData));
        return $response->withHeader('Content-Type', 'application/json');
    }
}