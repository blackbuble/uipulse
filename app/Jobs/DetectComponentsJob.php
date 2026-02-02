<?php

namespace App\Jobs;

use App\Models\Design;
use App\Services\ComponentDetectionService;
use Filament\Notifications\Notification;
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
            // Validate design has required data
            if (!$this->design->figma_file_key && empty($this->design->metadata['nodes'])) {
                $this->sendNotification(
                    'Component Detection Failed',
                    'Design must have a Figma URL or uploaded design data. Please add Figma file key or upload design metadata.',
                    'danger'
                );

                $this->design->update(['status' => 'failed']);
                return;
            }

            // Check if Figma token is configured when using Figma URL
            if ($this->design->figma_file_key && !config('services.figma.token')) {
                $this->sendNotification(
                    'Figma Token Not Configured',
                    'Please add FIGMA_ACCESS_TOKEN to your .env file to enable Figma integration. See documentation for setup instructions.',
                    'warning'
                );
            }

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

            // Send success notification
            if (count($components) > 0) {
                $this->sendNotification(
                    'Components Detected Successfully',
                    "Found " . count($components) . " components in the design. View them in the 'Detected Components' tab.",
                    'success'
                );
            } else {
                $this->sendNotification(
                    'No Components Found',
                    'AI could not detect any components in this design. Make sure the design has a valid Figma URL or contains component data.',
                    'warning'
                );
            }

            Log::info("Component detection completed for design: {$this->design->id}", [
                'components_count' => count($components),
            ]);

        } catch (\Exception $e) {
            Log::error("Component detection failed for design: {$this->design->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Send error notification
            $this->sendNotification(
                'Component Detection Failed',
                'Error: ' . $e->getMessage(),
                'danger'
            );

            // Update design status to failed
            $this->design->update(['status' => 'failed']);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Send notification to user.
     */
    private function sendNotification(string $title, string $body, string $status = 'info'): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->status($status)
            ->persistent()
            ->sendToDatabase($this->design->project->user ?? auth()->user());
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

        $this->sendNotification(
            'Component Detection Failed Permanently',
            'The component detection job failed after multiple attempts. Please check the logs for details.',
            'danger'
        );
    }
}
