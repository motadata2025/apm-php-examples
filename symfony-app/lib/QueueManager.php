<?php

namespace App\Lib;

use Redis;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Queue Manager for Symfony with Redis-based queue operations
 * Enhanced with Symfony Messenger integration and proper read/write operations
 */
class QueueManager
{
    private $redis;
    private $queueName;
    private $messageBus;

    public function __construct(string $queueName = 'symfony_queue', MessageBusInterface $messageBus = null)
    {
        $this->redis = DatabaseConnection::getRedisConnection();
        $this->queueName = $queueName;
        $this->messageBus = $messageBus;
    }

    /**
     * Add job to queue using Symfony Messenger
     */
    public function addToQueueMessenger(array $data, int $delay = 0): bool
    {
        if (!$this->messageBus) {
            return false;
        }

        try {
            $message = new SymfonyQueueMessage($data);
            $envelope = new Envelope($message);
            
            if ($delay > 0) {
                $envelope = $envelope->with(new DelayStamp($delay * 1000)); // Convert to milliseconds
            }
            
            $this->messageBus->dispatch($envelope);
            return true;
        } catch (Exception $e) {
            error_log("Symfony Messenger add error: " . $e->getMessage());
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
                'queue' => $this->queueName,
                'framework' => 'symfony'
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
                'framework' => 'symfony',
                'messenger_available' => $this->messageBus !== null,
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
    public function clearQueue(): bool
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
                    'framework' => 'symfony'
                ];
            } catch (Exception $e) {
                $this->markJobFailed($job['id'], $e->getMessage());
                $results[] = [
                    'job_id' => $job['id'], 
                    'status' => 'failed', 
                    'error' => $e->getMessage(),
                    'framework' => 'symfony'
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
     * Demonstrate Symfony Messenger integration
     */
    public function demonstrateSymfonyMessenger(): array
    {
        try {
            $results = [];
            
            // Add jobs using different methods
            $this->addToQueue(['message' => 'Direct Redis job', 'type' => 'direct', 'framework' => 'symfony']);
            
            if ($this->messageBus) {
                $this->addToQueueMessenger(['message' => 'Symfony Messenger job', 'type' => 'messenger']);
                $this->addToQueueMessenger(['message' => 'Delayed Symfony job', 'type' => 'delayed'], 5);
                $results['messenger_jobs'] = 'added';
            } else {
                $results['messenger_jobs'] = 'not_available';
            }
            
            // Process jobs
            $processing = $this->processQueue(function($data) {
                return [
                    'processed_message' => $data['message'] ?? 'No message',
                    'processed_at' => time(),
                    'processor' => 'Symfony Queue Manager',
                    'framework' => 'symfony'
                ];
            }, 5);
            
            return [
                'demonstration' => 'Symfony Messenger Integration',
                'methods_used' => $this->messageBus ? 
                    ['Direct Redis', 'Symfony Messenger'] : 
                    ['Direct Redis'],
                'processing_results' => $processing,
                'messenger_available' => $this->messageBus !== null,
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'Symfony Messenger Integration',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Simple message class for Symfony Messenger
 */
class SymfonyQueueMessage
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
