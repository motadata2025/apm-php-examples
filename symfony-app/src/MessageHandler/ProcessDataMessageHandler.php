<?php

namespace App\MessageHandler;

use App\Message\ProcessDataMessage;
use Psr\Log\LoggerInterface;
use Shared\Utils\QueueManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessDataMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ProcessDataMessage $message): void
    {
        $this->logger->info('ProcessDataMessageHandler started', [
            'data' => $message->getData(),
            'started_at' => (new \DateTime())->format('c')
        ]);

        try {
            // Process the data and store in shared queue
            $queueManager = new QueueManager();
            $processedData = [
                'original_data' => $message->getData(),
                'processed_by' => 'Symfony ProcessDataMessageHandler',
                'processed_at' => (new \DateTime())->format('c'),
                'status' => 'completed',
                'message' => $message->getMessage(),
                'priority' => $message->getPriority()
            ];

            $queueManager->enqueue('symfony_processed', $processedData);

            $this->logger->info('ProcessDataMessageHandler completed successfully', [
                'processed_data' => $processedData,
                'completed_at' => (new \DateTime())->format('c')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('ProcessDataMessageHandler processing failed', [
                'data' => $message->getData(),
                'error' => $e->getMessage(),
                'failed_at' => (new \DateTime())->format('c')
            ]);

            throw $e;
        }
    }
}