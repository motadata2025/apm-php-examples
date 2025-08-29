<?php

namespace App\Lib;

use Redis;
use Exception;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis as LaravelRedis;

/**
 * Queue Manager for Laravel with Redis-based queue operations
 * Enhanced with proper read/write operations using both Laravel and direct Redis
 */
class QueueManager
{
    private $redis;
    private $queueName;

    public function __construct(string $queueName = 'laravel_queue')
    {
        $this->redis = DatabaseConnection::getRedisConnection();
        $this->queueName = $queueName;
    }

    /**
     * Add job to queue using Laravel Queue
     */
    public function addToQueueLaravel(array $data, string $queue = 'default'): bool
    {
        try {
            $job = new \App\Jobs\ProcessDataJob($data);
            Queue::push($job, '', $queue);
            return true;
        } catch (Exception $e) {
            error_log("Laravel Queue add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add job to queue using direct Redis
     */
    public function addToQueue(array $data): bool
    {
        try {
            $job = [
                'id' => uniqid(),
                'data' => $data,
                'created_at' => time(),
                'status' => 'pending',
                'queue' => $this->queueName
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
     * Add job to queue using Laravel Redis facade
     */
    public function addToQueueLaravelRedis(array $data): bool
    {
        try {
            $job = [
                'id' => uniqid(),
                'data' => $data,
                'created_at' => time(),
                'status' => 'pending',
                'queue' => $this->queueName . '_laravel'
            ];

            LaravelRedis::lpush($this->queueName . '_laravel', json_encode($job));
            LaravelRedis::hset($this->queueName . '_laravel:jobs', $job['id'], json_encode($job));
            
            return true;
        } catch (Exception $e) {
            error_log("Laravel Redis Queue add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Read job from queue (non-blocking) using direct Redis
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
     * Read job from queue using Laravel Redis facade
     */
    public function readFromQueueLaravelRedis(): ?array
    {
        try {
            $jobData = LaravelRedis::rpop($this->queueName . '_laravel');
            
            if ($jobData) {
                $job = json_decode($jobData, true);
                
                // Update job status
                $job['status'] = 'processing';
                $job['processed_at'] = time();
                
                LaravelRedis::hset($this->queueName . '_laravel:jobs', $job['id'], json_encode($job));
                
                return $job;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Laravel Redis Queue read error: " . $e->getMessage());
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
     * Get queue statistics for both direct and Laravel Redis
     */
    public function getQueueStatus(): array
    {
        try {
            // Direct Redis stats
            $pending = $this->redis->llen($this->queueName);
            $completed = $this->redis->llen($this->queueName . ':completed');
            $failed = $this->redis->llen($this->queueName . ':failed');
            $totalJobs = $this->redis->hlen($this->queueName . ':jobs');
            
            // Laravel Redis stats
            $laravelPending = LaravelRedis::llen($this->queueName . '_laravel');
            $laravelCompleted = LaravelRedis::llen($this->queueName . '_laravel:completed');
            $laravelFailed = LaravelRedis::llen($this->queueName . '_laravel:failed');
            $laravelTotalJobs = LaravelRedis::hlen($this->queueName . '_laravel:jobs');
            
            return [
                'direct_redis' => [
                    'queue_name' => $this->queueName,
                    'pending' => $pending,
                    'completed' => $completed,
                    'failed' => $failed,
                    'total_jobs' => $totalJobs
                ],
                'laravel_redis' => [
                    'queue_name' => $this->queueName . '_laravel',
                    'pending' => $laravelPending,
                    'completed' => $laravelCompleted,
                    'failed' => $laravelFailed,
                    'total_jobs' => $laravelTotalJobs
                ],
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
     * Clear all queues (both direct and Laravel Redis)
     */
    public function clearQueue(): bool
    {
        try {
            // Clear direct Redis queues
            $this->redis->del($this->queueName);
            $this->redis->del($this->queueName . ':jobs');
            $this->redis->del($this->queueName . ':completed');
            $this->redis->del($this->queueName . ':failed');
            
            // Clear Laravel Redis queues
            LaravelRedis::del($this->queueName . '_laravel');
            LaravelRedis::del($this->queueName . '_laravel:jobs');
            LaravelRedis::del($this->queueName . '_laravel:completed');
            LaravelRedis::del($this->queueName . '_laravel:failed');
            
            return true;
        } catch (Exception $e) {
            error_log("Queue clear error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process queue with callback (demonstrating read operations)
     */
    public function processQueue(callable $processor, int $maxJobs = 10): array
    {
        $results = [];
        $processed = 0;
        
        while ($processed < $maxJobs) {
            // Try to read from direct Redis queue first
            $job = $this->readFromQueue();
            
            // If no job in direct queue, try Laravel Redis queue
            if (!$job) {
                $job = $this->readFromQueueLaravelRedis();
            }
            
            if (!$job) {
                break; // No more jobs in either queue
            }
            
            try {
                $result = $processor($job['data']);
                $this->markJobCompleted($job['id'], $result);
                $results[] = [
                    'job_id' => $job['id'], 
                    'status' => 'completed', 
                    'result' => $result,
                    'queue_type' => $job['queue'] ?? 'unknown'
                ];
            } catch (Exception $e) {
                $this->markJobFailed($job['id'], $e->getMessage());
                $results[] = [
                    'job_id' => $job['id'], 
                    'status' => 'failed', 
                    'error' => $e->getMessage(),
                    'queue_type' => $job['queue'] ?? 'unknown'
                ];
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
     * Demonstrate Laravel Queue integration
     */
    public function demonstrateLaravelQueue(): array
    {
        try {
            // Add jobs using different methods
            $this->addToQueue(['message' => 'Direct Redis job', 'type' => 'direct']);
            $this->addToQueueLaravelRedis(['message' => 'Laravel Redis job', 'type' => 'laravel_redis']);
            
            // Process jobs
            $results = $this->processQueue(function($data) {
                return [
                    'processed_message' => $data['message'] ?? 'No message',
                    'processed_at' => time(),
                    'processor' => 'Laravel Queue Manager'
                ];
            }, 5);
            
            return [
                'demonstration' => 'Laravel Queue Integration',
                'methods_used' => ['Direct Redis', 'Laravel Redis Facade'],
                'processing_results' => $results,
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'Laravel Queue Integration',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
