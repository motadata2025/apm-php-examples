<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use SimplePhp\Config;
use SimplePhp\DB;
use SimplePhp\RedisQueue;

// Error handling
set_error_handler(function ($severity, $message, $file, $line) {
    $timestamp = date('Y-m-d_H-i-s');
    $logFile = dirname(__DIR__) . "/augment/logs/simple-php-error-{$timestamp}.log";
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Error: $message in $file on line $line\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($exception) {
    $timestamp = date('Y-m-d_H-i-s');
    $logFile = dirname(__DIR__) . "/augment/logs/simple-php-error-{$timestamp}.log";
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Exception: " . $exception->getMessage() . "\n";
    $logMessage .= "Stack trace:\n" . $exception->getTraceAsString() . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Internal server error', 'code' => 500]);
    exit;
});

// Initialize config
$config = Config::getInstance();

// Check for missing environment variables
$missingVars = $config->validateRequiredVars();
if (!empty($missingVars)) {
    foreach ($missingVars as $var) {
        $logFile = dirname(__DIR__) . "/augment/logs/env-missing-{$var}.log";
        file_put_contents($logFile, "Missing required environment variable: {$var}\n");
    }
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'error' => 'Missing required environment variables: ' . implode(', ', $missingVars),
        'code' => 500
    ]);
    exit;
}

// Route handling
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API routes
if (str_starts_with($requestUri, '/api/')) {
    header('Content-Type: application/json');
    
    switch ($requestUri) {
        case '/api/php-version':
            echo json_encode([
                'ok' => true,
                'php_version' => PHP_VERSION,
                'major_minor' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
            ]);
            break;
            
        case '/api/config':
            echo json_encode([
                'ok' => true,
                'external_api_url' => $config->getExternalApiUrl()
            ]);
            break;
            
        case '/api/external':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Method not allowed', 'code' => 405]);
                break;
            }
            
            $url = $config->getExternalApiUrl();
            $timeout = $config->getHttpTimeout();
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => $timeout,
                    'header' => 'User-Agent: SimplePhp/1.0'
                ]
            ]);
            
            $result = @file_get_contents($url, false, $context);
            
            if ($result === false) {
                // Retry once after 2s
                sleep(2);
                $result = @file_get_contents($url, false, $context);
                
                if ($result === false) {
                    echo json_encode(['ok' => false, 'error' => 'Failed to fetch external API', 'code' => 500]);
                    break;
                }
            }
            
            $data = json_decode($result, true);
            echo json_encode(['ok' => true, 'data' => $data]);
            break;
            
        case '/api/db/check':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Method not allowed', 'code' => 405]);
                break;
            }
            
            $db = new DB();
            $mysqlResult = $db->testConnection('mysql');
            $postgresResult = $db->testConnection('postgres');
            
            echo json_encode([
                'ok' => true,
                'mysql' => $mysqlResult,
                'postgres' => $postgresResult
            ]);
            break;
            
        case '/api/db/crud':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Method not allowed', 'code' => 405]);
                break;
            }
            
            $db = new DB();
            $mysqlResult = $db->performCRUD('mysql');
            $postgresResult = $db->performCRUD('postgres');
            
            echo json_encode([
                'ok' => true,
                'mysql' => $mysqlResult,
                'postgres' => $postgresResult
            ]);
            break;
            
        case '/api/redis/insert-multiple':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Method not allowed', 'code' => 405]);
                break;
            }
            
            $count = (int) ($_GET['count'] ?? 3);
            $values = [];
            for ($i = 0; $i < $count; $i++) {
                $values[] = 'msg_' . time() . '_' . bin2hex(random_bytes(6));
            }
            
            $redis = new RedisQueue();
            $result = $redis->pushMultiple($values);
            echo json_encode($result);
            break;
            
        case '/api/redis/insert-single':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Method not allowed', 'code' => 405]);
                break;
            }
            
            $value = 'single_' . time() . '_' . bin2hex(random_bytes(6));
            $redis = new RedisQueue();
            $result = $redis->pushSingle($value);
            echo json_encode($result);
            break;
            
        case '/api/redis/read-single':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Method not allowed', 'code' => 405]);
                break;
            }
            
            $redis = new RedisQueue();
            $result = $redis->popSingle();
            echo json_encode($result);
            break;
            
        case '/api/redis/clear':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Method not allowed', 'code' => 405]);
                break;
            }
            
            $redis = new RedisQueue();
            $result = $redis->clear();
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'API endpoint not found', 'code' => 404]);
            break;
    }
    exit;
}

// Serve HTML for root path
if ($requestUri === '/' && $requestMethod === 'GET') {
    $externalApiUrl = htmlspecialchars($config->getExternalApiUrl(), ENT_QUOTES, 'UTF-8');
    $phpVersion = PHP_VERSION;
    
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple PHP - APM Example</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Simple PHP - APM Example</h1>
        </header>
        
        <main class="grid-container">
            <div class="card">
                <h2>Application Info</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Application Type:</label>
                        <span>Simple PHP</span>
                    </div>
                    <div class="info-item">
                        <label>PHP Version:</label>
                        <span id="php-version">{$phpVersion}</span>
                    </div>
                    <div class="info-item">
                        <label>Web Server:</label>
                        <span>php_cli</span>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>External API & Database</h2>
                <div class="button-group">
                    <button id="external-api-btn" class="btn btn-primary">External API</button>
                    <button id="db-check-btn" class="btn btn-secondary">DB Connection Check</button>
                    <button id="db-crud-btn" class="btn btn-secondary">DB Calls</button>
                </div>
                <div id="api-db-results" class="results"></div>
            </div>
            
            <div class="card">
                <h2>Redis Queue</h2>
                <div class="button-group">
                    <button id="redis-insert-3-btn" class="btn btn-accent">Insert 3</button>
                    <button id="redis-insert-1-btn" class="btn btn-accent">Insert 1</button>
                    <button id="redis-read-1-btn" class="btn btn-accent">Read 1</button>
                    <button id="redis-clear-btn" class="btn btn-danger">Clear Queue</button>
                </div>
                <div id="redis-results" class="results"></div>
            </div>
        </main>
    </div>
    
    <script>
        window.__CONFIG__ = {
            externalApiUrl: '{$externalApiUrl}'
        };
    </script>
    <script src="/assets/app.js"></script>
</body>
</html>
HTML;
    exit;
}

// 404 for other routes
http_response_code(404);
echo '404 - Not Found';
