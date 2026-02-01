<?php

namespace App\Jobs;

use App\Models\Design;
use App\Services\ComponentDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectComponentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Design $design
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(ComponentDetectionService $service): void
    {
        Log::info("Starting component detection job for design: {$this->design->id}");

        try {
            // Update design status
            $this->design->update(['status' => 'processing']);

            // Detect components
            $components = $service->detectComponents($this->design);

            // Update design status
            $this->design->update([
                'status' => 'completed',
                'metadata' => array_merge($this->design->metadata ?? [], [
                    'components_detected' => count($components),
                    'last_detection_at' => now()->toISOString(),
                ])
            ]);

            Log::info("Component detection completed for design: {$this->design->id}", [
                'components_count' => count($components),
            ]);

        } catch (\Exception $e) {
            Log::error("Component detection failed for design: {$this->design->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update design status to failed
            $this->design->update(['status' => 'failed']);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Component detection job failed permanently for design: {$this->design->id}", [
            'error' => $exception->getMessage(),
        ]);

        $this->design->update(['status' => 'failed']);
    }
}
