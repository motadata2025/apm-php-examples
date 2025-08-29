<?php

namespace App\Lib;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;

/**
 * Enhanced Logger for Slim Framework APM testing
 * Provides structured logging with Slim container integration
 */
class Logger
{
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    private $logFile;
    private $minLevel;
    private $context;
    private $slimLogger;
    private $container;

    public function __construct(
        string $logFile = null, 
        string $minLevel = self::LEVEL_INFO,
        ContainerInterface $container = null
    ) {
        $this->logFile = $logFile ?: $this->getDefaultLogFile();
        $this->minLevel = $minLevel;
        $this->container = $container;
        $this->context = [
            'application' => 'slim-framework',
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'development'
        ];
        
        // Get Slim logger from container if available
        if ($this->container && $this->container->has('logger')) {
            $this->slimLogger = $this->container->get('logger');
        }
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log debug message using Slim Logger
     */
    public function debugSlim(string $message, array $context = []): void
    {
        if ($this->slimLogger) {
            $this->slimLogger->debug($message, array_merge($this->context, $context));
        }
        $this->debug($message, $context);
    }

    /**
     * Log info message using Slim Logger
     */
    public function infoSlim(string $message, array $context = []): void
    {
        if ($this->slimLogger) {
            $this->slimLogger->info($message, array_merge($this->context, $context));
        }
        $this->info($message, $context);
    }

    /**
     * Log warning message using Slim Logger
     */
    public function warningSlim(string $message, array $context = []): void
    {
        if ($this->slimLogger) {
            $this->slimLogger->warning($message, array_merge($this->context, $context));
        }
        $this->warning($message, $context);
    }

    /**
     * Log error message using Slim Logger
     */
    public function errorSlim(string $message, array $context = []): void
    {
        if ($this->slimLogger) {
            $this->slimLogger->error($message, array_merge($this->context, $context));
        }
        $this->error($message, $context);
    }

    /**
     * Log critical message using Slim Logger
     */
    public function criticalSlim(string $message, array $context = []): void
    {
        if ($this->slimLogger) {
            $this->slimLogger->critical($message, array_merge($this->context, $context));
        }
        $this->critical($message, $context);
    }

    /**
     * Log debug message (custom implementation)
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log info message (custom implementation)
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log warning message (custom implementation)
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log error message (custom implementation)
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log critical message (custom implementation)
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Log exception using Slim Logger
     */
    public function exceptionSlim(Exception $exception, array $context = []): void
    {
        $exceptionContext = [
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        ];
        
        if ($this->slimLogger) {
            $this->slimLogger->error('Exception occurred: ' . $exception->getMessage(), 
                array_merge($this->context, $context, $exceptionContext));
        }
        
        $this->exception($exception, $context);
    }

    /**
     * Log exception (custom implementation)
     */
    public function exception(Exception $exception, array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        $this->log(self::LEVEL_ERROR, 'Exception occurred: ' . $exception->getMessage(), $context);
    }

    /**
     * Log HTTP request
     */
    public function logRequest(string $method, string $url, array $headers = [], array $data = []): void
    {
        $context = [
            'type' => 'http_request',
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'data' => $data,
            'timestamp' => microtime(true)
        ];
        
        $this->info("HTTP Request: $method $url", $context);
    }

    /**
     * Log HTTP response
     */
    public function logResponse(int $statusCode, array $headers = [], $body = null, float $duration = null): void
    {
        $context = [
            'type' => 'http_response',
            'status_code' => $statusCode,
            'headers' => $headers,
            'body_size' => is_string($body) ? strlen($body) : 0,
            'duration' => $duration,
            'timestamp' => microtime(true)
        ];
        
        $level = $statusCode >= 400 ? self::LEVEL_ERROR : self::LEVEL_INFO;
        $this->log($level, "HTTP Response: $statusCode", $context);
    }

    /**
     * Log database query
     */
    public function logQuery(string $query, array $params = [], float $duration = null): void
    {
        $context = [
            'type' => 'database_query',
            'query' => $query,
            'params' => $params,
            'duration' => $duration,
            'timestamp' => microtime(true)
        ];
        
        $this->debug("Database Query", $context);
    }

    /**
     * Log Slim route access
     */
    public function logRoute(string $route, string $method, array $params = []): void
    {
        $context = [
            'type' => 'route_access',
            'route' => $route,
            'method' => $method,
            'params' => $params,
            'timestamp' => microtime(true)
        ];
        
        $this->info("Route Access: $method $route", $context);
    }

    /**
     * Log middleware execution
     */
    public function logMiddleware(string $middleware, float $duration = null): void
    {
        $context = [
            'type' => 'middleware_execution',
            'middleware' => $middleware,
            'duration' => $duration,
            'timestamp' => microtime(true)
        ];
        
        $this->debug("Middleware: $middleware", $context);
    }

    /**
     * Main logging method (custom implementation)
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('c'), // ISO 8601 format
            'level' => strtoupper($level),
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid()
        ];
        
        // Add Slim-specific context if container is available
        if ($this->container) {
            $logEntry['slim_container'] = true;
            
            // Add request information if available
            if ($this->container->has('request')) {
                try {
                    $request = $this->container->get('request');
                    $logEntry['request_info'] = [
                        'method' => $request->getMethod(),
                        'uri' => (string) $request->getUri(),
                        'headers' => $request->getHeaders()
                    ];
                } catch (Exception $e) {
                    // Ignore if request is not available
                }
            }
        }
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Write to file
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to Slim logger for critical errors
        if ($level === self::LEVEL_CRITICAL && $this->slimLogger) {
            $this->slimLogger->critical("CRITICAL: $message", $context);
        }
    }

    /**
     * Check if level should be logged
     */
    private function shouldLog(string $level): bool
    {
        $levels = [
            self::LEVEL_DEBUG => 0,
            self::LEVEL_INFO => 1,
            self::LEVEL_WARNING => 2,
            self::LEVEL_ERROR => 3,
            self::LEVEL_CRITICAL => 4
        ];
        
        return ($levels[$level] ?? 0) >= ($levels[$this->minLevel] ?? 1);
    }

    /**
     * Get default log file path
     */
    private function getDefaultLogFile(): string
    {
        $logDir = __DIR__ . '/../var/log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        return $logDir . '/app-' . date('Y-m-d') . '.log';
    }

    /**
     * Set context data
     */
    public function setContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * Add context data
     */
    public function addContext(string $key, $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * Get recent log entries (custom implementation)
     */
    public function getRecentLogs(int $lines = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = [];
        $file = new \SplFileObject($this->logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logEntry = json_decode($line, true);
                if ($logEntry) {
                    $logs[] = $logEntry;
                }
            }
            $file->next();
        }
        
        return $logs;
    }

    /**
     * Demonstrate Slim logging features
     */
    public function demonstrateSlimLogging(): array
    {
        try {
            $results = [];
            
            // Test different logging methods
            $this->infoSlim('Slim logger demo', ['feature' => 'slim_integration']);
            $this->info('Custom logger demo', ['feature' => 'custom_implementation']);
            
            $results['logging_methods'] = [
                'slim_logger' => $this->slimLogger !== null,
                'custom_logger' => true,
                'container_integration' => $this->container !== null
            ];
            
            // Test structured logging
            $this->logRequest('GET', '/api/test', ['Accept' => 'application/json']);
            $this->logResponse(200, ['Content-Type' => 'application/json'], '{"status":"ok"}', 0.123);
            $this->logQuery('SELECT * FROM users WHERE id = ?', [1], 0.045);
            $this->logRoute('/users/{id}', 'GET', ['id' => 1]);
            $this->logMiddleware('AuthMiddleware', 0.012);
            
            $results['structured_logging'] = 'completed';
            
            // Test container integration
            if ($this->container) {
                $results['container_features'] = [
                    'logger_service' => $this->container->has('logger'),
                    'request_service' => $this->container->has('request'),
                    'settings_service' => $this->container->has('settings')
                ];
            }
            
            return [
                'demonstration' => 'Slim Framework Logging Features',
                'features' => $results,
                'loggers_available' => [
                    'slim' => $this->slimLogger !== null,
                    'custom' => true,
                    'container' => $this->container !== null
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'Slim Framework Logging Features',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear log files
     */
    public function clearLogs(): bool
    {
        try {
            if (file_exists($this->logFile)) {
                file_put_contents($this->logFile, '');
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get log file size
     */
    public function getLogFileSize(): int
    {
        return file_exists($this->logFile) ? filesize($this->logFile) : 0;
    }
}
