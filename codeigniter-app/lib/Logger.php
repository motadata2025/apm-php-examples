<?php

namespace App\Lib;

use Exception;

/**
 * Enhanced Logger for CodeIgniter APM testing
 * Provides structured logging with CodeIgniter integration
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

    public function __construct(string $logFile = null, string $minLevel = self::LEVEL_INFO)
    {
        $this->logFile = $logFile ?: $this->getDefaultLogFile();
        $this->minLevel = $minLevel;
        $this->context = [
            'application' => 'codeigniter-app',
            'version' => '1.0.0',
            'environment' => ENVIRONMENT ?? 'development'
        ];
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log debug message using CodeIgniter Logger
     */
    public function debugCI(string $message, array $context = []): void
    {
        if (function_exists('log_message')) {
            log_message('debug', $message . ' ' . json_encode(array_merge($this->context, $context)));
        }
        $this->debug($message, $context);
    }

    /**
     * Log info message using CodeIgniter Logger
     */
    public function infoCI(string $message, array $context = []): void
    {
        if (function_exists('log_message')) {
            log_message('info', $message . ' ' . json_encode(array_merge($this->context, $context)));
        }
        $this->info($message, $context);
    }

    /**
     * Log warning message using CodeIgniter Logger
     */
    public function warningCI(string $message, array $context = []): void
    {
        if (function_exists('log_message')) {
            log_message('warning', $message . ' ' . json_encode(array_merge($this->context, $context)));
        }
        $this->warning($message, $context);
    }

    /**
     * Log error message using CodeIgniter Logger
     */
    public function errorCI(string $message, array $context = []): void
    {
        if (function_exists('log_message')) {
            log_message('error', $message . ' ' . json_encode(array_merge($this->context, $context)));
        }
        $this->error($message, $context);
    }

    /**
     * Log critical message using CodeIgniter Logger
     */
    public function criticalCI(string $message, array $context = []): void
    {
        if (function_exists('log_message')) {
            log_message('critical', $message . ' ' . json_encode(array_merge($this->context, $context)));
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
     * Log exception using CodeIgniter Logger
     */
    public function exceptionCI(Exception $exception, array $context = []): void
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
        
        if (function_exists('log_message')) {
            log_message('error', 'Exception occurred: ' . $exception->getMessage() . ' ' . 
                json_encode(array_merge($this->context, $context, $exceptionContext)));
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
     * Log CodeIgniter controller action
     */
    public function logController(string $controller, string $method, array $params = []): void
    {
        $context = [
            'type' => 'controller_action',
            'controller' => $controller,
            'method' => $method,
            'params' => $params,
            'timestamp' => microtime(true)
        ];
        
        $this->info("Controller Action: $controller::$method", $context);
    }

    /**
     * Log CodeIgniter model operation
     */
    public function logModel(string $model, string $operation, array $data = []): void
    {
        $context = [
            'type' => 'model_operation',
            'model' => $model,
            'operation' => $operation,
            'data' => $data,
            'timestamp' => microtime(true)
        ];
        
        $this->debug("Model Operation: $model::$operation", $context);
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
        
        // Add CodeIgniter-specific context
        if (defined('ENVIRONMENT')) {
            $logEntry['ci_environment'] = ENVIRONMENT;
        }
        
        // Add request information if available
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $logEntry['request_info'] = [
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
        }
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Write to file
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to CodeIgniter logger for critical errors
        if ($level === self::LEVEL_CRITICAL && function_exists('log_message')) {
            log_message('critical', "CRITICAL: $message");
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
        $logDir = __DIR__ . '/../writable/logs';
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
     * Demonstrate CodeIgniter logging features
     */
    public function demonstrateCILogging(): array
    {
        try {
            $results = [];
            
            // Test different logging methods
            $this->infoCI('CodeIgniter logger demo', ['feature' => 'ci_integration']);
            $this->info('Custom logger demo', ['feature' => 'custom_implementation']);
            
            $results['logging_methods'] = [
                'codeigniter_logger' => function_exists('log_message'),
                'custom_logger' => true,
                'environment_integration' => defined('ENVIRONMENT')
            ];
            
            // Test structured logging
            $this->logRequest('GET', '/api/test', ['Accept' => 'application/json']);
            $this->logResponse(200, ['Content-Type' => 'application/json'], '{"status":"ok"}', 0.123);
            $this->logQuery('SELECT * FROM users WHERE id = ?', [1], 0.045);
            $this->logController('UserController', 'index', ['id' => 1]);
            $this->logModel('UserModel', 'find', ['id' => 1]);
            
            $results['structured_logging'] = 'completed';
            
            // Test environment integration
            if (defined('ENVIRONMENT')) {
                $results['environment'] = ENVIRONMENT;
            }
            
            return [
                'demonstration' => 'CodeIgniter Logging Features',
                'features' => $results,
                'loggers_available' => [
                    'codeigniter' => function_exists('log_message'),
                    'custom' => true,
                    'environment' => defined('ENVIRONMENT')
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'CodeIgniter Logging Features',
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
