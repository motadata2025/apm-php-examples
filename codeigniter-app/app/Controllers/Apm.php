<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Shared\Utils\Logger;

class Apm extends Controller
{
    private $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance('CODEIGNITER', Logger::INFO);
    }

    public function testDatabases()
    {
        try {
            $this->logger->info('Testing database connections');
            $results = [];
            
            // Test CodeIgniter Database
            $db = \Config\Database::connect();
            $query = $db->query("SELECT 1 as test");
            $results['codeigniter_db'] = $query ? 'Connected' : 'Failed';
            
            // Test Shared Database Utilities
            require_once ROOTPATH . '../shared/utils/DatabaseConnection.php';
            $dbResults = \Shared\Utils\DatabaseConnection::testConnections();
            $results['shared_utilities'] = $dbResults;
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function demoCrud()
    {
        try {
            $results = [];
            
            // Test CodeIgniter Database CRUD
            $db = \Config\Database::connect();
            
            // Create
            $email = 'ci_' . time() . '_' . rand(1000, 9999) . '@example.com';
            $data = [
                'name' => 'CodeIgniter User',
                'email' => $email
            ];
            
            $db->table('users')->insert($data);
            $insertId = $db->insertID();
            $results['codeigniter_created'] = ['id' => $insertId, 'email' => $email];
            
            // Read
            $user = $db->table('users')->where('id', $insertId)->get()->getRowArray();
            $results['codeigniter_read'] = $user;
            
            // Test Shared Utilities CRUD
            require_once ROOTPATH . '../shared/utils/UserModel.php';
            $userModel = new \Shared\Utils\UserModel();
            $sharedResults = $userModel->demo();
            $results['shared_utilities'] = $sharedResults;
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function fetchApiData()
    {
        try {
            $results = [];
            
            // Test CodeIgniter HTTP Client
            $client = \Config\Services::curlrequest();
            $response = $client->get('https://jsonplaceholder.typicode.com/posts?_limit=3');
            $posts = json_decode($response->getBody(), true);
            
            $results['codeigniter_http'] = [
                'source' => 'CodeIgniter HTTP Client',
                'posts' => $posts
            ];
            
            // Test Shared API Client
            require_once ROOTPATH . '../shared/utils/ApiClient.php';
            $apiClient = new \Shared\Utils\ApiClient();
            $apiResults = $apiClient->testMultipleApis();
            $results['shared_api'] = $apiResults;
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function testQueue()
    {
        try {
            $results = [];
            
            // Test Shared Queue Manager
            require_once ROOTPATH . '../shared/utils/QueueManager.php';
            $queueManager = new \Shared\Utils\QueueManager();
            
            // Test queue operations
            $queueManager->enqueue('codeigniter_queue', ['message' => 'Hello from CodeIgniter', 'priority' => 1]);
            $queueManager->enqueue('codeigniter_queue', ['message' => 'Queue Test', 'priority' => 2]);
            $queueManager->enqueue('codeigniter_queue', ['message' => 'APM Demo', 'priority' => 3]);
            
            $queueLength = $queueManager->getQueueLength('codeigniter_queue');
            $peeked = $queueManager->peek('codeigniter_queue');
            $dequeued = $queueManager->dequeue('codeigniter_queue');
            $remaining = $queueManager->getAllItems('codeigniter_queue');
            
            $results['shared_queue'] = [
                'enqueued' => 3,
                'queue_length' => $queueLength,
                'peeked' => $peeked,
                'dequeued' => $dequeued,
                'remaining_items' => $remaining,
                'status' => 'success'
            ];
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function health()
    {
        return $this->response->setJSON([
            'status' => 'ok',
            'timestamp' => date('c'),
            'php_version' => phpversion(),
            'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'environment' => ENVIRONMENT
        ]);
    }
}
