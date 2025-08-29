<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Shared\Utils\QueueManager;

class ProcessDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessDataJob started', [
            'data' => $this->data,
            'started_at' => now()->toISOString()
        ]);

        try {
            // Process the data and store in shared queue
            $queueManager = new QueueManager();
            $processedData = [
                'original_data' => $this->data,
                'processed_by' => 'Laravel ProcessDataJob',
                'processed_at' => now()->toISOString(),
                'status' => 'completed'
            ];

            $queueManager->enqueue('laravel_processed', $processedData);

            Log::info('ProcessDataJob completed successfully', [
                'processed_data' => $processedData,
                'completed_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessDataJob processing failed', [
                'data' => $this->data,
                'error' => $e->getMessage(),
                'failed_at' => now()->toISOString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDataJob failed completely', [
            'data' => $this->data,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'failed_at' => now()->toISOString()
        ]);
    }
}