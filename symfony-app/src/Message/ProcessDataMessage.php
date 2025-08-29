<?php

namespace App\Message;

class ProcessDataMessage
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMessage(): string
    {
        return $this->data['message'] ?? 'No message provided';
    }

    public function getPriority(): int
    {
        return $this->data['priority'] ?? 1;
    }

    public function getTimestamp(): string
    {
        return $this->data['timestamp'] ?? (new \DateTime())->format('c');
    }
}