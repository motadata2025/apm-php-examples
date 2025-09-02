<?php

declare(strict_types=1);

namespace Shared\Utils;

class QueueManager
{
    public function push(array $data): void
    {
        // Queue implementation
    }

    public function enqueue(string $queue, array $data): void
    {
        // Queue implementation
    }

    public function pop(): ?array
    {
        return null;
    }
}
