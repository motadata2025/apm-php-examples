<?php

namespace App\Lib;

use Exception;
use Psr\Log\LoggerInterface;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

/**
 * Enhanced Logger for Symfony APM testing
 * Provides structured logging with Symfony integration and Monolog features
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
    private $symfonyLogger;
    private $monologLogger;

    public function __construct(
        string $logFile = null, 
        string $minLevel = self::LEVEL_INFO,
        LoggerInterface $symfonyLogger = null
    ) {
        $this->logFile = $logFile ?: $this->getDefaultLogFile();
        $this->minLevel = $minLevel;
        $this->symfonyLogger = $symfonyLogger;
        $this->context = [
            'application' => 'symfony-app',
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'dev'
        ];
        
        // Setup Monolog logger
        $this->setupMonologLogger();
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Setup Monolog logger with custom configuration
     */
    private function setupMonologLogger(): void
    {
        $this->monologLogger = new MonologLogger('symfony-app');
        
        // Add rotating file handler
        $rotatingHandler = new RotatingFileHandler(
            dirname($this->logFile) . '/symfony-app.log',
            7, // Keep 7 days
            MonologLogger::DEBUG
        );
        $rotatingHandler->setFormatter(new JsonFormatter());
        
        $this->monologLogger->pushHandler($rotatingHandler);
        
        // Add stream handler for immediate logging
        $streamHandler = new StreamHandler($this->logFile, MonologLogger::DEBUG);
        $streamHandler->setFormatter(new JsonFormatter());
        
        $this->monologLogger->pushHandler($streamHandler);
    }

    /**
     * Log debug message using Symfony Logger
     */
    public function debugSymfony(string $message, array $context = []): void
    {
        if ($this->symfonyLogger) {
            $this->symfonyLogger->debug($message, array_merge($this->context, $context));
        }
        $this->debug($message, $context);
    }

    /**
     * Log info message using Symfony Logger
     */
    public function infoSymfony(string $message, array $context = []): void
    {
        if ($this->symfonyLogger) {
            $this->symfonyLogger->info($message, array_merge($this->context, $context));
        }
        $this->info($message, $context);
    }

    /**
     * Log warning message using Symfony Logger
     */
    public function warningSymfony(string $message, array $context = []): void
    {
        if ($this->symfonyLogger) {
            $this->symfonyLogger->warning($message, array_merge($this->context, $context));
        }
        $this->warning($message, $context);
    }

    /**
     * Log error message using Symfony Logger
     */
    public function errorSymfony(string $message, array $context = []): void
    {
        if ($this->symfonyLogger) {
            $this->symfonyLogger->error($message, array_merge($this->context, $context));
        }
        $this->error($message, $context);
    }

    /**
     * Log critical message using Symfony Logger
     */
    public function criticalSymfony(string $message, array $context = []): void
    {
        if ($this->symfonyLogger) {
            $this->symfonyLogger->critical($message, array_merge($this->context, $context));
        }
        $this->critical($message, $context);
    }

    /**
     * Log debug message using Monolog
     */
    public function debugMonolog(string $message, array $context = []): void
    {
        $this->monologLogger->debug($message, array_merge($this->context, $context));
        $this->debug($message, $context);
    }

    /**
     * Log info message using Monolog
     */
    public function infoMonolog(string $message, array $context = []): void
    {
        $this->monologLogger->info($message, array_merge($this->context, $context));
        $this->info($message, $context);
    }

    /**
     * Log warning message using Monolog
     */
    public function warningMonolog(string $message, array $context = []): void
    {
        $this->monologLogger->warning($message, array_merge($this->context, $context));
        $this->warning($message, $context);
    }

    /**
     * Log error message using Monolog
     */
    public function errorMonolog(string $message, array $context = []): void
    {
        $this->monologLogger->error($message, array_merge($this->context, $context));
        $this->error($message, $context);
    }

    /**
     * Log critical message using Monolog
     */
    public function criticalMonolog(string $message, array $context = []): void
    {
        $this->monologLogger->critical($message, array_merge($this->context, $context));
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
     * Log exception using Symfony Logger
     */
    public function exceptionSymfony(Exception $exception, array $context = []): void
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
        
        if ($this->symfonyLogger) {
            $this->symfonyLogger->error('Exception occurred: ' . $exception->getMessage(), 
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
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Write to file
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to Symfony logger for critical errors
        if ($level === self::LEVEL_CRITICAL && $this->symfonyLogger) {
            $this->symfonyLogger->critical("CRITICAL: $message", $context);
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
     * Demonstrate Symfony logging features
     */
    public function demonstrateSymfonyLogging(): array
    {
        try {
            $results = [];
            
            // Test different logging methods
            $this->infoSymfony('Symfony logger demo', ['feature' => 'symfony_integration']);
            $this->infoMonolog('Monolog logger demo', ['feature' => 'monolog_integration']);
            $this->info('Custom logger demo', ['feature' => 'custom_implementation']);
            
            $results['logging_methods'] = [
                'symfony_logger' => $this->symfonyLogger !== null,
                'monolog_logger' => true,
                'custom_logger' => true
            ];
            
            // Test structured logging
            $this->logRequest('GET', '/api/test', ['Accept' => 'application/json']);
            $this->logResponse(200, ['Content-Type' => 'application/json'], '{"status":"ok"}', 0.123);
            $this->logQuery('SELECT * FROM users WHERE id = ?', [1], 0.045);
            
            $results['structured_logging'] = 'completed';
            
            return [
                'demonstration' => 'Symfony Logging Features',
                'features' => $results,
                'loggers_available' => [
                    'symfony' => $this->symfonyLogger !== null,
                    'monolog' => true,
                    'custom' => true
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'Symfony Logging Features',
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
            // Clear custom log file
            if (file_exists($this->logFile)) {
                file_put_contents($this->logFile, '');
            }
            
            // Clear Monolog rotating files
            $logDir = dirname($this->logFile);
            $files = glob($logDir . '/symfony-app-*.log');
            foreach ($files as $file) {
                unlink($file);
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get log file sizes
     */
    public function getLogFileSizes(): array
    {
        $sizes = [
            'custom_log' => file_exists($this->logFile) ? filesize($this->logFile) : 0
        ];
        
        // Get Monolog file sizes
        $logDir = dirname($this->logFile);
        $files = glob($logDir . '/symfony-app-*.log');
        foreach ($files as $file) {
            $sizes[basename($file)] = filesize($file);
        }
        
        return $sizes;
    }
}
