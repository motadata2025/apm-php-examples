<?php

namespace App;

use Dotenv\Dotenv;

/**
 * Application Configuration Helper
 * Reads .env file and provides service configuration
 */
class AppConfig
{
    private array $config = [];

    public function __construct()
    {
        $this->loadEnvironment();
    }

    private function loadEnvironment(): void
    {
        // Load .env file if it exists
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $dotenv = Dotenv::createImmutable(dirname($envFile));
            $dotenv->load();
        }

        // Set configuration with defaults
        $this->config = [
            'app_name' => $_ENV['APP_NAME'] ?? 'Slim Framework App',
            'app_env' => $_ENV['APP_ENV'] ?? 'development',
            'app_debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
            
            // MySQL Configuration - use correct credentials from docker-compose.yml
            'mysql_host' => $_ENV['DB_MYSQL_HOST'] ?? '127.0.0.1',
            'mysql_port' => $_ENV['DB_MYSQL_PORT'] ?? '3309',
            'mysql_database' => $_ENV['DB_MYSQL_DATABASE'] ?? 'slim_framework_db',
            'mysql_username' => 'slim-framework_user',
            'mysql_password' => 'slim-framework_password',
            
            // PostgreSQL Configuration
            'pgsql_host' => $_ENV['DB_PGSQL_HOST'] ?? '127.0.0.1',
            'pgsql_port' => $_ENV['DB_PGSQL_PORT'] ?? '5435',
            'pgsql_database' => $_ENV['DB_PGSQL_DATABASE'] ?? 'slim_framework_db',
            'pgsql_username' => $_ENV['DB_PGSQL_USERNAME'] ?? 'postgres',
            'pgsql_password' => $_ENV['DB_PGSQL_PASSWORD'] ?? 'postgrespassword',
            
            // Redis Configuration
            'redis_host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'redis_port' => $_ENV['REDIS_PORT'] ?? '6382',
            'redis_password' => $_ENV['REDIS_PASSWORD'] ?? '',
            'redis_database' => $_ENV['REDIS_DATABASE'] ?? 0,
            
            // External API Configuration
            'external_api_url' => $_ENV['EXTERNAL_API_URL'] ?? 'https://httpbin.org/get',
            'http_timeout' => $_ENV['HTTP_TIMEOUT'] ?? 20,
        ];
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function getMysqlDsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->config['mysql_host'],
            $this->config['mysql_port'],
            $this->config['mysql_database']
        );
    }

    public function getPgsqlDsn(): string
    {
        return sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $this->config['pgsql_host'],
            $this->config['pgsql_port'],
            $this->config['pgsql_database']
        );
    }

    public function getRedisQueueKey(): string
    {
        $phpVersion = str_replace('.', '_', phpversion());
        return "slim-framework_{$phpVersion}";
    }

    public function getMysqlCredentials(): array
    {
        return [
            'username' => $this->config['mysql_username'],
            'password' => $this->config['mysql_password']
        ];
    }

    public function getPgsqlCredentials(): array
    {
        return [
            'username' => $this->config['pgsql_username'],
            'password' => $this->config['pgsql_password']
        ];
    }

    public function getRedisConfig(): array
    {
        return [
            'host' => $this->config['redis_host'],
            'port' => (int)$this->config['redis_port'],
            'password' => $this->config['redis_password'] ?: null,
            'database' => (int)$this->config['redis_database']
        ];
    }
}
