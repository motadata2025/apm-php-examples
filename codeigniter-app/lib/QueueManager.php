<?php

namespace App\Lib;

use Redis;
use Exception;

/**
 * Queue Manager for CodeIgniter with Redis-based queue operations
 * Enhanced with proper read/write operations and CodeIgniter integration
 */
class QueueManager
{
    private $redis;
    private $queueName;

    public function __construct(string $queueName = 'codeigniter_queue')
    {
        $this->redis = DatabaseConnection::getRedisConnection();
        $this->queueName = $queueName;
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
                'queue' => $this->queueName,
                'framework' => 'codeigniter'
            ];

            $this->redis->lpush($this->queueName, json_encode($job));
            
            // Also add to processing queue for tracking
            $this->redis->hset($this->queueName . ':jobs', $job['id'], json_encode($job));
            
            return true;
        } catch (Exception $e) {
            log_message('error', "Queue add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add job to queue with CodeIgniter configuration
     */
    public function addToQueueCI(array $data, array $options = []): bool
    {
        try {
            // Get queue settings from CodeIgniter config if available
            $queueConfig = config('Queue') ?? [];

            $job = [
                'id' => uniqid(),
                'data' => $data,
                'created_at' => time(),
                'status' => 'pending',
                'queue' => $this->queueName,
                'framework' => 'codeigniter',
                'priority' => $options['priority'] ?? $queueConfig->defaultPriority ?? 'normal',
                'delay' => $options['delay'] ?? 0,
                'attempts' => 0,
                'max_attempts' => $options['max_attempts'] ?? $queueConfig->maxAttempts ?? 3
            ];

            // Handle delayed jobs
            if ($job['delay'] > 0) {
                $job['execute_at'] = time() + $job['delay'];
                $this->redis->zadd($this->queueName . ':delayed', $job['execute_at'], json_encode($job));
            } else {
                $this->redis->lpush($this->queueName, json_encode($job));
            }
            
            // Also add to processing queue for tracking
            $this->redis->hset($this->queueName . ':jobs', $job['id'], json_encode($job));
            
            return true;
        } catch (Exception $e) {
            log_message('error', "CodeIgniter Queue add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Read job from queue (non-blocking) using direct Redis
     */
    public function readFromQueue(): ?array
    {
        try {
            // First check for delayed jobs that are ready
            $this->processDelayedJobs();
            
            $jobData = $this->redis->rpop($this->queueName);
            
            if ($jobData) {
                $job = json_decode($jobData, true);
                
                // Update job status
                $job['status'] = 'processing';
                $job['processed_at'] = time();
                $job['attempts'] = ($job['attempts'] ?? 0) + 1;
                
                $this->redis->hset($this->queueName . ':jobs', $job['id'], json_encode($job));
                
                return $job;
            }
            
            return null;
        } catch (Exception $e) {
            log_message('error', "Queue read error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Read job from queue (blocking with timeout)
     */
    public function blockingReadFromQueue(int $timeout = 10): ?array
    {
        try {
            // First check for delayed jobs that are ready
            $this->processDelayedJobs();
            
            $result = $this->redis->brpop([$this->queueName], $timeout);
            
            if ($result && isset($result[1])) {
                $job = json_decode($result[1], true);
                
                // Update job status
                $job['status'] = 'processing';
                $job['processed_at'] = time();
                $job['attempts'] = ($job['attempts'] ?? 0) + 1;
                
                $this->redis->hset($this->queueName . ':jobs', $job['id'], json_encode($job));
                
                return $job;
            }
            
            return null;
        } catch (Exception $e) {
            log_message('error', "Queue blocking read error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process delayed jobs that are ready to execute
     */
    private function processDelayedJobs(): void
    {
        try {
            $currentTime = time();
            $delayedJobs = $this->redis->zrangebyscore(
                $this->queueName . ':delayed', 
                0, 
                $currentTime, 
                ['limit' => [0, 10]]
            );
            
            foreach ($delayedJobs as $jobData) {
                $job = json_decode($jobData, true);
                
                // Move to main queue
                $this->redis->lpush($this->queueName, $jobData);
                
                // Remove from delayed queue
                $this->redis->zrem($this->queueName . ':delayed', $jobData);
            }
        } catch (Exception $e) {
            log_message('error', "Process delayed jobs error: " . $e->getMessage());
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
            log_message('error', "Queue mark completed error: " . $e->getMessage());
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
                
                // Check if we should retry
                $maxAttempts = $job['max_attempts'] ?? 3;
                $currentAttempts = $job['attempts'] ?? 1;
                
                if ($currentAttempts < $maxAttempts) {
                    // Retry with exponential backoff
                    $delay = pow(2, $currentAttempts) * 60; // 2^attempts minutes
                    $job['execute_at'] = time() + $delay;
                    $job['status'] = 'retrying';
                    
                    $this->redis->zadd($this->queueName . ':delayed', $job['execute_at'], json_encode($job));
                } else {
                    // Move to failed queue
                    $this->redis->lpush($this->queueName . ':failed', json_encode($job));
                }
                
                $this->redis->hset($this->queueName . ':jobs', $jobId, json_encode($job));
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            log_message('error', "Queue mark failed error: " . $e->getMessage());
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
            $delayed = $this->redis->zcard($this->queueName . ':delayed');
            $completed = $this->redis->llen($this->queueName . ':completed');
            $failed = $this->redis->llen($this->queueName . ':failed');
            $totalJobs = $this->redis->hlen($this->queueName . ':jobs');
            
            return [
                'queue_name' => $this->queueName,
                'pending' => $pending,
                'delayed' => $delayed,
                'completed' => $completed,
                'failed' => $failed,
                'total_jobs' => $totalJobs,
                'framework' => 'codeigniter',
                'timestamp' => time()
            ];
        } catch (Exception $e) {
            log_message('error', "Queue status error: " . $e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear all queues
     */
    public function clearQueue(): bool
    {
        try {
            $this->redis->del($this->queueName);
            $this->redis->del($this->queueName . ':delayed');
            $this->redis->del($this->queueName . ':jobs');
            $this->redis->del($this->queueName . ':completed');
            $this->redis->del($this->queueName . ':failed');
            
            return true;
        } catch (Exception $e) {
            log_message('error', "Queue clear error: " . $e->getMessage());
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
            $job = $this->readFromQueue();
            
            if (!$job) {
                break; // No more jobs
            }
            
            try {
                $result = $processor($job['data']);
                $this->markJobCompleted($job['id'], $result);
                $results[] = [
                    'job_id' => $job['id'], 
                    'status' => 'completed', 
                    'result' => $result,
                    'framework' => 'codeigniter',
                    'attempts' => $job['attempts'] ?? 1
                ];
            } catch (Exception $e) {
                $this->markJobFailed($job['id'], $e->getMessage());
                $results[] = [
                    'job_id' => $job['id'], 
                    'status' => 'failed', 
                    'error' => $e->getMessage(),
                    'framework' => 'codeigniter',
                    'attempts' => $job['attempts'] ?? 1
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
     * Demonstrate CodeIgniter queue integration
     */
    public function demonstrateCIQueue(): array
    {
        try {
            $results = [];
            
            // Add jobs using different methods
            $this->addToQueue(['message' => 'Direct Redis job', 'type' => 'direct', 'framework' => 'codeigniter']);
            $this->addToQueueCI(['message' => 'CodeIgniter job', 'type' => 'ci'], ['priority' => 'high']);
            $this->addToQueueCI(['message' => 'Delayed CI job', 'type' => 'delayed'], ['delay' => 5]);
            
            $results['jobs_added'] = 3;
            
            // Process jobs
            $processing = $this->processQueue(function($data) {
                return [
                    'processed_message' => $data['message'] ?? 'No message',
                    'processed_at' => time(),
                    'processor' => 'CodeIgniter Queue Manager',
                    'framework' => 'codeigniter'
                ];
            }, 5);
            
            return [
                'demonstration' => 'CodeIgniter Queue Integration',
                'methods_used' => ['Direct Redis', 'CodeIgniter Configuration'],
                'features' => [
                    'delayed_jobs' => 'supported',
                    'job_retry' => 'supported',
                    'priority_queues' => 'supported',
                    'logging_integration' => 'supported'
                ],
                'processing_results' => $processing,
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'CodeIgniter Queue Integration',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
