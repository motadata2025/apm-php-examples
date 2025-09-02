<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$startTime = microtime(true);

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'application' => 'slim-framework',
    'version' => '1.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'services' => [],
    'metrics' => [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
    ]
];

// Test database connection
try {
    $mysql_host = $_ENV['MYSQL_HOST'] ?? 'localhost';
    $mysql_port = $_ENV['MYSQL_PORT'] ?? '3306';
    $mysql_db = $_ENV['MYSQL_DATABASE'] ?? 'app_db';
    $mysql_user = $_ENV['MYSQL_USERNAME'] ?? 'root';
    $mysql_pass = $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';
    
    $pdo = new PDO(
        "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_db}",
        $mysql_user,
        $mysql_pass,
        [PDO::ATTR_TIMEOUT => 2]
    );
    
    $health['services']['mysql'] = [
        'status' => 'healthy',
        'response_time' => microtime(true) - $startTime
    ];
} catch (Exception $e) {
    $health['services']['mysql'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

// Test Redis connection
try {
    $redis_host = $_ENV['REDIS_HOST'] ?? 'localhost';
    $redis_port = (int)($_ENV['REDIS_PORT'] ?? 6379);
    
    $redis = new Redis();
    $redis->connect($redis_host, $redis_port, 2);
    $redis->ping();
    
    $health['services']['redis'] = [
        'status' => 'healthy',
        'response_time' => microtime(true) - $startTime
    ];
} catch (Exception $e) {
    $health['services']['redis'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

$health['response_time'] = microtime(true) - $startTime;

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
