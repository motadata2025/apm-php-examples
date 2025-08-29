<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use SimplePhp\Lib\DatabaseConnection;
use SimplePhp\Lib\UserModel;
use SimplePhp\Lib\ApiClient;
use SimplePhp\Lib\QueueManager;
use SimplePhp\Lib\Logger;

// Get the request URI and method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle different routes
switch ($requestUri) {
    case '/health':
        handleHealthCheck();
        break;

    case '/api/test':
        if ($requestMethod === 'GET') {
            handleApiTest();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;

    case '/':
    case '/index.php':
        // Handle AJAX requests for the main page
        if ($requestMethod === 'POST' && isset($_POST['action'])) {
            handleAjaxRequest();
        } else {
            // Serve the main HTML page
            serveMainPage();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        break;
}

// Health check endpoint
function handleHealthCheck() {
    header('Content-Type: application/json');

    $health = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'php_version' => phpversion(),
        'memory_usage' => memory_get_usage(true),
        'uptime' => getUptime(),
        'services' => checkServices()
    ];

    echo json_encode($health, JSON_PRETTY_PRINT);
    exit;
}

// API test endpoint
function handleApiTest() {
    header('Content-Type: application/json');

    try {
        $apiClient = new ApiClient();
        $results = $apiClient->testMultipleApis();
        echo json_encode(['success' => true, 'data' => $results]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get system uptime
function getUptime() {
    if (file_exists('/proc/uptime')) {
        $uptime = file_get_contents('/proc/uptime');
        $uptime = floatval(explode(' ', $uptime)[0]);
        return round($uptime);
    }
    return null;
}

// Check service health
function checkServices() {
    $services = [];

    try {
        // Check Redis
        $redis = DatabaseConnection::getRedisConnection();
        $services['redis'] = $redis->ping() ? 'healthy' : 'unhealthy';
    } catch (Exception $e) {
        $services['redis'] = 'unhealthy';
    }

    try {
        // Check MySQL
        $mysql = DatabaseConnection::getMysqlConnection();
        $services['mysql'] = $mysql ? 'healthy' : 'unhealthy';
    } catch (Exception $e) {
        $services['mysql'] = 'unhealthy';
    }

    try {
        // Check PostgreSQL
        $postgres = DatabaseConnection::getPostgresConnection();
        $services['postgres'] = $postgres ? 'healthy' : 'unhealthy';
    } catch (Exception $e) {
        $services['postgres'] = 'unhealthy';
    }

    return $services;
}

// Handle AJAX requests
function handleAjaxRequest() {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'test_databases':
                $results = DatabaseConnection::testConnections();
                echo json_encode(['success' => true, 'data' => $results]);
                break;

            case 'demo_crud':
                $userModel = new UserModel();
                $results = $userModel->demo();
                echo json_encode(['success' => true, 'data' => $results]);
                break;

            case 'fetch_api_data':
                $apiClient = new ApiClient();
                $results = $apiClient->testMultipleApis();
                echo json_encode(['success' => true, 'data' => $results]);
                break;

            case 'test_queue':
                $queueManager = new QueueManager('demo_queue');
                $results = $queueManager->demo();
                echo json_encode(['success' => true, 'data' => $results]);
                break;

            case 'add_queue_data':
                $queueManager = new QueueManager('demo_queue');
                $data = json_decode($_POST['data'] ?? '{}', true);
                $result = $queueManager->enqueue('demo_queue', $data);
                echo json_encode(['success' => $result, 'message' => 'Data added to queue']);
                break;

            case 'read_queue_data':
                $queueManager = new QueueManager('demo_queue');
                $data = $queueManager->getAllQueueData('demo_queue');
                echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
                break;

            case 'clear_queue':
                $queueManager = new QueueManager('demo_queue');
                $result = $queueManager->clearQueue('demo_queue');
                echo json_encode(['success' => $result, 'message' => 'Queue cleared']);
                break;

            case 'generate_random_data':
                $queueManager = new QueueManager();
                $randomData = $queueManager->generateRandomData();
                echo json_encode(['success' => true, 'data' => $randomData]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Function to detect the actual web server and PHP handler
function detectWebServerType() {
    // Check if running under PHP built-in server
    if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'PHP') !== false) {
        return 'PHP Built-in Server';
    }

    // Check for Apache
    if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
        // Determine if using mod_php or PHP-FPM
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();

            // Check for any PHP module (mod_php)
            foreach ($modules as $module) {
                if (strpos($module, 'php') !== false && strpos($module, 'mod_') === 0) {
                    return 'Apache with mod_php';
                }
            }
        }

        // Check for PHP-FPM by looking at the SAPI
        if (php_sapi_name() === 'fpm-fcgi') {
            return 'Apache with PHP-FPM';
        }

        // Check for CGI/FastCGI indicators
        if (isset($_SERVER['GATEWAY_INTERFACE']) && strpos($_SERVER['GATEWAY_INTERFACE'], 'CGI') !== false) {
            return 'Apache with PHP-FPM';
        }

        // Default to PHP-FPM for Apache if we can't determine
        return 'Apache with PHP-FPM';
    }

    // Check for Nginx
    if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false) {
        return 'Nginx with PHP-FPM';
    }

    // Check for other web servers
    if (isset($_SERVER['SERVER_SOFTWARE'])) {
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'];
        if (strpos($serverSoftware, 'nginx') !== false) {
            return 'Nginx with PHP-FPM';
        } elseif (strpos($serverSoftware, 'Microsoft-IIS') !== false) {
            return 'IIS with PHP';
        } elseif (strpos($serverSoftware, 'LiteSpeed') !== false) {
            return 'LiteSpeed with PHP';
        }
    }

    // Check SAPI as fallback
    $sapi = php_sapi_name();
    switch ($sapi) {
        case 'apache2handler':
            return 'Apache with mod_php';
        case 'fpm-fcgi':
            return 'PHP-FPM (Unknown Web Server)';
        case 'cgi-fcgi':
            return 'CGI/FastCGI (Unknown Web Server)';
        case 'cli-server':
            return 'PHP Built-in Server';
        default:
            return 'Unknown Web Server (' . $sapi . ')';
    }
}

// Serve the main HTML page
function serveMainPage() {
    $phpVersion = phpversion();
    $framework = 'Vanilla PHP';

    // Detect the actual web server and PHP handler
    $deploymentType = detectWebServerType();

    // Override with environment variable if set (for testing/debugging)
    if (isset($_ENV['DEPLOYMENT_DESC'])) {
        $deploymentType = $_ENV['DEPLOYMENT_DESC'];
    }

    // Generate random data for queue operations
    $queueManager = new QueueManager();
    $randomData = $queueManager->generateRandomData();

    include __DIR__ . '/main-page.php';
}
