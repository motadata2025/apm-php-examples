<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;
use Predis\Client as RedisClient;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'health_check', methods: ['GET'])]
    public function healthCheck(Connection $connection): JsonResponse
    {
        $startTime = microtime(true);
        
        $health = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'application' => 'symfony-app',
            'version' => '1.0.0',
            'environment' => $this->getParameter('kernel.environment'),
            'services' => [],
            'metrics' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]
        ];
        
        // Test database connection
        try {
            $connection->executeQuery('SELECT 1');
            $health['services']['database'] = [
                'status' => 'healthy',
                'response_time' => microtime(true) - $startTime
            ];
        } catch (\Exception $e) {
            $health['services']['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }
        
        // Test Redis connection
        try {
            $redis = new RedisClient([
                'scheme' => 'tcp',
                'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
                'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
            ]);
            $redis->ping();
            
            $health['services']['redis'] = [
                'status' => 'healthy',
                'response_time' => microtime(true) - $startTime
            ];
        } catch (\Exception $e) {
            $health['services']['redis'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }
        
        $health['response_time'] = microtime(true) - $startTime;
        
        return new JsonResponse($health, $health['status'] === 'ok' ? 200 : 503);
    }
}
