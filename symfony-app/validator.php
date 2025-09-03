<?php
/**
 * Symfony Application Validator
 * Tests database connectivity, Redis connectivity, and HTTP functionality
 */

declare(strict_types=1);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timeout for the entire script
set_time_limit(120);

class SymfonyAppValidator
{
    private array $config = [];
    private array $results = [];
    private float $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->loadEnvironment();
    }

    private function loadEnvironment(): void
    {
        // Try to load .env file
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    [$key, $value] = explode('=', $line, 2);
                    $this->config[trim($key)] = trim($value);
                }
            }
        }

        // Fallback to environment variables
        $this->config = array_merge([
            'DB_MYSQL_HOST' => $_ENV['DB_MYSQL_HOST'] ?? '127.0.0.1',
            'DB_MYSQL_PORT' => $_ENV['DB_MYSQL_PORT'] ?? '3307',
            'DB_MYSQL_DATABASE' => $_ENV['DB_MYSQL_DATABASE'] ?? 'simple_php_db',
            'DB_MYSQL_USERNAME' => $_ENV['DB_MYSQL_USERNAME'] ?? 'simple_php_user',
            'DB_MYSQL_PASSWORD' => $_ENV['DB_MYSQL_PASSWORD'] ?? 'simple_php_password',
            'DB_PGSQL_HOST' => $_ENV['DB_PGSQL_HOST'] ?? '127.0.0.1',
            'DB_PGSQL_PORT' => $_ENV['DB_PGSQL_PORT'] ?? '5433',
            'DB_PGSQL_DATABASE' => $_ENV['DB_PGSQL_DATABASE'] ?? 'simple_php_db',
            'DB_PGSQL_USERNAME' => $_ENV['DB_PGSQL_USERNAME'] ?? 'postgres',
            'DB_PGSQL_PASSWORD' => $_ENV['DB_PGSQL_PASSWORD'] ?? 'postgrespassword',
            'REDIS_HOST' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'REDIS_PORT' => $_ENV['REDIS_PORT'] ?? '6380',
            'REDIS_PASSWORD' => $_ENV['REDIS_PASSWORD'] ?? '',
            'HTTP_TIMEOUT' => $_ENV['HTTP_TIMEOUT'] ?? '20',
            'EXTERNAL_API_URL' => $_ENV['EXTERNAL_API_URL'] ?? 'https://httpbin.org/get',
        ], $this->config);
    }

    public function validateMySQL(): bool
    {
        $start = microtime(true);
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config['DB_MYSQL_HOST'],
                $this->config['DB_MYSQL_PORT'],
                $this->config['DB_MYSQL_DATABASE']
            );

            $pdo = new PDO($dsn, $this->config['DB_MYSQL_USERNAME'], $this->config['DB_MYSQL_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 20,
            ]);

            // Test table existence and basic operations
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            $tableExists = $stmt->rowCount() > 0;

            if ($tableExists) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $this->results['mysql'] = [
                    'success' => true,
                    'duration' => microtime(true) - $start,
                    'table_exists' => true,
                    'user_count' => $count,
                ];
            } else {
                $this->results['mysql'] = [
                    'success' => true,
                    'duration' => microtime(true) - $start,
                    'table_exists' => false,
                    'note' => 'users table not found, but connection successful',
                ];
            }

            return true;
        } catch (Exception $e) {
            $this->results['mysql'] = [
                'success' => false,
                'duration' => microtime(true) - $start,
                'error' => $e->getMessage(),
            ];
            return false;
        }
    }

    public function validatePostgreSQL(): bool
    {
        $start = microtime(true);
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $this->config['DB_PGSQL_HOST'],
                $this->config['DB_PGSQL_PORT'],
                $this->config['DB_PGSQL_DATABASE']
            );

            $pdo = new PDO($dsn, $this->config['DB_PGSQL_USERNAME'], $this->config['DB_PGSQL_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 20,
            ]);

            // Test table existence and basic operations
            $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'users')");
            $tableExists = $stmt->fetch(PDO::FETCH_COLUMN);

            if ($tableExists) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $this->results['postgresql'] = [
                    'success' => true,
                    'duration' => microtime(true) - $start,
                    'table_exists' => true,
                    'user_count' => $count,
                ];
            } else {
                $this->results['postgresql'] = [
                    'success' => true,
                    'duration' => microtime(true) - $start,
                    'table_exists' => false,
                    'note' => 'users table not found, but connection successful',
                ];
            }

            return true;
        } catch (Exception $e) {
            $this->results['postgresql'] = [
                'success' => false,
                'duration' => microtime(true) - $start,
                'error' => $e->getMessage(),
            ];
            return false;
        }
    }

    public function validateRedis(): bool
    {
        $start = microtime(true);
        try {
            // Try Redis extension first
            if (extension_loaded('redis')) {
                $redis = new Redis();
                $redis->connect($this->config['REDIS_HOST'], (int)$this->config['REDIS_PORT'], 20);
                
                if (!empty($this->config['REDIS_PASSWORD'])) {
                    $redis->auth($this->config['REDIS_PASSWORD']);
                }

                $testKey = 'apm_test_' . time();
                $testValue = 'test_value_' . rand(1000, 9999);
                
                $redis->set($testKey, $testValue);
                $retrieved = $redis->get($testKey);
                $redis->del($testKey);

                $this->results['redis'] = [
                    'success' => $retrieved === $testValue,
                    'duration' => microtime(true) - $start,
                    'client' => 'redis_extension',
                    'test_passed' => $retrieved === $testValue,
                ];

                return $retrieved === $testValue;
            }

            // Try Predis if Redis extension not available
            $composerAutoload = __DIR__ . '/vendor/autoload.php';
            if (file_exists($composerAutoload)) {
                require_once $composerAutoload;
                
                if (class_exists('Predis\Client')) {
                    $redis = new \Predis\Client([
                        'scheme' => 'tcp',
                        'host' => $this->config['REDIS_HOST'],
                        'port' => (int)$this->config['REDIS_PORT'],
                        'password' => $this->config['REDIS_PASSWORD'] ?: null,
                        'timeout' => 20,
                    ]);

                    $testKey = 'apm_test_' . time();
                    $testValue = 'test_value_' . rand(1000, 9999);
                    
                    $redis->set($testKey, $testValue);
                    $retrieved = $redis->get($testKey);
                    $redis->del($testKey);

                    $this->results['redis'] = [
                        'success' => $retrieved === $testValue,
                        'duration' => microtime(true) - $start,
                        'client' => 'predis',
                        'test_passed' => $retrieved === $testValue,
                    ];

                    return $retrieved === $testValue;
                }
            }

            $this->results['redis'] = [
                'success' => false,
                'duration' => microtime(true) - $start,
                'error' => 'redis-client-not-installed',
                'note' => 'Neither Redis extension nor Predis client available',
            ];
            return false;

        } catch (Exception $e) {
            $this->results['redis'] = [
                'success' => false,
                'duration' => microtime(true) - $start,
                'error' => $e->getMessage(),
            ];
            return false;
        }
    }

    public function validateHTTP(): bool
    {
        $start = microtime(true);
        try {
            $url = $this->config['EXTERNAL_API_URL'];
            $timeout = (int)$this->config['HTTP_TIMEOUT'];

            // Try cURL first
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_USERAGENT => 'APM-PHP-Validator/1.0',
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($response === false || !empty($error)) {
                    throw new Exception("cURL error: $error");
                }

                if ($httpCode !== 200) {
                    throw new Exception("HTTP error: $httpCode");
                }

                $decoded = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON response");
                }

                $this->results['http'] = [
                    'success' => true,
                    'duration' => microtime(true) - $start,
                    'method' => 'curl',
                    'http_code' => $httpCode,
                    'json_valid' => true,
                ];

                return true;
            }

            // Fallback to file_get_contents
            if (ini_get('allow_url_fopen')) {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => $timeout,
                        'user_agent' => 'APM-PHP-Validator/1.0',
                    ],
                ]);

                $response = file_get_contents($url, false, $context);
                if ($response === false) {
                    throw new Exception("file_get_contents failed");
                }

                $decoded = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON response");
                }

                $this->results['http'] = [
                    'success' => true,
                    'duration' => microtime(true) - $start,
                    'method' => 'file_get_contents',
                    'json_valid' => true,
                ];

                return true;
            }

            throw new Exception("No HTTP client available (cURL disabled, allow_url_fopen disabled)");

        } catch (Exception $e) {
            $this->results['http'] = [
                'success' => false,
                'duration' => microtime(true) - $start,
                'error' => $e->getMessage(),
            ];
            return false;
        }
    }

    public function run(): int
    {
        $errors = [];

        // Run all validations
        if (!$this->validateMySQL()) {
            $errors[] = "MySQL validation failed";
        }

        if (!$this->validatePostgreSQL()) {
            $errors[] = "PostgreSQL validation failed";
        }

        if (!$this->validateRedis()) {
            $errors[] = "Redis validation failed";
        }

        if (!$this->validateHTTP()) {
            $errors[] = "HTTP validation failed";
        }

        // Prepare final result
        $totalDuration = microtime(true) - $this->startTime;
        $result = [
            'app' => 'symfony-app',
            'php_version' => PHP_VERSION,
            'timestamp' => time(),
            'total_duration' => $totalDuration,
            'mysql_ok' => $this->results['mysql']['success'] ?? false,
            'pg_ok' => $this->results['postgresql']['success'] ?? false,
            'redis_ok' => $this->results['redis']['success'] ?? false,
            'http_ok' => $this->results['http']['success'] ?? false,
            'success' => empty($errors),
            'errors' => $errors,
            'details' => $this->results,
        ];

        // Output JSON result
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return empty($errors) ? 0 : 1;
    }
}

// Run validator if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $validator = new SymfonyAppValidator();
    exit($validator->run());
}
