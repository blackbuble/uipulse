<?php

namespace App\Jobs;

use App\Models\Design;
use App\Services\FigmaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateDesignFromFigma implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

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
    public function handle(FigmaService $figmaService): void
    {
        Log::info("Syncing design from Figma: {$this->design->id}");

        try {
            // Fetch latest data from Figma
            $figmaData = $figmaService->getFile($this->design->figma_file_key);

            if (!$figmaData) {
                throw new \Exception("Failed to fetch Figma file");
            }

            // Update design metadata
            $this->design->update([
                'metadata' => array_merge($this->design->metadata ?? [], [
                    'last_synced_at' => now()->toISOString(),
                    'figma_version' => $figmaData['version'] ?? null,
                    'figma_last_modified' => $figmaData['lastModified'] ?? null,
                ]),
            ]);

            // Re-run component detection if needed
            if ($this->shouldRedetectComponents($figmaData)) {
                \App\Jobs\DetectComponentsJob::dispatch($this->design);
            }

            Log::info("Design synced successfully from Figma", [
                'design_id' => $this->design->id,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to sync design from Figma: {$this->design->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Determine if components should be re-detected.
     */
    private function shouldRedetectComponents(array $figmaData): bool
    {
        // Check if there are significant changes
        $lastVersion = $this->design->metadata['figma_version'] ?? null;
        $currentVersion = $figmaData['version'] ?? null;

        return $lastVersion !== $currentVersion;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Figma sync job failed permanently for design: {$this->design->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
