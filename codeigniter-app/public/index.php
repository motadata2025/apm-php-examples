<?php

/**
 * CodeIgniter 4 Bootstrap for APM Demo
 * Minimal bootstrap to run the APM dashboard without full CodeIgniter installation
 */

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define paths
define('ROOTPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
define('PUBLICPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Load environment variables
function loadEnv($file) {
    if (!file_exists($file)) return;
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

loadEnv(ROOTPATH . '.env');

// Simple router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove query string and normalize
$uri = rtrim($uri, '/');
if (empty($uri)) $uri = '/';

// Include the controller
require_once APPPATH . 'Controllers/ApmController.php';

// Route handling
$controller = new ApmController();

try {
    switch (true) {
        case $uri === '/' && $method === 'GET':
            $controller->index();
            break;
            
        case $uri === '/api/external' && $method === 'POST':
            $controller->externalApi();
            break;
            
        case $uri === '/api/db/connection' && $method === 'POST':
            $controller->dbConnectionCheck();
            break;
            
        case $uri === '/api/db/crud' && $method === 'POST':
            $controller->dbCrud();
            break;
            
        case $uri === '/api/redis/insert-batch' && $method === 'POST':
            $controller->redisInsertBatch();
            break;
            
        case $uri === '/api/redis/insert-one' && $method === 'POST':
            $controller->redisInsertOne();
            break;
            
        case $uri === '/api/redis/pop' && $method === 'POST':
            $controller->redisPop();
            break;
            
        case $uri === '/api/redis/clear' && $method === 'POST':
            $controller->redisClear();
            break;
            
        default:
            // Serve static files
            if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico)$/', $uri)) {
                $file = PUBLICPATH . ltrim($uri, '/');
                if (file_exists($file)) {
                    $mimeTypes = [
                        'css' => 'text/css',
                        'js' => 'application/javascript',
                        'png' => 'image/png',
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'gif' => 'image/gif',
                        'ico' => 'image/x-icon'
                    ];
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if (isset($mimeTypes[$ext])) {
                        header('Content-Type: ' . $mimeTypes[$ext]);
                    }
                    readfile($file);
                    exit;
                }
            }
            
            http_response_code(404);
            echo json_encode(['error' => 'Not Found', 'uri' => $uri]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error', 'message' => $e->getMessage()]);
}
