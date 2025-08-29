<?php

namespace Shared\Utils;

/**
 * Comprehensive Logging System for APM PHP Examples
 * Provides structured logging across all applications
 */
class Logger
{
    private static $instance = null;
    private $logPath;
    private $logLevel;
    private $applicationName;

    const EMERGENCY = 0;
    const ALERT = 1;
    const CRITICAL = 2;
    const ERROR = 3;
    const WARNING = 4;
    const NOTICE = 5;
    const INFO = 6;
    const DEBUG = 7;

    private $levels = [
        0 => 'EMERGENCY',
        1 => 'ALERT',
        2 => 'CRITICAL',
        3 => 'ERROR',
        4 => 'WARNING',
        5 => 'NOTICE',
        6 => 'INFO',
        7 => 'DEBUG'
    ];

    private function __construct($applicationName = 'APM_PHP', $logLevel = self::INFO)
    {
        $this->applicationName = $applicationName;
        $this->logLevel = $logLevel;
        $this->logPath = $this->getLogPath();
        $this->ensureLogDirectory();
    }

    public static function getInstance($applicationName = 'APM_PHP', $logLevel = self::INFO)
    {
        if (self::$instance === null) {
            self::$instance = new self($applicationName, $logLevel);
        }
        return self::$instance;
    }

    private function getLogPath()
    {
        // Try to determine the application root
        $possibleRoots = [
            dirname(__DIR__, 2) . '/logs',
            '/var/log/apm-php',
            '/tmp/apm-php-logs'
        ];

        foreach ($possibleRoots as $path) {
            if (is_writable(dirname($path)) || is_writable($path)) {
                return $path;
            }
        }

        return '/tmp/apm-php-logs';
    }

    private function ensureLogDirectory()
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function log($level, $message, array $context = [])
    {
        if ($level > $this->logLevel) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelName = $this->levels[$level] ?? 'UNKNOWN';
        $contextString = !empty($context) ? json_encode($context) : '';
        
        $logEntry = sprintf(
            "[%s] %s.%s: %s %s\n",
            $timestamp,
            $this->applicationName,
            $levelName,
            $message,
            $contextString
        );

        // Write to application-specific log file
        $logFile = $this->logPath . '/' . strtolower($this->applicationName) . '.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Write to combined log file
        $combinedLogFile = $this->logPath . '/combined.log';
        file_put_contents($combinedLogFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Write errors to separate error log
        if ($level <= self::ERROR) {
            $errorLogFile = $this->logPath . '/error.log';
            file_put_contents($errorLogFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    public function emergency($message, array $context = [])
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(self::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log database operations
     */
    public function logDatabaseOperation($operation, $table, $data = [], $executionTime = null)
    {
        $context = [
            'operation' => $operation,
            'table' => $table,
            'data' => $data,
            'execution_time' => $executionTime
        ];
        $this->info("Database operation: $operation on $table", $context);
    }

    /**
     * Log API calls
     */
    public function logApiCall($url, $method, $responseCode, $executionTime = null)
    {
        $context = [
            'url' => $url,
            'method' => $method,
            'response_code' => $responseCode,
            'execution_time' => $executionTime
        ];
        $this->info("API call: $method $url", $context);
    }

    /**
     * Log queue operations
     */
    public function logQueueOperation($operation, $queue, $data = [])
    {
        $context = [
            'operation' => $operation,
            'queue' => $queue,
            'data' => $data
        ];
        $this->info("Queue operation: $operation on $queue", $context);
    }

    /**
     * Log application performance metrics
     */
    public function logPerformance($metric, $value, $unit = 'ms')
    {
        $context = [
            'metric' => $metric,
            'value' => $value,
            'unit' => $unit
        ];
        $this->info("Performance metric: $metric = $value $unit", $context);
    }

    /**
     * Get recent log entries
     */
    public function getRecentLogs($lines = 100, $logType = 'combined')
    {
        $logFile = $this->logPath . '/' . $logType . '.log';
        if (!file_exists($logFile)) {
            return [];
        }

        $command = "tail -n $lines " . escapeshellarg($logFile);
        $output = shell_exec($command);
        
        return $output ? explode("\n", trim($output)) : [];
    }

    /**
     * Clear old log files
     */
    public function rotateLogs($daysToKeep = 7)
    {
        $files = glob($this->logPath . '/*.log');
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}
