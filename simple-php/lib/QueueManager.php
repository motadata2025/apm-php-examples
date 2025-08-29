<?php

namespace SimplePhp\Lib;

use Redis;
use Exception;

/**
 * Queue Manager for Redis-based queue operations
 * Enhanced with proper read/write operations
 */
class QueueManager
{
    private $redis;
    private $queueName;

    public function __construct(string $queueName = 'default_queue')
    {
        $this->redis = DatabaseConnection::getRedisConnection();
        $this->queueName = $queueName;
    }

    /**
     * Add job to queue
     */
    public function addToQueue(array $data): bool
    {
        try {
            $job = [
                'id' => uniqid(),
                'data' => $data,
                'created_at' => time(),
                'status' => 'pending'
            ];

            $this->redis->lpush($this->queueName, json_encode($job));
            
            // Also add to processing queue for tracking
            $this->redis->hset($this->queueName . ':jobs', $job['id'], json_encode($job));
            
            return true;
        } catch (Exception $e) {
            error_log("Queue add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Read job from queue (non-blocking)
     */
    public function readFromQueue(): ?array
    {
        try {
            $jobData = $this->redis->rpop($this->queueName);
            
            if ($jobData) {
                $job = json_decode($jobData, true);
                
                // Update job status
                $job['status'] = 'processing';
                $job['processed_at'] = time();
                
                $this->redis->hset($this->queueName . ':jobs', $job['id'], json_encode($job));
                
                return $job;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Queue read error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Read job from queue (blocking with timeout)
     */
    public function blockingReadFromQueue(int $timeout = 10): ?array
    {
        try {
            $result = $this->redis->brpop([$this->queueName], $timeout);
            
            if ($result && isset($result[1])) {
                $job = json_decode($result[1], true);
                
                // Update job status
                $job['status'] = 'processing';
                $job['processed_at'] = time();
                
                $this->redis->hset($this->queueName . ':jobs', $job['id'], json_encode($job));
                
                return $job;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Queue blocking read error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark job as completed
     */
    public function markJobCompleted(string $jobId, array $result = []): bool
    {
        try {
            $jobData = $this->redis->hget($this->queueName . ':jobs', $jobId);
            
            if ($jobData) {
                $job = json_decode($jobData, true);
                $job['status'] = 'completed';
                $job['completed_at'] = time();
                $job['result'] = $result;
                
                $this->redis->hset($this->queueName . ':jobs', $jobId, json_encode($job));
                
                // Move to completed queue
                $this->redis->lpush($this->queueName . ':completed', json_encode($job));
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Queue mark completed error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark job as failed
     */
    public function markJobFailed(string $jobId, string $error): bool
    {
        try {
            $jobData = $this->redis->hget($this->queueName . ':jobs', $jobId);
            
            if ($jobData) {
                $job = json_decode($jobData, true);
                $job['status'] = 'failed';
                $job['failed_at'] = time();
                $job['error'] = $error;
                
                $this->redis->hset($this->queueName . ':jobs', $jobId, json_encode($job));
                
                // Move to failed queue
                $this->redis->lpush($this->queueName . ':failed', json_encode($job));
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Queue mark failed error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get queue statistics
     */
    public function getQueueStatus(): array
    {
        try {
            $pending = $this->redis->llen($this->queueName);
            $completed = $this->redis->llen($this->queueName . ':completed');
            $failed = $this->redis->llen($this->queueName . ':failed');
            $totalJobs = $this->redis->hlen($this->queueName . ':jobs');
            
            return [
                'queue_name' => $this->queueName,
                'pending' => $pending,
                'completed' => $completed,
                'failed' => $failed,
                'total_jobs' => $totalJobs,
                'timestamp' => time()
            ];
        } catch (Exception $e) {
            error_log("Queue status error: " . $e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear all queues
     */
    public function clearAllQueues(): bool
    {
        try {
            $this->redis->del($this->queueName);
            $this->redis->del($this->queueName . ':jobs');
            $this->redis->del($this->queueName . ':completed');
            $this->redis->del($this->queueName . ':failed');

            return true;
        } catch (Exception $e) {
            error_log("Queue clear error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent jobs
     */
    public function getRecentJobs(int $limit = 10): array
    {
        try {
            $jobs = [];
            
            // Get recent completed jobs
            $completed = $this->redis->lrange($this->queueName . ':completed', 0, $limit - 1);
            foreach ($completed as $jobData) {
                $jobs[] = json_decode($jobData, true);
            }
            
            // Get recent failed jobs
            $failed = $this->redis->lrange($this->queueName . ':failed', 0, $limit - 1);
            foreach ($failed as $jobData) {
                $jobs[] = json_decode($jobData, true);
            }
            
            // Sort by timestamp
            usort($jobs, function($a, $b) {
                return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0);
            });
            
            return array_slice($jobs, 0, $limit);
        } catch (Exception $e) {
            error_log("Queue recent jobs error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Process queue with callback
     */
    public function processQueue(callable $processor, int $maxJobs = 10): array
    {
        $results = [];
        $processed = 0;
        
        while ($processed < $maxJobs) {
            $job = $this->readFromQueue();
            
            if (!$job) {
                break; // No more jobs
            }
            
            try {
                $result = $processor($job['data']);
                $this->markJobCompleted($job['id'], $result);
                $results[] = ['job_id' => $job['id'], 'status' => 'completed', 'result' => $result];
            } catch (Exception $e) {
                $this->markJobFailed($job['id'], $e->getMessage());
                $results[] = ['job_id' => $job['id'], 'status' => 'failed', 'error' => $e->getMessage()];
            }
            
            $processed++;
        }
        
        return [
            'processed' => $processed,
            'results' => $results,
            'queue_status' => $this->getQueueStatus()
        ];
    }

    /**
     * Demo function for queue operations
     */
    public function demo(): array
    {
        $results = [];

        // Add some demo data
        $demoData = [
            ['name' => 'Demo User 1', 'email' => 'demo1@example.com', 'action' => 'process_user'],
            ['name' => 'Demo User 2', 'email' => 'demo2@example.com', 'action' => 'send_email'],
            ['name' => 'Demo User 3', 'email' => 'demo3@example.com', 'action' => 'generate_report']
        ];

        foreach ($demoData as $data) {
            $this->addToQueue($data);
        }

        $results['demo_data_added'] = count($demoData);
        $results['queue_status'] = $this->getQueueStatus();
        $results['message'] = 'Demo queue operations completed';

        return $results;
    }

    /**
     * Enqueue data (alias for addToQueue)
     */
    public function enqueue(string $queueName, array $data): bool
    {
        // Create a new instance with the specified queue name if different
        if ($queueName !== $this->queueName) {
            $queueManager = new QueueManager($queueName);
            return $queueManager->addToQueue($data);
        }

        return $this->addToQueue($data);
    }

    /**
     * Dequeue data (alias for readFromQueue)
     */
    public function dequeue(string $queueName): ?array
    {
        // Create a new instance with the specified queue name if different
        if ($queueName !== $this->queueName) {
            $queueManager = new QueueManager($queueName);
            return $queueManager->readFromQueue();
        }

        return $this->readFromQueue();
    }

    /**
     * Clear specific queue
     */
    public function clearQueue(string $queueName): bool
    {
        // Create a new instance with the specified queue name if different
        if ($queueName !== $this->queueName) {
            $queueManager = new QueueManager($queueName);
            return $queueManager->clearAllQueues();
        }

        return $this->clearAllQueues();
    }

    /**
     * Get all data from queue without removing
     */
    public function getAllQueueData(string $queueName): array
    {
        try {
            $queueKey = $queueName;
            $allData = $this->redis->lrange($queueKey, 0, -1);

            $results = [];
            foreach ($allData as $jobData) {
                $job = json_decode($jobData, true);
                if ($job) {
                    $results[] = $job;
                }
            }

            return $results;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Generate random demo data for queue operations
     */
    public function generateRandomData(): array
    {
        $names = ['Alice Johnson', 'Bob Smith', 'Carol Davis', 'David Wilson', 'Eva Brown', 'Frank Miller', 'Grace Lee', 'Henry Taylor'];
        $actions = ['process_order', 'send_notification', 'generate_report', 'backup_data', 'sync_database', 'send_email'];
        $priorities = ['high', 'medium', 'low'];
        $departments = ['Sales', 'Marketing', 'Engineering', 'Support', 'Finance'];

        $randomName = $names[array_rand($names)];
        $randomAction = $actions[array_rand($actions)];
        $randomPriority = $priorities[array_rand($priorities)];
        $randomDepartment = $departments[array_rand($departments)];

        return [
            'id' => uniqid('task_'),
            'name' => $randomName,
            'email' => strtolower(str_replace(' ', '.', $randomName)) . '@company.com',
            'action' => $randomAction,
            'priority' => $randomPriority,
            'department' => $randomDepartment,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 minute')),
            'data' => [
                'amount' => rand(100, 10000),
                'reference' => 'REF-' . rand(10000, 99999),
                'notes' => 'Auto-generated task for ' . $randomAction
            ]
        ];
    }
}
