<?php

namespace App\Lib;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Enhanced Logger for Laravel APM testing
 * Provides structured logging with Laravel integration and custom features
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
    private $channel;

    public function __construct(string $logFile = null, string $minLevel = self::LEVEL_INFO, string $channel = 'custom')
    {
        $this->logFile = $logFile ?: $this->getDefaultLogFile();
        $this->minLevel = $minLevel;
        $this->channel = $channel;
        $this->context = [
            'application' => 'laravel-app',
            'version' => '1.0.0',
            'environment' => app()->environment()
        ];
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log debug message using Laravel Log facade
     */
    public function debugLaravel(string $message, array $context = []): void
    {
        Log::channel($this->channel)->debug($message, array_merge($this->context, $context));
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log info message using Laravel Log facade
     */
    public function infoLaravel(string $message, array $context = []): void
    {
        Log::channel($this->channel)->info($message, array_merge($this->context, $context));
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log warning message using Laravel Log facade
     */
    public function warningLaravel(string $message, array $context = []): void
    {
        Log::channel($this->channel)->warning($message, array_merge($this->context, $context));
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log error message using Laravel Log facade
     */
    public function errorLaravel(string $message, array $context = []): void
    {
        Log::channel($this->channel)->error($message, array_merge($this->context, $context));
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log critical message using Laravel Log facade
     */
    public function criticalLaravel(string $message, array $context = []): void
    {
        Log::channel($this->channel)->critical($message, array_merge($this->context, $context));
        $this->log(self::LEVEL_CRITICAL, $message, $context);
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
     * Log exception using Laravel Log facade
     */
    public function exceptionLaravel(Exception $exception, array $context = []): void
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
        
        Log::channel($this->channel)->error('Exception occurred: ' . $exception->getMessage(), 
            array_merge($this->context, $context, $exceptionContext));
        
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
     * Log HTTP request using Laravel Log facade
     */
    public function logRequestLaravel(string $method, string $url, array $headers = [], array $data = []): void
    {
        $context = [
            'type' => 'http_request',
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'data' => $data,
            'timestamp' => microtime(true)
        ];
        
        Log::channel($this->channel)->info("HTTP Request: $method $url", array_merge($this->context, $context));
        $this->logRequest($method, $url, $headers, $data);
    }

    /**
     * Log HTTP request (custom implementation)
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
     * Log HTTP response using Laravel Log facade
     */
    public function logResponseLaravel(int $statusCode, array $headers = [], $body = null, float $duration = null): void
    {
        $context = [
            'type' => 'http_response',
            'status_code' => $statusCode,
            'headers' => $headers,
            'body_size' => is_string($body) ? strlen($body) : 0,
            'duration' => $duration,
            'timestamp' => microtime(true)
        ];
        
        $level = $statusCode >= 400 ? 'error' : 'info';
        Log::channel($this->channel)->$level("HTTP Response: $statusCode", array_merge($this->context, $context));
        
        $this->logResponse($statusCode, $headers, $body, $duration);
    }

    /**
     * Log HTTP response (custom implementation)
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
     * Log database query using Laravel Log facade
     */
    public function logQueryLaravel(string $query, array $params = [], float $duration = null): void
    {
        $context = [
            'type' => 'database_query',
            'query' => $query,
            'params' => $params,
            'duration' => $duration,
            'timestamp' => microtime(true)
        ];
        
        Log::channel($this->channel)->debug("Database Query", array_merge($this->context, $context));
        $this->logQuery($query, $params, $duration);
    }

    /**
     * Log database query (custom implementation)
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
            'timestamp' => now()->toISOString(),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'request_id' => request()->header('X-Request-ID', uniqid())
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Write to file
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to Laravel's default log for critical errors
        if ($level === self::LEVEL_CRITICAL) {
            Log::critical("CRITICAL: $message", $context);
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
        $logDir = storage_path('logs/custom');
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
     * Get recent log entries using Laravel Storage
     */
    public function getRecentLogsLaravel(int $lines = 100): array
    {
        try {
            $logPath = 'logs/laravel.log';
            if (Storage::exists($logPath)) {
                $content = Storage::get($logPath);
                $logLines = array_slice(explode("\n", $content), -$lines);
                
                $logs = [];
                foreach ($logLines as $line) {
                    if (!empty(trim($line))) {
                        // Try to parse Laravel log format
                        if (preg_match('/\[(.*?)\] (\w+)\.(\w+): (.*)/', $line, $matches)) {
                            $logs[] = [
                                'timestamp' => $matches[1],
                                'environment' => $matches[2],
                                'level' => $matches[3],
                                'message' => $matches[4]
                            ];
                        }
                    }
                }
                
                return $logs;
            }
            
            return [];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
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
     * Demonstrate Laravel logging features
     */
    public function demonstrateLaravelLogging(): array
    {
        try {
            // Log using different Laravel channels
            Log::channel('single')->info('Single channel log');
            Log::channel('daily')->info('Daily channel log');
            Log::channel('slack')->error('Slack notification log');
            
            // Log with context
            Log::withContext(['user_id' => 123, 'action' => 'demo'])->info('Contextual log');
            
            // Log using custom logger
            $this->infoLaravel('Custom Laravel logger demo', ['feature' => 'demonstration']);
            
            return [
                'demonstration' => 'Laravel Logging Features',
                'channels_used' => ['single', 'daily', 'slack', 'custom'],
                'features' => [
                    'multiple_channels' => 'Different log destinations',
                    'contextual_logging' => 'Automatic context addition',
                    'custom_integration' => 'Custom logger with Laravel integration'
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'Laravel Logging Features',
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
                unlink($this->logFile);
            }
            
            // Clear Laravel log file
            $laravelLogPath = storage_path('logs/laravel.log');
            if (file_exists($laravelLogPath)) {
                file_put_contents($laravelLogPath, '');
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
        return [
            'custom_log' => file_exists($this->logFile) ? filesize($this->logFile) : 0,
            'laravel_log' => file_exists(storage_path('logs/laravel.log')) ? filesize(storage_path('logs/laravel.log')) : 0
        ];
    }
}
