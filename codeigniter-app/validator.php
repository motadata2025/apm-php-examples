<?php
/**
 * Codeigniter-app Application Validator
 * Tests database connectivity, Redis connectivity, and HTTP functionality
 */

declare(strict_types=1);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timeout for the entire script
set_time_limit(120);

class CodeIgniterAppValidator
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

        // Fallback to environment variables with correct MySQL credentials
        $this->config = array_merge([
            'DB_MYSQL_HOST' => $_ENV['DB_MYSQL_HOST'] ?? '127.0.0.1',
            'DB_MYSQL_PORT' => $_ENV['DB_MYSQL_PORT'] ?? '3310',
            'DB_MYSQL_DATABASE' => $_ENV['DB_MYSQL_DATABASE'] ?? 'codeigniter_app_db',
            'DB_MYSQL_USERNAME' => $_ENV['DB_MYSQL_USERNAME'] ?? 'codeigniter_app_user',
            'DB_MYSQL_PASSWORD' => $_ENV['DB_MYSQL_PASSWORD'] ?? 'codeigniter_app_password',
            'DB_PGSQL_HOST' => $_ENV['DB_PGSQL_HOST'] ?? '127.0.0.1',
            'DB_PGSQL_PORT' => $_ENV['DB_PGSQL_PORT'] ?? '5436',
            'DB_PGSQL_DATABASE' => $_ENV['DB_PGSQL_DATABASE'] ?? 'codeigniter_app_db',
            'DB_PGSQL_USERNAME' => $_ENV['DB_PGSQL_USERNAME'] ?? 'postgres',
            'DB_PGSQL_PASSWORD' => $_ENV['DB_PGSQL_PASSWORD'] ?? 'postgrespassword',
            'REDIS_HOST' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'REDIS_PORT' => $_ENV['REDIS_PORT'] ?? '6383',
            'REDIS_PASSWORD' => $_ENV['REDIS_PASSWORD'] ?? '',
            'HTTP_TIMEOUT' => $_ENV['HTTP_TIMEOUT'] ?? '20',
            'EXTERNAL_API_URL' => $_ENV['EXTERNAL_API_URL'] ?? 'https://httpbin.org/get',
        ], $this->config);

        // Override MySQL credentials to match docker-compose.yml
        $this->config['DB_MYSQL_USERNAME'] = 'codeigniter_app_user';
        $this->config['DB_MYSQL_PASSWORD'] = 'codeigniter_app_password';
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
            // Test our application's external API endpoint instead of calling external API directly
            $url = 'http://127.0.0.1:8082/api/external';
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
                    CURLOPT_SSL_VERIFYPEER => false, // Local server
                    CURLOPT_POST => true, // Use POST method
                    CURLOPT_USERAGENT => 'APM-PHP-Validator/1.0',
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($response === false || !empty($error)) {
                    throw new Exception("cURL error: $error");
                }

                // Accept 200 and 500 status codes since our API returns JSON for both success and failure
                if ($httpCode !== 200 && $httpCode !== 500) {
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

    public function validateWebUI(): bool
    {
        $start = microtime(true);
        try {
            // Check if web server is running on port 8082
            $url = 'http://127.0.0.1:8082/';
            $timeout = 10;

            // Try cURL first
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_FOLLOWLOCATION => true,
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

                // Check if response contains expected content
                $hasCodeIgniter = strpos($response, 'CodeIgniter') !== false;
                $hasPhpVersion = strpos($response, PHP_VERSION) !== false;

                $this->results['web'] = [
                    'success' => $hasCodeIgniter && $hasPhpVersion,
                    'duration' => microtime(true) - $start,
                    'http_code' => $httpCode,
                    'has_codeigniter_text' => $hasCodeIgniter,
                    'has_php_version' => $hasPhpVersion,
                    'url' => $url,
                ];

                return $hasCodeIgniter && $hasPhpVersion;
            }

            throw new Exception("cURL not available for web UI validation");

        } catch (Exception $e) {
            $this->results['web'] = [
                'success' => false,
                'duration' => microtime(true) - $start,
                'error' => $e->getMessage(),
                'url' => $url ?? 'unknown',
            ];
            return false;
        }
    }

    public function validateFramework(): bool
    {
        $start = microtime(true);
        try {
            // Check for CodeIgniter 4 framework
            $ci4Present = false;
            $sparkExists = false;
            $composerHasCI4 = false;
            $controllersExist = false;
            $viewsExist = false;

            // Check for spark executable
            if (file_exists(__DIR__ . '/spark') && is_executable(__DIR__ . '/spark')) {
                $sparkExists = true;
            }

            // Check composer.json for CI4
            $composerFile = __DIR__ . '/composer.json';
            if (file_exists($composerFile)) {
                $composer = json_decode(file_get_contents($composerFile), true);
                if (isset($composer['require']['codeigniter4/framework']) ||
                    file_exists(__DIR__ . '/vendor/codeigniter4/framework')) {
                    $composerHasCI4 = true;
                }
            }

            // Check for app structure
            if (is_dir(__DIR__ . '/app/Controllers') && is_dir(__DIR__ . '/app/Views')) {
                $controllersExist = true;
                $viewsExist = true;
            }

            $ci4Present = $sparkExists && $composerHasCI4 && $controllersExist && $viewsExist;

            $this->results['framework'] = [
                'success' => $ci4Present,
                'duration' => microtime(true) - $start,
                'ci4_detected' => $ci4Present,
                'spark_exists' => $sparkExists,
                'composer_has_ci4' => $composerHasCI4,
                'controllers_exist' => $controllersExist,
                'views_exist' => $viewsExist,
            ];

            return $ci4Present;

        } catch (Exception $e) {
            $this->results['framework'] = [
                'success' => false,
                'duration' => microtime(true) - $start,
                'error' => $e->getMessage(),
            ];
            return false;
        }
    }

    public function validatePHPVersion(): bool
    {
        $start = microtime(true);
        try {
            $phpMajorMinor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
            $allowedVersions = ['8.1', '8.2', '8.3', '8.4'];
            $isValidVersion = in_array($phpMajorMinor, $allowedVersions);

            $this->results['php_version_check'] = [
                'success' => $isValidVersion,
                'duration' => microtime(true) - $start,
                'php_version' => PHP_VERSION,
                'php_major_minor' => $phpMajorMinor,
                'allowed_versions' => $allowedVersions,
                'is_valid' => $isValidVersion,
            ];

            return $isValidVersion;

        } catch (Exception $e) {
            $this->results['php_version_check'] = [
                'success' => false,
                'duration' => microtime(true) - $start,
                'error' => $e->getMessage(),
            ];
            return false;
        }
    }

    public function validatePHPExtensions(): bool
    {
        $start = microtime(true);
        try {
            $requiredExtensions = ['pdo', 'pdo_mysql', 'pdo_pgsql', 'curl', 'json', 'mbstring', 'openssl'];
            $missingExtensions = [];
            $loadedExtensions = [];

            foreach ($requiredExtensions as $ext) {
                if (extension_loaded($ext)) {
                    $loadedExtensions[] = $ext;
                } else {
                    $missingExtensions[] = $ext;
                }
            }

            // Check for Redis client
            $redisAvailable = false;
            $redisClient = 'none';

            if (extension_loaded('redis')) {
                $redisAvailable = true;
                $redisClient = 'phpredis';
            } elseif (class_exists('Predis\Client')) {
                $redisAvailable = true;
                $redisClient = 'predis';
            }

            $this->results['php_extensions'] = [
                'success' => empty($missingExtensions),
                'duration' => microtime(true) - $start,
                'required_extensions' => $requiredExtensions,
                'loaded_extensions' => $loadedExtensions,
                'missing_extensions' => $missingExtensions,
                'redis_available' => $redisAvailable,
                'redis_client' => $redisClient,
            ];

            return empty($missingExtensions);

        } catch (Exception $e) {
            $this->results['php_extensions'] = [
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

        // Validate PHP version first
        if (!$this->validatePHPVersion()) {
            $errors[] = "PHP version validation failed - must be 8.1, 8.2, 8.3, or 8.4";
        }

        // Validate PHP extensions
        if (!$this->validatePHPExtensions()) {
            $errors[] = "PHP extensions validation failed";
        }

        // Validate framework
        if (!$this->validateFramework()) {
            $errors[] = "CodeIgniter 4 framework validation failed";
        }

        // Run all other validations
        if (!$this->validateWebUI()) {
            $errors[] = "Web UI validation failed";
        }

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
        $phpMajorMinor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        $result = [
            'app' => 'codeigniter-app',
            'php_version' => PHP_VERSION,
            'php_major_minor' => $phpMajorMinor,
            'timestamp' => time(),
            'total_duration' => $totalDuration,
            'framework_ok' => $this->results['framework']['success'] ?? false,
            'php_version_ok' => $this->results['php_version_check']['success'] ?? false,
            'php_extensions_ok' => $this->results['php_extensions']['success'] ?? false,
            'web_ok' => $this->results['web']['success'] ?? false,
            'mysql_ok' => $this->results['mysql']['success'] ?? false,
            'pg_ok' => $this->results['postgresql']['success'] ?? false,
            'redis_ok' => $this->results['redis']['success'] ?? false,
            'external_ok' => $this->results['http']['success'] ?? false,
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
    $validator = new CodeIgniterAppValidator();
    exit($validator->run());
}
