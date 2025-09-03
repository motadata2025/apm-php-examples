<?php

namespace App\Controller;

use Doctrine\DBAL\DriverManager;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Predis\Client as RedisClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;

class UiController extends AbstractController
{
    private array $config;
    private FakerGenerator $faker;

    public function __construct()
    {
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

    public function index(): Response
    {
        $phpVersion = PHP_VERSION;
        $appName = 'symfony-app';
        $webServer = 'php_cli';

        return $this->render('index.html.twig', [
            'app_name' => $appName,
            'php_version' => $phpVersion,
            'web_server' => $webServer,
        ]);
    }

    public function externalApi(): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $client = HttpClient::create([
                'timeout' => (int)$this->config['http_timeout'],
            ]);

            $response = $client->request('GET', $this->config['external_api_url']);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Try to decode JSON
            $decodedContent = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decodedContent = substr($content, 0, 200) . (strlen($content) > 200 ? '...' : '');
            }

            return new JsonResponse([
                'status' => 'success',
                'response' => [
                    'status_code' => $statusCode,
                    'content' => $decodedContent,
                    'duration_ms' => $duration
                ]
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            return new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
                'duration_ms' => $duration
            ], 500);
        }
    }

    public function dbCheck(): JsonResponse
    {
        $results = [
            'mysql' => $this->testMysqlConnection(),
            'pgsql' => $this->testPgsqlConnection()
        ];

        return new JsonResponse([
            'mysql' => $results['mysql']['status'],
            'pgsql' => $results['pgsql']['status'],
            'details' => $results
        ]);
    }

    private function testMysqlConnection(): array
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

            $connection->executeQuery('SELECT 1')->fetchOne();
            $connection->close();

            return ['status' => 'ok', 'message' => 'MySQL connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'MySQL connection failed: ' . $e->getMessage()];
        }
    }

    private function testPgsqlConnection(): array
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

            $connection->executeQuery('SELECT 1')->fetchOne();
            $connection->close();

            return ['status' => 'ok', 'message' => 'PostgreSQL connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'PostgreSQL connection failed: ' . $e->getMessage()];
        }
    }

    public function dbCrud(): JsonResponse
    {
        try {
            $mysqlResult = $this->performMysqlCrud();
            $pgsqlResult = $this->performPgsqlCrud();

            return new JsonResponse([
                'mysql' => $mysqlResult,
                'pgsql' => $pgsqlResult
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ], 500);
        }
    }

    private function performMysqlCrud(): array
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

            $connection->beginTransaction();

            try {
                // Create user
                $faker = $this->faker;
                $userName = $faker->name();
                $userEmail = $faker->unique()->email();

                $userId = $connection->executeStatement(
                    'INSERT INTO users (name, email) VALUES (?, ?)',
                    [$userName, $userEmail]
                );
                $userId = $connection->lastInsertId();

                // Create post for the user
                $postTitle = $faker->sentence();
                $postContent = $faker->paragraph();

                $postId = $connection->executeStatement(
                    'INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)',
                    [$userId, $postTitle, $postContent]
                );
                $postId = $connection->lastInsertId();

                // Read back the created records
                $user = $connection->executeQuery('SELECT * FROM users WHERE id = ?', [$userId])->fetchAssociative();
                $post = $connection->executeQuery('SELECT * FROM posts WHERE id = ?', [$postId])->fetchAssociative();

                // Update the user name
                $updatedName = $userName . '-updated';
                $connection->executeStatement('UPDATE users SET name = ? WHERE id = ?', [$updatedName, $userId]);

                // Delete the records
                $connection->executeStatement('DELETE FROM posts WHERE id = ?', [$postId]);
                $connection->executeStatement('DELETE FROM users WHERE id = ?', [$userId]);

                $connection->commit();

                return [
                    'created_id' => $userId,
                    'post_id' => $postId,
                    'read_count' => 2,
                    'updated' => true,
                    'deleted' => true,
                    'user_data' => $user,
                    'post_data' => $post
                ];

            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function performPgsqlCrud(): array
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

            $connection->beginTransaction();

            try {
                // Create user
                $faker = $this->faker;
                $userName = $faker->name();
                $userEmail = $faker->unique()->email();

                $result = $connection->executeQuery(
                    'INSERT INTO users (name, email) VALUES (?, ?) RETURNING id',
                    [$userName, $userEmail]
                );
                $userId = $result->fetchOne();

                // Create post for the user
                $postTitle = $faker->sentence();
                $postContent = $faker->paragraph();

                $result = $connection->executeQuery(
                    'INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?) RETURNING id',
                    [$userId, $postTitle, $postContent]
                );
                $postId = $result->fetchOne();

                // Read back the created records
                $user = $connection->executeQuery('SELECT * FROM users WHERE id = ?', [$userId])->fetchAssociative();
                $post = $connection->executeQuery('SELECT * FROM posts WHERE id = ?', [$postId])->fetchAssociative();

                // Update the user name
                $updatedName = $userName . '-updated';
                $connection->executeStatement('UPDATE users SET name = ? WHERE id = ?', [$updatedName, $userId]);

                // Delete the records
                $connection->executeStatement('DELETE FROM posts WHERE id = ?', [$postId]);
                $connection->executeStatement('DELETE FROM users WHERE id = ?', [$userId]);

                $connection->commit();

                return [
                    'created_id' => $userId,
                    'post_id' => $postId,
                    'read_count' => 2,
                    'updated' => true,
                    'deleted' => true,
                    'user_data' => $user,
                    'post_data' => $post
                ];

            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function getRedisClient(): RedisClient
    {
        $parameters = [
            'scheme' => 'tcp',
            'host' => $this->config['redis_host'],
            'port' => (int)$this->config['redis_port'],
            'database' => (int)$this->config['redis_database'],
        ];

        if (!empty($this->config['redis_password'])) {
            $parameters['password'] = $this->config['redis_password'];
        }

        return new RedisClient($parameters, ['timeout' => 3.0]);
    }

    private function getRedisQueueName(): string
    {
        $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        return "symfony-app_{$phpVersion}";
    }

    public function redisPush(): JsonResponse
    {
        try {
            $redis = $this->getRedisClient();
            $queueName = $this->getRedisQueueName();

            $values = [];
            for ($i = 0; $i < 3; $i++) {
                $value = 'symfony_msg_' . bin2hex(random_bytes(4)) . '_' . time() . '_' . $i;
                $redis->lpush($queueName, $value);
                $values[] = $value;
            }

            $queueLength = $redis->llen($queueName);

            return new JsonResponse([
                'status' => 'success',
                'pushed_values' => $values,
                'queue_length' => $queueLength,
                'queue_name' => $queueName
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ], 500);
        }
    }

    public function redisPushOne(): JsonResponse
    {
        try {
            $redis = $this->getRedisClient();
            $queueName = $this->getRedisQueueName();

            $value = 'symfony_single_' . bin2hex(random_bytes(4)) . '_' . time();
            $redis->lpush($queueName, $value);

            $queueLength = $redis->llen($queueName);

            return new JsonResponse([
                'status' => 'success',
                'pushed_value' => $value,
                'queue_length' => $queueLength,
                'queue_name' => $queueName
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ], 500);
        }
    }

    public function redisPop(): JsonResponse
    {
        try {
            $redis = $this->getRedisClient();
            $queueName = $this->getRedisQueueName();

            $value = $redis->rpop($queueName);
            $queueLength = $redis->llen($queueName);

            return new JsonResponse([
                'status' => 'success',
                'popped_value' => $value,
                'queue_length' => $queueLength,
                'queue_name' => $queueName
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ], 500);
        }
    }

    public function redisClear(): JsonResponse
    {
        try {
            $redis = $this->getRedisClient();
            $queueName = $this->getRedisQueueName();

            $previousLength = $redis->llen($queueName);
            $redis->del($queueName);

            return new JsonResponse([
                'status' => 'success',
                'cleared_count' => $previousLength,
                'queue_length' => 0,
                'queue_name' => $queueName
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ], 500);
        }
    }
}
