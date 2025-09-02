<?php

declare(strict_types=1);

namespace Shared\Utils;

/**
 * Shared Queue Manager for APM PHP Examples
 * Provides consistent queue management across all applications
 */
class QueueManager
{
    private array $queue = [];
    private string $queueFile;
    
    public function __construct(string $queueFile = 'queue.json')
    {
        $this->queueFile = sys_get_temp_dir() . '/' . $queueFile;
        $this->loadQueue();
    }
    
    /**
     * Add data to the queue
     */
    public function push(array $data): void
    {
        $this->queue[] = [
            'id' => uniqid(),
            'data' => $data,
            'timestamp' => date('c'),
            'status' => 'pending'
        ];
        
        $this->saveQueue();
    }
    
    /**
     * Get next item from queue
     */
    public function pop(): ?array
    {
        if (empty($this->queue)) {
            return null;
        }
        
        $item = array_shift($this->queue);
        $this->saveQueue();
        
        return $item;
    }
    
    /**
     * Get queue size
     */
    public function size(): int
    {
        return count($this->queue);
    }
    
    /**
     * Clear the queue
     */
    public function clear(): void
    {
        $this->queue = [];
        $this->saveQueue();
    }
    
    /**
     * Get all queue items
     */
    public function getAll(): array
    {
        return $this->queue;
    }
    
    /**
     * Load queue from file
     */
    private function loadQueue(): void
    {
        if (file_exists($this->queueFile)) {
            $content = file_get_contents($this->queueFile);
            $data = json_decode($content, true);
            
            if (is_array($data)) {
                $this->queue = $data;
            }
        }
    }
    
    /**
     * Save queue to file
     */
    private function saveQueue(): void
    {
        file_put_contents(
            $this->queueFile,
            json_encode($this->queue, JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
}
