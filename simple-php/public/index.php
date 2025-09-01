<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$envFiles = [
    __DIR__ . '/../.env',
    __DIR__ . '/../config/app.env'
];

foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                $_ENV[$name] = $value;
            }
        }
        break; // Use first found file
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

            case 'create_tables':
                $results = DatabaseConnection::createTables();
                echo json_encode(['success' => true, 'data' => $results]);
                break;

            case 'debug_env':
                $envFiles = [
                    __DIR__ . '/../config/app.env',
                    __DIR__ . '/../.env'
                ];
                $fileStatus = [];
                foreach ($envFiles as $file) {
                    $fileStatus[$file] = file_exists($file) ? 'exists' : 'not found';
                }

                $envVars = [
                    'MYSQL_HOST' => $_ENV['MYSQL_HOST'] ?? 'not set',
                    'MYSQL_PORT' => $_ENV['MYSQL_PORT'] ?? 'not set',
                    'MYSQL_DATABASE' => $_ENV['MYSQL_DATABASE'] ?? 'not set',
                    'POSTGRES_HOST' => $_ENV['POSTGRES_HOST'] ?? 'not set',
                    'POSTGRES_PORT' => $_ENV['POSTGRES_PORT'] ?? 'not set',
                    'REDIS_HOST' => $_ENV['REDIS_HOST'] ?? 'not set',
                    'REDIS_PORT' => $_ENV['REDIS_PORT'] ?? 'not set'
                ];
                echo json_encode(['success' => true, 'env' => $envVars, 'files' => $fileStatus]);
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
                $queueName = getApplicationQueueName();
                $queueManager = new QueueManager($queueName);
                $results = $queueManager->demo();
                echo json_encode(['success' => true, 'data' => $results, 'queue_name' => $queueName]);
                break;

            case 'add_queue_data':
                $queueName = getApplicationQueueName();

                // Generate batch of 3 randomized records
                $batchData = [];
                for ($i = 0; $i < 3; $i++) {
                    $batchData[] = [
                        'id' => rand(1000, 9999),
                        'message' => 'Simple PHP queue message ' . rand(100, 999),
                        'timestamp' => date('Y-m-d H:i:s'),
                        'priority' => rand(1, 5),
                        'type' => 'simple_php_batch',
                        'batch_index' => $i + 1,
                        'queue_name' => $queueName,
                        'ttl' => 60 // 1 minute TTL (non-modifiable)
                    ];
                }

                $queueManager = new QueueManager($queueName);
                $successCount = 0;

                // Add each item to queue
                foreach ($batchData as $data) {
                    $result = $queueManager->enqueue($queueName, $data);
                    if ($result) $successCount++;
                }

                echo json_encode([
                    'success' => $successCount > 0,
                    'message' => "Batch of {$successCount}/3 items added to queue successfully",
                    'queue_name' => $queueName,
                    'batch_data' => $batchData,
                    'total_added' => $successCount,
                    'message_ttl' => '1 minute (non-modifiable)'
                ]);
                break;

            case 'read_queue_data':
                $queueName = getApplicationQueueName();
                $queueManager = new QueueManager($queueName);
                $data = $queueManager->getAllQueueData($queueName);
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'count' => count($data),
                    'queue_name' => $queueName
                ]);
                break;

            case 'clear_queue':
                $queueName = getApplicationQueueName();
                $queueManager = new QueueManager($queueName);
                $result = $queueManager->clearQueue($queueName);
                echo json_encode([
                    'success' => $result,
                    'message' => 'Queue cleared',
                    'queue_name' => $queueName
                ]);
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

/**
 * Generate application-specific queue name
 * Format: simple_php_{php_version}_{web_server}
 */
function getApplicationQueueName(): string
{
    $phpVersion = str_replace('.', '', phpversion()); // e.g., 84 for 8.4
    $webServer = getWebServerType();

    return sprintf('simple_php_%s_%s', $phpVersion, $webServer);
}

/**
 * Get web server type for queue naming
 */
function getWebServerType(): string
{
    $sapi = php_sapi_name();
    $server = $_SERVER['SERVER_SOFTWARE'] ?? '';

    if ($sapi === 'cli-server') {
        return 'php_builtin';
    } elseif (strpos($server, 'Apache') !== false) {
        if (function_exists('apache_get_modules') && in_array('mod_php', apache_get_modules())) {
            return 'apache_modphp';
        } else {
            return 'apache_fpm';
        }
    } elseif (strpos($server, 'nginx') !== false) {
        return 'nginx_fpm';
    } else {
        return 'unknown';
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
