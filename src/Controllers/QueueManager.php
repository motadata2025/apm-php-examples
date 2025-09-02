<?php

declare(strict_types=1);

namespace App\Controllers;

class QueueManager
{
    public function push(array $data): void
    {
        // Queue implementation
    }

    public function enqueue(string $queue, array $data): bool
    {
        // Queue implementation
        return true;
    }

    public function dequeue(string $queue): ?array
    {
        return null;
    }

    public function clearQueue(string $queue): bool
    {
        return true;
    }

    public function pop(): ?array
    {
        return null;
    }
}
