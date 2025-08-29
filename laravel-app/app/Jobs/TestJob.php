<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestJob implements ShouldQueue
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
        Log::info('TestJob executed', [
            'data' => $this->data,
            'timestamp' => now()->toISOString()
        ]);

        // Simulate some processing
        sleep(1);

        Log::info('TestJob completed', [
            'message' => $this->data['message'] ?? 'No message',
            'processed_at' => now()->toISOString()
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TestJob failed', [
            'data' => $this->data,
            'error' => $exception->getMessage(),
            'failed_at' => now()->toISOString()
        ]);
    }
}