<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Utils\DatabaseConnection;
use Shared\Utils\UserModel;
use Shared\Utils\ApiClient;
use Shared\Utils\QueueManager;
use Shared\Utils\Logger;
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

class ApmController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private HttpClientInterface $httpClient;
    private MessageBusInterface $messageBus;
    private Logger $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->messageBus = $messageBus;
        $this->logger = Logger::getInstance('SYMFONY', Logger::INFO);
    }

    #[Route('/', name: 'apm_dashboard')]
    public function dashboard(): Response
    {
        $phpVersion = phpversion();
        $symfonyVersion = \Symfony\Component\HttpKernel\Kernel::VERSION;
        $environment = $this->getParameter('kernel.environment');

        return $this->render('apm/dashboard.html.twig', [
            'phpVersion' => $phpVersion,
            'symfonyVersion' => $symfonyVersion,
            'environment' => $environment,
        ]);
    }

    #[Route('/test-databases', name: 'apm_test_databases', methods: ['POST'])]
    public function testDatabases(): JsonResponse
    {
        try {
            $results = [];

            // Test Doctrine DBAL connection
            try {
                $connection = $this->entityManager->getConnection();
                $connection->executeQuery('SELECT 1');
                $results['doctrine'] = 'Connected';
            } catch (Exception $e) {
                $results['doctrine'] = 'Failed: ' . $e->getMessage();
            }

            // Test shared database utilities
            try {
                $sharedResults = DatabaseConnection::testConnections();
                $results = array_merge($results, $sharedResults);
            } catch (Exception $e) {
                $results['shared_utilities'] = 'Failed: ' . $e->getMessage();
            }

            return $this->json(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/demo-crud', name: 'apm_demo_crud', methods: ['POST'])]
    public function demoCrud(): JsonResponse
    {
        try {
            $results = [];

            // Demonstrate Doctrine ORM operations
            try {
                $user = new User();
                $user->setEmail(DatabaseConnection::randomEmail('Symfony'));
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

            // Also demonstrate shared utilities
            try {
                $userModel = new UserModel();
                $sharedResults = $userModel->demo();
                $results['shared_utilities'] = $sharedResults;
            } catch (Exception $e) {
                $results['shared_utilities'] = ['error' => $e->getMessage()];
            }

            return $this->json(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/fetch-api-data', name: 'apm_fetch_api_data', methods: ['POST'])]
    public function fetchApiData(): JsonResponse
    {
        try {
            $results = [];

            // Using Symfony HTTP Client
            try {
                $response = $this->httpClient->request('GET', 'https://jsonplaceholder.typicode.com/posts');
                $posts = array_slice($response->toArray(), 0, 3);

                $results['symfony_http_client'] = [
                    'source' => 'Symfony HTTP Client',
                    'posts' => $posts
                ];
            } catch (Exception $e) {
                $results['symfony_http_client'] = ['error' => $e->getMessage()];
            }

            // Using shared API client
            try {
                $apiClient = new ApiClient();
                $sharedResults = $apiClient->testMultipleApis();
                $results['shared_client'] = $sharedResults;
            } catch (Exception $e) {
                $results['shared_client'] = ['error' => $e->getMessage()];
            }

            return $this->json(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/test-queue', name: 'apm_test_queue', methods: ['POST'])]
    public function testQueue(): JsonResponse
    {
        try {
            $results = [];

            // Using Symfony Messenger
            try {
                $message = new ProcessDataMessage(['message' => 'Hello from Symfony Messenger']);
                $this->messageBus->dispatch($message);

                $results['symfony_messenger'] = [
                    'status' => 'Message dispatched successfully',
                    'message' => 'ProcessDataMessage sent to queue'
                ];
            } catch (Exception $e) {
                $results['symfony_messenger'] = ['error' => $e->getMessage()];
            }

            // Using shared queue manager
            try {
                $queueManager = new QueueManager();
                $sharedResults = $queueManager->demo();
                $results['shared_queue'] = $sharedResults;
            } catch (Exception $e) {
                $results['shared_queue'] = ['error' => $e->getMessage()];
            }

            return $this->json(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/add-queue-data', name: 'apm_add_queue_data', methods: ['POST'])]
    public function addQueueData(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->request->get('data', '{}'), true);

            // Using Symfony Messenger
            $message = new ProcessDataMessage($data);
            $this->messageBus->dispatch($message);

            return $this->json([
                'success' => true,
                'message' => 'Data added to Symfony Messenger queue successfully'
            ]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/read-queue-data', name: 'apm_read_queue_data', methods: ['POST'])]
    public function readQueueData(): JsonResponse
    {
        try {
            $queueManager = new QueueManager();
            $data = $queueManager->dequeue('symfony_queue');

            return $this->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/clear-queue', name: 'apm_clear_queue', methods: ['POST'])]
    public function clearQueue(): JsonResponse
    {
        try {
            $queueManager = new QueueManager();
            $result = $queueManager->clearQueue('symfony_queue');

            return $this->json([
                'success' => $result,
                'message' => 'Symfony queue cleared successfully'
            ]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[Route('/health', name: 'apm_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'timestamp' => (new \DateTime())->format('c'),
            'php_version' => phpversion(),
            'symfony_version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
            'environment' => $this->getParameter('kernel.environment')
        ]);
    }
}