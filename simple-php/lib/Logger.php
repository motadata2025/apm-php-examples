<?php

namespace SimplePhp\Lib;

use Exception;

/**
 * Simple Logger for APM testing
 * Provides structured logging with different levels
 */
class Logger
{
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';

    private $logFile;
    private $minLevel;
    private $context;

    public function __construct(string $logFile = null, string $minLevel = self::LEVEL_INFO)
    {
        $this->logFile = $logFile ?: $this->getDefaultLogFile();
        $this->minLevel = $minLevel;
        $this->context = [
            'application' => 'simple-php',
            'version' => '1.0.0'
        ];
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log critical message
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Log exception
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
     * Main logging method
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Write to file
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to error_log for critical errors
        if ($level === self::LEVEL_CRITICAL) {
            error_log("CRITICAL: $message");
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
        $logDir = __DIR__ . '/../logs';
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
     * Get recent log entries
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
     * Clear log file
     */
    public function clearLogs(): bool
    {
        if (file_exists($this->logFile)) {
            return unlink($this->logFile);
        }
        return true;
    }

    /**
     * Get log file size
     */
    public function getLogFileSize(): int
    {
        return file_exists($this->logFile) ? filesize($this->logFile) : 0;
    }
}
