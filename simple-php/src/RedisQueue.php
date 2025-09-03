<?php

declare(strict_types=1);

namespace SimplePhp;

use Redis;
use Predis\Client as PredisClient;
use Exception;

class RedisQueue
{
    private Config $config;
    private Redis|PredisClient|null $client = null;
    private bool $useNativeRedis = false;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        $config = $this->config->getRedisConfig();
        
        // Try native Redis extension first
        if (extension_loaded('redis')) {
            try {
                $this->client = new Redis();
                $this->client->connect($config['host'], $config['port'], 5.0);
                
                if (!empty($config['password'])) {
                    $this->client->auth($config['password']);
                }
                
                $this->client->select($config['database']);
                $this->useNativeRedis = true;
                return;
            } catch (Exception $e) {
                $this->client = null;
            }
        }
        
        // Fallback to Predis
        try {
            $predisConfig = [
                'scheme' => 'tcp',
                'host' => $config['host'],
                'port' => $config['port'],
                'database' => $config['database'],
                'timeout' => 5.0,
            ];
            
            if (!empty($config['password'])) {
                $predisConfig['password'] = $config['password'];
            }
            
            $this->client = new PredisClient($predisConfig);
            $this->client->ping();
            $this->useNativeRedis = false;
        } catch (Exception $e) {
            throw new Exception('Failed to connect to Redis: ' . $e->getMessage());
        }
    }

    public function queueName(): string
    {
        $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        return "simple-php_{$phpVersion}";
    }

    public function pushMultiple(array $values): array
    {
        try {
            $queueName = $this->queueName();
            
            if ($this->useNativeRedis) {
                $this->client->rPush($queueName, ...$values);
                $length = $this->client->lLen($queueName);
            } else {
                $this->client->rpush($queueName, $values);
                $length = $this->client->llen($queueName);
            }
            
            return [
                'ok' => true,
                'count' => count($values),
                'new_length' => $length
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function pushSingle(string $value): array
    {
        try {
            $queueName = $this->queueName();
            
            if ($this->useNativeRedis) {
                $this->client->rPush($queueName, $value);
                $length = $this->client->lLen($queueName);
            } else {
                $this->client->rpush($queueName, $value);
                $length = $this->client->llen($queueName);
            }
            
            return [
                'ok' => true,
                'new_length' => $length
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function popSingle(): array
    {
        try {
            $queueName = $this->queueName();
            
            if ($this->useNativeRedis) {
                $value = $this->client->lPop($queueName);
            } else {
                $value = $this->client->lpop($queueName);
            }
            
            return [
                'ok' => true,
                'value' => $value
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function clear(): array
    {
        try {
            $queueName = $this->queueName();
            
            if ($this->useNativeRedis) {
                $oldLength = $this->client->lLen($queueName);
                $this->client->del($queueName);
            } else {
                $oldLength = $this->client->llen($queueName);
                $this->client->del($queueName);
            }
            
            return [
                'ok' => true,
                'old_length' => $oldLength
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
