<?php

namespace Shared\Utils;

use Redis;
use Exception;

/**
 * Queue Manager for Redis-based queue operations
 * Provides methods for adding, reading, and clearing queue data
 */
class QueueManager
{
    private $redis;
    private $queuePrefix;

    public function __construct(string $queuePrefix = 'apm_queue')
    {
        $this->redis = DatabaseConnection::getRedisConnection();
        $this->queuePrefix = $queuePrefix;
    }

    /**
     * Add data to queue (FIFO)
     */
    public function enqueue(string $queueName, array $data): bool
    {
        try {
            $queueKey = $this->getQueueKey($queueName);
            $serializedData = json_encode([
                'data' => $data,
                'timestamp' => time(),
                'id' => uniqid()
            ]);

            $result = $this->redis->rPush($queueKey, $serializedData);
            return $result > 0;
        } catch (Exception $e) {
            throw new Exception("Failed to enqueue data: " . $e->getMessage());
        }
    }

    /**
     * Read data from queue (FIFO)
     */
    public function dequeue(string $queueName): ?array
    {
        try {
            $queueKey = $this->getQueueKey($queueName);
            $serializedData = $this->redis->lPop($queueKey);

            if ($serializedData === false || $serializedData === null) {
                return null;
            }

            $data = json_decode($serializedData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Failed to decode queue data: " . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            throw new Exception("Failed to dequeue data: " . $e->getMessage());
        }
    }

    /**
     * Peek at the next item without removing it
     */
    public function peek(string $queueName): ?array
    {
        try {
            $queueKey = $this->getQueueKey($queueName);
            $serializedData = $this->redis->lIndex($queueKey, 0);

            if ($serializedData === false || $serializedData === null) {
                return null;
            }

            $data = json_decode($serializedData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Failed to decode queue data: " . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            throw new Exception("Failed to peek queue data: " . $e->getMessage());
        }
    }

    /**
     * Get queue length
     */
    public function getQueueLength(string $queueName): int
    {
        try {
            $queueKey = $this->getQueueKey($queueName);
            return $this->redis->lLen($queueKey);
        } catch (Exception $e) {
            throw new Exception("Failed to get queue length: " . $e->getMessage());
        }
    }

    /**
     * Clear all data from queue
     */
    public function clearQueue(string $queueName): bool
    {
        try {
            $queueKey = $this->getQueueKey($queueName);
            $result = $this->redis->del($queueKey);
            return $result > 0;
        } catch (Exception $e) {
            throw new Exception("Failed to clear queue: " . $e->getMessage());
        }
    }

    /**
     * Get all items from queue without removing them
     */
    public function getAllItems(string $queueName, int $limit = 100): array
    {
        try {
            $queueKey = $this->getQueueKey($queueName);
            $items = $this->redis->lRange($queueKey, 0, $limit - 1);

            $decodedItems = [];
            foreach ($items as $item) {
                $decoded = json_decode($item, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decodedItems[] = $decoded;
                }
            }

            return $decodedItems;
        } catch (Exception $e) {
            throw new Exception("Failed to get all queue items: " . $e->getMessage());
        }
    }

    /**
     * Add multiple items to queue in batch
     */
    public function enqueueBatch(string $queueName, array $dataArray): int
    {
        try {
            $queueKey = $this->getQueueKey($queueName);
            $serializedItems = [];

            foreach ($dataArray as $data) {
                $serializedItems[] = json_encode([
                    'data' => $data,
                    'timestamp' => time(),
                    'id' => uniqid()
                ]);
            }

            if (empty($serializedItems)) {
                return 0;
            }

            return $this->redis->rPush($queueKey, ...$serializedItems);
        } catch (Exception $e) {
            throw new Exception("Failed to enqueue batch data: " . $e->getMessage());
        }
    }

    /**
     * Demo queue operations
     */
    public function demo(): array
    {
        $results = [];
        $queueName = 'demo_queue';

        try {
            // Clear queue first
            $this->clearQueue($queueName);

            // Add sample data
            $sampleData = [
                ['message' => 'Hello World', 'priority' => 1],
                ['message' => 'Queue Test', 'priority' => 2],
                ['message' => 'APM Demo', 'priority' => 3]
            ];

            $enqueued = $this->enqueueBatch($queueName, $sampleData);
            $results['enqueued'] = $enqueued;

            // Get queue length
            $length = $this->getQueueLength($queueName);
            $results['queue_length'] = $length;

            // Peek at first item
            $peeked = $this->peek($queueName);
            $results['peeked'] = $peeked;

            // Dequeue one item
            $dequeued = $this->dequeue($queueName);
            $results['dequeued'] = $dequeued;

            // Get remaining items
            $remaining = $this->getAllItems($queueName);
            $results['remaining_items'] = $remaining;

            $results['status'] = 'success';

        } catch (Exception $e) {
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(string $queueName): array
    {
        try {
            $queueKey = $this->getQueueKey($queueName);
            $length = $this->redis->lLen($queueKey);

            $stats = [
                'queue_name' => $queueName,
                'queue_key' => $queueKey,
                'length' => $length,
                'is_empty' => $length === 0
            ];

            if ($length > 0) {
                $firstItem = $this->peek($queueName);
                $stats['first_item_timestamp'] = $firstItem['timestamp'] ?? null;
                $stats['first_item_age_seconds'] = $firstItem['timestamp'] ? (time() - $firstItem['timestamp']) : null;
            }

            return $stats;
        } catch (Exception $e) {
            return [
                'queue_name' => $queueName,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get formatted queue key
     */
    private function getQueueKey(string $queueName): string
    {
        return $this->queuePrefix . ':' . $queueName;
    }
}