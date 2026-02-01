<?php

namespace App\Jobs;

use App\Models\Design;
use App\Services\VersionControlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateVersionSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Design $design,
        public ?int $userId = null,
        public ?string $versionName = null,
        public ?string $description = null,
        public array $tags = [],
        public bool $isAutoVersion = false
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(VersionControlService $service): void
    {
        Log::info("Creating version snapshot for design: {$this->design->id}");

        try {
            $version = $service->createVersion(
                $this->design,
                $this->userId,
                $this->versionName,
                $this->description,
                $this->tags,
                $this->isAutoVersion
            );

            Log::info("Version snapshot created successfully", [
                'version_id' => $version->id,
                'version_number' => $version->version_number,
            ]);

        } catch (\Exception $e) {
            Log::error("Version snapshot creation failed for design: {$this->design->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Version snapshot job failed permanently for design: {$this->design->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
