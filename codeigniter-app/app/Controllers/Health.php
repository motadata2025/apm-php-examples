<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Health extends ResourceController
{
    protected $format = 'json';
    
    public function index()
    {
        $startTime = microtime(true);
        
        $health = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'application' => 'codeigniter-app',
            'version' => '1.0.0',
            'environment' => ENVIRONMENT,
            'services' => [],
            'metrics' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]
        ];
        
        // Test database connection
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');
            
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
            $redis_host = $_ENV['REDIS_HOST'] ?? 'localhost';
            $redis_port = (int)($_ENV['REDIS_PORT'] ?? 6379);
            
            $redis = new \Redis();
            $redis->connect($redis_host, $redis_port, 2);
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
        
        return $this->respond($health, $health['status'] === 'ok' ? 200 : 503);
    }
}
