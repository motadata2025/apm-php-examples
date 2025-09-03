<?php

namespace App\Controllers;

use App\AppConfig;
use PDO;
use PDOException;
use Predis\Client as RedisClient;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * API Controller for Slim Framework Application
 * Handles all API endpoints for database, Redis, and external API operations
 */
class ApiController
{
    private AppConfig $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    public function externalApi(Request $request, Response $response): Response
    {
        $startTime = microtime(true);
        
        try {
            $url = $this->config->get('external_api_url');
            $timeout = (int)$this->config->get('http_timeout', 10);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'Slim-Framework-APM/1.0',
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($responseBody === false || !empty($error)) {
                throw new \Exception("cURL error: $error");
            }

            // Try to decode JSON, fallback to text
            $decodedBody = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decodedBody = substr($responseBody, 0, 200) . (strlen($responseBody) > 200 ? '...' : '');
            }

            $result = [
                'ok' => true,
                'payload' => [
                    'status' => 'success',
                    'http_code' => $httpCode,
                    'body' => $decodedBody,
                    'duration_ms' => $duration
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $result = [
                'ok' => false,
                'error' => [
                    'code' => 'EXTERNAL_API_ERROR',
                    'msg' => $e->getMessage(),
                    'duration_ms' => $duration
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function dbConnectionCheck(Request $request, Response $response): Response
    {
        $results = [
            'mysql' => $this->testMysqlConnection(),
            'pg' => $this->testPgsqlConnection()
        ];

        $allOk = $results['mysql']['ok'] && $results['pg']['ok'];

        $result = [
            'ok' => $allOk,
            'payload' => [
                'mysql' => $results['mysql']['ok'] ? 'OK' : 'ERROR',
                'pg' => $results['pg']['ok'] ? 'OK' : 'ERROR',
                'details' => $results
            ]
        ];

        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function testMysqlConnection(): array
    {
        try {
            $credentials = $this->config->getMysqlCredentials();
            $pdo = new PDO(
                $this->config->getMysqlDsn(),
                $credentials['username'],
                $credentials['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 3,
                ]
            );

            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetch();

            return ['ok' => true, 'message' => 'MySQL connection successful'];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => 'MySQL connection failed: ' . $e->getMessage()];
        }
    }

    private function testPgsqlConnection(): array
    {
        try {
            $credentials = $this->config->getPgsqlCredentials();
            $pdo = new PDO(
                $this->config->getPgsqlDsn(),
                $credentials['username'],
                $credentials['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 3,
                ]
            );

            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetch();

            return ['ok' => true, 'message' => 'PostgreSQL connection successful'];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => 'PostgreSQL connection failed: ' . $e->getMessage()];
        }
    }

    public function dbCrud(Request $request, Response $response): Response
    {
        try {
            $mysqlResult = $this->performMysqlCrud();
            $pgsqlResult = $this->performPgsqlCrud();

            $allOk = $mysqlResult['ok'] && $pgsqlResult['ok'];

            $result = [
                'ok' => $allOk,
                'payload' => [
                    'mysql' => $mysqlResult,
                    'pg' => $pgsqlResult
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $result = [
                'ok' => false,
                'error' => [
                    'code' => 'DB_CRUD_ERROR',
                    'msg' => $e->getMessage()
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function performMysqlCrud(): array
    {
        try {
            $credentials = $this->config->getMysqlCredentials();
            $pdo = new PDO(
                $this->config->getMysqlDsn(),
                $credentials['username'],
                $credentials['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Create table if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS apm_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Get count before
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM apm_items");
            $countBefore = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Insert randomized data
            $randomHex = bin2hex(random_bytes(4));
            $name = "apm_{$randomHex}";
            $value = "val_" . time() . "_{$randomHex}";

            $stmt = $pdo->prepare("INSERT INTO apm_items (name, value) VALUES (?, ?)");
            $stmt->execute([$name, $value]);
            $insertedId = $pdo->lastInsertId();

            // Get count after
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM apm_items");
            $countAfter = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Read back inserted row
            $stmt = $pdo->prepare("SELECT * FROM apm_items WHERE id = ?");
            $stmt->execute([$insertedId]);
            $insertedRow = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'ok' => true,
                'inserted_id' => $insertedId,
                'inserted_data' => $insertedRow,
                'count_before' => $countBefore,
                'count_after' => $countAfter
            ];

        } catch (PDOException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function performPgsqlCrud(): array
    {
        try {
            $credentials = $this->config->getPgsqlCredentials();
            $pdo = new PDO(
                $this->config->getPgsqlDsn(),
                $credentials['username'],
                $credentials['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Create table if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS apm_items (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Get count before
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM apm_items");
            $countBefore = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Insert randomized data
            $randomHex = bin2hex(random_bytes(4));
            $name = "apm_{$randomHex}";
            $value = "val_" . time() . "_{$randomHex}";

            $stmt = $pdo->prepare("INSERT INTO apm_items (name, value) VALUES (?, ?) RETURNING id");
            $stmt->execute([$name, $value]);
            $insertedId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

            // Get count after
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM apm_items");
            $countAfter = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Read back inserted row
            $stmt = $pdo->prepare("SELECT * FROM apm_items WHERE id = ?");
            $stmt->execute([$insertedId]);
            $insertedRow = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'ok' => true,
                'inserted_id' => $insertedId,
                'inserted_data' => $insertedRow,
                'count_before' => $countBefore,
                'count_after' => $countAfter
            ];

        } catch (PDOException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function getRedisClient(): RedisClient
    {
        $config = $this->config->getRedisConfig();
        
        $parameters = [
            'scheme' => 'tcp',
            'host' => $config['host'],
            'port' => $config['port'],
            'database' => $config['database'],
        ];

        if ($config['password']) {
            $parameters['password'] = $config['password'];
        }

        return new RedisClient($parameters, ['timeout' => 3.0]);
    }

    public function redisInsertBulk(Request $request, Response $response): Response
    {
        try {
            $redis = $this->getRedisClient();
            $queueKey = $this->config->getRedisQueueKey();

            $insertedValues = [];
            for ($i = 0; $i < 3; $i++) {
                $randomHex = bin2hex(random_bytes(4));
                $value = "bulk_val_" . time() . "_{$randomHex}_{$i}";
                $redis->rpush($queueKey, $value);
                $insertedValues[] = $value;
            }

            $newLength = $redis->llen($queueKey);

            $result = [
                'ok' => true,
                'payload' => [
                    'inserted_values' => $insertedValues,
                    'new_length' => $newLength
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $result = [
                'ok' => false,
                'error' => [
                    'code' => 'REDIS_INSERT_BULK_ERROR',
                    'msg' => $e->getMessage()
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function redisInsertSingle(Request $request, Response $response): Response
    {
        try {
            $redis = $this->getRedisClient();
            $queueKey = $this->config->getRedisQueueKey();

            $randomHex = bin2hex(random_bytes(4));
            $value = "single_val_" . time() . "_{$randomHex}";
            
            $redis->rpush($queueKey, $value);
            $newLength = $redis->llen($queueKey);

            $result = [
                'ok' => true,
                'payload' => [
                    'inserted_value' => $value,
                    'queue_length' => $newLength
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $result = [
                'ok' => false,
                'error' => [
                    'code' => 'REDIS_INSERT_SINGLE_ERROR',
                    'msg' => $e->getMessage()
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function redisReadSingle(Request $request, Response $response): Response
    {
        try {
            $redis = $this->getRedisClient();
            $queueKey = $this->config->getRedisQueueKey();

            $poppedValue = $redis->lpop($queueKey);
            $remainingLength = $redis->llen($queueKey);

            if ($poppedValue === null) {
                $result = [
                    'ok' => true,
                    'payload' => [
                        'empty' => true,
                        'remaining_length' => $remainingLength
                    ]
                ];
            } else {
                $result = [
                    'ok' => true,
                    'payload' => [
                        'popped_value' => $poppedValue,
                        'remaining_length' => $remainingLength
                    ]
                ];
            }

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $result = [
                'ok' => false,
                'error' => [
                    'code' => 'REDIS_READ_SINGLE_ERROR',
                    'msg' => $e->getMessage()
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function redisClear(Request $request, Response $response): Response
    {
        try {
            $redis = $this->getRedisClient();
            $queueKey = $this->config->getRedisQueueKey();

            $previousLength = $redis->llen($queueKey);
            $redis->del($queueKey);

            $result = [
                'ok' => true,
                'payload' => [
                    'previous_length' => $previousLength,
                    'current_length' => 0
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $result = [
                'ok' => false,
                'error' => [
                    'code' => 'REDIS_CLEAR_ERROR',
                    'msg' => $e->getMessage()
                ]
            ];

            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
