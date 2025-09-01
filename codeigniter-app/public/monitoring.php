<?php
// Production Monitoring Endpoints
// For load balancers and monitoring systems

header('Content-Type: application/json');

$endpoint = $_GET['endpoint'] ?? 'status';

switch ($endpoint) {
    case 'health':
        // Detailed health check
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'uptime' => $_SERVER['REQUEST_TIME'] - $_SERVER['REQUEST_TIME_FLOAT'],
            'load_average' => sys_getloadavg(),
            'services' => []
        ];
        
        // Check Redis (CodeIgniter port: 6383)
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6383);
            $redis->ping();
            $health['services']['redis'] = 'healthy';
            $redis->close();
        } catch (Exception $e) {
            $health['services']['redis'] = 'unhealthy';
        }

        // Check MySQL (CodeIgniter port: 3310)
        try {
            $pdo = new PDO('mysql:host=127.0.0.1;port=3310;dbname=codeigniter_app_db', 'root', 'rootpassword');
            $pdo->query('SELECT 1');
            $health['services']['mysql'] = 'healthy';
        } catch (Exception $e) {
            $health['services']['mysql'] = 'unhealthy';
        }

        // Check PostgreSQL (CodeIgniter port: 5436)
        try {
            $pdo = new PDO('pgsql:host=127.0.0.1;port=5436;dbname=codeigniter_app_db', 'postgres', 'postgrespassword');
            $pdo->query('SELECT 1');
            $health['services']['postgres'] = 'healthy';
        } catch (Exception $e) {
            $health['services']['postgres'] = 'unhealthy';
        }
        
        echo json_encode($health);
        break;
        
    case 'metrics':
        // Performance metrics for monitoring
        $metrics = [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => function_exists('opcache_get_status'),
            'request_time' => $_SERVER['REQUEST_TIME_FLOAT'],
            'server_load' => sys_getloadavg()[0]
        ];
        
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            $metrics['opcache_hit_rate'] = round($opcache['opcache_statistics']['opcache_hit_rate'], 2);
            $metrics['opcache_memory_usage'] = round($opcache['memory_usage']['used_memory'] / 1024 / 1024, 2);
        }
        
        echo json_encode($metrics);
        break;
        
    case 'ready':
        // Simple readiness check for load balancers
        echo json_encode(['status' => 'ready', 'timestamp' => time()]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Unknown endpoint']);
}
