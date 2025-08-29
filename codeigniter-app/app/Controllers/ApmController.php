<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use Shared\Utils\DatabaseConnection;
use Shared\Utils\UserModel;
use Shared\Utils\ApiClient;
use Shared\Utils\QueueManager;
use Exception;

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
     * Display the main APM dashboard
     */
    public function index()
    {
        $phpVersion = phpversion();
        $ciVersion = \CodeIgniter\CodeIgniter::CI_VERSION;
        $environment = ENVIRONMENT;

        $data = [
            'phpVersion' => $phpVersion,
            'ciVersion' => $ciVersion,
            'environment' => $environment,
        ];

        return view('apm/dashboard', $data);
    }

    /**
     * Test database connections
     */
    public function testDatabases()
    {
        try {
            $results = [];

            // Test CodeIgniter database connections
            try {
                $db = \Config\Database::connect();
                $db->query('SELECT 1');
                $results['codeigniter_mysql'] = 'Connected';
            } catch (Exception $e) {
                $results['codeigniter_mysql'] = 'Failed: ' . $e->getMessage();
            }

            // Test PostgreSQL connection
            try {
                $dbPostgres = \Config\Database::connect('postgres');
                $dbPostgres->query('SELECT 1');
                $results['codeigniter_postgres'] = 'Connected';
            } catch (Exception $e) {
                $results['codeigniter_postgres'] = 'Failed: ' . $e->getMessage();
            }

            // Test shared database utilities
            try {
                $sharedResults = DatabaseConnection::testConnections();
                $results = array_merge($results, $sharedResults);
            } catch (Exception $e) {
                $results['shared_utilities'] = 'Failed: ' . $e->getMessage();
            }

            log_message('info', 'Database connections tested', $results);

            return $this->response->setJSON(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            log_message('error', 'Database test failed: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Demo CRUD operations using CodeIgniter Active Record and shared utilities
     */
    public function demoCrud()
    {
        try {
            $results = [];

            // Demonstrate CodeIgniter Active Record operations
            try {
                $db = \Config\Database::connect();

                // Create a user using Active Record
                $email = DatabaseConnection::randomEmail('CodeIgniter');
                $name = 'CodeIgniter User';

                $userData = [
                    'email' => $email,
                    'name' => $name,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $db->table('users')->insert($userData);
                $userId = $db->insertID();

                $results['active_record_created'] = [
                    'id' => $userId,
                    'email' => $email,
                    'name' => $name
                ];

                // Read users using Active Record
                $users = $db->table('users')
                    ->orderBy('created_at', 'DESC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();

                $results['active_record_users'] = $users;

            } catch (Exception $e) {
                $results['active_record_error'] = $e->getMessage();
            }

            // Also demonstrate shared utilities
            try {
                $userModel = new UserModel();
                $sharedResults = $userModel->demo();
                $results['shared_utilities'] = $sharedResults;
            } catch (Exception $e) {
                $results['shared_utilities'] = ['error' => $e->getMessage()];
            }

            log_message('info', 'CRUD operations completed', $results);

            return $this->response->setJSON(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            log_message('error', 'CRUD operations failed: ' . $e->getMessage());
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

            // Using shared API client
            try {
                $apiClient = new ApiClient();
                $sharedResults = $apiClient->testMultipleApis();
                $results['shared_client'] = $sharedResults;
            } catch (Exception $e) {
                $results['shared_client'] = ['error' => $e->getMessage()];
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
}