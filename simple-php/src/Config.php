<?php

declare(strict_types=1);

namespace SimplePhp;

use Dotenv\Dotenv;

class Config
{
    private static ?Config $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadEnvironment();
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnvironment(): void
    {
        $envPath = dirname(__DIR__);
        if (file_exists($envPath . '/.env')) {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->load();
        }
        
        $this->config = $_ENV;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function getMysqlConfig(): array
    {
        return [
            'host' => $this->get('DB_MYSQL_HOST', '127.0.0.1'),
            'port' => (int) $this->get('DB_MYSQL_PORT', 3307),
            'database' => $this->get('DB_MYSQL_DATABASE', 'simple_php_db'),
            'username' => $this->get('DB_MYSQL_USERNAME', 'simple_php_user'),
            'password' => $this->get('DB_MYSQL_PASSWORD', 'simple_php_password'),
        ];
    }

    public function getPostgresConfig(): array
    {
        return [
            'host' => $this->get('DB_PGSQL_HOST', '127.0.0.1'),
            'port' => (int) $this->get('DB_PGSQL_PORT', 5433),
            'database' => $this->get('DB_PGSQL_DATABASE', 'simple_php_db'),
            'username' => $this->get('DB_PGSQL_USERNAME', 'postgres'),
            'password' => $this->get('DB_PGSQL_PASSWORD', 'postgrespassword'),
        ];
    }

    public function getRedisConfig(): array
    {
        return [
            'host' => $this->get('REDIS_HOST', '127.0.0.1'),
            'port' => (int) $this->get('REDIS_PORT', 6380),
            'password' => $this->get('REDIS_PASSWORD', ''),
            'database' => (int) $this->get('REDIS_DATABASE', 0),
        ];
    }

    public function getExternalApiUrl(): string
    {
        return $this->get('EXTERNAL_API_URL', 'https://httpbin.org/get');
    }

    public function getHttpTimeout(): int
    {
        return (int) $this->get('HTTP_TIMEOUT', 20);
    }

    public function validateRequiredVars(): array
    {
        $required = [
            'DB_MYSQL_HOST', 'DB_MYSQL_PORT', 'DB_MYSQL_DATABASE', 'DB_MYSQL_USERNAME', 'DB_MYSQL_PASSWORD',
            'DB_PGSQL_HOST', 'DB_PGSQL_PORT', 'DB_PGSQL_DATABASE', 'DB_PGSQL_USERNAME', 'DB_PGSQL_PASSWORD',
            'REDIS_HOST', 'REDIS_PORT', 'EXTERNAL_API_URL'
        ];

        $missing = [];
        foreach ($required as $var) {
            if (empty($this->get($var))) {
                $missing[] = $var;
            }
        }

        return $missing;
    }
}
