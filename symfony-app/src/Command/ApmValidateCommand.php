<?php

namespace App\Command;

use Doctrine\DBAL\DriverManager;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Predis\Client as RedisClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(
    name: 'app:apm-validate',
    description: 'Validates APM functionality including external API, databases, and Redis'
)]
class ApmValidateCommand extends Command
{
    private array $config;
    private FakerGenerator $faker;

    public function __construct()
    {
        parent::__construct();
        $this->loadConfig();
        $this->faker = FakerFactory::create();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'mysql_host' => $_ENV['DB_MYSQL_HOST'] ?? '127.0.0.1',
            'mysql_port' => $_ENV['DB_MYSQL_PORT'] ?? '3308',
            'mysql_database' => $_ENV['DB_MYSQL_DATABASE'] ?? 'symfony_app_db',
            'mysql_username' => $_ENV['DB_MYSQL_USERNAME'] ?? 'symfony_app_user',
            'mysql_password' => $_ENV['DB_MYSQL_PASSWORD'] ?? 'symfony_app_password',
            'pgsql_host' => $_ENV['DB_PGSQL_HOST'] ?? '127.0.0.1',
            'pgsql_port' => $_ENV['DB_PGSQL_PORT'] ?? '5434',
            'pgsql_database' => $_ENV['DB_PGSQL_DATABASE'] ?? 'symfony_app_db',
            'pgsql_username' => $_ENV['DB_PGSQL_USERNAME'] ?? 'postgres',
            'pgsql_password' => $_ENV['DB_PGSQL_PASSWORD'] ?? 'postgrespassword',
            'redis_host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'redis_port' => $_ENV['REDIS_PORT'] ?? '6381',
            'redis_password' => $_ENV['REDIS_PASSWORD'] ?? '',
            'redis_database' => $_ENV['REDIS_DATABASE'] ?? 0,
            'external_api_url' => $_ENV['EXTERNAL_API_URL'] ?? 'https://httpbin.org/get',
            'http_timeout' => $_ENV['HTTP_TIMEOUT'] ?? 20,
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);
        $errors = [];

        // Validate external API
        $externalOk = $this->validateExternalApi($errors);

        // Validate MySQL
        $mysqlOk = $this->validateMysql($errors);

        // Validate PostgreSQL
        $pgsqlOk = $this->validatePgsql($errors);

        // Validate Redis
        $redisOk = $this->validateRedis($errors);

        $totalDuration = microtime(true) - $startTime;

        $result = [
            'app' => 'symfony-app',
            'php_version' => PHP_VERSION,
            'web_server' => 'php_cli',
            'mysql_ok' => $mysqlOk,
            'pgsql_ok' => $pgsqlOk,
            'redis_ok' => $redisOk,
            'external_ok' => $externalOk,
            'errors' => $errors,
            'duration' => round($totalDuration, 3),
            'timestamp' => time()
        ];

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }

    private function validateExternalApi(array &$errors): bool
    {
        try {
            $client = HttpClient::create([
                'timeout' => (int)$this->config['http_timeout'],
            ]);

            $response = $client->request('GET', $this->config['external_api_url']);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $errors[] = "External API returned status code: $statusCode";
                return false;
            }

            $content = $response->getContent();
            json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "External API response is not valid JSON";
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $errors[] = "External API validation failed: " . $e->getMessage();
            return false;
        }
    }

    private function validateMysql(array &$errors): bool
    {
        try {
            $connection = DriverManager::getConnection([
                'driver' => 'pdo_mysql',
                'host' => $this->config['mysql_host'],
                'port' => $this->config['mysql_port'],
                'dbname' => $this->config['mysql_database'],
                'user' => $this->config['mysql_username'],
                'password' => $this->config['mysql_password'],
                'charset' => 'utf8mb4',
            ]);

            // Test connection
            $connection->executeQuery('SELECT 1')->fetchOne();

            // Test CRUD operations
            $connection->beginTransaction();

            try {
                $faker = $this->faker;
                $userName = $faker->name();
                $userEmail = $faker->unique()->email();

                // Create user
                $connection->executeStatement(
                    'INSERT INTO users (name, email) VALUES (?, ?)',
                    [$userName, $userEmail]
                );
                $userId = $connection->lastInsertId();

                // Read user
                $user = $connection->executeQuery('SELECT * FROM users WHERE id = ?', [$userId])->fetchAssociative();

                if (!$user) {
                    throw new \Exception('Failed to read created user');
                }

                // Update user
                $connection->executeStatement('UPDATE users SET name = ? WHERE id = ?', [$userName . '-updated', $userId]);

                // Delete user
                $connection->executeStatement('DELETE FROM users WHERE id = ?', [$userId]);

                $connection->commit();
                $connection->close();

                return true;

            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $errors[] = "MySQL validation failed: " . $e->getMessage();
            return false;
        }
    }

    private function validatePgsql(array &$errors): bool
    {
        try {
            $connection = DriverManager::getConnection([
                'driver' => 'pdo_pgsql',
                'host' => $this->config['pgsql_host'],
                'port' => $this->config['pgsql_port'],
                'dbname' => $this->config['pgsql_database'],
                'user' => $this->config['pgsql_username'],
                'password' => $this->config['pgsql_password'],
            ]);

            // Test connection
            $connection->executeQuery('SELECT 1')->fetchOne();

            // Test CRUD operations
            $connection->beginTransaction();

            try {
                $faker = $this->faker;
                $userName = $faker->name();
                $userEmail = $faker->unique()->email();

                // Create user
                $result = $connection->executeQuery(
                    'INSERT INTO users (name, email) VALUES (?, ?) RETURNING id',
                    [$userName, $userEmail]
                );
                $userId = $result->fetchOne();

                // Read user
                $user = $connection->executeQuery('SELECT * FROM users WHERE id = ?', [$userId])->fetchAssociative();

                if (!$user) {
                    throw new \Exception('Failed to read created user');
                }

                // Update user
                $connection->executeStatement('UPDATE users SET name = ? WHERE id = ?', [$userName . '-updated', $userId]);

                // Delete user
                $connection->executeStatement('DELETE FROM users WHERE id = ?', [$userId]);

                $connection->commit();
                $connection->close();

                return true;

            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $errors[] = "PostgreSQL validation failed: " . $e->getMessage();
            return false;
        }
    }

    private function validateRedis(array &$errors): bool
    {
        try {
            // Check if Redis extension or Predis is available
            if (!extension_loaded('redis') && !class_exists('Predis\Client')) {
                $errors[] = "Redis validation failed: Neither Redis extension nor Predis library is available";
                return false;
            }

            $parameters = [
                'scheme' => 'tcp',
                'host' => $this->config['redis_host'],
                'port' => (int)$this->config['redis_port'],
                'database' => (int)$this->config['redis_database'],
            ];

            if (!empty($this->config['redis_password'])) {
                $parameters['password'] = $this->config['redis_password'];
            }

            $redis = new RedisClient($parameters, ['timeout' => 3.0]);

            // Test queue operations
            $queueName = $this->getRedisQueueName();

            // Clear queue first
            $redis->del($queueName);

            // Test push operations
            $testValue1 = 'test_value_1_' . time();
            $testValue2 = 'test_value_2_' . time();

            $redis->lpush($queueName, $testValue1);
            $redis->lpush($queueName, $testValue2);

            $length = $redis->llen($queueName);
            if ($length !== 2) {
                throw new \Exception("Expected queue length 2, got $length");
            }

            // Test pop operation
            $poppedValue = $redis->rpop($queueName);
            if ($poppedValue !== $testValue1) {
                throw new \Exception("Expected popped value '$testValue1', got '$poppedValue'");
            }

            // Clear queue
            $redis->del($queueName);

            return true;

        } catch (\Exception $e) {
            $errors[] = "Redis validation failed: " . $e->getMessage();
            return false;
        }
    }

    private function getRedisQueueName(): string
    {
        $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        return "symfony-app_{$phpVersion}";
    }
}
